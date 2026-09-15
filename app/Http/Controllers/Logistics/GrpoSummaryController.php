<?php

namespace App\Http\Controllers\Logistics;

use App\Exports\LogisticsGrpoExport;
use App\Http\Controllers\Controller;
use App\Models\AdditionalDocument;
use App\Repositories\SapGrpoRepository;
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

class GrpoSummaryController extends Controller
{
    private const MAX_DATE_RANGE_DAYS = 92;

    private const DATE_RANGE_ERROR = 'Rentang tanggal tidak boleh lebih dari 92 hari.';

    public function __construct(private SapGrpoRepository $grpoRepository) {}

    public function index(Request $request): View
    {
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);

        $rows = [];
        $kpis = $this->emptyKpis();
        $filterOptions = [
            'projects' => collect(),
            'vendors' => collect(),
        ];
        $summaryByProject = [];
        $summaryByVendor = [];

        if ($dateRangeError === null) {
            $rows = $this->grpoRepository->fetch($fromDate, $toDate);
            $filterOptions = $this->buildFilterOptions($rows);
            $filteredRows = $this->applyFilters($rows, $request);
            $kpis = $this->buildKpis($filteredRows);
            $summaryByProject = $kpis['by_project'];
            $summaryByVendor = $kpis['by_vendor'];
        }

        return view('logistics.grpo', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'dateRangeError' => $dateRangeError,
            'kpis' => $kpis,
            'summaryByProject' => $summaryByProject,
            'summaryByVendor' => $summaryByVendor,
            'filterOptions' => $filterOptions,
            'selectedProject' => $request->string('project')->toString(),
            'selectedVendor' => $request->string('vendor')->toString(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);

        if ($dateRangeError !== null) {
            return response()->json(['message' => $dateRangeError], 422);
        }

        $rows = $this->applyFilters(
            $this->grpoRepository->fetch($fromDate, $toDate),
            $request
        );

        $documentLinks = $this->resolveDocumentLinks($rows);

        return DataTables::of(collect($rows))
            ->addColumn('grpo_no_display', function (array $row) use ($documentLinks) {
                $grpoNo = (string) ($row['grpo_no'] ?? '');
                if ($grpoNo === '') {
                    return '-';
                }

                $document = $documentLinks->get($grpoNo);
                if ($document === null) {
                    return e($grpoNo);
                }

                $url = route('additional-documents.show', $document);

                return '<a href="'.e($url).'">'.e($grpoNo).'</a>';
            })
            ->addColumn('formatted_quantity', fn (array $row) => $this->formatNumber($row['quantity'] ?? null, 2))
            ->addColumn('formatted_price', fn (array $row) => $this->formatNumber($row['price'] ?? null, 2))
            ->addColumn('formatted_total_price', fn (array $row) => $this->formatNumber($row['total_price'] ?? null, 2))
            ->rawColumns(['grpo_no_display'])
            ->make(true);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);

        if ($dateRangeError !== null) {
            return redirect()
                ->route('logistics.grpo.index', $request->only(['from_date', 'to_date', 'project', 'vendor']))
                ->withErrors(['date_range' => $dateRangeError]);
        }

        $rows = $this->applyFilters(
            $this->grpoRepository->fetch($fromDate, $toDate),
            $request
        );

        $filename = 'logistics_grpo_'.$fromDate.'_'.$toDate.'_'.now()->format('His').'.xlsx';

        return Excel::download(new LogisticsGrpoExport($rows), $filename);
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
        $vendor = $request->string('vendor')->toString();

