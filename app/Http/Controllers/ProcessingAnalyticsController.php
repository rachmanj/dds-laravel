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

    /**
     * Get accurate processing days based on distribution workflow
     */
    public function getAccurateProcessingDays(Request $request)
    {
        try {
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);
            $department = $request->get('department');

            $data = $this->analyticsService->getAccurateProcessingDays($year, $month, $department);

            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => [
                    'year' => $year,
                    'month' => $month,
                    'department' => $department
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get individual document processing timeline
     */
    public function getDocumentTimeline(Request $request)
    {
        try {
            $documentId = $request->get('document_id');
            $documentType = $request->get('document_type', 'invoice');

            if (!$documentId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Document ID is required'
                ], 400);
            }

            $data = $this->analyticsService->getDocumentProcessingTimeline($documentId, $documentType);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'error' => 'Document not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department processing efficiency with accurate calculations
     */
    public function getDepartmentEfficiencyAccurate(Request $request)
    {
        try {
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);
            $departmentId = $request->get('department_id');

            $data = $this->analyticsService->getDepartmentProcessingEfficiency($year, $month, $departmentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => [
                    'year' => $year,
                    'month' => $month,
                    'department_id' => $departmentId
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get processing bottlenecks
     */
    public function getProcessingBottlenecks(Request $request)
    {
        try {
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);
            $limit = $request->get('limit', 5);

            $data = $this->analyticsService->getProcessingBottlenecks($year, $month, $limit);

            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => [
                    'year' => $year,
                    'month' => $month,
                    'limit' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get documents exceeding normal processing times
     */
    public function getSlowProcessingDocuments(Request $request)
    {
        try {
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);
            $thresholdDays = $request->get('threshold_days', 7);

            $data = $this->analyticsService->getSlowProcessingDocuments($year, $month, $thresholdDays);

            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => [
                    'year' => $year,
                    'month' => $month,
                    'threshold_days' => $thresholdDays
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly performance for selected department
     */
    public function getDepartmentMonthlyPerformance(Request $request)
    {
        try {
            $year = $request->get('year', now()->year);
            $departmentId = $request->get('department_id');
            $documentType = $request->get('document_type', 'both');

            if (!$departmentId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Department ID is required'
                ], 400);
            }

            $data = $this->analyticsService->getDepartmentMonthlyPerformance($year, $departmentId, $documentType);

            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => [
                    'year' => $year,
                    'department_id' => $departmentId,
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

    /**
     * Document journey tracking page
     */
    public function documentJourney(Request $request)
    {
        $documentId = $request->get('document_id');
        $documentType = $request->get('document_type', 'invoice');

        return view('processing-analytics.document-journey', compact('documentId', 'documentType'));
    }
}
