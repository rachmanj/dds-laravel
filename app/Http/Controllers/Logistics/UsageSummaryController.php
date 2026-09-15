<?php

namespace App\Http\Controllers\Logistics;

use App\Exports\LogisticsUsageExport;
use App\Http\Controllers\Controller;
use App\Repositories\SapUsageRepository;
use App\Support\CompactNumberFormatter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class UsageSummaryController extends Controller
{
    private const MAX_DATE_RANGE_DAYS = 92;

    private const DATE_RANGE_ERROR = 'Rentang tanggal tidak boleh lebih dari 92 hari.';

    public function __construct(private SapUsageRepository $usageRepository) {}

    public function index(Request $request): View
    {
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);

        $rows = [];
        $kpis = $this->emptyKpis();
        $filterOptions = [
            'projects' => collect(),
        ];
        $summaryByProject = [];
        $summaryByCategory = [];

        if ($dateRangeError === null) {
            $rows = $this->usageRepository->fetch($fromDate, $toDate);
            $filterOptions = $this->buildFilterOptions($rows);
            $filteredRows = $this->applyFilters($rows, $request);
            $kpis = $this->buildKpis($filteredRows);
            $summaryByProject = $kpis['by_project'];
            $summaryByCategory = $kpis['by_category'];
        }

        return view('logistics.usage', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'dateRangeError' => $dateRangeError,
            'kpis' => $kpis,
            'summaryByProject' => $summaryByProject,
            'summaryByCategory' => $summaryByCategory,
            'filterOptions' => $filterOptions,
            'selectedProject' => $request->string('project')->toString(),
            'selectedSource' => $request->string('sumber')->toString(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);

        if ($dateRangeError !== null) {
            return response()->json(['message' => $dateRangeError], 422);
        }

        $rows = $this->applyFilters(
            $this->usageRepository->fetch($fromDate, $toDate),
            $request
        );

        return DataTables::of(collect($rows))
            ->addColumn('source_label', fn (array $row) => $this->sourceLabel($row['source'] ?? ''))
            ->addColumn('formatted_quantity', fn (array $row) => $this->formatNumber($row['quantity'] ?? null, 2))
            ->addColumn('formatted_stockprice', fn (array $row) => $this->formatNumber($row['stockprice'] ?? null, 2))
            ->addColumn('formatted_total', fn (array $row) => $this->formatNumber($row['total'] ?? null, 2))
            ->addColumn('formatted_return_quantity', fn (array $row) => $this->formatNumber($row['return_quantity'] ?? null, 2))
            ->make(true);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);

        if ($dateRangeError !== null) {
            return redirect()
                ->route('logistics.usage.index', $request->only(['from_date', 'to_date', 'project', 'sumber']))
                ->withErrors(['date_range' => $dateRangeError]);
        }

        $rows = collect($this->applyFilters(
            $this->usageRepository->fetch($fromDate, $toDate),
            $request
        ));

        $filename = 'logistics_usage_'.$fromDate.'_'.$toDate.'_'.now()->format('His').'.xlsx';

        return Excel::download(new LogisticsUsageExport($rows), $filename);
    }

    /**
     * @return array{0: string, 1: string, 2: string|null}
     */
    private function resolveDateRange(Request $request): array
    {
        $fromDate = $request->filled('from_date')
            ? $request->string('from_date')->toString()
            : now()->startOfMonth()->toDateString();

        $toDate = $request->filled('to_date')
            ? $request->string('to_date')->toString()
            : now()->toDateString();

        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();

        if ($from->gt($to)) {
            return [$fromDate, $toDate, self::DATE_RANGE_ERROR];
        }

        if ($from->diffInDays($to) > self::MAX_DATE_RANGE_DAYS) {
            return [$fromDate, $toDate, self::DATE_RANGE_ERROR];
        }

        return [$fromDate, $toDate, null];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function applyFilters(array $rows, Request $request): array
    {
        $project = $request->string('project')->toString();
        $source = $request->string('sumber')->toString();

        return array_values(array_filter($rows, function (array $row) use ($project, $source) {
            if ($project !== '' && (string) ($row['project'] ?? '') !== $project) {
                return false;
            }

            if ($source !== '' && (string) ($row['source'] ?? '') !== $source) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     row_count: int,
     *     document_count: int,
     *     total_value: float,
     *     row_count_formatted: string,
     *     document_count_formatted: string,
     *     total_value_formatted: string,
     *     by_project: array<int, array{label: string, document_count: int, total_value: float}>,
     *     by_category: array<int, array{label: string, document_count: int, total_value: float}>
     * }
     */
    private function buildKpis(array $rows): array
    {
        $collection = collect($rows);

        $documentCount = $collection
            ->pluck('doc_num')
            ->filter()
            ->unique()
            ->count();

        $totalValue = (float) $collection->sum(fn (array $row) => (float) ($row['total'] ?? 0));

        $byProject = $collection
            ->groupBy(fn (array $row) => filled($row['project'] ?? null) ? (string) $row['project'] : '(tanpa project)')
            ->map(function (Collection $group, string $label) {
                return [
                    'label' => $label,
                    'document_count' => $group->pluck('doc_num')->filter()->unique()->count(),
                    'total_value' => (float) $group->sum(fn (array $row) => (float) ($row['total'] ?? 0)),
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();

        $byCategory = $collection
            ->groupBy(fn (array $row) => filled($row['category'] ?? null) ? (string) $row['category'] : '(tanpa kategori)')
            ->map(function (Collection $group, string $label) {
                return [
                    'label' => $label,
                    'document_count' => $group->pluck('doc_num')->filter()->unique()->count(),
                    'total_value' => (float) $group->sum(fn (array $row) => (float) ($row['total'] ?? 0)),
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();

        return [
            'row_count' => count($rows),
            'document_count' => $documentCount,
            'total_value' => $totalValue,
            'row_count_formatted' => CompactNumberFormatter::format(count($rows), 0),
            'document_count_formatted' => CompactNumberFormatter::format($documentCount, 0),
            'total_value_formatted' => CompactNumberFormatter::format($totalValue),
            'by_project' => $byProject,
            'by_category' => $byCategory,
        ];
    }

    /**
     * @return array{
     *     row_count: int,
     *     document_count: int,
     *     total_value: float,
     *     row_count_formatted: string,
     *     document_count_formatted: string,
     *     total_value_formatted: string,
     *     by_project: array<int, array{label: string, document_count: int, total_value: float}>,
     *     by_category: array<int, array{label: string, document_count: int, total_value: float}>
     * }
     */
    private function emptyKpis(): array
    {
        return [
            'row_count' => 0,
            'document_count' => 0,
            'total_value' => 0.0,
            'row_count_formatted' => CompactNumberFormatter::format(0, 0),
            'document_count_formatted' => CompactNumberFormatter::format(0, 0),
            'total_value_formatted' => CompactNumberFormatter::format(0),
            'by_project' => [],
            'by_category' => [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{projects: Collection<int, string>}
     */
    private function buildFilterOptions(array $rows): array
    {
        $projects = collect($rows)
            ->pluck('project')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'projects' => $projects,
        ];
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'goods_issue' => 'Goods Issue',
            'delivery' => 'Delivery',
            'ap_service' => 'AP Service',
            default => $source !== '' ? $source : '-',
        };
    }

    private function formatNumber(mixed $value, int $decimals): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, $decimals, ',', '.');
    }
}
