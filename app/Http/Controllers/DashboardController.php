<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Distribution;
use App\Models\DistributionHistory;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Load relationships with null safety
            $user->loadMissing(['roles', 'department']);
            
            $userLocationCode = $user->department_location_code;
            
            // Use optimized service for all metrics
            $metricsService = app(DashboardMetricsService::class);

            // Get workflow metrics (optimized with caching)
            $metrics = $metricsService->getWorkflowMetrics($user, $userLocationCode);

            // Get department-specific document age breakdown (optimized)
            $documentAgeBreakdown = $metricsService->getDocumentAgeBreakdown($user, $userLocationCode);

            // Get pending distributions (already optimized)
            $pendingDistributions = $this->getPendingDistributions($user, $userLocationCode);

            // Get recent activity (already optimized)
            $recentActivity = $this->getRecentActivity($user, $userLocationCode);

            // Get SAP document metrics by department (optimized)
            $sapDocumentMetrics = $metricsService->getSapDocumentMetrics($user, $userLocationCode);

            // Get department-specific aging alerts (optimized)
            $departmentAgingAlerts = $metricsService->getDepartmentSpecificAgingAlerts($user, $userLocationCode);

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
