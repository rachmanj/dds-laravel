<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\Department;
use Carbon\Carbon;

class InvoiceDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userDepartment = $user->department;
        $userLocationCode = $user->department_location_code;
        $isAdmin = array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin']);

        // Get invoice status overview
        $statusOverview = $this->getInvoiceStatusOverview($user, $userLocationCode, $isAdmin);

        // Get financial metrics
        $financialMetrics = $this->getFinancialMetrics($user, $userLocationCode, $isAdmin);

        // Get processing metrics
        $processingMetrics = $this->getProcessingMetrics($user, $userLocationCode, $isAdmin);

        // Get distribution status
        $distributionStatus = $this->getDistributionStatus($user, $userLocationCode, $isAdmin);

        // Get supplier analysis
        $supplierAnalysis = $this->getSupplierAnalysis($user, $userLocationCode, $isAdmin);

        // Get invoice types breakdown
        $typeBreakdown = $this->getInvoiceTypeBreakdown($user, $userLocationCode, $isAdmin);

        return view('invoices.dashboard', compact(
            'statusOverview',
            'financialMetrics',
            'processingMetrics',
            'distributionStatus',
            'supplierAnalysis',
            'typeBreakdown'
        ));
    }

    private function getInvoiceStatusOverview($user, $userLocationCode, $isAdmin)
    {
        $query = Invoice::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        $statuses = ['open', 'verify', 'return', 'sap', 'close', 'cancel'];
        $overview = [];

        foreach ($statuses as $status) {
            $count = (clone $query)->where('status', $status)->count();
            $overview[$status] = $count;
        }

        return $overview;
    }

    private function getFinancialMetrics($user, $userLocationCode, $isAdmin)
    {
        $query = Invoice::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        // Get current month invoices
        $currentMonth = Carbon::now()->startOfMonth();
        $currentMonthInvoices = (clone $query)
            ->where('created_at', '>=', $currentMonth)
            ->get();

        // Get total amounts
        $totalAmount = $currentMonthInvoices->sum('amount');
        $totalPaid = $currentMonthInvoices->where('status', 'close')->sum('amount');
        $totalPending = $currentMonthInvoices->where('status', 'open')->sum('amount');
        $totalApproved = $currentMonthInvoices->where('status', 'verify')->sum('amount');

        // Calculate averages
        $count = $currentMonthInvoices->count();
        $averageAmount = $count > 0 ? round($totalAmount / $count, 2) : 0;

        // Get overdue invoices (open > 30 days)
        $overdueInvoices = (clone $query)
            ->where('status', 'open')
            ->where('created_at', '<=', Carbon::now()->subDays(30))
            ->get();

        $overdueAmount = $overdueInvoices->sum('amount');

        return [
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'total_approved' => $totalApproved,
            'average_amount' => $averageAmount,
            'overdue_amount' => $overdueAmount,
            'overdue_count' => $overdueInvoices->count(),
            'payment_rate' => $totalAmount > 0 ? round(($totalPaid / $totalAmount) * 100, 2) : 0
        ];
    }

    private function getProcessingMetrics($user, $userLocationCode, $isAdmin)
    {
        $query = Invoice::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        // Get processing times for different statuses
        $processingMetrics = [];

        // Open to Verify
        $openToVerify = (clone $query)
            ->where('status', 'verify')
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->get();

        $openToVerifyTime = 0;
        $openToVerifyCount = $openToVerify->count();

        foreach ($openToVerify as $invoice) {
            $openToVerifyTime += $invoice->created_at->diffInHours($invoice->updated_at);
        }

        $processingMetrics['open_to_verify'] = $openToVerifyCount > 0 ? round($openToVerifyTime / $openToVerifyCount, 2) : 0;

        // Verify to Close
        $verifyToClose = (clone $query)
            ->where('status', 'close')
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->get();

        $verifyToCloseTime = 0;
        $verifyToCloseCount = $verifyToClose->count();

        foreach ($verifyToClose as $invoice) {
            $verifyToCloseTime += $invoice->created_at->diffInHours($invoice->updated_at);
        }

        $processingMetrics['verify_to_close'] = $verifyToCloseCount > 0 ? round($verifyToCloseTime / $verifyToCloseCount, 2) : 0;

        // Open to Close (Overall)
        $openToClose = (clone $query)
            ->where('status', 'close')
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->get();

        $openToCloseTime = 0;
        $openToCloseCount = $openToClose->count();

        foreach ($openToClose as $invoice) {
            $openToCloseTime += $invoice->created_at->diffInHours($invoice->updated_at);
        }

        $processingMetrics['open_to_close'] = $openToCloseCount > 0 ? round($openToCloseTime / $openToCloseCount, 2) : 0;

        return [
            'stage_metrics' => $processingMetrics,
            'total_processed' => $openToVerifyCount + $verifyToCloseCount + $openToCloseCount
        ];
    }

    private function getDistributionStatus($user, $userLocationCode, $isAdmin)
    {
        $query = Invoice::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        $distributionStatuses = ['available', 'in_transit', 'distributed', 'unaccounted_for'];
        $status = [];

        foreach ($distributionStatuses as $distStatus) {
            $count = (clone $query)->where('distribution_status', $distStatus)->count();
            $status[$distStatus] = $count;
        }

        // Get invoices by age
        $now = Carbon::now();
        $invoices = (clone $query)->get();

        $ageBreakdown = [
            '0-7_days' => 0,
            '8-14_days' => 0,
            '15_plus_days' => 0
        ];

        foreach ($invoices as $invoice) {
            $age = $invoice->created_at->diffInDays($now);

            if ($age <= 7) {
                $ageBreakdown['0-7_days']++;
            } elseif ($age <= 14) {
                $ageBreakdown['8-14_days']++;
            } else {
                $ageBreakdown['15_plus_days']++;
            }
        }

        return [
            'status_counts' => $status,
            'age_breakdown' => $ageBreakdown
        ];
    }

    private function getSupplierAnalysis($user, $userLocationCode, $isAdmin)
    {
        $query = Invoice::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        // Get top suppliers by invoice count
        $topSuppliers = (clone $query)
            ->with('supplier')
            ->selectRaw('supplier_id, COUNT(*) as invoice_count, SUM(amount) as total_amount')
            ->groupBy('supplier_id')
            ->orderBy('invoice_count', 'desc')
            ->limit(10)
            ->get();

        $supplierAnalysis = [];
        foreach ($topSuppliers as $supplier) {
            if ($supplier->supplier) {
                $supplierAnalysis[$supplier->supplier->name] = [
                    'invoice_count' => $supplier->invoice_count,
                    'total_amount' => $supplier->amount
                ];
            }
        }

        // Get supplier performance (payment rate)
        $supplierPerformance = [];
        $suppliers = Supplier::all();

        foreach ($suppliers as $supplier) {
            $supplierInvoices = (clone $query)->where('supplier_id', $supplier->id);
            $totalInvoices = $supplierInvoices->count();
            $closedInvoices = (clone $supplierInvoices)->where('status', 'close')->count();

            if ($totalInvoices > 0) {
                $supplierPerformance[$supplier->name] = [
                    'total_invoices' => $totalInvoices,
                    'closed_invoices' => $closedInvoices,
                    'payment_rate' => round(($closedInvoices / $totalInvoices) * 100, 2)
                ];
            }
        }

        return [
            'top_suppliers' => $supplierAnalysis,
            'supplier_performance' => $supplierPerformance
        ];
    }

    private function getInvoiceTypeBreakdown($user, $userLocationCode, $isAdmin)
    {
        $query = Invoice::query();

        if (!$isAdmin && $userLocationCode) {
            $query->where('cur_loc', $userLocationCode);
        }

        $types = InvoiceType::all();
        $breakdown = [];

        foreach ($types as $type) {
            $count = (clone $query)->where('type_id', $type->id)->count();
            $amount = (clone $query)->where('type_id', $type->id)->sum('amount');

            $breakdown[$type->name] = [
                'count' => $count,
                'amount' => $amount
            ];
        }

        return $breakdown;
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'open' => 'warning',
            'verify' => 'info',
            'return' => 'danger',
            'sap' => 'primary',
            'close' => 'success',
            'cancel' => 'dark',
            default => 'secondary'
        };
    }

    public function getDistributionStatusColor($status)
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
            'open' => 'clock',
            'verify' => 'check-circle',
            'return' => 'times-circle',
            'sap' => 'database',
            'close' => 'money-bill-wave',
            'cancel' => 'ban',
            default => 'file-invoice'
        };
    }
}
