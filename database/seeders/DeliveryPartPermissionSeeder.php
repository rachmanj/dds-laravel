<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DeliveryPartPermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const DELIVERY_PART_PERMISSIONS = [
        'view-delivery-part',
        'edit-delivery-part',
        'export-delivery-part',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            'view-delivery-part',
            'edit-delivery-part',
            'export-delivery-part',
            'cancel-ito',
        ] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $rolesGranted = [];

        Role::query()->each(function (Role $role) use (&$rolesGranted): void {
            if (! $role->hasPermissionTo('view-logistics-summary')) {
                return;
            }

            foreach (self::DELIVERY_PART_PERMISSIONS as $permissionName) {
                if (! $role->hasPermissionTo($permissionName)) {
                    $role->givePermissionTo($permissionName);
                }
            }

            $rolesGranted[] = $role->name;
        });

        sort($rolesGranted);

        Log::info('DeliveryPartPermissionSeeder: view/edit/export-delivery-part granted to roles: '.implode(', ', $rolesGranted));
        $this->command?->info('Delivery Part permissions granted to roles: '.implode(', ', $rolesGranted));
    }
}
