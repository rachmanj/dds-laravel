<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LogisticsUsageExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn (array $row) => [
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
        ]);
    }

    public function headings(): array
    {
        return [
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
            'ItemCode',
            'Dscription',
            'Quantity',
            'Comments',
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
            'A' => 12,
            'B' => 18,
            'C' => 14,
            'D' => 12,
            'E' => 20,
            'F' => 14,
            'G' => 8,
            'H' => 16,
            'I' => 14,
            'J' => 18,
            'K' => 12,
            'L' => 12,
            'M' => 12,
            'N' => 12,
            'O' => 14,
            'P' => 24,
            'Q' => 10,
            'R' => 12,
            'S' => 14,
            'T' => 12,
            'U' => 18,
            'V' => 12,
            'W' => 12,
            'X' => 12,
            'Y' => 12,
            'Z' => 12,
            'AA' => 14,
            'AB' => 24,
            'AC' => 10,
            'AD' => 30,
        ];
    }
}
