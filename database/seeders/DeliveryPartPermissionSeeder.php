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
        'cancel-ito',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            'view-delivery-part',
            'edit-delivery-part',
            'export-delivery-part',
            'cancel-ito',
            'manage-delivery-part-mapping',
        ] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $rolesGranted = [];
        $mappingRolesGranted = [];

        Role::query()->each(function (Role $role) use (&$rolesGranted, &$mappingRolesGranted): void {
            if (! $role->hasPermissionTo('view-logistics-summary')) {
                if ($role->hasPermissionTo('view-delivery-part')) {
                    if (! $role->hasPermissionTo('manage-delivery-part-mapping')) {
                        $role->givePermissionTo('manage-delivery-part-mapping');
                    }
                    $mappingRolesGranted[] = $role->name;
                }

                return;
            }

            foreach (self::DELIVERY_PART_PERMISSIONS as $permissionName) {
                if (! $role->hasPermissionTo($permissionName)) {
                    $role->givePermissionTo($permissionName);
                }
            }

            if (! $role->hasPermissionTo('manage-delivery-part-mapping')) {
                $role->givePermissionTo('manage-delivery-part-mapping');
            }

            $rolesGranted[] = $role->name;
            $mappingRolesGranted[] = $role->name;
        });

        sort($rolesGranted);
        sort($mappingRolesGranted);

        Log::info('DeliveryPartPermissionSeeder: view/edit/export-delivery-part granted to roles: '.implode(', ', $rolesGranted));
        $this->command?->info('Delivery Part permissions granted to roles: '.implode(', ', $rolesGranted));
        Log::info('DeliveryPartPermissionSeeder: manage-delivery-part-mapping granted to roles: '.implode(', ', $mappingRolesGranted));
        $this->command?->info('manage-delivery-part-mapping granted to roles: '.implode(', ', $mappingRolesGranted));
    }
}
