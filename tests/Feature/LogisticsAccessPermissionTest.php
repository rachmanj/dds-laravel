<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LogisticsAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LogisticsAccessPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_finance_role_has_logistics_summary_permissions(): void
    {
        $finance = Role::findByName('finance');

        $this->assertTrue($finance->hasPermissionTo('view-logistics-summary'));
        $this->assertTrue($finance->hasPermissionTo('export-logistics-summary'));
        $this->assertFalse($finance->hasPermissionTo('manage-logistics-category-map'));
    }

    public function test_finance_user_can_access_inventory_and_export(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');

        $this->actingAs($user)
            ->get(route('logistics.inventory.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('logistics.inventory.export'))
            ->assertOk();
    }

    public function test_finance_user_without_direct_permission_cannot_access_categories(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');

        $this->actingAs($user)
            ->get(route('logistics.categories.index'))
            ->assertForbidden();
    }

    public function test_marlov_user_can_access_categories_after_logistics_access_seeder(): void
    {
        $user = User::factory()->create([
            'username' => 'marlov',
            'email' => 'marlov@arka.co.id',
            'is_active' => true,
        ]);
        $user->assignRole('finance');

        $this->actingAs($user)
            ->get(route('logistics.categories.index'))
            ->assertForbidden();

        $this->seed(LogisticsAccessSeeder::class);

        $this->actingAs($user->fresh())
            ->get(route('logistics.categories.index'))
            ->assertOk();
    }

    public function test_logistics_access_seeder_is_idempotent_for_marlov(): void
    {
        $user = User::factory()->create([
            'username' => 'marlov',
            'email' => 'marlov@arka.co.id',
            'is_active' => true,
        ]);
        $user->assignRole('finance');

        $this->seed(LogisticsAccessSeeder::class);
        $this->seed(LogisticsAccessSeeder::class);

        $this->assertTrue($user->fresh()->hasPermissionTo('manage-logistics-category-map'));

        $this->actingAs($user->fresh())
            ->get(route('logistics.categories.index'))
            ->assertOk();
    }
}
