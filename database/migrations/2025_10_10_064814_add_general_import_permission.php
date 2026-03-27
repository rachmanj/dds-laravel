<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::create(['name' => 'import-general-documents']);

        $logisticRole = Role::where('name', 'logistic')->where('guard_name', 'web')->first();
        $accountingRole = Role::where('name', 'accounting')->where('guard_name', 'web')->first();

        if ($logisticRole) {
            $logisticRole->givePermissionTo($permission);
        }

        if ($accountingRole) {
            $accountingRole->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'import-general-documents')->where('guard_name', 'web')->first();

        if ($permission) {
            $permission->delete();
        }
    }
};
