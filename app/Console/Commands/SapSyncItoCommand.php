<?php

namespace App\Console\Commands;

use App\Jobs\SyncSapItoDocumentsJob;
use App\Services\SapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SapSyncItoCommand extends Command
{
    protected $signature = 'sap:sync-ito
                            {--today : Sync SAP ITO for today}
                            {--yesterday : Sync SAP ITO for yesterday}
                            {--start= : Start date (Y-m-d), use with --end}
                            {--end= : End date (Y-m-d), use with --start}
                            {--user=1 : User ID for created_by and audit log (default 1)}';

    protected $description = 'Run SAP ITO sync (same as admin UI) and write sap_logs audit';

    public function handle(): int
    {
        $hasToday = (bool) $this->option('today');
        $hasYesterday = (bool) $this->option('yesterday');
        $startOpt = $this->option('start');
        $endOpt = $this->option('end');
        $hasRange = $startOpt !== null && $startOpt !== '' && $endOpt !== null && $endOpt !== '';

        $modes = (int) $hasToday + (int) $hasYesterday + (int) $hasRange;
        if ($modes !== 1) {
            $this->error('Specify exactly one of: --today, --yesterday, or both --start and --end');

            return self::FAILURE;
        }

        if (($startOpt !== null && $startOpt !== '') xor ($endOpt !== null && $endOpt !== '')) {
            $this->error('Both --start and --end are required for a custom range');

            return self::FAILURE;
        }

        if ($hasToday) {
            $startDate = now()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        } elseif ($hasYesterday) {
            $startDate = now()->subDay()->format('Y-m-d');
            $endDate = now()->subDay()->format('Y-m-d');
        } else {
            $startDate = $startOpt;
            $endDate = $endOpt;
        }

        $userId = max(1, (int) $this->option('user'));
        Auth::loginUsingId($userId);

        $this->info("SAP ITO sync: {$startDate} → {$endDate} (user {$userId})");

        try {
            $sapService = app(SapService::class);
            $job = new SyncSapItoDocumentsJob($startDate, $endDate, [
                'trigger' => 'cli',
                'triggered_by_user_id' => $userId,
            ]);
            $job->handle($sapService);

            $logEntry = DB::table('sap_logs')
                ->where('action', 'query_sync')
                ->latest('id')
                ->first();

            if ($logEntry && $logEntry->status === 'success') {
                $response = json_decode($logEntry->response_payload, true) ?? [];
                $successCount = $response['success'] ?? 0;
                $skippedCount = $response['skipped'] ?? 0;
                $this->info("Done. Created: {$successCount}, skipped: {$skippedCount}");

                return self::SUCCESS;
            }

            $this->error('Sync failed: '.substr($logEntry?->error_message ?? 'Unknown error', 0, 500));

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
