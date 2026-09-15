<?php

namespace Tests\Feature;

use App\Models\LogisticsInventoryItem;
use App\Models\LogisticsInventoryPivot;
use App\Models\LogisticsInventorySnapshot;
use App\Repositories\SapInventoryRepository;
use App\Services\Logistics\ItemCategoryResolver;
use Carbon\Carbon;
use Database\Seeders\LogisticsItemCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LogisticsInventorySnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LogisticsItemCategorySeeder::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleInventoryRows(): array
    {
        return [
            [
                'model_no' => 'MODEL-A',
                'unit_no' => 'U-001',
                'item_code' => 'SP-0280806120',
                'item_name' => 'Spare Part A',
                'uom' => 'PCS',
                'instock' => 10,
                'committed' => 2,
                'ordered' => 0,
                'currency' => 'IDR',
                'last_price' => 1000,
                'total_value' => 10000,
                'whs_code' => 'WH01',
                'whs_name' => 'Warehouse 01',
                'project' => '022C',
                'status' => 'Active',
                'last_mr_no' => 'MR-100',
                'last_mi_no' => 'MI-200',
            ],
            [
                'model_no' => null,
                'unit_no' => null,
                'item_code' => 'VOE11030271',
                'item_name' => 'Volvo Part',
                'uom' => 'PCS',
                'instock' => 5,
                'committed' => 0,
                'ordered' => 1,
                'currency' => 'IDR',
                'last_price' => 2000,
                'total_value' => 10000,
                'whs_code' => 'WH01',
                'whs_name' => 'Warehouse 01',
                'project' => '022C',
                'status' => 'Active',
                'last_mr_no' => null,
                'last_mi_no' => null,
            ],
            [
                'model_no' => null,
                'unit_no' => null,
                'item_code' => 'UNKNOWN-999',
                'item_name' => 'Unknown Item',
                'uom' => 'PCS',
                'instock' => 1,
                'committed' => 0,
                'ordered' => 0,
                'currency' => 'IDR',
                'last_price' => 500,
                'total_value' => 500,
                'whs_code' => 'WH02',
                'whs_name' => 'Warehouse 02',
                'project' => '017C',
                'status' => 'Inactive',
                'last_mr_no' => null,
                'last_mi_no' => null,
            ],
        ];
    }

    public function test_snapshot_command_creates_successful_snapshot_with_pivots_and_items(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 06:00:00', 'Asia/Makassar'));

        $this->mock(SapInventoryRepository::class, function ($mock): void {
            $mock->shouldReceive('fetchAll')
                ->once()
                ->andReturn($this->sampleInventoryRows());
        });

        $this->artisan('logistics:snapshot-inventory')
            ->assertSuccessful();

        $snapshot = LogisticsInventorySnapshot::query()->first();
        $this->assertNotNull($snapshot);
        $this->assertSame('2026-09-15', $snapshot->snapshot_date->format('Y-m-d'));
        $this->assertSame('success', $snapshot->status);
        $this->assertSame(3, $snapshot->row_count);
        $this->assertSame(20500.0, (float) $snapshot->total_value);
        $this->assertNull($snapshot->error_message);
        $this->assertNotNull($snapshot->duration_ms);

        $this->assertSame(3, LogisticsInventoryItem::query()->count());

        $sparepartPivot = LogisticsInventoryPivot::query()
            ->where('project', '022C')
            ->where('category', 'SPAREPART')
            ->first();
        $this->assertNotNull($sparepartPivot);
        $this->assertSame(15.0, (float) $sparepartPivot->sum_instock);
        $this->assertSame(20000.0, (float) $sparepartPivot->sum_value);

        $uncategorizedPivot = LogisticsInventoryPivot::query()
            ->where('project', '017C')
            ->where('category', '(tanpa kategori)')
            ->first();
        $this->assertNotNull($uncategorizedPivot);
        $this->assertSame(1.0, (float) $uncategorizedPivot->sum_instock);
        $this->assertSame(500.0, (float) $uncategorizedPivot->sum_value);

        $log = DB::table('sap_logs')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('logistics_inventory_snapshot', $log->action);
        $this->assertSame('success', $log->status);
    }

    public function test_item_category_resolver_applies_prefix_rules(): void
    {
        $resolver = app(ItemCategoryResolver::class);

        $this->assertSame('SPAREPART', $resolver->resolve('SP-0280806120'));
        $this->assertSame('SPAREPART', $resolver->resolve('SP1761372'));
        $this->assertSame('SPAREPART', $resolver->resolve('VOE11030271'));
        $this->assertSame('SOLAR', $resolver->resolve('SOLAR'));
        $this->assertSame('(tanpa kategori)', $resolver->resolve('UNKNOWN-999'));
    }

    public function test_snapshot_command_records_failed_status_and_sap_log_on_exception(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 06:00:00', 'Asia/Makassar'));

        $this->mock(SapInventoryRepository::class, function ($mock): void {
            $mock->shouldReceive('fetchAll')
                ->once()
                ->andThrow(new \RuntimeException('SAP connection failed'));
        });

        $this->artisan('logistics:snapshot-inventory')
            ->assertFailed();

        $snapshot = LogisticsInventorySnapshot::query()->first();
        $this->assertNotNull($snapshot);
        $this->assertSame('failed', $snapshot->status);
        $this->assertSame('SAP connection failed', $snapshot->error_message);
        $this->assertSame(0, LogisticsInventoryItem::query()->count());
        $this->assertSame(0, LogisticsInventoryPivot::query()->count());

        $log = DB::table('sap_logs')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('logistics_inventory_snapshot', $log->action);
        $this->assertSame('failed', $log->status);
        $this->assertSame('SAP connection failed', $log->error_message);
    }

    public function test_prune_command_removes_old_snapshots_and_detail_except_retained(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 06:00:00', 'Asia/Makassar'));

        $oldSnapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2025-08-01',
            'status' => 'success',
            'row_count' => 1,
            'total_value' => 100,
            'duration_ms' => 10,
            'created_at' => now()->subMonths(14),
        ]);

        $augustMidSnapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-08-10',
            'status' => 'success',
            'row_count' => 1,
            'total_value' => 200,
            'duration_ms' => 10,
            'created_at' => now()->subMonth(),
        ]);

        $augustEndSnapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-08-31',
            'status' => 'success',
            'row_count' => 1,
            'total_value' => 300,
            'duration_ms' => 10,
            'created_at' => now()->subDays(15),
        ]);

        $septemberMidSnapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-10',
            'status' => 'success',
            'row_count' => 1,
            'total_value' => 400,
            'duration_ms' => 10,
            'created_at' => now()->subDays(5),
        ]);

        $latestSnapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-15',
            'status' => 'success',
            'row_count' => 1,
            'total_value' => 500,
            'duration_ms' => 10,
            'created_at' => now(),
        ]);

        foreach ([$oldSnapshot, $augustMidSnapshot, $augustEndSnapshot, $septemberMidSnapshot, $latestSnapshot] as $snapshot) {
            LogisticsInventoryItem::query()->create([
                'snapshot_id' => $snapshot->id,
                'item_code' => 'ITEM-'.$snapshot->id,
                'item_name' => 'Item '.$snapshot->id,
                'category' => 'SPAREPART',
                'uom' => 'PCS',
                'instock' => 1,
                'committed' => 0,
                'ordered' => 0,
                'last_price' => 100,
                'total_value' => 100,
                'whs_code' => 'WH01',
                'whs_name' => 'Warehouse 01',
                'project' => '022C',
                'status' => 'Active',
            ]);
        }

        $this->artisan('logistics:prune-inventory')
            ->assertSuccessful();

        $this->assertDatabaseMissing('logistics_inventory_snapshots', ['id' => $oldSnapshot->id]);

        $this->assertDatabaseHas('logistics_inventory_snapshots', ['id' => $augustMidSnapshot->id]);
        $this->assertDatabaseHas('logistics_inventory_snapshots', ['id' => $augustEndSnapshot->id]);
        $this->assertDatabaseHas('logistics_inventory_snapshots', ['id' => $septemberMidSnapshot->id]);
        $this->assertDatabaseHas('logistics_inventory_snapshots', ['id' => $latestSnapshot->id]);

        $this->assertDatabaseMissing('logistics_inventory_items', ['snapshot_id' => $augustMidSnapshot->id]);
        $this->assertDatabaseHas('logistics_inventory_items', ['snapshot_id' => $augustEndSnapshot->id]);
        $this->assertDatabaseMissing('logistics_inventory_items', ['snapshot_id' => $septemberMidSnapshot->id]);
        $this->assertDatabaseHas('logistics_inventory_items', ['snapshot_id' => $latestSnapshot->id]);
    }
}
