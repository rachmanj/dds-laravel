<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LogisticsOnlyRoleSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const PERMISSIONS = [
        'view-logistics-summary',
        'export-logistics-summary',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $role = Role::firstOrCreate([
            'name' => 'logistics-only',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(self::PERMISSIONS);

        $assignedPermissions = $role->permissions()->orderBy('name')->pluck('name')->all();

        $this->command?->info('Logistics-only role seeder completed.');
        $this->command?->info('Role: '.$role->name.' (guard: '.$role->guard_name.')');
        $this->command?->info('Permissions: '.implode(', ', $assignedPermissions));
    }
}
