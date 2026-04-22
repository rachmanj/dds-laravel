<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $names = [
            'create-solar-price-histories',
            'edit-solar-price-histories',
            'delete-solar-price-histories',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $roleNames = ['superadmin', 'admin', 'accounting', 'finance'];

        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }
            $role->givePermissionTo($names);
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['create-solar-price-histories', 'edit-solar-price-histories', 'delete-solar-price-histories'] as $name) {
            $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();
            if ($permission) {
                $permission->delete();
            }
        }
    }
};
