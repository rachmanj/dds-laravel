<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LogisticsInventoryExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles
{
    public function __construct(private Collection $items) {}

    public function collection(): Collection
    {
        return $this->items->map(fn ($item) => [
            $item->model_no,
            $item->unit_no,
            $item->item_code,
            $item->item_name,
            $item->category,
            $item->uom,
            $item->instock,
            $item->committed,
            $item->ordered,
            $item->currency,
            $item->last_price,
            $item->total_value,
            $item->whs_code,
            $item->whs_name,
            $item->project,
            $item->status,
            $item->last_mr_no,
            $item->last_mi_no,
        ]);
    }

    public function headings(): array
    {
        return [
            'Model no',
            'Unit No',
            'Item No.',
            'Item Description',
            'Category',
            'Inventory UoM',
            'Instock',
            'Committed',
            'Ordered',
            'Currency',
            'Last Purchase Price',
            'Total',
            'WhsCode',
            'WhsName',
            'Project',
            'Status',
            'Last MR No',
            'Last MI No',
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
            'B' => 12,
            'C' => 16,
            'D' => 30,
            'E' => 16,
            'F' => 14,
            'G' => 12,
            'H' => 12,
            'I' => 12,
            'J' => 10,
            'K' => 18,
            'L' => 14,
            'M' => 10,
            'N' => 20,
            'O' => 10,
            'P' => 12,
            'Q' => 14,
            'R' => 14,
        ];
    }
}
