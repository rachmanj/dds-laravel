<?php

namespace App\Http\Controllers;

use App\Jobs\CancelSapApInvoiceJob;
use App\Jobs\CreateSapApInvoiceJob;
use App\Models\AdditionalDocument;
use App\Models\Invoice;
use App\Models\InvoiceLineDetail;
use App\Models\InvoiceType;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use App\Rules\UniqueInvoicePerSupplier;
use App\Services\InvoiceCreatorService;
use App\Services\InvoiceImportLineDetailsPersister;
use App\Services\SapApInvoicePayloadBuilder;
use App\Services\SapService;
use App\Support\InvoiceListFilters;
use App\Support\InvoiceListScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

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

        $query = InvoiceListFilters::baseQuery();

        InvoiceListScope::applyLocationFilter($query, $user, $request->boolean('show_all'));

        InvoiceListFilters::apply($request, $query);

        // Use DataTables with database-level sorting and pagination
        return DataTables::of($query)
            ->orderColumn('days_difference', 'days_in_location $1')
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
            ->addColumn('sap_status_badge', function ($invoice) {
                return $invoice->sap_status_badge;
            })
            ->addColumn('days_difference', function ($invoice) {
                // Use pre-calculated days_in_location from query
                $daysInCurrentLocation = $invoice->days_in_location ?? $invoice->days_in_current_location;

                if ($daysInCurrentLocation == 0 || $daysInCurrentLocation === null) {
                    return '<span class="text-muted">-</span>';
                }

                // Round to 1 decimal place
                $roundedDays = round($daysInCurrentLocation, 1);

                if ($roundedDays <= 7) {
                    return '<span class="badge badge-success">'.$roundedDays.'</span>';
                } elseif ($roundedDays <= 14) {
                    return '<span class="badge badge-warning">'.$roundedDays.'</span>';
                } else {
                    return '<span class="badge badge-danger">'.$roundedDays.'</span>';
                }
            })
            ->addColumn('actions', function ($invoice) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="'.route('invoices.show', $invoice).'" class="btn btn-info btn-xs" title="View Invoice"><i class="fas fa-eye"></i></a>';
                $actions .= '<a href="'.route('invoices.edit', $invoice).'" class="btn btn-warning btn-xs" title="Edit Invoice"><i class="fas fa-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-invoice" data-id="'.$invoice->id.'" data-number="'.$invoice->invoice_number.'" data-delete-url="'.route('invoices.destroy', $invoice).'" title="Delete Invoice"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['status_badge', 'sap_status_badge', 'days_difference', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();
        $departments = \App\Models\Department::active()->orderBy('project')->get();
        $additionalDocumentTypes = \App\Models\AdditionalDocumentType::orderByName()->get();
        $invoiceImportEnabled = config('services.openrouter.enabled', true)
            && filled(config('services.openrouter.key'));
        $invoiceImportQueueDriver = config('queue.default');

        return view('invoices.create', compact('invoiceTypes', 'suppliers', 'projects', 'departments', 'additionalDocumentTypes', 'invoiceImportEnabled', 'invoiceImportQueueDriver'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_number' => ['required', 'string', 'max:255', new UniqueInvoicePerSupplier],
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
            'gl_account' => [
                Rule::requiredIf(fn () => InvoiceType::isConsignmentTypeId($request->input('type_id'))),
                'nullable',
                'string',
                'max:30',
            ],
            'payment_date' => ['nullable', 'date', 'after_or_equal:receive_date'],
            'remarks' => ['nullable', 'string'],
            'cur_loc' => ['required', 'string', 'max:30'],
            'sap_doc' => ['nullable', 'string', 'max:20', 'unique:invoices,sap_doc'],
            'import_uuid' => ['nullable', 'uuid'],
            'import_line_items' => [
                Rule::requiredIf(fn () => InvoiceType::isConsignmentTypeId($request->input('type_id'))),
                'nullable',
                'array',
                'min:1',
                'max:200',
            ],
            'import_line_items.*.description' => ['required', 'string', 'max:65535'],
            'import_line_items.*.quantity' => ['nullable', 'numeric'],
            'import_line_items.*.unit_price' => ['nullable', 'numeric'],
            'import_line_items.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        /** @var User|null $authUser */
        $authUser = Auth::user();

        // Ensure we have a valid user ID
        $userId = Auth::id();
        if (! $userId) {
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

        $creatorPayload = $request->only([
            'invoice_number',
            'faktur_no',
            'invoice_date',
            'receive_date',
            'supplier_id',
            'po_no',
            'receive_project',
            'invoice_project',
            'payment_project',
            'currency',
            'amount',
            'type_id',
            'gl_account',
            'payment_date',
            'remarks',
            'cur_loc',
            'sap_doc',
        ]);

        $additionalDocumentIds = $request->input('additional_document_ids', []);
        if (! is_array($additionalDocumentIds)) {
            $additionalDocumentIds = [];
        }

        $userImportLines = $request->input('import_line_items');
        $importLineItems = null;
        $isConsignment = InvoiceType::isConsignmentTypeId($request->input('type_id'));
        if (is_array($userImportLines) && count($userImportLines) > 0
            && ($request->filled('import_uuid') || $isConsignment)) {
            $importLineItems = $userImportLines;
        }

        $importUuid = $request->filled('import_uuid') ? $request->string('import_uuid')->toString() : null;

        $created = app(InvoiceCreatorService::class)->create(
            $creatorPayload,
            $userId,
            $authUser,
            $importUuid,
            $importLineItems,
            $additionalDocumentIds
        );

        $invoice = $created['invoice'];
        $importAttachmentSaved = $created['import_attachment_saved'];

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully.',
                'invoice_id' => $invoice->id,
                'import_attachment_saved' => $importAttachmentSaved,
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
        if (! $user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only view invoices from your department location.');
            }
        }

        $invoice->load(['supplier', 'type', 'creator', 'attachments.uploader', 'additionalDocuments.type', 'lineDetails']);

        $blockingSignatureDocuments = $invoice->additionalDocuments->filter(
            fn (AdditionalDocument $doc) => $doc->blocksInvoiceSubmission()
        );

        $canEditInvoiceLineDetails = false;
        if ($user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance'])) {
            $canEditInvoiceLineDetails = true;
        } else {
            $locationCode = $user->department_location_code;
            $canEditInvoiceLineDetails = ! ($locationCode && $invoice->cur_loc !== $locationCode);
        }

        return view('invoices.show', compact('invoice', 'canEditInvoiceLineDetails', 'blockingSignatureDocuments'));
    }

    public function updateLineDetail(Request $request, Invoice $invoice, InvoiceLineDetail $lineDetail)
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only edit invoices from your department location.');
            }
        }

        if ($lineDetail->invoice_id !== $invoice->id) {
            abort(404);
        }

        $normalizeOptionalNumeric = static function ($value): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            return is_numeric($value) ? (string) $value : null;
        };

        $payload = [
            'description' => $request->input('description'),
            'quantity' => $normalizeOptionalNumeric($request->input('quantity')),
            'unit_price' => $normalizeOptionalNumeric($request->input('unit_price')),
            'amount' => $normalizeOptionalNumeric($request->input('amount')),
        ];

        $validated = validator($payload, [
            'description' => ['required', 'string', 'max:65535'],
            'quantity' => ['nullable', 'numeric'],
            'unit_price' => ['nullable', 'numeric'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        $lineDetail->update([
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'amount' => $validated['amount'],
            'source' => $lineDetail->source === 'import' ? 'adjusted' : $lineDetail->source,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Line updated.',
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Line detail updated.');
    }

    public function edit(Invoice $invoice)
    {
        // Check if user can edit this invoice
        /** @var User $user */
        $user = Auth::user();
        if (! $user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only edit invoices from your department location.');
            }
        }

        $invoice->load('lineDetails');
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
        if (! $user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only edit invoices from your department location.');
            }
        }

        // Check if location change is being attempted
        if ($request->has('cur_loc') && $request->cur_loc !== $invoice->cur_loc) {
            if (! $invoice->canChangeLocationManually()) {
                return redirect()->back()
                    ->withErrors([
                        'cur_loc' => 'Cannot change location manually. This invoice has distribution history. Location can only be changed through the distribution process.',
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
            'gl_account' => [
                Rule::requiredIf(fn () => InvoiceType::isConsignmentTypeId($request->input('type_id'))),
                'nullable',
                'string',
                'max:30',
            ],
            'payment_date' => ['nullable', 'date', 'after_or_equal:receive_date'],
            'remarks' => ['nullable', 'string'],
            'cur_loc' => ['required', 'string', 'max:30'],
            'status' => ['required', 'string', 'in:open,verify,return,sap,close,cancel'],
            'sap_doc' => ['nullable', 'string', 'max:20', 'unique:invoices,sap_doc,'.$invoice->id],
            'import_line_items' => ['nullable', 'array', 'max:200'],
            'import_line_items.*.description' => ['required', 'string', 'max:65535'],
            'import_line_items.*.quantity' => ['nullable', 'numeric'],
            'import_line_items.*.unit_price' => ['nullable', 'numeric'],
            'import_line_items.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $userImportLines = $request->input('import_line_items');
        $hasSubmittedLines = is_array($userImportLines) && count($userImportLines) > 0;
        if (InvoiceType::isConsignmentTypeId($request->input('type_id'))
            && ! $hasSubmittedLines
            && $invoice->lineDetails()->doesntExist()) {
            return redirect()->back()
                ->withErrors(['import_line_items' => 'Consignment invoices require at least one line item.'])
                ->withInput();
        }

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
            'gl_account' => $request->gl_account,
            'payment_date' => $request->payment_date,
            'remarks' => $request->remarks,
            'cur_loc' => $request->cur_loc,
            'sap_doc' => $request->sap_doc,
            'status' => $request->status,
        ]);

        if ($hasSubmittedLines) {
            app(InvoiceImportLineDetailsPersister::class)->persistFromUserInput($invoice, $userImportLines);
        }

        // Sync additional documents if provided
        $additionalDocumentIds = $request->input('additional_document_ids', []);
        $invoice->additionalDocuments()->sync(array_unique($additionalDocumentIds));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully.',
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
        if (! $user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance'])) {
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
                'message' => 'Invoice deleted successfully.',
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
            Log::error('Invoice import error: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Import failed: '.$e->getMessage())
                ->withInput();
        }
    }

    public function downloadTemplate()
    {
        $filePath = storage_path('app/templates/invoice_import_template.xlsx');

        if (! file_exists($filePath)) {
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
            'message' => $isDuplicate ? 'Invoice number already exists for this supplier.' : 'Invoice number is available.',
        ]);
    }

    /**
     * Validate SAP document number uniqueness
     */
    public function validateSapDoc(Request $request)
    {
        $request->validate([
            'sap_doc' => 'required|string|max:20',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

        $query = Invoice::where('sap_doc', $request->sap_doc);

        if ($request->filled('invoice_id')) {
            $query->where('id', '!=', $request->invoice_id);
        }

        $exists = $query->exists();

        return response()->json([
            'valid' => ! $exists,
            'message' => $exists ? 'SAP document number already exists.' : 'SAP document number is available.',
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
            'user' => Auth::user()->name,
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
            ->where('po_no', 'like', '%'.$request->po_no.'%')
            ->orderByDesc('document_date')
            ->limit(50);

        return response()->json([
            'success' => true,
            'documents' => $this->mapAdditionalDocumentsForInvoiceSearch(
                $query->get(),
                $request->integer('current_invoice_id') ?: null,
                $user
            ),
        ]);
    }

    /**
     * Search additional documents by document number fragment, with no PO or department filters.
     */
    public function searchAdditionalDocumentsByNumber(Request $request)
    {
        $request->validate([
            'document_number' => 'required|string|max:255',
            'current_invoice_id' => 'nullable|exists:invoices,id',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $query = AdditionalDocument::query()
            ->with(['type', 'invoices'])
            ->where('document_number', 'like', '%'.$request->document_number.'%')
            ->orderByDesc('document_date')
            ->limit(50);

        return response()->json([
            'success' => true,
            'documents' => $this->mapAdditionalDocumentsForInvoiceSearch(
                $query->get(),
                $request->integer('current_invoice_id') ?: null,
                $user
            ),
        ]);
    }

    /**
     * @param  Collection<int, AdditionalDocument>  $documents
     * @return list<array<string, mixed>>
     */
    private function mapAdditionalDocumentsForInvoiceSearch(Collection $documents, ?int $currentInvoiceId, User $user): array
    {
        return $documents->map(function (AdditionalDocument $doc) use ($currentInvoiceId, $user) {
            $linkedInvoices = $doc->invoices;
            $linkedInvoicesCount = $linkedInvoices->count();
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
        })->values()->all();
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

    public function previewSapSubmission(Invoice $invoice, SapService $sapService)
    {
        $this->authorizeSapSync($invoice);

        $blockingSignatureDocuments = $invoice->additionalDocuments()
            ->with('type')
            ->get()
            ->filter(fn (AdditionalDocument $doc) => $doc->blocksInvoiceSubmission());

        if ($blockingSignatureDocuments->isNotEmpty()) {
            $documentList = $blockingSignatureDocuments
                ->map(fn (AdditionalDocument $doc) => sprintf(
                    '%s (%s, signature: %s)',
                    $doc->document_number,
                    $doc->type?->type_name ?? 'Unknown',
                    $doc->signature_status ?? 'unknown'
                ))
                ->implode('; ');

            return redirect()
                ->route('invoices.show', $invoice)
                ->withErrors([
                    'sap_sync' => 'Invoice cannot be submitted to SAP until signature verification is resolved or overridden for: '.$documentList,
                ]);
        }

        $validationErrors = $invoice->canSyncToSap();
        if (! empty($validationErrors)) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->withErrors(['sap_sync' => implode(', ', $validationErrors)]);
        }

        if (in_array($invoice->sap_status, ['pending', 'posted', 'cancelling'], true)) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Invoice is already sent, pending, or cancelling in SAP.');
        }

        $invoice->load(['supplier', 'type', 'sapSubmitter']);
        $isConsignment = $invoice->isConsignment();
        $isStandalone = ! $invoice->po_no || trim((string) $invoice->po_no) === '';
        $grpoRows = $isStandalone ? [] : $this->resolveGrpoLinesForPreview($invoice, $sapService);
        $grpoReferences = array_values(array_filter($grpoRows, fn (array $row) => $row['found']));

        $payloadBuilder = new SapApInvoicePayloadBuilder(
            $invoice,
            array_map(fn (array $row) => [
                'grpo_no' => $row['grpo_no'],
                'doc_entry' => $row['doc_entry'],
                'base_line' => $row['base_line'],
                'item_code' => $row['item_code'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
            ], $grpoReferences)
        );

        $apPreview = $payloadBuilder->getPreviewData()['ap_invoice'];

        if (empty($apPreview['submitted_by_name']) && auth()->user()) {
            $apPreview['submitted_by_name'] = auth()->user()->name;
        }

        return view('invoices.sap-preview', [
            'invoice' => $invoice,
            'grpoRows' => $grpoRows,
            'apPreview' => $apPreview,
            'isStandalone' => $isStandalone,
            'isConsignment' => $isConsignment,
        ]);
    }

    public function submitToSap(Request $request, Invoice $invoice)
    {
        $this->authorizeSapSync($invoice);

        $blockingSignatureDocuments = $invoice->additionalDocuments()
            ->with('type')
            ->get()
            ->filter(fn (AdditionalDocument $doc) => $doc->blocksInvoiceSubmission());

        if ($blockingSignatureDocuments->isNotEmpty()) {
            $documentList = $blockingSignatureDocuments
                ->map(fn (AdditionalDocument $doc) => sprintf(
                    '%s (%s, signature: %s)',
                    $doc->document_number,
                    $doc->type?->type_name ?? 'Unknown',
                    $doc->signature_status ?? 'unknown'
                ))
                ->implode('; ');

            return back()->withErrors([
                'sap_sync' => 'Invoice cannot be submitted to SAP until signature verification is resolved or overridden for: '.$documentList,
            ]);
        }

        $validationErrors = $invoice->canSyncToSap();
        if (! empty($validationErrors)) {
            return back()->withErrors(['sap_sync' => implode(', ', $validationErrors)]);
        }

        if (in_array($invoice->sap_status, ['pending', 'posted', 'cancelling'], true)) {
            return back()->with('error', 'Invoice is already sent, pending, or cancelling in SAP.');
        }

        $validated = $request->validate([
            'grpo_references' => 'nullable|array',
            'grpo_references.*.grpo_no' => 'required_with:grpo_references|string|max:50',
            'grpo_references.*.doc_entry' => 'required_with:grpo_references|integer',
            'grpo_references.*.base_line' => 'required_with:grpo_references|integer|min:0',
            'grpo_references.*.item_code' => 'required_with:grpo_references|string|max:50',
            'grpo_references.*.quantity' => 'required_with:grpo_references|numeric|min:0.0001',
            'grpo_references.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        $grpoReferences = collect($validated['grpo_references'] ?? [])
            ->map(fn (array $ref) => [
                'grpo_no' => trim((string) $ref['grpo_no']),
                'doc_entry' => (int) $ref['doc_entry'],
                'base_line' => (int) $ref['base_line'],
                'item_code' => trim((string) $ref['item_code']),
                'quantity' => (float) $ref['quantity'],
                'unit_price' => isset($ref['unit_price']) && $ref['unit_price'] !== ''
                    ? (float) $ref['unit_price']
                    : null,
            ])
            ->filter(fn (array $ref) => $ref['grpo_no'] !== '' && $ref['doc_entry'] > 0 && $ref['item_code'] !== '')
            ->values()
            ->all();

        $hasPoNo = $invoice->po_no && trim((string) $invoice->po_no) !== '';

        if ($hasPoNo && empty($grpoReferences)) {
            return back()->withErrors([
                'grpo_references' => 'At least one valid GRPO line reference is required when PO number is set.',
            ]);
        }

        $amountSum = collect($grpoReferences)->sum(
            fn (array $ref) => (float) $ref['quantity'] * (float) ($ref['unit_price'] ?? 0)
        );
        $warning = null;
        if (! empty($grpoReferences) && abs($amountSum - (float) $invoice->amount) > 0.01) {
            $warning = sprintf(
                'GRPO line amounts (%.2f) do not match invoice total (%.2f). Submission will continue.',
                $amountSum,
                (float) $invoice->amount
            );
        }

        $invoice->update([
            'sap_status' => 'pending',
            'sap_grpo_references' => ! empty($grpoReferences) ? $grpoReferences : null,
            'sap_submitted_by_user_id' => auth()->id(),
            'sap_submitted_at' => now(),
        ]);

        CreateSapApInvoiceJob::dispatch($invoice, $grpoReferences);

        $message = $hasPoNo
            ? 'Invoice queued for SAP posting with GRPO relationship links.'
            : 'Invoice queued for SAP posting as standalone AP Invoice.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'warning' => $warning,
                'sap_status' => 'pending',
                'status_url' => route('invoices.sap-status', $invoice),
                'invoice_url' => route('invoices.show', $invoice),
            ]);
        }

        $redirect = redirect()
            ->route('invoices.show', $invoice)
            ->with('success', $message);

        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function sapSync(Invoice $invoice)
    {
        $this->authorizeSapSync($invoice);

        $validationErrors = $invoice->canSyncToSap();
        if (! empty($validationErrors)) {
            return back()->withErrors(['sap_sync' => implode(', ', $validationErrors)]);
        }

        if (in_array($invoice->sap_status, ['pending', 'posted', 'cancelling'], true)) {
            return back()->with('error', 'Invoice is already sent, pending, or cancelling in SAP.');
        }

        $invoice->update(['sap_status' => 'pending']);
        CreateSapApInvoiceJob::dispatch($invoice);

        return back()->with('success', 'Invoice queued for SAP posting.');
    }

    public function cancelSapInvoice(Request $request, Invoice $invoice)
    {
        $this->authorizeCancelSap($invoice);

        $validationErrors = $invoice->canCancelSapInvoice();
        if (! empty($validationErrors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(', ', $validationErrors),
                ], 422);
            }

            return back()->withErrors(['sap_cancel' => implode(', ', $validationErrors)]);
        }

        if ($invoice->sap_status === 'cancelling') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice cancellation is already in progress.',
                ], 422);
            }

            return back()->with('error', 'Invoice cancellation is already in progress.');
        }

        $invoice->update([
            'sap_status' => 'cancelling',
            'sap_cancel_error_message' => null,
        ]);

        CancelSapApInvoiceJob::dispatch($invoice);

        $message = 'Invoice queued for SAP AP Invoice cancellation.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'sap_status' => 'cancelling',
                'status_url' => route('invoices.sap-status', $invoice),
                'invoice_url' => route('invoices.show', $invoice),
            ]);
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', $message);
    }

    protected function authorizeSapSync(Invoice $invoice): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->can('send-to-sap')) {
            abort(403, 'You do not have permission to send invoices to SAP.');
        }

        if (! $user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only sync invoices from your department location.');
            }
        }
    }

    protected function authorizeCancelSap(Invoice $invoice): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->can('cancel-sap-invoice')) {
            abort(403, 'You do not have permission to cancel SAP invoices.');
        }

        if (! $user->hasAnyRole(['superadmin', 'admin', 'accounting'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only cancel SAP invoices from your department location.');
            }
        }
    }

    /**
     * Resolve open GRPO document lines from the invoice PO number(s).
     *
     * @return array<int, array{grpo_no: string, doc_entry: int|null, base_line: int, item_code: string, quantity: float, unit_price: float, found: bool, sap_card_code: string|null, error: string|null, po_no?: string}>
     */
    protected function resolveGrpoLinesForPreview(Invoice $invoice, SapService $sapService): array
    {
        $poNumbers = $this->parseDocumentNumbers($invoice->po_no);
        $rows = [];

        if (empty($poNumbers)) {
            return [];
        }

        foreach ($poNumbers as $poNo) {
            try {
                $grpos = $sapService->getGrposByPoNumber($poNo);

                if (empty($grpos)) {
                    $rows[] = [
                        'grpo_no' => '',
                        'doc_entry' => null,
                        'base_line' => 0,
                        'item_code' => '',
                        'quantity' => 0.0,
                        'unit_price' => 0.0,
                        'found' => false,
                        'sap_card_code' => null,
                        'error' => "No GRPO found in SAP for PO {$poNo}",
                        'po_no' => $poNo,
                    ];

                    continue;
                }

                foreach ($grpos as $grpo) {
                    $docEntry = (int) ($grpo['DocEntry'] ?? 0);
                    $grpoNo = (string) ($grpo['DocNum'] ?? '');
                    $cardCode = $grpo['CardCode'] ?? null;
                    $lines = $grpo['DocumentLines'] ?? [];
                    $openLinesFound = false;

                    foreach ($lines as $line) {
                        $openQty = $this->resolveOpenQuantity($line);
                        if ($openQty <= 0) {
                            continue;
                        }

                        $openLinesFound = true;
                        $unitPrice = (float) ($line['Price'] ?? $line['UnitPrice'] ?? 0);

                        $rows[] = [
                            'grpo_no' => $grpoNo,
                            'doc_entry' => $docEntry,
                            'base_line' => (int) ($line['LineNum'] ?? 0),
                            'item_code' => (string) ($line['ItemCode'] ?? ''),
                            'quantity' => $openQty,
                            'unit_price' => $unitPrice,
                            'found' => true,
                            'sap_card_code' => $cardCode,
                            'error' => null,
                            'po_no' => $poNo,
                        ];
                    }

                    if (! $openLinesFound) {
                        $rows[] = [
                            'grpo_no' => $grpoNo,
                            'doc_entry' => $docEntry,
                            'base_line' => 0,
                            'item_code' => '',
                            'quantity' => 0.0,
                            'unit_price' => 0.0,
                            'found' => false,
                            'sap_card_code' => $cardCode,
                            'error' => "GRPO {$grpoNo} has no open lines",
                            'po_no' => $poNo,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $rows[] = [
                    'grpo_no' => '',
                    'doc_entry' => null,
                    'base_line' => 0,
                    'item_code' => '',
                    'quantity' => 0.0,
                    'unit_price' => 0.0,
                    'found' => false,
                    'sap_card_code' => null,
                    'error' => $e->getMessage(),
                    'po_no' => $poNo,
                ];
            }
        }

        return $rows;
    }

    /**
     * Prefer RemainingOpenQuantity; fall back to Quantity when open qty is absent.
     *
     * @param  array<string, mixed>  $line
     */
    protected function resolveOpenQuantity(array $line): float
    {
        if (array_key_exists('RemainingOpenQuantity', $line) && $line['RemainingOpenQuantity'] !== null) {
            return (float) $line['RemainingOpenQuantity'];
        }

        if (array_key_exists('RemainingOpenInventoryQuantity', $line) && $line['RemainingOpenInventoryQuantity'] !== null) {
            return (float) $line['RemainingOpenInventoryQuantity'];
        }

        return (float) ($line['Quantity'] ?? 0);
    }

    /**
     * @return array<int, string>
     */
    protected function parseDocumentNumbers(?string $value): array
    {
        if (! $value || trim($value) === '') {
            return [];
        }

        $parts = preg_split('/[\s,;|]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_map('trim', $parts ?: [])));
    }

    /**
     * @deprecated Use parseDocumentNumbers()
     *
     * @return array<int, string>
     */
    protected function parseGrpoNumbersFromPoNo(?string $poNo): array
    {
        return $this->parseDocumentNumbers($poNo);
    }

    public function sapSubmissionStatus(Invoice $invoice)
    {
        $invoice->refresh();

        $canCancel = auth()->user()?->can('cancel-sap-invoice') ?? false;
        $cancelErrors = $invoice->canCancelSapInvoice();

        return response()->json([
            'sap_status' => $invoice->sap_status,
            'sap_status_badge' => $invoice->sap_status_badge,
            'display_sap_document' => $invoice->display_sap_document,
            'display_sap_cancellation_document' => $invoice->display_sap_cancellation_document,
            'sap_doc_num' => $invoice->sap_doc_num,
            'sap_error_message' => $invoice->sap_error_message,
            'sap_cancel_error_message' => $invoice->sap_cancel_error_message,
            'sap_cancelled_at' => $invoice->sap_cancelled_at?->toIso8601String(),
            'sap_cancellation_doc_num' => $invoice->sap_cancellation_doc_num,
            'is_terminal' => in_array($invoice->sap_status, ['posted', 'failed', 'cancelled'], true),
            'show_send_button' => auth()->user()?->can('send-to-sap')
                && in_array($invoice->sap_status, [null, 'failed', 'cancelled'], true)
                && $invoice->status !== 'cancel'
                && ! $invoice->has_legacy_sap_doc,
            'show_retry_button' => auth()->user()?->can('send-to-sap')
                && $invoice->sap_status === 'failed'
                && ! $invoice->has_legacy_sap_doc,
            'show_cancel_button' => $canCancel
                && empty($cancelErrors)
                && $invoice->sap_status === 'posted',
            'show_retry_cancel_button' => $canCancel
                && $invoice->sap_status === 'posted'
                && filled($invoice->sap_cancel_error_message),
        ]);
    }
}
