<?php

namespace App\Services;

use App\Models\AdditionalDocument;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PasteDocumentImportService
{
    /**
     * @param  array<int, string>  $lines
     * @return array{success_count: int, skipped_count: int, error_count: int, errors: array<int, string>, total_processed: int}
     */
    public function import(
        array $lines,
        int $documentTypeId,
        ?int $projectId,
        ?string $fallbackDate,
        User $user
    ): array {
        $successCount = 0;
        $skippedCount = 0;
        $errors = [];

        $defaultDate = $fallbackDate
            ? Carbon::parse($fallbackDate)->toDateString()
            : now()->toDateString();

        $projectCode = null;
        if ($projectId) {
            $projectCode = Project::query()->find($projectId)?->code;
        }

        $curLoc = $user->department_location_code ?: 'DEFAULT';

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            $segments = explode("\t", $trimmed);
            $number = trim($segments[0] ?? '');
            $dateSegment = count($segments) >= 2 ? $segments[1] : null;

            if ($number === '') {
                $errors[] = "Baris {$lineNumber}: Nomor dokumen kosong";

                continue;
            }

            if (strlen($number) > 255) {
                $errors[] = "Baris {$lineNumber}: Nomor dokumen terlalu panjang (maksimal 255 karakter)";

                continue;
            }

            try {
                $documentDate = $this->resolveDocumentDate($dateSegment, $defaultDate);
            } catch (\InvalidArgumentException $e) {
                $errors[] = "Baris {$lineNumber}: {$e->getMessage()}";

                continue;
            }

            $exists = AdditionalDocument::query()
                ->where('type_id', $documentTypeId)
                ->where('document_number', $number)
                ->exists();

            if ($exists) {
                $skippedCount++;

                continue;
            }

            try {
                AdditionalDocument::create([
                    'type_id' => $documentTypeId,
                    'document_number' => $number,
                    'document_date' => $documentDate,
                    'project' => $projectCode,
                    'vendor_code' => null,
                    'remarks' => null,
                    'cur_loc' => $curLoc,
                    'status' => 'open',
                    'created_by' => $user->id,
                ]);
                $successCount++;
            } catch (\Exception $e) {
                Log::error('Paste import line error', [
                    'line' => $lineNumber,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = "Baris {$lineNumber}: {$e->getMessage()}";
            }
        }

        return [
            'success_count' => $successCount,
            'skipped_count' => $skippedCount,
            'error_count' => count($errors),
            'errors' => $errors,
            'total_processed' => $successCount + $skippedCount + count($errors),
        ];
    }

    private function resolveDocumentDate(?string $dateSegment, string $defaultDate): string
    {
        if ($dateSegment === null || trim($dateSegment) === '') {
            return $defaultDate;
        }

        $parsed = $this->tryParseDate(trim($dateSegment));

        if ($parsed === null) {
            throw new \InvalidArgumentException("Format tanggal tidak dikenali: {$dateSegment}");
        }

        return $parsed->toDateString();
    }

    private function tryParseDate(string $value): ?Carbon
    {
        if (preg_match('/^\d+$/', $value)) {
            $serial = (int) $value;
            if ($serial >= 20000) {
                return Carbon::create(1899, 12, 30)->addDays($serial);
            }
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            $parsed = $this->parseWithFormat($value, $format);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        $parsed = $this->parseWithFormat($value, 'Y-m-d');
        if ($parsed !== null) {
            return $parsed;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            // Continue to m/d/Y fallback.
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            if ((int) $matches[1] <= 12) {
                $parsed = $this->parseWithFormat($value, 'm/d/Y');
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    private function parseWithFormat(string $value, string $format): ?Carbon
    {
        try {
            $parsed = Carbon::createFromFormat('!'.$format, $value);
            if ($parsed === false) {
                return null;
            }

            $errors = Carbon::getLastErrors();
            if (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
                return null;
            }

            return $parsed;
        } catch (\Exception $e) {
            return null;
        }
    }
}
