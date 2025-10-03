<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\DistributionDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Store distribution analytics data
     */
    public function storeDistributionAnalytics(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            // Store analytics data (you can create a dedicated table for this)
            // For now, we'll log it and return success

            \Log::info('Distribution Analytics Data', [
                'user_id' => auth()->id(),
                'distribution_id' => $data['distribution_id'] ?? null,
                'metrics' => $data['metrics'] ?? [],
                'real_time_data' => $data['realTimeData'] ?? [],
                'predictive_models' => $data['predictiveModels'] ?? [],
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Analytics data stored successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store analytics data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distribution performance metrics
     */
    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        try {
            $distributionId = $request->get('distribution_id');

            if (!$distributionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution ID is required'
                ], 400);
            }

            $distribution = Distribution::with('documents')->find($distributionId);

            if (!$distribution) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution not found'
                ], 404);
            }

            $metrics = $this->calculatePerformanceMetrics($distribution);

            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user behavior analytics
     */
    public function getUserBehaviorAnalytics(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $days = $request->get('days', 30);

            $startDate = Carbon::now()->subDays($days);

            // Get user's distribution activities
            $activities = DB::table('distribution_documents')
                ->join('distributions', 'distribution_documents.distribution_id', '=', 'distributions.id')
                ->where('distributions.created_by', $userId)
                ->where('distributions.created_at', '>=', $startDate)
                ->select([
                    'distribution_documents.sender_verification_status',
                    'distribution_documents.sender_verified_at',
                    'distributions.status as distribution_status',
                    'distributions.created_at'
                ])
                ->get();

            $analytics = $this->analyzeUserBehavior($activities);

            return response()->json([
                'success' => true,
                'analytics' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user behavior analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document flow analytics
     */
    public function getDocumentFlowAnalytics(Request $request): JsonResponse
    {
        try {
            $distributionId = $request->get('distribution_id');

            if (!$distributionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution ID is required'
                ], 400);
            }

            $flowData = DB::table('distribution_documents')
                ->where('distribution_id', $distributionId)
                ->select([
                    'sender_verification_status',
                    'sender_verified_at',
                    'receiver_verification_status',
                    'receiver_verified_at',
                    'skip_verification',
                    'created_at'
                ])
                ->get();

            $analytics = $this->analyzeDocumentFlow($flowData);

            return response()->json([
                'success' => true,
                'analytics' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get document flow analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time dashboard data
     */
    public function getRealTimeDashboard(Request $request): JsonResponse
    {
        try {
            $distributionId = $request->get('distribution_id');

            if (!$distributionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution ID is required'
                ], 400);
            }

            $dashboardData = $this->getDashboardData($distributionId);

            return response()->json([
                'success' => true,
                'dashboard' => $dashboardData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get predictive analytics
     */
    public function getPredictiveAnalytics(Request $request): JsonResponse
    {
        try {
            $distributionId = $request->get('distribution_id');

            if (!$distributionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution ID is required'
                ], 400);
            }

            $predictions = $this->calculatePredictions($distributionId);

            return response()->json([
                'success' => true,
                'predictions' => $predictions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get predictive analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate performance metrics for a distribution
     */
    private function calculatePerformanceMetrics($distribution)
    {
        $documents = $distribution->documents;
        $totalDocuments = $documents->count();
        $verifiedDocuments = $documents->where('sender_verified', true)->count();
        $skippedDocuments = $documents->where('skip_verification', true)->count();

        $completionRate = $totalDocuments > 0 ? ($verifiedDocuments / $totalDocuments) * 100 : 0;

        // Calculate average verification time
        $verificationTimes = $documents->whereNotNull('sender_verified_at')
            ->map(function ($doc) {
                return $doc->sender_verified_at->diffInSeconds($doc->created_at);
            });

        $averageVerificationTime = $verificationTimes->count() > 0
            ? $verificationTimes->avg()
            : 0;

        // Calculate error rate
        $errorCount = $documents->whereIn('sender_verification_status', ['missing', 'damaged'])->count();
        $errorRate = $totalDocuments > 0 ? ($errorCount / $totalDocuments) * 100 : 0;

        return [
            'total_documents' => $totalDocuments,
            'verified_documents' => $verifiedDocuments,
            'skipped_documents' => $skippedDocuments,
            'completion_rate' => round($completionRate, 2),
            'average_verification_time' => round($averageVerificationTime, 2),
            'error_rate' => round($errorRate, 2),
            'distribution_status' => $distribution->status,
            'created_at' => $distribution->created_at,
            'updated_at' => $distribution->updated_at
        ];
    }

    /**
     * Analyze user behavior
     */
    private function analyzeUserBehavior($activities)
    {
        $totalActivities = $activities->count();
        $verifiedActivities = $activities->where('sender_verification_status', 'verified')->count();
        $errorActivities = $activities->whereIn('sender_verification_status', ['missing', 'damaged'])->count();

        $verificationRate = $totalActivities > 0 ? ($verifiedActivities / $totalActivities) * 100 : 0;
        $errorRate = $totalActivities > 0 ? ($errorActivities / $totalActivities) * 100 : 0;

        // Group by date
        $dailyActivities = $activities->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('Y-m-d');
        });

        $dailyStats = $dailyActivities->map(function ($dayActivities) {
            return [
                'total' => $dayActivities->count(),
                'verified' => $dayActivities->where('sender_verification_status', 'verified')->count(),
                'errors' => $dayActivities->whereIn('sender_verification_status', ['missing', 'damaged'])->count()
            ];
        });

        return [
            'total_activities' => $totalActivities,
            'verification_rate' => round($verificationRate, 2),
            'error_rate' => round($errorRate, 2),
            'daily_stats' => $dailyStats,
            'efficiency_score' => $this->calculateEfficiencyScore($activities)
        ];
    }

    /**
     * Analyze document flow
     */
    private function analyzeDocumentFlow($flowData)
    {
        $totalDocuments = $flowData->count();
        $verifiedDocuments = $flowData->where('sender_verification_status', 'verified')->count();
        $skippedDocuments = $flowData->where('skip_verification', true)->count();

        $statusDistribution = $flowData->groupBy('sender_verification_status');

        $flowMetrics = [
            'total_documents' => $totalDocuments,
            'verified_documents' => $verifiedDocuments,
            'skipped_documents' => $skippedDocuments,
            'status_distribution' => $statusDistribution->map->count(),
            'completion_rate' => $totalDocuments > 0 ? ($verifiedDocuments / $totalDocuments) * 100 : 0
        ];

        return $flowMetrics;
    }

    /**
     * Get dashboard data
     */
    private function getDashboardData($distributionId)
    {
        $distribution = Distribution::with('documents')->find($distributionId);

        if (!$distribution) {
            return null;
        }

        $documents = $distribution->documents;
        $totalDocuments = $documents->count();
        $verifiedDocuments = $documents->where('sender_verified', true)->count();

        $completionRate = $totalDocuments > 0 ? ($verifiedDocuments / $totalDocuments) * 100 : 0;

        // Calculate estimated completion time
        $remainingDocuments = $totalDocuments - $verifiedDocuments;
        $averageTimePerDocument = 30; // 30 seconds baseline
        $estimatedCompletion = $remainingDocuments * $averageTimePerDocument;

        return [
            'distribution_id' => $distributionId,
            'status' => $distribution->status,
            'completion_rate' => round($completionRate, 2),
            'total_documents' => $totalDocuments,
            'verified_documents' => $verifiedDocuments,
            'remaining_documents' => $remainingDocuments,
            'estimated_completion_seconds' => $estimatedCompletion,
            'last_updated' => now()
        ];
    }

    /**
     * Calculate predictions
     */
    private function calculatePredictions($distributionId)
    {
        $distribution = Distribution::with('documents')->find($distributionId);

        if (!$distribution) {
            return null;
        }

        $documents = $distribution->documents;
        $totalDocuments = $documents->count();
        $verifiedDocuments = $documents->where('sender_verified', true)->count();

        // Simple prediction based on current rate
        $completionRate = $totalDocuments > 0 ? ($verifiedDocuments / $totalDocuments) * 100 : 0;
        $remainingDocuments = $totalDocuments - $verifiedDocuments;

        $averageTimePerDocument = 30; // 30 seconds baseline
        $estimatedCompletion = $remainingDocuments * $averageTimePerDocument;

        // Calculate error probability based on historical data
        $errorCount = $documents->whereIn('sender_verification_status', ['missing', 'damaged'])->count();
        $errorProbability = $totalDocuments > 0 ? ($errorCount / $totalDocuments) * 100 : 0;

        // Calculate efficiency score
        $efficiencyScore = $this->calculateEfficiencyScore($documents);

        return [
            'completion_time_prediction' => $estimatedCompletion,
            'error_probability' => round($errorProbability, 2),
            'efficiency_score' => $efficiencyScore,
            'completion_rate' => round($completionRate, 2),
            'remaining_documents' => $remainingDocuments
        ];
    }

    /**
     * Calculate efficiency score
     */
    private function calculateEfficiencyScore($documents)
    {
        if ($documents->count() === 0) {
            return 0;
        }

        $verifiedDocuments = $documents->where('sender_verified', true)->count();
        $errorDocuments = $documents->whereIn('sender_verification_status', ['missing', 'damaged'])->count();

        $verificationRate = ($verifiedDocuments / $documents->count()) * 100;
        $errorRate = ($errorDocuments / $documents->count()) * 100;

        // Efficiency score based on verification rate and error rate
        $efficiencyScore = $verificationRate - ($errorRate * 2); // Penalize errors more

        return max(0, min(100, round($efficiencyScore, 2)));
    }
}
