<?php

namespace App\Jobs;

use App\Services\InvoiceImportAttachmentService;
use App\Services\InvoiceImportDraftBuilder;
use App\Services\InvoiceImportSupplierResolver;
use App\Services\OpenRouterInvoiceExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExtractInvoiceFromDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public string $uuid
    ) {}

    public function handle(
        OpenRouterInvoiceExtractionService $extraction,
        InvoiceImportSupplierResolver $supplierResolver,
        InvoiceImportDraftBuilder $draftBuilder
    ): void {
        $key = InvoiceImportAttachmentService::cacheKey($this->uuid);
        $data = Cache::get($key);
        if (! is_array($data)) {
            return;
        }

        $data['status'] = 'processing';
        Cache::put($key, $data, now()->addMinutes(120));

        $path = $data['path'] ?? '';
        $mime = $data['mime'] ?? '';
        if (! $path || ! Storage::disk('local')->exists($path)) {
            $this->failCache($key, 'Uploaded file expired or missing.');

            return;
        }

        $absolute = Storage::disk('local')->path($path);

        try {
            if ($mime === 'application/pdf' || str_ends_with(strtolower($path), '.pdf')) {
                $result = $extraction->extractFromPdf($absolute);
            } else {
                $result = $extraction->extractFromImage($absolute, $mime);
            }

            $supplierMatch = $supplierResolver->resolve($result->supplierNameRaw);

            $userId = (int) ($data['user_id'] ?? 0);
            $user = \App\Models\User::find($userId);
            if (! $user) {
                $this->failCache($key, 'User not found for import.');

                return;
            }

            $draft = $draftBuilder->build($user, $result, $supplierMatch);

            $data['status'] = 'completed';
            $data['draft'] = $draft;
            $data['extraction_confidence'] = $result->confidence;
            $data['extracted_at'] = now()->toIso8601String();
            Cache::put($key, $data, now()->addMinutes(120));
        } catch (\Throwable $e) {
            Log::channel('invoice_import')->error('ExtractInvoiceFromDocumentJob failed', [
                'uuid' => $this->uuid,
                'message' => $e->getMessage(),
            ]);
            $this->failCache($key, $e->getMessage());
        }
    }

    private function failCache(string $key, string $message): void
    {
        $data = Cache::get($key);
        if (! is_array($data)) {
            return;
        }
        $data['status'] = 'failed';
        $data['error'] = $message;
        Cache::put($key, $data, now()->addMinutes(120));
    }
}
