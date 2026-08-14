<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['type_name' => 'Item', 'is_consignment' => false],
            ['type_name' => 'Service', 'is_consignment' => false],
            ['type_name' => 'Rental', 'is_consignment' => false],
            ['type_name' => 'Catering', 'is_consignment' => false],
            ['type_name' => 'Ekspedisi', 'is_consignment' => false],
            ['type_name' => 'Consultans', 'is_consignment' => false],
            ['type_name' => 'Others', 'is_consignment' => false],
            ['type_name' => 'Consignment', 'is_consignment' => true],
        ];

        DB::table('invoice_types')->insert($data);
    }
}
