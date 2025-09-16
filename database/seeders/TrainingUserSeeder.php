<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TrainingUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create training users for each role
        
        // Logistics Department Users
        $logisticManager = User::create([
            'name' => 'Sarah Logistics',
            'email' => 'logistics.manager@training.com',
            'username' => 'logistics_manager',
            'password' => Hash::make('training123'),
            'project' => '000H',
            'department_id' => 9, // Logistic department
            'is_active' => 1,
        ]);
        $logisticManager->assignRole('logistic');
        
        $warehouseStaff1 = User::create([
            'name' => 'John Warehouse',
            'email' => 'warehouse.staff1@training.com',
            'username' => 'warehouse_staff1',
            'password' => Hash::make('training123'),
            'project' => '017C',
            'department_id' => 10, // Warehouse 017C
            'is_active' => 1,
        ]);
        $warehouseStaff1->assignRole('logistic');
        
        $warehouseStaff2 = User::create([
            'name' => 'Michael Inventory',
            'email' => 'warehouse.staff2@training.com',
            'username' => 'warehouse_staff2',
            'password' => Hash::make('training123'),
            'project' => '021C',
            'department_id' => 11, // Warehouse 021C
            'is_active' => 1,
        ]);
        $warehouseStaff2->assignRole('logistic');
        
        // Accounting Department Users
        $accountingManager = User::create([
            'name' => 'Emma Accounting',
            'email' => 'accounting.manager@training.com',
            'username' => 'accounting_manager',
            'password' => Hash::make('training123'),
            'project' => '000H',
            'department_id' => 15, // Accounting department
            'is_active' => 1,
        ]);
        $accountingManager->assignRole('accounting');
        
        $accountingStaff = User::create([
            'name' => 'David Accounts',
            'email' => 'accounting.staff@training.com',
            'username' => 'accounting_staff',
            'password' => Hash::make('training123'),
            'project' => '000H',
            'department_id' => 15, // Accounting department
            'is_active' => 1,
        ]);
        $accountingStaff->assignRole('accounting');
        
        // Finance Department Users
        $financeManager = User::create([
            'name' => 'Olivia Finance',
            'email' => 'finance.manager@training.com',
            'username' => 'finance_manager',
            'password' => Hash::make('training123'),
            'project' => '001H',
            'department_id' => 7, // Finance department
            'is_active' => 1,
        ]);
        $financeManager->assignRole('finance');
        
        $financeStaff = User::create([
            'name' => 'William Payments',
            'email' => 'finance.staff@training.com',
            'username' => 'finance_staff',
            'password' => Hash::make('training123'),
            'project' => '001H',
            'department_id' => 7, // Finance department
            'is_active' => 1,
        ]);
        $financeStaff->assignRole('finance');
        
        // Cashier HO User
        $cashierHO = User::create([
            'name' => 'Sophia Cashier',
            'email' => 'cashier.ho@training.com',
            'username' => 'cashier_ho',
            'password' => Hash::make('training123'),
            'project' => '000H',
            'department_id' => 16, // Cashier HO department
            'is_active' => 1,
        ]);
        $cashierHO->assignRole('cashierho');
        
        // Procurement Department User
        $procurementStaff = User::create([
            'name' => 'Daniel Procurement',
            'email' => 'procurement.staff@training.com',
            'username' => 'procurement_staff',
            'password' => Hash::make('training123'),
            'project' => '000H',
            'department_id' => 18, // Procurement department
            'is_active' => 1,
        ]);
        $procurementStaff->assignRole('logistic');
    }
}