        return array_values(array_filter($rows, function (array $row) use ($project, $vendor) {
            if ($project !== '' && (string) ($row['project'] ?? '') !== $project) {
                return false;
            }

            if ($vendor !== '' && (string) ($row['vendor_code'] ?? '') !== $vendor) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     grpo_count: int,
     *     row_count: int,
     *     total_value: float,
     *     grpo_count_formatted: string,
     *     row_count_formatted: string,
     *     total_value_formatted: string,
     *     by_project: array<int, array{label: string, grpo_count: int, total_value: float}>,
     *     by_vendor: array<int, array{label: string, grpo_count: int, total_value: float}>
     * }
     */
    private function buildKpis(array $rows): array
    {
        $collection = collect($rows);

        $grpoCount = $collection
            ->pluck('grpo_no')
            ->filter()
            ->unique()
            ->count();

        $totalValue = (float) $collection->sum(fn (array $row) => (float) ($row['total_price'] ?? 0));

        $byProject = $collection
            ->groupBy(fn (array $row) => filled($row['project'] ?? null) ? (string) $row['project'] : '(tanpa project)')
            ->map(function (Collection $group, string $label) {
                return [
                    'label' => $label,
                    'grpo_count' => $group->pluck('grpo_no')->filter()->unique()->count(),
                    'total_value' => (float) $group->sum(fn (array $row) => (float) ($row['total_price'] ?? 0)),
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();

        $byVendor = $collection
            ->groupBy(fn (array $row) => filled($row['vendor_name'] ?? null)
                ? (string) $row['vendor_name']
                : (filled($row['vendor_code'] ?? null) ? (string) $row['vendor_code'] : '(tanpa vendor)'))
            ->map(function (Collection $group, string $label) {
                return [
                    'label' => $label,
                    'grpo_count' => $group->pluck('grpo_no')->filter()->unique()->count(),
                    'total_value' => (float) $group->sum(fn (array $row) => (float) ($row['total_price'] ?? 0)),
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();

        return [
            'grpo_count' => $grpoCount,
            'row_count' => count($rows),
            'total_value' => $totalValue,
            'grpo_count_formatted' => CompactNumberFormatter::format($grpoCount, 0),
            'row_count_formatted' => CompactNumberFormatter::format(count($rows), 0),
            'total_value_formatted' => CompactNumberFormatter::format($totalValue),
            'by_project' => $byProject,
            'by_vendor' => $byVendor,
        ];
    }

    /**
     * @return array{
     *     grpo_count: int,
     *     row_count: int,
     *     total_value: float,
     *     grpo_count_formatted: string,
     *     row_count_formatted: string,
     *     total_value_formatted: string,
     *     by_project: array<int, array{label: string, grpo_count: int, total_value: float}>,
     *     by_vendor: array<int, array{label: string, grpo_count: int, total_value: float}>
     * }
     */
    private function emptyKpis(): array
    {
        return [
            'grpo_count' => 0,
            'row_count' => 0,
            'total_value' => 0.0,
            'grpo_count_formatted' => CompactNumberFormatter::format(0, 0),
            'row_count_formatted' => CompactNumberFormatter::format(0, 0),
            'total_value_formatted' => CompactNumberFormatter::format(0),
            'by_project' => [],
            'by_vendor' => [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     projects: Collection<int, string>,
     *     vendors: Collection<int, array{code: string, name: string}>
     * }
     */
    private function buildFilterOptions(array $rows): array
    {
        $collection = collect($rows);

        $projects = $collection
            ->pluck('project')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $vendors = $collection
            ->filter(fn (array $row) => filled($row['vendor_code'] ?? null))
            ->unique('vendor_code')
            ->sortBy('vendor_code')
            ->map(fn (array $row) => [
                'code' => (string) $row['vendor_code'],
                'name' => filled($row['vendor_name'] ?? null)
                    ? (string) $row['vendor_name']
                    : (string) $row['vendor_code'],
            ])
            ->values();

        return [
            'projects' => $projects,
            'vendors' => $vendors,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<string, AdditionalDocument>
     */
    private function resolveDocumentLinks(array $rows): Collection
    {
        $grpoNumbers = collect($rows)
            ->pluck('grpo_no')
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values();

        if ($grpoNumbers->isEmpty()) {
            return collect();
        }

        return AdditionalDocument::query()
            ->whereIn('grpo_no', $grpoNumbers->all())
            ->get()
            ->keyBy(fn (AdditionalDocument $document) => (string) $document->grpo_no);
    }

    private function formatNumber(mixed $value, int $decimals): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, $decimals, ',', '.');
    }
}
