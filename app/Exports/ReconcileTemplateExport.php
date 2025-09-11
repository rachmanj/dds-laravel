<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReconcileTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            // Sample data rows
            ['INV-2024-001', '2024-01-15'],
            ['INV-2024-002', '2024-01-16'],
            ['INV-2024-003', '2024-01-17'],
            ['INV-2024-004', '2024-01-18'],
            ['INV-2024-005', '2024-01-19'],
            // Empty rows for user input
            ['', ''],
            ['', ''],
            ['', ''],
            ['', ''],
            ['', ''],
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Invoice Number',
            'Invoice Date',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'E3F2FD',
                    ],
                ],
            ],
            // Style sample data rows
            'A2:B6' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'F5F5F5',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25, // Invoice Number
            'B' => 20, // Invoice Date
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Add instructions
                $event->sheet->getDelegate()->setCellValue('A8', 'Instructions:');
                $event->sheet->getDelegate()->setCellValue('A9', '1. Replace sample data with your actual invoice numbers');
                $event->sheet->getDelegate()->setCellValue('A10', '2. Invoice Date is optional but recommended');
                $event->sheet->getDelegate()->setCellValue('A11', '3. Supported date formats: YYYY-MM-DD, DD/MM/YYYY, MM/DD/YYYY');
                $event->sheet->getDelegate()->setCellValue('A12', '4. Save as .xlsx format before uploading');

                // Style instructions
                $event->sheet->getDelegate()->getStyle('A8:A12')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A8:A12')->getFont()->setSize(10);

                // Add borders to data area
                $event->sheet->getDelegate()->getStyle('A1:B11')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);
            },
        ];
    }
}
