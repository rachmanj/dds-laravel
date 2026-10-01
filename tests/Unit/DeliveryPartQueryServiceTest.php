<?php

namespace Tests\Unit;

use App\Services\Logistics\DeliveryPartQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DeliveryPartQueryServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_rows_skips_cache_put_when_payload_exceeds_safe_size(): void
    {
        Cache::spy();

        $dbRows = [];
        for ($i = 0; $i < 800; $i++) {
            $dbRows[] = (object) [
                'grpo_no' => 'GRPO-'.$i,
                'doc_entry' => $i,
                'ito_no' => 'ITO-'.$i,
                'ito_date' => '2026-09-01',
                'ito_created_date' => '2026-09-01 08:00:00',
                'iti_no' => null,
                'iti_date' => null,
                'item_code' => 'PART-'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'description' => str_repeat('Deskripsi panjang untuk uji cache ', 4),
                'uom' => 'PCS',
                'qty' => 1.0,
                'po_no' => 'PO-'.$i,
                'pr_no' => 'PR-'.$i,
                'mr_no' => 'MR-'.$i,
                'unit_no' => 'UNIT-'.$i,
                'vendor' => 'Vendor panjang '.$i,
                'from_warehouse' => '01-HO',
                'to_warehouse' => '02-SPT',
                'delivery_status' => 'Not Delivered',
                'delivery_date' => null,
                'remarks' => str_repeat('catatan ', 10),
            ];
        }

        $connection = Mockery::mock();
        $connection->shouldReceive('select')->once()->andReturn($dbRows);
        DB::shouldReceive('connection')->with('sap_sql')->andReturn($connection);

        $service = new DeliveryPartQueryService;
        $from = Carbon::parse('2026-09-01');
        $to = Carbon::parse('2026-09-30');

        $rows = $service->rows($from, $to);

        $this->assertCount(800, $rows);
        Cache::shouldNotHaveReceived('put');
    }

    public function test_rows_returns_data_when_cache_put_throws(): void
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once()->andThrow(new \RuntimeException('cache write failed'));

        $connection = Mockery::mock();
        $connection->shouldReceive('select')->once()->andReturn([
            (object) [
                'grpo_no' => 'G1',
                'doc_entry' => 1,
                'ito_no' => 'ITO-1',
                'ito_date' => '2026-09-01',
                'ito_created_date' => '2026-09-01',
                'iti_no' => null,
                'iti_date' => null,
                'item_code' => 'P1',
                'description' => 'Desc',
                'uom' => 'PCS',
                'qty' => 1.0,
                'po_no' => 'PO',
                'pr_no' => null,
                'mr_no' => null,
                'unit_no' => 'U1',
                'vendor' => 'V',
                'from_warehouse' => '01',
                'to_warehouse' => '02-SPT',
                'delivery_status' => 'Not Delivered',
                'delivery_date' => null,
                'remarks' => null,
            ],
        ]);
        DB::shouldReceive('connection')->with('sap_sql')->andReturn($connection);

        $service = new DeliveryPartQueryService;
        $rows = $service->rows(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-05'));

        $this->assertCount(1, $rows);
        $this->assertSame('ITO-1', $rows->first()['ito_no']);
    }

    public function test_rows_returns_data_when_cache_get_throws(): void
    {
        Cache::shouldReceive('get')->once()->andThrow(new \RuntimeException('cache read failed'));

        $connection = Mockery::mock();
        $connection->shouldReceive('select')->once()->andReturn([]);
        DB::shouldReceive('connection')->with('sap_sql')->andReturn($connection);

        $service = new DeliveryPartQueryService;
        $rows = $service->rows(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-02'));

        $this->assertCount(0, $rows);
    }

    public function test_validate_page_date_range_rejects_more_than_92_days(): void
    {
        $service = new DeliveryPartQueryService;

        $error = $service->validatePageDateRange(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-09-15'),
        );

        $this->assertSame(DeliveryPartQueryService::PAGE_DATE_RANGE_ERROR, $error);
    }

    public function test_validate_page_date_range_accepts_92_day_span(): void
    {
        $service = new DeliveryPartQueryService;

        $from = Carbon::parse('2026-01-01');
        $to = $from->copy()->addDays(92);

        $this->assertNull($service->validatePageDateRange($from, $to));
    }
}
