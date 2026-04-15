<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdditionalDocumentExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Document Number',
            'Document Type',
            'Document Date',
            'PO Number',
            'Vendor Code',
            'Receive Date',
            'Current Location',
            'Days in location',
            'Status',
            'Distribution Status',
            'Remarks',
            'Created By',
            'Created At',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Document Number
            'B' => 20, // Document Type
            'C' => 15, // Document Date
            'D' => 15, // PO Number
            'E' => 15, // Vendor Code
            'F' => 15, // Receive Date
            'G' => 15, // Current Location
            'H' => 12, // Days in location
            'I' => 15, // Status
            'J' => 20, // Distribution Status
            'K' => 30, // Remarks
            'L' => 20, // Created By
            'M' => 20, // Created At
        ];
    }
}
