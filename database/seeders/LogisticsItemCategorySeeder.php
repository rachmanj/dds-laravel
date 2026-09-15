<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogisticsItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['prefix' => 'CE', 'category' => 'CATERING'],
            ['prefix' => 'CO', 'category' => 'CONSIGNMENT'],
            ['prefix' => 'FE', 'category' => 'FREE PART'],
            ['prefix' => 'FR', 'category' => 'MESS EQUIPMENT'],
            ['prefix' => 'FU', 'category' => 'SOLAR'],
            ['prefix' => 'GT', 'category' => 'GET'],
            ['prefix' => 'IT', 'category' => 'INFORMATION TECNOLOGI'],
            ['prefix' => 'LO', 'category' => 'LUBRICATING OIL'],
            ['prefix' => 'OH', 'category' => 'OVERHEAD COST'],
            ['prefix' => 'OS', 'category' => 'OFFICE SUPPLY'],
            ['prefix' => 'RC', 'category' => 'RECONDITION COMPONEN'],
            ['prefix' => 'RM', 'category' => 'RE-US'],
            ['prefix' => 'SA', 'category' => 'SAFETY SUPPLY'],
            ['prefix' => 'SOLAR', 'category' => 'SOLAR'],
            ['prefix' => 'SP', 'category' => 'SPAREPART'],
            ['prefix' => 'TO', 'category' => 'TOOLS'],
            ['prefix' => 'TY', 'category' => 'TYRE'],
            ['prefix' => 'UC', 'category' => 'UNDERCARRIAGE'],
            ['prefix' => 'US', 'category' => 'RE-US'],
            ['prefix' => 'WO', 'category' => 'WORKSHOP CONSUMABLE'],
            ['prefix' => 'VOE', 'category' => 'SPAREPART'],
        ];

        $now = now();

        DB::table('logistics_item_categories')->upsert(
            array_map(
                fn (array $row): array => [
                    'prefix' => $row['prefix'],
                    'category' => $row['category'],
                    'is_active' => true,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $rows
            ),
            ['prefix'],
            ['category', 'is_active', 'updated_at']
        );
    }
}
