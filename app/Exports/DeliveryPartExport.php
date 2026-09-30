<?php

namespace App\Exports;

use Generator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliveryPartExport implements FromGenerator, WithHeadings, WithStyles
{
    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $rows
     */
    public function __construct(private Collection|array $rows) {}

    public function generator(): Generator
    {
        $rows = $this->rows instanceof Collection ? $this->rows : collect($this->rows);

        foreach ($rows as $row) {
            yield [
                $row['tanggal_received'] ?? null,
                $row['supplier'] ?? null,
                $row['po_number'] ?? null,
                $row['no_spb'] ?? null,
                $row['no_ito'] ?? null,
                $row['no_unit'] ?? null,
                $row['parts_number'] ?? null,
                $row['descriptions'] ?? null,
                $row['qty'] ?? null,
                $row['uom'] ?? null,
                $row['remarks_barang'] ?? null,
                $row['tgl_delivery'] ?? null,
                $row['transporter'] ?? null,
                $row['unit_kendaraan'] ?? null,
                $row['ekspedisi'] ?? null,
                $row['tgl_iti'] ?? null,
                $row['no_iti'] ?? null,
                $row['keterangan'] ?? null,
            ];
        }
    }

    public function headings(): array
    {
        return [
            'TANGGAL RECEIVED',
            'Supplier',
            'PO Number',
            'No. SPB',
            'NO ITO',
            'No Unit',
            'Parts Number',
            'Descriptions',
            'QTY',
            'UOM',
            'Remarks Barang',
            'Tgl Delivery',
            'Transporter',
            'Unit & No Kendaraan',
            'Ekspedisi',
            'Tgl ITI',
            'NO. ITI',
            'Keterangan',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
