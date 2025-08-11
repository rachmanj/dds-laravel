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
        $documentTypes = AdditionalDocumentType::orderByName()->pluck('type_name')->toArray();
        $typeList = implode(', ', $documentTypes);

        return [
            [
                'DOC-001',
                'ITO',
                '01/01/2024',
                'PO-2024-001',
                'Project Alpha',
                '02/01/2024',
                'Sample ITO document for import testing',
                'open',
                '000HLOG',
                'John Doe',
                'GRPO-001',
                'WH-001',
                'WH-002'
            ],
            [
                'DOC-002',
                'Goods Issue',
                '01/02/2024',
                'PO-2024-002',
                'Project Beta',
                '02/02/2024',
                'Sample Goods Issue document',
                'open',
                '000HLOG',
                'Jane Smith',
                'GRPO-002',
                'WH-003',
                'WH-004'
            ],
            [
                'DOC-003',
                'BAPP',
                '01/03/2024',
                'PO-2024-003',
                'Project Gamma',
                '02/03/2024',
                'Sample BAPP document',
                'open',
                '000HLOG',
                'Mike Johnson',
                'GRPO-003',
                'WH-005',
                'WH-006'
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
                ''
            ],
            [
                '2. Date formats supported: DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD',
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
                '3. Document types available: ' . $typeList,
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
                '4. Status options: open, closed',
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
                '5. Location (cur_loc) will be automatically set to 000HLOG',
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
            'document_type',
            'ito_date',
            'po_no',
            'project',
            'ito_created_date',
            'ito_remarks',
            'status',
            'cur_loc',
            'ito_creator',
            'grpo_no',
            'origin_wh',
            'destination_wh'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:M1')->applyFromArray([
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
        $sheet->getStyle('A2:M4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F8FF'],
            ],
        ]);

        // Style the instructions section
        $sheet->getStyle('A6:A10')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FF0000'],
            ],
        ]);

        // Add borders to the data area
        $sheet->getStyle('A1:M4')->applyFromArray([
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
            'A' => 20, // ito_no
            'B' => 15, // document_type
            'C' => 15, // ito_date
            'D' => 15, // po_no
            'E' => 20, // project
            'F' => 15, // ito_created_date
            'G' => 30, // ito_remarks
            'H' => 10, // status
            'I' => 15, // cur_loc
            'J' => 20, // ito_creator
            'K' => 15, // grpo_no
            'L' => 15, // origin_wh
            'M' => 15, // destination_wh
        ];
    }
}
