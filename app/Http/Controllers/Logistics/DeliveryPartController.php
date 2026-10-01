<?php

namespace App\Http\Controllers\Logistics;

use App\Exceptions\SapSqlQueryException;
use App\Exports\DeliveryPartExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryPartEntryRequest;
use App\Http\Requests\UpdateDeliveryPartEntryRequest;
use App\Models\DeliveryPartEntry;
use App\Models\DeliveryPartEntryHistory;
use App\Models\DeliveryPartItoCancel;
use App\Models\LogisticsWarehouseProject;
use App\Models\Project;
use App\Services\Logistics\DeliveryPartAssembler;
use App\Services\Logistics\DeliveryPartQueryService;
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

class DeliveryPartController extends Controller
{
    private const DATE_RANGE_ERROR = DeliveryPartQueryService::PAGE_DATE_RANGE_ERROR;

    public function __construct(
        private DeliveryPartQueryService $queryService,
        private DeliveryPartAssembler $assembler,
    ) {}

    public function index(Request $request): View
    {
        $sites = $this->loadSiteOptions();
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);
        $selectedProjectCode = $request->string('project')->toString();
        $sapError = null;

        if ($dateRangeError === null && $selectedProjectCode !== '' && $sites->contains('code', $selectedProjectCode)) {
            try {
                $this->queryService->rows(Carbon::parse($fromDate), Carbon::parse($toDate));
            } catch (SapSqlQueryException $e) {
                $sapError = $e->getMessage();
            }
        }

        return view('logistics.delivery-part', [
            'sites' => $sites,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedProject' => $selectedProjectCode,
            'sapError' => $sapError,
            'dateRangeError' => $dateRangeError,
            'ekspedisiOptions' => DeliveryPartEntry::EKSPEDISI_OPTIONS,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);

        if ($dateRangeError !== null) {
            return response()->json(['message' => $dateRangeError], 422);
        }

        $project = $this->resolveProject($request);

        if ($project === null) {
            return response()->json(['message' => 'Pilih site (project) terlebih dahulu.'], 422);
        }

        try {
            $rows = $this->buildRows($fromDate, $toDate, $project);
        } catch (SapSqlQueryException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        $canEdit = $request->user()?->can('edit-delivery-part') ?? false;
        $canCancel = $request->user()?->can('cancel-ito') ?? false;
        $cancelByDocEntry = $this->latestCancelsByDocEntry($rows);

        return DataTables::of($rows)
            ->addColumn('qty_display', fn (array $row) => $this->formatQty($row['qty'] ?? null))
            ->addColumn('keterangan_display', function (array $row) {
                if (($row['keterangan'] ?? null) === 'COMPLETE') {
                    return '<span class="badge badge-success">COMPLETE</span>';
                }

                return '';
            })
            ->addColumn('no_ito_display', function (array $row) use ($cancelByDocEntry) {
                $display = e((string) ($row['no_ito'] ?? '-'));
                $sap = $row['ito_no_sap'] ?? null;
                if ($sap !== null && ($row['ito_no_override'] ?? null) !== null && (string) $row['ito_no_override'] !== (string) $sap) {
                    $display = $display.' <small class="text-muted">(SAP: '.e((string) $sap).')</small>';
                }

                $badge = $this->cancelBadgeForRow($row, $cancelByDocEntry);
                if ($badge !== '') {
                    $display .= ' '.$badge;
                }

                return $display;
            })
            ->addColumn('actions', function (array $row) use ($canEdit, $canCancel, $cancelByDocEntry) {
                $buttons = [];

                if ($canEdit) {
                    $payload = htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    $buttons[] = '<button type="button" class="btn btn-xs btn-primary btn-edit-entry" data-row="'.$payload.'"><i class="fas fa-edit"></i> Edit</button>';
                }

                if ($canCancel && $this->rowEligibleForCancel($row, $cancelByDocEntry)) {
                    $payload = htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    $buttons[] = '<button type="button" class="btn btn-xs btn-danger btn-cancel-ito" data-row="'.$payload.'"><i class="fas fa-ban"></i> Batalkan</button>';
                }

                return implode(' ', $buttons);
            })
            ->rawColumns(['keterangan_display', 'no_ito_display', 'actions'])
            ->make(true);
    }

    public function storeEntry(StoreDeliveryPartEntryRequest $request): JsonResponse
    {
        $project = Project::query()->where('code', $request->string('project_code')->toString())->firstOrFail();

        $entry = DeliveryPartEntry::firstOrCreate(
            [
                'project_id' => $project->id,
                'ito_no' => $request->input('ito_no'),
                'item_code' => $request->input('item_code'),
                'unit_no' => $request->input('unit_no'),
            ],
            [
                'source' => DeliveryPartEntry::SOURCE_SAP,
                'created_by' => $request->user()?->id,
            ]
        );

        $this->applyManualFieldUpdates($entry, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Data berhasil disimpan.',
            'entry_id' => $entry->id,
        ]);
    }

