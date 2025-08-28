<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\InvoiceType;
use App\Models\User;
use App\Models\Department;
use Carbon\Carbon;

class TestInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create test data
        $supplier = Supplier::firstOrCreate(['name' => 'Test Supplier'], [
            'sap_code' => 'TEST001',
            'type' => 'vendor',
            'payment_project' => '001H',
            'created_by' => 1
        ]);

        $invoiceType = InvoiceType::firstOrCreate(['type_name' => 'Test Type']);

        $department = Department::firstOrCreate(['location_code' => 'HQ'], [
            'name' => 'Headquarters',
            'project' => '001H',
            'akronim' => 'HQ'
        ]);

        $user = User::firstOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'department_id' => $department->id
        ]);

        // Create test invoices with different receive dates
        $testInvoices = [
            [
                'invoice_number' => 'INV-001',
                'invoice_date' => Carbon::now()->subDays(30),
                'receive_date' => Carbon::now()->subDays(25), // 25 days ago
                'supplier_id' => $supplier->id,
                'po_no' => 'PO-001',
                'currency' => 'USD',
                'amount' => 1000.00,
                'type_id' => $invoiceType->id,
                'payment_status' => 'pending',
                'cur_loc' => 'HQ',
                'status' => 'open',
                'distribution_status' => 'available',
                'created_by' => $user->id,
            ],
            [
                'invoice_number' => 'INV-002',
                'invoice_date' => Carbon::now()->subDays(20),
                'receive_date' => Carbon::now()->subDays(18), // 18 days ago
                'supplier_id' => $supplier->id,
                'po_no' => 'PO-002',
                'currency' => 'USD',
                'amount' => 2500.00,
                'type_id' => $invoiceType->id,
                'payment_status' => 'pending',
                'cur_loc' => 'HQ',
                'status' => 'verify',
                'distribution_status' => 'available',
                'created_by' => $user->id,
            ],
            [
                'invoice_number' => 'INV-003',
                'invoice_date' => Carbon::now()->subDays(10),
                'receive_date' => Carbon::now()->subDays(8), // 8 days ago
                'supplier_id' => $supplier->id,
                'po_no' => 'PO-003',
                'currency' => 'USD',
                'amount' => 750.00,
                'type_id' => $invoiceType->id,
                'payment_status' => 'pending',
                'cur_loc' => 'HQ',
                'status' => 'open',
                'distribution_status' => 'available',
                'created_by' => $user->id,
            ],
            [
                'invoice_number' => 'INV-004',
                'invoice_date' => Carbon::now()->subDays(5),
                'receive_date' => Carbon::now()->subDays(3), // 3 days ago
                'supplier_id' => $supplier->id,
                'po_no' => 'PO-004',
                'currency' => 'USD',
                'amount' => 1500.00,
                'type_id' => $invoiceType->id,
                'payment_status' => 'pending',
                'cur_loc' => 'HQ',
                'status' => 'open',
                'distribution_status' => 'available',
                'created_by' => $user->id,
            ],
            [
                'invoice_number' => 'INV-005',
                'invoice_date' => Carbon::now()->subDays(2),
                'receive_date' => Carbon::now()->subDays(1), // 1 day ago
                'supplier_id' => $supplier->id,
                'po_no' => 'PO-005',
                'currency' => 'USD',
                'amount' => 500.00,
                'type_id' => $invoiceType->id,
                'payment_status' => 'pending',
                'cur_loc' => 'HQ',
                'status' => 'open',
                'distribution_status' => 'available',
                'created_by' => $user->id,
            ],
        ];

        foreach ($testInvoices as $invoiceData) {
            Invoice::firstOrCreate(['invoice_number' => $invoiceData['invoice_number']], $invoiceData);
        }

        $this->command->info('Test invoices created successfully!');
        $this->command->info('Created invoices with receive dates: 25, 18, 8, 3, and 1 days ago');
    }
}
