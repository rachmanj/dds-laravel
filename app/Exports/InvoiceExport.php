<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoiceExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles
{
    public function __construct(protected $data) {}

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Faktur No',
            'Supplier',
            'Invoice Type',
            'Invoice Date',
            'Receive Date',
            'PO Number',
            'Amount',
            'Currency',
            'Status',
            'SAP Status',
            'Current Location',
            'Days in Location',
            'Distribution Status',
            'Invoice Project',
            'Created By',
            'Created At',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 15,
            'C' => 25,
            'D' => 20,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 10,
            'J' => 12,
            'K' => 15,
            'L' => 15,
            'M' => 12,
            'N' => 18,
            'O' => 15,
            'P' => 20,
            'Q' => 20,
        ];
    }
}
