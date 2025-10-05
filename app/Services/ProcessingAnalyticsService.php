<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\Distribution;
use App\Models\Department;
use App\Models\DistributionDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessingAnalyticsService
{
    /**
     * Application timezone for consistent date calculations
     */
    protected $timezone;

    public function __construct()
    {
        $this->timezone = config('app.timezone', 'UTC');
    }

    /**
     * Validate that a date range contains only historical data
     */
    private function validateHistoricalDateRange($startDate, $endDate)
    {
        $now = Carbon::now($this->timezone);
        $currentMonthEnd = $now->copy()->endOfMonth();

        // Ensure dates are not in the future beyond current month
        if ($startDate->gt($currentMonthEnd) || $endDate->gt($currentMonthEnd)) {
            throw new \InvalidArgumentException(
                'Date range cannot include future months beyond current month. ' .
                    'Current month: ' . $now->format('F Y')
            );
        }

        // Ensure start date is not after end date
        if ($startDate->gt($endDate)) {
            throw new \InvalidArgumentException('Start date cannot be after end date');
        }

        return true;
    }

    /**
     * Validate month and year parameters
     */
    private function validateMonthYear($year, $month)
    {
        $now = Carbon::now($this->timezone);

        // Validate year is not in the future beyond current year
        if ($year > $now->year) {
            throw new \InvalidArgumentException(
                "Year {$year} is in the future. Maximum allowed year: {$now->year}"
            );
        }

        // Validate month range
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException("Month must be between 1 and 12, got: {$month}");
        }

        // If it's the current year, validate month is not in the future
        if ($year == $now->year && $month > $now->month) {
            throw new \InvalidArgumentException(
                "Month {$month} in year {$year} is in the future. Current month: {$now->month}"
            );
        }

        return true;
    }
    public function getMonthlyProcessingDays($year, $month, $department = null)
    {
        // Validate input parameters
        $this->validateMonthYear($year, $month);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Validate date range is historical
        $this->validateHistoricalDateRange($startDate, $endDate);

        $query = Invoice::select([
            DB::raw('u.department_id'),
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days')
        ])
            ->join('users as u', 'invoices.created_by', '=', 'u.id')
            ->where('receive_date', '>=', $startDate)
            ->where('receive_date', '<=', $endDate)
            ->when($department, function ($query, $department) {
                return $query->where('u.department_id', $department);
            })
            ->groupBy('u.department_id');

        $invoiceStats = $query->get();

        $additionalDocStats = AdditionalDocument::select([
            DB::raw('u.department_id'),
            DB::raw('COUNT(*) as document_count'),
            DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days')
        ])
            ->join('users as u', 'additional_documents.created_by', '=', 'u.id')
            ->whereNotNull('receive_date')
            ->where('receive_date', '>=', $startDate)
            ->where('receive_date', '<=', $endDate)
            ->when($department, function ($query, $department) {
                return $query->where('u.department_id', $department);
            })
            ->groupBy('u.department_id')
            ->get();

        return [
            'invoices' => $invoiceStats,
            'additional_documents' => $additionalDocStats,
            'departments' => $this->getDepartmentSummary($startDate, $endDate)
        ];
    }

    public function getDepartmentProcessingDays($departmentId, $year = null, $month = null)
    {
        $query = Invoice::join('users', 'invoices.created_by', '=', 'users.id')
            ->where('users.department_id', $departmentId)
            ->whereNotNull('receive_date');

        if ($year && $month) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            $query->where('receive_date', '>=', $startDate)
                ->where('receive_date', '<=', $endDate);
        }

        $invoiceStats = $query->select([
            DB::raw('YEAR(receive_date) as year'),
            DB::raw('MONTH(receive_date) as month'),
            DB::raw('COUNT(*) as count'),
            DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days'),
            DB::raw('MIN(processing_days) as min_days'),
            DB::raw('MAX(processing_days) as max_days')
        ])
            ->groupBy(DB::raw('YEAR(receive_date), MONTH(receive_date)'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $additionalDocStats = AdditionalDocument::join('users', 'additional_documents.created_by', '=', 'users.id')
            ->where('users.department_id', $departmentId)
            ->whereNotNull('receive_date')
            ->when($year && $month, function ($query) use ($startDate, $endDate) {
                return $query->where('receive_date', '>=', $startDate)
                    ->where('receive_date', '<=', $endDate);
            })
            ->select([
                DB::raw('YEAR(receive_date) as year'),
                DB::raw('MONTH(receive_date) as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days'),
                DB::raw('MIN(DATEDIFF(now(), receive_date)) as min_days'),
                DB::raw('MAX(DATEDIFF(now(), receive_date)) as max_days')
            ])
            ->groupBy(DB::raw('YEAR(receive_date), MONTH(receive_date)'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return [
            'department' => Department::find($departmentId),
            'invoice_stats' => $invoiceStats,
            'additional_document_stats' => $additionalDocStats
        ];
    }

    public function getProcessingTrends($monthsBack = 6)
    {
        // Fix: Calculate the correct start date for historical data with timezone awareness
        $currentDate = Carbon::now($this->timezone);
        $currentMonthStart = $currentDate->copy()->startOfMonth();

        // Start from the month that is $monthsBack months before the current month
        $startDate = $currentMonthStart->copy()->subMonths($monthsBack - 1);

        $trendData = [];
        for ($i = 0; $i < $monthsBack; $i++) {
            $currentMonth = $startDate->copy()->addMonths($i);

            // Validation: Don't include future months beyond current month
            if ($currentMonth->isFuture() || $currentMonth->gt($currentMonthStart)) {
                break;
            }

            // Include months that are in the past or current month
            if ($currentMonth->lte($currentMonthStart)) {
                try {
                    $monthData = $this->getMonthlyProcessingDays($currentMonth->year, $currentMonth->month);

                    $trendData[] = [
                        'year' => $currentMonth->year,
                        'month' => $currentMonth->month,
                        'month_name' => $currentMonth->format('F Y'),
                        'data' => $monthData,
                        'is_historical' => !$currentMonth->isSameMonth($currentMonthStart),
                        'is_current_month' => $currentMonth->isSameMonth($currentMonthStart),
                        'timezone' => $this->timezone,
                        'calculated_at' => Carbon::now($this->timezone)->toISOString(),
                        'date_range' => [
                            'start' => $currentMonth->startOfMonth()->toISOString(),
                            'end' => $currentMonth->endOfMonth()->toISOString()
                        ]
                    ];
                } catch (\InvalidArgumentException $e) {
                    // Skip months that fail validation (e.g., future months)
                    continue;
                }
            }
        }

        return $trendData;
    }

    public function getMonthlyOverview($year, $month, $documentType = 'both')
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $data = [];

        if ($documentType === 'both' || $documentType === 'invoice') {
            $data['invoices'] = Invoice::select([
                'u.department_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days'),
                DB::raw('SUM(DATEDIFF(now(), receive_date)) as total_processing_days')
            ])
                ->join('users as u', 'invoices.created_by', '=', 'u.id')
                ->where('receive_date', '>=', $startDate)
                ->where('receive_date', '<=', $endDate)
                ->groupBy('u.department_id')
                ->get();
        }

        if ($documentType === 'both' || $documentType === 'additional_document') {
            $data['additional_documents'] = AdditionalDocument::select([
                'u.department_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days'),
                DB::raw('SUM(DATEDIFF(now(), receive_date)) as total_processing_days')
            ])
                ->join('users as u', 'additional_documents.created_by', '=', 'u.id')
                ->whereNotNull('receive_date')
                ->where('receive_date', '>=', $startDate)
                ->where('receive_date', '<=', $endDate)
                ->groupBy('u.department_id')
                ->get();
        }

        $departments = Department::select('id', 'name', 'akronim')->get()->keyBy('id');

        return [
            'month' => $month,
            'year' => $year,
            'data' => $data,
            'departments' => $departments
        ];
    }

    public function getDepartmentEfficiency($year, $documentType = 'both')
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = Carbon::create($year, 12, 31)->endOfYear();

        $efficiencyData = [];

        $departments = Department::all();

        foreach ($departments as $department) {
            $invoiceStats = null;
            $additionalDocStats = null;

            if ($documentType === 'both' || $documentType === 'invoice') {
                $invoiceStats = Invoice::join('users', 'invoices.created_by', '=', 'users.id')
                    ->where('users.department_id', $department->id)
                    ->where('receive_date', '>=', $startDate)
                    ->where('receive_date', '<=', $endDate)
                    ->whereNotNull('receive_date')
                    ->select([
                        DB::raw('COUNT(*) as count'),
                        DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days'),
                        DB::raw('SUM(amount) as total_amount')
                    ])
                    ->first();
            }

            if ($documentType === 'both' || $documentType === 'additional_document') {
                $additionalDocStats = AdditionalDocument::join('users', 'additional_documents.created_by', '=', 'users.id')
                    ->where('users.department_id', $department->id)
                    ->where('receive_date', '>=', $startDate)
                    ->where('receive_date', '<=', $endDate)
                    ->whereNotNull('receive_date')
                    ->select([
                        DB::raw('COUNT(*) as count'),
                        DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days')
                    ])
                    ->first();
            }

            $efficiencyData[] = [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'department_akronim' => $department->akronim,
                'invoice_stats' => $invoiceStats,
                'additional_document_stats' => $additionalDocStats
            ];
        }

        return $efficiencyData;
    }

    private function getDepartmentSummary($startDate, $endDate)
    {
        return Department::select([
            'departments.id',
            'departments.name',
            'departments.akronim',
            DB::raw('COALESCE(invoice_count.count, 0) as invoice_count'),
            DB::raw('COALESCE(doc_count.count, 0) as additional_document_count')
        ])
            ->leftJoinSub(
                Invoice::join('users', 'invoices.created_by', '=', 'users.id')
                    ->select('users.department_id', DB::raw('COUNT(*) as count'))
                    ->where('invoices.receive_date', '>=', $startDate)
                    ->where('invoices.receive_date', '<=', $endDate)
                    ->groupBy('users.department_id'),
                'invoice_count',
                'departments.id',
                '=',
                'invoice_count.department_id'
            )
            ->leftJoinSub(
                AdditionalDocument::join('users', 'additional_documents.created_by', '=', 'users.id')
                    ->select('users.department_id', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('additional_documents.receive_date')
                    ->where('additional_documents.receive_date', '>=', $startDate)
                    ->where('additional_documents.receive_date', '<=', $endDate)
                    ->groupBy('users.department_id'),
                'doc_count',
                'departments.id',
                '=',
                'doc_count.department_id'
            )
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereExists(function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->select(DB::raw(1))
                        ->from('invoices')
                        ->join('users', 'invoices.created_by', '=', 'users.id')
                        ->where('users.department_id', DB::raw('departments.id'))
                        ->where('receive_date', '>=', $startDate)
                        ->where('receive_date', '<=', $endDate);
                })
                    ->orWhereExists(function ($subQuery) use ($startDate, $endDate) {
                        $subQuery->select(DB::raw(1))
                            ->from('additional_documents')
                            ->join('users', 'additional_documents.created_by', '=', 'users.id')
                            ->where('users.department_id', DB::raw('departments.id'))
                            ->whereNotNull('receive_date')
                            ->where('receive_date', '>=', $startDate)
                            ->where('receive_date', '<=', $endDate);
                    });
            })
            ->get();
    }

    public function exportMonthlyReport($year, $month, $format = 'excel')
    {
        $data = $this->getMonthlyOverview($year, $month);
        $filename = "processing_analytics_{$year}_{$month}." . ($format === 'excel' ? 'xlsx' : $format);

        // Implementation for export will be added using Laravel Excel
        return storage_path("exports/{$filename}");
    }

    /**
     * Get accurate processing days based on actual distribution workflow
     * This calculates processing days from receive_date to when document was sent to next department
     */
    public function getAccurateProcessingDays($year, $month, $department = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get invoice processing days based on distribution workflow
        $invoiceQuery = DB::table('invoices as i')
            ->join('users as u', 'i.created_by', '=', 'u.id')
            ->join('distribution_documents as dd', function ($join) {
                $join->on('i.id', '=', 'dd.document_id')
                    ->where('dd.document_type', '=', 'App\\Models\\Invoice');
            })
            ->join('distributions as d', 'dd.distribution_id', '=', 'd.id')
            ->join('departments as dept', 'u.department_id', '=', 'dept.id')
            ->where('i.receive_date', '>=', $startDate)
            ->where('i.receive_date', '<=', $endDate)
            ->whereNotNull('d.sent_at')
            ->when($department, function ($query, $department) {
                return $query->where('u.department_id', $department);
            })
            ->select([
                'u.department_id',
                'dept.name as department_name',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('AVG(DATEDIFF(d.sent_at, i.receive_date)) as avg_processing_days'),
                DB::raw('MIN(DATEDIFF(d.sent_at, i.receive_date)) as min_processing_days'),
                DB::raw('MAX(DATEDIFF(d.sent_at, i.receive_date)) as max_processing_days')
            ])
            ->groupBy('u.department_id', 'dept.name');

        $invoiceStats = $invoiceQuery->get();

        // Get additional document processing days based on distribution workflow
        $additionalDocQuery = DB::table('additional_documents as ad')
            ->join('users as u', 'ad.created_by', '=', 'u.id')
            ->join('distribution_documents as dd', function ($join) {
                $join->on('ad.id', '=', 'dd.document_id')
                    ->where('dd.document_type', '=', 'App\\Models\\AdditionalDocument');
            })
            ->join('distributions as d', 'dd.distribution_id', '=', 'd.id')
            ->join('departments as dept', 'u.department_id', '=', 'dept.id')
            ->whereNotNull('ad.receive_date')
            ->where('ad.receive_date', '>=', $startDate)
            ->where('ad.receive_date', '<=', $endDate)
            ->whereNotNull('d.sent_at')
            ->when($department, function ($query, $department) {
                return $query->where('u.department_id', $department);
            })
            ->select([
                'u.department_id',
                'dept.name as department_name',
                DB::raw('COUNT(*) as document_count'),
                DB::raw('AVG(DATEDIFF(d.sent_at, ad.receive_date)) as avg_processing_days'),
                DB::raw('MIN(DATEDIFF(d.sent_at, ad.receive_date)) as min_processing_days'),
                DB::raw('MAX(DATEDIFF(d.sent_at, ad.receive_date)) as max_processing_days')
            ])
            ->groupBy('u.department_id', 'dept.name');

        $additionalDocStats = $additionalDocQuery->get();

        return [
            'invoices' => $invoiceStats,
            'additional_documents' => $additionalDocStats,
            'departments' => $this->getDepartmentSummary($startDate, $endDate)
        ];
    }

    /**
     * Get individual document processing timeline with department-specific aging
     * Shows complete journey of a document through departments
     */
    public function getDocumentProcessingTimeline($documentId, $documentType = 'invoice')
    {
        $modelClass = $documentType === 'invoice' ? Invoice::class : AdditionalDocument::class;
        $document = $modelClass::find($documentId);

        if (!$document) {
            return null;
        }

        // Get all distributions for this document
        $distributions = DB::table('distribution_documents as dd')
            ->join('distributions as d', 'dd.distribution_id', '=', 'd.id')
            ->join('departments as origin_dept', 'd.origin_department_id', '=', 'origin_dept.id')
            ->join('departments as dest_dept', 'd.destination_department_id', '=', 'dest_dept.id')
            ->where('dd.document_id', $documentId)
            ->where('dd.document_type', $modelClass)
            ->select([
                'd.id as distribution_id',
                'd.distribution_number',
                'd.status',
                'd.sent_at',
                'd.received_at',
                'origin_dept.name as origin_department',
                'dest_dept.name as destination_department',
                'origin_dept.id as origin_department_id',
                'dest_dept.id as destination_department_id',
                'origin_dept.location_code as origin_location_code',
                'dest_dept.location_code as destination_location_code'
            ])
            ->orderBy('d.sent_at', 'asc')
            ->get();

        $timeline = [];
        $currentLocationArrivalDate = $document->current_location_arrival_date;
        $totalProcessingDays = 0;

        // Add initial department (where document was first received)
        if (!$document->hasBeenDistributed()) {
            $daysInCurrentLocation = $document->days_in_current_location;
            $timeline[] = [
                'step' => 1,
                'department' => $this->getDepartmentByLocationCode($document->cur_loc),
                'department_id' => null,
                'location_code' => $document->cur_loc,
                'arrival_date' => $currentLocationArrivalDate,
                'departure_date' => null,
                'processing_days' => $daysInCurrentLocation,
                'status' => 'current',
                'distribution_number' => null,
                'next_department' => null,
                'is_current' => true
            ];
            $totalProcessingDays += $daysInCurrentLocation;
        } else {
            // Document has been distributed - build timeline from distributions
            foreach ($distributions as $index => $distribution) {
                $arrivalDate = $distribution->received_at ?? $distribution->sent_at;
                $departureDate = $distribution->sent_at;

                // Calculate processing days in this department
                $processingDays = 0;
                if ($arrivalDate && $departureDate) {
                    $processingDays = Carbon::parse($arrivalDate)->diffInDays(Carbon::parse($departureDate));
                } elseif ($arrivalDate && !$departureDate) {
                    // Still in this department
                    $processingDays = Carbon::parse($arrivalDate)->diffInDays(now());
                }

                $isCurrent = ($index === count($distributions) - 1) && !$departureDate;

                $timeline[] = [
                    'step' => $index + 1,
                    'department' => $distribution->origin_department,
                    'department_id' => $distribution->origin_department_id,
                    'location_code' => $distribution->origin_location_code,
                    'arrival_date' => $arrivalDate,
                    'departure_date' => $departureDate,
                    'processing_days' => $processingDays,
                    'status' => $isCurrent ? 'current' : 'completed',
                    'distribution_number' => $distribution->distribution_number,
                    'next_department' => $distribution->destination_department,
                    'is_current' => $isCurrent
                ];

                $totalProcessingDays += $processingDays;
            }
        }

        // Calculate enhanced metrics
        $metrics = $this->calculateDepartmentMetrics($timeline);
        $journeySummary = $this->generateJourneySummary($document, $timeline);

        return [
            'document' => [
                'id' => $document->id,
                'number' => $documentType === 'invoice' ? $document->invoice_number : $document->document_number,
                'type' => $documentType,
                'receive_date' => $document->receive_date,
                'created_at' => $document->created_at,
                'current_location_arrival_date' => $currentLocationArrivalDate,
                'days_in_current_location' => $document->days_in_current_location
            ],
            'timeline' => $timeline,
            'total_processing_days' => $totalProcessingDays,
            'current_status' => $document->distribution_status,
            'metrics' => $metrics,
            'journey_summary' => $journeySummary
        ];
    }

    /**
     * Calculate department-specific metrics
     */
    private function calculateDepartmentMetrics($timeline)
    {
        $metrics = [
            'total_departments' => count($timeline),
            'current_department' => null,
            'longest_stay' => 0,
            'shortest_stay' => PHP_INT_MAX,
            'average_stay' => 0,
            'delayed_departments' => []
        ];

        $totalDays = 0;
        $completedSteps = array_filter($timeline, function ($step) {
            return !$step['is_current'];
        });

        foreach ($timeline as $step) {
            $processingDays = $step['processing_days'];
            $totalDays += $processingDays;

            if ($step['is_current']) {
                $metrics['current_department'] = $step['department'];
            }

            if ($processingDays > $metrics['longest_stay']) {
                $metrics['longest_stay'] = $processingDays;
            }

            if ($processingDays < $metrics['shortest_stay']) {
                $metrics['shortest_stay'] = $processingDays;
            }

            // Flag departments with processing > 14 days
            if ($processingDays > 14) {
                $metrics['delayed_departments'][] = [
                    'department' => $step['department'],
                    'days' => $processingDays,
                    'status' => $step['is_current'] ? 'current' : 'completed'
                ];
            }
        }

        if (count($completedSteps) > 0) {
            $metrics['average_stay'] = round($totalDays / count($completedSteps), 1);
        }

        if ($metrics['shortest_stay'] === PHP_INT_MAX) {
            $metrics['shortest_stay'] = 0;
        }

        return $metrics;
    }

    /**
     * Generate journey summary
     */
    private function generateJourneySummary($document, $timeline)
    {
        $summary = [
            'status' => $document->distribution_status,
            'current_location' => $document->cur_loc,
            'total_days' => array_sum(array_column($timeline, 'processing_days')),
            'departments_visited' => count($timeline),
            'is_delayed' => false,
            'recommendations' => []
        ];

        // Check if document is delayed
        $currentStep = array_filter($timeline, function ($step) {
            return $step['is_current'];
        });

        if (!empty($currentStep)) {
            $current = array_values($currentStep)[0];
            if ($current['processing_days'] > 14) {
                $summary['is_delayed'] = true;
                $summary['recommendations'][] = "Document has been in {$current['department']} for {$current['processing_days']} days. Consider expediting processing.";
            }
        }

        return $summary;
    }

    /**
     * Get department name by location code
     */
    private function getDepartmentByLocationCode($locationCode)
    {
        $department = Department::where('location_code', $locationCode)->first();
        return $department ? $department->name : $locationCode;
    }

    /**
     * Get department processing efficiency with accurate calculations
     */
    public function getDepartmentProcessingEfficiency($year, $month, $departmentId = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = DB::table('departments as dept')
            ->leftJoin('users as u', 'dept.id', '=', 'u.department_id')
            ->leftJoin('invoices as i', function ($join) use ($startDate, $endDate) {
                $join->on('u.id', '=', 'i.created_by')
                    ->where('i.receive_date', '>=', $startDate)
                    ->where('i.receive_date', '<=', $endDate);
            })
            ->leftJoin('distribution_documents as dd_i', function ($join) {
                $join->on('i.id', '=', 'dd_i.document_id')
                    ->where('dd_i.document_type', '=', 'App\\Models\\Invoice');
            })
            ->leftJoin('distributions as d_i', 'dd_i.distribution_id', '=', 'd_i.id')
            ->leftJoin('additional_documents as ad', function ($join) use ($startDate, $endDate) {
                $join->on('u.id', '=', 'ad.created_by')
                    ->whereNotNull('ad.receive_date')
                    ->where('ad.receive_date', '>=', $startDate)
                    ->where('ad.receive_date', '<=', $endDate);
            })
            ->leftJoin('distribution_documents as dd_ad', function ($join) {
                $join->on('ad.id', '=', 'dd_ad.document_id')
                    ->where('dd_ad.document_type', '=', 'App\\Models\\AdditionalDocument');
            })
            ->leftJoin('distributions as d_ad', 'dd_ad.distribution_id', '=', 'd_ad.id')
            ->when($departmentId, function ($query, $departmentId) {
                return $query->where('dept.id', $departmentId);
            })
            ->select([
                'dept.id as department_id',
                'dept.name as department_name',
                'dept.akronim',
                DB::raw('COUNT(DISTINCT i.id) as total_invoices'),
                DB::raw('COUNT(DISTINCT ad.id) as total_additional_documents'),
                DB::raw('AVG(CASE WHEN d_i.sent_at IS NOT NULL THEN DATEDIFF(d_i.sent_at, i.receive_date) END) as avg_invoice_processing_days'),
                DB::raw('AVG(CASE WHEN d_ad.sent_at IS NOT NULL THEN DATEDIFF(d_ad.sent_at, ad.receive_date) END) as avg_document_processing_days'),
                DB::raw('SUM(CASE WHEN d_i.sent_at IS NOT NULL THEN DATEDIFF(d_i.sent_at, i.receive_date) END) as total_invoice_processing_days'),
                DB::raw('SUM(CASE WHEN d_ad.sent_at IS NOT NULL THEN DATEDIFF(d_ad.sent_at, ad.receive_date) END) as total_document_processing_days')
            ])
            ->groupBy('dept.id', 'dept.name', 'dept.akronim')
            ->orderBy('avg_invoice_processing_days', 'asc');

        return $query->get();
    }

    /**
     * Get processing bottlenecks - departments with longest processing times
     */
    public function getProcessingBottlenecks($year, $month, $limit = 5)
    {
        $efficiencyData = $this->getDepartmentProcessingEfficiency($year, $month);

        return $efficiencyData
            ->where('total_invoices', '>', 0)
            ->sortByDesc('avg_invoice_processing_days')
            ->take($limit)
            ->values();
    }

    /**
     * Get documents exceeding normal processing times
     */
    public function getSlowProcessingDocuments($year, $month, $thresholdDays = 7)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get slow invoices
        $slowInvoices = DB::table('invoices as i')
            ->join('users as u', 'i.created_by', '=', 'u.id')
            ->join('departments as dept', 'u.department_id', '=', 'dept.id')
            ->leftJoin('distribution_documents as dd', function ($join) {
                $join->on('i.id', '=', 'dd.document_id')
                    ->where('dd.document_type', '=', 'App\\Models\\Invoice');
            })
            ->leftJoin('distributions as d', 'dd.distribution_id', '=', 'd.id')
            ->where('i.receive_date', '>=', $startDate)
            ->where('i.receive_date', '<=', $endDate)
            ->where(function ($query) use ($thresholdDays) {
                $query->where(function ($subQuery) use ($thresholdDays) {
                    $subQuery->whereNotNull('d.sent_at')
                        ->whereRaw('DATEDIFF(d.sent_at, i.receive_date) > ?', [$thresholdDays]);
                })
                    ->orWhere(function ($subQuery) use ($thresholdDays) {
                        $subQuery->whereNull('d.sent_at')
                            ->whereRaw('DATEDIFF(now(), i.receive_date) > ?', [$thresholdDays]);
                    });
            })
            ->select([
                'i.id',
                'i.invoice_number',
                'i.receive_date',
                'dept.name as department_name',
                DB::raw('CASE 
                    WHEN d.sent_at IS NOT NULL THEN DATEDIFF(d.sent_at, i.receive_date)
                    ELSE DATEDIFF(now(), i.receive_date)
                END as processing_days'),
                'd.status as distribution_status'
            ])
            ->orderBy('processing_days', 'desc')
            ->get();

        // Get slow additional documents
        $slowAdditionalDocs = DB::table('additional_documents as ad')
            ->join('users as u', 'ad.created_by', '=', 'u.id')
            ->join('departments as dept', 'u.department_id', '=', 'dept.id')
            ->leftJoin('distribution_documents as dd', function ($join) {
                $join->on('ad.id', '=', 'dd.document_id')
                    ->where('dd.document_type', '=', 'App\\Models\\AdditionalDocument');
            })
            ->leftJoin('distributions as d', 'dd.distribution_id', '=', 'd.id')
            ->whereNotNull('ad.receive_date')
            ->where('ad.receive_date', '>=', $startDate)
            ->where('ad.receive_date', '<=', $endDate)
            ->where(function ($query) use ($thresholdDays) {
                $query->where(function ($subQuery) use ($thresholdDays) {
                    $subQuery->whereNotNull('d.sent_at')
                        ->whereRaw('DATEDIFF(d.sent_at, ad.receive_date) > ?', [$thresholdDays]);
                })
                    ->orWhere(function ($subQuery) use ($thresholdDays) {
                        $subQuery->whereNull('d.sent_at')
                            ->whereRaw('DATEDIFF(now(), ad.receive_date) > ?', [$thresholdDays]);
                    });
            })
            ->select([
                'ad.id',
                'ad.document_number',
                'ad.receive_date',
                'dept.name as department_name',
                DB::raw('CASE 
                    WHEN d.sent_at IS NOT NULL THEN DATEDIFF(d.sent_at, ad.receive_date)
                    ELSE DATEDIFF(now(), ad.receive_date)
                END as processing_days'),
                'd.status as distribution_status'
            ])
            ->orderBy('processing_days', 'desc')
            ->get();

        return [
            'slow_invoices' => $slowInvoices,
            'slow_additional_documents' => $slowAdditionalDocs,
            'threshold_days' => $thresholdDays
        ];
    }
    public function getDepartmentMonthlyPerformance($year, $departmentId, $documentType = 'both')
    {
        $monthlyData = [];

        // Generate data for all 12 months
        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $monthData = [
                'month' => $month,
                'month_name' => $startDate->format('F'),
                'year' => $year,
                'invoices' => [],
                'additional_documents' => [],
                'total_documents' => 0,
                'avg_processing_days' => 0
            ];

            // Get invoice data for this month
            if ($documentType === 'both' || $documentType === 'invoice') {
                $invoiceStats = Invoice::select([
                    DB::raw('COUNT(*) as count'),
                    DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days'),
                    DB::raw('MIN(DATEDIFF(now(), receive_date)) as min_days'),
                    DB::raw('MAX(DATEDIFF(now(), receive_date)) as max_days')
                ])
                    ->join('users as u', 'invoices.created_by', '=', 'u.id')
                    ->where('u.department_id', $departmentId)
                    ->where('receive_date', '>=', $startDate)
                    ->where('receive_date', '<=', $endDate)
                    ->first();

                $monthData['invoices'] = [
                    'count' => $invoiceStats->count ?? 0,
                    'avg_processing_days' => round($invoiceStats->avg_processing_days ?? 0, 2),
                    'min_days' => $invoiceStats->min_days ?? 0,
                    'max_days' => $invoiceStats->max_days ?? 0
                ];
            }

            // Get additional document data for this month
            if ($documentType === 'both' || $documentType === 'additional_document') {
                $docStats = AdditionalDocument::select([
                    DB::raw('COUNT(*) as count'),
                    DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days'),
                    DB::raw('MIN(DATEDIFF(now(), receive_date)) as min_days'),
                    DB::raw('MAX(DATEDIFF(now(), receive_date)) as max_days')
                ])
                    ->join('users as u', 'additional_documents.created_by', '=', 'u.id')
                    ->where('u.department_id', $departmentId)
                    ->whereNotNull('receive_date')
                    ->where('receive_date', '>=', $startDate)
                    ->where('receive_date', '<=', $endDate)
                    ->first();

                $monthData['additional_documents'] = [
                    'count' => $docStats->count ?? 0,
                    'avg_processing_days' => round($docStats->avg_processing_days ?? 0, 2),
                    'min_days' => $docStats->min_days ?? 0,
                    'max_days' => $docStats->max_days ?? 0
                ];
            }

            // Calculate totals
            $totalCount = ($monthData['invoices']['count'] ?? 0) + ($monthData['additional_documents']['count'] ?? 0);
            $totalDays = (($monthData['invoices']['avg_processing_days'] ?? 0) * ($monthData['invoices']['count'] ?? 0)) +
                (($monthData['additional_documents']['avg_processing_days'] ?? 0) * ($monthData['additional_documents']['count'] ?? 0));

            $monthData['total_documents'] = $totalCount;
            $monthData['avg_processing_days'] = $totalCount > 0 ? round($totalDays / $totalCount, 2) : 0;

            $monthlyData[] = $monthData;
        }

        // Get department information
        $department = Department::find($departmentId);

        return [
            'department' => $department,
            'year' => $year,
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_documents' => array_sum(array_column($monthlyData, 'total_documents')),
                'avg_processing_days' => $this->calculateYearlyAverage($monthlyData),
                'best_month' => $this->getBestMonth($monthlyData),
                'worst_month' => $this->getWorstMonth($monthlyData)
            ]
        ];
    }

    private function calculateYearlyAverage($monthlyData)
    {
        $totalDays = 0;
        $totalCount = 0;

        foreach ($monthlyData as $month) {
            $totalDays += $month['avg_processing_days'] * $month['total_documents'];
            $totalCount += $month['total_documents'];
        }

        return $totalCount > 0 ? round($totalDays / $totalCount, 2) : 0;
    }

    private function getBestMonth($monthlyData)
    {
        $bestMonth = null;
        $bestScore = PHP_FLOAT_MAX;

        foreach ($monthlyData as $month) {
            if ($month['total_documents'] > 0 && $month['avg_processing_days'] < $bestScore) {
                $bestScore = $month['avg_processing_days'];
                $bestMonth = $month;
            }
        }

        return $bestMonth;
    }

    private function getWorstMonth($monthlyData)
    {
        $worstMonth = null;
        $worstScore = 0;

        foreach ($monthlyData as $month) {
            if ($month['total_documents'] > 0 && $month['avg_processing_days'] > $worstScore) {
                $worstScore = $month['avg_processing_days'];
                $worstMonth = $month;
            }
        }

        return $worstMonth;
    }

    /**
     * Get department-specific processing metrics using our new aging calculations
     * Uses current_location_arrival_date and days_in_current_location accessors
     */
    public function getDepartmentSpecificMetrics($year, $month, $department = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Validate date range is historical
        $this->validateHistoricalDateRange($startDate, $endDate);

        // Get invoices with department-specific aging
        $invoices = Invoice::with(['distributions.documents', 'creator.department'])
            ->where('receive_date', '>=', $startDate)
            ->where('receive_date', '<=', $endDate)
            ->when($department, function($query, $department) {
                return $query->whereHas('creator.department', function($q) use ($department) {
                    $q->where('id', $department);
                });
            })
            ->get();

        // Get additional documents with department-specific aging
        $additionalDocs = AdditionalDocument::with(['distributions.documents', 'creator.department'])
            ->where('receive_date', '>=', $startDate)
            ->where('receive_date', '<=', $endDate)
            ->when($department, function($query, $department) {
                return $query->whereHas('creator.department', function($q) use ($department) {
                    $q->where('id', $department);
                });
            })
            ->get();

        // Process invoices with department-specific aging
        $invoiceMetrics = $this->processDocumentMetrics($invoices, 'invoice');
        
        // Process additional documents with department-specific aging
        $additionalDocMetrics = $this->processDocumentMetrics($additionalDocs, 'additional_document');

        return [
            'invoices' => $invoiceMetrics,
            'additional_documents' => $additionalDocMetrics,
            'summary' => $this->generateDepartmentSpecificSummary($invoiceMetrics, $additionalDocMetrics),
            'period' => [
                'year' => $year,
                'month' => $month,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]
        ];
    }

    /**
     * Process document metrics using department-specific aging calculations
     */
    private function processDocumentMetrics($documents, $documentType)
    {
        $metrics = [
            'total_count' => $documents->count(),
            'department_breakdown' => [],
            'aging_categories' => [
                '0-7_days' => 0,
                '8-14_days' => 0,
                '15-30_days' => 0,
                '30_plus_days' => 0
            ],
            'current_location_metrics' => [],
            'processing_efficiency' => []
        ];

        foreach ($documents as $document) {
            $departmentId = $document->creator->department_id ?? null;
            $departmentName = $document->creator->department->name ?? 'Unknown';
            $currentLocationCode = $document->cur_loc;
            
            // Use our new department-specific aging accessors
            $daysInCurrentLocation = $document->days_in_current_location;
            $currentLocationArrivalDate = $document->current_location_arrival_date;
            $ageCategory = $document->current_location_age_category;

            // Initialize department breakdown
            if (!isset($metrics['department_breakdown'][$departmentId])) {
                $metrics['department_breakdown'][$departmentId] = [
                    'department_name' => $departmentName,
                    'total_documents' => 0,
                    'current_location_days' => [],
                    'aging_categories' => [
                        '0-7_days' => 0,
                        '8-14_days' => 0,
                        '15-30_days' => 0,
                        '30_plus_days' => 0
                    ],
                    'avg_days_in_current_location' => 0,
                    'max_days_in_current_location' => 0,
                    'min_days_in_current_location' => 0
                ];
            }

            // Update department metrics
            $metrics['department_breakdown'][$departmentId]['total_documents']++;
            $metrics['department_breakdown'][$departmentId]['current_location_days'][] = $daysInCurrentLocation;
            $metrics['department_breakdown'][$departmentId]['aging_categories'][$ageCategory]++;

            // Update overall aging categories
            $metrics['aging_categories'][$ageCategory]++;

            // Track current location metrics
            if (!isset($metrics['current_location_metrics'][$currentLocationCode])) {
                $metrics['current_location_metrics'][$currentLocationCode] = [
                    'location_code' => $currentLocationCode,
                    'total_documents' => 0,
                    'avg_days_in_location' => 0,
                    'aging_breakdown' => [
                        '0-7_days' => 0,
                        '8-14_days' => 0,
                        '15-30_days' => 0,
                        '30_plus_days' => 0
                    ]
                ];
            }

            $metrics['current_location_metrics'][$currentLocationCode]['total_documents']++;
            $metrics['current_location_metrics'][$currentLocationCode]['aging_breakdown'][$ageCategory]++;
        }

        // Calculate averages and statistics
        foreach ($metrics['department_breakdown'] as $deptId => &$deptData) {
            if (!empty($deptData['current_location_days'])) {
                $deptData['avg_days_in_current_location'] = round(array_sum($deptData['current_location_days']) / count($deptData['current_location_days']), 1);
                $deptData['max_days_in_current_location'] = max($deptData['current_location_days']);
                $deptData['min_days_in_current_location'] = min($deptData['current_location_days']);
            }
            unset($deptData['current_location_days']); // Remove raw data to save memory
        }

        foreach ($metrics['current_location_metrics'] as $locCode => &$locData) {
            if ($locData['total_documents'] > 0) {
                // Calculate weighted average based on aging categories
                $totalDays = ($locData['aging_breakdown']['0-7_days'] * 3.5) + 
                           ($locData['aging_breakdown']['8-14_days'] * 11) + 
                           ($locData['aging_breakdown']['15-30_days'] * 22.5) + 
                           ($locData['aging_breakdown']['30_plus_days'] * 45); // Assume 45+ days for 30+ category
                $locData['avg_days_in_location'] = round($totalDays / $locData['total_documents'], 1);
            }
        }

        return $metrics;
    }

    /**
     * Generate summary statistics for department-specific metrics
     */
    private function generateDepartmentSpecificSummary($invoiceMetrics, $additionalDocMetrics)
    {
        $totalDocuments = $invoiceMetrics['total_count'] + $additionalDocMetrics['total_count'];
        
        $combinedAgingCategories = [
            '0-7_days' => $invoiceMetrics['aging_categories']['0-7_days'] + $additionalDocMetrics['aging_categories']['0-7_days'],
            '8-14_days' => $invoiceMetrics['aging_categories']['8-14_days'] + $additionalDocMetrics['aging_categories']['8-14_days'],
            '15-30_days' => $invoiceMetrics['aging_categories']['15-30_days'] + $additionalDocMetrics['aging_categories']['15-30_days'],
            '30_plus_days' => $invoiceMetrics['aging_categories']['30_plus_days'] + $additionalDocMetrics['aging_categories']['30_plus_days']
        ];

        return [
            'total_documents' => $totalDocuments,
            'total_invoices' => $invoiceMetrics['total_count'],
            'total_additional_documents' => $additionalDocMetrics['total_count'],
            'aging_categories' => $combinedAgingCategories,
            'critical_documents' => $combinedAgingCategories['30_plus_days'],
            'warning_documents' => $combinedAgingCategories['15-30_days'],
            'healthy_documents' => $combinedAgingCategories['0-7_days'] + $combinedAgingCategories['8-14_days'],
            'departments_analyzed' => count($invoiceMetrics['department_breakdown']) + count($additionalDocMetrics['department_breakdown']),
            'locations_analyzed' => count($invoiceMetrics['current_location_metrics']) + count($additionalDocMetrics['current_location_metrics'])
        ];
    }

    /**
     * Get department-specific aging alerts for Dashboard 2
     */
    public function getDepartmentAgingAlerts($department = null)
    {
        $query = Invoice::with(['distributions.documents', 'creator.department']);
        
        if ($department) {
            $query->whereHas('creator.department', function($q) use ($department) {
                $q->where('id', $department);
            });
        }

        $invoices = $query->get();

        $additionalDocsQuery = AdditionalDocument::with(['distributions.documents', 'creator.department']);
        
        if ($department) {
            $additionalDocsQuery->whereHas('creator.department', function($q) use ($department) {
                $q->where('id', $department);
            });
        }

        $additionalDocs = $additionalDocsQuery->get();

        $alerts = [
            'overdue_critical' => 0,
            'overdue_warning' => 0,
            'stuck_documents' => 0,
            'recently_arrived' => 0,
            'critical_documents' => [],
            'warning_documents' => [],
            'departments_affected' => []
        ];

        // Process invoices
        foreach ($invoices as $invoice) {
            $this->processDocumentForAlerts($invoice, 'invoice', $alerts);
        }

        // Process additional documents
        foreach ($additionalDocs as $doc) {
            $this->processDocumentForAlerts($doc, 'additional_document', $alerts);
        }

        return $alerts;
    }

    /**
     * Process individual document for aging alerts
     */
    private function processDocumentForAlerts($document, $documentType, &$alerts)
    {
        $daysInCurrentLocation = $document->days_in_current_location;
        $status = $document->distribution_status;
        $departmentName = $document->creator->department->name ?? 'Unknown';
        
        if (!in_array($departmentName, $alerts['departments_affected'])) {
            $alerts['departments_affected'][] = $departmentName;
        }

        if ($daysInCurrentLocation > 30 && in_array($status, ['available', 'in_transit'])) {
            $alerts['overdue_critical']++;
            $alerts['critical_documents'][] = [
                'id' => $document->id,
                'number' => $document->invoice_number ?? $document->document_number,
                'type' => $documentType,
                'department' => $departmentName,
                'days_in_current_location' => round($daysInCurrentLocation, 1),
                'current_location' => $document->cur_loc,
                'status' => $status
            ];
        } elseif ($daysInCurrentLocation > 14 && $daysInCurrentLocation <= 30 && in_array($status, ['available', 'in_transit'])) {
            $alerts['overdue_warning']++;
            $alerts['warning_documents'][] = [
                'id' => $document->id,
                'number' => $document->invoice_number ?? $document->document_number,
                'type' => $documentType,
                'department' => $departmentName,
                'days_in_current_location' => round($daysInCurrentLocation, 1),
                'current_location' => $document->cur_loc,
                'status' => $status
            ];
        } elseif ($daysInCurrentLocation > 7 && $status === 'available') {
            $alerts['stuck_documents']++;
        } elseif ($daysInCurrentLocation <= 3) {
            $alerts['recently_arrived']++;
        }
    }

    /**
     * Get department aging breakdown for specific department
     */
    public function getDepartmentAgingBreakdown($departmentId)
    {
        $department = Department::find($departmentId);
        if (!$department) {
            return null;
        }

        // Get invoices for this department
        $invoices = Invoice::with(['distributions.documents'])
            ->whereHas('creator.department', function($query) use ($departmentId) {
                $query->where('id', $departmentId);
            })
            ->get();

        // Get additional documents for this department
        $additionalDocs = AdditionalDocument::with(['distributions.documents'])
            ->whereHas('creator.department', function($query) use ($departmentId) {
                $query->where('id', $departmentId);
            })
            ->get();

        $breakdown = [
            'department' => $department,
            'total_documents' => $invoices->count() + $additionalDocs->count(),
            'invoices' => $this->processDocumentMetrics($invoices, 'invoice'),
            'additional_documents' => $this->processDocumentMetrics($additionalDocs, 'additional_document'),
            'summary' => [
                'avg_days_in_current_location' => 0,
                'max_days_in_current_location' => 0,
                'critical_documents_count' => 0,
                'efficiency_score' => 0
            ]
        ];

        // Calculate summary statistics
        $allDaysInCurrentLocation = [];
        $criticalCount = 0;

        foreach ($invoices as $invoice) {
            $days = $invoice->days_in_current_location;
            $allDaysInCurrentLocation[] = $days;
            if ($days > 30) $criticalCount++;
        }

        foreach ($additionalDocs as $doc) {
            $days = $doc->days_in_current_location;
            $allDaysInCurrentLocation[] = $days;
            if ($days > 30) $criticalCount++;
        }

        if (!empty($allDaysInCurrentLocation)) {
            $breakdown['summary']['avg_days_in_current_location'] = round(array_sum($allDaysInCurrentLocation) / count($allDaysInCurrentLocation), 1);
            $breakdown['summary']['max_days_in_current_location'] = max($allDaysInCurrentLocation);
            
            // Calculate efficiency score (0-100, higher is better)
            $avgDays = $breakdown['summary']['avg_days_in_current_location'];
            $efficiencyScore = max(0, 100 - ($avgDays * 2)); // Penalty of 2 points per day
            $breakdown['summary']['efficiency_score'] = round($efficiencyScore, 1);
        }

        $breakdown['summary']['critical_documents_count'] = $criticalCount;

        return $breakdown;
    }
}
