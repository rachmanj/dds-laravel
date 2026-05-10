<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MenuSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_menu_search(): void
    {
        $response = $this->getJson('/api/menu/search');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_receives_menu_items_json(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->getJson('/api/menu/search');

        $response->assertOk();
        $response->assertJsonStructure(['items']);
        $payload = $response->json();
        $titles = collect($payload['items'])->pluck('title')->all();
        $this->assertContains('Dashboard 1', $titles);
        $this->assertNotContains('Users', $titles);
    }

    public function test_user_with_view_admin_sees_users_menu_item(): void
    {
        Permission::firstOrCreate(['name' => 'view-admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('view-admin');

        $response = $this->actingAs($user)->getJson('/api/menu/search');

        $response->assertOk();
        $payload = $response->json();
        $titles = collect($payload['items'])->pluck('title')->all();
        $this->assertContains('Users', $titles);
    }

    public function test_q_parameter_filters_items(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->getJson('/api/menu/search?q=zzzznomatchxxx');

        $response->assertOk();
        $payload = $response->json();
        $this->assertSame([], $payload['items']);

        $response = $this->actingAs($user)->getJson('/api/menu/search?q=dashboard');

        $response->assertOk();
        $payload = $response->json();
        $this->assertLessThanOrEqual(15, count($payload['items']));
        $this->assertNotEmpty($payload['items']);
    }
}
