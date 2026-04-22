<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DomainAssistantDataService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainAssistantSolarPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_active_solar_unit_price_when_no_row(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true, 'username' => 'tuser']);
        $user->assignRole('admin');

        $out = app(DomainAssistantDataService::class)->getActiveSolarUnitPrice($user, null);

        $this->assertFalse($out['active']);
    }

    public function test_get_active_solar_unit_price_rejects_invalid_date(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true, 'username' => 'tuser2']);
        $user->assignRole('admin');

        $out = app(DomainAssistantDataService::class)->getActiveSolarUnitPrice($user, 'not-valid');

        $this->assertArrayHasKey('error', $out);
    }
}
