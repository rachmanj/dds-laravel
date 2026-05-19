<?php

namespace App\Http\Controllers;

use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use App\Rules\UniqueInvoicePerSupplier;
use App\Services\InvoiceCreatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class InvoiceBatchImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(): View|RedirectResponse
    {
        if (! config('services.openrouter.enabled', true)) {
            abort(404);
        }
        if (! config('services.openrouter.key')) {
            return redirect()->route('invoices.index')
                ->with('error', 'Batch invoice import is not available: API key is not configured.');
        }

        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $invoiceImportQueueDriver = config('queue.default');
        $batchImportMax = (int) config('services.openrouter.batch_import_max', 50);

        return view('invoices.import-batch', compact(
            'invoiceTypes',
            'suppliers',
            'invoiceImportQueueDriver',
            'batchImportMax'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        if (! config('services.openrouter.enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice import is disabled.',
            ], 404);
        }
        if (! config('services.openrouter.key')) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice import is not configured (API key missing).',
            ], 503);
        }

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $userId = Auth::id();
        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication error. Please refresh the page and try again.',
            ], 401);
        }

        $max = (int) config('services.openrouter.batch_import_max', 50);
        $invoicesInput = $request->input('invoices');
        if (! is_array($invoicesInput)) {
            return response()->json([
                'success' => false,
                'message' => 'Request must include an invoices array.',
            ], 422);
        }

        if (count($invoicesInput) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least one invoice to create.',
                'errors' => ['invoices' => ['Select at least one invoice to create.']],
            ], 422);
        }

        if (count($invoicesInput) > $max) {
            return response()->json([
                'success' => false,
                'message' => "A maximum of {$max} invoices can be created per batch.",
                'errors' => ['invoices' => ["A maximum of {$max} invoices can be created per batch."]],
            ], 422);
        }

        $seenSupplierInvoice = [];
        $seenImportUuids = [];
        $results = [];
        $creator = app(InvoiceCreatorService::class);

        foreach ($invoicesInput as $index => $row) {
            if (! is_array($row)) {
                $results[] = [
                    'index' => $index,
                    'status' => 'validation_failed',
                    'errors' => ['Invalid row payload.'],
                ];

                continue;
            }

            $validator = Validator::make($row, $this->singleInvoiceRules());

            if ($validator->fails()) {
                $results[] = [
                    'index' => $index,
                    'status' => 'validation_failed',
                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            $data = $validator->validated();

            $pairKey = $data['supplier_id'].'|'.strtoupper((string) $data['invoice_number']);
            if (isset($seenSupplierInvoice[$pairKey])) {
                $results[] = [
                    'index' => $index,
                    'status' => 'validation_failed',
                    'errors' => ['Duplicate invoice number for the same supplier within this batch.'],
                ];

                continue;
            }
            $seenSupplierInvoice[$pairKey] = true;

            $importUuid = $data['import_uuid'] ?? null;
            if (is_string($importUuid) && $importUuid !== '') {
                if (isset($seenImportUuids[$importUuid])) {
                    $results[] = [
                        'index' => $index,
                        'status' => 'validation_failed',
                        'errors' => ['Each uploaded file can only be used once in this batch.'],
                    ];

                    continue;
                }
                $seenImportUuids[$importUuid] = true;
            }

            $importLineItems = $data['import_line_items'] ?? null;
            $additionalDocumentIds = $data['additional_document_ids'] ?? [];
            if (! is_array($additionalDocumentIds)) {
                $additionalDocumentIds = [];
            }

            unset($data['import_uuid'], $data['import_line_items'], $data['additional_document_ids']);

            try {
                $created = $creator->create(
                    $data,
                    $userId,
                    $authUser,
                    is_string($importUuid) && $importUuid !== '' ? $importUuid : null,
                    is_array($importLineItems) && count($importLineItems) > 0 ? $importLineItems : null,
                    array_values(array_map('intval', $additionalDocumentIds))
                );
                $invoice = $created['invoice'];

                $results[] = [
                    'index' => $index,
                    'status' => 'created',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'import_attachment_saved' => $created['import_attachment_saved'],
                ];
            } catch (\Throwable $e) {
                Log::error('Batch invoice create failed', [
                    'index' => $index,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $results[] = [
                    'index' => $index,
                    'status' => 'failed',
                    'message' => 'Could not create invoice: '.$e->getMessage(),
                ];
            }
        }

        $createdCount = count(array_filter($results, fn (array $r) => ($r['status'] ?? '') === 'created'));

        return response()->json([
            'success' => $createdCount > 0,
            'created_count' => $createdCount,
            'results' => $results,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function singleInvoiceRules(): array
    {
        return [
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
            'payment_date' => ['nullable', 'date', 'after_or_equal:receive_date'],
            'remarks' => ['nullable', 'string'],
            'cur_loc' => ['required', 'string', 'max:30'],
            'sap_doc' => ['nullable', 'string', 'max:20', 'unique:invoices,sap_doc'],
            'import_uuid' => ['nullable', 'uuid'],
            'import_line_items' => ['nullable', 'array', 'max:200'],
            'import_line_items.*.description' => ['required', 'string', 'max:65535'],
            'import_line_items.*.quantity' => ['nullable', 'numeric'],
            'import_line_items.*.unit_price' => ['nullable', 'numeric'],
            'import_line_items.*.amount' => ['nullable', 'numeric', 'min:0'],
            'additional_document_ids' => ['nullable', 'array'],
            'additional_document_ids.*' => ['integer', 'exists:additional_documents,id'],
        ];
    }
}
