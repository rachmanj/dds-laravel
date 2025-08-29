<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InvoiceApiController extends Controller
{
    /**
     * Get invoices by department location code
     *
     * @param Request $request
     * @param string $locationCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvoicesByDepartment(Request $request, $locationCode)
    {
        try {
            // Validate location code is not empty
            if (empty($locationCode)) {
                Log::warning('API: Empty location code requested', [
                    'ip' => $request->ip(),
                    'timestamp' => now()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Invalid location code',
                    'message' => 'Location code cannot be empty'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate location code
            $department = Department::where('location_code', $locationCode)->first();

            if (!$department) {
                Log::warning('API: Invalid location code requested', [
                    'location_code' => $locationCode,
                    'ip' => $request->ip(),
                    'timestamp' => now()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Invalid location code',
                    'message' => 'Department with the specified location code not found'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate query parameters
            $validator = Validator::make($request->all(), [
                'status' => 'string|in:open,closed,overdue,cancelled',
                'date_from' => 'date_format:Y-m-d',
                'date_to' => 'date_format:Y-m-d|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'message' => 'Invalid query parameters',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            // Build query
            $query = Invoice::with([
                'supplier',
                'additionalDocuments',
                'type',
                'user',
                'distributions' => function ($query) use ($locationCode) {
                    $query->where('destination_department_id', function ($subQuery) use ($locationCode) {
                        $subQuery->select('id')
                            ->from('departments')
                            ->where('location_code', $locationCode);
                    })
                        ->orderBy('created_at', 'desc')
                        ->limit(1); // Only get the latest distribution
                },
                'distributions.type',
                'distributions.originDepartment',
                'distributions.destinationDepartment',
                'distributions.creator'
            ])->where('cur_loc', $locationCode);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->where('invoice_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('invoice_date', '<=', $request->date_to);
            }

            // Get all invoices (no pagination)
            $invoices = $query->orderBy('invoice_date', 'desc')->get();

            // Transform data
            $transformedInvoices = $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number ? str_replace('\\', '', $invoice->invoice_number) : null,
                    'faktur_no' => $invoice->faktur_no ? str_replace('\\', '', $invoice->faktur_no) : null,
                    'invoice_date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : null,
                    'receive_date' => $invoice->receive_date ? $invoice->receive_date->format('Y-m-d') : null,
                    'supplier_name' => $invoice->supplier->name ?? null,
                    'supplier_sap_code' => $invoice->supplier->sap_code ?? null,
                    'po_no' => $invoice->po_no,
                    'receive_project' => $invoice->receive_project,
                    'invoice_project' => $invoice->invoice_project,
                    'payment_project' => $invoice->payment_project,
                    'currency' => $invoice->currency,
                    'amount' => $invoice->amount,
                    'invoice_type' => $invoice->type->type_name ?? null,
                    'payment_date' => $invoice->payment_date ? $invoice->payment_date->format('Y-m-d') : null,
                    'paid_by' => $invoice->user ? $invoice->user->name : null,
                    'remarks' => $invoice->remarks,
                    'status' => $invoice->status,
                    'sap_doc' => $invoice->sap_doc,
                    'additional_documents' => $invoice->additionalDocuments->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'document_no' => $doc->document_number ? str_replace('\\', '', $doc->document_number) : null,
                            'document_date' => $doc->document_date ? $doc->document_date->format('Y-m-d') : null,
                            'document_type' => $doc->type->type_name ?? '',
                        ];
                    })->toArray(),
                    'distribution' => $invoice->distributions->first() ? [
                        'id' => $invoice->distributions->first()->id,
                        'distribution_number' => $invoice->distributions->first()->distribution_number,
                        'type' => $invoice->distributions->first()->type->name ?? null,
                        'origin_department' => $invoice->distributions->first()->originDepartment->name ?? null,
                        'destination_department' => $invoice->distributions->first()->destinationDepartment->name ?? null,
                        'status' => $invoice->distributions->first()->status,
                        'created_by' => $invoice->distributions->first()->creator->name ?? null,
                        'created_at' => $invoice->distributions->first()->created_at ? $invoice->distributions->first()->created_at->format('Y-m-d H:i:s') : null,
                        'sender_verified_at' => $invoice->distributions->first()->sender_verified_at ? $invoice->distributions->first()->sender_verified_at->format('Y-m-d H:i:s') : null,
                        'sent_at' => $invoice->distributions->first()->sent_at ? $invoice->distributions->first()->sent_at->format('Y-m-d H:i:s') : null,
                        'received_at' => $invoice->distributions->first()->received_at ? $invoice->distributions->first()->received_at->format('Y-m-d H:i:s') : null,
                        'receiver_verified_at' => $invoice->distributions->first()->receiver_verified_at ? $invoice->distributions->first()->receiver_verified_at->format('Y-m-d H:i:s') : null,
                        'has_discrepancies' => $invoice->distributions->first()->has_discrepancies,
                        'notes' => $invoice->distributions->first()->notes,
                    ] : null,
                ];
            });

            // Log successful API request
            Log::info('API: Invoices retrieved successfully', [
                'location_code' => $locationCode,
                'department_name' => $department->name,
                'total_invoices' => $invoices->count(),
                'ip' => $request->ip(),
                'filters_applied' => $request->only(['status', 'date_from', 'date_to'])
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'invoices' => $transformedInvoices,
                ],
                'meta' => [
                    'department_location' => $locationCode,
                    'department_name' => $department->name,
                    'total_invoices' => $invoices->count(),
                    'requested_at' => now()->toISOString(),
                    'filters_applied' => $request->only(['status', 'date_from', 'date_to'])
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error retrieving invoices', [
                'location_code' => $locationCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'An error occurred while processing your request'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get wait-payment invoices for a specific department by location code
     */
    public function getWaitPaymentInvoicesByDepartment(Request $request, $locationCode)
    {
        // Validate location code
        if (empty($locationCode)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid location code',
                'message' => 'Location code cannot be empty'
            ], 400);
        }

        // Find department by location code
        $department = Department::where('location_code', $locationCode)->first();
        if (!$department) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid location code',
                'message' => 'Department with the specified location code not found'
            ], 404);
        }

        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:open,closed,overdue,cancelled',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'project' => 'nullable|string',
            'supplier' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => 'Invalid query parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        // Build query for wait-payment invoices (payment_date IS NULL)
        $query = Invoice::with([
            'supplier',
            'additionalDocuments',
            'type',
            'user',
            'distributions' => function ($query) use ($locationCode) {
                $query->where('destination_department_id', function ($subQuery) use ($locationCode) {
                    $subQuery->select('id')
                        ->from('departments')
                        ->where('location_code', $locationCode);
                })
                    ->orderBy('created_at', 'desc')
                    ->limit(1); // Only get the latest distribution
            },
            'distributions.type',
            'distributions.originDepartment',
            'distributions.destinationDepartment',
            'distributions.creator'
        ])->where('cur_loc', $locationCode)
            ->whereNull('payment_date'); // Wait-payment filter

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        if ($request->filled('project')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_project', 'like', '%' . $request->project . '%')
                    ->orWhere('payment_project', 'like', '%' . $request->project . '%')
                    ->orWhere('receive_project', 'like', '%' . $request->project . '%');
            });
        }

        if ($request->filled('supplier')) {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->supplier . '%')
                    ->orWhere('sap_code', 'like', '%' . $request->supplier . '%');
            });
        }

        // Get invoices
        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        // Transform data
        $transformedInvoices = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number ? str_replace('\\', '', $invoice->invoice_number) : null,
                'faktur_no' => $invoice->faktur_no ? str_replace('\\', '', $invoice->faktur_no) : null,
                'invoice_date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : null,
                'receive_date' => $invoice->receive_date ? $invoice->receive_date->format('Y-m-d') : null,
                'supplier_name' => $invoice->supplier->name ?? null,
                'supplier_sap_code' => $invoice->supplier->sap_code ?? null,
                'po_no' => $invoice->po_no,
                'receive_project' => $invoice->receive_project,
                'invoice_project' => $invoice->invoice_project,
                'payment_project' => $invoice->payment_project,
                'currency' => $invoice->currency,
                'amount' => $invoice->amount,
                'invoice_type' => $invoice->type->type_name ?? null,
                'payment_date' => $invoice->payment_date ? $invoice->payment_date->format('Y-m-d') : null,
                'paid_by' => $invoice->user ? $invoice->user->name : null,
                'remarks' => $invoice->remarks,
                'status' => $invoice->status,
                'sap_doc' => $invoice->sap_doc,
                'additional_documents' => $invoice->additionalDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'document_no' => $doc->document_number ? str_replace('\\', '', $doc->document_number) : null,
                        'document_date' => $doc->document_date ? $doc->document_date->format('Y-m-d') : null,
                        'document_type' => $doc->type->type_name ?? '',
                    ];
                })->toArray(),
                'distribution' => $invoice->distributions->first() ? [
                    'id' => $invoice->distributions->first()->id,
                    'distribution_number' => $invoice->distributions->first()->distribution_number,
                    'type' => $invoice->distributions->first()->type->name ?? null,
                    'origin_department' => $invoice->distributions->first()->originDepartment->name ?? null,
                    'destination_department' => $invoice->distributions->first()->destinationDepartment->name ?? null,
                    'status' => $invoice->distributions->first()->status,
                    'created_by' => $invoice->distributions->first()->creator->name ?? null,
                    'created_at' => $invoice->distributions->first()->created_at ? $invoice->distributions->first()->created_at->format('Y-m-d H:i:s') : null,
                    'sender_verified_at' => $invoice->distributions->first()->sender_verified_at ? $invoice->distributions->first()->sender_verified_at->format('Y-m-d H:i:s') : null,
                    'sent_at' => $invoice->distributions->first()->sent_at ? $invoice->distributions->first()->sent_at->format('Y-m-d H:i:s') : null,
                    'received_at' => $invoice->distributions->first()->received_at ? $invoice->distributions->first()->received_at->format('Y-m-d H:i:s') : null,
                    'receiver_verified_at' => $invoice->distributions->first()->receiver_verified_at ? $invoice->distributions->first()->receiver_verified_at->format('Y-m-d H:i:s') : null,
                    'has_discrepancies' => $invoice->distributions->first()->has_discrepancies,
                    'notes' => $invoice->distributions->first()->notes,
                ] : null,
            ];
        });

        // Build response
        $response = [
            'success' => true,
            'data' => [
                'invoices' => $transformedInvoices,
            ],
            'meta' => [
                'department_location' => $locationCode,
                'department_name' => $department->name,
                'total_invoices' => $invoices->count(),
                'requested_at' => now()->toISOString(),
                'filters_applied' => $request->only(['status', 'date_from', 'date_to', 'project', 'supplier']),
                'payment_status' => 'waiting_payment'
            ]
        ];

        return response()->json($response);
    }

    /**
     * Get paid invoices for a specific department by location code
     */
    public function getPaidInvoicesByDepartment(Request $request, $locationCode)
    {
        // Validate location code
        if (empty($locationCode)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid location code',
                'message' => 'Location code cannot be empty'
            ], 400);
        }

        // Find department by location code
        $department = Department::where('location_code', $locationCode)->first();
        if (!$department) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid location code',
                'message' => 'Department with the specified location code not found'
            ], 404);
        }

        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:open,closed,overdue,cancelled',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'project' => 'nullable|string',
            'supplier' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => 'Invalid query parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        // Build query for paid invoices (payment_date IS NOT NULL)
        $query = Invoice::with([
            'supplier',
            'additionalDocuments',
            'type',
            'user',
            'distributions' => function ($query) use ($locationCode) {
                $query->where('destination_department_id', function ($subQuery) use ($locationCode) {
                    $subQuery->select('id')
                        ->from('departments')
                        ->where('location_code', $locationCode);
                })
                    ->orderBy('created_at', 'desc')
                    ->limit(1); // Only get the latest distribution
            },
            'distributions.type',
            'distributions.originDepartment',
            'distributions.destinationDepartment',
            'distributions.creator'
        ])->where('cur_loc', $locationCode)
            ->whereNotNull('payment_date'); // Paid filter

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        if ($request->filled('project')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_project', 'like', '%' . $request->project . '%')
                    ->orWhere('payment_project', 'like', '%' . $request->project . '%')
                    ->orWhere('receive_project', 'like', '%' . $request->project . '%');
            });
        }

        if ($request->filled('supplier')) {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->supplier . '%')
                    ->orWhere('sap_code', 'like', '%' . $request->supplier . '%');
            });
        }

        // Get invoices
        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        // Transform data
        $transformedInvoices = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number ? str_replace('\\', '', $invoice->invoice_number) : null,
                'faktur_no' => $invoice->faktur_no ? str_replace('\\', '', $invoice->faktur_no) : null,
                'invoice_date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : null,
                'receive_date' => $invoice->receive_date ? $invoice->receive_date->format('Y-m-d') : null,
                'supplier_name' => $invoice->supplier->name ?? null,
                'supplier_sap_code' => $invoice->supplier->sap_code ?? null,
                'po_no' => $invoice->po_no,
                'receive_project' => $invoice->receive_project,
                'invoice_project' => $invoice->invoice_project,
                'payment_project' => $invoice->payment_project,
                'currency' => $invoice->currency,
                'amount' => $invoice->amount,
                'invoice_type' => $invoice->type->type_name ?? null,
                'payment_date' => $invoice->payment_date ? $invoice->payment_date->format('Y-m-d') : null,
                'paid_by' => $invoice->user ? $invoice->user->name : null,
                'remarks' => $invoice->remarks,
                'status' => $invoice->status,
                'sap_doc' => $invoice->sap_doc,
                'additional_documents' => $invoice->additionalDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'document_no' => $doc->document_number ? str_replace('\\', '', $doc->document_number) : null,
                        'document_date' => $doc->document_date ? $doc->document_date->format('Y-m-d') : null,
                        'document_type' => $doc->type->type_name ?? '',
                    ];
                })->toArray(),
                'distribution' => $invoice->distributions->first() ? [
                    'id' => $invoice->distributions->first()->id,
                    'distribution_number' => $invoice->distributions->first()->distribution_number,
                    'type' => $invoice->distributions->first()->type->name ?? null,
                    'origin_department' => $invoice->distributions->first()->originDepartment->name ?? null,
                    'destination_department' => $invoice->distributions->first()->destinationDepartment->name ?? null,
                    'status' => $invoice->distributions->first()->status,
                    'created_by' => $invoice->distributions->first()->creator->name ?? null,
                    'created_at' => $invoice->distributions->first()->created_at ? $invoice->distributions->first()->created_at->format('Y-m-d H:i:s') : null,
                    'sender_verified_at' => $invoice->distributions->first()->sender_verified_at ? $invoice->distributions->first()->sender_verified_at->format('Y-m-d H:i:s') : null,
                    'sent_at' => $invoice->distributions->first()->sent_at ? $invoice->distributions->first()->sent_at->format('Y-m-d H:i:s') : null,
                    'received_at' => $invoice->distributions->first()->received_at ? $invoice->distributions->first()->received_at->format('Y-m-d H:i:s') : null,
                    'receiver_verified_at' => $invoice->distributions->first()->receiver_verified_at ? $invoice->distributions->first()->receiver_verified_at->format('Y-m-d H:i:s') : null,
                    'has_discrepancies' => $invoice->distributions->first()->has_discrepancies,
                    'notes' => $invoice->distributions->first()->notes,
                ] : null,
            ];
        });

        // Build response
        $response = [
            'success' => true,
            'data' => [
                'invoices' => $transformedInvoices,
            ],
            'meta' => [
                'department_location' => $locationCode,
                'department_name' => $department->name,
                'total_invoices' => $invoices->count(),
                'requested_at' => now()->toISOString(),
                'filters_applied' => $request->only(['status', 'date_from', 'date_to', 'project', 'supplier']),
                'payment_status' => 'paid'
            ]
        ];

        return response()->json($response);
    }

    /**
     * Update invoice payment information
     */
    public function updateInvoicePayment(Request $request, $invoiceId)
    {
        try {
            // Validate invoice ID
            if (empty($invoiceId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid invoice ID',
                    'message' => 'Invoice ID cannot be empty'
                ], 400);
            }

            // Find the invoice
            $invoice = Invoice::with(['supplier', 'type'])->find($invoiceId);
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invoice not found',
                    'message' => 'Invoice with the specified ID not found'
                ], 404);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'payment_date' => 'required|date_format:Y-m-d',
                'payment_status' => 'required|in:paid,pending',
                'remarks' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'message' => 'Invalid request data',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Update invoice payment information
            $invoice->update([
                'payment_date' => $request->payment_date,
                'payment_status' => $request->payment_status ?? $invoice->payment_status,
                'status' => $request->status ?? $invoice->status,
                'remarks' => $request->remarks ?? $invoice->remarks,
                'payment_project' => $request->payment_project ?? $invoice->payment_project,
            ]);

            // Log the payment update
            Log::info('API: Invoice payment updated successfully', [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoice->invoice_number,
                'payment_date' => $request->payment_date,
                'status' => $request->status ?? $invoice->status,
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            // Return updated invoice data
            return response()->json([
                'success' => true,
                'message' => 'Invoice payment updated successfully',
                'data' => [
                    'id' => $invoice->id,
                    'invoice_number' => str_replace('\\', '', $invoice->invoice_number),
                    'faktur_no' => str_replace('\\', '', $invoice->faktur_no),
                    'invoice_date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : null,
                    'receive_date' => $invoice->receive_date ? $invoice->receive_date->format('Y-m-d') : null,
                    'supplier_name' => $invoice->supplier->name ?? null,
                    'supplier_sap_code' => $invoice->supplier->sap_code ?? null,
                    'po_no' => $invoice->po_no,
                    'receive_project' => $invoice->receive_project,
                    'invoice_project' => $invoice->invoice_project,
                    'payment_project' => $invoice->payment_project,
                    'currency' => $invoice->currency,
                    'amount' => $invoice->amount,
                    'invoice_type' => $invoice->type->type_name ?? null,
                    'payment_date' => $invoice->payment_date ? $invoice->payment_date->format('Y-m-d') : null,
                    'paid_by' => $invoice->user ? $invoice->user->name : null,
                    'remarks' => $invoice->remarks,
                    'status' => $invoice->status,
                    'sap_doc' => $invoice->sap_doc,
                ],
                'meta' => [
                    'updated_at' => now()->toISOString(),
                    'payment_status' => $invoice->payment_date ? 'paid' : 'waiting_payment'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error updating invoice payment', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'An error occurred while updating the invoice payment'
            ], 500);
        }
    }

    /**
     * Get available departments for API reference
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDepartments(Request $request)
    {
        try {
            $departments = Department::select('id', 'name', 'location_code', 'akronim')
                ->whereNotNull('location_code')
                ->orderBy('name')
                ->get();

            Log::info('API: Departments list retrieved', [
                'total_departments' => $departments->count(),
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'departments' => $departments
                ],
                'meta' => [
                    'total_departments' => $departments->count(),
                    'requested_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error retrieving departments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'An error occurred while processing your request'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
