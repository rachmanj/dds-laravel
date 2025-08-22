<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Distribution;
use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\DistributionHistory;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
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

        return view('dashboard', compact(
            'metrics',
            'documentAgeBreakdown',
            'pendingDistributions',
            'recentActivity'
        ))->with([
            'getActivityIcon' => fn($action) => $this->getActivityIcon($action),
            'getActivityColor' => fn($action) => $this->getActivityColor($action)
        ]);
    }

    private function getWorkflowMetrics($user, $userLocationCode)
    {
        $isAdmin = array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin']);

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

        // Overdue documents (>14 days in department)
        $overdueDate = Carbon::now()->subDays(14);
        $overdueInvoicesQuery = Invoice::where('distribution_status', 'distributed')
            ->where('created_at', '<', $overdueDate);
        $overdueAdditionalQuery = AdditionalDocument::where('distribution_status', 'distributed')
            ->where('created_at', '<', $overdueDate);

        if (!$isAdmin && $userLocationCode) {
            $overdueInvoicesQuery->where('cur_loc', $userLocationCode);
            $overdueAdditionalQuery->where('cur_loc', $userLocationCode);
        }

        $overdueCount = $overdueInvoicesQuery->count() + $overdueAdditionalQuery->count();

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
        $isAdmin = array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin']);

        // Get documents in user's department
        $invoicesQuery = Invoice::where('distribution_status', 'distributed');
        $additionalQuery = AdditionalDocument::where('distribution_status', 'distributed');

        if (!$isAdmin && $userLocationCode) {
            $invoicesQuery->where('cur_loc', $userLocationCode);
            $additionalQuery->where('cur_loc', $userLocationCode);
        }

        $now = Carbon::now();

        // Age breakdown for invoices
        $invoices = $invoicesQuery->get();
        $invoiceAgeBreakdown = $this->categorizeDocumentsByAge($invoices, $now);

        // Age breakdown for additional documents
        $additionalDocs = $additionalQuery->get();
        $additionalAgeBreakdown = $this->categorizeDocumentsByAge($additionalDocs, $now);

        // Combine both
        return [
            '0_7_days' => $invoiceAgeBreakdown['0_7_days'] + $additionalAgeBreakdown['0_7_days'],
            '8_14_days' => $invoiceAgeBreakdown['8_14_days'] + $additionalAgeBreakdown['8_14_days'],
            '15_plus_days' => $invoiceAgeBreakdown['15_plus_days'] + $additionalAgeBreakdown['15_plus_days'],
        ];
    }

    private function categorizeDocumentsByAge($documents, $now)
    {
        $breakdown = [
            '0_7_days' => 0,
            '8_14_days' => 0,
            '15_plus_days' => 0,
        ];

        foreach ($documents as $document) {
            $daysInDepartment = $document->created_at->diffInDays($now);

            if ($daysInDepartment <= 7) {
                $breakdown['0_7_days']++;
            } elseif ($daysInDepartment <= 14) {
                $breakdown['8_14_days']++;
            } else {
                $breakdown['15_plus_days']++;
            }
        }

        return $breakdown;
    }

    private function getPendingDistributions($user, $userLocationCode)
    {
        $isAdmin = array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin']);

        $query = Distribution::with(['originDepartment', 'destinationDepartment', 'type'])
            ->where('status', 'sent')
            ->orderBy('sent_at', 'desc')
            ->limit(5);

        if (!$isAdmin && $userLocationCode) {
            $query->where('destination_department_id', $user->department_id);
        }

        return $query->get();
    }

    private function getRecentActivity($user, $userLocationCode)
    {
        $isAdmin = array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin']);

        $query = DistributionHistory::with(['distribution', 'user'])
            ->orderBy('action_performed_at', 'desc')
            ->limit(10);

        if (!$isAdmin && $userLocationCode) {
            $query->whereHas('distribution', function ($q) use ($user) {
                $q->where('origin_department_id', $user->department_id)
                    ->orWhere('destination_department_id', $user->department_id);
            });
        }

        return $query->get();
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
