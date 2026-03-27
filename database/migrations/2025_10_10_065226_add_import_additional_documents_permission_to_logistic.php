<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::where('name', 'import-additional-documents')->first();
        $logisticRole = Role::where('name', 'logistic')->where('guard_name', 'web')->first();

        if ($permission && $logisticRole) {
            $logisticRole->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'import-additional-documents')->first();
        $logisticRole = Role::where('name', 'logistic')->where('guard_name', 'web')->first();

        if ($permission && $logisticRole) {
            $logisticRole->revokePermissionTo($permission);
        }
    }
};
