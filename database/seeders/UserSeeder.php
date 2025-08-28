<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        $admin = User::create([
            'name' => 'Omanof Sullivan',
            'email' => 'admin@ninja.com',
            'password' => Hash::make('123456'),
            'project' => '000H',
            'department_id' => 21,
            'is_active' => 1,
        ]);

        // Assign super-admin role
        $admin->assignRole('superadmin');

        // Create additional test users
        $logistic = User::create([
            'name' => 'Logistic User',
            'email' => 'log@ninja.com',
            'password' => Hash::make('123456'),
            'project' => '000H',
            'department_id' => 9,
            'is_active' => 1,
        ]);
        $logistic->assignRole('logistic');

        $accounting = User::create([
            'name' => 'Accounting User',
            'email' => 'acc@ninja.com',
            'password' => Hash::make('123456'),
            'project' => '000H',
            'department_id' => 15,
            'is_active' => 1,
        ]);
        $accounting->assignRole('accounting');

        $finance = User::create([
            'name' => 'Finance User',
            'email' => 'fin@ninja.com',
            'password' => Hash::make('123456'),
            'project' => '001H',
            'department_id' => 7,
            'is_active' => 1,
        ]);
        $finance->assignRole('finance');
    }
}
