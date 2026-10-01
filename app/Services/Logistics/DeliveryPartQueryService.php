<?php

namespace App\Services\Logistics;

use App\Exceptions\SapSqlQueryException;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliveryPartQueryService
{
    public const MAX_CACHE_PAYLOAD_BYTES = 262144;

    public const MAX_PAGE_DATE_RANGE_DAYS = 92;

    public const PAGE_DATE_RANGE_ERROR = 'Rentang tanggal tidak boleh lebih dari 92 hari.';

    private const CACHE_TTL_SECONDS = 300;

    private const DISTINCT_WAREHOUSE_CACHE_TTL_SECONDS = 600;

    public function rows(Carbon $fromDate, Carbon $toDate): Collection
    {
        $cacheKey = $this->cacheKey($fromDate, $toDate);

        $cached = $this->cacheGet($cacheKey);
        if ($cached instanceof Collection) {
            return $cached;
        }

        $rows = $this->fetchRows($fromDate, $toDate);
        $this->cachePutIfSmall($cacheKey, $rows, self::CACHE_TTL_SECONDS, 'delivery_part_sap_rows');

        return $rows;
    }

    public function fetchRowsWithoutCache(Carbon $fromDate, Carbon $toDate): Collection
    {
        return $this->fetchRows($fromDate, $toDate);
    }

    public function validatePageDateRange(Carbon $fromDate, Carbon $toDate): ?string
    {
        $from = $fromDate->copy()->startOfDay();
        $to = $toDate->copy()->startOfDay();

        if ($from->gt($to)) {
            return self::PAGE_DATE_RANGE_ERROR;
        }

        if ($from->diffInDays($to) > self::MAX_PAGE_DATE_RANGE_DAYS) {
            return self::PAGE_DATE_RANGE_ERROR;
        }

        return null;
    }

    public function bust(?Carbon $fromDate = null, ?Carbon $toDate = null): void
    {
        if ($fromDate !== null && $toDate !== null) {
            $this->cacheForget($this->cacheKey($fromDate, $toDate));

            return;
        }

        // Best-effort: forget is keyed; callers should pass dates when refreshing a filter.
    }

    public function bustForRange(Carbon $fromDate, Carbon $toDate): void
    {
        $this->cacheForget($this->cacheKey($fromDate, $toDate));
        $this->cacheForget($this->distinctWarehouseCacheKey($fromDate, $toDate));
    }

    /**
     * @return list<array{whs_code: string, row_count: int, document_count: int}>
     */
    public function distinctToWarehouses(Carbon $fromDate, Carbon $toDate): array
    {
        $cacheKey = $this->distinctWarehouseCacheKey($fromDate, $toDate);

        $cached = $this->cacheGet($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $rows = $this->fetchDistinctToWarehouses($fromDate, $toDate);
        $this->cachePutIfSmall($cacheKey, $rows, self::DISTINCT_WAREHOUSE_CACHE_TTL_SECONDS, 'delivery_part_distinct_to_wh');

        return $rows;
    }

    private function cacheKey(Carbon $fromDate, Carbon $toDate): string
    {
        return 'delivery_part_sap_rows:'.$fromDate->toDateString().'|'.$toDate->toDateString();
    }

    private function distinctWarehouseCacheKey(Carbon $fromDate, Carbon $toDate): string
    {
        return 'delivery_part_distinct_to_wh:'.$fromDate->toDateString().'|'.$toDate->toDateString();
    }

    private function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (Throwable $e) {
            Log::warning('Delivery Part cache get failed: '.$e->getMessage(), ['key' => $key]);

            return null;
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>|list<array{whs_code: string, row_count: int, document_count: int}>  $payload
     */
    private function cachePutIfSmall(string $key, Collection|array $payload, int $ttlSeconds, string $context): void
    {
        try {
            $serialized = serialize($payload instanceof Collection ? $payload->all() : $payload);
            if (strlen($serialized) > self::MAX_CACHE_PAYLOAD_BYTES) {
                Log::debug('Delivery Part cache skipped: payload exceeds safe size', [
                    'context' => $context,
                    'key' => $key,
                    'bytes' => strlen($serialized),
                    'max_bytes' => self::MAX_CACHE_PAYLOAD_BYTES,
                ]);

                return;
            }

            Cache::put($key, $payload, $ttlSeconds);
        } catch (Throwable $e) {
            Log::warning('Delivery Part cache put failed: '.$e->getMessage(), [
                'context' => $context,
                'key' => $key,
            ]);
        }
    }

    private function cacheForget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (Throwable $e) {
            Log::warning('Delivery Part cache forget failed: '.$e->getMessage(), ['key' => $key]);
        }
    }

    /**
     * @return list<array{whs_code: string, row_count: int, document_count: int}>
     */
    private function fetchDistinctToWarehouses(Carbon $fromDate, Carbon $toDate): array
    {
        $from = $fromDate->toDateString();
        $to = $toDate->toDateString();

        $sql = <<<'SQL'
            SELECT
                T0.U_MIS_ToWarehouse AS to_warehouse,
                COUNT(*) AS row_count,
                COUNT(DISTINCT T0.DocNum) AS document_count
            FROM OWTR T0
            INNER JOIN WTR1 T1 ON T0.DocEntry = T1.DocEntry
            INNER JOIN OITW T2 ON T1.ItemCode = T2.ItemCode
            WHERE T0.DocDate >= ?
                AND T0.DocDate <= ?
                AND T2.WhsCode = T0.Filler
                AND T0.U_MIS_TransferType = 'OUT'
            GROUP BY T0.U_MIS_ToWarehouse
            ORDER BY row_count DESC
            SQL;

        try {
            $results = DB::connection('sap_sql')->select($sql, [$from, $to]);
        } catch (Throwable $e) {
            Log::channel('sap')->error('Delivery Part distinct warehouse query failed: '.$e->getMessage());
            throw SapSqlQueryException::connectionFailed($e->getMessage());
        }

        $rows = [];

        foreach ($results as $result) {
            $row = (array) $result;
            $whsCode = $this->normalizeString($row['to_warehouse'] ?? null);

            if ($whsCode === null) {
                continue;
            }

            $rows[] = [
                'whs_code' => $whsCode,
                'row_count' => (int) ($row['row_count'] ?? 0),
                'document_count' => (int) ($row['document_count'] ?? 0),
            ];
        }

        return $rows;
    }

    private function fetchRows(Carbon $fromDate, Carbon $toDate): Collection
    {
        $from = $fromDate->toDateString();
        $to = $toDate->toDateString();

        $sql = <<<'SQL'
            SELECT DISTINCT
                T10.DocNum AS grpo_no,
                T0.DocEntry AS doc_entry,
                T0.DocNum AS ito_no,
                T0.DocDate AS ito_date,
                T0.CreateDate AS ito_created_date,
                T3.DocNum AS iti_no,
                T3.DocDate AS iti_date,
                T1.ItemCode AS item_code,
                T1.Dscription AS description,
                T1.unitMsr AS uom,
                T1.Quantity AS qty,
                T6.DocNum AS po_no,
                T7.DocNum AS pr_no,
                T9.DocNum AS mr_no,
                T7.U_MIS_UnitNo AS unit_no,
                T4.CardName AS vendor,
                T0.Filler AS from_warehouse,
                T0.U_MIS_ToWarehouse AS to_warehouse,
                CASE T0.U_ARK_DelivStat WHEN 'N' THEN 'Not Delivered' WHEN 'Y' THEN 'Delivered' END AS delivery_status,
                T0.U_MIS_DeliveryTime AS delivery_date,
                T0.Comments AS remarks
            FROM OWTR T0
            INNER JOIN WTR1 T1 ON T0.DocEntry = T1.DocEntry
            INNER JOIN OITW T2 ON T1.ItemCode = T2.ItemCode
            LEFT JOIN OWTR T3 ON T0.DocNum = T3.U_MIS_DocRefNo
            LEFT JOIN OPDN T4 ON T0.U_MIS_GRPONo = T4.DocNum
            LEFT JOIN PDN1 T5 ON T4.DocEntry = T5.DocEntry
            LEFT JOIN OPOR T6 ON T5.BaseRef = T6.DocNum
            LEFT JOIN OPRQ T7 ON T6.U_MIS_PRNo = T7.DocNum
            LEFT JOIN POR1 T8 ON T6.DocEntry = T8.DocEntry
            LEFT JOIN ORDR T9 ON T8.U_MISMRNo = T9.DocNum
            LEFT JOIN OPDN T10 ON T0.U_MIS_GRPONo = T10.DocNum
            WHERE T0.DocDate >= ?
                AND T0.DocDate <= ?
                AND T2.WhsCode = T0.Filler
                AND T0.U_MIS_TransferType = 'OUT'
            SQL;

        try {
            $results = DB::connection('sap_sql')->select($sql, [$from, $to]);
        } catch (Throwable $e) {
            Log::channel('sap')->error('Delivery Part SAP query failed: '.$e->getMessage());
            throw SapSqlQueryException::connectionFailed($e->getMessage());
        }

        return collect($results)->map(fn ($row) => $this->normalizeRow((array) $row));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        return [
            'grpo_no' => $this->normalizeString($row['grpo_no'] ?? null),
            'doc_entry' => isset($row['doc_entry']) ? (int) $row['doc_entry'] : null,
            'ito_no' => $this->normalizeString($row['ito_no'] ?? null),
            'ito_date' => $this->normalizeDate($row['ito_date'] ?? null),
            'ito_created_date' => $this->normalizeDateTime($row['ito_created_date'] ?? null),
            'iti_no' => $this->normalizeString($row['iti_no'] ?? null),
            'iti_date' => $this->normalizeDate($row['iti_date'] ?? null),
            'item_code' => $this->normalizeString($row['item_code'] ?? null),
            'description' => $this->normalizeString($row['description'] ?? null),
            'uom' => $this->normalizeString($row['uom'] ?? null),
            'qty' => $this->normalizeQty($row['qty'] ?? null),
            'po_no' => $this->normalizeString($row['po_no'] ?? null),
            'pr_no' => $this->normalizeString($row['pr_no'] ?? null),
            'mr_no' => $this->normalizeString($row['mr_no'] ?? null),
            'unit_no' => $this->normalizeString($row['unit_no'] ?? null),
            'vendor' => $this->normalizeString($row['vendor'] ?? null),
            'from_warehouse' => $this->normalizeString($row['from_warehouse'] ?? null),
            'to_warehouse' => $this->normalizeString($row['to_warehouse'] ?? null),
            'delivery_status' => $this->normalizeString($row['delivery_status'] ?? null),
            'delivery_date' => $this->normalizeDate($row['delivery_date'] ?? null),
            'remarks' => $this->normalizeString($row['remarks'] ?? null),
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function normalizeQty(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function normalizeDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        return Carbon::parse((string) $value)->startOfDay();
    }

    private function normalizeDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy();
        }

        return Carbon::parse((string) $value);
    }
}
