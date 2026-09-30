<?php

namespace Database\Seeders;

use App\Models\LogisticsWarehouseProject;
use App\Models\Project;
use Illuminate\Database\Seeder;

class DeliveryPartWarehouseProjectSeeder extends Seeder
{
    /**
     * @var array<string, string> whs_code => project code
     */
    private const WAREHOUSE_TO_PROJECT = [
        '08-SPT' => '017C',
        '14-SPT' => '022C',
        '17-SPT' => '026C',
    ];

    public function run(): void
    {
        Project::query()->firstOrCreate(
            ['code' => 'PRATASABA'],
            [
                'owner' => null,
                'location' => null,
                'is_active' => true,
            ],
        );

        $created = 0;
        $skippedExisting = 0;
        $skippedMissingProject = [];

        foreach (self::WAREHOUSE_TO_PROJECT as $whsCode => $projectCode) {
            $project = Project::query()->where('code', $projectCode)->first();

            if ($project === null) {
                $skippedMissingProject[] = "{$whsCode} → {$projectCode} (project tidak ada)";
                $this->command?->warn("Lewati {$whsCode}: project {$projectCode} tidak ditemukan di tabel projects.");

                continue;
            }

            $mapping = LogisticsWarehouseProject::query()->firstOrCreate(
                ['whs_code' => $whsCode],
                [
                    'project_id' => $project->id,
                    'is_active' => true,
                ],
            );

            if ($mapping->wasRecentlyCreated) {
                $created++;
                $this->command?->info("Mapping dibuat: {$whsCode} → {$projectCode}");
            } else {
                $skippedExisting++;
                $this->command?->line("Mapping sudah ada: {$whsCode} → {$projectCode}");
            }
        }

        $activeCount = LogisticsWarehouseProject::query()->where('is_active', true)->count();

        $this->command?->info("Ringkasan: {$created} mapping baru, {$skippedExisting} sudah ada, ".count($skippedMissingProject).' dilewati (project hilang).');
        $this->command?->info("Total mapping aktif: {$activeCount}.");
    }
}
