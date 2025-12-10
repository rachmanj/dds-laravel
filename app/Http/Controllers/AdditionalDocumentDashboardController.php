<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use Carbon\Carbon;

class AdditionalDocumentDashboardController extends Controller
{
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
            Log::error('AdditionalDocumentDashboardController@index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            
            // Return a safe fallback view or redirect
            return redirect()->route('additional-documents.index')->with('error', 'An error occurred while loading the dashboard. Please try again.');
        }
    }

    private function getDocumentStatusOverview($user, $userLocationCode, $isAdmin)
    {
        $query = AdditionalDocument::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        $statuses = ['available', 'in_transit', 'distributed', 'unaccounted_for'];
        $overview = [];

        foreach ($statuses as $status) {
            $count = (clone $query)->where('distribution_status', $status)->count();
            $overview[$status] = $count;
        }

        return $overview;
    }

    private function getDocumentTypeAnalysis($user, $userLocationCode, $isAdmin)
    {
        $query = AdditionalDocument::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        $types = AdditionalDocumentType::all();
        $analysis = [];

        foreach ($types as $type) {
            $count = (clone $query)->where('type_id', $type->id)->count();
            $analysis[$type->name] = $count;
        }

        // Get documents by source (ITO, PO, etc.)
        $sourceAnalysis = [
            'ito_documents' => (clone $query)->whereNotNull('ito_creator')->count(),
            'po_documents' => (clone $query)->whereNotNull('po_no')->count(),
            'grpo_documents' => (clone $query)->whereNotNull('grpo_no')->count(),
            'other_documents' => (clone $query)->whereNull('ito_creator')->whereNull('po_no')->whereNull('grpo_no')->count()
        ];

        return [
            'type_breakdown' => $analysis,
            'source_breakdown' => $sourceAnalysis
        ];
    }

    private function getAgeAndStatusMetrics($user, $userLocationCode, $isAdmin)
    {
        try {
            $query = AdditionalDocument::query();

            if (!$isAdmin && $userLocationCode) {
                $query->where('cur_loc', $userLocationCode);
            }

            $documents = (clone $query)->get();

            $ageBreakdown = [
                '0-7_days' => 0,
                '8-14_days' => 0,
                '15-30_days' => 0,
                '30_plus_days' => 0
            ];

            $statusByAge = [
                '0-7_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
                '8-14_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
                '15-30_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
                '30_plus_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0]
            ];

            foreach ($documents as $document) {
                try {
                    // Use the new department-specific aging calculation
                    $ageCategory = $document->current_location_age_category;
                    $status = $document->distribution_status ?? 'available';

                    // Validate age category exists in breakdown
                    if (!isset($ageBreakdown[$ageCategory])) {
                        $ageCategory = '0-7_days'; // Safe fallback
                    }

                    $ageBreakdown[$ageCategory]++;
                    
                    // Validate status exists in statusByAge
                    if (isset($statusByAge[$ageCategory][$status])) {
                        $statusByAge[$ageCategory][$status]++;
                    }
                } catch (\Exception $e) {
                    Log::warning('Error calculating age category for document', [
                        'document_id' => $document->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    // Continue processing other documents
                    $ageBreakdown['0-7_days']++; // Safe fallback
                }
            }

            return [
                'age_breakdown' => $ageBreakdown,
                'status_by_age' => $statusByAge
            ];
        } catch (\Exception $e) {
            Log::error('Error in getAgeAndStatusMetrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return safe defaults
            return [
                'age_breakdown' => [
                    '0-7_days' => 0,
                    '8-14_days' => 0,
                    '15-30_days' => 0,
                    '30_plus_days' => 0
                ],
                'status_by_age' => [
                    '0-7_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
                    '8-14_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
                    '15-30_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
                    '30_plus_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0]
                ]
            ];
        }
    }

    /**
     * Get department-specific aging alerts
     */
    private function getDepartmentSpecificAgingAlerts($user, $userLocationCode, $isAdmin)
    {
        try {
            $query = AdditionalDocument::query();

            if (!$isAdmin && $userLocationCode) {
                $query->where('cur_loc', $userLocationCode);
            }

            $documents = (clone $query)->get();

            $alerts = [
                'overdue_critical' => 0,
                'overdue_warning' => 0,
                'stuck_documents' => 0,
                'recently_arrived' => 0
            ];

            foreach ($documents as $document) {
                try {
                    $daysInCurrentLocation = $document->days_in_current_location;
                    $status = $document->distribution_status ?? 'available';

                    if ($daysInCurrentLocation > 30 && in_array($status, ['available', 'in_transit'])) {
                        $alerts['overdue_critical']++;
                    } elseif ($daysInCurrentLocation > 14 && $daysInCurrentLocation <= 30 && in_array($status, ['available', 'in_transit'])) {
                        $alerts['overdue_warning']++;
                    } elseif ($daysInCurrentLocation > 7 && $status === 'available') {
                        $alerts['stuck_documents']++;
                    } elseif ($daysInCurrentLocation <= 3) {
                        $alerts['recently_arrived']++;
                    }
                } catch (\Exception $e) {
                    Log::warning('Error calculating alerts for document', [
                        'document_id' => $document->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    // Continue processing other documents
                }
            }

            return $alerts;
        } catch (\Exception $e) {
            Log::error('Error in getDepartmentSpecificAgingAlerts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return safe defaults
            return [
                'overdue_critical' => 0,
                'overdue_warning' => 0,
                'stuck_documents' => 0,
                'recently_arrived' => 0
            ];
        }
    }

    private function getLocationAnalysis($user, $userLocationCode, $isAdmin)
    {
        $query = AdditionalDocument::query();

        if (!$isAdmin && $userLocationCode) {
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
            'destination' => $destinationBreakdown
        ];
    }

    private function getPONumberAnalysis($user, $userLocationCode, $isAdmin)
    {
        $query = AdditionalDocument::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        // Get documents with PO numbers
        $poDocuments = (clone $query)->whereNotNull('po_no')->get();

        $poAnalysis = [
            'total_with_po' => $poDocuments->count(),
            'unique_po_count' => $poDocuments->pluck('po_no')->unique()->count(),
            'po_distribution' => $poDocuments->groupBy('po_no')->map->count()->sortDesc()->take(10)
        ];

        // Get documents linked to invoices
        try {
            $linkedDocuments = (clone $query)->whereHas('invoices')->count();
            $unlinkedDocuments = (clone $query)->whereDoesntHave('invoices')->count();
        } catch (\Exception $e) {
            Log::warning('Error counting linked/unlinked documents', [
                'error' => $e->getMessage()
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

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        // Get workflow efficiency metrics
        $totalDocuments = (clone $query)->count();
        $distributedDocuments = (clone $query)->where('distribution_status', 'distributed')->count();
        $inTransitDocuments = (clone $query)->where('distribution_status', 'in_transit')->count();
        $unaccountedDocuments = (clone $query)->where('distribution_status', 'unaccounted_for')->count();

        // Calculate distribution efficiency
        $distributionEfficiency = $totalDocuments > 0 ? round(($distributedDocuments / $totalDocuments) * 100, 2) : 0;

        // Get documents by month (last 6 months)
        $monthlyBreakdown = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $count = (clone $query)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $monthlyBreakdown[$month->format('M Y')] = $count;
        }

        return [
            'total_documents' => $totalDocuments,
            'distributed_documents' => $distributedDocuments,
            'in_transit_documents' => $inTransitDocuments,
            'unaccounted_documents' => $unaccountedDocuments,
            'distribution_efficiency' => $distributionEfficiency,
            'monthly_breakdown' => $monthlyBreakdown
        ];
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
