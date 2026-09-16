<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class LogisticsAccessSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'manage-logistics-category-map']);

        $user = User::query()->where('username', 'marlov')->first();

        if ($user === null) {
            Log::warning('LogisticsAccessSeeder: user with username "marlov" not found, skipping manage-logistics-category-map assignment.');

            return;
        }

        if (! $user->hasPermissionTo('manage-logistics-category-map')) {
            $user->givePermissionTo('manage-logistics-category-map');
        }
    }
}
