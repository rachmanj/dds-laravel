<?php

namespace Database\Seeders;

use App\Models\AdditionalDocumentType;
use Illuminate\Database\Seeder;

class AdditionalDocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'ITO',
            'Goods Issue',
            'BAPP',
            'Time Sheet',
            'OSR',
            'Goods Receipt',
            'Material Issue',
            'Faktur Pajak',
            'Delivery Order (DO)',
            'BAST',
            'Lembar Manifest',
            'SPK (Surat Perintah Kerja)',
            'Monthly Report Satnet dan Megasatcom',
            'Material Requisition',
            'Credit Note',
            'Kwitansi',
            'Good Return',
            'Sertifikat Uji Emisi Genset',
            'Calibration Sertificate',
            'PO',
            'BA',
            'GRPO',
            'Surat Pengiriman Barang',
        ];

        $requiresSignatureTypes = ['Delivery Order (DO)', 'ITO'];

        foreach ($types as $typeName) {
            AdditionalDocumentType::updateOrCreate(
                ['type_name' => $typeName],
                ['requires_signature' => in_array($typeName, $requiresSignatureTypes, true)]
            );
        }
    }
}
