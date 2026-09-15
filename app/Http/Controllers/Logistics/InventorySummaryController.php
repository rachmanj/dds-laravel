<?php

namespace App\Http\Controllers\Logistics;

use App\Exports\LogisticsInventoryExport;
use App\Http\Controllers\Controller;
use App\Models\LogisticsInventoryItem;
use App\Models\LogisticsInventoryPivot;
use App\Models\LogisticsInventorySnapshot;
use App\Support\CompactNumberFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class InventorySummaryController extends Controller
{
    public function index(): View
    {
        $latestSnapshot = LogisticsInventorySnapshot::query()->latest('id')->first();
        $displaySnapshot = $this->latestSuccessfulSnapshot();

        $showWarning = $latestSnapshot === null || $latestSnapshot->status === 'failed';
        $warningMessage = $this->buildWarningMessage($latestSnapshot, $displaySnapshot);

        $totalItems = $displaySnapshot?->row_count ?? 0;
        $totalValue = (float) ($displaySnapshot?->total_value ?? 0);
        $warehouseCount = 0;
        $snapshotDate = $displaySnapshot?->snapshot_date;

        $instockByProjectChart = ['labels' => [], 'values' => []];
        $valueByCategoryChart = ['labels' => [], 'values' => []];
        $pivotMatrix = [
            'projects' => [],
            'categories' => [],
            'values' => [],
        ];
        $valuePivotMatrix = [
            'projects' => [],
            'categories' => [],
            'values' => [],
        ];
        $filterOptions = [
            'warehouses' => collect(),
            'projects' => collect(),
            'categories' => collect(),
            'statuses' => collect(),
        ];

        if ($displaySnapshot !== null) {
            $warehouseCount = LogisticsInventoryItem::query()
                ->where('snapshot_id', $displaySnapshot->id)
                ->distinct()
                ->count('whs_code');

            $pivots = LogisticsInventoryPivot::query()
                ->where('snapshot_id', $displaySnapshot->id)
                ->get();

            $instockByProject = $pivots
                ->groupBy(fn ($pivot) => $pivot->project ?? '(tanpa project)')
                ->map(fn (Collection $rows) => (float) $rows->sum('sum_instock'))
                ->sortKeys();

            $valueByCategory = $pivots
                ->groupBy('category')
                ->map(fn (Collection $rows) => (float) $rows->sum('sum_value'))
                ->sortKeys();

            $instockByProjectChart = [
                'labels' => $instockByProject->keys()->values()->all(),
                'values' => $instockByProject->values()->all(),
            ];

            $valueByCategoryChart = [
                'labels' => $valueByCategory->keys()->values()->all(),
                'values' => $valueByCategory->values()->all(),
            ];

            $pivotMatrix = $this->buildPivotMatrix($pivots, 'sum_instock');
            $valuePivotMatrix = $this->buildPivotMatrix($pivots, 'sum_value');
            $filterOptions = $this->buildFilterOptions($displaySnapshot->id);
        }

        $recentSnapshots = LogisticsInventorySnapshot::query()
            ->latest('id')
            ->limit(10)
            ->get();

        return view('logistics.inventory', [
            'showWarning' => $showWarning,
            'warningMessage' => $warningMessage,
            'totalItems' => $totalItems,
            'totalItemsFormatted' => CompactNumberFormatter::format($totalItems, 0),
            'totalValue' => $totalValue,
            'totalValueFormatted' => CompactNumberFormatter::format($totalValue),
            'warehouseCount' => $warehouseCount,
            'warehouseCountFormatted' => CompactNumberFormatter::format($warehouseCount, 0),
            'snapshotDate' => $snapshotDate,
            'instockByProjectChart' => $instockByProjectChart,
            'valueByCategoryChart' => $valueByCategoryChart,
            'pivotMatrix' => $pivotMatrix,
            'valuePivotMatrix' => $valuePivotMatrix,
            'filterOptions' => $filterOptions,
            'recentSnapshots' => $recentSnapshots,
            'hasDisplayData' => $displaySnapshot !== null,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $displaySnapshot = $this->latestSuccessfulSnapshot();

        if ($displaySnapshot === null) {
            return DataTables::of(collect())->make(true);
        }

        $query = $this->filteredItemsQuery($displaySnapshot->id, $request);

        return DataTables::of($query)
            ->addColumn('formatted_instock', fn ($item) => number_format((float) $item->instock, 2, ',', '.'))
            ->addColumn('formatted_committed', fn ($item) => number_format((float) $item->committed, 2, ',', '.'))
            ->addColumn('formatted_ordered', fn ($item) => number_format((float) $item->ordered, 2, ',', '.'))
            ->addColumn('formatted_last_price', fn ($item) => $item->last_price !== null
                ? number_format((float) $item->last_price, 2, ',', '.')
                : '-')
            ->addColumn('formatted_total_value', fn ($item) => CompactNumberFormatter::format((float) $item->total_value))
            ->make(true);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $displaySnapshot = $this->latestSuccessfulSnapshot();

        $query = $displaySnapshot
            ? $this->filteredItemsQuery($displaySnapshot->id, $request)
            : LogisticsInventoryItem::query()->whereRaw('0 = 1');

        $filename = 'logistics_inventory_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new LogisticsInventoryExport($query), $filename);
    }

    private function latestSuccessfulSnapshot(): ?LogisticsInventorySnapshot
    {
        return LogisticsInventorySnapshot::query()
            ->where('status', 'success')
            ->latest('id')
            ->first();
    }

    private function buildWarningMessage(
        ?LogisticsInventorySnapshot $latestSnapshot,
        ?LogisticsInventorySnapshot $displaySnapshot
    ): ?string {
        if ($latestSnapshot === null) {
            return 'Belum ada snapshot inventory. Data akan tersedia setelah proses snapshot harian berjalan.';
        }

        if ($latestSnapshot->status !== 'failed') {
            return null;
        }

        $errorMessage = $latestSnapshot->error_message ?: 'Error tidak diketahui';

        if ($displaySnapshot !== null) {
            return 'Snapshot terakhir gagal: '.$errorMessage.'. Menampilkan data snapshot sukses terakhir ('.$displaySnapshot->snapshot_date->format('d M Y').').';
        }

        return 'Snapshot terakhir gagal: '.$errorMessage.'. Belum ada data inventory yang dapat ditampilkan.';
    }

    /**
     * @return array{projects: array<int, string>, categories: array<int, string>, values: array<string, array<string, float>>}
     */
    private function buildPivotMatrix(Collection $pivots, string $valueField): array
    {
        $projects = $pivots
            ->pluck('project')
            ->map(fn ($project) => $project ?? '(tanpa project)')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $categories = $pivots
            ->pluck('category')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $values = [];

        foreach ($pivots as $pivot) {
            $projectKey = $pivot->project ?? '(tanpa project)';
            $values[$projectKey][$pivot->category] = (float) $pivot->{$valueField};
        }

        return [
            'projects' => $projects,
            'categories' => $categories,
            'values' => $values,
        ];
    }

    /**
     * @return array{
     *     warehouses: \Illuminate\Support\Collection<int, array{code: string, name: string}>,
     *     projects: \Illuminate\Support\Collection<int, string>,
     *     categories: \Illuminate\Support\Collection<int, string>,
     *     statuses: \Illuminate\Support\Collection<int, string>
     * }
     */
    private function buildFilterOptions(int $snapshotId): array
    {
        $items = LogisticsInventoryItem::query()
            ->where('snapshot_id', $snapshotId)
            ->get(['whs_code', 'whs_name', 'project', 'category', 'status']);

        $warehouses = $items
            ->filter(fn ($item) => filled($item->whs_code))
            ->unique('whs_code')
            ->sortBy('whs_code')
            ->map(fn ($item) => [
                'code' => $item->whs_code,
                'name' => $item->whs_name ?: $item->whs_code,
            ])
            ->values();

        return [
            'warehouses' => $warehouses,
            'projects' => $items->pluck('project')->filter()->unique()->sort()->values(),
            'categories' => $items->pluck('category')->filter()->unique()->sort()->values(),
            'statuses' => $items->pluck('status')->filter()->unique()->sort()->values(),
        ];
    }

    private function filteredItemsQuery(int $snapshotId, Request $request)
    {
        $query = LogisticsInventoryItem::query()
            ->where('snapshot_id', $snapshotId)
            ->orderBy('item_code');

        if ($request->filled('whs_code')) {
            $query->where('whs_code', $request->string('whs_code')->toString());
        }

        if ($request->filled('project')) {
            $query->where('project', $request->string('project')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $query;
    }
}
