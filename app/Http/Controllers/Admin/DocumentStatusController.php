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
     * Display the document status management page
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $statusFilter = $request->get('status', 'all');
        $documentType = $request->get('document_type', 'all');
        $search = $request->get('search', '');

        // Build queries for invoices
        $invoicesQuery = Invoice::query()
            ->with(['supplier', 'project', 'creator.department'])
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

        // Build queries for additional documents
        $additionalDocumentsQuery = AdditionalDocument::query()
            ->with(['type', 'project', 'creator.department'])
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                return $query->where('distribution_status', $statusFilter);
            })
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%")
                        ->orWhere('ito_no', 'like', "%{$search}%");
                });
            });

        // Apply department filtering for non-admin users
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
            $userLocationCode = $user->department->location_code ?? null;
            if ($userLocationCode) {
                $invoicesQuery->where('cur_loc', $userLocationCode);
                $additionalDocumentsQuery->where('cur_loc', $userLocationCode);
            }
        }

        // Get paginated results
        $invoices = $invoicesQuery->orderBy('created_at', 'desc')->paginate(15);
        $additionalDocuments = $additionalDocumentsQuery->orderBy('created_at', 'desc')->paginate(15);

        // Get status counts for filtering
        $statusCounts = $this->getStatusCounts($user);

        return view('admin.document-status.index', compact(
            'invoices',
            'additionalDocuments',
            'statusCounts',
            'statusFilter',
            'documentType',
            'search'
        ));
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

            if ($documentType === 'invoice') {
                $documents = Invoice::whereIn('id', $documentIds)
                    ->where('distribution_status', 'unaccounted_for')
                    ->get();
            } else {
                $documents = AdditionalDocument::whereIn('id', $documentIds)
                    ->where('distribution_status', 'unaccounted_for')
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
     * Get status counts for filtering
     */
    private function getStatusCounts($user): array
    {
        $userLocationCode = null;
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
            $userLocationCode = $user->department->location_code ?? null;
        }

        $invoicesQuery = Invoice::query();
        $additionalQuery = AdditionalDocument::query();

        if ($userLocationCode) {
            $invoicesQuery->where('cur_loc', $userLocationCode);
            $additionalQuery->where('cur_loc', $userLocationCode);
        }

        $statuses = ['available', 'in_transit', 'distributed', 'unaccounted_for'];
        $counts = [];

        foreach ($statuses as $status) {
            $counts[$status] = $invoicesQuery->where('distribution_status', $status)->count() +
                $additionalQuery->where('distribution_status', $status)->count();
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
            'action_performed' => 'status_reset',
            'action_details' => json_encode([
                'document_type' => $documentType,
                'document_id' => $documentId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'operation_type' => $operationType,
                'timestamp' => now()->toISOString()
            ]),
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
