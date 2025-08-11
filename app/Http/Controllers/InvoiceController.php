<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\Project;
use App\Rules\UniqueInvoicePerSupplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();

        return view('invoices.index', compact('invoiceTypes', 'suppliers', 'projects'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $query = Invoice::with(['supplier', 'type', 'creator', 'attachments']);

        // Apply location-based filtering unless user is admin/superadmin
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->forLocation($locationCode);
            }
        }

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

        if ($request->filled('search_date_range')) {
            $dates = explode(' - ', $request->search_date_range);
            if (count($dates) == 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                $query->whereBetween('invoice_date', [$startDate, $endDate]);
            }
        }

        // Show all records for admin/superadmin if requested
        if ($request->show_all && $user->hasRole(['superadmin', 'admin'])) {
            // Don't apply location filter
        }

        return DataTables::of($query)
            ->addColumn('supplier_name', function ($invoice) {
                return $invoice->supplier ? $invoice->supplier->name : '-';
            })
            ->addColumn('type_name', function ($invoice) {
                return $invoice->type ? $invoice->type->type_name : '-';
            })
            ->addColumn('creator_name', function ($invoice) {
                return $invoice->creator ? $invoice->creator->name : '-';
            })
            ->addColumn('formatted_invoice_date', function ($invoice) {
                return $invoice->formatted_invoice_date;
            })
            ->addColumn('formatted_receive_date', function ($invoice) {
                return $invoice->formatted_receive_date;
            })
            ->addColumn('formatted_amount', function ($invoice) {
                return $invoice->formatted_amount;
            })
            ->addColumn('status_badge', function ($invoice) {
                return $invoice->status_badge;
            })
            ->addColumn('attachment_count', function ($invoice) {
                $count = $invoice->attachment_count;
                if ($count > 0) {
                    return '<span class="badge badge-info">' . $count . ' file(s)</span>';
                }
                return '<span class="badge badge-secondary">No files</span>';
            })
            ->addColumn('actions', function ($invoice) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('invoices.show', $invoice) . '" class="btn btn-info btn-xs" title="View Invoice"><i class="fas fa-eye"></i></a>';
                $actions .= '<a href="' . route('invoices.edit', $invoice) . '" class="btn btn-warning btn-xs" title="Edit Invoice"><i class="fas fa-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-invoice" data-id="' . $invoice->id . '" data-number="' . $invoice->invoice_number . '" data-delete-url="' . route('invoices.destroy', $invoice) . '" title="Delete Invoice"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_badge', 'attachment_count', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();
        $departments = \App\Models\Department::active()->orderBy('project')->get();

        return view('invoices.create', compact('invoiceTypes', 'suppliers', 'projects', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_number' => ['required', 'string', 'max:255', new UniqueInvoicePerSupplier()],
            'faktur_no' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'receive_date' => ['required', 'date'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'po_no' => ['nullable', 'string', 'max:30'],
            'receive_project' => ['nullable', 'string', 'max:30', 'exists:projects,code'],
            'invoice_project' => ['nullable', 'string', 'max:30', 'exists:projects,code'],
            'payment_project' => ['nullable', 'string', 'max:30', 'exists:projects,code'],
            'currency' => ['required', 'string', 'max:3'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type_id' => ['required', 'exists:invoice_types,id'],
            'payment_date' => ['nullable', 'date', 'after_or_equal:receive_date'],
            'remarks' => ['nullable', 'string'],
            'cur_loc' => ['required', 'string', 'max:30'],
        ]);

        // Auto-populate receive_project from user's project if not provided
        $receiveProject = $request->receive_project;
        if (!$receiveProject && auth()->user()->project) {
            $receiveProject = auth()->user()->project;
        }

        $invoice = Invoice::create([
            'invoice_number' => $request->invoice_number,
            'faktur_no' => $request->faktur_no,
            'invoice_date' => $request->invoice_date,
            'receive_date' => $request->receive_date,
            'supplier_id' => $request->supplier_id,
            'po_no' => $request->po_no,
            'receive_project' => $receiveProject,
            'invoice_project' => $request->invoice_project,
            'payment_project' => $request->payment_project,
            'currency' => $request->currency,
            'amount' => $request->amount,
            'type_id' => $request->type_id,
            'payment_date' => $request->payment_date,
            'remarks' => $request->remarks,
            'cur_loc' => $request->cur_loc,
            'status' => 'open', // Always set to 'open' for new invoices
            'created_by' => Auth::id(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully.',
                'invoice_id' => $invoice->id
            ]);
        }

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        // Check if user can view this invoice
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only view invoices from your department location.');
            }
        }

        $invoice->load(['supplier', 'type', 'creator', 'attachments.uploader']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        // Check if user can edit this invoice
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only edit invoices from your department location.');
            }
        }

        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();
        $departments = \App\Models\Department::active()->orderBy('project')->get();

        return view('invoices.edit', compact('invoice', 'invoiceTypes', 'suppliers', 'projects', 'departments'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        // Check if user can edit this invoice
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only edit invoices from your department location.');
            }
        }

        $request->validate([
            'invoice_number' => ['required', 'string', 'max:255', new UniqueInvoicePerSupplier($invoice->id)],
            'faktur_no' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'receive_date' => ['required', 'date'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'po_no' => ['nullable', 'string', 'max:30'],
            'receive_project' => ['nullable', 'string', 'max:30', 'exists:projects,code'],
            'invoice_project' => ['nullable', 'string', 'max:30', 'exists:projects,code'],
            'payment_project' => ['nullable', 'string', 'max:30', 'exists:projects,code'],
            'currency' => ['required', 'string', 'max:3'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type_id' => ['required', 'exists:invoice_types,id'],
            'payment_date' => ['nullable', 'date', 'after_or_equal:receive_date'],
            'remarks' => ['nullable', 'string'],
            'cur_loc' => ['required', 'string', 'max:30'],
            'status' => ['required', 'string', 'in:open,verify,return,sap,close,cancel'],
        ]);

        $invoice->update([
            'invoice_number' => $request->invoice_number,
            'faktur_no' => $request->faktur_no,
            'invoice_date' => $request->invoice_date,
            'receive_date' => $request->receive_date,
            'supplier_id' => $request->supplier_id,
            'po_no' => $request->po_no,
            'receive_project' => $request->receive_project,
            'invoice_project' => $request->invoice_project,
            'payment_project' => $request->payment_project,
            'currency' => $request->currency,
            'amount' => $request->amount,
            'type_id' => $request->type_id,
            'payment_date' => $request->payment_date,
            'remarks' => $request->remarks,
            'cur_loc' => $request->cur_loc,
            'status' => $request->status,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully.'
            ]);
        }

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        // Check if user can delete this invoice
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only delete invoices from your department location.');
            }
        }

        // Delete all attachments first
        foreach ($invoice->attachments as $attachment) {
            if (Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
            }
        }

        $invoice->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully.'
            ]);
        }

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    public function import()
    {
        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $user = Auth::user();

        return view('invoices.import', compact('invoiceTypes', 'suppliers', 'user'));
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            'invoice_type_id' => 'nullable|exists:invoice_types,id',
        ]);

        try {
            $user = Auth::user();

            // Prepare import options
            $invoiceTypeId = $request->input('invoice_type_id');

            // Default values based on user's department
            $defaultValues = [
                'cur_loc' => $user->department_location_code ?: 'DEFAULT',
                'status' => 'open',
            ];

            // TODO: Create InvoiceImport class when needed
            // For now, return a placeholder response
            return redirect()->back()
                ->with('error', 'Import functionality will be implemented soon. Please use manual entry for now.');
        } catch (\Exception $e) {
            Log::error('Invoice import error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function downloadTemplate()
    {
        $filePath = storage_path('app/templates/invoice_import_template.xlsx');

        if (!file_exists($filePath)) {
            abort(404, 'Template file not found.');
        }

        return response()->download($filePath, 'invoice_import_template.xlsx');
    }

    /**
     * Validate invoice number for a specific supplier (AJAX endpoint).
     */
    public function validateInvoiceNumber(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string|max:255',
            'supplier_id' => 'required|exists:suppliers,id',
            'exclude_id' => 'nullable|exists:invoices,id',
        ]);

        $query = Invoice::where('supplier_id', $request->supplier_id)
            ->where('invoice_number', $request->invoice_number);

        if ($request->filled('exclude_id')) {
            $query->where('id', '!=', $request->exclude_id);
        }

        $isDuplicate = $query->exists();

        return response()->json([
            'is_duplicate' => $isDuplicate,
            'message' => $isDuplicate ? 'Invoice number already exists for this supplier.' : 'Invoice number is available.'
        ]);
    }

    /**
     * Check if the user session is still valid (AJAX endpoint).
     */
    public function checkSession()
    {
        // If this method is reached, it means the user is authenticated
        // (due to auth middleware in constructor)
        return response()->json([
            'status' => 'authenticated',
            'user' => Auth::user()->name
        ]);
    }
}
