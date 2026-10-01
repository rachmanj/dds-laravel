<?php

namespace Tests\Feature;

use App\Exports\DeliveryPartExport;
use App\Models\DeliveryPartEntry;
use App\Models\LogisticsWarehouseProject;
use App\Models\Project;
use App\Models\User;
use App\Services\Logistics\DeliveryPartQueryService;
use Carbon\Carbon;
use Database\Seeders\DeliveryPartPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class DeliveryPartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(DeliveryPartPermissionSeeder::class);
        Carbon::setTestNow('2026-09-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    private function createProjectWithMapping(string $code, string $whsCode): Project
    {
        $project = Project::query()->create([
            'code' => $code,
            'owner' => 'Test',
            'location' => 'Test',
            'is_active' => true,
        ]);

        LogisticsWarehouseProject::query()->create([
            'whs_code' => $whsCode,
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        return $project;
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleSapRow(string $whsCode = '02-SPT'): array
    {
        return [
            'grpo_no' => 'GRPO-1',
            'doc_entry' => 10001,
            'ito_no' => 'ITO-100',
            'ito_date' => Carbon::parse('2026-09-10'),
            'ito_created_date' => Carbon::parse('2026-09-10 08:00:00'),
            'iti_no' => 'ITI-50',
            'iti_date' => Carbon::parse('2026-09-12'),
            'item_code' => 'PART-001',
            'description' => 'Filter oli',
            'uom' => 'PCS',
            'qty' => 2.0,
            'po_no' => 'PO-900',
            'pr_no' => 'PR-1',
            'mr_no' => 'MR-1',
            'unit_no' => 'U-77',
            'vendor' => 'Vendor ABC',
            'from_warehouse' => '01-HO',
            'to_warehouse' => $whsCode,
            'delivery_status' => 'Not Delivered',
            'delivery_date' => null,
            'remarks' => null,
        ];
    }

    private function userWithViewPermission(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('view-delivery-part');

        return $user;
    }

    private function userWithEditPermission(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(['view-delivery-part', 'edit-delivery-part']);

        return $user;
    }

    private function bindFakeSapRows(Collection $rows): void
    {
        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('rows')->andReturn($rows);
        $fake->shouldReceive('bustForRange')->andReturnNull();
        $fake->shouldReceive('validatePageDateRange')->andReturnUsing(
            function (Carbon $from, Carbon $to): ?string {
                $fromDay = $from->copy()->startOfDay();
                $toDay = $to->copy()->startOfDay();
                if ($fromDay->gt($toDay)) {
                    return DeliveryPartQueryService::PAGE_DATE_RANGE_ERROR;
                }
                if ($fromDay->diffInDays($toDay) > DeliveryPartQueryService::MAX_PAGE_DATE_RANGE_DAYS) {
                    return DeliveryPartQueryService::PAGE_DATE_RANGE_ERROR;
                }

                return null;
            },
        );
        $this->app->instance(DeliveryPartQueryService::class, $fake);
    }

    public function test_index_returns_200_with_permission_and_403_without(): void
    {
        $this->createProjectWithMapping('017C', '02-SPT');
        $this->bindFakeSapRows(collect());

        $this->actingAs($this->userWithViewPermission())
            ->get(route('logistics.delivery-part.index'))
            ->assertOk()
            ->assertSee('Delivery Part');

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('logistics.delivery-part.index'))
            ->assertForbidden();
    }

    public function test_data_filters_rows_by_warehouse_project_mapping(): void
    {
        $project017 = $this->createProjectWithMapping('017C', '02-SPT');
        $this->createProjectWithMapping('022C', '08-SPT');

        $this->bindFakeSapRows(collect([
            $this->sampleSapRow('02-SPT'),
            $this->sampleSapRow('08-SPT'),
        ]));

        $response = $this->actingAs($this->userWithViewPermission())
            ->getJson(route('logistics.delivery-part.data', [
                'project' => '017C',
                'from_date' => '2026-09-01',
                'to_date' => '2026-09-30',
            ]));

        $response->assertOk();
        $payload = $response->json();
        $this->assertSame(1, (int) $payload['recordsTotal']);
        $this->assertStringContainsString('ITO-100', json_encode($payload['data']));
    }

    public function test_manual_columns_merge_and_sap_ito_remains_visible(): void
    {
        $project = $this->createProjectWithMapping('017C', '02-SPT');
        $this->bindFakeSapRows(collect([$this->sampleSapRow()]));

        DeliveryPartEntry::query()->create([
            'project_id' => $project->id,
            'ito_no' => 'ITO-100',
            'item_code' => 'PART-001',
            'unit_no' => 'U-77',
            'source' => DeliveryPartEntry::SOURCE_SAP,
            'no_spb' => 'SPB-2026-01',
            'ito_no_override' => 'ITO-100-CORR',
        ]);

        $response = $this->actingAs($this->userWithViewPermission())
            ->getJson(route('logistics.delivery-part.data', [
                'project' => '017C',
                'from_date' => '2026-09-01',
                'to_date' => '2026-09-30',
            ]));

        $response->assertOk();
        $html = json_encode($response->json('data'));
        $this->assertStringContainsString('SPB-2026-01', $html);
        $this->assertStringContainsString('ITO-100-CORR', $html);
        $this->assertStringContainsString('ITO-100', $html);
    }

    public function test_update_entry_records_history_for_changed_manual_fields(): void
    {
        $project = $this->createProjectWithMapping('017C', '02-SPT');
        $user = $this->userWithEditPermission();

        $entry = DeliveryPartEntry::query()->create([
            'project_id' => $project->id,
            'ito_no' => 'ITO-1',
            'item_code' => 'P-1',
            'unit_no' => 'U-1',
            'source' => DeliveryPartEntry::SOURCE_SAP,
            'no_spb' => 'OLD',
        ]);

        $this->actingAs($user)
            ->patchJson(route('logistics.delivery-part.entry.update', $entry), [
                'no_spb' => 'NEW-SPB',
                'ekspedisi' => 'TRUCK ARKA',
            ])
            ->assertOk();

        $this->assertDatabaseHas('delivery_part_entries', [
            'id' => $entry->id,
            'no_spb' => 'NEW-SPB',
            'ekspedisi' => 'TRUCK ARKA',
        ]);

        $this->assertDatabaseHas('delivery_part_entry_histories', [
            'delivery_part_entry_id' => $entry->id,
            'field' => 'no_spb',
            'old_value' => 'OLD',
            'new_value' => 'NEW-SPB',
            'user_id' => $user->id,
        ]);
    }

    public function test_update_rejects_invalid_ekspedisi(): void
    {
        $project = $this->createProjectWithMapping('017C', '02-SPT');
        $entry = DeliveryPartEntry::query()->create([
            'project_id' => $project->id,
            'ito_no' => 'ITO-1',
            'item_code' => 'P-1',
            'unit_no' => 'U-1',
            'source' => DeliveryPartEntry::SOURCE_SAP,
        ]);

        $this->actingAs($this->userWithEditPermission())
            ->patchJson(route('logistics.delivery-part.entry.update', $entry), [
                'ekspedisi' => 'EKSPEDISI TIDAK ADA',
            ])
            ->assertUnprocessable();
    }

    public function test_export_headings_match_excel_column_order(): void
    {
        $export = new DeliveryPartExport(collect());

        $this->assertSame([
            'TANGGAL RECEIVED',
            'Supplier',
            'PO Number',
            'No. SPB',
            'NO ITO',
            'No Unit',
            'Parts Number',
            'Descriptions',
            'QTY',
            'UOM',
            'Remarks Barang',
            'Tgl Delivery',
            'Transporter',
            'Unit & No Kendaraan',
            'Ekspedisi',
            'Tgl ITI',
            'NO. ITI',
            'Keterangan',
        ], $export->headings());
    }

    public function test_refresh_busts_sap_cache_for_date_range(): void
    {
        $this->createProjectWithMapping('017C', '02-SPT');

        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('validatePageDateRange')->andReturn(null);
        $fake->shouldReceive('bustForRange')
            ->once()
            ->with(
                Mockery::on(fn (Carbon $d) => $d->toDateString() === '2026-09-01'),
                Mockery::on(fn (Carbon $d) => $d->toDateString() === '2026-09-30'),
            );
        $this->app->instance(DeliveryPartQueryService::class, $fake);

        $this->actingAs($this->userWithViewPermission())
            ->postJson(route('logistics.delivery-part.refresh'), [
                'from_date' => '2026-09-01',
                'to_date' => '2026-09-30',
            ])
            ->assertOk();
    }

    public function test_data_returns_503_when_sap_query_fails(): void
    {
        $this->createProjectWithMapping('017C', '02-SPT');

        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('validatePageDateRange')->andReturn(null);
        $fake->shouldReceive('rows')->andThrow(new \App\Exceptions\SapSqlQueryException('Gagal koneksi'));
        $this->app->instance(DeliveryPartQueryService::class, $fake);

        $this->actingAs($this->userWithViewPermission())
            ->getJson(route('logistics.delivery-part.data', [
                'project' => '017C',
                'from_date' => '2026-09-01',
                'to_date' => '2026-09-30',
            ]))
            ->assertStatus(503);
    }

    public function test_index_rejects_date_range_over_92_days(): void
    {
        $this->createProjectWithMapping('017C', '02-SPT');
        $this->bindFakeSapRows(collect());

        $this->actingAs($this->userWithViewPermission())
            ->get(route('logistics.delivery-part.index', [
                'project' => '017C',
                'from_date' => '2026-01-01',
                'to_date' => '2026-09-15',
            ]))
            ->assertOk()
            ->assertSee('Rentang tanggal tidak boleh lebih dari 92 hari', false);
    }

    public function test_data_endpoint_rejects_date_range_over_92_days(): void
    {
        $this->createProjectWithMapping('017C', '02-SPT');

        $this->actingAs($this->userWithViewPermission())
            ->getJson(route('logistics.delivery-part.data', [
                'project' => '017C',
                'from_date' => '2026-01-01',
                'to_date' => '2026-09-15',
            ]))
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Rentang tanggal tidak boleh lebih dari 92 hari.',
            ]);
    }

    public function test_export_rejects_date_range_over_92_days(): void
    {
        $this->createProjectWithMapping('017C', '02-SPT');

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(['view-delivery-part', 'export-delivery-part']);

        $this->actingAs($user)
            ->get(route('logistics.delivery-part.export', [
                'project' => '017C',
                'from_date' => '2026-01-01',
                'to_date' => '2026-09-15',
            ]))
            ->assertSessionHasErrors('date_range');
    }

    public function test_permission_seeder_grants_delivery_part_to_logistics_summary_roles(): void
    {
        $finance = User::factory()->create(['is_active' => true]);
        $finance->assignRole('finance');

        $this->assertTrue($finance->hasPermissionTo('view-delivery-part'));
        $this->assertTrue($finance->hasPermissionTo('export-delivery-part'));
    }
}
