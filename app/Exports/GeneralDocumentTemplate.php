<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GeneralDocumentTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                'PANAOIL CRUISER ADVANCE PLUS 15W-40 CI-4/SL (DRUM 205L)',
                'SPB-ARKA/I/022C/IX/2025-72',
                '10-Sep-25',
                '252551966',
                '10-Sep-25',
                '',
                ''
            ],
            [
                'PANAOIL UNITRANS HD 30 (DRUM 205L)',
                'SPB-ARKA/I/022C/IX/2025-73',
                '11-Sep-25',
                '252551967',
                '11-Sep-25',
                '',
                ''
            ],
            [
                'PANAOIL UNITRANS HD 50 (DRUM 205L)',
                'SPB-ARKA/I/022C/IX/2025-74',
                '12-Sep-25',
                '252551968',
                '12-Sep-25',
                'MR-2025-001',
                '12-Sep-25'
            ],
            [
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
                ''
            ],
            [
                '1. Description field is required for all rows',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '2. At least one document number (DO, GR, or MR) must be provided',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '3. Date format: DD-Mon-YY (e.g., 10-Sep-25) or DD.MM.YYYY',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '4. Each row can create up to 3 documents (DO, GR, MR)',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '5. DO = Delivery Order, GR = Goods Receipt, MR = Material Request',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '6. Duplicate document numbers will be automatically skipped',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '7. All documents will be assigned to your department location',
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
            'description',
            'do_no',
            'do_date',
            'gr_no',
            'gr_date',
            'mr_no',
            'mr_date'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:G1')->applyFromArray([
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
        $sheet->getStyle('A2:G4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F8FF'],
            ],
        ]);

        // Style the instructions section
        $sheet->getStyle('A6:A12')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FF0000'],
            ],
        ]);

        // Add borders to the data area
        $sheet->getStyle('A1:G4')->applyFromArray([
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
            'A' => 60, // description
            'B' => 25, // do_no
            'C' => 15, // do_date
            'D' => 15, // gr_no
            'E' => 15, // gr_date
            'F' => 15, // mr_no
            'G' => 15, // mr_date
        ];
    }
}
