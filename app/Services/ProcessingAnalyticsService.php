<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\Distribution;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessingAnalyticsService
{
    public function getMonthlyProcessingDays($year, $month, $department = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = Invoice::select([
            DB::raw('created_by as department_id'),
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days')
        ])
        ->where('receive_date', '>=', $startDate)
        ->where('receive_date', '<=', $endDate)
        ->when($department, function($query, $department) {
            return $query->where('created_by', $department);
        })
        ->groupBy('created_by');

        $invoiceStats = $query->get();

        $additionalDocStats = AdditionalDocument::select([
            DB::raw('created_by as department_id'),
            DB::raw('COUNT(*) as document_count'),
            DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days')
        ])
        ->whereNotNull('receive_date')
        ->where('receive_date', '>=', $startDate)
        ->where('receive_date', '<=', $endDate)
        ->when($department, function($query, $department) {
            return $query->where('created_by', $department);
        })
        ->groupBy('created_by')
        ->get();

        return [
            'invoices' => $invoiceStats,
            'additional_documents' => $additionalDocStats,
            'departments' => $this->getDepartmentSummary($startDate, $endDate)
        ];
    }

    public function getDepartmentProcessingDays($departmentId, $year = null, $month = null)
    {
        $query = Invoice::where('created_by', $departmentId)
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

        $additionalDocStats = AdditionalDocument::where('created_by', $departmentId)
            ->whereNotNull('receive_date')
            ->when($year && $month, function($query) use ($startDate, $endDate) {
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
                'created_by as department_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days'),
                DB::raw('SUM(DATEDIFF(now(), receive_date)) as total_processing_days')
            ])
            ->where('receive_date', '>=', $startDate)
            ->where('receive_date', '<=', $endDate)
            ->groupBy('created_by')
            ->get();
        }

        if ($documentType === 'both' || $documentType === 'additional_document') {
            $data['additional_documents'] = AdditionalDocument::select([
                'created_by as department_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(DATEDIFF(now(), receive_date)) as avg_processing_days'),
                DB::raw('SUM(DATEDIFF(now(), receive_date)) as total_processing_days')
            ])
            ->whereNotNull('receive_date')
            ->where('receive_date', '>=', $startDate)
            ->where('receive_date', '<=', $endDate)
            ->groupBy('created_by')
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
                $invoiceStats = Invoice::where('created_by', $department->id)
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
                $additionalDocStats = AdditionalDocument::where('created_by', $department->id)
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
            Invoice::select('created_by', DB::raw('COUNT(*) as count'))
                ->where('receive_date', '>=', $startDate)
                ->where('receive_date', '<=', $endDate)
                ->groupBy('created_by'),
            'invoice_count',
            'departments.id',
            '=',
            'invoice_count.created_by'
        )
        ->leftJoinSub(
            AdditionalDocument::select('created_by', DB::raw('COUNT(*) as count'))
                ->whereNotNull('receive_date')
                ->where('receive_date', '>=', $startDate)
                ->where('receive_date', '<=', $endDate)
                ->groupBy('created_by'),
            'doc_count',
            'departments.id',
            '=',
            'doc_count.created_by'
        )
        ->where(function($query) use ($startDate, $endDate) {
            $query->whereExists(function($subQuery) use ($startDate, $endDate) {
                $subQuery->select(DB::raw(1))
                    ->from('invoices')
                    ->where('created_by', DB::raw('departments.id'))
                    ->where('receive_date', '>=', $startDate)
                    ->where('receive_date', '<=', $endDate);
            })
            ->orWhereExists(function($subQuery) use ($startDate, $endDate) {
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
}