<?php

namespace App\Console\Commands;

use App\Services\SapProjectSyncService;
use Illuminate\Console\Command;

class SapSyncProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:sync-projects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync SAP Projects from SAP B1';

    /**
     * Execute the console command.
     */
    public function handle(SapProjectSyncService $syncService)
    {
        $this->info('Starting SAP Projects sync...');

        $result = $syncService->syncProjects();

        if ($result['success']) {
            $this->info($result['message']);
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total', $result['stats']['total']],
                    ['Created', $result['stats']['created']],
                    ['Updated', $result['stats']['updated']],
                    ['Errors', $result['stats']['errors']],
                ]
            );

            if ($result['stats']['errors'] > 0 && ! empty($result['stats']['error_messages'])) {
                $this->warn('Error messages:');
                foreach ($result['stats']['error_messages'] as $error) {
                    $this->line('  - '.$error);
                }
            }
        } else {
            $this->error($result['message']);

            return 1;
        }

        return 0;
    }
}
