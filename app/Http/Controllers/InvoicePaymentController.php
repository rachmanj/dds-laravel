<?php

namespace App\Http\Controllers;

use App\Jobs\CreateSapOutgoingPaymentJob;
use App\Models\Department;
use App\Models\Invoice;
use App\Services\SapApInvoicePaymentResolver;
use App\Services\SapOutgoingPaymentPayloadBuilder;
use App\Services\SapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InvoicePaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-invoice-payment');
    }

    /**
     * Display the payment dashboard.
     */
    public function dashboard()
    {
        $user = Auth::user();
        $userLocationCode = $user->department_location_code;

        // Get payment metrics for user's department
        $metrics = $this->getPaymentMetrics($userLocationCode);

        return view('invoice-payments.dashboard', compact('metrics'));
    }

    /**
     * Display invoices waiting for payment.
     */
    public function waitingPayment(Request $request)
    {
        $user = Auth::user();
        $userLocationCode = $user->department_location_code;

        $query = Invoice::with(['supplier', 'type', 'creator.department'])
            ->inUserDepartment($userLocationCode)
            ->orderBy('receive_date', 'asc');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('po_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply distribution status filter
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        // Apply payment status filter - only show pending invoices by default
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        } else {
            // Default: only show pending invoices waiting for payment
            $query->where('payment_status', 'pending');
        }

        $invoices = $query->paginate(15);

        return view('invoice-payments.waiting-payment', compact('invoices'));
    }

    /**
     * Display paid invoices.
     */
    public function paidInvoices(Request $request)
    {
        $user = Auth::user();
        $userLocationCode = $user->department_location_code;

        $query = Invoice::with(['supplier', 'type', 'creator.department', 'paidByUser'])
            ->paid()
            ->inUserDepartment($userLocationCode)
            ->orderBy('paid_at', 'desc');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('po_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->where('paid_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('paid_at', '<=', $request->date_to.' 23:59:59');
        }

        $invoices = $query->paginate(15);

        return view('invoice-payments.paid-invoices', compact('invoices'));
    }

    /**
     * Update invoice payment status.
     */
    public function updatePayment(Request $request, Invoice $invoice)
    {
        $this->middleware('permission:update-invoice-payment');

        $user = Auth::user();
        $userLocationCode = $user->department_location_code;

        // Check if user can update this invoice (must be in their department)
        if ($invoice->cur_loc !== $userLocationCode) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update invoices in your department.',
            ], 403);
        }

        $request->validate([
            'payment_status' => ['required', Rule::in(['pending', 'paid'])],
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Handle status reversal (paid -> pending)
            if ($request->payment_status === 'pending' && $invoice->payment_status === 'paid') {
                $invoice->update([
                    'payment_status' => 'pending',
                    'payment_date' => $request->payment_date,
                    'paid_by' => null,
                    'paid_at' => null,
                    'remarks' => $request->remarks,
                ]);
            } else {
                // Normal update (pending -> paid or paid -> paid with new date)
                $invoice->update([
                    'payment_status' => $request->payment_status,
                    'payment_date' => $request->payment_date,
                    'paid_by' => $request->payment_status === 'paid' ? $user->id : $invoice->paid_by,
                    'paid_at' => $request->payment_status === 'paid' ? now() : $invoice->paid_at,
                    'remarks' => $request->remarks,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice payment status updated successfully.',
                'invoice' => $invoice->fresh(['supplier', 'type', 'paidByUser']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice payment status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update paid invoice details (payment date, remarks, or revert to pending).
     */
    public function updatePaidInvoice(Request $request, Invoice $invoice)
    {
        $this->middleware('permission:update-invoice-payment');

        $user = Auth::user();
        $userLocationCode = $user->department_location_code;

        // Check if user can update this invoice (must be in their department)
        if ($invoice->cur_loc !== $userLocationCode) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update invoices in your department.',
            ], 403);
        }

        // Ensure invoice is currently paid
        if ($invoice->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This invoice is not currently marked as paid.',
            ], 400);
        }

        $request->validate([
            'action' => ['required', Rule::in(['update_details', 'revert_to_pending'])],
            'payment_date' => 'required_if:action,update_details|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            if ($request->action === 'revert_to_pending') {
                // Revert to pending payment status
                $invoice->update([
                    'payment_status' => 'pending',
                    'payment_date' => null,
                    'paid_by' => null,
                    'paid_at' => null,
                    'remarks' => $request->remarks ?: 'Reverted to pending payment status',
                ]);

                $message = 'Invoice payment status reverted to pending successfully.';
            } else {
                // Update payment details (date, remarks)
                $invoice->update([
                    'payment_date' => $request->payment_date,
                    'remarks' => $request->remarks,
                ]);

                $message = 'Invoice payment details updated successfully.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'invoice' => $invoice->fresh(['supplier', 'type', 'paidByUser']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update invoice payment status.
     */
    public function bulkUpdatePayment(Request $request)
    {
        $this->middleware('permission:update-invoice-payment');

        $user = Auth::user();
        $userLocationCode = $user->department_location_code;

        // Debug logging
        Log::info('Bulk update request data:', $request->all());

        $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'exists:invoices,id',
            'payment_status' => ['required', Rule::in(['pending', 'paid'])],
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $invoices = Invoice::whereIn('id', $request->invoice_ids)
                ->inUserDepartment($userLocationCode)
                ->get();

            if ($invoices->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid invoices found for bulk update.',
                ], 400);
            }

            $updatedCount = 0;
            foreach ($invoices as $invoice) {
                $invoice->update([
                    'payment_status' => $request->payment_status,
                    'payment_date' => $request->payment_date,
                    'paid_by' => $request->payment_status === 'paid' ? $user->id : null,
                    'paid_at' => $request->payment_status === 'paid' ? now() : null,
                    'remarks' => $request->remarks,
                ]);
                $updatedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} invoices.",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update invoices: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment metrics for dashboard.
     */
    private function getPaymentMetrics($locationCode)
    {
        $configDays = config('invoice.payment_overdue_days', 30);

        $metrics = [
            'total_pending' => Invoice::pendingPayment()
                ->inUserDepartment($locationCode)
                ->count(),

            'total_paid' => Invoice::paid()
                ->inUserDepartment($locationCode)
                ->count(),

            'overdue_count' => Invoice::overdue($configDays)
                ->inUserDepartment($locationCode)
                ->count(),

            'total_amount_pending' => Invoice::pendingPayment()
                ->inUserDepartment($locationCode)
                ->sum('amount'),

            'total_amount_paid' => Invoice::paid()
                ->inUserDepartment($locationCode)
                ->sum('amount'),

            'average_payment_days' => Invoice::paid()
                ->inUserDepartment($locationCode)
                ->whereNotNull('receive_date')
                ->whereNotNull('paid_at')
                ->avg(DB::raw('DATEDIFF(paid_at, receive_date)')),
        ];

        // Get recent payment activity
        $metrics['recent_payments'] = Invoice::paid()
            ->inUserDepartment($locationCode)
            ->with(['supplier', 'paidByUser'])
            ->orderBy('paid_at', 'desc')
            ->limit(5)
            ->get();

        // Get overdue invoices for alerts
        $metrics['overdue_invoices'] = Invoice::overdue($configDays)
            ->inUserDepartment($locationCode)
            ->with(['supplier'])
            ->orderBy('receive_date', 'asc')
            ->limit(10)
            ->get();

        return $metrics;
    }

    public function previewSapPayment(Invoice $invoice, SapService $sapService, SapApInvoicePaymentResolver $apInvoiceResolver)
    {
        $this->authorizePaymentSapSync();

        $validationErrors = $invoice->canSubmitPaymentToSap();
        if (! empty($validationErrors)) {
            return redirect()
                ->route('invoices.payments.paid')
                ->withErrors(['sap_payment' => implode(', ', $validationErrors)]);
        }

        $invoice->load(['supplier']);

        try {
            $resolvedApInvoice = $apInvoiceResolver->resolve($invoice, $sapService);
        } catch (\Exception $e) {
            return redirect()
                ->route('invoices.payments.paid')
                ->withErrors(['sap_payment' => $e->getMessage()]);
        }

        $paymentMeans = request('payment_means', 'transfer');
        $accountCode = request('account_code', '');
        $paymentDate = request('payment_date', $invoice->payment_date?->format('Y-m-d'));

        $payloadBuilder = new SapOutgoingPaymentPayloadBuilder(
            $invoice,
            $paymentMeans,
            $accountCode,
            (int) $resolvedApInvoice['DocEntry'],
            $paymentDate
        );

        $paymentPreview = $payloadBuilder->getPreviewData()['outgoing_payment'];
        $paymentPreview['ap_invoice']['doc_entry'] = $resolvedApInvoice['DocEntry'];
        $paymentPreview['ap_invoice']['doc_num'] = $resolvedApInvoice['DocNum'] ?? $invoice->sap_doc_num;

        return view('invoice-payments.payment-sap-preview', [
            'invoice' => $invoice,
            'paymentPreview' => $paymentPreview,
            'paymentMeans' => $paymentMeans,
            'accountCode' => $accountCode,
            'paymentDate' => $paymentDate,
        ]);
    }

    public function submitPaymentToSap(Request $request, Invoice $invoice, SapService $sapService, SapApInvoicePaymentResolver $apInvoiceResolver)
    {
        $this->authorizePaymentSapSync();

        $validationErrors = $invoice->canSubmitPaymentToSap();
        if (! empty($validationErrors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(', ', $validationErrors),
                ], 422);
            }

            return back()->withErrors(['sap_payment' => implode(', ', $validationErrors)]);
        }

        $invoice->load(['supplier']);

        $validated = $request->validate([
            'payment_means' => ['required', Rule::in(['cash', 'transfer'])],
            'account_code' => 'required|string|max:15',
            'payment_date' => 'nullable|date',
        ]);

        try {
            $apInvoiceResolver->resolve($invoice, $sapService);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['sap_payment' => $e->getMessage()]);
        }

        $invoice->update([
            'sap_payment_status' => 'pending',
            'sap_payment_means' => $validated['payment_means'],
            'sap_payment_account_code' => $validated['account_code'],
        ]);

        CreateSapOutgoingPaymentJob::dispatch(
            $invoice,
            $validated['payment_means'],
            $validated['account_code'],
            $validated['payment_date'] ?? null
        );

        $message = 'Invoice payment queued for SAP posting.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'sap_payment_status' => 'pending',
                'status_url' => route('invoices.payment-sap-status', $invoice),
                'invoice_url' => route('invoices.payments.paid'),
            ]);
        }

        return redirect()
            ->route('invoices.payments.paid')
            ->with('success', $message);
    }

    public function paymentSapStatus(Invoice $invoice)
    {
        $invoice->refresh();

        return response()->json([
            'sap_status' => $invoice->sap_payment_status,
            'sap_status_badge' => $invoice->sap_payment_status_badge,
            'sap_doc_num' => $invoice->sap_payment_doc_num,
            'sap_error_message' => $invoice->sap_payment_error_message,
            'display_sap_document' => $invoice->sap_payment_doc_num,
            'is_terminal' => in_array($invoice->sap_payment_status, ['posted', 'failed'], true),
        ]);
    }

    protected function authorizePaymentSapSync(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('send-payment-to-sap')) {
            abort(403, 'You do not have permission to send payments to SAP.');
        }
    }
}
