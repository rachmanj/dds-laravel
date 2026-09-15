<?php

namespace App\Exports;

use App\Models\LogisticsInventoryItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LogisticsInventoryExport implements FromQuery, WithColumnWidths, WithCustomChunkSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @var list<string>
     */
    public const EXPORT_COLUMNS = [
        'model_no',
        'unit_no',
        'item_code',
        'item_name',
        'category',
        'uom',
        'instock',
        'committed',
        'ordered',
        'currency',
        'last_price',
        'total_value',
        'whs_code',
        'whs_name',
        'project',
        'status',
        'last_mr_no',
        'last_mi_no',
    ];

    /**
     * @param  Builder<LogisticsInventoryItem>  $query
     */
    public function __construct(private Builder $query) {}

    /**
     * @return Builder<LogisticsInventoryItem>
     */
    public function query(): Builder
    {
        return (clone $this->query)->select(self::EXPORT_COLUMNS);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * @param  LogisticsInventoryItem  $item
     * @return list<mixed>
     */
    public function map($item): array
    {
        return [
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
        ];
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
