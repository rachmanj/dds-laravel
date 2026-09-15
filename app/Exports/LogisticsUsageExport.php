<?php

namespace App\Exports;

use Generator;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LogisticsUsageExport implements FromGenerator, WithColumnWidths, WithEvents, WithHeadings, WithStyles
{
    private const CHUNK_SIZE = 1000;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private array $rows) {}

    public function generator(): Generator
    {
        foreach (array_chunk($this->rows, self::CHUNK_SIZE) as $chunk) {
            foreach ($chunk as $row) {
                yield $this->mapRow($row);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<mixed>
     */
    private function mapRow(array $row): array
    {
        return [
            $this->sourceLabel($row['source'] ?? ''),
            $row['doc_num'] ?? null,
            $row['create_date'] ?? null,
            $row['doc_date'] ?? null,
            $row['wo_no'] ?? null,
            $row['subject'] ?? null,
            $row['category'] ?? null,
            $row['line'] ?? null,
            $row['issue_purpose'] ?? null,
            $row['job_category'] ?? null,
            $row['job_name'] ?? null,
            $row['unit_no'] ?? null,
            $row['model_no'] ?? null,
            $row['serial_no'] ?? null,
            $row['hours_meter'] ?? null,
            $row['item_code'] ?? null,
            $row['dscription'] ?? null,
            $row['quantity'] ?? null,
            $row['stockprice'] ?? null,
            $row['total'] ?? null,
            $row['project'] ?? null,
            $row['whs_name'] ?? null,
            $row['u_mis_no_ba'] ?? null,
            $row['order_type'] ?? null,
            $row['status_doc'] ?? null,
            $row['gr_no'] ?? null,
            $row['m_ret_no'] ?? null,
            $row['return_item_code'] ?? null,
            $row['return_dscription'] ?? null,
            $row['return_quantity'] ?? null,
            $row['comments'] ?? null,
        ];
    }

    public function headings(): array
    {
        return [
            'Source',
            'DocNum',
            'createDate',
            'DocDate',
            'WO No',
            'Subject',
            'Category',
            'Line',
            'Issue Purpose',
            'Job Category',
            'Job Name',
            'Unit No',
            'Model No',
            'Serial No',
            'Hours Meter',
            'ItemCode',
            'Dscription',
            'Quantity',
            'Stockprice',
            'Total',
            'Project',
            'WhsName',
            'U_MIS_NoBA',
            'Order Type',
            'Status',
            'GR No',
            'M Ret No',
            'Ret ItemCode',
            'Ret Dscription',
            'Ret Quantity',
            'Comments',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                foreach (['S', 'T'] as $column) {
                    $sheet->getStyle("{$column}1:{$column}{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 12,
            'C' => 18,
            'D' => 14,
            'E' => 12,
            'F' => 20,
            'G' => 14,
            'H' => 8,
            'I' => 16,
            'J' => 14,
            'K' => 18,
            'L' => 12,
            'M' => 12,
            'N' => 12,
            'O' => 12,
            'P' => 14,
            'Q' => 24,
            'R' => 10,
            'S' => 12,
            'T' => 14,
            'U' => 12,
            'V' => 18,
            'W' => 12,
            'X' => 12,
            'Y' => 12,
            'Z' => 12,
            'AA' => 12,
            'AB' => 14,
            'AC' => 24,
            'AD' => 10,
            'AE' => 30,
        ];
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'goods_issue' => 'Goods Issue',
            'delivery' => 'Delivery',
            'ap_service' => 'AP Service',
            default => $source !== '' ? $source : '-',
        };
    }
}
