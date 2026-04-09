<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_admin_user_show(): void
    {
        $target = User::factory()->create(['is_active' => true]);

        $this->get(route('admin.users.show', $target))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_admin_user_show(): void
    {
        $viewer = User::factory()->create(['is_active' => true, 'username' => 'logviewer']);
        $viewer->assignRole('logistic');

        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($viewer)->get(route('admin.users.show', $target))
            ->assertForbidden();
    }

    public function test_admin_can_view_user_show_page(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'username' => 'listadmin']);
        $admin->assignRole('admin');

        $target = User::factory()->create([
            'is_active' => true,
            'name' => 'Shown User Alpha',
            'email' => 'shown@example.test',
        ]);

        $this->actingAs($admin)->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee('User Information', false)
            ->assertSee('Shown User Alpha', false)
            ->assertSee('shown@example.test', false);
    }
}
