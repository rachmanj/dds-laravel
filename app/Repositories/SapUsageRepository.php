<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SapUsageRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $from, string $to): array
    {
        $sql = $this->prepareSql($this->loadSql());
        $rows = DB::connection('sap_sql')->select($sql, [$from, $to, $from, $to, $from, $to]);

        return array_map(
            fn (object $row): array => $this->normalizeRow((array) $row),
            $rows
        );
    }

    private function loadSql(): string
    {
        $path = base_path('docs/sap-queries/pemakaian.sql');

        return trim((string) file_get_contents($path));
    }

    private function prepareSql(string $raw): string
    {
        $sql = preg_replace('/^\s*\/\*.*?\*\/\s*$/m', '', $raw);
        $sql = preg_replace('/DECLARE\s+@\w+\s+AS\s+DATE\s*/i', '', $sql ?? $raw);
        $sql = preg_replace("/SET\s+@\w+\s*=\s*\/\*.*?\*\/\s*'?\[%\d\]'?\s*/i", '', $sql ?? $raw);
        $sql = str_replace('@A', '?', $sql ?? $raw);
        $sql = str_replace('@B', '?', $sql ?? $raw);

        $sql = preg_replace(
            '/^select\s+/i',
            "SELECT 'Goods Issue' AS [Source], ",
            $sql ?? $raw,
            1
        );

        $sql = preg_replace(
            '/UNION\s+select\s+/i',
            "UNION SELECT 'Delivery' AS [Source], ",
            $sql ?? $raw,
            1
        );

        $sql = preg_replace(
            '/UNION\s+select\s+/i',
            "UNION SELECT 'AP Service' AS [Source], ",
            $sql ?? $raw,
            1
        );

        $sql = str_replace("NULL 'ItemCode'", 'NULL AS [Ret ItemCode]', $sql ?? $raw);
        $sql = str_replace("NULL 'Dscription'", 'NULL AS [Ret Dscription]', $sql ?? $raw);
        $sql = str_replace("NULL 'Quantity'", 'NULL AS [Ret Quantity]', $sql ?? $raw);
        $sql = str_replace('i.ItemCode', 'i.ItemCode AS [Ret ItemCode]', $sql ?? $raw);
        $sql = str_replace('i.Dscription', 'i.Dscription AS [Ret Dscription]', $sql ?? $raw);
        $sql = str_replace('i.Quantity', 'i.Quantity AS [Ret Quantity]', $sql ?? $raw);

        return trim($sql ?? $raw);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeColumnKey((string) $key);
            $normalized[$normalizedKey] = $normalizedKey === 'source'
                ? $this->normalizeSourceValue($value)
                : $value;
        }

        return $normalized;
    }

    private function normalizeColumnKey(string $key): string
    {
        $map = [
            'Source' => 'source',
            'DocNum' => 'doc_num',
            'createDate' => 'create_date',
            'DocDate' => 'doc_date',
            'WO No' => 'wo_no',
            'Subject' => 'subject',
            'Category' => 'category',
            'Line' => 'line',
            'Issue Purpose' => 'issue_purpose',
            'Job Category' => 'job_category',
            'Job Name' => 'job_name',
            ' Unit No' => 'unit_no',
            'Unit No' => 'unit_no',
            'Model No' => 'model_no',
            'Serial No' => 'serial_no',
            'Hours Meter' => 'hours_meter',
            'ItemCode' => 'item_code',
            'Dscription' => 'dscription',
            'Quantity' => 'quantity',
            'Stockprice' => 'stockprice',
            'Total' => 'total',
            'U_MIS_Project' => 'project',
            'Project' => 'project',
            'WhsName' => 'whs_name',
            'U_MIS_NoBA' => 'u_mis_no_ba',
            'Order Type' => 'order_type',
            'Status GI' => 'status_doc',
            'Status Doc' => 'status_doc',
            'GR No' => 'gr_no',
            'M Ret No' => 'm_ret_no',
            'Ret ItemCode' => 'return_item_code',
            'Ret Dscription' => 'return_dscription',
            'Ret Quantity' => 'return_quantity',
            'Comments' => 'comments',
        ];

        if (isset($map[$key])) {
            return $map[$key];
        }

        return Str::snake(preg_replace('/[^A-Za-z0-9]+/', '_', trim($key)) ?? $key);
    }

    private function normalizeSourceValue(mixed $value): string
    {
        return match ((string) $value) {
            'Goods Issue' => 'goods_issue',
            'Delivery' => 'delivery',
            'AP Service' => 'ap_service',
            default => Str::snake((string) $value),
        };
    }
}
