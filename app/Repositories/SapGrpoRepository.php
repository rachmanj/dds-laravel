<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SapGrpoRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $from, string $to): array
    {
        $sql = $this->loadSql();
        $rows = DB::connection('sap_sql')->select($sql, [$from, $to]);

        return array_map(
            fn (object $row): array => $this->normalizeRow((array) $row),
            $rows
        );
    }

    private function loadSql(): string
    {
        $path = base_path('docs/sap-queries/grpo-param.sql');

        return trim((string) file_get_contents($path));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[$this->normalizeColumnKey((string) $key)] = $value;
        }

        return $normalized;
    }

    private function normalizeColumnKey(string $key): string
    {
        $map = [
            'GRPO Date' => 'grpo_date',
            'GRPO Created Date' => 'grpo_created_date',
            'GRPO No' => 'grpo_no',
            'PO No.' => 'po_no',
            'PO Date' => 'po_date',
            'PO Created Date' => 'po_created_date',
            'PO Delivery Status' => 'po_delivery_status',
            'PO Delivery Time' => 'po_delivery_time',
            'PR No.' => 'pr_no',
            'Item Code' => 'item_code',
            'OEM No.' => 'oem_no',
            'Item Name' => 'item_name',
            'U_MIS_ConsRe1' => 'u_mis_consre1',
            'U_MIS_ConsRe2' => 'u_mis_consre2',
            'U Cons Re1' => 'u_mis_consre1',
            'U Cons Re2' => 'u_mis_consre2',
            'Quantity' => 'quantity',
            'U_MIS_UnitNo' => 'u_mis_unitno',
            'Unit No' => 'u_mis_unitno',
            'Currency' => 'currency',
            'Price' => 'price',
            'Total Price' => 'total_price',
            'UoM' => 'uom',
            'Warehouse Code' => 'warehouse_code',
            'Warehouse Name' => 'warehouse_name',
            'Received By' => 'received_by',
            'Time' => 'time',
            'Project' => 'project',
            'Department' => 'department',
            'Comments' => 'comments',
            'Vendor Code' => 'vendor_code',
            'Vendor Name' => 'vendor_name',
        ];

        if (isset($map[$key])) {
            return $map[$key];
        }

        return Str::snake(preg_replace('/[^A-Za-z0-9]+/', '_', trim($key)) ?? $key);
    }
}
