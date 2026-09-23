<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\SapGrpoRepository;
use App\Repositories\SapUsageRepository;
use Database\Seeders\LogisticsOnlyRoleSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LogisticsOnlyRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_seeder_creates_logistics_only_role_with_exact_permissions(): void
    {
        $this->seed(LogisticsOnlyRoleSeeder::class);

        $role = Role::findByName('logistics-only', 'web');

        $this->assertSame('logistics-only', $role->name);
        $this->assertSame('web', $role->guard_name);

        $permissionNames = $role->permissions()->orderBy('name')->pluck('name')->all();

        $this->assertSame([
            'export-logistics-summary',
            'view-logistics-summary',
        ], $permissionNames);

        $this->assertFalse($role->hasPermissionTo('manage-logistics-category-map'));
    }

    public function test_seeder_sync_removes_extra_permissions_from_logistics_only_role(): void
    {
        $this->seed(LogisticsOnlyRoleSeeder::class);

        $role = Role::findByName('logistics-only', 'web');
        $role->givePermissionTo('view-invoices');

        $this->seed(LogisticsOnlyRoleSeeder::class);

        $permissionNames = $role->fresh()->permissions()->orderBy('name')->pluck('name')->all();

        $this->assertSame([
            'export-logistics-summary',
            'view-logistics-summary',
        ], $permissionNames);
    }

    public function test_logistics_only_user_can_access_logistics_summary_pages(): void
    {
        $this->seed(LogisticsOnlyRoleSeeder::class);
        $this->mock(SapGrpoRepository::class, function ($mock): void {
            $mock->shouldReceive('fetch')->andReturn([]);
        });
        $this->mock(SapUsageRepository::class, function ($mock): void {
            $mock->shouldReceive('fetch')->andReturn([]);
        });

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('logistics-only');

        $this->actingAs($user)->get('/logistics/inventory')->assertOk();
        $this->actingAs($user)->get('/logistics/grpo')->assertOk();
        $this->actingAs($user)->get('/logistics/usage')->assertOk();
    }

    public function test_logistics_only_user_cannot_access_restricted_modules(): void
    {
        $this->seed(LogisticsOnlyRoleSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('logistics-only');

        $this->actingAs($user)->get('/logistics/categories')->assertForbidden();
        $this->actingAs($user)->get('/invoices')->assertForbidden();
        $this->actingAs($user)->get('/additional-documents')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_logistics_only_user_can_export_logistics_summaries(): void
    {
        $this->seed(LogisticsOnlyRoleSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('logistics-only');

        $this->actingAs($user)
            ->get(route('logistics.inventory.export'))
            ->assertOk();

        $this->mock(SapGrpoRepository::class, function ($mock): void {
            $mock->shouldReceive('fetch')->andReturn([]);
        });

        $this->actingAs($user)
            ->get(route('logistics.grpo.export'))
            ->assertOk();

        $this->mock(SapUsageRepository::class, function ($mock): void {
            $mock->shouldReceive('fetch')->andReturn([]);
        });

        $this->actingAs($user)
            ->get(route('logistics.usage.export'))
            ->assertOk();
    }
}
