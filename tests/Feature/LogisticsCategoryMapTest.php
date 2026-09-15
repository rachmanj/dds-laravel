<?php

namespace Tests\Feature;

use App\Models\LogisticsInventoryItem;
use App\Models\LogisticsInventoryPivot;
use App\Models\LogisticsInventorySnapshot;
use App\Models\LogisticsItemCategory;
use App\Models\User;
use App\Services\Logistics\ItemCategoryResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsCategoryMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function createCategoryMapUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('logistic');

        return $user;
    }

    private function createUserWithoutPermission(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    /**
     * @return array{snapshot: LogisticsInventorySnapshot, items: array<int, LogisticsInventoryItem>}
     */
    private function createSuccessfulSnapshot(): array
    {
        $snapshot = LogisticsInventorySnapshot::query()->create([
            'snapshot_date' => '2026-09-15',
            'status' => 'success',
            'row_count' => 2,
            'total_value' => 15000,
            'duration_ms' => 120,
            'created_at' => now(),
        ]);

        $sparepartItem = LogisticsInventoryItem::query()->create([
            'snapshot_id' => $snapshot->id,
            'item_code' => 'SP-001',
            'item_name' => 'Spare Part A',
            'category' => 'SPAREPART',
            'instock' => 10,
            'total_value' => 10000,
            'whs_code' => 'WH01',
            'project' => '022C',
        ]);

        $unknownItem = LogisticsInventoryItem::query()->create([
            'snapshot_id' => $snapshot->id,
            'item_code' => 'UNKNOWN-999',
            'item_name' => 'Unknown Item',
            'category' => ItemCategoryResolver::FALLBACK_CATEGORY,
            'instock' => 1,
            'total_value' => 5000,
            'whs_code' => 'WH01',
            'project' => '022C',
        ]);

        LogisticsInventoryPivot::query()->create([
            'snapshot_id' => $snapshot->id,
            'project' => '022C',
            'category' => 'SPAREPART',
            'sum_instock' => 10,
            'sum_value' => 10000,
        ]);

        LogisticsInventoryPivot::query()->create([
            'snapshot_id' => $snapshot->id,
            'project' => '022C',
            'category' => ItemCategoryResolver::FALLBACK_CATEGORY,
            'sum_instock' => 1,
            'sum_value' => 5000,
        ]);

        return [
            'snapshot' => $snapshot,
            'items' => [$sparepartItem, $unknownItem],
        ];
    }

    public function test_user_with_permission_can_open_category_map_page(): void
    {
        $user = $this->createCategoryMapUser();
        LogisticsItemCategory::query()->create([
            'prefix' => 'SP',
            'category' => 'SPAREPART',
            'is_active' => true,
        ]);
        $this->createSuccessfulSnapshot();

        $this->actingAs($user)
            ->get(route('logistics.categories.index'))
            ->assertOk()
            ->assertSee('Kategori Item')
            ->assertSee('Mapping Prefix')
            ->assertSee('SP')
            ->assertSee('SPAREPART');
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        $user = $this->createUserWithoutPermission();

        $this->actingAs($user)
            ->get(route('logistics.categories.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('logistics.categories.store'), [
                'prefix' => 'XX',
                'category' => 'TEST',
            ])
            ->assertForbidden();
    }

    public function test_can_store_new_prefix_mapping(): void
    {
        $user = $this->createCategoryMapUser();

        $this->actingAs($user)
            ->post(route('logistics.categories.store'), [
                'prefix' => 'GT',
                'category' => 'GET',
            ])
            ->assertRedirect(route('logistics.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('logistics_item_categories', [
            'prefix' => 'GT',
            'category' => 'GET',
            'is_active' => true,
            'updated_by' => $user->id,
        ]);
    }

    public function test_can_update_category_of_existing_prefix(): void
    {
        $user = $this->createCategoryMapUser();
        $mapping = LogisticsItemCategory::query()->create([
            'prefix' => 'SP',
            'category' => 'SPAREPART',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('logistics.categories.update', $mapping), [
                'category' => 'TOOLS',
            ])
            ->assertRedirect(route('logistics.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('logistics_item_categories', [
            'id' => $mapping->id,
            'prefix' => 'SP',
            'category' => 'TOOLS',
            'updated_by' => $user->id,
        ]);
    }

    public function test_can_toggle_prefix_inactive_so_items_no_longer_match(): void
    {
        $user = $this->createCategoryMapUser();
        $mapping = LogisticsItemCategory::query()->create([
            'prefix' => 'SP',
            'category' => 'SPAREPART',
            'is_active' => true,
        ]);

        $resolver = app(ItemCategoryResolver::class);
        $this->assertSame('SPAREPART', $resolver->resolve('SP-001'));

        $this->actingAs($user)
            ->patch(route('logistics.categories.toggle', $mapping))
            ->assertRedirect(route('logistics.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('logistics_item_categories', [
            'id' => $mapping->id,
            'is_active' => false,
        ]);

        $freshResolver = app(ItemCategoryResolver::class);
        $this->assertSame(ItemCategoryResolver::FALLBACK_CATEGORY, $freshResolver->resolve('SP-001'));
    }

    public function test_store_validates_prefix_required_and_unique(): void
    {
        $user = $this->createCategoryMapUser();
        LogisticsItemCategory::query()->create([
            'prefix' => 'SP',
            'category' => 'SPAREPART',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->from(route('logistics.categories.index'))
            ->post(route('logistics.categories.store'), [
                'prefix' => '',
                'category' => 'TEST',
            ])
            ->assertRedirect(route('logistics.categories.index'))
            ->assertSessionHasErrors('prefix');

        $this->actingAs($user)
            ->from(route('logistics.categories.index'))
            ->post(route('logistics.categories.store'), [
                'prefix' => 'SP',
                'category' => 'DUPLICATE',
            ])
            ->assertRedirect(route('logistics.categories.index'))
            ->assertSessionHasErrors('prefix');
    }

    public function test_recompute_applies_categories_to_latest_successful_snapshot(): void
    {
        $user = $this->createCategoryMapUser();
        LogisticsItemCategory::query()->create([
            'prefix' => 'SP',
            'category' => 'SPAREPART',
            'is_active' => true,
        ]);
        ['snapshot' => $snapshot] = $this->createSuccessfulSnapshot();

        LogisticsItemCategory::query()
            ->where('prefix', 'SP')
            ->update(['category' => 'TOOLS']);

        $this->actingAs($user)
            ->post(route('logistics.categories.recompute'))
            ->assertRedirect(route('logistics.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('logistics_inventory_items', [
            'snapshot_id' => $snapshot->id,
            'item_code' => 'SP-001',
            'category' => 'TOOLS',
        ]);

        $this->assertDatabaseHas('logistics_inventory_pivots', [
            'snapshot_id' => $snapshot->id,
            'project' => '022C',
            'category' => 'TOOLS',
            'sum_instock' => 10,
            'sum_value' => 10000,
        ]);

        $this->assertDatabaseMissing('logistics_inventory_pivots', [
            'snapshot_id' => $snapshot->id,
            'category' => 'SPAREPART',
        ]);

        $this->assertDatabaseHas('logistics_inventory_pivots', [
            'snapshot_id' => $snapshot->id,
            'category' => ItemCategoryResolver::FALLBACK_CATEGORY,
            'sum_instock' => 1,
            'sum_value' => 5000,
        ]);
    }
}
