<?php

namespace App\Http\Controllers\Logistics;

use App\Exceptions\SapSqlQueryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseProjectMappingRequest;
use App\Http\Requests\UpdateWarehouseProjectMappingRequest;
use App\Models\LogisticsWarehouseProject;
use App\Models\Project;
use App\Services\Logistics\DeliveryPartQueryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseProjectMappingController extends Controller
{
    public function __construct(
        private DeliveryPartQueryService $queryService,
    ) {}

    public function index(Request $request): View
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $mappings = LogisticsWarehouseProject::query()
            ->with(['project', 'updatedBy'])
            ->orderBy('whs_code')
            ->get();

        $mappedWhsCodes = $mappings->pluck('whs_code')->all();

        $projects = Project::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $sapError = null;
        $unmappedWarehouses = [];

        try {
            $distinct = $this->queryService->distinctToWarehouses(
                Carbon::parse($fromDate),
                Carbon::parse($toDate),
            );

            $mappedSet = array_flip($mappedWhsCodes);

            foreach ($distinct as $row) {
                if (! isset($mappedSet[$row['whs_code']])) {
                    $unmappedWarehouses[] = $row;
                }
            }
        } catch (SapSqlQueryException $e) {
            $sapError = $e->getMessage();
        }

        return view('logistics.warehouse-projects.index', [
            'mappings' => $mappings,
            'projects' => $projects,
            'unmappedWarehouses' => $unmappedWarehouses,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'sapError' => $sapError,
        ]);
    }

    public function store(StoreWarehouseProjectMappingRequest $request): RedirectResponse
    {
        LogisticsWarehouseProject::query()->create([
            'whs_code' => $request->validated('whs_code'),
            'project_id' => $request->validated('project_id'),
            'is_active' => true,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('logistics.warehouse-projects.index')
            ->with('success', 'Mapping warehouse berhasil ditambahkan.');
    }

    public function update(
        UpdateWarehouseProjectMappingRequest $request,
        LogisticsWarehouseProject $mapping,
    ): RedirectResponse {
        $data = [
            'project_id' => $request->validated('project_id'),
            'updated_by' => $request->user()->id,
        ];

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $mapping->update($data);

        return redirect()
            ->route('logistics.warehouse-projects.index')
            ->with('success', "Mapping {$mapping->whs_code} berhasil diperbarui.");
    }

    public function toggle(Request $request, LogisticsWarehouseProject $mapping): RedirectResponse
    {
        abort_unless($request->user()?->can('manage-delivery-part-mapping'), 403);

        $mapping->update([
            'is_active' => ! $mapping->is_active,
            'updated_by' => $request->user()->id,
        ]);

        $status = $mapping->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('logistics.warehouse-projects.index')
            ->with('success', "Warehouse {$mapping->whs_code} berhasil {$status}.");
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(Request $request): array
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
}
