<?php

namespace App\Console\Commands;

use App\Services\SapDepartmentSyncService;
use Illuminate\Console\Command;

class SapSyncDepartments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:sync-departments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync SAP Departments (Profit Centers) from SAP B1';

    /**
     * Execute the console command.
     */
    public function handle(SapDepartmentSyncService $syncService)
    {
        $this->info('Starting SAP Departments sync...');

        $result = $syncService->syncDepartments();

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
