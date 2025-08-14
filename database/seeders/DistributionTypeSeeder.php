<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DistributionType;

class DistributionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Normal',
                'code' => 'NORM',
                'description' => 'Standard distribution with normal priority',
                'is_active' => true
            ],
            [
                'name' => 'Urgent',
                'code' => 'URG',
                'description' => 'High priority distribution requiring immediate attention',
                'is_active' => true
            ],
            [
                'name' => 'Express',
                'code' => 'EXP',
                'description' => 'Fast-track distribution for time-sensitive documents',
                'is_active' => true
            ],
            [
                'name' => 'Confidential',
                'code' => 'CONF',
                'description' => 'Confidential distribution with restricted access',
                'is_active' => true
            ],
            [
                'name' => 'Bulk',
                'code' => 'BULK',
                'description' => 'Bulk distribution for multiple documents',
                'is_active' => true
            ]
        ];

        foreach ($types as $type) {
            DistributionType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
