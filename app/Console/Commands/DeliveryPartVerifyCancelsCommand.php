<?php

namespace App\Console\Commands;

use App\Models\DeliveryPartItoCancel;
use App\Services\SapService;
use Illuminate\Console\Command;

class DeliveryPartVerifyCancelsCommand extends Command
{
    protected $signature = 'delivery-part:verify-cancels';

    protected $description = 'Verifikasi ulang status CANCELED di SAP untuk permintaan pembatalan ITO';

    public function handle(SapService $sapService): int
    {
        $pending = DeliveryPartItoCancel::query()
            ->whereIn('status', [
                DeliveryPartItoCancel::STATUS_REQUESTED,
                DeliveryPartItoCancel::STATUS_FAILED,
            ])
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Tidak ada permintaan requested/failed untuk diverifikasi.');

            return self::SUCCESS;
        }

        $verified = 0;
        $stillPending = 0;
        $notFound = 0;

        foreach ($pending as $cancel) {
            $sapCancelled = $sapService->isStockTransferCancelled((int) $cancel->doc_entry);

            if ($sapCancelled === null) {
                $notFound++;
                $this->line("DocEntry {$cancel->doc_entry} (ITO {$cancel->ito_no}): dokumen tidak ditemukan di OWTR.");

                continue;
            }

            if ($sapCancelled === true) {
                $cancel->status = DeliveryPartItoCancel::STATUS_CANCELLED;
                $cancel->verified_at = now();
                if ($cancel->executed_at === null) {
                    $cancel->executed_at = now();
                }
                $cancel->save();
                $verified++;
                $this->line("DocEntry {$cancel->doc_entry} (ITO {$cancel->ito_no}): ditandai cancelled (CANCELED=Y).");

                continue;
            }

            $stillPending++;
            $this->line("DocEntry {$cancel->doc_entry} (ITO {$cancel->ito_no}): masih belum CANCELED=Y.");
        }

        $this->newLine();
        $this->info("Ringkasan: {$verified} diverifikasi cancelled, {$stillPending} masih menunggu, {$notFound} tidak ditemukan di SAP.");

        return self::SUCCESS;
    }
}
