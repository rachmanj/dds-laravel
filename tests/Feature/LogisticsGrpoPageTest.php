<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\SapGrpoRepository;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsGrpoPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow('2026-09-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createLogisticUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('logistic');

        return $user;
    }

    private function createUserWithViewOnlyPermission(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('view-logistics-summary');

        return $user;
    }

    private function createUserWithoutLogisticsPermission(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleGrpoRows(): array
    {
        return [
            [
                'grpo_date' => '2026-09-10',
                'grpo_created_date' => '2026-09-10 08:00:00',
                'grpo_no' => '2605001',
                'po_no' => '2602001',
                'po_date' => '2026-09-05',
                'po_created_date' => '2026-09-05 09:00:00',
                'po_delivery_status' => 'Delivered',
                'po_delivery_time' => '2026-09-08',
                'pr_no' => 'PR-100',
                'item_code' => 'SP-001',
                'oem_no' => 'OEM-001',
                'item_name' => 'Spare Part A',
                'u_mis_consre1' => null,
                'u_mis_consre2' => null,
                'quantity' => 10.0,
                'u_mis_unitno' => 'U-001',
                'currency' => 'IDR',
                'price' => 1000.0,
                'total_price' => 10000.0,
                'uom' => 'PCS',
                'warehouse_code' => 'WH01',
                'warehouse_name' => 'Warehouse 01',
                'received_by' => 'Budi',
                'time' => '10:30',
                'project' => '022C',
                'department' => 'LOG',
                'comments' => 'Test comment',
                'vendor_code' => 'VEND01',
                'vendor_name' => 'Vendor Satu',
            ],
            [
                'grpo_date' => '2026-09-12',
                'grpo_created_date' => '2026-09-12 08:00:00',
                'grpo_no' => '2605002',
                'po_no' => '2602002',
                'po_date' => '2026-09-06',
                'po_created_date' => '2026-09-06 09:00:00',
                'po_delivery_status' => 'Not Delivered',
                'po_delivery_time' => null,
                'pr_no' => 'PR-101',
                'item_code' => 'SP-002',
                'oem_no' => 'OEM-002',
                'item_name' => 'Spare Part B',
                'u_mis_consre1' => null,
                'u_mis_consre2' => null,
                'quantity' => 5.0,
                'u_mis_unitno' => 'U-002',
                'currency' => 'IDR',
                'price' => 2000.0,
                'total_price' => 10000.0,
                'uom' => 'PCS',
                'warehouse_code' => 'WH02',
                'warehouse_name' => 'Warehouse 02',
                'received_by' => 'Ani',
                'time' => '11:00',
                'project' => '023D',
                'department' => 'LOG',
                'comments' => null,
                'vendor_code' => 'VEND02',
                'vendor_name' => 'Vendor Dua',
            ],
        ];
    }

    private function mockGrpoRepository(array $rows): void
    {
        $this->mock(SapGrpoRepository::class, function ($mock) use ($rows) {
            $mock->shouldReceive('fetch')
                ->andReturn($rows);
        });
    }

    public function test_guest_cannot_access_grpo_summary_page(): void
    {
        $this->get(route('logistics.grpo.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        $user = $this->createUserWithoutLogisticsPermission();

        $this->actingAs($user)
            ->get(route('logistics.grpo.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('logistics.grpo.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('logistics.grpo.export'))
            ->assertForbidden();
    }

    public function test_user_with_permission_can_open_grpo_summary_page(): void
    {
        $user = $this->createLogisticUser();
        $this->mockGrpoRepository($this->sampleGrpoRows());

        $this->actingAs($user)
            ->get(route('logistics.grpo.index'))
            ->assertOk()
            ->assertSee('GRPO')
            ->assertSee('Jumlah GRPO')
            ->assertSee('Jumlah Baris')
            ->assertSee('Total Nilai');
    }

    public function test_index_defaults_date_filter_to_current_month(): void
    {
        $user = $this->createLogisticUser();
        $this->mockGrpoRepository([]);

        $this->actingAs($user)
            ->get(route('logistics.grpo.index'))
            ->assertOk()
            ->assertViewHas('fromDate', '2026-09-01')
            ->assertViewHas('toDate', '2026-09-15');
    }

    public function test_export_returns_excel_file_for_user_with_export_permission(): void
    {
        $user = $this->createLogisticUser();
        $this->mockGrpoRepository($this->sampleGrpoRows());

        $response = $this->actingAs($user)
            ->get(route('logistics.grpo.export', [
                'from_date' => '2026-09-01',
                'to_date' => '2026-09-15',
            ]));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_user_without_export_permission_cannot_export(): void
    {
        $user = $this->createUserWithViewOnlyPermission();
        $this->mockGrpoRepository($this->sampleGrpoRows());

        $this->actingAs($user)
            ->get(route('logistics.grpo.export', [
                'from_date' => '2026-09-01',
                'to_date' => '2026-09-15',
            ]))
            ->assertForbidden();
    }

    public function test_data_endpoint_returns_rows_from_mocked_repository(): void
    {
        $user = $this->createLogisticUser();
        $this->mockGrpoRepository($this->sampleGrpoRows());

        $response = $this->actingAs($user)->getJson(route('logistics.grpo.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-15',
        ]));

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 2);
        $response->assertJsonFragment(['grpo_no' => '2605001']);
        $response->assertJsonFragment(['item_code' => 'SP-002']);
    }

    public function test_index_rejects_date_range_over_92_days(): void
    {
        $user = $this->createLogisticUser();
        $this->mockGrpoRepository([]);

        $from = '2026-01-01';
        $to = '2026-09-15';

        $this->actingAs($user)
            ->get(route('logistics.grpo.index', [
                'from_date' => $from,
                'to_date' => $to,
            ]))
            ->assertOk()
            ->assertSee('Rentang tanggal tidak boleh lebih dari 92 hari', false);
    }

    public function test_data_endpoint_rejects_date_range_over_92_days(): void
    {
        $user = $this->createLogisticUser();

        $response = $this->actingAs($user)->getJson(route('logistics.grpo.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'from_date' => '2026-01-01',
            'to_date' => '2026-09-15',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonFragment([
            'message' => 'Rentang tanggal tidak boleh lebih dari 92 hari.',
        ]);
    }

    public function test_export_rejects_date_range_over_92_days(): void
    {
        $user = $this->createLogisticUser();

        $this->actingAs($user)
            ->get(route('logistics.grpo.export', [
                'from_date' => '2026-01-01',
                'to_date' => '2026-09-15',
            ]))
            ->assertSessionHasErrors('date_range');
    }
}
