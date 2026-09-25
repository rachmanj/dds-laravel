<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustedProxyUrlSchemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_forwarded_https_proto_generates_https_absolute_urls_in_markup(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'username' => 'proxyadmin']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/admin/users');

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '#ajax: "https://[^"]+/admin/users/data"#',
            $content
        );
    }

    public function test_request_without_forwarded_proto_keeps_http_absolute_urls_in_markup(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'username' => 'directadmin']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '#ajax: "http://[^"]+/admin/users/data"#',
            $content
        );
    }
}
