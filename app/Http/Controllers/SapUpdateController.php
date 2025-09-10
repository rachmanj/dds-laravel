<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class SapUpdateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-sap-update');
    }

    /**
     * Display the SAP Update main page (dashboard)
     */
    public function index()
    {
        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();

        return view('invoices.sap-update.dashboard', compact('invoiceTypes', 'suppliers', 'projects'));
    }

    /**
     * Display the Without SAP Doc page
     */
    public function withoutSapPage()
    {
        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();

        return view('invoices.sap-update.without-sap', compact('invoiceTypes', 'suppliers', 'projects'));
    }

    /**
     * Display the With SAP Doc page
     */
    public function withSapPage()
    {
        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();

        return view('invoices.sap-update.with-sap', compact('invoiceTypes', 'suppliers', 'projects'));
    }

    /**
     * Get dashboard data for SAP Update
     */
    public function dashboard()
    {
        /** @var User $user */
        $user = Auth::user();

        // Get base query with location filtering
        $baseQuery = Invoice::query();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $baseQuery->where('cur_loc', $locationCode);
            }
        }

        // Dashboard metrics
        $totalInvoices = $baseQuery->count();
        $invoicesWithoutSap = $baseQuery->clone()->whereNull('sap_doc')->count();
        $invoicesWithSap = $baseQuery->clone()->whereNotNull('sap_doc')->count();
        $completionPercentage = $totalInvoices > 0 ? round(($invoicesWithSap / $totalInvoices) * 100, 2) : 0;

        // Recent updates (last 7 days)
        $recentUpdates = $baseQuery->clone()
            ->whereNotNull('sap_doc')
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->with(['supplier', 'creator'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // SAP doc updates over time (last 30 days)
        $updatesOverTime = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $count = $baseQuery->clone()
                ->whereNotNull('sap_doc')
                ->whereDate('updated_at', $date)
                ->count();
            $updatesOverTime[] = [
                'date' => $date,
                'count' => $count
            ];
        }

        // SAP doc status by department
        $statusByDepartment = $baseQuery->clone()
            ->select('cur_loc', DB::raw('COUNT(*) as total'))
            ->selectRaw('SUM(CASE WHEN sap_doc IS NOT NULL THEN 1 ELSE 0 END) as with_sap')
            ->selectRaw('SUM(CASE WHEN sap_doc IS NULL THEN 1 ELSE 0 END) as without_sap')
            ->groupBy('cur_loc')
            ->get();

        return response()->json([
            'metrics' => [
                'total_invoices' => $totalInvoices,
                'invoices_without_sap' => $invoicesWithoutSap,
                'invoices_with_sap' => $invoicesWithSap,
                'completion_percentage' => $completionPercentage,
            ],
            'recent_updates' => $recentUpdates,
            'updates_over_time' => $updatesOverTime,
            'status_by_department' => $statusByDepartment,
        ]);
    }

    /**
     * Get invoices without SAP doc for DataTables
     */
    public function withoutSap(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $query = Invoice::with(['supplier', 'type', 'creator']);

        // Apply location-based filtering unless user is admin/superadmin
        if (!$request->show_all && !array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->where('cur_loc', $locationCode);
            }
        }

        // Filter for invoices without SAP doc
        $query->whereNull('sap_doc');

        // Apply search filters
        if ($request->filled('search_invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->search_invoice_number . '%');
        }

        if ($request->filled('search_faktur_no')) {
            $query->where('faktur_no', 'like', '%' . $request->search_faktur_no . '%');
        }

        if ($request->filled('search_po_no')) {
            $query->where('po_no', 'like', '%' . $request->search_po_no . '%');
        }

        if ($request->filled('search_type')) {
            $query->whereHas('type', function ($q) use ($request) {
                $q->where('type_name', $request->search_type);
            });
        }

        if ($request->filled('search_status')) {
            $query->where('status', $request->search_status);
        }

        if ($request->filled('search_supplier')) {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search_supplier . '%');
            });
        }

        if ($request->filled('search_invoice_project')) {
            $query->where('invoice_project', $request->search_invoice_project);
        }

        return DataTables::of($query)
            ->addColumn('action', function ($invoice) {
                return '<button class="btn btn-sm btn-primary update-sap-btn" data-invoice-id="' . $invoice->id . '" data-invoice-number="' . $invoice->invoice_number . '">
                    <i class="fas fa-edit"></i> Update SAP Doc
                </button>';
            })
            ->addColumn('supplier_name', function ($invoice) {
                return $invoice->supplier ? $invoice->supplier->name : '-';
            })
            ->addColumn('type_name', function ($invoice) {
                return $invoice->type ? $invoice->type->type_name : '-';
            })
            ->addColumn('creator_name', function ($invoice) {
                return $invoice->creator ? $invoice->creator->name : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Get invoices with SAP doc for DataTables
     */
    public function withSap(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $query = Invoice::with(['supplier', 'type', 'creator']);

        // Apply location-based filtering unless user is admin/superadmin
        if (!$request->show_all && !array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->where('cur_loc', $locationCode);
            }
        }

        // Filter for invoices with SAP doc
        $query->whereNotNull('sap_doc');

        // Apply search filters
        if ($request->filled('search_invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->search_invoice_number . '%');
        }

        if ($request->filled('search_faktur_no')) {
            $query->where('faktur_no', 'like', '%' . $request->search_faktur_no . '%');
        }

        if ($request->filled('search_po_no')) {
            $query->where('po_no', 'like', '%' . $request->search_po_no . '%');
        }

        if ($request->filled('search_sap_doc')) {
            $query->where('sap_doc', 'like', '%' . $request->search_sap_doc . '%');
        }

        if ($request->filled('search_type')) {
            $query->whereHas('type', function ($q) use ($request) {
                $q->where('type_name', $request->search_type);
            });
        }

        if ($request->filled('search_status')) {
            $query->where('status', $request->search_status);
        }

        if ($request->filled('search_supplier')) {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search_supplier . '%');
            });
        }

        if ($request->filled('search_invoice_project')) {
            $query->where('invoice_project', $request->search_invoice_project);
        }

        return DataTables::of($query)
            ->addColumn('action', function ($invoice) {
                return '<button class="btn btn-sm btn-warning update-sap-btn" data-invoice-id="' . $invoice->id . '" data-invoice-number="' . $invoice->invoice_number . '" data-current-sap="' . $invoice->sap_doc . '">
                    <i class="fas fa-edit"></i> Update SAP Doc
                </button>';
            })
            ->addColumn('supplier_name', function ($invoice) {
                return $invoice->supplier ? $invoice->supplier->name : '-';
            })
            ->addColumn('type_name', function ($invoice) {
                return $invoice->type ? $invoice->type->type_name : '-';
            })
            ->addColumn('creator_name', function ($invoice) {
                return $invoice->creator ? $invoice->creator->name : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Update SAP document number for an invoice
     */
    public function updateSapDoc(Request $request, Invoice $invoice)
    {
        $request->validate([
            'sap_doc' => 'required|string|max:20|unique:invoices,sap_doc,' . $invoice->id,
        ]);

        try {
            DB::beginTransaction();

            $invoice->update([
                'sap_doc' => $request->sap_doc,
            ]);

            DB::commit();

            Log::info('SAP document updated', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'sap_doc' => $request->sap_doc,
                'updated_by' => Auth::user()->id,
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SAP document number updated successfully.',
                'invoice' => $invoice->fresh(['supplier', 'type', 'creator'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update SAP document', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update SAP document number.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate SAP document number uniqueness
     */
    public function validateSapDoc(Request $request)
    {
        $request->validate([
            'sap_doc' => 'required|string|max:20',
            'invoice_id' => 'nullable|exists:invoices,id'
        ]);

        $query = Invoice::where('sap_doc', $request->sap_doc);

        if ($request->filled('invoice_id')) {
            $query->where('id', '!=', $request->invoice_id);
        }

        $exists = $query->exists();

        return response()->json([
            'valid' => !$exists,
            'message' => $exists ? 'SAP document number already exists.' : 'SAP document number is available.'
        ]);
    }
}
