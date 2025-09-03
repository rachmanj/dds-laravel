<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\DistributionHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:reset-document-status');
    }

    /**
     * Display the main document status management page with tabs
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // Debug logging
            Log::info('Document status main page accessed', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'department_location_code' => $user->department_location_code,
                'roles' => $user->roles->pluck('name')->toArray()
            ]);

            // Get status counts for overview
            $statusCounts = $this->getStatusCounts($user);

            return view('admin.document-status.index', compact('statusCounts'));
        } catch (\Exception $e) {
            Log::error('Document status main page error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->view('errors.500', [], 500);
        }
    }

    /**
     * Display the invoice document status management page
     */
    public function invoices(Request $request)
    {
        try {
            $user = Auth::user();
            $statusFilter = $request->get('status', 'all');
            $search = $request->get('search', '');

            // Debug logging
            Log::info('Invoice document status page accessed', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'department_location_code' => $user->department_location_code,
                'roles' => $user->roles->pluck('name')->toArray()
            ]);

            // Build query for invoices
            $invoicesQuery = Invoice::query()
                ->with(['supplier', 'creator'])
                ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                    return $query->where('distribution_status', $statusFilter);
                })
                ->when($search, function ($query) use ($search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('invoice_number', 'like', "%{$search}%")
                            ->orWhere('po_no', 'like', "%{$search}%")
                            ->orWhereHas('supplier', function ($sq) use ($search) {
                                $sq->where('name', 'like', "%{$search}%");
                            });
                    });
                });

            // Apply department filtering for non-admin users
            if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
                $userLocationCode = $user->department_location_code;
                if ($userLocationCode) {
                    $invoicesQuery->where('cur_loc', $userLocationCode);
                }
            }

            // Get paginated results
            $invoices = $invoicesQuery->orderBy('created_at', 'desc')->paginate(15);

            // Get status counts for invoices only
            $statusCounts = $this->getInvoiceStatusCounts($user);

            // Debug logging for view data
            Log::info('Invoice document status view data', [
                'invoices_count' => $invoices->count(),
                'status_counts' => $statusCounts,
                'status_filter' => $statusFilter,
                'search' => $search
            ]);

            return view('admin.document-status.invoices', compact(
                'invoices',
                'statusCounts',
                'statusFilter',
                'search'
            ));
        } catch (\Exception $e) {
            Log::error('Invoice document status page error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->view('errors.500', [], 500);
        }
    }

    /**
     * Display the additional document status management page
     */
    public function additionalDocuments(Request $request)
    {
        try {
            $user = Auth::user();
            $statusFilter = $request->get('status', 'all');
            $search = $request->get('search', '');

            // Debug logging
            Log::info('Additional document status page accessed', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'department_location_code' => $user->department_location_code,
                'roles' => $user->roles->pluck('name')->toArray()
            ]);

            // Build query for additional documents
            $additionalDocumentsQuery = AdditionalDocument::query()
                ->with(['type', 'creator'])
                ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                    return $query->where('distribution_status', $statusFilter);
                })
                ->when($search, function ($query) use ($search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('document_number', 'like', "%{$search}%")
                            ->orWhere('po_no', 'like', "%{$search}%");
                    });
                });

            // Apply department filtering for non-admin users
            if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
                $userLocationCode = $user->department_location_code;
                if ($userLocationCode) {
                    $additionalDocumentsQuery->where('cur_loc', $userLocationCode);
                }
            }

            // Get paginated results
            $additionalDocuments = $additionalDocumentsQuery->orderBy('created_at', 'desc')->paginate(15);

            // Get status counts for additional documents only
            $statusCounts = $this->getAdditionalDocumentStatusCounts($user);

            // Debug logging for view data
            Log::info('Additional document status view data', [
                'additional_documents_count' => $additionalDocuments->count(),
                'status_counts' => $statusCounts,
                'status_filter' => $statusFilter,
                'search' => $search
            ]);

            return view('admin.document-status.additional-documents', compact(
                'additionalDocuments',
                'statusCounts',
                'statusFilter',
                'search'
            ));
        } catch (\Exception $e) {
            Log::error('Additional document status page error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->view('errors.500', [], 500);
        }
    }

    /**
     * Reset individual document status
     */
    public function resetStatus(Request $request): JsonResponse
    {
        $request->validate([
            'document_id' => 'required|integer',
            'document_type' => 'required|in:invoice,additional_document',
            'new_status' => 'required|in:available,in_transit,distributed,unaccounted_for',
            'reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $documentId = $request->document_id;
            $documentType = $request->document_type;
            $newStatus = $request->new_status;
            $reason = $request->reason;

            if ($documentType === 'invoice') {
                $document = Invoice::findOrFail($documentId);
            } else {
                $document = AdditionalDocument::findOrFail($documentId);
            }

            $oldStatus = $document->distribution_status;

            // Update document status
            $document->update(['distribution_status' => $newStatus]);

            // Log the status change for audit purposes
            $this->logStatusChange($document, $user, $oldStatus, $newStatus, $reason, 'individual');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document status updated successfully',
                'document' => $document->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset document status', [
                'error' => $e->getMessage(),
                'document_id' => $request->document_id,
                'document_type' => $request->document_type,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update document status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk reset document statuses (only unaccounted_for → available)
     */
    public function bulkResetStatus(Request $request): JsonResponse
    {
        $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'integer',
            'document_type' => 'required|in:invoice,additional_document',
            'reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $documentIds = $request->document_ids;
            $documentType = $request->document_type;
            $reason = $request->reason;

            $updatedCount = 0;
            $skippedCount = 0;

            // Apply department filtering for non-admin users
            $userLocationCode = null;
            if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
                $userLocationCode = $user->department_location_code;
            }

            if ($documentType === 'invoice') {
                $documents = Invoice::whereIn('id', $documentIds)
                    ->when($userLocationCode, function ($query) use ($userLocationCode) {
                        return $query->where('cur_loc', $userLocationCode);
                    })
                    ->get();
            } else {
                $documents = AdditionalDocument::whereIn('id', $documentIds)
                    ->when($userLocationCode, function ($query) use ($userLocationCode) {
                        return $query->where('cur_loc', $userLocationCode);
                    })
                    ->get();
            }

            foreach ($documents as $document) {
                $oldStatus = $document->distribution_status;

                // Only allow unaccounted_for → available for bulk operations
                if ($oldStatus === 'unaccounted_for') {
                    $document->update(['distribution_status' => 'available']);

                    // Log the status change for audit purposes
                    $this->logStatusChange($document, $user, $oldStatus, 'available', $reason, 'bulk');

                    $updatedCount++;
                } else {
                    $skippedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} documents. Skipped {$skippedCount} documents (not eligible for bulk reset).",
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk reset document statuses', [
                'error' => $e->getMessage(),
                'document_ids' => $request->document_ids,
                'document_type' => $request->document_type,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update document statuses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get status counts for all document types (combined)
     */
    private function getStatusCounts($user): array
    {
        $userLocationCode = null;
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
            $userLocationCode = $user->department_location_code;
        }

        $statuses = ['available', 'in_transit', 'distributed', 'unaccounted_for'];
        $counts = [];

        foreach ($statuses as $status) {
            $counts[$status] = Invoice::where('distribution_status', $status)
                ->when($userLocationCode, function ($query) use ($userLocationCode) {
                    return $query->where('cur_loc', $userLocationCode);
                })
                ->count() +
                AdditionalDocument::where('distribution_status', $status)
                ->when($userLocationCode, function ($query) use ($userLocationCode) {
                    return $query->where('cur_loc', $userLocationCode);
                })
                ->count();
        }

        return $counts;
    }

    /**
     * Get status counts for invoices only
     */
    private function getInvoiceStatusCounts($user): array
    {
        $userLocationCode = null;
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
            $userLocationCode = $user->department_location_code;
        }

        $statuses = ['available', 'in_transit', 'distributed', 'unaccounted_for'];
        $counts = [];

        foreach ($statuses as $status) {
            $counts[$status] = Invoice::where('distribution_status', $status)
                ->when($userLocationCode, function ($query) use ($userLocationCode) {
                    return $query->where('cur_loc', $userLocationCode);
                })
                ->count();
        }

        return $counts;
    }

    /**
     * Get status counts for additional documents only
     */
    private function getAdditionalDocumentStatusCounts($user): array
    {
        $userLocationCode = null;
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
            $userLocationCode = $user->department_location_code;
        }

        $statuses = ['available', 'in_transit', 'distributed', 'unaccounted_for'];
        $counts = [];

        foreach ($statuses as $status) {
            $counts[$status] = AdditionalDocument::where('distribution_status', $status)
                ->when($userLocationCode, function ($query) use ($userLocationCode) {
                    return $query->where('cur_loc', $userLocationCode);
                })
                ->count();
        }

        return $counts;
    }

    /**
     * Log status changes for audit purposes
     */
    private function logStatusChange($document, $user, $oldStatus, $newStatus, $reason, $operationType): void
    {
        $documentType = get_class($document);
        $documentId = $document->id;

        // Log to DistributionHistory for audit trail
        DistributionHistory::create([
            'distribution_id' => null, // Not tied to a specific distribution
            'user_id' => $user->id,
            'action' => 'status_reset',
            'action_type' => 'status_management', // Required field for status reset operations
            'metadata' => [
                'document_type' => $documentType,
                'document_id' => $documentId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'operation_type' => $operationType,
                'timestamp' => now()->toISOString()
            ],
            'action_performed_at' => now()
        ]);

        // Also log to Laravel log for system monitoring
        Log::info('Document status reset', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'operation_type' => $operationType,
            'timestamp' => now()
        ]);
    }
}
