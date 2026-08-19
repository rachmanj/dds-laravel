<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::create(['name' => 'manage-ito-batch-import']);

        $roles = ['accounting', 'admin', 'superadmin'];

        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'manage-ito-batch-import')->where('guard_name', 'web')->first();

        if ($permission) {
            $permission->delete();
        }
    }
};
