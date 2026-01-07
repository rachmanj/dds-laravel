<?php

namespace App\Services;

use App\Models\Distribution;
use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardMetricsService
{
    /**
     * Get workflow metrics with optimized database queries
     */
    public function getWorkflowMetrics($user, $userLocationCode)
    {
        $cacheKey = "dashboard.workflow_metrics.{$user->id}.{$userLocationCode}";
        
        return Cache::remember($cacheKey, 300, function() use ($user, $userLocationCode) {
            $isAdmin = $user->hasAnyRole(['admin', 'superadmin']);
            
            return [
                'pending_distributions' => $this->getPendingDistributionsCount($user, $userLocationCode, $isAdmin),
                'in_transit_documents' => $this->getInTransitCount($userLocationCode, $isAdmin),
                'overdue_documents' => $this->getOverdueDocumentsCount($userLocationCode, $isAdmin),
                'unaccounted_documents' => $this->getUnaccountedCount($userLocationCode, $isAdmin),
            ];
        });
    }

    /**
     * Get pending distributions count
     */
    private function getPendingDistributionsCount($user, $userLocationCode, $isAdmin)
    {
        $query = Distribution::where('status', 'sent');
        
        if (!$isAdmin && $userLocationCode && $user->department_id) {
            $query->where('destination_department_id', $user->department_id);
        }
        
        return $query->count();
    }

    /**
     * Get in-transit documents count (optimized with UNION)
     */
    private function getInTransitCount($userLocationCode, $isAdmin)
    {
        $invoiceQuery = Invoice::where('distribution_status', 'in_transit')
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->selectRaw("'invoice' as type, id");
        
        $additionalQuery = AdditionalDocument::where('distribution_status', 'in_transit')
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->selectRaw("'additional' as type, id");
        
        // Use UNION ALL for better performance
        return DB::table(DB::raw("({$invoiceQuery->toSql()} UNION ALL {$additionalQuery->toSql()}) as combined"))
            ->mergeBindings($invoiceQuery->getQuery())
            ->mergeBindings($additionalQuery->getQuery())
            ->count();
    }

    /**
     * Get overdue documents count (>14 days) using database-level calculation
     */
    private function getOverdueDocumentsCount($userLocationCode, $isAdmin)
    {
        $invoiceArrivalDateSubquery = $this->getArrivalDateSubquery('Invoice');
        $additionalArrivalDateSubquery = $this->getArrivalDateSubquery('AdditionalDocument');
        
        // Single query for invoices
        $invoiceOverdue = Invoice::selectRaw("COUNT(*) as count")
            ->whereRaw("DATEDIFF(NOW(), {$invoiceArrivalDateSubquery}) > 14")
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->value('count') ?? 0;
        
        // Single query for additional documents
        $additionalOverdue = AdditionalDocument::selectRaw("COUNT(*) as count")
            ->whereRaw("DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) > 14")
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->value('count') ?? 0;
        
        return $invoiceOverdue + $additionalOverdue;
    }

    /**
     * Get unaccounted documents count (optimized with UNION)
     */
    private function getUnaccountedCount($userLocationCode, $isAdmin)
    {
        $invoiceQuery = Invoice::where('distribution_status', 'unaccounted_for')
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->selectRaw("'invoice' as type, id");
        
        $additionalQuery = AdditionalDocument::where('distribution_status', 'unaccounted_for')
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->selectRaw("'additional' as type, id");
        
        return DB::table(DB::raw("({$invoiceQuery->toSql()} UNION ALL {$additionalQuery->toSql()}) as combined"))
            ->mergeBindings($invoiceQuery->getQuery())
            ->mergeBindings($additionalQuery->getQuery())
            ->count();
    }

    /**
     * Get document age breakdown using database-level calculations
     */
    public function getDocumentAgeBreakdown($user, $userLocationCode)
    {
        $cacheKey = "dashboard.age_breakdown.{$user->id}.{$userLocationCode}";
        
        return Cache::remember($cacheKey, 300, function() use ($user, $userLocationCode) {
            $isAdmin = $user->hasAnyRole(['admin', 'superadmin']);
            
            $invoiceArrivalDateSubquery = $this->getArrivalDateSubquery('Invoice');
            $additionalArrivalDateSubquery = $this->getArrivalDateSubquery('AdditionalDocument');
            
            // Single query for invoices with age categories
            $invoiceBreakdown = Invoice::selectRaw("
                SUM(CASE WHEN DATEDIFF(NOW(), {$invoiceArrivalDateSubquery}) <= 7 THEN 1 ELSE 0 END) as days_0_7,
                SUM(CASE WHEN DATEDIFF(NOW(), {$invoiceArrivalDateSubquery}) BETWEEN 8 AND 14 THEN 1 ELSE 0 END) as days_8_14,
                SUM(CASE WHEN DATEDIFF(NOW(), {$invoiceArrivalDateSubquery}) > 14 THEN 1 ELSE 0 END) as days_15_plus,
                COUNT(*) as total
            ")
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->first();
            
            // Single query for additional documents
            $additionalBreakdown = AdditionalDocument::selectRaw("
                SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) <= 7 THEN 1 ELSE 0 END) as days_0_7,
                SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) BETWEEN 8 AND 14 THEN 1 ELSE 0 END) as days_8_14,
                SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) > 14 THEN 1 ELSE 0 END) as days_15_plus,
                COUNT(*) as total
            ")
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->first();
            
            return [
                '0_7_days' => ($invoiceBreakdown->days_0_7 ?? 0) + ($additionalBreakdown->days_0_7 ?? 0),
                '8_14_days' => ($invoiceBreakdown->days_8_14 ?? 0) + ($additionalBreakdown->days_8_14 ?? 0),
                '15_plus_days' => ($invoiceBreakdown->days_15_plus ?? 0) + ($additionalBreakdown->days_15_plus ?? 0),
                'total_documents' => ($invoiceBreakdown->total ?? 0) + ($additionalBreakdown->total ?? 0),
                'invoices_count' => $invoiceBreakdown->total ?? 0,
                'additional_docs_count' => $additionalBreakdown->total ?? 0,
            ];
        });
    }

    /**
     * Get department-specific aging alerts using database-level calculations
     */
    public function getDepartmentSpecificAgingAlerts($user, $userLocationCode)
    {
        $cacheKey = "dashboard.aging_alerts.{$user->id}.{$userLocationCode}";
        
        return Cache::remember($cacheKey, 300, function() use ($user, $userLocationCode) {
            $isAdmin = $user->hasAnyRole(['admin', 'superadmin']);
            
            $invoiceArrivalDateSubquery = $this->getArrivalDateSubquery('Invoice');
            $additionalArrivalDateSubquery = $this->getArrivalDateSubquery('AdditionalDocument');
            
            // Single query for invoices with all alert categories
            $invoiceAlerts = Invoice::selectRaw("
                SUM(CASE WHEN DATEDIFF(NOW(), {$invoiceArrivalDateSubquery}) > 30 
                    AND distribution_status IN ('available', 'in_transit') THEN 1 ELSE 0 END) as overdue_critical,
                SUM(CASE WHEN DATEDIFF(NOW(), {$invoiceArrivalDateSubquery}) BETWEEN 15 AND 30 
                    AND distribution_status IN ('available', 'in_transit') THEN 1 ELSE 0 END) as overdue_warning,
                SUM(CASE WHEN DATEDIFF(NOW(), {$invoiceArrivalDateSubquery}) > 7 
                    AND distribution_status = 'available' THEN 1 ELSE 0 END) as stuck_documents,
                SUM(CASE WHEN DATEDIFF(NOW(), {$invoiceArrivalDateSubquery}) <= 3 THEN 1 ELSE 0 END) as recently_arrived,
                COUNT(*) as total
            ")
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->first();
            
            // Get unique locations for invoices
            $invoiceLocations = Invoice::select('cur_loc')
                ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                    $q->where('cur_loc', $userLocationCode);
                })
                ->distinct()
                ->pluck('cur_loc')
                ->filter()
                ->toArray();
            
            // Single query for additional documents
            $additionalAlerts = AdditionalDocument::selectRaw("
                SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) > 30 
                    AND distribution_status IN ('available', 'in_transit') THEN 1 ELSE 0 END) as overdue_critical,
                SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) BETWEEN 15 AND 30 
                    AND distribution_status IN ('available', 'in_transit') THEN 1 ELSE 0 END) as overdue_warning,
                SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) > 7 
                    AND distribution_status = 'available' THEN 1 ELSE 0 END) as stuck_documents,
                SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) <= 3 THEN 1 ELSE 0 END) as recently_arrived,
                COUNT(*) as total
            ")
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->first();
            
            // Get unique locations for additional documents
            $additionalLocations = AdditionalDocument::select('cur_loc')
                ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                    $q->where('cur_loc', $userLocationCode);
                })
                ->distinct()
                ->pluck('cur_loc')
                ->filter()
                ->toArray();
            
            // Combine locations
            $allLocations = array_values(array_unique(array_merge($invoiceLocations, $additionalLocations)));
            
            return [
                'overdue_critical' => ($invoiceAlerts->overdue_critical ?? 0) + ($additionalAlerts->overdue_critical ?? 0),
                'overdue_warning' => ($invoiceAlerts->overdue_warning ?? 0) + ($additionalAlerts->overdue_warning ?? 0),
                'stuck_documents' => ($invoiceAlerts->stuck_documents ?? 0) + ($additionalAlerts->stuck_documents ?? 0),
                'recently_arrived' => ($invoiceAlerts->recently_arrived ?? 0) + ($additionalAlerts->recently_arrived ?? 0),
                'total_documents' => ($invoiceAlerts->total ?? 0) + ($additionalAlerts->total ?? 0),
                'departments_affected' => array_values($allLocations),
            ];
        });
    }

    /**
     * Get SAP document metrics (optimized - single query instead of loop)
     */
    public function getSapDocumentMetrics($user, $userLocationCode)
    {
        $cacheKey = "dashboard.sap_metrics.{$user->id}.{$userLocationCode}";
        
        return Cache::remember($cacheKey, 300, function() use ($user, $userLocationCode) {
            $isAdmin = $user->hasAnyRole(['admin', 'superadmin']);
            
            $departmentsQuery = Department::whereHas('invoices')
                ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                    $q->where('location_code', $userLocationCode);
                });
            
            $locationCodes = $departmentsQuery->pluck('location_code');
            
            if ($locationCodes->isEmpty()) {
                return [];
            }
            
            // Single aggregated query for all departments
            $sapMetrics = Invoice::select('cur_loc')
                ->selectRaw('COUNT(*) as total_invoices')
                ->selectRaw('SUM(CASE WHEN sap_doc IS NULL THEN 1 ELSE 0 END) as without_sap_doc')
                ->selectRaw('SUM(CASE WHEN sap_doc IS NOT NULL THEN 1 ELSE 0 END) as with_sap_doc')
                ->whereIn('cur_loc', $locationCodes)
                ->groupBy('cur_loc')
                ->get()
                ->keyBy('cur_loc');
            
            // Join with department names
            $departments = $departmentsQuery->get()->keyBy('location_code');
            
            $result = $sapMetrics->map(function($metric) use ($departments) {
                $department = $departments->get($metric->cur_loc);
                
                return [
                    'department_name' => $department->name ?? 'Unknown',
                    'location_code' => $metric->cur_loc,
                    'total_invoices' => $metric->total_invoices,
                    'without_sap_doc' => $metric->without_sap_doc,
                    'with_sap_doc' => $metric->with_sap_doc,
                    'completion_percentage' => $metric->total_invoices > 0 
                        ? round(($metric->with_sap_doc / $metric->total_invoices) * 100, 1) 
                        : 0
                ];
            })->values()->toArray();
            
            // Sort by completion percentage (ascending)
            usort($result, function ($a, $b) {
                return $a['completion_percentage'] <=> $b['completion_percentage'];
            });
            
            return $result;
        });
    }

    /**
     * Get SQL subquery for arrival date calculation (replicates accessor logic)
     */
    private function getArrivalDateSubquery($modelType)
    {
        $tableName = $modelType === 'Invoice' ? 'invoices' : 'additional_documents';
        $documentType = $modelType === 'Invoice' 
            ? 'App\\\\Models\\\\Invoice' 
            : 'App\\\\Models\\\\AdditionalDocument';
        
        return "
            COALESCE(
                (SELECT received_at FROM distributions 
                 INNER JOIN distribution_documents ON distributions.id = distribution_documents.distribution_id
                 WHERE distribution_documents.document_type = '{$documentType}'
                   AND distribution_documents.document_id = {$tableName}.id
                   AND distribution_documents.receiver_verification_status = 'verified'
                   AND distributions.received_at IS NOT NULL
                 ORDER BY distributions.received_at DESC LIMIT 1),
                COALESCE({$tableName}.receive_date, {$tableName}.created_at)
            )
        ";
    }

    /**
     * Invalidate all dashboard caches for a user
     */
    public function invalidateCache($userId, $userLocationCode)
    {
        Cache::forget("dashboard.workflow_metrics.{$userId}.{$userLocationCode}");
        Cache::forget("dashboard.age_breakdown.{$userId}.{$userLocationCode}");
        Cache::forget("dashboard.sap_metrics.{$userId}.{$userLocationCode}");
        Cache::forget("dashboard.aging_alerts.{$userId}.{$userLocationCode}");
    }

    /**
     * Invalidate all dashboard caches (for admin operations)
     */
    public function invalidateAllCaches()
    {
        Cache::flush();
    }
}
