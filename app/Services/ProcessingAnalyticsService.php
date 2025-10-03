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
    public function getMonthlyProcessingDays($year, $month, $department = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

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
        $startDate = Carbon::now()->subMonths($monthsBack)->startOfMonth();

        $trendData = [];
        for ($i = 0; $i < $monthsBack; $i++) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $monthData = $this->getMonthlyProcessingDays($currentMonth->year, $currentMonth->month);

            $trendData[] = [
                'year' => $currentMonth->year,
                'month' => $currentMonth->month,
                'month_name' => $currentMonth->format('F Y'),
                'data' => $monthData
            ];
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
                        ->where('created_by', DB::raw('departments.id'))
                        ->where('receive_date', '>=', $startDate)
                        ->where('receive_date', '<=', $endDate);
                })
                    ->orWhereExists(function ($subQuery) use ($startDate, $endDate) {
                        $subQuery->select(DB::raw(1))
                            ->from('additional_documents')
                            ->where('created_by', DB::raw('departments.id'))
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
     * Get individual document processing timeline
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
                'dest_dept.id as destination_department_id'
            ])
            ->orderBy('d.sent_at', 'asc')
            ->get();

        $timeline = [];
        $currentDate = $document->receive_date;

        foreach ($distributions as $index => $distribution) {
            $processingDays = $distribution->sent_at ?
                Carbon::parse($currentDate)->diffInDays(Carbon::parse($distribution->sent_at)) : 0;

            $timeline[] = [
                'step' => $index + 1,
                'department' => $distribution->origin_department,
                'department_id' => $distribution->origin_department_id,
                'start_date' => $currentDate,
                'end_date' => $distribution->sent_at,
                'processing_days' => $processingDays,
                'status' => $distribution->status,
                'distribution_number' => $distribution->distribution_number,
                'next_department' => $distribution->destination_department
            ];

            $currentDate = $distribution->received_at ?? $distribution->sent_at;
        }

        // Add current department if document is still in process
        if (!empty($distributions)) {
            $lastDistribution = $distributions->last();
            if ($lastDistribution && $lastDistribution->status !== 'completed') {
                $currentProcessingDays = $lastDistribution->received_at ?
                    Carbon::parse($lastDistribution->received_at)->diffInDays(now()) : 0;

                $timeline[] = [
                    'step' => count($timeline) + 1,
                    'department' => $lastDistribution->destination_department,
                    'department_id' => $lastDistribution->destination_department_id,
                    'start_date' => $lastDistribution->received_at,
                    'end_date' => null,
                    'processing_days' => $currentProcessingDays,
                    'status' => 'in_progress',
                    'distribution_number' => null,
                    'next_department' => null
                ];
            }
        }

        return [
            'document' => [
                'id' => $document->id,
                'number' => $documentType === 'invoice' ? $document->invoice_number : $document->document_number,
                'type' => $documentType,
                'receive_date' => $document->receive_date,
                'created_at' => $document->created_at
            ],
            'timeline' => $timeline,
            'total_processing_days' => array_sum(array_column($timeline, 'processing_days')),
            'current_status' => !empty($distributions) && $distributions->last() ? $distributions->last()->status : 'not_distributed'
        ];
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
}
