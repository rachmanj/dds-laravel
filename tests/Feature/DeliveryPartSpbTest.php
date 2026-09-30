<?php

namespace Tests\Feature;

use App\Models\DeliveryPartSpb;
use App\Models\LogisticsWarehouseProject;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DeliveryPartPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryPartSpbTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(DeliveryPartPermissionSeeder::class);
    }

    private function project017C(): Project
    {
        $project = Project::query()->create([
            'code' => '017C',
            'owner' => 'Test',
            'location' => 'Test',
            'is_active' => true,
        ]);

        LogisticsWarehouseProject::query()->create([
            'whs_code' => '02-SPT',
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        return $project;
    }

    private function editor(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(['view-delivery-part', 'edit-delivery-part']);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(int $projectId): array
    {
        return [
            'project_id' => $projectId,
            'no_spb' => 'SPB-001',
            'tanggal' => '2026-09-10',
            'remarks' => 'Kirim pagi',
            'items' => [
                [
                    'part_number' => 'PN-1',
                    'description' => 'Filter',
                    'qty' => 2,
                    'uom' => 'PCS',
                    'remarks' => 'OK',
                ],
            ],
        ];
    }

    public function test_store_spb_with_items(): void
    {
        $project = $this->project017C();

        $response = $this->actingAs($this->editor())
            ->postJson(route('logistics.delivery-part.spb.store'), $this->validPayload($project->id));

        $response->assertCreated();
        $this->assertDatabaseHas('delivery_part_spb', [
            'project_id' => $project->id,
            'no_spb' => 'SPB-001',
        ]);
        $this->assertDatabaseCount('delivery_part_spb_items', 1);
    }

    public function test_no_spb_required_and_unique_per_project(): void
    {
        $project = $this->project017C();
        $user = $this->editor();

        $this->actingAs($user)
            ->postJson(route('logistics.delivery-part.spb.store'), array_merge($this->validPayload($project->id), [
                'no_spb' => '',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['no_spb']);

        $this->actingAs($user)
            ->postJson(route('logistics.delivery-part.spb.store'), $this->validPayload($project->id))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('logistics.delivery-part.spb.store'), $this->validPayload($project->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['no_spb']);
    }

    public function test_update_and_delete_spb(): void
    {
        $project = $this->project017C();
        $user = $this->editor();

        $this->actingAs($user)
            ->postJson(route('logistics.delivery-part.spb.store'), $this->validPayload($project->id))
            ->assertCreated();

        $spb = DeliveryPartSpb::query()->firstOrFail();

        $this->actingAs($user)
            ->putJson(route('logistics.delivery-part.spb.update', $spb), [
                'project_id' => $project->id,
                'no_spb' => 'SPB-UPDATED',
                'tanggal' => '2026-09-11',
                'remarks' => 'Revisi',
                'items' => [
                    [
                        'part_number' => '',
                        'description' => 'Hanya deskripsi',
                        'qty' => 1,
                        'uom' => 'EA',
                        'remarks' => null,
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('delivery_part_spb', ['id' => $spb->id, 'no_spb' => 'SPB-UPDATED']);

        $this->actingAs($user)
            ->deleteJson(route('logistics.delivery-part.spb.destroy', $spb))
            ->assertOk();

        $this->assertDatabaseMissing('delivery_part_spb', ['id' => $spb->id]);
    }

    public function test_delete_denied_for_non_creator_without_admin_role(): void
    {
        $project = $this->project017C();
        $creator = $this->editor();
        $other = $this->editor();

        $this->actingAs($creator)
            ->postJson(route('logistics.delivery-part.spb.store'), $this->validPayload($project->id));

        $spb = DeliveryPartSpb::query()->firstOrFail();

        $this->actingAs($other)
            ->deleteJson(route('logistics.delivery-part.spb.destroy', $spb))
            ->assertForbidden();
    }

    public function test_store_denied_without_edit_permission(): void
    {
        $project = $this->project017C();
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo('view-delivery-part');

        $this->actingAs($viewer)
            ->postJson(route('logistics.delivery-part.spb.store'), $this->validPayload($project->id))
            ->assertForbidden();
    }
}
