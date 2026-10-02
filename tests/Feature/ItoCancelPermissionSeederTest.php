<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DeliveryPartPermissionSeeder;
use Database\Seeders\ItoCancelPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Keputusan Iwan (1 Okt 2026): permission `cancel-ito` berdiri sendiri —
 * hanya user marlov (langsung) dan role admin + superadmin.
 */
class ItoCancelPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(DeliveryPartPermissionSeeder::class);
    }

    public function test_seeder_bukan_seeder_delivery_part_yang_memberi_cancel_ito_ke_role_logistik(): void
    {
        $logistic = Role::where('name', 'logistic')->firstOrFail();

        $this->assertTrue($logistic->hasPermissionTo('view-delivery-part'));
        $this->assertFalse($logistic->hasPermissionTo('cancel-ito'));
    }

    public function test_hanya_marlov_dan_role_admin_superadmin_yang_punya_cancel_ito(): void
    {
        // Keadaan sebelum perbaikan: role logistic punya lewat seeder Delivery Part,
        // dan ada user lain yang mendapatkannya langsung.
        $logistic = Role::where('name', 'logistic')->firstOrFail();
        $logistic->givePermissionTo('cancel-ito');

        $oranglain = User::factory()->create(['username' => 'oranglain', 'is_active' => true]);
        $oranglain->givePermissionTo('cancel-ito');

        $marlov = User::factory()->create(['username' => 'marlov', 'is_active' => true]);

        $this->seed(ItoCancelPermissionSeeder::class);

        // Diberi
        $this->assertTrue(Role::where('name', 'admin')->firstOrFail()->hasPermissionTo('cancel-ito'));
        $this->assertTrue(Role::where('name', 'superadmin')->firstOrFail()->hasPermissionTo('cancel-ito'));
        $this->assertTrue($marlov->fresh()->hasDirectPermission('cancel-ito'));

        // Dicabut
        $this->assertFalse(Role::where('name', 'logistic')->firstOrFail()->hasPermissionTo('cancel-ito'));
        $this->assertFalse($oranglain->fresh()->hasPermissionTo('cancel-ito'));
        $this->assertFalse($oranglain->fresh()->hasDirectPermission('cancel-ito'));

        // Hasil akhir persis: role admin + superadmin saja
        $this->assertSame(
            ['admin', 'superadmin'],
            Role::permission('cancel-ito')->pluck('name')->sort()->values()->all()
        );
    }

    public function test_seeder_idempoten(): void
    {
        $this->seed(ItoCancelPermissionSeeder::class);
        $this->seed(ItoCancelPermissionSeeder::class);

        $this->assertSame(
            ['admin', 'superadmin'],
            Role::permission('cancel-ito')->pluck('name')->sort()->values()->all()
        );
        $this->assertSame(1, Permission::where('name', 'cancel-ito')->count());
    }

    public function test_user_ber_role_logistik_tidak_boleh_membatalkan_ito(): void
    {
        $petugas = User::factory()->create(['username' => 'petugas', 'is_active' => true]);
        $petugas->assignRole('logistic');

        $marlov = User::factory()->create(['username' => 'marlov', 'is_active' => true]);

        $this->seed(ItoCancelPermissionSeeder::class);

        $this->assertFalse($petugas->fresh()->can('cancel-ito'));
        $this->assertTrue($marlov->fresh()->can('cancel-ito'));
    }
}
