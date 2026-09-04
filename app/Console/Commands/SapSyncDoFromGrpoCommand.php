<?php

namespace App\Console\Commands;

use App\Jobs\SyncSapDoFromGrpoJob;
use App\Services\SapService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SapSyncDoFromGrpoCommand extends Command
{
    protected $signature = 'sap:sync-do-from-grpo
                            {--today : Sync GRPO created today (default when no other mode is given)}
                            {--yesterday : Sync GRPO created yesterday}
                            {--start= : Start date (Y-m-d), use with --end}
                            {--end= : End date (Y-m-d), use with --start}
                            {--user=1 : User ID for created_by and audit log (default 1)}';

    protected $description = 'Sync Delivery Order records from SAP GRPO (OPDN) and write sap_logs audit';

    public function handle(): int
    {
        $hasToday = (bool) $this->option('today');
        $hasYesterday = (bool) $this->option('yesterday');
        $startOpt = $this->option('start');
        $endOpt = $this->option('end');
        $hasRange = $startOpt !== null && $startOpt !== '' && $endOpt !== null && $endOpt !== '';

        if (($startOpt !== null && $startOpt !== '') xor ($endOpt !== null && $endOpt !== '')) {
            $this->error('Both --start and --end are required for a custom range');

            return self::FAILURE;
        }

        $modes = (int) $hasToday + (int) $hasYesterday + (int) $hasRange;
        if ($modes > 1) {
            $this->error('Specify at most one of: --today, --yesterday, or both --start and --end');

            return self::FAILURE;
        }

        $timezone = 'Asia/Makassar';
        $now = now($timezone);

        if ($hasYesterday) {
            $targetDates = [$now->copy()->subDay()->format('Y-m-d')];
        } elseif ($hasRange) {
            $start = Carbon::parse($startOpt, $timezone)->startOfDay();
            $end = Carbon::parse($endOpt, $timezone)->startOfDay();

            if ($start->gt($end)) {
                $this->error('--start must be on or before --end');

                return self::FAILURE;
            }

            $targetDates = [];
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $targetDates[] = $date->format('Y-m-d');
            }
        } else {
            $targetDates = [$now->format('Y-m-d')];
        }

        $userId = max(1, (int) $this->option('user'));
        Auth::loginUsingId($userId);

        $auditContext = [
            'trigger' => 'cli',
            'triggered_by_user_id' => $userId,
        ];

        $sapService = app(SapService::class);
        $totalFetched = 0;
        $totalCreated = 0;
        $totalSkipped = 0;
        $failed = false;
        $lastError = null;

        foreach ($targetDates as $targetDate) {
            $this->info("SAP GRPO → DO sync: {$targetDate} (user {$userId})");

            try {
                $job = new SyncSapDoFromGrpoJob($targetDate, $auditContext);
                $job->handle($sapService);

                $logEntry = DB::table('sap_logs')
                    ->where('action', 'query_sync')
                    ->latest('id')
                    ->first();

                if (! $logEntry || $logEntry->status !== 'success') {
                    $failed = true;
                    $lastError = $logEntry?->error_message ?? 'Unknown error';

                    continue;
                }

                $response = json_decode($logEntry->response_payload, true) ?? [];
                $totalFetched += $response['fetched'] ?? 0;
                $totalCreated += $response['success'] ?? 0;
                $totalSkipped += $response['skipped'] ?? 0;
            } catch (\Throwable $e) {
                $failed = true;
                $lastError = $e->getMessage();
            }
        }

        if ($failed) {
            $this->error('Sync failed: '.substr((string) $lastError, 0, 500));

            return self::FAILURE;
        }

        $this->info("Done. SAP rows: {$totalFetched}. Created: {$totalCreated}, skipped: {$totalSkipped}");

        return self::SUCCESS;
    }
}
