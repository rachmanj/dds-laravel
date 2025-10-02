<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupplierApiController extends Controller
{
    /**
     * Get all SAP codes from suppliers
     */
    public function getSapCodes(): JsonResponse
    {
        try {
            $suppliers = Supplier::whereNotNull('sap_code')
                ->where('sap_code', '!=', '')
                ->where('is_active', true)
                ->select('id', 'sap_code', 'name')
                ->orderBy('sap_code')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $suppliers,
                'message' => 'SAP codes retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve SAP codes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate vendor code against supplier SAP codes
     */
    public function validateVendorCode(Request $request): JsonResponse
    {
        $request->validate([
            'vendor_code' => 'required|string|max:50'
        ]);

        try {
            $vendorCode = $request->vendor_code;

            $suppliers = Supplier::where('sap_code', $vendorCode)
                ->where('is_active', true)
                ->select('id', 'sap_code', 'name')
                ->get();

            $matchFound = $suppliers->count() > 0;
            $multipleMatches = $suppliers->count() > 1;

            return response()->json([
                'success' => true,
                'data' => [
                    'vendor_code' => $vendorCode,
                    'match_found' => $matchFound,
                    'multiple_matches' => $multipleMatches,
                    'suppliers' => $suppliers,
                    'supplier_count' => $suppliers->count()
                ],
                'message' => $matchFound
                    ? ($multipleMatches ? 'Multiple suppliers found' : 'Single supplier found')
                    : 'No matching suppliers found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate vendor code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get supplier by ID
     */
    public function getSupplier($id): JsonResponse
    {
        try {
            $supplier = Supplier::where('id', $id)
                ->where('is_active', true)
                ->select('id', 'sap_code', 'name', 'type', 'city')
                ->first();

            if (!$supplier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Supplier retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get PO suggestions based on vendor code and supplier SAP code
     */
    public function getPoSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'vendor_code' => 'required|string|max:50'
        ]);

        try {
            $vendorCode = $request->vendor_code;

            // Get PO numbers from additional documents where:
            // 1. vendor_code matches the provided vendor_code
            // 2. status is 'open' (available for use)
            // 3. po_no is not null/empty
            $poSuggestions = \App\Models\AdditionalDocument::where('vendor_code', $vendorCode)
                ->where('status', 'open')
                ->whereNotNull('po_no')
                ->where('po_no', '!=', '')
                ->select('id', 'po_no', 'document_number', 'document_date', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'vendor_code' => $vendorCode,
                    'suggestions' => $poSuggestions,
                    'count' => $poSuggestions->count()
                ],
                'message' => $poSuggestions->count() > 0
                    ? 'PO suggestions found'
                    : 'No PO suggestions available for this vendor code'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get PO suggestions: ' . $e->getMessage()
            ], 500);
        }
    }
}
