<?php

namespace App\Jobs;

use App\Models\AdditionalDocument;
use App\Models\SignatureMatchResult;
use App\Models\SignatureSpecimen;
use App\Services\SignatureMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyDocumentSignatureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public int $additionalDocumentId
    ) {
        $this->onQueue('default');
    }

    public function handle(SignatureMatchingService $matchingService): void
    {
        $document = AdditionalDocument::with('type')->find($this->additionalDocumentId);
        if (! $document) {
            return;
        }

        if (! $document->requiresSignature()) {
            return;
        }

        if (! $document->attachment) {
            $document->update(['signature_status' => 'skipped']);

            return;
        }

        $projectId = $document->signature_project_id;
        if (! $projectId) {
            $document->update(['signature_status' => 'skipped']);

            return;
        }

        $absolutePath = storage_path('app/public/'.$document->attachment);
        if (! is_file($absolutePath)) {
            Log::warning('VerifyDocumentSignatureJob: attachment missing on disk', [
                'document_id' => $document->id,
                'attachment' => $document->attachment,
            ]);
            $document->update(['signature_status' => 'skipped']);

            return;
        }

        $mime = $this->resolveMime($absolutePath);

        $specimens = SignatureSpecimen::query()
            ->active()
            ->whereHas('projects', fn ($q) => $q->where('projects.id', $projectId))
            ->with('images')
            ->get();

        $candidates = [];
        foreach ($specimens as $specimen) {
            $image = $specimen->images->first();
            if (! $image) {
                continue;
            }

            $imagePath = storage_path('app/public/'.$image->path);
            if (! is_file($imagePath)) {
                continue;
            }

            $candidates[] = [
                'id' => $specimen->id,
                'name' => $specimen->name,
                'image_path' => $imagePath,
            ];
        }

        if (empty($candidates)) {
            $document->update(['signature_status' => 'no_match']);

            return;
        }

        $matchResults = $matchingService->matchCandidates($absolutePath, $mime, $candidates);
        $model = config('services.openrouter.signature_model') ?: config('services.openrouter.model');
        $runAt = now();

        foreach ($matchResults as $result) {
            SignatureMatchResult::create([
                'additional_document_id' => $document->id,
                'specimen_id' => $result['specimen_id'],
                'score' => $result['score'],
                'verdict' => $result['verdict'],
                'model' => (string) $model,
                'raw_response' => $result['raw_response'],
                'created_at' => $runAt,
            ]);
        }

        $bestVerdict = $this->resolveDocumentStatus($matchResults);
        $document->update([
            'signature_status' => $bestVerdict,
            'signature_override_reason' => null,
            'signature_override_by' => null,
            'signature_override_at' => null,
            'signature_checked_by' => null,
            'signature_checked_at' => null,
        ]);
    }

    /**
     * @param  array<int, array{specimen_id: int, score: float|null, verdict: string}>  $matchResults
     */
    private function resolveDocumentStatus(array $matchResults): string
    {
        if (empty($matchResults)) {
            return 'no_match';
        }

        usort($matchResults, function (array $a, array $b): int {
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });

        $best = $matchResults[0];

        return $best['verdict'] ?? 'uncertain';
    }

    private function resolveMime(string $absolutePath): string
    {
        return match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
