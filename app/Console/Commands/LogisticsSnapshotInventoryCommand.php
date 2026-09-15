<?php

namespace App\Console\Commands;

use App\Models\LogisticsInventoryItem;
use App\Models\LogisticsInventoryPivot;
use App\Models\LogisticsInventorySnapshot;
use App\Repositories\SapInventoryRepository;
use App\Services\Logistics\ItemCategoryResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LogisticsSnapshotInventoryCommand extends Command
{
    protected $signature = 'logistics:snapshot-inventory';

    protected $description = 'Fetch SAP warehouse inventory and store a daily snapshot in DDS';

    public function handle(
        SapInventoryRepository $repository,
        ItemCategoryResolver $categoryResolver
    ): int {
        $timezone = 'Asia/Makassar';
        $snapshotDate = now($timezone)->toDateString();
        $startedAt = microtime(true);

        try {
            $rows = $repository->fetchAll();
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            DB::transaction(function () use ($rows, $categoryResolver, $snapshotDate, $durationMs): void {
                $totalValue = 0.0;
                $preparedRows = [];

                foreach ($rows as $row) {
                    $category = $categoryResolver->resolve((string) $row['item_code']);
                    $rowTotalValue = (float) ($row['total_value'] ?? 0);
                    $totalValue += $rowTotalValue;

                    $preparedRows[] = [
                        'row' => $row,
                        'category' => $category,
                    ];
                }

                $snapshot = LogisticsInventorySnapshot::query()->create([
                    'snapshot_date' => $snapshotDate,
                    'status' => 'success',
                    'row_count' => count($preparedRows),
                    'total_value' => $totalValue,
                    'duration_ms' => $durationMs,
                    'created_at' => now(),
                ]);

                $pivotAccumulator = [];

                foreach ($preparedRows as $prepared) {
                    $row = $prepared['row'];
                    $category = $prepared['category'];
                    $project = $row['project'] ?? null;
                    $pivotKey = ($project ?? '').'|'.$category;

                    if (! isset($pivotAccumulator[$pivotKey])) {
                        $pivotAccumulator[$pivotKey] = [
                            'project' => $project,
                            'category' => $category,
                            'sum_instock' => 0.0,
                            'sum_value' => 0.0,
                        ];
                    }

                    $pivotAccumulator[$pivotKey]['sum_instock'] += (float) ($row['instock'] ?? 0);
                    $pivotAccumulator[$pivotKey]['sum_value'] += (float) ($row['total_value'] ?? 0);

                    LogisticsInventoryItem::query()->create([
                        'snapshot_id' => $snapshot->id,
                        'model_no' => $row['model_no'] ?? null,
                        'unit_no' => $row['unit_no'] ?? null,
                        'item_code' => $row['item_code'],
                        'item_name' => $row['item_name'] ?? null,
                        'category' => $category,
                        'uom' => $row['uom'] ?? null,
                        'instock' => $row['instock'] ?? 0,
                        'committed' => $row['committed'] ?? 0,
                        'ordered' => $row['ordered'] ?? 0,
                        'currency' => $row['currency'] ?? null,
                        'last_price' => $row['last_price'] ?? null,
                        'total_value' => $row['total_value'] ?? 0,
                        'whs_code' => $row['whs_code'] ?? null,
                        'whs_name' => $row['whs_name'] ?? null,
                        'project' => $project,
                        'status' => $row['status'] ?? null,
                        'last_mr_no' => $row['last_mr_no'] ?? null,
                        'last_mi_no' => $row['last_mi_no'] ?? null,
                    ]);
                }

                foreach ($pivotAccumulator as $pivot) {
                    LogisticsInventoryPivot::query()->create([
                        'snapshot_id' => $snapshot->id,
                        'project' => $pivot['project'],
                        'category' => $pivot['category'],
                        'sum_instock' => $pivot['sum_instock'],
                        'sum_value' => $pivot['sum_value'],
                    ]);
                }
            });

            $snapshot = LogisticsInventorySnapshot::query()->latest('id')->first();

            $this->writeSapLog(
                status: 'success',
                snapshotDate: $snapshotDate,
                durationMs: $snapshot?->duration_ms ?? 0,
                rowCount: $snapshot?->row_count ?? 0,
                totalValue: (float) ($snapshot?->total_value ?? 0),
            );

            $this->info(sprintf(
                'Inventory snapshot saved for %s (%d rows, %.2f total value, %d ms).',
                $snapshotDate,
                $snapshot?->row_count ?? 0,
                (float) ($snapshot?->total_value ?? 0),
                $snapshot?->duration_ms ?? 0
            ));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            LogisticsInventorySnapshot::query()->create([
                'snapshot_date' => $snapshotDate,
                'status' => 'failed',
                'row_count' => 0,
                'total_value' => 0,
                'error_message' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'created_at' => now(),
            ]);

            $this->writeSapLog(
                status: 'failed',
                snapshotDate: $snapshotDate,
                durationMs: $durationMs,
                rowCount: 0,
                totalValue: 0.0,
                errorMessage: $e->getMessage(),
            );

            $this->error('Inventory snapshot failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function writeSapLog(
        string $status,
        string $snapshotDate,
        int $durationMs,
        int $rowCount,
        float $totalValue,
        ?string $errorMessage = null
    ): void {
        DB::table('sap_logs')->insert([
            'action' => 'logistics_inventory_snapshot',
            'status' => $status,
            'request_payload' => json_encode([
                'snapshot_date' => $snapshotDate,
                'trigger' => 'cli',
            ]),
            'response_payload' => json_encode([
                'row_count' => $rowCount,
                'total_value' => $totalValue,
                'duration_ms' => $durationMs,
            ]),
            'error_message' => $errorMessage,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
