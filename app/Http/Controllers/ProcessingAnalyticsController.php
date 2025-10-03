<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProcessingAnalyticsService;
use App\Models\Invoice;
use App\Models\AdditionalDocument;
use Carbon\Carbon;

class ProcessingAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(ProcessingAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index()
    {
        return view('processing-analytics.index');
    }

    public function getMonthlyProcessing($year, $month)
    {
        try {
            $data = $this->analyticsService->getMonthlyProcessingDays($year, $month);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'year' => $year,
                'month' => $month
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDepartmentProcessing($departmentId, $year = null, $month = null)
    {
        try {
            $data = $this->analyticsService->getDepartmentProcessingDays($departmentId, $year, $month);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'department_id' => $departmentId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProcessingTrends($monthsBack = 6)
    {
        try {
            $data = $this->analyticsService->getProcessingTrends($monthsBack);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'months_back' => $monthsBack
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMonthlyOverview(Request $request)
    {
        try {
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);
            $documentType = $request->get('document_type', 'both'); // invoice, additional_document, both

            $data = $this->analyticsService->getMonthlyOverview($year, $month, $documentType);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => [
                    'year' => $year,
                    'month' => $month,
                    'document_type' => $documentType
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDepartmentEfficiency(Request $request)
    {
        try {
            $year = $request->get('year', now()->year);
            $documentType = $request->get('document_type', 'both');

            $data = $this->analyticsService->getDepartmentEfficiency($year, $documentType);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => [
                    'year' => $year,
                    'document_type' => $documentType
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportMonthlyReport(Request $request)
    {
        try {
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);
            $format = $request->get('format', 'excel'); // excel, pdf, csv

            $filePath = $this->analyticsService->exportMonthlyReport($year, $month, $format);
            
            return response()->download($filePath);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}