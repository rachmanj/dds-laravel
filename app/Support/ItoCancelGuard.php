<?php

namespace App\Support;

use App\Models\DeliveryPartItoCancel;
use App\Services\SapService;

class ItoCancelGuard
{
    public function __construct(
        private SapService $sapService,
    ) {}

    /**
     * @return array{allowed: bool, message: string}
     */
    public function validate(int $docEntry, string $itoDocNum, string $reason): array
    {
        if (mb_strlen(trim($reason)) < 10) {
            return [
                'allowed' => false,
                'message' => 'Alasan pembatalan wajib diisi minimal 10 karakter.',
            ];
        }

        if (! $this->sapService->stockTransferExists($docEntry)) {
            return [
                'allowed' => false,
                'message' => 'Dokumen ITO tidak ditemukan di SAP.',
            ];
        }

        if ($this->sapService->hasItiForIto($itoDocNum)) {
            return [
                'allowed' => false,
                'message' => 'ITO ini sudah memiliki ITI, tidak bisa dibatalkan.',
            ];
        }

        $cancelled = $this->sapService->isStockTransferCancelled($docEntry);
        if ($cancelled === true) {
            return [
                'allowed' => false,
                'message' => 'ITO ini sudah dibatalkan di SAP.',
            ];
        }

        if (DeliveryPartItoCancel::hasActiveRequestForDocEntry($docEntry)) {
            return [
                'allowed' => false,
                'message' => 'Sudah ada permintaan pembatalan aktif untuk dokumen ITO ini.',
            ];
        }

        return [
            'allowed' => true,
            'message' => '',
        ];
    }
}
