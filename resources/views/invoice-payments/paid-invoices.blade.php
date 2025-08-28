@extends('invoice-payments.layout')

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('payment-content')
    <!-- Search and Filter -->
    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" action="{{ route('invoices.payments.paid') }}" class="form-inline">
                <div class="input-group mr-2">
                    <input type="text" name="search" class="form-control"
                        placeholder="Search invoice, PO, or supplier..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div class="input-group mr-2">
                    <input type="date" name="date_from" class="form-control" placeholder="From Date"
                        value="{{ request('date_from') }}">
                </div>

                <div class="input-group mr-2">
                    <input type="date" name="date_to" class="form-control" placeholder="To Date"
                        value="{{ request('date_to') }}">
                </div>

                <button type="submit" class="btn btn-primary mr-2">Filter</button>
                <a href="{{ route('invoices.payments.paid') }}" class="btn btn-outline-secondary">Clear</a>
            </form>
        </div>

        <div class="col-md-4 text-right">
            <button type="button" class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel mr-2"></i>Export to Excel
            </button>
        </div>
    </div>

    <!-- Paid Invoices Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-check-circle mr-2"></i>Paid Invoices
                <span class="badge badge-success ml-2">{{ $invoices->total() }}</span>
            </h3>
        </div>
        <div class="card-body p-0">
            @if ($invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="paid-invoices-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Supplier</th>
                                <th>PO Number</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Paid By</th>
                                <th>Payment Status</th>
                                <th>Days to Pay</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td>
                                        <strong>{{ $invoice->invoice_number }}</strong>
                                        <br><small class="text-muted">{{ $invoice->formatted_invoice_date }}</small>
                                        @if ($invoice->po_no)
                                            <br><small class="text-muted">PO: {{ $invoice->po_no }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $invoice->supplier->name ?? 'N/A' }}
                                        <br><small class="text-muted">{{ $invoice->cur_loc }}</small>
                                    </td>
                                    <td>
                                        @if ($invoice->po_no)
                                            <span class="badge badge-info">{{ $invoice->po_no }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $invoice->formatted_amount }}</strong>
                                        <br><small class="text-muted">{{ $invoice->currency }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $invoice->formatted_payment_date }}</strong>
                                        <br><small class="text-muted">{{ $invoice->formatted_paid_at }}</small>
                                    </td>
                                    <td>
                                        {{ $invoice->paidByUser->name ?? 'N/A' }}
                                        @if ($invoice->paid_at)
                                            <br><small
                                                class="text-muted">{{ $invoice->paid_at->format('d-M-Y H:i') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {!! $invoice->payment_status_badge !!}
                                    </td>
                                    <td>
                                        @if ($invoice->receive_date && $invoice->payment_date)
                                            @php
                                                $daysToPay = $invoice->receive_date->diffInDays($invoice->payment_date);
                                            @endphp
                                            <span
                                                class="badge {{ $daysToPay <= 30 ? 'badge-success' : ($daysToPay <= 60 ? 'badge-warning' : 'badge-danger') }}">
                                                {{ $daysToPay }} days
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-primary"
                                                onclick="updatePaidInvoice({{ $invoice->id }}, '{{ $invoice->payment_date }}', '{{ $invoice->remarks ?? '' }}')"
                                                title="Update Payment Details">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning"
                                                onclick="revertToPending({{ $invoice->id }})"
                                                title="Revert to Pending Payment">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer">
                    {{ $invoices->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center p-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No paid invoices found.</p>
                    <p class="text-muted">Invoices will appear here once they are marked as paid.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Payment Summary -->
    @if ($invoices->count() > 0)
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie mr-2"></i>Payment Summary
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Paid</span>
                                        <span class="info-box-number">{{ $invoices->count() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-calendar"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">This Period</span>
                                        <span class="info-box-number">
                                            @if (request('date_from') || request('date_to'))
                                                {{ $invoices->count() }}
                                            @else
                                                {{ $invoices->where('paid_at', '>=', now()->startOfMonth())->count() }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clock mr-2"></i>Payment Timing
                        </h3>
                    </div>
                    <div class="card-body">
                        @php
                            $paidInvoices = $invoices->where('receive_date')->where('payment_date');
                            $avgDays = $paidInvoices->avg(function ($invoice) {
                                return $invoice->receive_date->diffInDays($invoice->payment_date);
                            });
                            $onTimeCount = $paidInvoices
                                ->filter(function ($invoice) {
                                    return $invoice->receive_date->diffInDays($invoice->payment_date) <= 30;
                                })
                                ->count();
                            $onTimePercentage =
                                $paidInvoices->count() > 0
                                    ? round(($onTimeCount / $paidInvoices->count()) * 100, 1)
                                    : 0;
                        @endphp

                        <div class="progress-group">
                            On-Time Payments (≤30 days)
                            <span class="float-right">
                                <b>{{ $onTimePercentage }}%</b>
                            </span>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: {{ $onTimePercentage }}%"></div>
                            </div>
                        </div>

                        <div class="progress-group mt-3">
                            Average Days to Pay
                            <span class="float-right">
                                <b>{{ round($avgDays ?? 0, 1) }} days</b>
                            </span>
                            <div class="progress">
                                <div class="progress-bar bg-info"
                                    style="width: {{ $avgDays ? min(($avgDays / 90) * 100, 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Update Paid Invoice Modal -->
    <div class="modal fade" id="update-paid-invoice-modal" tabindex="-1" role="dialog"
        aria-labelledby="update-paid-invoice-modal-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="update-paid-invoice-modal-label">
                        <i class="fas fa-edit mr-2"></i>Update Payment Details
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="update-paid-invoice-form">
                    <div class="modal-body">
                        <input type="hidden" id="update_invoice_id" name="invoice_id">

                        <div class="form-group">
                            <label for="update_payment_date">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="update_payment_date" name="payment_date"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="update_remarks">Remarks</label>
                            <textarea class="form-control" id="update_remarks" name="remarks" rows="3"
                                placeholder="Optional remarks about the payment update..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Revert to Pending Modal -->
    <div class="modal fade" id="revert-to-pending-modal" tabindex="-1" role="dialog"
        aria-labelledby="revert-to-pending-modal-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="revert-to-pending-modal-label">
                        <i class="fas fa-undo mr-2"></i>Revert to Pending Payment
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="revert-to-pending-form">
                    <div class="modal-body">
                        <input type="hidden" id="revert_invoice_id" name="invoice_id">

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Warning:</strong> This action will revert the invoice payment status back to "Pending
                            Payment".
                            The invoice will no longer appear in the paid invoices list and will be available for payment
                            again.
                        </div>

                        <div class="form-group">
                            <label for="revert_remarks">Reason for Reverting <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="revert_remarks" name="remarks" rows="3"
                                placeholder="Please provide a reason for reverting this invoice to pending status..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo mr-2"></i>Revert to Pending
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        function exportToExcel() {
            // Get current filter parameters
            const search = '{{ request('search') }}';
            const dateFrom = '{{ request('date_from') }}';
            const dateTo = '{{ request('date_to') }}';

            // Create export URL with current filters
            let exportUrl = '{{ route('invoices.payments.paid') }}?export=excel';
            if (search) exportUrl += '&search=' + encodeURIComponent(search);
            if (dateFrom) exportUrl += '&date_from=' + encodeURIComponent(dateFrom);
            if (dateTo) exportUrl += '&date_to=' + encodeURIComponent(dateTo);

            // Trigger download
            window.location.href = exportUrl;
        }

        // Initialize DataTable for better user experience
        $(document).ready(function() {
            if ($('#paid-invoices-table tbody tr').length > 0) {
                $('#paid-invoices-table').DataTable({
                    pageLength: 25,
                    order: [
                        [4, 'desc']
                    ], // Sort by payment date by default
                    responsive: true,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
            }

            // Handle update paid invoice form submission
            $('#update-paid-invoice-form').submit(function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Updating...');

                const formData = {
                    action: 'update_details',
                    payment_date: $('#update_payment_date').val(),
                    remarks: $('#update_remarks').val(),
                };

                const invoiceId = $('#update_invoice_id').val();

                $.ajax({
                    url: `/invoices/payments/${invoiceId}/update-paid`,
                    method: 'PUT',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#update-paid-invoice-modal').modal('hide');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message || 'Failed to update invoice.');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'An error occurred while updating the invoice.';
                        toastr.error(message);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // Handle revert to pending form submission
            $('#revert-to-pending-form').submit(function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Reverting...');

                const formData = {
                    action: 'revert_to_pending',
                    remarks: $('#revert_remarks').val(),
                };

                const invoiceId = $('#revert_invoice_id').val();

                $.ajax({
                    url: `/invoices/payments/${invoiceId}/update-paid`,
                    method: 'PUT',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#revert-to-pending-modal').modal('hide');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message || 'Failed to revert invoice.');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'An error occurred while reverting the invoice.';
                        toastr.error(message);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });

        // Function to open update paid invoice modal
        function updatePaidInvoice(invoiceId, currentPaymentDate, currentRemarks) {
            $('#update_invoice_id').val(invoiceId);
            $('#update_payment_date').val(currentPaymentDate);
            $('#update_remarks').val(currentRemarks);
            $('#update-paid-invoice-modal').modal('show');
        }

        // Function to open revert to pending modal
        function revertToPending(invoiceId) {
            $('#revert_invoice_id').val(invoiceId);
            $('#revert_remarks').val('');
            $('#revert-to-pending-modal').modal('show');
        }
    </script>
@endsection
