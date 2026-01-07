<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Distribution;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsAccountingFulfillmentController extends Controller
{
    private const ACCOUNTING_LOCATION_CODE = '000HACC';
    private const FINANCE_LOCATION_CODE = '001HFIN';

    /**
     * Display the Accounting Monthly Invoice Fulfillment report page.
     */
    public function index(Request $request): View
    {
        $selectedYear = $request->get('year', date('Y'));
        $years = $this->getAvailableYears();

        return view('reports.accounting-fulfillment.index', compact('selectedYear', 'years'));
    }

    /**
     * Get data for the report table (AJAX endpoint).
     */
    public function data(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));

        // Get Accounting and Finance department IDs
        $accountingDept = Department::where('location_code', self::ACCOUNTING_LOCATION_CODE)->first();
        $financeDept = Department::where('location_code', self::FINANCE_LOCATION_CODE)->first();

        if (!$accountingDept || !$financeDept) {
            return response()->json([
                'success' => false,
                'message' => 'Accounting or Finance department not found'
            ], 404);
        }

        $accountingDeptId = $accountingDept->id;
        $financeDeptId = $financeDept->id;

        // Get monthly data using optimized SQL query
        $monthlyData = $this->getMonthlyFulfillmentData($year, $accountingDeptId, $financeDeptId);

        return response()->json([
            'success' => true,
            'data' => $monthlyData
        ]);
    }

    /**
     * Get monthly fulfillment data with optimized SQL queries.
     */
    private function getMonthlyFulfillmentData(int $year, int $accountingDeptId, int $financeDeptId): array
    {
        // Step 1: Get all invoices with receive_date in the given year/month
        // that were created by Accounting department users
        $invoicesReceivedQuery = DB::table('invoices')
            ->select(
                'invoices.id',
                'invoices.receive_date as arrival_date'
            )
            ->whereIn('invoices.created_by', function ($query) use ($accountingDeptId) {
                $query->select('id')
                    ->from('users')
                    ->where('department_id', $accountingDeptId);
            })
            ->whereNotNull('invoices.receive_date');
        
        // Get all results first, then filter by year/month in PHP (more reliable)
        $allReceivedInvoices = $invoicesReceivedQuery->get();

        // Step 2: Get invoices distributed to Finance (first distribution only)
        $invoicesDistributedToFinance = DB::table('invoices')
            ->select(
                'invoices.id',
                DB::raw("(
                    SELECT distributions.sender_verified_at
                    FROM distributions
                    INNER JOIN distribution_documents ON distributions.id = distribution_documents.distribution_id
                    WHERE distribution_documents.document_type = 'App\\\\Models\\\\Invoice'
                      AND distribution_documents.document_id = invoices.id
                      AND distributions.origin_department_id = {$accountingDeptId}
                      AND distributions.destination_department_id = {$financeDeptId}
                      AND distributions.sender_verified_at IS NOT NULL
                    ORDER BY distributions.sender_verified_at ASC
                    LIMIT 1
                ) as distribution_date")
            )
            ->whereExists(function ($query) use ($accountingDeptId, $financeDeptId) {
                $query->select(DB::raw(1))
                    ->from('distributions')
                    ->join('distribution_documents', 'distributions.id', '=', 'distribution_documents.distribution_id')
                    ->whereColumn('distribution_documents.document_id', 'invoices.id')
                    ->where('distribution_documents.document_type', 'App\\Models\\Invoice')
                    ->where('distributions.origin_department_id', $accountingDeptId)
                    ->where('distributions.destination_department_id', $financeDeptId)
                    ->whereNotNull('distributions.sender_verified_at');
            });

        // Step 3: Filter received invoices by year/month and combine data
        $receivedInvoices = collect($allReceivedInvoices)
            ->filter(function ($invoice) use ($year) {
                if (!$invoice->arrival_date) {
                    return false;
                }
                $arrivalDate = Carbon::parse($invoice->arrival_date);
                return $arrivalDate->year == $year;
            })
            ->keyBy('id');
        
        $distributedInvoices = $invoicesDistributedToFinance->get()->keyBy('id');

        // Initialize monthly data structure
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[$month] = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('M Y'),
                'total_received' => 0,
                'distributed_to_finance' => 0,
                'percentage_distributed' => 0,
                'total_days' => 0,
                'average_days' => 0
            ];
        }

        // Process received invoices
        foreach ($receivedInvoices as $invoice) {
            if (!$invoice->arrival_date) {
                continue;
            }
            
            $receiveDate = Carbon::parse($invoice->arrival_date);
            $month = (int) $receiveDate->format('n');

            if ($receiveDate->year == $year && $month >= 1 && $month <= 12) {
                $monthlyData[$month]['total_received']++;

                // Check if this invoice was distributed to Finance
                if (isset($distributedInvoices[$invoice->id])) {
                    $distributionDate = Carbon::parse($distributedInvoices[$invoice->id]->distribution_date);
                    
                    // Only count if distribution happened in the same year or later
                    if ($distributionDate->year >= $year) {
                        $monthlyData[$month]['distributed_to_finance']++;
                        
                        // Calculate days in Accounting (from receive_date to distribution)
                        $daysInAccounting = $receiveDate->diffInDays($distributionDate);
                        $monthlyData[$month]['total_days'] += $daysInAccounting;
                    }
                }
            }
        }

        // Calculate percentages and averages
        foreach ($monthlyData as &$data) {
            if ($data['total_received'] > 0) {
                $data['percentage_distributed'] = round(
                    ($data['distributed_to_finance'] / $data['total_received']) * 100,
                    2
                );
            }

            if ($data['distributed_to_finance'] > 0) {
                $data['average_days'] = round(
                    $data['total_days'] / $data['distributed_to_finance'],
                    2
                );
            }
        }

        // Convert to indexed array and filter out months with no data (optional)
        return array_values($monthlyData);
    }

    /**
     * Get available years for the year selector.
     */
    private function getAvailableYears(): array
    {
        // Get years from invoices created by Accounting users
        $invoiceYears = DB::table('invoices')
            ->select(DB::raw('YEAR(receive_date) as year'))
            ->whereIn('created_by', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('department_id', function ($subQuery) {
                        $subQuery->select('id')
                            ->from('departments')
                            ->where('location_code', self::ACCOUNTING_LOCATION_CODE)
                            ->limit(1);
                    });
            })
            ->whereNotNull('receive_date')
            ->distinct()
            ->pluck('year')
            ->filter()
            ->toArray();

        $distributionYears = DB::table('distributions')
            ->select(DB::raw('YEAR(sender_verified_at) as year'))
            ->whereNotNull('sender_verified_at')
            ->distinct()
            ->pluck('year')
            ->filter()
            ->toArray();

        $years = array_unique(array_merge($invoiceYears, $distributionYears));
        rsort($years);

        // Ensure current year is included
        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        return $years ?: [$currentYear];
    }
}
