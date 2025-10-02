<?php

namespace App\Exports;

use App\Models\AdditionalDocumentType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdditionalDocumentTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                '251005358',
                '20.08.2025',
                '20.08.2025',
                'logbpn3',
                '000H LOG',
                'Y',
                '250506242',
                '250204145',
                '02-SPT',
                '13-SPT',
                'PR 250140607 (T105) BARANG DITERIMA DISITE TGL 08.08.2025',
                '251005358',
                '20.08.2025',
                '20.08.2025',
                'Delivered',
                '20.08.2025',
                '20.08.2025',
                '',
                '',
                '',
                'Inventory Transfers -'
            ],
            [
                '251005359',
                '20.08.2025',
                '20.08.2025',
                'logbpn3',
                '000H LOG',
                'Y',
                '250506243',
                '250204857',
                '02-SPT',
                '13-SPT',
                'ADA BARANGNYA',
                '251005359',
                '20.08.2025',
                '20.08.2025',
                'Delivered',
                '20.08.2025',
                '20.08.2025',
                '',
                '',
                '',
                'Inventory Transfers -'
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                'INSTRUCTIONS:',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '1. Required fields: ito_no, ito_date',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '2. Date format: DD.MM.YYYY (e.g., 20.08.2025)',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '3. Document type will be automatically set to ITO',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '4. Location will be automatically set to your department location',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '5. Duplicate ITO numbers will be automatically skipped',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'ito_no',
            'ito_date',
            'ito_create',
            'ito_create',
            'User Nam',
            'Printed',
            'grpo_no',
            'po_no',
            'vendor_code',
            'origin_wh',
            'destinatic',
            'ito_remar',
            'iti_no',
            'iti_date',
            'iti_create',
            'delivery_s',
            'delivery_1',
            'Send To Si',
            'Send To B',
            'Send To A',
            'TA Numbe',
            'Journal Remarks'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style the sample data rows
        $sheet->getStyle('A2:U3')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F8FF'],
            ],
        ]);

        // Style the instructions section
        $sheet->getStyle('A5:A9')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FF0000'],
            ],
        ]);

        // Add borders to the data area
        $sheet->getStyle('A1:U3')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // ito_no
            'B' => 12, // ito_date
            'C' => 12, // ito_create
            'D' => 12, // ito_create
            'E' => 15, // User Nam
            'F' => 10, // Printed
            'G' => 15, // grpo_no
            'H' => 15, // po_no
            'I' => 15, // vendor_code
            'J' => 12, // origin_wh
            'K' => 12, // destinatic
            'L' => 40, // ito_remar
            'M' => 15, // iti_no
            'N' => 12, // iti_date
            'O' => 12, // iti_create
            'P' => 15, // delivery_s
            'Q' => 12, // delivery_1
            'R' => 12, // Send To Si
            'S' => 12, // Send To B
            'T' => 12, // Send To A
            'U' => 12, // TA Numbe
            'V' => 30, // Journal Remarks
        ];
    }
}
