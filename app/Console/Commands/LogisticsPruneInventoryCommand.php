<?php

namespace App\Console\Commands;

use App\Models\LogisticsInventoryItem;
use App\Models\LogisticsInventorySnapshot;
use Illuminate\Console\Command;

class LogisticsPruneInventoryCommand extends Command
{
    protected $signature = 'logistics:prune-inventory';

    protected $description = 'Purge inventory snapshots older than 12 months and trim item detail to latest and monthly archives';

    public function handle(): int
    {
        $timezone = 'Asia/Makassar';
        $cutoffDate = now($timezone)->subMonths(12)->toDateString();

        $deletedOldSnapshots = LogisticsInventorySnapshot::query()
            ->whereDate('snapshot_date', '<', $cutoffDate)
            ->delete();

        $latestSnapshotId = LogisticsInventorySnapshot::query()->latest('id')->value('id');

        $monthlySnapshotIds = LogisticsInventorySnapshot::query()
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->get(['id', 'snapshot_date'])
            ->unique(fn (LogisticsInventorySnapshot $snapshot): string => $snapshot->snapshot_date->format('Y-m'))
            ->pluck('id')
            ->all();

        $keepItemSnapshotIds = collect($monthlySnapshotIds)
            ->when($latestSnapshotId, fn ($ids) => $ids->push($latestSnapshotId))
            ->unique()
            ->values()
            ->all();

        if ($keepItemSnapshotIds === []) {
            $deletedItems = LogisticsInventoryItem::query()->delete();
        } else {
            $deletedItems = LogisticsInventoryItem::query()
                ->whereNotIn('snapshot_id', $keepItemSnapshotIds)
                ->delete();
        }

        $this->info(sprintf(
            'Pruned %d old snapshots, removed %d item rows (kept detail for %d snapshots).',
            $deletedOldSnapshots,
            $deletedItems,
            count($keepItemSnapshotIds)
        ));

        return self::SUCCESS;
    }
}
