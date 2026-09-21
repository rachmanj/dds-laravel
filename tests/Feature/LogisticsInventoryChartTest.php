<?php

namespace Tests\Feature;

use App\Models\LogisticsInventoryItem;
use App\Models\LogisticsInventorySnapshot;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsInventoryChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function createLogisticUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('logistic');

        return $user;
    }

    private function createSnapshotItem(
        LogisticsInventorySnapshot $snapshot,
        string $whsCode,
        ?string $whsName,
        float $totalValue,
    ): void {
        LogisticsInventoryItem::query()->create([
            'snapshot_id' => $snapshot->id,
            'item_code' => 'ITEM-'.$whsCode.'-'.$totalValue,
            'category' => 'SPAREPART',
            'instock' => 1,
            'total_value' => $totalValue,
            'whs_code' => $whsCode,
            'whs_name' => $whsName,
        ]);
    }

    public function test_value_by_warehouse_chart_uses_warehouse_name_as_label(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');

        $snapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-20',
            'status' => 'success',
            'row_count' => 2,
            'total_value' => 300,
            'duration_ms' => 100,
            'created_at' => now(),
        ]);

        $this->createSnapshotItem($snapshot, 'WH-A', 'Gudang Alpha', 100);
        $this->createSnapshotItem($snapshot, 'WH-B', null, 200);

        $user = $this->createLogisticUser();

        $this->actingAs($user)
            ->get(route('logistics.inventory.index'))
            ->assertOk()
            ->assertViewHas('valueByWarehouseChart', function (array $chart): bool {
                return $chart['labels'] === ['WH-B', 'Gudang Alpha']
                    && $chart['values'] === [200.0, 100.0];
            });
    }

    public function test_value_by_warehouse_chart_groups_extra_warehouses_into_lainnya(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');

        $snapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-20',
            'status' => 'success',
            'row_count' => 13,
            'total_value' => 910,
            'duration_ms' => 100,
            'created_at' => now(),
        ]);

        for ($index = 1; $index <= 13; $index++) {
            $this->createSnapshotItem(
                $snapshot,
                sprintf('WH%02d', $index),
                'Warehouse '.$index,
                (float) $index * 10,
            );
        }

        $user = $this->createLogisticUser();

        $this->actingAs($user)
            ->get(route('logistics.inventory.index'))
            ->assertOk()
            ->assertViewHas('valueByWarehouseChart', function (array $chart): bool {
                if (count($chart['labels']) !== 13 || count($chart['values']) !== 13) {
                    return false;
                }

                if ($chart['labels'][12] !== 'Lainnya' || $chart['values'][12] !== 10.0) {
                    return false;
                }

                return $chart['labels'][0] === 'Warehouse 13'
                    && $chart['values'][0] === 130.0
                    && $chart['labels'][11] === 'Warehouse 2'
                    && $chart['values'][11] === 20.0;
            });
    }

    public function test_monthly_value_chart_uses_latest_successful_snapshot_per_month_and_null_for_missing_months(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');

        $snapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-20',
            'status' => 'success',
            'row_count' => 1,
            'total_value' => 5000,
            'duration_ms' => 100,
            'created_at' => now(),
        ]);

        $this->createSnapshotItem($snapshot, 'WH01', 'Warehouse 01', 5000);

        LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-05',
            'status' => 'success',
            'row_count' => 1,
            'total_value' => 1000,
            'duration_ms' => 100,
            'created_at' => now(),
        ]);

        LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-08-31',
            'status' => 'success',
            'row_count' => 1,
            'total_value' => 2500,
            'duration_ms' => 100,
            'created_at' => now(),
        ]);

        LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-08-15',
            'status' => 'failed',
            'row_count' => 0,
            'total_value' => 0,
            'error_message' => 'SAP error',
            'duration_ms' => 50,
            'created_at' => now(),
        ]);

        $user = $this->createLogisticUser();

        $this->actingAs($user)
            ->get(route('logistics.inventory.index'))
            ->assertOk()
            ->assertViewHas('monthlyValueChart', function (array $chart): bool {
                if (count($chart['labels']) !== 12 || count($chart['values']) !== 12) {
                    return false;
                }

                if ($chart['labels'][10] !== 'Agu 2026' || $chart['values'][10] !== 2500.0) {
                    return false;
                }

                if ($chart['labels'][11] !== 'Sep 2026' || $chart['values'][11] !== 5000.0) {
                    return false;
                }

                return $chart['values'][9] === null;
            });
    }
}
