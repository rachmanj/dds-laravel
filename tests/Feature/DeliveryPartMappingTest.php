<?php

namespace Tests\Feature;

use App\Models\LogisticsWarehouseProject;
use App\Models\Project;
use App\Models\User;
use App\Services\Logistics\DeliveryPartQueryService;
use Carbon\Carbon;
use Database\Seeders\DeliveryPartPermissionSeeder;
use Database\Seeders\DeliveryPartWarehouseProjectSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DeliveryPartMappingTest extends TestCase
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

    private function userWithMappingPermission(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('manage-delivery-part-mapping');

        return $user;
    }

    private function bindDistinctWarehouses(array $rows): void
    {
        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('distinctToWarehouses')->andReturn($rows);
        $this->app->instance(DeliveryPartQueryService::class, $fake);
    }

    public function test_index_returns_200_with_permission_and_403_without(): void
    {
        $this->bindDistinctWarehouses([]);

        $this->actingAs($this->userWithMappingPermission())
            ->get(route('logistics.warehouse-projects.index'))
            ->assertOk()
            ->assertSee('Mapping Warehouse');

        $other = User::factory()->create(['is_active' => true]);
        $other->givePermissionTo('view-delivery-part');

        $this->actingAs($other)
            ->get(route('logistics.warehouse-projects.index'))
            ->assertForbidden();
    }

    public function test_store_creates_new_mapping(): void
    {
        $this->bindDistinctWarehouses([]);

        $project = Project::query()->create([
            'code' => '017C',
            'owner' => 'Test',
            'location' => 'Site A',
            'is_active' => true,
        ]);

        $user = $this->userWithMappingPermission();

        $this->actingAs($user)
            ->post(route('logistics.warehouse-projects.store'), [
                'whs_code' => '08-spt',
                'project_id' => $project->id,
            ])
            ->assertRedirect(route('logistics.warehouse-projects.index'));

        $this->assertDatabaseHas('logistics_warehouse_projects', [
            'whs_code' => '08-SPT',
            'project_id' => $project->id,
            'is_active' => true,
            'updated_by' => $user->id,
        ]);
    }

    public function test_store_rejects_duplicate_whs_code(): void
    {
        $this->bindDistinctWarehouses([]);

        $project = Project::query()->create([
            'code' => '017C',
            'owner' => 'Test',
            'location' => 'Site A',
            'is_active' => true,
        ]);

        LogisticsWarehouseProject::query()->create([
            'whs_code' => '08-SPT',
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->userWithMappingPermission())
            ->from(route('logistics.warehouse-projects.index'))
            ->post(route('logistics.warehouse-projects.store'), [
                'whs_code' => '08-SPT',
                'project_id' => $project->id,
            ])
            ->assertSessionHasErrors('whs_code');

        $this->assertSame(1, LogisticsWarehouseProject::query()->count());
    }

    public function test_toggle_flips_active_status(): void
    {
        $this->bindDistinctWarehouses([]);

        $project = Project::query()->create([
            'code' => '017C',
            'owner' => 'Test',
            'location' => 'Site A',
            'is_active' => true,
        ]);

        $mapping = LogisticsWarehouseProject::query()->create([
            'whs_code' => '08-SPT',
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $user = $this->userWithMappingPermission();

        $this->actingAs($user)
            ->patch(route('logistics.warehouse-projects.toggle', $mapping))
            ->assertRedirect(route('logistics.warehouse-projects.index'));

        $mapping->refresh();
        $this->assertFalse($mapping->is_active);
        $this->assertSame($user->id, $mapping->updated_by);

        $this->actingAs($user)
            ->patch(route('logistics.warehouse-projects.toggle', $mapping))
            ->assertRedirect(route('logistics.warehouse-projects.index'));

        $mapping->refresh();
        $this->assertTrue($mapping->is_active);
    }

    public function test_unmapped_list_excludes_already_mapped_warehouses(): void
    {
        $project = Project::query()->create([
            'code' => '017C',
            'owner' => 'Test',
            'location' => 'Site A',
            'is_active' => true,
        ]);

        LogisticsWarehouseProject::query()->create([
            'whs_code' => '08-SPT',
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $this->bindDistinctWarehouses([
            ['whs_code' => '08-SPT', 'row_count' => 100, 'document_count' => 10],
            ['whs_code' => '99-NEW', 'row_count' => 50, 'document_count' => 5],
        ]);

        $this->actingAs($this->userWithMappingPermission())
            ->get(route('logistics.warehouse-projects.index'))
            ->assertOk()
            ->assertSee('value="99-NEW"', false)
            ->assertDontSee('value="08-SPT"', false);
    }

    public function test_warehouse_project_seeder_is_idempotent(): void
    {
        foreach (['017C', '022C', '026C'] as $code) {
            Project::query()->create([
                'code' => $code,
                'owner' => 'Test',
                'location' => 'Loc',
                'is_active' => true,
            ]);
        }

        $this->seed(DeliveryPartWarehouseProjectSeeder::class);
        $firstMappingCount = LogisticsWarehouseProject::query()->count();
        $firstPratasabaCount = Project::query()->where('code', 'PRATASABA')->count();

        $this->seed(DeliveryPartWarehouseProjectSeeder::class);

        $this->assertSame($firstMappingCount, LogisticsWarehouseProject::query()->count());
        $this->assertSame(1, Project::query()->where('code', 'PRATASABA')->count());
        $this->assertSame($firstPratasabaCount, 1);
        $this->assertSame(3, $firstMappingCount);
    }
}
