<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the role and permission seeder first
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            ProjectSeeder::class,
            DepartmentSeeder::class,
            AdditionalDocumentTypeSeeder::class,
            InvoiceTypeSeeder::class,
            SupplierSeeder::class,
            DistributionTypeSeeder::class,
        ]);
    }
}
