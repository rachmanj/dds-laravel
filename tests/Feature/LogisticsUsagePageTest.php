<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\SapUsageRepository;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsUsagePageTest extends TestCase
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
    private function sampleUsageRows(): array
    {
        return [
            [
                'doc_num' => '1001',
                'create_date' => '2026-09-10 08:00:00',
                'doc_date' => '2026-09-10',
                'wo_no' => '',
                'subject' => '',
                'category' => '',
                'line' => 1,
                'issue_purpose' => 'Maintenance',
                'job_category' => 'Mechanical',
                'job_name' => 'Job A',
                'unit_no' => 'U-001',
                'model_no' => 'M-001',
                'serial_no' => 'SN-001',
                'hours_meter' => 100,
                'item_code' => 'SP-001',
                'dscription' => 'Spare Part A',
                'quantity' => 2.0,
                'stockprice' => 5000.0,
                'total' => 10000.0,
                'project' => '022C',
                'whs_name' => 'Warehouse 01',
                'u_mis_no_ba' => 'BA-001',
                'order_type' => '',
                'status_doc' => null,
                'gr_no' => 'GR-100',
                'm_ret_no' => null,
                'return_item_code' => null,
                'return_dscription' => null,
                'return_quantity' => null,
                'comments' => 'GI comment',
                'source' => 'goods_issue',
            ],
            [
                'doc_num' => '2001',
                'create_date' => '2026-09-12 08:00:00',
                'doc_date' => '2026-09-12',
                'wo_no' => 'WO-200',
                'subject' => 'Delivery subject',
                'category' => 'Schedule',
                'line' => 1,
                'issue_purpose' => 'Repair',
                'job_category' => 'Electrical',
                'job_name' => 'Job B',
                'unit_no' => 'U-002',
                'model_no' => 'M-002',
                'serial_no' => 'SN-002',
                'hours_meter' => 200,
                'item_code' => 'SP-002',
                'dscription' => 'Spare Part B',
                'quantity' => 1.0,
                'stockprice' => 15000.0,
                'total' => 15000.0,
                'project' => '023D',
                'whs_name' => 'Warehouse 02',
                'u_mis_no_ba' => 'BA-002',
                'order_type' => 'Regular',
                'status_doc' => '',
                'gr_no' => '',
                'm_ret_no' => 'MR-500',
                'return_item_code' => 'SP-002-R',
                'return_dscription' => 'Return part',
                'return_quantity' => 1.0,
                'comments' => 'Delivery comment',
                'source' => 'delivery',
            ],
            [
                'doc_num' => '3001',
                'create_date' => '2026-09-14 08:00:00',
                'doc_date' => '2026-09-14',
                'wo_no' => 'WO-300',
                'subject' => 'AP subject',
                'category' => 'Unschedule',
                'line' => 1,
                'issue_purpose' => 'Service',
                'job_category' => null,
                'job_name' => null,
                'unit_no' => 'U-003',
                'model_no' => 'M-003',
                'serial_no' => 'SN-003',
                'hours_meter' => 300,
                'item_code' => 'SV-LABOUR',
                'dscription' => 'Labour service',
                'quantity' => 1.0,
                'stockprice' => 8000.0,
                'total' => 8000.0,
                'project' => '022C',
                'whs_name' => null,
                'u_mis_no_ba' => null,
                'order_type' => 'Service',
                'status_doc' => null,
                'gr_no' => null,
                'm_ret_no' => null,
                'return_item_code' => null,
                'return_dscription' => null,
                'return_quantity' => null,
                'comments' => 'AP comment',
                'source' => 'ap_service',
            ],
        ];
    }

    private function mockUsageRepository(array $rows): void
    {
        $this->mock(SapUsageRepository::class, function ($mock) use ($rows) {
            $mock->shouldReceive('fetch')
                ->andReturn($rows);

            $mock->shouldReceive('fetchBySource')
                ->andReturnUsing(function (string $from, string $to, string $source) use ($rows): array {
                    return array_values(array_filter(
                        $rows,
                        fn (array $row): bool => ($row['source'] ?? '') === $source
                    ));
                });
        });
    }

    public function test_guest_cannot_access_usage_summary_page(): void
    {
        $this->get(route('logistics.usage.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        $user = $this->createUserWithoutLogisticsPermission();

        $this->actingAs($user)
            ->get(route('logistics.usage.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('logistics.usage.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('logistics.usage.export'))
            ->assertForbidden();
    }

    public function test_user_with_permission_can_open_usage_summary_page(): void
    {
        $user = $this->createLogisticUser();
        $this->mockUsageRepository($this->sampleUsageRows());

        $this->actingAs($user)
            ->get(route('logistics.usage.index'))
            ->assertOk()
            ->assertSee('Pemakaian')
            ->assertSee('Jumlah Baris')
            ->assertSee('Total Nilai')
            ->assertSee('Jumlah Dokumen');
    }

    public function test_index_defaults_date_filter_to_current_month(): void
    {
        $user = $this->createLogisticUser();
        $this->mockUsageRepository([]);

        $this->actingAs($user)
            ->get(route('logistics.usage.index'))
            ->assertOk()
            ->assertViewHas('fromDate', '2026-09-01')
            ->assertViewHas('toDate', '2026-09-15');
    }

    public function test_export_returns_excel_file_for_user_with_export_permission(): void
    {
        $user = $this->createLogisticUser();
        $this->mockUsageRepository($this->sampleUsageRows());

        $response = $this->actingAs($user)
            ->get(route('logistics.usage.export', [
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
        $this->mockUsageRepository($this->sampleUsageRows());

        $this->actingAs($user)
            ->get(route('logistics.usage.export', [
                'from_date' => '2026-09-01',
                'to_date' => '2026-09-15',
            ]))
            ->assertForbidden();
    }

    public function test_data_endpoint_returns_rows_from_mocked_repository(): void
    {
        $user = $this->createLogisticUser();
        $this->mockUsageRepository($this->sampleUsageRows());

        $response = $this->actingAs($user)->getJson(route('logistics.usage.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-15',
        ]));

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 3);
        $response->assertJsonFragment(['doc_num' => '1001']);
        $response->assertJsonFragment(['item_code' => 'SV-LABOUR']);
    }

    public function test_data_endpoint_filters_by_source(): void
    {
        $user = $this->createLogisticUser();
        $this->mockUsageRepository($this->sampleUsageRows());

        $response = $this->actingAs($user)->getJson(route('logistics.usage.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-15',
            'sumber' => 'delivery',
        ]));

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 1);
        $response->assertJsonFragment(['doc_num' => '2001']);
        $response->assertJsonMissing(['doc_num' => '1001']);
    }

    public function test_index_rejects_date_range_over_92_days(): void
    {
        $user = $this->createLogisticUser();
        $this->mockUsageRepository([]);

        $this->actingAs($user)
            ->get(route('logistics.usage.index', [
                'from_date' => '2026-01-01',
                'to_date' => '2026-09-15',
            ]))
            ->assertOk()
            ->assertSee('Rentang tanggal tidak boleh lebih dari 92 hari', false);
    }

    public function test_data_endpoint_rejects_date_range_over_92_days(): void
    {
        $user = $this->createLogisticUser();

        $response = $this->actingAs($user)->getJson(route('logistics.usage.data', [
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
            ->get(route('logistics.usage.export', [
                'from_date' => '2026-01-01',
                'to_date' => '2026-09-15',
            ]))
            ->assertSessionHasErrors('date_range');
    }
}
