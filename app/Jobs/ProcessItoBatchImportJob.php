<?php

namespace App\Jobs;

use App\Models\ItoBatchImport;
use App\Models\ItoBatchItem;
use App\Services\ItoBatchImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessItoBatchImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(
        public int $itoBatchImportId
    ) {
        $this->onQueue('default');
    }

    public function handle(ItoBatchImportService $service): void
    {
        $batch = ItoBatchImport::query()->find($this->itoBatchImportId);
        if (! $batch) {
            return;
        }

        $batch->update(['status' => 'processing']);

        $sourceAbsolute = storage_path('app/public/'.$batch->stored_path);
        if (! is_file($sourceAbsolute)) {
            $batch->update(['status' => 'failed']);

            return;
        }

        try {
            $totalPages = $service->countPages($sourceAbsolute);
        } catch (\Throwable $e) {
            Log::error('ITO batch import: could not read PDF page count', [
                'batch_id' => $batch->id,
                'message' => $e->getMessage(),
            ]);
            $batch->update(['status' => 'failed', 'total_pages' => 0]);

            return;
        }

        $batch->update(['total_pages' => $totalPages]);

        if ($totalPages === 0) {
            $batch->update(['status' => 'failed']);

            return;
        }

        $batchNo = $service->nextBatchNo();
        $matchedCount = 0;
        $failed = false;

        for ($page = 1; $page <= $totalPages; $page++) {
            $item = ItoBatchItem::query()->firstOrCreate(
                [
                    'batch_id' => $batch->id,
                    'page_number' => $page,
                ],
                ['status' => 'pending']
            );

            try {
                $this->processPage($service, $batch, $item, $batchNo);
                if ($item->fresh()?->status === 'matched') {
                    $matchedCount++;
                }
            } catch (\Throwable $e) {
                $failed = true;
                Log::error('ITO batch import: page processing failed', [
                    'batch_id' => $batch->id,
                    'page' => $page,
                    'message' => $e->getMessage(),
                ]);
                $item->update(['status' => 'low_confidence']);
            }
        }

        $batch->refresh();
        $reviewCount = $batch->reviewNeededCount();
        $finalMatched = $batch->matchedCount();

        if ($failed && $finalMatched === 0) {
            $status = 'failed';
        } elseif ($reviewCount > 0) {
            $status = 'partial';
        } else {
            $status = 'processed';
        }

        $batch->update(['status' => $status]);
    }

    private function processPage(ItoBatchImportService $service, ItoBatchImport $batch, ItoBatchItem $item, int $batchNo): void
    {
        $attachedPath = $service->splitPage($batch->stored_path, $item->page_number);
        $item->update(['attached_path' => $attachedPath]);

        $extraction = $service->extractItoNumber($attachedPath);
        $itoNo = $extraction['ito_no'];
        $confidence = $extraction['confidence'];

        $item->update([
            'extracted_ito_no' => $itoNo,
            'confidence' => $confidence,
        ]);

        if ($service->isLowConfidence($itoNo, $confidence)) {
            $item->update(['status' => 'low_confidence']);

            return;
        }

        $normalized = $service->normalizeNumber($itoNo);
        $match = $service->matchDocument($normalized);

        if ($match['status'] === 'not_found') {
            $item->update(['status' => 'not_found']);

            return;
        }

        if ($match['status'] === 'ambiguous') {
            $item->update(['status' => 'ambiguous']);

            return;
        }

        $document = $match['document'];
        if (! $document) {
            $item->update(['status' => 'not_found']);

            return;
        }

        if ($document->attachment) {
            $item->update(['status' => 'ambiguous']);

            return;
        }

        $service->attachPage($item, $document, $batchNo);
        $this->dispatchSignatureVerification($document->id);
    }

    private function dispatchSignatureVerification(int $documentId): void
    {
        try {
            VerifyDocumentSignatureJob::dispatch($documentId);
        } catch (\Throwable $e) {
            Log::warning('ITO batch import: signature verification dispatch failed', [
                'document_id' => $documentId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
