<?php

namespace App\Console\Commands;

use App\Models\LogisticsWarehouseProject;
use App\Models\Project;
use App\Services\Logistics\DeliveryPartQueryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeliveryPartPreviewCommand extends Command
{
    protected $signature = 'delivery-part:preview
                            {--from= : Tanggal awal ITO (YYYY-MM-DD)}
                            {--to= : Tanggal akhir ITO (YYYY-MM-DD)}
                            {--project= : Filter kode project (mis. 017C)}';

    protected $description = 'Preview baris Delivery Part dari SAP (tanpa menulis ke database DDS)';

    public function handle(DeliveryPartQueryService $queryService): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if (! is_string($from) || $from === '' || ! is_string($to) || $to === '') {
            $this->error('Gunakan --from=YYYY-MM-DD dan --to=YYYY-MM-DD.');

            return self::FAILURE;
        }

        $fromDate = Carbon::parse($from);
        $toDate = Carbon::parse($to);

        $rows = $queryService->rows($fromDate, $toDate);
        $projectFilter = $this->option('project');

        if (is_string($projectFilter) && $projectFilter !== '') {
            $project = Project::query()->where('code', $projectFilter)->first();
            if ($project === null) {
                $this->error('Project tidak ditemukan: '.$projectFilter);

                return self::FAILURE;
            }

            $warehouseCodes = LogisticsWarehouseProject::query()
                ->where('project_id', $project->id)
                ->where('is_active', true)
                ->pluck('whs_code')
                ->all();

            $warehouseSet = array_flip($warehouseCodes);
            $rows = $rows->filter(fn (array $row) => isset($warehouseSet[$row['to_warehouse'] ?? '']));
        }

        $this->info('Total baris: '.$rows->count());
        $this->newLine();
        $this->info('5 baris pertama:');
        $this->table(
            ['ito_no', 'item_code', 'unit_no', 'to_warehouse', 'vendor'],
            $rows->take(5)->map(fn (array $row) => [
                $row['ito_no'] ?? '-',
                $row['item_code'] ?? '-',
                $row['unit_no'] ?? '-',
                $row['to_warehouse'] ?? '-',
                $row['vendor'] ?? '-',
            ])->all()
        );

        $this->newLine();
        $this->info('Jumlah per site (to_warehouse → project):');

        $mapping = LogisticsWarehouseProject::query()
            ->where('is_active', true)
            ->with('project')
            ->get()
            ->keyBy('whs_code');

        $countsByProject = [];
        foreach ($rows as $row) {
            $whs = $row['to_warehouse'] ?? '';
            $map = $mapping->get($whs);
            $label = $map?->project?->code ?? '(belum dipetakan: '.$whs.')';
            $countsByProject[$label] = ($countsByProject[$label] ?? 0) + 1;
        }

        ksort($countsByProject);
        foreach ($countsByProject as $site => $count) {
            $this->line(sprintf('  %s: %d', $site, $count));
        }

        return self::SUCCESS;
    }
}