    public function updateEntry(UpdateDeliveryPartEntryRequest $request, DeliveryPartEntry $entry): JsonResponse
    {
        $this->applyManualFieldUpdates($entry, $request->validated(), $request->user());

        return response()->json(['message' => 'Data berhasil disimpan.']);
    }

    public function refresh(Request $request): JsonResponse
    {
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);

        if ($dateRangeError !== null) {
            return response()->json(['message' => $dateRangeError], 422);
        }

        $this->queryService->bustForRange(Carbon::parse($fromDate), Carbon::parse($toDate));

        return response()->json(['message' => 'Cache SAP dibersihkan.']);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        [$fromDate, $toDate, $dateRangeError] = $this->resolveDateRange($request);

        if ($dateRangeError !== null) {
            return redirect()
                ->route('logistics.delivery-part.index', $request->only(['from_date', 'to_date', 'project']))
                ->withErrors(['date_range' => $dateRangeError]);
        }

        $project = $this->resolveProject($request);

        if ($project === null) {
            return redirect()
                ->route('logistics.delivery-part.index', $request->only(['from_date', 'to_date', 'project']))
                ->withErrors(['project' => 'Pilih site (project) untuk export.']);
        }

        try {
            $rows = $this->buildRows($fromDate, $toDate, $project);
        } catch (SapSqlQueryException $e) {
            return redirect()
                ->route('logistics.delivery-part.index', $request->only(['from_date', 'to_date', 'project']))
                ->withErrors(['sap' => $e->getMessage()]);
        }

        $filename = sprintf(
            'Delivery-Part-%s-%s-%s.xlsx',
            $project->code,
            $fromDate,
            $toDate
        );

