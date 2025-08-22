<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Distribution;
use App\Models\DistributionHistory;
use App\Models\DistributionType;
use App\Models\Department;
use Carbon\Carbon;

class DistributionDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userDepartment = $user->department;
        $userLocationCode = $user->department_location_code;
        $isAdmin = array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin']);

        // Get distribution status overview
        $statusOverview = $this->getDistributionStatusOverview($user, $userLocationCode, $isAdmin);

        // Get workflow performance metrics
        $workflowMetrics = $this->getWorkflowPerformanceMetrics($user, $userLocationCode, $isAdmin);

        // Get pending actions
        $pendingActions = $this->getPendingActions($user, $userLocationCode, $isAdmin);

        // Get recent activity
        $recentActivity = $this->getRecentActivity($user, $userLocationCode, $isAdmin);

        // Get department performance comparison
        $departmentPerformance = $this->getDepartmentPerformance($user, $userLocationCode, $isAdmin);

        // Get distribution types breakdown
        $typeBreakdown = $this->getDistributionTypeBreakdown($user, $userLocationCode, $isAdmin);

        return view('distributions.dashboard', compact(
            'statusOverview',
            'workflowMetrics',
            'pendingActions',
            'recentActivity',
            'departmentPerformance',
            'typeBreakdown'
        ));
    }

    private function getDistributionStatusOverview($user, $userLocationCode, $isAdmin)
    {
        $query = Distribution::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where(function ($q) use ($user) {
                $q->where('origin_department_id', $user->department_id)
                    ->orWhere('destination_department_id', $user->department_id);
            });
        }

        $statuses = ['draft', 'verified_by_sender', 'sent', 'received', 'verified_by_receiver', 'completed'];
        $overview = [];

        foreach ($statuses as $status) {
            $count = (clone $query)->where('status', $status)->count();
            $overview[$status] = $count;
        }

        return $overview;
    }

    private function getWorkflowPerformanceMetrics($user, $userLocationCode, $isAdmin)
    {
        $query = Distribution::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where(function ($q) use ($user) {
                $q->where('origin_department_id', $user->department_id)
                    ->orWhere('destination_department_id', $user->department_id);
            });
        }

        // Get completed distributions for performance calculation
        $completedDistributions = (clone $query)
            ->where('status', 'completed')
            ->whereNotNull('created_at')
            ->get();

        $totalTime = 0;
        $count = $completedDistributions->count();

        foreach ($completedDistributions as $distribution) {
            $totalTime += $distribution->created_at->diffInHours($distribution->updated_at);
        }

        $avgTime = $count > 0 ? round($totalTime / $count, 2) : 0;

        // Calculate stage-specific metrics
        $stageMetrics = [];
        $stages = [
            'draft_to_verified' => ['draft', 'verified_by_sender'],
            'verified_to_sent' => ['verified_by_sender', 'sent'],
            'sent_to_received' => ['sent', 'received'],
            'received_to_completed' => ['received', 'completed']
        ];

        foreach ($stages as $stage => $statuses) {
            $stageTime = 0;
            $stageCount = 0;

            foreach ($completedDistributions as $distribution) {
                $startTime = $this->getStatusTimestamp($distribution, $statuses[0]);
                $endTime = $this->getStatusTimestamp($distribution, $statuses[1]);

                if ($startTime && $endTime) {
                    $stageTime += $startTime->diffInHours($endTime);
                    $stageCount++;
                }
            }

            $stageMetrics[$stage] = $stageCount > 0 ? round($stageTime / $stageCount, 2) : 0;
        }

        return [
            'average_completion_time' => $avgTime,
            'total_completed' => $count,
            'stage_metrics' => $stageMetrics
        ];
    }

    private function getStatusTimestamp($distribution, $status)
    {
        return match ($status) {
            'draft' => $distribution->created_at,
            'verified_by_sender' => $distribution->sender_verified_at,
            'sent' => $distribution->sent_at,
            'received' => $distribution->received_at,
            'verified_by_receiver' => $distribution->receiver_verified_at,
            'completed' => $distribution->updated_at,
            default => null
        };
    }

    private function getPendingActions($user, $userLocationCode, $isAdmin)
    {
        $pendingActions = [];

        // Distributions waiting for sender verification
        $query = Distribution::where('status', 'draft');
        if (!$isAdmin && $userLocationCode) {
            $query->where('origin_department_id', $user->department_id);
        }
        $pendingActions['sender_verification'] = $query->count();

        // Distributions waiting to be sent
        $query = Distribution::where('status', 'verified_by_sender');
        if (!$isAdmin && $userLocationCode) {
            $query->where('origin_department_id', $user->department_id);
        }
        $pendingActions['waiting_to_send'] = $query->count();

        // Distributions waiting to be received
        $query = Distribution::where('status', 'sent');
        if (!$isAdmin && $userLocationCode) {
            $query->where('destination_department_id', $user->department_id);
        }
        $pendingActions['waiting_to_receive'] = $query->count();

        // Distributions waiting for receiver verification
        $query = Distribution::where('status', 'received');
        if (!$isAdmin && $userLocationCode) {
            $query->where('destination_department_id', $user->department_id);
        }
        $pendingActions['receiver_verification'] = $query->count();

        return $pendingActions;
    }

    private function getRecentActivity($user, $userLocationCode, $isAdmin)
    {
        $query = DistributionHistory::with(['distribution', 'user'])
            ->orderBy('action_performed_at', 'desc')
            ->limit(15);

        if (!$isAdmin && $userLocationCode) {
            $query->whereHas('distribution', function ($q) use ($user) {
                $q->where('origin_department_id', $user->department_id)
                    ->orWhere('destination_department_id', $user->department_id);
            });
        }

        return $query->get();
    }

    private function getDepartmentPerformance($user, $userLocationCode, $isAdmin)
    {
        if ($isAdmin) {
            // Admin can see all departments
            $departments = Department::all();
        } else {
            // Regular user sees only their department
            $departments = collect([$user->department])->filter();
        }

        $performance = [];

        foreach ($departments as $department) {
            // Distributions created by this department
            $created = Distribution::where('origin_department_id', $department->id)->count();

            // Distributions received by this department
            $received = Distribution::where('destination_department_id', $department->id)->count();

            // Completed distributions
            $completed = Distribution::where('origin_department_id', $department->id)
                ->where('status', 'completed')
                ->count();

            $performance[$department->name] = [
                'created' => $created,
                'received' => $received,
                'completed' => $completed,
                'completion_rate' => $created > 0 ? round(($completed / $created) * 100, 2) : 0
            ];
        }

        return $performance;
    }

    private function getDistributionTypeBreakdown($user, $userLocationCode, $isAdmin)
    {
        $query = Distribution::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where(function ($q) use ($user) {
                $q->where('origin_department_id', $user->department_id)
                    ->orWhere('destination_department_id', $user->department_id);
            });
        }

        $types = DistributionType::all();
        $breakdown = [];

        foreach ($types as $type) {
            $count = (clone $query)->where('type_id', $type->id)->count();
            $breakdown[$type->name] = $count;
        }

        return $breakdown;
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
            'cancelled' => 'times',
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
            'cancelled' => 'danger',
            default => 'gray'
        };
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'draft' => 'secondary',
            'verified_by_sender' => 'info',
            'sent' => 'warning',
            'received' => 'primary',
            'verified_by_receiver' => 'success',
            'completed' => 'success',
            default => 'secondary'
        };
    }
}
