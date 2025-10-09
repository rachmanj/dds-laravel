<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\Project;
use App\Models\AdditionalDocument;
use App\Models\User;
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
        /** @var User $user */
        $user = Auth::user();
        $query = Invoice::with(['supplier', 'type', 'creator', 'attachments']);

        // Apply location-based filtering unless user is admin/superadmin
        if (!$request->show_all && !array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->where('cur_loc', $locationCode);
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

        if ($request->filled('search_supplier')) {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search_supplier . '%');
            });
        }

        if ($request->filled('search_invoice_project')) {
            $query->where('invoice_project', $request->search_invoice_project);
        }



        // Show all records for users with see-all-record-switch permission if requested
        if ($request->show_all && $user->can('see-all-record-switch')) {
            // Don't apply location filter - already handled above
        }

        // Get invoices and sort by days in current location (oldest first - highest days first)
        $invoices = $query->get()->sortByDesc(function ($invoice) {
            // For available invoices that haven't been distributed, use receive_date
            if ($invoice->distribution_status === 'available' && !$invoice->hasBeenDistributed()) {
                $dateToUse = $invoice->receive_date;
            } else {
                // For distributed invoices, use the model's current_location_arrival_date
                $dateToUse = $invoice->current_location_arrival_date;
            }
            return $dateToUse ? $dateToUse->diffInDays(now()) : 0;
        })->values();


        return DataTables::of($invoices)
            ->addColumn('supplier_name', function ($invoice) {
                return $invoice->supplier ? $invoice->supplier->name : '-';
            })
            ->addColumn('type_name', function ($invoice) {
                return $invoice->type ? $invoice->type->type_name : '-';
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
            ->addColumn('days_difference', function ($invoice) {
                // Use department-specific aging calculation
                $daysInCurrentLocation = $invoice->days_in_current_location;

                if ($daysInCurrentLocation == 0) {
                    return '<span class="text-muted">-</span>';
                }

                // Round to 1 decimal place
                $roundedDays = round($daysInCurrentLocation, 1);

                if ($roundedDays <= 7) {
                    return '<span class="badge badge-success">' . $roundedDays . '</span>';
                } elseif ($roundedDays <= 14) {
                    return '<span class="badge badge-warning">' . $roundedDays . '</span>';
                } else {
                    return '<span class="badge badge-danger">' . $roundedDays . '</span>';
                }
            })
            ->addColumn('actions', function ($invoice) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('invoices.show', $invoice) . '" class="btn btn-info btn-xs" title="View Invoice"><i class="fas fa-eye"></i></a>';
                $actions .= '<a href="' . route('invoices.edit', $invoice) . '" class="btn btn-warning btn-xs" title="Edit Invoice"><i class="fas fa-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-invoice" data-id="' . $invoice->id . '" data-number="' . $invoice->invoice_number . '" data-delete-url="' . route('invoices.destroy', $invoice) . '" title="Delete Invoice"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_badge', 'days_difference', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();
        $departments = \App\Models\Department::active()->orderBy('project')->get();
        $additionalDocumentTypes = \App\Models\AdditionalDocumentType::orderByName()->get();

        return view('invoices.create', compact('invoiceTypes', 'suppliers', 'projects', 'departments', 'additionalDocumentTypes'));
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type_id' => ['required', 'exists:invoice_types,id'],
            'payment_date' => ['nullable', 'date', 'after_or_equal:receive_date'],
            'remarks' => ['nullable', 'string'],
            'cur_loc' => ['required', 'string', 'max:30'],
            'sap_doc' => ['nullable', 'string', 'max:20', 'unique:invoices,sap_doc'],
        ]);

        // Auto-populate receive_project from user's project if not provided
        $receiveProject = $request->receive_project;
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if (!$receiveProject && $authUser && $authUser->project) {
            $receiveProject = $authUser->project;
        }

        // Ensure we have a valid user ID
        $userId = Auth::id();
        if (!$userId) {
            // Log the error for debugging
            Log::error('Auth::id() returned null when creating invoice', [
                'invoice_number' => $request->invoice_number,
                'supplier_id' => $request->supplier_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication error. Please refresh the page and try again.',
            ], 401);
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
            'sap_doc' => $request->sap_doc,
            'status' => 'open', // Always set to 'open' for new invoices
            'created_by' => $userId,
        ]);

        // Link additional documents if provided
        $additionalDocumentIds = $request->input('additional_document_ids', []);
        if (!empty($additionalDocumentIds)) {
            $invoice->additionalDocuments()->sync(array_unique($additionalDocumentIds));
        }

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
        /** @var User $user */
        $user = Auth::user();
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only view invoices from your department location.');
            }
        }

        $invoice->load(['supplier', 'type', 'creator', 'attachments.uploader', 'additionalDocuments.type']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        // Check if user can edit this invoice
        /** @var User $user */
        $user = Auth::user();
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only edit invoices from your department location.');
            }
        }

        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();
        $departments = \App\Models\Department::active()->orderBy('project')->get();
        $additionalDocumentTypes = \App\Models\AdditionalDocumentType::orderByName()->get();

        return view('invoices.edit', compact('invoice', 'invoiceTypes', 'suppliers', 'projects', 'departments', 'additionalDocumentTypes'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        // Check if user can edit this invoice
        /** @var User $user */
        $user = Auth::user();
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only edit invoices from your department location.');
            }
        }

        // Check if location change is being attempted
        if ($request->has('cur_loc') && $request->cur_loc !== $invoice->cur_loc) {
            if (!$invoice->canChangeLocationManually()) {
                return redirect()->back()
                    ->withErrors([
                        'cur_loc' => 'Cannot change location manually. This invoice has distribution history. Location can only be changed through the distribution process.'
                    ])
                    ->withInput();
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type_id' => ['required', 'exists:invoice_types,id'],
            'payment_date' => ['nullable', 'date', 'after_or_equal:receive_date'],
            'remarks' => ['nullable', 'string'],
            'cur_loc' => ['required', 'string', 'max:30'],
            'status' => ['required', 'string', 'in:open,verify,return,sap,close,cancel'],
            'sap_doc' => ['nullable', 'string', 'max:20', 'unique:invoices,sap_doc,' . $invoice->id],
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
            'sap_doc' => $request->sap_doc,
            'status' => $request->status,
        ]);

        // Sync additional documents if provided
        $additionalDocumentIds = $request->input('additional_document_ids', []);
        $invoice->additionalDocuments()->sync(array_unique($additionalDocumentIds));

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
        /** @var User $user */
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
        /** @var User $user */
        $user = Auth::user();

        return view('invoices.import', compact('invoiceTypes', 'suppliers', 'user'));
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200', // 50MB max
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

    /**
     * Search additional documents by PO number, showing all documents regardless of department.
     */
    public function searchAdditionalDocuments(Request $request)
    {
        $request->validate([
            'po_no' => 'required|string|max:50',
            'current_invoice_id' => 'nullable|exists:invoices,id',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $query = AdditionalDocument::query()
            ->with(['type', 'invoices'])
            ->whereNotNull('po_no')
            ->where('po_no', 'like', '%' . $request->po_no . '%')
            ->orderByDesc('document_date')
            ->limit(50);

        // Remove department filtering - show all documents with matching PO number
        // Users can now link documents from any department

        $documents = $query->get()->map(function ($doc) use ($request, $user) {
            $linkedInvoices = $doc->invoices;
            $linkedInvoicesCount = $linkedInvoices->count();
            $currentInvoiceId = $request->current_invoice_id;

            // Determine if document is in user's department for badge coloring
            $isInUserDepartment = $user->department_location_code &&
                $doc->cur_loc === $user->department_location_code;

            return [
                'id' => $doc->id,
                'document_number' => $doc->document_number,
                'type_name' => optional($doc->type)->type_name,
                'document_date' => optional($doc->document_date)->format('Y-m-d'),
                'po_no' => $doc->po_no,
                'cur_loc' => $doc->cur_loc,
                'remarks' => $doc->remarks,
                'status' => $doc->status,
                'linked_invoices_count' => $linkedInvoicesCount,
                'linked_invoices_list' => $linkedInvoices->take(3)->pluck('invoice_number')->toArray(),
                'is_linked_to_current' => $currentInvoiceId ? $linkedInvoices->contains('id', $currentInvoiceId) : false,
                'is_in_user_department' => $isInUserDepartment,
            ];
        });

        return response()->json([
            'success' => true,
            'documents' => $documents,
        ]);
    }

    /**
     * Get supplier-specific defaults for auto-suggestion.
     * Returns common currency, last used invoice type, and common payment project.
     */
    public function getSupplierDefaults($supplierId)
    {
        $user = Auth::user();

        // Get most common currency for this supplier (from user's invoices)
        $commonCurrency = Invoice::where('supplier_id', $supplierId)
            ->where('created_by', $user->id)
            ->select('currency')
            ->groupBy('currency')
            ->orderByRaw('COUNT(*) DESC')
            ->first();

        // Get last used invoice type for this supplier (from user's invoices)
        $lastInvoice = Invoice::with('type')
            ->where('supplier_id', $supplierId)
            ->where('created_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get most common payment project for this supplier (from user's invoices)
        $commonPaymentProject = Invoice::where('supplier_id', $supplierId)
            ->where('created_by', $user->id)
            ->whereNotNull('payment_project')
            ->select('payment_project')
            ->groupBy('payment_project')
            ->orderByRaw('COUNT(*) DESC')
            ->first();

        return response()->json([
            'success' => true,
            'common_currency' => $commonCurrency ? $commonCurrency->currency : null,
            'last_type' => $lastInvoice && $lastInvoice->type ? $lastInvoice->type_id : null,
            'last_type_name' => $lastInvoice && $lastInvoice->type ? $lastInvoice->type->type_name : null,
            'common_payment_project' => $commonPaymentProject ? $commonPaymentProject->payment_project : null,
            'total_invoices' => Invoice::where('supplier_id', $supplierId)
                ->where('created_by', $user->id)
                ->count(),
        ]);
    }

    /**
     * Check if invoice with same faktur number and supplier already exists.
     * Used to warn about potential duplicates.
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'faktur_no' => 'required|string',
        ]);

        $existingInvoice = Invoice::with('supplier')
            ->where('supplier_id', $request->supplier_id)
            ->where('faktur_no', $request->faktur_no)
            ->first();

        if ($existingInvoice) {
            return response()->json([
                'exists' => true,
                'existing' => [
                    'id' => $existingInvoice->id,
                    'invoice_number' => $existingInvoice->invoice_number,
                    'faktur_no' => $existingInvoice->faktur_no,
                    'invoice_date' => $existingInvoice->invoice_date->format('d M Y'),
                    'amount_formatted' => number_format($existingInvoice->amount, 2),
                    'currency' => $existingInvoice->currency,
                    'status' => ucfirst($existingInvoice->status),
                    'supplier_name' => $existingInvoice->supplier->name,
                ],
            ]);
        }

        return response()->json([
            'exists' => false,
        ]);
    }

    /**
     * Get user's recent invoices for quick fill auto-complete.
     * Returns last 5 invoices created by the current user.
     */
    public function getRecentInvoices()
    {
        $user = Auth::user();

        $recentInvoices = Invoice::with(['supplier', 'type'])
            ->where('created_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'faktur_no' => $invoice->faktur_no,
                    'supplier_id' => $invoice->supplier_id,
                    'supplier_name' => $invoice->supplier ? $invoice->supplier->name : '',
                    'type_id' => $invoice->type_id,
                    'type_name' => $invoice->type ? $invoice->type->type_name : '',
                    'currency' => $invoice->currency,
                    'invoice_project' => $invoice->invoice_project,
                    'payment_project' => $invoice->payment_project,
                    'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                    'amount' => $invoice->amount,
                    'amount_formatted' => number_format($invoice->amount, 2),
                    'created_at' => $invoice->created_at->format('d M Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'invoices' => $recentInvoices,
        ]);
    }
}
