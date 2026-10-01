<?php

namespace App\Console\Commands;

use App\Exceptions\SapSqlQueryException;
use App\Services\Logistics\DeliveryPartExcelImportService;
use Illuminate\Console\Command;

class DeliveryPartImportExcelCommand extends Command
{
    protected $signature = 'delivery-part:import-excel
                            {--file= : Path ke file Excel (.xlsx)}
                            {--write : Tulis perubahan ke database (default: dry-run)}
                            {--report= : Path berkas laporan kegagalan/duplikat (CSV atau .json)}';

    protected $description = 'Import kolom manual Delivery Part dari Excel 2026 (default dry-run; gunakan --write untuk menulis)';

    public function handle(DeliveryPartExcelImportService $importService): int
    {
        $file = $this->option('file');
        if (! is_string($file) || $file === '') {
            $this->error('Wajib menyertakan --file=<path> ke file Excel (.xlsx).');
            $this->line('');
            $this->line('Contoh:');
            $this->line('  php artisan delivery-part:import-excel --file=/path/Copy-of-Delivery-Part.xlsx');
            $this->line('  php artisan delivery-part:import-excel --file=/path/file.xlsx --write');

            return self::FAILURE;
        }

        if (! is_file($file)) {
            $this->error('File tidak ditemukan: '.$file);

            return self::FAILURE;
        }

        $write = (bool) $this->option('write');
        if ($write) {
            $this->warn('Mode WRITE aktif — perubahan akan disimpan ke database DDS.');
        } else {
            $this->info('Mode dry-run (default) — tidak ada perubahan database.');
        }

        $this->newLine();

        try {
            $result = $importService->import($file, $write, function (string $sheetName, array $summary): void {
                $this->printSheetSummary($sheetName, $summary);
                if (function_exists('flush')) {
                    flush();
                }
            });
        } catch (SapSqlQueryException $e) {
            $this->error('Import dibatalkan: '.$e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Gagal membaca file: '.$e->getMessage());

            return self::FAILURE;
        }

        $reportPath = $this->option('report');
        if (is_string($reportPath) && $reportPath !== '') {
            try {
                $importService->writeReportFile($reportPath, $result);
                $this->info('Laporan kegagalan/duplikat ditulis ke: '.$reportPath);
            } catch (\Throwable $e) {
                $this->error('Gagal menulis laporan: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        if ($write) {
            $this->info(sprintf(
                'Ringkasan akhir: %d insert, %d update.',
                $result['total_inserts'],
                $result['total_updates']
            ));
        }

        $peakBytes = (int) ($result['peak_memory_bytes'] ?? memory_get_peak_usage(true));
        $this->line(sprintf('Memori puncak: %s', $this->formatBytes($peakBytes)));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function printSheetSummary(string $sheetName, array $summary): void
    {
        $this->info('=== Sheet: '.$sheetName.' ===');
        if (! empty($summary['skipped'])) {
            $this->warn('  Dilewati: '.($summary['skip_reason'] ?? 'tidak diketahui'));
            $this->newLine();

            return;
        }

        $this->line('  Baris dibaca: '.($summary['rows_read'] ?? 0));
        $this->line('  Baris kosong dilewati: '.($summary['rows_empty_skipped'] ?? 0));
        $this->line('  Cocok dengan DDS: '.($summary['matched'] ?? 0));
        $this->line('  Akan dibuat (SAP): '.($summary['will_create_sap'] ?? 0));
        $this->line('  Akan dibuat (manual): '.($summary['will_create_manual'] ?? 0));
        $this->line('  Akan diisi (kolom kosong): '.($summary['will_fill'] ?? 0));
        $this->line('  Konflik (sudah terisi): '.($summary['conflicts'] ?? 0));
        $this->line('  Duplikat dilewati: '.($summary['duplikat_dilewati'] ?? 0));
        $this->line('  Gagal tulis: '.($summary['write_failures'] ?? 0));
        if (isset($summary['sap_chunks_loaded'])) {
            $this->line('  Chunk SAP dimuat: '.($summary['sap_chunks_loaded'] ?? 0));
        }

        $duplicateMessages = $summary['duplicate_messages'] ?? [];
        if ($duplicateMessages !== []) {
            $this->line('  Duplikat (detail):');
            foreach ($duplicateMessages as $message) {
                $this->line('    - '.$message);
            }
        }

        $failureMessages = $summary['failure_messages'] ?? [];
        if ($failureMessages !== []) {
            $this->line('  Kegagalan (detail):');
            foreach ($failureMessages as $message) {
                $this->line('    - '.$message);
            }
        }

        if (! empty($summary['notes'])) {
            $this->line('  Catatan:');
            foreach (array_slice($summary['notes'], 0, 10) as $note) {
                $this->line('    - '.$note);
            }
            if (count($summary['notes']) > 10) {
                $this->line('    ... dan '.(count($summary['notes']) - 10).' catatan lainnya');
            }
        }

        $this->newLine();
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}
