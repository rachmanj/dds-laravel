<?php

namespace Tests\Feature;

use App\Exports\LogisticsInventoryExport;
use App\Models\LogisticsInventoryItem;
use App\Models\LogisticsInventoryPivot;
use App\Models\LogisticsInventorySnapshot;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LogisticsInventoryPageTest extends TestCase
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

    private function createUserWithoutLogisticsPermission(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    /**
     * @return array{snapshot: LogisticsInventorySnapshot, item: LogisticsInventoryItem}
     */
    private function createSuccessfulSnapshot(): array
    {
        $snapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-15',
            'status' => 'success',
            'row_count' => 1,
            'total_value' => 10000,
            'duration_ms' => 120,
            'created_at' => now(),
        ]);

        $item = LogisticsInventoryItem::query()->create([
            'snapshot_id' => $snapshot->id,
            'model_no' => 'MODEL-A',
            'unit_no' => 'U-001',
            'item_code' => 'SP-001',
            'item_name' => 'Spare Part A',
            'category' => 'SPAREPART',
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
        ]);

        LogisticsInventoryPivot::query()->create([
            'snapshot_id' => $snapshot->id,
            'project' => '022C',
            'category' => 'SPAREPART',
            'sum_instock' => 10,
            'sum_value' => 10000,
        ]);

        return ['snapshot' => $snapshot, 'item' => $item];
    }

    public function test_guest_cannot_access_inventory_summary_page(): void
    {
        $this->get(route('logistics.inventory.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        $user = $this->createUserWithoutLogisticsPermission();

        $this->actingAs($user)
            ->get(route('logistics.inventory.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('logistics.inventory.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertForbidden();
    }

    public function test_user_with_permission_can_open_inventory_summary_page(): void
    {
        $user = $this->createLogisticUser();
        $this->createSuccessfulSnapshot();

        $this->actingAs($user)
            ->get(route('logistics.inventory.index'))
            ->assertOk()
            ->assertSee('Ringkasan Inventory')
            ->assertSee('Total Item')
            ->assertSee('Total Nilai')
            ->assertSee('Jumlah Warehouse');
    }

    public function test_export_returns_excel_file_for_user_with_export_permission(): void
    {
        $user = $this->createLogisticUser();
        $this->createSuccessfulSnapshot();

        $response = $this->actingAs($user)
            ->get(route('logistics.inventory.export'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_export_uses_query_builder_and_writes_all_items_from_latest_successful_snapshot(): void
    {
        $user = $this->createLogisticUser();
        ['snapshot' => $snapshot] = $this->createSuccessfulSnapshot();

        for ($index = 2; $index <= 4; $index++) {
            LogisticsInventoryItem::query()->create([
                'snapshot_id' => $snapshot->id,
                'item_code' => 'SP-00'.$index,
                'item_name' => 'Spare Part '.$index,
                'category' => 'SPAREPART',
                'instock' => 5,
                'total_value' => 5000,
                'whs_code' => 'WH01',
            ]);
        }

        LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-16',
            'status' => 'failed',
            'row_count' => 0,
            'total_value' => 0,
            'error_message' => 'SAP connection failed',
            'duration_ms' => 50,
            'created_at' => now(),
        ]);

        $expectedItemCount = LogisticsInventoryItem::query()
            ->where('snapshot_id', $snapshot->id)
            ->count();

        $query = LogisticsInventoryItem::query()
            ->where('snapshot_id', $snapshot->id)
            ->orderBy('item_code');

        $export = new LogisticsInventoryExport($query);

        $this->assertInstanceOf(FromQuery::class, $export);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $export->query());
        $this->assertSame(LogisticsInventoryItem::class, $export->query()->getModel()::class);

        $response = $this->actingAs($user)
            ->get(route('logistics.inventory.export'));

        $response->assertOk();

        $spreadsheet = IOFactory::load($response->getFile()->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $this->assertCount($expectedItemCount + 1, $rows);
        $this->assertSame('Item No.', $rows[0][2]);
        $this->assertSame('SP-001', $rows[1][2]);
    }

    public function test_warning_banner_shown_when_no_snapshot_exists(): void
    {
        $user = $this->createLogisticUser();

        $this->actingAs($user)
            ->get(route('logistics.inventory.index'))
            ->assertOk()
            ->assertSee('Belum ada snapshot inventory');
    }

    public function test_warning_banner_shown_when_latest_snapshot_failed(): void
    {
        $user = $this->createLogisticUser();
        $this->createSuccessfulSnapshot();

        LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-16',
            'status' => 'failed',
            'row_count' => 0,
            'total_value' => 0,
            'error_message' => 'SAP connection failed',
            'duration_ms' => 50,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('logistics.inventory.index'))
            ->assertOk()
            ->assertSee('Snapshot terakhir gagal')
            ->assertSee('SAP connection failed');
    }

    public function test_data_endpoint_returns_items_from_latest_successful_snapshot(): void
    {
        $user = $this->createLogisticUser();
        $this->createSuccessfulSnapshot();

        LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-16',
            'status' => 'failed',
            'row_count' => 0,
            'total_value' => 0,
            'error_message' => 'SAP connection failed',
            'duration_ms' => 50,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('logistics.inventory.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 1);
    }
}