        return Excel::download(new DeliveryPartExport($rows), $filename);
    }

    /**
     * @return Collection<int, array{code: string, id: int}>
     */
    private function loadSiteOptions(): Collection
    {
        return LogisticsWarehouseProject::query()
            ->where('is_active', true)
            ->with('project')
            ->get()
            ->pluck('project')
            ->filter()
            ->unique('id')
            ->sortBy('code')
            ->values()
            ->map(fn (Project $project) => ['id' => $project->id, 'code' => $project->code]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function defaultDateRange(Request $request): array
    {
        if ($request->filled('from_date') && $request->filled('to_date')) {
            return [
                $request->string('from_date')->toString(),
                $request->string('to_date')->toString(),
            ];
        }

        $now = now();

        return [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()];
    }

    /**
     * @return array{0: string, 1: string, 2: string|null}
     */
    private function resolveDateRange(Request $request): array
    {
        [$fromDate, $toDate] = $this->defaultDateRange($request);
        $dateRangeError = $this->queryService->validatePageDateRange(
            Carbon::parse($fromDate),
            Carbon::parse($toDate),
        );

        return [$fromDate, $toDate, $dateRangeError];
    }

    private function resolveProject(Request $request): ?Project
    {
        $code = $request->string('project')->toString();
        if ($code === '') {
            return null;
        }

        return Project::query()->where('code', $code)->first();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildRows(string $fromDate, string $toDate, Project $project): Collection
    {
        $warehouseCodes = LogisticsWarehouseProject::query()
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->pluck('whs_code');

        $sapRows = $this->queryService->rows(Carbon::parse($fromDate), Carbon::parse($toDate));

        $entries = DeliveryPartEntry::query()
            ->where('project_id', $project->id)
            ->get();

        return $this->assembler->assemble($sapRows, $warehouseCodes, $entries, $project->id);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyManualFieldUpdates(DeliveryPartEntry $entry, array $validated, ?\App\Models\User $user): void
    {
        $changes = [];

        foreach (DeliveryPartEntry::MANUAL_TRACKED_FIELDS as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $newValue = $validated[$field];
            $oldValue = $entry->{$field};

            $oldNormalized = $this->normalizeHistoryValue($field, $oldValue);
            $newNormalized = $this->normalizeHistoryValue($field, $newValue);

            if ($oldNormalized === $newNormalized) {
                continue;
            }

            $changes[$field] = ['old' => $oldNormalized, 'new' => $newNormalized];
            $entry->{$field} = $newValue;
        }

        if ($changes === []) {
            return;
        }

        $entry->updated_by = $user?->id;
        $entry->save();

        foreach ($changes as $field => $change) {
            DeliveryPartEntryHistory::query()->create([
                'delivery_part_entry_id' => $entry->id,
                'field' => $field,
                'old_value' => $change['old'],
                'new_value' => $change['new'],
                'user_id' => $user?->id,
                'created_at' => now(),
            ]);
        }
    }

    private function normalizeHistoryValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($field === 'tgl_delivery' && $value instanceof Carbon) {
            return $value->toDateString();
        }

        return (string) $value;
    }

    private function formatQty(mixed $qty): string
    {
        if ($qty === null) {
            return '-';
        }

        return CompactNumberFormatter::format((float) $qty, 2);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, DeliveryPartItoCancel>
     */
    private function latestCancelsByDocEntry(Collection $rows): array
    {
        $docEntries = $rows
            ->pluck('doc_entry')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        if ($docEntries === []) {
            return [];
        }

        $cancels = DeliveryPartItoCancel::query()
            ->whereIn('doc_entry', $docEntries)
            ->orderByDesc('requested_at')
            ->get()
            ->groupBy('doc_entry');

        $latest = [];
        foreach ($cancels as $docEntry => $group) {
            $latest[(int) $docEntry] = $group->first();
        }

        return $latest;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, DeliveryPartItoCancel>  $cancelByDocEntry
     */
    private function cancelBadgeForRow(array $row, array $cancelByDocEntry): string
    {
        $docEntry = $row['doc_entry'] ?? null;
        if ($docEntry === null) {
            return '';
        }

        $cancel = $cancelByDocEntry[(int) $docEntry] ?? null;
        if ($cancel === null) {
            return '';
        }

        if ($cancel->status === DeliveryPartItoCancel::STATUS_CANCELLED && $cancel->verified_at !== null) {
            $date = $cancel->verified_at->format('d-m-Y');

            return '<span class="badge badge-secondary">dibatalkan '.$date.'</span>';
        }

        if ($cancel->status === DeliveryPartItoCancel::STATUS_REQUESTED) {
            return '<span class="badge badge-warning">menunggu verifikasi</span>';
        }

        if ($cancel->status === DeliveryPartItoCancel::STATUS_FAILED) {
            return '<span class="badge badge-danger">gagal batalkan</span>';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, DeliveryPartItoCancel>  $cancelByDocEntry
     */
    private function rowEligibleForCancel(array $row, array $cancelByDocEntry): bool
    {
        if (($row['source'] ?? null) !== DeliveryPartEntry::SOURCE_SAP) {
            return false;
        }

        if (filled($row['no_iti'] ?? null)) {
            return false;
        }

        $docEntry = $row['doc_entry'] ?? null;
        if ($docEntry === null) {
            return false;
        }

        $cancel = $cancelByDocEntry[(int) $docEntry] ?? null;
        if ($cancel !== null) {
            if ($cancel->status === DeliveryPartItoCancel::STATUS_CANCELLED && $cancel->verified_at !== null) {
                return false;
            }

            if ($cancel->status === DeliveryPartItoCancel::STATUS_REQUESTED) {
                return false;
            }
        }

        return true;
    }
}
