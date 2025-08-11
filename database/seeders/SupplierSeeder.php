<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\User;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active users for created_by field
        $users = User::where('is_active', 1)->get();
        $defaultUser = $users->first() ?? User::first();

        $suppliers = [
            // Vendors
            [
                'sap_code' => 'V001',
                'name' => 'PT Maju Bersama Sukses',
                'type' => 'vendor',
                'city' => 'Jakarta',
                'payment_project' => '001H',
                'is_active' => true,
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 12190',
                'npwp' => '01.234.567.8-123.000',
                'created_by' => $defaultUser->id,
            ],
            [
                'sap_code' => 'V002',
                'name' => 'CV Mitra Abadi Jaya',
                'type' => 'vendor',
                'city' => 'Surabaya',
                'payment_project' => '000H',
                'is_active' => true,
                'address' => 'Jl. Ahmad Yani No. 45, Surabaya, Jawa Timur 60231',
                'npwp' => '02.345.678.9-234.000',
                'created_by' => $defaultUser->id,
            ],
            [
                'sap_code' => 'V003',
                'name' => 'PT Sumber Rejeki Makmur',
                'type' => 'vendor',
                'city' => 'Bandung',
                'payment_project' => '017C',
                'is_active' => true,
                'address' => 'Jl. Asia Afrika No. 67, Bandung, Jawa Barat 40111',
                'npwp' => '03.456.789.0-345.000',
                'created_by' => $defaultUser->id,
            ],
            [
                'sap_code' => 'V004',
                'name' => 'UD Berkah Sejahtera',
                'type' => 'vendor',
                'city' => 'Medan',
                'payment_project' => '021C',
                'is_active' => true,
                'address' => 'Jl. Gatot Subroto No. 89, Medan, Sumatera Utara 20112',
                'npwp' => '04.567.890.1-456.000',
                'created_by' => $defaultUser->id,
            ],
            [
                'sap_code' => 'V005',
                'name' => 'PT Global Teknologi Indonesia',
                'type' => 'vendor',
                'city' => 'Semarang',
                'payment_project' => '022C',
                'is_active' => true,
                'address' => 'Jl. Pandanaran No. 12, Semarang, Jawa Tengah 50134',
                'npwp' => '05.678.901.2-567.000',
                'created_by' => $defaultUser->id,
            ],
            [
                'sap_code' => 'V006',
                'name' => 'CV Prima Mandiri',
                'type' => 'vendor',
                'city' => 'Makassar',
                'payment_project' => '001H',
                'is_active' => false, // Inactive vendor
                'address' => 'Jl. Pengayoman No. 34, Makassar, Sulawesi Selatan 90111',
                'npwp' => '06.789.012.3-678.000',
                'created_by' => $defaultUser->id,
            ],

            // Customers
            [
                'sap_code' => 'C001',
                'name' => 'PT Pelabuhan Indonesia II',
                'type' => 'customer',
                'city' => 'Palembang',
                'payment_project' => '000H',
                'is_active' => true,
                'address' => 'Jl. Soekarno-Hatta No. 56, Palembang, Sumatera Selatan 30114',
                'npwp' => '07.890.123.4-789.000',
                'created_by' => $defaultUser->id,
            ],
            [
                'sap_code' => 'C002',
                'name' => 'PT Pertamina Hulu Mahakam',
                'type' => 'customer',
                'city' => 'Balikpapan',
                'payment_project' => '017C',
                'is_active' => true,
                'address' => 'Jl. Jenderal Sudirman No. 78, Balikpapan, Kalimantan Timur 76112',
                'npwp' => '08.901.234.5-890.000',
                'created_by' => $defaultUser->id,
            ],
            [
                'sap_code' => 'C003',
                'name' => 'PT Chevron Pacific Indonesia',
                'type' => 'customer',
                'city' => 'Riau',
                'payment_project' => '021C',
                'is_active' => true,
                'address' => 'Jl. Riau No. 90, Pekanbaru, Riau 28112',
                'npwp' => '09.012.345.6-901.000',
                'created_by' => $defaultUser->id,
            ],
            [
                'sap_code' => 'C004',
                'name' => 'PT Total E&P Indonesie',
                'type' => 'customer',
                'city' => 'Jakarta',
                'payment_project' => '022C',
                'is_active' => true,
                'address' => 'Jl. Jenderal Gatot Subroto No. 23, Jakarta Selatan, DKI Jakarta 12930',
                'npwp' => '10.123.456.7-012.000',
                'created_by' => $defaultUser->id,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        $this->command->info('Supplier seeder completed successfully!');
        $this->command->info('Created ' . count($suppliers) . ' suppliers:');
        $this->command->info('- 6 vendors (5 active, 1 inactive)');
        $this->command->info('- 4 customers (all active)');
    }
}
