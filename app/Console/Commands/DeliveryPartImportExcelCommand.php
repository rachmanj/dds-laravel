<?php

namespace App\Console\Commands;

use App\Services\Logistics\DeliveryPartExcelImportService;
use Illuminate\Console\Command;

class DeliveryPartImportExcelCommand extends Command
{
    protected $signature = 'delivery-part:import-excel
                            {--file= : Path ke file Excel (.xlsx)}
                            {--write : Tulis perubahan ke database (default: dry-run)}';

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
            $result = $importService->import($file, $write);
        } catch (\Throwable $e) {
            $this->error('Gagal membaca file: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($result['sheets'] as $sheetName => $summary) {
            $this->info('=== Sheet: '.$sheetName.' ===');
            if (! empty($summary['skipped'])) {
                $this->warn('  Dilewati: '.($summary['skip_reason'] ?? 'tidak diketahui'));
                $this->newLine();

                continue;
            }

            $this->line('  Baris dibaca: '.($summary['rows_read'] ?? 0));
            $this->line('  Baris kosong dilewati: '.($summary['rows_empty_skipped'] ?? 0));
            $this->line('  Cocok dengan DDS: '.($summary['matched'] ?? 0));
            $this->line('  Akan dibuat (SAP): '.($summary['will_create_sap'] ?? 0));
            $this->line('  Akan dibuat (manual): '.($summary['will_create_manual'] ?? 0));
            $this->line('  Akan diisi (kolom kosong): '.($summary['will_fill'] ?? 0));
            $this->line('  Konflik (sudah terisi): '.($summary['conflicts'] ?? 0));
            $this->line('  Gagal tulis: '.($summary['write_failures'] ?? 0));

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

        if ($write) {
            $this->info(sprintf(
                'Ringkasan akhir: %d insert, %d update.',
                $result['total_inserts'],
                $result['total_updates']
            ));
        }

        return self::SUCCESS;
    }
}
