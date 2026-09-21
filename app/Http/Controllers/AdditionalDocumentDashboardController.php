<?php

namespace App\Http\Controllers;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdditionalDocumentDashboardController extends Controller
{
    /** @var array<string, mixed>|null */
    private ?array $documentAgingMetricsCache = null;

    /** @var array{is_admin: mixed, location: string|null}|null */
    private ?array $documentAgingMetricsScope = null;

    public function index()
    {
        try {
            $user = Auth::user();

            // Load relationships with null safety
            $user->loadMissing(['roles', 'department']);

            $userDepartment = $user->department;
            $userLocationCode = $user->department_location_code;
            $userRoles = $user->roles ?? collect();
            $isAdmin = array_intersect($userRoles->pluck('name')->toArray(), ['admin', 'superadmin']);

            // Get document status overview
            $statusOverview = $this->getDocumentStatusOverview($user, $userLocationCode, $isAdmin);

            // Get document type analysis
            $typeAnalysis = $this->getDocumentTypeAnalysis($user, $userLocationCode, $isAdmin);

            // Get age and status metrics with department-specific aging
            $ageAndStatus = $this->getAgeAndStatusMetrics($user, $userLocationCode, $isAdmin);

            // Get department-specific aging alerts
            $departmentAlerts = $this->getDepartmentSpecificAgingAlerts($user, $userLocationCode, $isAdmin);

            // Get location and movement analysis
            $locationAnalysis = $this->getLocationAnalysis($user, $userLocationCode, $isAdmin);

            // Get PO number analysis
            $poAnalysis = $this->getPONumberAnalysis($user, $userLocationCode, $isAdmin);

            // Get document workflow metrics
            $workflowMetrics = $this->getWorkflowMetrics($user, $userLocationCode, $isAdmin);

            return view('additional_documents.dashboard', compact(
                'statusOverview',
                'typeAnalysis',
                'ageAndStatus',
                'departmentAlerts',
                'locationAnalysis',
                'poAnalysis',
                'workflowMetrics'
            ));
        } catch (\Exception $e) {
            Log::error('AdditionalDocumentDashboardController@index error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            // Return a safe fallback view or redirect
            return redirect()->route('additional-documents.index')->with('error', 'An error occurred while loading the dashboard. Please try again.');
        }
    }

    private function getDocumentStatusOverview($user, $userLocationCode, $isAdmin)
    {
        $query = AdditionalDocument::query();

        if (! $isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        $statuses = ['available', 'in_transit', 'distributed', 'unaccounted_for'];
        $counts = (clone $query)
            ->selectRaw('distribution_status, COUNT(*) as aggregate_count')
            ->groupBy('distribution_status')
            ->pluck('aggregate_count', 'distribution_status');

        $overview = [];
        foreach ($statuses as $status) {
            $overview[$status] = (int) ($counts[$status] ?? 0);
        }

        return $overview;
    }

    private function getDocumentTypeAnalysis($user, $userLocationCode, $isAdmin)
    {
        $query = AdditionalDocument::query();

        if (! $isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        $types = AdditionalDocumentType::all();
        $typeCounts = (clone $query)
            ->selectRaw('type_id, COUNT(*) as aggregate_count')
            ->groupBy('type_id')
            ->pluck('aggregate_count', 'type_id');

        $analysis = [];
        foreach ($types as $type) {
            $analysis[$type->name] = (int) ($typeCounts[$type->id] ?? 0);
        }

        $sourceRow = (clone $query)->selectRaw('
            SUM(CASE WHEN ito_creator IS NOT NULL THEN 1 ELSE 0 END) as ito_documents,
            SUM(CASE WHEN po_no IS NOT NULL THEN 1 ELSE 0 END) as po_documents,
            SUM(CASE WHEN grpo_no IS NOT NULL THEN 1 ELSE 0 END) as grpo_documents,
            SUM(CASE WHEN ito_creator IS NULL AND po_no IS NULL AND grpo_no IS NULL THEN 1 ELSE 0 END) as other_documents
        ')->first();

        $sourceAnalysis = [
            'ito_documents' => (int) ($sourceRow->ito_documents ?? 0),
            'po_documents' => (int) ($sourceRow->po_documents ?? 0),
            'grpo_documents' => (int) ($sourceRow->grpo_documents ?? 0),
            'other_documents' => (int) ($sourceRow->other_documents ?? 0),
        ];

        return [
            'type_breakdown' => $analysis,
            'source_breakdown' => $sourceAnalysis,
        ];
    }

    private function getAgeAndStatusMetrics($user, $userLocationCode, $isAdmin)
    {
        try {
            $metrics = $this->resolveDocumentAgingMetrics($userLocationCode, $isAdmin);

            return [
                'age_breakdown' => $metrics['age_breakdown'],
                'status_by_age' => $metrics['status_by_age'],
            ];
        } catch (\Exception $e) {
            Log::error('Error in getAgeAndStatusMetrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->emptyAgeAndStatusMetrics();
        }
    }

    /**
     * Get department-specific aging alerts
     */
    private function getDepartmentSpecificAgingAlerts($user, $userLocationCode, $isAdmin)
    {
        try {
            $metrics = $this->resolveDocumentAgingMetrics($userLocationCode, $isAdmin);

            return $metrics['alerts'];
        } catch (\Exception $e) {
            Log::error('Error in getDepartmentSpecificAgingAlerts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->emptyDepartmentAgingAlerts();
        }
    }

    /**
     * @return array{age_breakdown: array<string, int>, status_by_age: array<string, array<string, int>>, alerts: array<string, int>}
     */
    private function resolveDocumentAgingMetrics($userLocationCode, $isAdmin): array
    {
        $scope = [
            'is_admin' => $isAdmin,
            'location' => $userLocationCode,
        ];

        if ($this->documentAgingMetricsCache !== null && $this->documentAgingMetricsScope === $scope) {
            return $this->documentAgingMetricsCache;
        }

        $query = DB::table('additional_documents');

        if (! $isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        $rows = $query->get(['id', 'distribution_status', 'receive_date', 'created_at']);

        [$documentsWithDistributionPivot, $maxVerifiedReceivedAtByDocumentId] = AdditionalDocument::bulkLocationArrivalMaps();

        $ageBreakdown = [
            '0-7_days' => 0,
            '8-14_days' => 0,
            '15-30_days' => 0,
            '30_plus_days' => 0,
        ];

        $statusByAge = [
            '0-7_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
            '8-14_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
            '15-30_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
            '30_plus_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
        ];

        $alerts = [
            'overdue_critical' => 0,
            'overdue_warning' => 0,
            'stuck_documents' => 0,
            'recently_arrived' => 0,
        ];

        $now = now();

        foreach ($rows as $row) {
            try {
                $status = $row->distribution_status ?? 'available';
                $daysInCurrentLocation = $this->daysInCurrentLocationForRow(
                    $row,
                    $documentsWithDistributionPivot,
                    $maxVerifiedReceivedAtByDocumentId,
                    $now,
                );
                $ageCategory = $this->ageCategoryForDays($daysInCurrentLocation);

                if (! isset($ageBreakdown[$ageCategory])) {
                    $ageCategory = '0-7_days';
                }

                $ageBreakdown[$ageCategory]++;

                if (isset($statusByAge[$ageCategory][$status])) {
                    $statusByAge[$ageCategory][$status]++;
                }

                if ($daysInCurrentLocation > 30 && in_array($status, ['available', 'in_transit'], true)) {
                    $alerts['overdue_critical']++;
                } elseif ($daysInCurrentLocation > 14 && $daysInCurrentLocation <= 30 && in_array($status, ['available', 'in_transit'], true)) {
                    $alerts['overdue_warning']++;
                } elseif ($daysInCurrentLocation > 7 && $status === 'available') {
                    $alerts['stuck_documents']++;
                } elseif ($daysInCurrentLocation <= 3) {
                    $alerts['recently_arrived']++;
                }
            } catch (\Exception $e) {
                Log::warning('Error calculating age category for document', [
                    'document_id' => $row->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                $ageBreakdown['0-7_days']++;
            }
        }

        $this->documentAgingMetricsScope = $scope;
        $this->documentAgingMetricsCache = [
            'age_breakdown' => $ageBreakdown,
            'status_by_age' => $statusByAge,
            'alerts' => $alerts,
        ];

        return $this->documentAgingMetricsCache;
    }

    /**
     * @param  array<int, true>  $documentsWithDistributionPivot
     * @param  array<int, string>  $maxVerifiedReceivedAtByDocumentId
     */
    private function daysInCurrentLocationForRow(
        object $row,
        array $documentsWithDistributionPivot,
        array $maxVerifiedReceivedAtByDocumentId,
        Carbon $now,
    ): float|int {
        $documentId = (int) $row->id;
        $distributionStatus = $row->distribution_status ?? 'available';

        if ($distributionStatus === 'available' && ! isset($documentsWithDistributionPivot[$documentId])) {
            $arrivalDate = $row->receive_date ?? $row->created_at;
        } elseif (isset($maxVerifiedReceivedAtByDocumentId[$documentId])) {
            $arrivalDate = $maxVerifiedReceivedAtByDocumentId[$documentId];
        } else {
            $arrivalDate = $row->receive_date ?? $row->created_at;
        }

        if ($arrivalDate === null) {
            return 0;
        }

        return Carbon::parse($arrivalDate)->diffInDays($now);
    }

    private function ageCategoryForDays(float|int $days): string
    {
        if ($days <= 7) {
            return '0-7_days';
        }

        if ($days <= 14) {
            return '8-14_days';
        }

        if ($days <= 30) {
            return '15-30_days';
        }

        return '30_plus_days';
    }

    /**
     * @return array{age_breakdown: array<string, int>, status_by_age: array<string, array<string, int>>}
     */
    private function emptyAgeAndStatusMetrics(): array
    {
        return [
            'age_breakdown' => [
                '0-7_days' => 0,
                '8-14_days' => 0,
                '15-30_days' => 0,
                '30_plus_days' => 0,
            ],
            'status_by_age' => [
                '0-7_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
                '8-14_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
                '15-30_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
                '30_plus_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyDepartmentAgingAlerts(): array
    {
        return [
            'overdue_critical' => 0,
            'overdue_warning' => 0,
            'stuck_documents' => 0,
            'recently_arrived' => 0,
        ];
    }

    private function getLocationAnalysis($user, $userLocationCode, $isAdmin)
    {
        $query = AdditionalDocument::query();

        if (! $isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        // Get documents by current location
        $locationBreakdown = (clone $query)
            ->selectRaw('cur_loc, COUNT(*) as count')
            ->groupBy('cur_loc')
            ->get()
            ->pluck('count', 'cur_loc')
            ->toArray();

        // Get documents by origin warehouse
        $originBreakdown = (clone $query)
            ->selectRaw('origin_wh, COUNT(*) as count')
            ->whereNotNull('origin_wh')
            ->groupBy('origin_wh')
            ->get()
            ->pluck('count', 'origin_wh')
            ->toArray();

        // Get documents by destination
        $destinationBreakdown = (clone $query)
            ->selectRaw('destination_wh, COUNT(*) as count')
            ->whereNotNull('destination_wh')
            ->groupBy('destination_wh')
            ->get()
            ->pluck('count', 'destination_wh')
            ->toArray();

        return [
            'current_location' => $locationBreakdown,
            'origin_warehouse' => $originBreakdown,
            'destination' => $destinationBreakdown,
        ];
    }

    private function getPONumberAnalysis($user, $userLocationCode, $isAdmin)
    {
        $query = AdditionalDocument::query();

        if (! $isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        // Get documents with PO numbers
        $poDocuments = (clone $query)->whereNotNull('po_no')->get(['po_no']);

        $poAnalysis = [
            'total_with_po' => $poDocuments->count(),
            'unique_po_count' => $poDocuments->pluck('po_no')->unique()->count(),
            'po_distribution' => $poDocuments->groupBy('po_no')->map->count()->sortDesc()->take(10),
        ];

        // Get documents linked to invoices
        try {
            $linkedDocuments = (clone $query)->whereHas('invoices')->count();
            $unlinkedDocuments = (clone $query)->whereDoesntHave('invoices')->count();
        } catch (\Exception $e) {
            Log::warning('Error counting linked/unlinked documents', [
                'error' => $e->getMessage(),
            ]);
            $linkedDocuments = 0;
            $unlinkedDocuments = 0;
        }

        $poAnalysis['linked_to_invoices'] = $linkedDocuments;
        $poAnalysis['unlinked_documents'] = $unlinkedDocuments;
        $poAnalysis['linkage_rate'] = ($poDocuments->count() > 0) ? round(($linkedDocuments / $poDocuments->count()) * 100, 2) : 0;

        return $poAnalysis;
    }

    private function getWorkflowMetrics($user, $userLocationCode, $isAdmin)
    {
        $query = AdditionalDocument::query();

        if (! $isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        // Get workflow efficiency metrics
        $totalDocuments = (clone $query)->count();
        $distributedDocuments = (clone $query)->where('distribution_status', 'distributed')->count();
        $inTransitDocuments = (clone $query)->where('distribution_status', 'in_transit')->count();
        $unaccountedDocuments = (clone $query)->where('distribution_status', 'unaccounted_for')->count();

        // Calculate distribution efficiency
        $distributionEfficiency = $totalDocuments > 0 ? round(($distributedDocuments / $totalDocuments) * 100, 2) : 0;

        $monthlyBreakdown = $this->buildMonthlyDocumentBreakdown($query);

        return [
            'total_documents' => $totalDocuments,
            'distributed_documents' => $distributedDocuments,
            'in_transit_documents' => $inTransitDocuments,
            'unaccounted_documents' => $unaccountedDocuments,
            'distribution_efficiency' => $distributionEfficiency,
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<AdditionalDocument>  $query
     * @return array<string, int>
     */
    private function buildMonthlyDocumentBreakdown($query): array
    {
        $rangeStart = Carbon::now()->subMonths(5)->startOfMonth();
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = (clone $query)
                ->where('created_at', '>=', $rangeStart)
                ->selectRaw("cast(strftime('%Y', created_at) as integer) as year, cast(strftime('%m', created_at) as integer) as month, COUNT(*) as aggregate_count")
                ->groupBy(DB::raw("strftime('%Y', created_at), strftime('%m', created_at)"))
                ->get();
        } else {
            $rows = (clone $query)
                ->where('created_at', '>=', $rangeStart)
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as aggregate_count')
                ->groupBy(DB::raw('YEAR(created_at), MONTH(created_at)'))
                ->get();
        }

        $countsByMonth = [];
        foreach ($rows as $row) {
            $key = Carbon::createFromDate((int) $row->year, (int) $row->month, 1)->format('M Y');
            $countsByMonth[$key] = (int) $row->aggregate_count;
        }

        $monthlyBreakdown = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyBreakdown[$month->format('M Y')] = $countsByMonth[$month->format('M Y')] ?? 0;
        }

        return $monthlyBreakdown;
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'available' => 'success',
            'in_transit' => 'warning',
            'distributed' => 'info',
            'unaccounted_for' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusIcon($status)
    {
        return match ($status) {
            'available' => 'check',
            'in_transit' => 'truck',
            'distributed' => 'download',
            'unaccounted_for' => 'exclamation-triangle',
            default => 'file'
        };
    }

    public function getAgeColor($age)
    {
        return match ($age) {
            '0-7_days' => 'success',
            '8-14_days' => 'warning',
            '15-30_days' => 'info',
            '30_plus_days' => 'danger',
            default => 'secondary'
        };
    }
}
