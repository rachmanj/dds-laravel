<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Distribution;
use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\DistributionHistory;
use App\Models\Department;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Load relationships with null safety
            $user->loadMissing(['roles', 'department']);
            
            $userDepartment = $user->department;
            $userLocationCode = $user->department_location_code;

            // Get workflow metrics
            $metrics = $this->getWorkflowMetrics($user, $userLocationCode);

            // Get department-specific document age breakdown
            $documentAgeBreakdown = $this->getDocumentAgeBreakdown($user, $userLocationCode);

            // Get pending distributions
            $pendingDistributions = $this->getPendingDistributions($user, $userLocationCode);

            // Get recent activity
            $recentActivity = $this->getRecentActivity($user, $userLocationCode);

            // Get SAP document metrics by department
            $sapDocumentMetrics = $this->getSapDocumentMetrics($user, $userLocationCode);

            // Get department-specific aging alerts
            $departmentAgingAlerts = $this->getDepartmentSpecificAgingAlerts($user, $userLocationCode);

            return view('dashboard', compact(
                'metrics',
                'documentAgeBreakdown',
                'pendingDistributions',
                'recentActivity',
                'sapDocumentMetrics',
                'departmentAgingAlerts'
            ))->with([
                'getActivityIcon' => fn($action) => $this->getActivityIcon($action),
                'getActivityColor' => fn($action) => $this->getActivityColor($action)
            ]);
        } catch (\Exception $e) {
            \Log::error('DashboardController@index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            
            // Return a safe fallback view or redirect
            return redirect()->route('login')->with('error', 'An error occurred while loading the dashboard. Please try again.');
        }
    }

    private function getWorkflowMetrics($user, $userLocationCode)
    {
        $userRoles = $user->roles ?? collect();
        $isAdmin = array_intersect($userRoles->pluck('name')->toArray(), ['admin', 'superadmin']);

        // Pending distributions (sent but not received)
        $pendingDistributionsQuery = Distribution::where('status', 'sent');
        if (!$isAdmin && $userLocationCode) {
            $pendingDistributionsQuery->where('destination_department_id', $user->department_id);
        }
        $pendingDistributionsCount = $pendingDistributionsQuery->count();

        // In-transit documents
        $inTransitQuery = Invoice::where('distribution_status', 'in_transit');
        $inTransitQuery2 = AdditionalDocument::where('distribution_status', 'in_transit');

        if (!$isAdmin && $userLocationCode) {
            $inTransitQuery->where('cur_loc', $userLocationCode);
            $inTransitQuery2->where('cur_loc', $userLocationCode);
        }

        $inTransitCount = $inTransitQuery->count() + $inTransitQuery2->count();

        // Overdue documents (>14 days in current department) - using department-specific aging
        $overdueInvoicesQuery = Invoice::query();
        $overdueAdditionalQuery = AdditionalDocument::query();

        if (!$isAdmin && $userLocationCode) {
            $overdueInvoicesQuery->where('cur_loc', $userLocationCode);
            $overdueAdditionalQuery->where('cur_loc', $userLocationCode);
        }

        // Get all documents and filter by department-specific aging
        // Use try-catch to handle potential accessor errors
        $overdueCount = 0;

        try {
            $allInvoices = $overdueInvoicesQuery->get();
            $allAdditionalDocs = $overdueAdditionalQuery->get();

            // Count invoices with >14 days in current department
            foreach ($allInvoices as $invoice) {
                try {
                    if ($invoice->days_in_current_location > 14) {
                        $overdueCount++;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error calculating days_in_current_location for invoice', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue processing other invoices
                }
            }

            // Count additional documents with >14 days in current department
            foreach ($allAdditionalDocs as $doc) {
                try {
                    if ($doc->days_in_current_location > 14) {
                        $overdueCount++;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error calculating days_in_current_location for document', [
                        'document_id' => $doc->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue processing other documents
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error in getWorkflowMetrics overdue calculation', [
                'error' => $e->getMessage()
            ]);
            // Return safe default
        }

        // Unaccounted documents
        $unaccountedInvoicesQuery = Invoice::where('distribution_status', 'unaccounted_for');
        $unaccountedAdditionalQuery = AdditionalDocument::where('distribution_status', 'unaccounted_for');

        if (!$isAdmin && $userLocationCode) {
            $unaccountedInvoicesQuery->where('cur_loc', $userLocationCode);
            $unaccountedAdditionalQuery->where('cur_loc', $userLocationCode);
        }

        $unaccountedCount = $unaccountedInvoicesQuery->count() + $unaccountedAdditionalQuery->count();

        return [
            'pending_distributions' => $pendingDistributionsCount,
            'in_transit_documents' => $inTransitCount,
            'overdue_documents' => $overdueCount,
            'unaccounted_documents' => $unaccountedCount,
        ];
    }

    private function getDocumentAgeBreakdown($user, $userLocationCode)
    {
        $userRoles = $user->roles ?? collect();
        $isAdmin = array_intersect($userRoles->pluck('name')->toArray(), ['admin', 'superadmin']);

        // Get documents in user's department - include all statuses for comprehensive view
        $invoicesQuery = Invoice::query();
        $additionalQuery = AdditionalDocument::query();

        if (!$isAdmin && $userLocationCode) {
            $invoicesQuery->where('cur_loc', $userLocationCode);
            $additionalQuery->where('cur_loc', $userLocationCode);
        }

        // Get all documents (not just distributed) for comprehensive aging analysis
        // Use try-catch to handle potential accessor errors
        $invoiceAgeBreakdown = ['0_7_days' => 0, '8_14_days' => 0, '15_plus_days' => 0];
        $additionalAgeBreakdown = ['0_7_days' => 0, '8_14_days' => 0, '15_plus_days' => 0];
        $invoiceCount = 0;
        $additionalDocCount = 0;

        try {
            $invoices = $invoicesQuery->get();
            $additionalDocs = $additionalQuery->get();
            $invoiceCount = $invoices->count();
            $additionalDocCount = $additionalDocs->count();

            // Use department-specific aging calculations
            $invoiceAgeBreakdown = $this->categorizeDocumentsByDepartmentSpecificAge($invoices);
            $additionalAgeBreakdown = $this->categorizeDocumentsByDepartmentSpecificAge($additionalDocs);
        } catch (\Exception $e) {
            \Log::error('Error in getDocumentAgeBreakdown', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Use safe defaults already set above
        }

        // Combine both document types
        return [
            '0_7_days' => $invoiceAgeBreakdown['0_7_days'] + $additionalAgeBreakdown['0_7_days'],
            '8_14_days' => $invoiceAgeBreakdown['8_14_days'] + $additionalAgeBreakdown['8_14_days'],
            '15_plus_days' => $invoiceAgeBreakdown['15_plus_days'] + $additionalAgeBreakdown['15_plus_days'],
            'total_documents' => $invoiceCount + $additionalDocCount,
            'invoices_count' => $invoiceCount,
            'additional_docs_count' => $additionalDocCount,
        ];
    }

    /**
     * NEW: Categorize documents using department-specific aging calculations
     * Uses our new accessors: days_in_current_location and current_location_age_category
     */
    private function categorizeDocumentsByDepartmentSpecificAge($documents)
    {
        $breakdown = [
            '0_7_days' => 0,
            '8_14_days' => 0,
            '15_plus_days' => 0,
        ];

        foreach ($documents as $document) {
            try {
                // Use our department-specific aging accessor
                $ageCategory = $document->current_location_age_category;

                // Map the age category to our breakdown keys
                switch ($ageCategory) {
                    case '0-7_days':
                        $breakdown['0_7_days']++;
                        break;
                    case '8-14_days':
                        $breakdown['8_14_days']++;
                        break;
                    case '15-30_days':
                    case '30_plus_days':
                        $breakdown['15_plus_days']++;
                        break;
                    default:
                        // Fallback for any unexpected categories
                        $breakdown['0_7_days']++;
                        break;
                }
            } catch (\Exception $e) {
                \Log::warning('Error calculating age category for document', [
                    'document_id' => $document->id ?? 'unknown',
                    'document_type' => get_class($document),
                    'error' => $e->getMessage()
                ]);
                // Continue processing other documents
                $breakdown['0_7_days']++; // Safe fallback
            }
        }

        return $breakdown;
    }

    /**
     * NEW: Get department-specific aging alerts for Dashboard 1
     * Similar to AdditionalDocumentDashboardController but tailored for Dashboard 1
     */
    private function getDepartmentSpecificAgingAlerts($user, $userLocationCode)
    {
        $userRoles = $user->roles ?? collect();
        $isAdmin = array_intersect($userRoles->pluck('name')->toArray(), ['admin', 'superadmin']);

        // Get all documents in user's department
        $invoicesQuery = Invoice::query();
        $additionalQuery = AdditionalDocument::query();

        if (!$isAdmin && $userLocationCode) {
            $invoicesQuery->where('cur_loc', $userLocationCode);
            $additionalQuery->where('cur_loc', $userLocationCode);
        }

        $alerts = [
            'overdue_critical' => 0,    // >30 days
            'overdue_warning' => 0,    // 15-30 days
            'stuck_documents' => 0,    // 7+ days in available status
            'recently_arrived' => 0,   // ≤3 days
            'total_documents' => 0,
            'departments_affected' => []
        ];

        try {
            $invoices = $invoicesQuery->get();
            $additionalDocs = $additionalQuery->get();
            $allDocuments = $invoices->concat($additionalDocs);
            $alerts['total_documents'] = $allDocuments->count();

            foreach ($allDocuments as $document) {
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
                    \Log::warning('Error calculating alerts for document', [
                        'document_id' => $document->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    // Continue processing other documents
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error in getDepartmentSpecificAgingAlerts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Return safe defaults already set above
        }

        // Get unique departments affected
        $departments = $allDocuments->pluck('cur_loc')->unique()->filter()->values()->toArray();
        $alerts['departments_affected'] = $departments;

        return $alerts;
    }

    private function getPendingDistributions($user, $userLocationCode)
    {
        try {
            $userRoles = $user->roles ?? collect();
            $isAdmin = array_intersect($userRoles->pluck('name')->toArray(), ['admin', 'superadmin']);

            $query = Distribution::with(['originDepartment', 'destinationDepartment', 'type'])
                ->where('status', 'sent')
                ->orderBy('sent_at', 'desc')
                ->limit(5);

            if (!$isAdmin && $userLocationCode && $user->department_id) {
                $query->where('destination_department_id', $user->department_id);
            }

            return $query->get();
        } catch (\Exception $e) {
            \Log::error('Error in getPendingDistributions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Return empty collection as safe fallback
            return collect();
        }
    }

    private function getRecentActivity($user, $userLocationCode)
    {
        try {
            $userRoles = $user->roles ?? collect();
            $isAdmin = array_intersect($userRoles->pluck('name')->toArray(), ['admin', 'superadmin']);

            $query = DistributionHistory::with(['distribution', 'user'])
                ->orderBy('action_performed_at', 'desc')
                ->limit(10);

            if (!$isAdmin && $userLocationCode && $user->department_id) {
                $query->whereHas('distribution', function ($q) use ($user) {
                    $q->where('origin_department_id', $user->department_id)
                        ->orWhere('destination_department_id', $user->department_id);
                });
            }

            return $query->get();
        } catch (\Exception $e) {
            \Log::error('Error in getRecentActivity', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Return empty collection as safe fallback
            return collect();
        }
    }

    private function getSapDocumentMetrics($user, $userLocationCode)
    {
        $userRoles = $user->roles ?? collect();
        $isAdmin = array_intersect($userRoles->pluck('name')->toArray(), ['admin', 'superadmin']);

        // Get all departments that have invoices
        $departmentsQuery = Department::whereHas('invoices');

        if (!$isAdmin && $userLocationCode) {
            // For non-admin users, only show their department
            $departmentsQuery->where('location_code', $userLocationCode);
        }

        $departments = $departmentsQuery->get();

        $sapMetrics = [];

        foreach ($departments as $department) {
            // Get invoices for this department
            $invoicesQuery = Invoice::where('cur_loc', $department->location_code);

            $totalInvoices = $invoicesQuery->count();
            $invoicesWithoutSap = $invoicesQuery->clone()->whereNull('sap_doc')->count();
            $invoicesWithSap = $invoicesQuery->clone()->whereNotNull('sap_doc')->count();

            // Only include departments that have invoices
            if ($totalInvoices > 0) {
                $sapMetrics[] = [
                    'department_name' => $department->name,
                    'location_code' => $department->location_code,
                    'total_invoices' => $totalInvoices,
                    'without_sap_doc' => $invoicesWithoutSap,
                    'with_sap_doc' => $invoicesWithSap,
                    'completion_percentage' => $totalInvoices > 0 ? round(($invoicesWithSap / $totalInvoices) * 100, 1) : 0
                ];
            }
        }

        // Sort by completion percentage (ascending) to show departments needing attention first
        usort($sapMetrics, function ($a, $b) {
            return $a['completion_percentage'] <=> $b['completion_percentage'];
        });

        return $sapMetrics;
    }

    public function getActivityIcon($action)
    {
        return match ($action) {
            'created' => 'plus',
            'sent' => 'paper-plane',
            'received' => 'download',
            'verified_by_sender' => 'check',
            'verified_by_receiver' => 'check-double',
            'completed' => 'flag-checkered',
            'discrepancy_reported' => 'exclamation-triangle',
            default => 'info'
        };
    }

    public function getActivityColor($action)
    {
        return match ($action) {
            'created' => 'blue',
            'sent' => 'warning',
            'received' => 'info',
            'verified_by_sender' => 'success',
            'verified_by_receiver' => 'success',
            'completed' => 'success',
            'discrepancy_reported' => 'danger',
            default => 'gray'
        };
    }
}
