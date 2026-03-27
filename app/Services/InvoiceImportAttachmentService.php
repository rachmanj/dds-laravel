<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceImportAttachmentService
{
    public const CACHE_PREFIX = 'invoice_import:';

    public static function cacheKey(string $uuid): string
    {
        return self::CACHE_PREFIX.$uuid;
    }

    /**
     * Snapshot of completed import cache for persisting on the invoice (call before attachFromImport).
     *
     * @return array<string, mixed>|null
     */
    public function getImportExtractionPayload(string $uuid, int $userId): ?array
    {
        $key = self::cacheKey($uuid);
        $data = Cache::get($key);
        if (! is_array($data)) {
            return null;
        }
        if (($data['user_id'] ?? null) !== $userId) {
            return null;
        }
        if (($data['status'] ?? '') !== 'completed') {
            return null;
        }

        return [
            'import_uuid' => $uuid,
            'extracted_at' => $data['extracted_at'] ?? now()->toIso8601String(),
            'draft' => $data['draft'] ?? null,
            'confidence' => $data['extraction_confidence'] ?? null,
            'original_filename' => $data['original_name'] ?? null,
        ];
    }

    public function attachFromImport(Invoice $invoice, string $uuid, int $userId): bool
    {
        $key = self::cacheKey($uuid);
        $data = Cache::get($key);
        if (! is_array($data)) {
            return false;
        }
        if (($data['user_id'] ?? null) !== $userId) {
            return false;
        }
        if (($data['status'] ?? '') !== 'completed') {
            return false;
        }
        $path = $data['path'] ?? null;
        $originalName = $data['original_name'] ?? 'import.pdf';
        $mime = $data['mime'] ?? 'application/octet-stream';
        if (! $path || ! Storage::disk('local')->exists($path)) {
            Cache::forget($key);

            return false;
        }

        $absolute = Storage::disk('local')->path($path);
        if (! is_file($absolute)) {
            Cache::forget($key);

            return false;
        }

        $extension = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';
        $filename = Str::random(40).'.'.$extension;
        $dir = 'invoices/'.date('Y/m').'/'.$invoice->id;
        $targetPath = $dir.'/'.$filename;

        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->put($targetPath, file_get_contents($absolute));

        InvoiceAttachment::create([
            'invoice_id' => $invoice->id,
            'file_name' => $originalName,
            'file_path' => $targetPath,
            'file_size' => Storage::disk('local')->size($targetPath),
            'mime_type' => $mime,
            'description' => 'Imported from document',
            'category' => 'Invoice Copy',
            'uploaded_by' => $userId,
        ]);

        Storage::disk('local')->delete($path);
        Cache::forget($key);

        return true;
    }
}
