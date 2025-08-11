<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['type_name' => 'Item'],
            ['type_name' => 'Service'],
            ['type_name' => 'Rental'],
            ['type_name' => 'Catering'],
            ['type_name' => 'Ekspedisi'],
            ['type_name' => 'Consultans'],
            ['type_name' => 'Others'],
        ];

        DB::table('invoice_types')->insert($data);
    }
}
