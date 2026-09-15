<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class SapInventoryRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(): array
    {
        $sql = $this->loadSql();
        $rows = DB::connection('sap_sql')->select($sql, [0]);

        return array_map(
            fn (object $row): array => $this->normalizeRow((array) $row),
            $rows
        );
    }

    private function loadSql(): string
    {
        $path = base_path('docs/sap-queries/inventory-all-warehouse.sql');

        return trim((string) file_get_contents($path));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        return [
            'model_no' => $this->value($row, ['Model no', 'model_no']),
            'unit_no' => $this->value($row, ['Unit No', 'unit_no']),
            'item_code' => (string) $this->value($row, ['ItemCode', 'item_code'], ''),
            'item_name' => $this->value($row, ['ItemName', 'item_name']),
            'uom' => $this->value($row, ['InvntryUom', 'uom']),
            'instock' => (float) ($this->value($row, ['Instock', 'instock'], 0) ?? 0),
            'committed' => (float) ($this->value($row, ['Committed', 'committed'], 0) ?? 0),
            'ordered' => (float) ($this->value($row, ['Ordered', 'ordered'], 0) ?? 0),
            'currency' => $this->value($row, ['Currency', 'currency']),
            'last_price' => $this->nullableFloat($this->value($row, ['Last Purchase Price', 'last_price'])),
            'total_value' => (float) ($this->value($row, ['Total', 'total_value'], 0) ?? 0),
            'whs_code' => $this->value($row, ['WhsCode', 'whs_code']),
            'whs_name' => $this->value($row, ['WhsName', 'whs_name']),
            'project' => $this->value($row, ['U_MIS_Project', 'project']),
            'status' => $this->value($row, ['Status', 'status']),
            'last_mr_no' => $this->value($row, ['Last MR No', 'last_mr_no']),
            'last_mi_no' => $this->value($row, ['Last MI No', 'last_mi_no']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function value(array $row, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return $default;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
