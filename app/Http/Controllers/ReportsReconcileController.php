<?php

namespace App\Http\Controllers;

use App\Models\ReconcileDetail;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Imports\ReconcileDetailImport;
use App\Exports\ReconcileExport;
use App\Exports\ReconcileTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ReportsReconcileController extends Controller
{
    /**
     * Display the reconcile data page.
     */
    public function index(): View
    {
        return view('reports.reconcile.index');
    }

    /**
     * Handle Excel file upload and import.
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_upload' => 'required|mimes:xls,xlsx|max:10240', // 10MB max
            'vendor_id' => 'required|exists:suppliers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file_upload');
            $fileName = time() . '_' . $file->getClientOriginalName();

            // Store file temporarily
            $file->storeAs('file_upload', $fileName, 'public');

            // Import data with temporary flag
            Excel::import(new ReconcileDetailImport, storage_path('app/public/file_upload/' . $fileName));

            // Update flag and assign vendor_id
            $tempFlag = 'TEMP' . Auth::id();
            $importedCount = ReconcileDetail::where('flag', $tempFlag)->count();

            ReconcileDetail::where('flag', $tempFlag)
                ->update([
                    'vendor_id' => $request->vendor_id,
                    'flag' => null
                ]);

            // Clean up uploaded file
            Storage::disk('public')->delete('file_upload/' . $fileName);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and processed successfully!',
                'imported_count' => $importedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user's own reconcile data.
     */
    public function deleteMine()
    {
        try {
            $deletedCount = ReconcileDetail::where('user_id', Auth::id())->delete();

            // If it's an AJAX request, return JSON
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Deleted {$deletedCount} reconcile records successfully!"
                ]);
            }

            // Otherwise, redirect with success message
            return redirect()->route('reconcile.index')
                ->with('success', "Deleted {$deletedCount} reconcile records successfully!");
        } catch (\Exception $e) {
            Log::error('Error deleting reconcile data: ' . $e->getMessage());

            // If it's an AJAX request, return JSON error
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting data: ' . $e->getMessage()
                ], 500);
            }

            // Otherwise, redirect with error message
            return redirect()->back()
                ->with('error', 'Error deleting data: ' . $e->getMessage());
        }
    }

    /**
     * Export reconcile data to Excel.
     */
    public function export()
    {
        $reconciles = ReconcileDetail::forUser(Auth::id())
            ->withoutFlag()
            ->with(['supplier', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Excel::download(new ReconcileExport($reconciles), 'reconcile_data_' . date('Y-m-d_H-i-s') . '.xlsx');
    }

    /**
     * Get data for DataTables AJAX request.
     */
    public function data(): JsonResponse
    {
        $reconciles = ReconcileDetail::forUser(Auth::id())
            ->withoutFlag()
            ->with(['supplier', 'user'])
            ->orderBy('created_at', 'desc');

        return DataTables::of($reconciles)
            ->addColumn('reconciliation_data', function ($reconcile) {
                return $reconcile->reconciliation_data;
            })
            ->addColumn('supplier_name', function ($reconcile) {
                return $reconcile->supplier ? $reconcile->supplier->name : 'N/A';
            })
            ->addColumn('user_name', function ($reconcile) {
                return $reconcile->user ? $reconcile->user->name : 'N/A';
            })
            ->addColumn('reconciliation_status', function ($reconcile) {
                return ucfirst(str_replace('_', ' ', $reconcile->reconciliation_status));
            })
            ->addColumn('matching_invoice', function ($reconcile) {
                $matchingInvoice = $reconcile->matching_invoice;
                return $matchingInvoice ? $matchingInvoice->invoice_number : 'No Match';
            })
            ->addColumn('status_badge', function ($reconcile) {
                $status = $reconcile->reconciliation_status;
                $badgeClass = match ($status) {
                    'matched' => 'badge-success',
                    'partial_match' => 'badge-warning',
                    'no_match' => 'badge-danger',
                    default => 'badge-secondary'
                };

                return '<span class="badge ' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
            })
            ->addColumn('actions', function ($reconcile) {
                return '<button class="btn btn-sm btn-info" onclick="viewDetails(' . $reconcile->id . ')">
                    <i class="fas fa-eye"></i> View
                </button>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Get matching invoice from internal system.
     */
    public function getInvoiceIrr(string $invoiceNo): JsonResponse
    {
        $invoice = Invoice::where('invoice_number', 'LIKE', '%' . $invoiceNo . '%')
            ->orWhere('faktur_no', 'LIKE', '%' . $invoiceNo . '%')
            ->with(['supplier', 'type'])
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'No matching invoice found'], 404);
        }

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'faktur_no' => $invoice->faktur_no,
                'supplier_name' => $invoice->supplier ? $invoice->supplier->name : 'N/A',
                'receive_date' => $invoice->receive_date ? $invoice->receive_date->format('Y-m-d') : null,
                'amount' => number_format($invoice->amount, 2),
                'sap_doc' => $invoice->sap_doc,
                'payment_date' => $invoice->payment_date ? $invoice->payment_date->format('Y-m-d') : null,
                'status' => $invoice->status,
                'payment_status' => $invoice->payment_status,
            ]
        ]);
    }

    /**
     * Get reconcile data for display.
     */
    public function getReconcileData(): JsonResponse
    {
        $reconciles = ReconcileDetail::forUser(Auth::id())
            ->withoutFlag()
            ->with(['supplier'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($reconcile) {
                $matchingInvoice = $reconcile->matching_invoice;

                return [
                    'id' => $reconcile->id,
                    'invoice_no' => $reconcile->invoice_no,
                    'invoice_irr' => $matchingInvoice ? $matchingInvoice->invoice_number : 'No Match',
                    'vendor_n' => $matchingInvoice && $matchingInvoice->supplier ? $matchingInvoice->supplier->name : 'N/A',
                    'receive_d' => $matchingInvoice ? $matchingInvoice->receive_date->format('Y-m-d') : 'N/A',
                    'amount' => $matchingInvoice ? number_format($matchingInvoice->amount, 2) : 'N/A',
                    'spi_no' => $matchingInvoice ? $matchingInvoice->sap_doc : 'N/A',
                    'spi_date' => $matchingInvoice && $matchingInvoice->payment_date ? $matchingInvoice->payment_date->format('Y-m-d') : 'N/A',
                    'status' => $reconcile->reconciliation_status,
                    'created_at' => $reconcile->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($reconciles);
    }

    /**
     * Get suppliers for dropdown.
     */
    public function getSuppliers(): JsonResponse
    {
        try {
            $suppliers = Supplier::active()
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json($suppliers);
        } catch (\Exception $e) {
            Log::error('Error loading suppliers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load suppliers'], 500);
        }
    }

    /**
     * Get reconcile statistics for dashboard.
     */
    public function getStats(): JsonResponse
    {
        $userId = Auth::id();

        $stats = [
            'total_records' => ReconcileDetail::forUser($userId)->count(),
            'matched_records' => ReconcileDetail::forUser($userId)->withMatchingInvoices()->count(),
            'unmatched_records' => ReconcileDetail::forUser($userId)->withoutMatchingInvoices()->count(),
            'recent_uploads' => ReconcileDetail::forUser($userId)
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Download Excel template for reconciliation.
     */
    public function downloadTemplate()
    {
        return Excel::download(new ReconcileTemplateExport, 'reconcile_template_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Get invoice details for the detail modal.
     */
    public function getInvoiceDetails($id)
    {
        $reconcile = ReconcileDetail::with(['supplier', 'user'])->findOrFail($id);
        $matchingInvoice = $reconcile->matching_invoice;

        return view('reports.reconcile.partials.details', compact('reconcile', 'matchingInvoice'));
    }
}
