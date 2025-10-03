<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DistributionDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DistributionDocumentController extends Controller
{
    /**
     * Update document status
     */
    public function updateStatus(Request $request, $documentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,missing,damaged',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $document = DistributionDocument::findOrFail($documentId);

            // Check if document can be updated
            if ($document->skip_verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document is skipped from verification'
                ], 422);
            }

            $document->update([
                'sender_verification_status' => $request->status,
                'sender_verification_notes' => $request->notes,
                'sender_verified' => true,
                'sender_verified_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document status updated successfully',
                'document' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify document
     */
    public function verify(Request $request, $documentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,missing,damaged',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $document = DistributionDocument::findOrFail($documentId);

            // Check if document can be verified
            if ($document->skip_verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document is skipped from verification'
                ], 422);
            }

            $document->update([
                'sender_verification_status' => $request->status,
                'sender_verification_notes' => $request->notes,
                'sender_verified' => true,
                'sender_verified_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document verified successfully',
                'document' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add notes to document
     */
    public function addNotes(Request $request, $documentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:1000',
            'append' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $document = DistributionDocument::findOrFail($documentId);

            $currentNotes = $document->sender_verification_notes ?? '';
            $newNotes = $request->notes;

            if ($request->append && $currentNotes) {
                $updatedNotes = $currentNotes . "\n" . $newNotes;
            } else {
                $updatedNotes = $newNotes;
            }

            $document->update([
                'sender_verification_notes' => $updatedNotes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notes added successfully',
                'document' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add notes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update document statuses
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_ids' => 'required|array',
            'document_ids.*' => 'integer|exists:distribution_documents,id',
            'status' => 'required|in:verified,missing,damaged',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updatedCount = 0;
            $skippedCount = 0;

            foreach ($request->document_ids as $documentId) {
                $document = DistributionDocument::find($documentId);

                if (!$document) {
                    continue;
                }

                if ($document->skip_verification) {
                    $skippedCount++;
                    continue;
                }

                $document->update([
                    'sender_verification_status' => $request->status,
                    'sender_verification_notes' => $request->notes,
                    'sender_verified' => true,
                    'sender_verified_at' => now()
                ]);

                $updatedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} documents" .
                    ($skippedCount > 0 ? ", {$skippedCount} documents skipped" : ""),
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk verify documents
     */
    public function bulkVerify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_ids' => 'required|array',
            'document_ids.*' => 'integer|exists:distribution_documents,id',
            'status' => 'required|in:verified,missing,damaged',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $verifiedCount = 0;
            $skippedCount = 0;

            foreach ($request->document_ids as $documentId) {
                $document = DistributionDocument::find($documentId);

                if (!$document) {
                    continue;
                }

                if ($document->skip_verification) {
                    $skippedCount++;
                    continue;
                }

                $document->update([
                    'sender_verification_status' => $request->status,
                    'sender_verification_notes' => $request->notes,
                    'sender_verified' => true,
                    'sender_verified_at' => now()
                ]);

                $verifiedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully verified {$verifiedCount} documents" .
                    ($skippedCount > 0 ? ", {$skippedCount} documents skipped" : ""),
                'verified_count' => $verifiedCount,
                'skipped_count' => $skippedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk verify: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk add notes to documents
     */
    public function bulkAddNotes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_ids' => 'required|array',
            'document_ids.*' => 'integer|exists:distribution_documents,id',
            'notes' => 'required|string|max:1000',
            'append' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updatedCount = 0;

            foreach ($request->document_ids as $documentId) {
                $document = DistributionDocument::find($documentId);

                if (!$document) {
                    continue;
                }

                $currentNotes = $document->sender_verification_notes ?? '';
                $newNotes = $request->notes;

                if ($request->append && $currentNotes) {
                    $updatedNotes = $currentNotes . "\n" . $newNotes;
                } else {
                    $updatedNotes = $newNotes;
                }

                $document->update([
                    'sender_verification_notes' => $updatedNotes
                ]);

                $updatedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully added notes to {$updatedCount} documents",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk add notes: ' . $e->getMessage()
            ], 500);
        }
    }
}
