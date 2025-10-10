<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::where('name', 'import-additional-documents')->first();
        $logisticRole = Role::findByName('logistic');

        if ($permission && $logisticRole) {
            $logisticRole->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'import-additional-documents')->first();
        $logisticRole = Role::findByName('logistic');

        if ($permission && $logisticRole) {
            $logisticRole->revokePermissionTo($permission);
        }
    }
};
