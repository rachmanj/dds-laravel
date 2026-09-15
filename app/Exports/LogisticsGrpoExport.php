<?php

namespace App\Exports;

use Generator;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LogisticsGrpoExport implements FromGenerator, WithColumnWidths, WithHeadings, WithStyles
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
            $row['grpo_date'] ?? null,
            $row['grpo_created_date'] ?? null,
            $row['grpo_no'] ?? null,
            $row['po_no'] ?? null,
            $row['po_date'] ?? null,
            $row['po_created_date'] ?? null,
            $row['po_delivery_status'] ?? null,
            $row['po_delivery_time'] ?? null,
            $row['pr_no'] ?? null,
            $row['item_code'] ?? null,
            $row['oem_no'] ?? null,
            $row['item_name'] ?? null,
            $row['u_mis_consre1'] ?? null,
            $row['u_mis_consre2'] ?? null,
            $row['quantity'] ?? null,
            $row['u_mis_unitno'] ?? null,
            $row['currency'] ?? null,
            $row['price'] ?? null,
            $row['total_price'] ?? null,
            $row['uom'] ?? null,
            $row['warehouse_code'] ?? null,
            $row['warehouse_name'] ?? null,
            $row['received_by'] ?? null,
            $row['time'] ?? null,
            $row['project'] ?? null,
            $row['department'] ?? null,
            $row['comments'] ?? null,
        ];
    }

    public function headings(): array
    {
        return [
            'GRPO Date',
            'GRPO Created Date',
            'GRPO No',
            'PO No.',
            'PO Date',
            'PO Created Date',
            'PO Delivery Status',
            'PO Delivery Time',
            'PR No.',
            'Item Code',
            'OEM No.',
            'Item Name',
            'U_MIS_ConsRe1',
            'U_MIS_ConsRe2',
            'Quantity',
            'U_MIS_UnitNo',
            'Currency',
            'Price',
            'Total Price',
            'UoM',
            'Warehouse Code',
            'Warehouse Name',
            'Received By',
            'Time',
            'Project',
            'Department',
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
            'A' => 14,
            'B' => 18,
            'C' => 12,
            'D' => 12,
            'E' => 14,
            'F' => 18,
            'G' => 18,
            'H' => 16,
            'I' => 12,
            'J' => 14,
            'K' => 14,
            'L' => 28,
            'M' => 14,
            'N' => 14,
            'O' => 10,
            'P' => 12,
            'Q' => 10,
            'R' => 12,
            'S' => 14,
            'T' => 10,
            'U' => 14,
            'V' => 20,
            'W' => 16,
            'X' => 10,
            'Y' => 10,
            'Z' => 14,
            'AA' => 30,
        ];
    }
}
