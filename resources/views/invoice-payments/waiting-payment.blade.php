@extends('invoice-payments.layout')

@section('payment-content')
    <!-- Search and Filter -->
    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" action="{{ route('invoices.payments.waiting') }}" class="form-inline">
                <div class="input-group mr-2">
                    <input type="text" name="search" class="form-control" placeholder="Search invoice, PO, or supplier..."
                        value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <select name="status" class="form-control mr-2">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="verify" {{ request('status') === 'verify' ? 'selected' : '' }}>Verify</option>
                    <option value="return" {{ request('status') === 'return' ? 'selected' : '' }}>Return</option>
                    <option value="sap" {{ request('status') === 'sap' ? 'selected' : '' }}>SAP</option>
                    <option value="close" {{ request('status') === 'close' ? 'selected' : '' }}>Close</option>
                    <option value="cancel" {{ request('status') === 'cancel' ? 'selected' : '' }}>Cancel</option>
                </select>

                <button type="submit" class="btn btn-primary mr-2">Filter</button>
                <a href="{{ route('invoices.payments.waiting') }}" class="btn btn-outline-secondary">Clear</a>
            </form>
        </div>

        @can('update-invoice-payment')
            <div class="col-md-4 text-right">
                <button type="button" class="btn btn-success" id="bulk-update-btn" disabled>
                    <i class="fas fa-check-double mr-2"></i>Bulk Update Payment
                </button>
            </div>
        @endcan
    </div>

    <!-- Invoices Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-clock mr-2"></i>Invoices Waiting for Payment
                <span class="badge badge-warning ml-2">{{ $invoices->total() }}</span>
            </h3>
        </div>
        <div class="card-body p-0">
            @if ($invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                @can('update-invoice-payment')
                                    <th width="50">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                @endcan
                                <th>Invoice #</th>
                                <th>Supplier</th>
                                <th>PO Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Days Since Received</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr class="{{ $invoice->is_overdue ? 'table-danger' : '' }}">
                                    @can('update-invoice-payment')
                                        <td>
                                            <input type="checkbox" class="form-check-input invoice-checkbox"
                                                value="{{ $invoice->id }}">
                                        </td>
                                    @endcan
                                    <td>
                                        <strong>{{ $invoice->invoice_number }}</strong>
                                        <br><small class="text-muted">{{ $invoice->formatted_invoice_date }}</small>
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
                                        {!! $invoice->status_badge !!}
                                    </td>
                                    <td>
                                        @if ($invoice->days_since_received !== null)
                                            <span
                                                class="badge {{ $invoice->days_since_received > 15 ? 'badge-danger' : 'badge-secondary' }}">
                                                {{ $invoice->days_since_received }} days
                                            </span>
                                            <br><small class="text-muted">
                                                @if ($invoice->receive_date)
                                                    Received: {{ $invoice->receive_date->format('d-M-Y') }}
                                                @elseif ($invoice->created_at)
                                                    Created: {{ $invoice->created_at->format('d-M-Y') }}
                                                @else
                                                    No date
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                            <br><small class="text-muted">No date available</small>
                                        @endif
                                    </td>
                                    <td>
                                        @can('update-invoice-payment')
                                            <button type="button" class="btn btn-sm btn-primary update-payment-btn"
                                                data-invoice="{{ $invoice->id }}"
                                                data-invoice-number="{{ $invoice->invoice_number }}"
                                                data-supplier="{{ $invoice->supplier->name ?? 'N/A' }}"
                                                data-amount="{{ $invoice->formatted_amount }}">
                                                <i class="fas fa-edit"></i> Update Payment
                                            </button>
                                        @endcan
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
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-success">No invoices waiting for payment!</p>
                    <p class="text-muted">All invoices in your department have been paid.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Update Payment Modal -->
    @can('update-invoice-payment')
        <div class="modal fade" id="update-payment-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="update-payment-form">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Payment Status</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Invoice</label>
                                <input type="text" class="form-control" id="modal-invoice-number" readonly>
                            </div>

                            <div class="form-group">
                                <label>Supplier</label>
                                <input type="text" class="form-control" id="modal-supplier" readonly>
                            </div>

                            <div class="form-group">
                                <label>Amount</label>
                                <input type="text" class="form-control" id="modal-amount" readonly>
                            </div>

                            <div class="form-group">
                                <label for="payment_status">Payment Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="payment_status" name="payment_status" required>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="form-group">
                                <label for="remarks">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                    placeholder="Optional remarks about the payment..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bulk Update Modal -->
        <div class="modal fade" id="bulk-update-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="bulk-update-form">
                        <div class="modal-header">
                            <h5 class="modal-title">Bulk Update Payment Status</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                You are about to update <strong id="bulk-count">0</strong> selected invoices.
                            </div>

                            <div class="form-group">
                                <label for="bulk_payment_status">Payment Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="bulk_payment_status" name="payment_status" required>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="bulk_payment_date">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="bulk_payment_date" name="payment_date"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="form-group">
                                <label for="bulk_remarks">Remarks</label>
                                <textarea class="form-control" id="bulk_remarks" name="remarks" rows="3"
                                    placeholder="Optional remarks about the bulk update..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update All Selected</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Select all functionality
            $('#select-all').change(function() {
                $('.invoice-checkbox').prop('checked', $(this).is(':checked'));
                updateBulkButton();
            });

            // Individual checkbox change
            $('.invoice-checkbox').change(function() {
                updateBulkButton();
                updateSelectAll();
            });

            // Update bulk button state
            function updateBulkButton() {
                const checkedCount = $('.invoice-checkbox:checked').length;
                $('#bulk-update-btn').prop('disabled', checkedCount === 0);
                if (checkedCount > 0) {
                    $('#bulk-update-btn').text(`Bulk Update Payment (${checkedCount})`);
                } else {
                    $('#bulk-update-btn').text('Bulk Update Payment');
                }
            }

            // Update select all checkbox
            function updateSelectAll() {
                const totalCheckboxes = $('.invoice-checkbox').length;
                const checkedCheckboxes = $('.invoice-checkbox:checked').length;

                if (checkedCheckboxes === 0) {
                    $('#select-all').prop('indeterminate', false).prop('checked', false);
                } else if (checkedCheckboxes === totalCheckboxes) {
                    $('#select-all').prop('indeterminate', false).prop('checked', true);
                } else {
                    $('#select-all').prop('indeterminate', true);
                }
            }

            // Individual update payment
            $('.update-payment-btn').click(function() {
                const invoiceId = $(this).data('invoice');
                const invoiceNumber = $(this).data('invoice-number');
                const supplier = $(this).data('supplier');
                const amount = $(this).data('amount');

                $('#modal-invoice-number').val(invoiceNumber);
                $('#modal-supplier').val(supplier);
                $('#modal-amount').val(amount);

                // Set form action
                $('#update-payment-form').attr('action', `/invoices/payments/${invoiceId}/update`);

                $('#update-payment-modal').modal('show');
            });

            // Bulk update button
            $('#bulk-update-btn').click(function() {
                const checkedInvoices = $('.invoice-checkbox:checked');
                const count = checkedInvoices.length;

                if (count === 0) {
                    alert('Please select at least one invoice to update.');
                    return;
                }

                $('#bulk-count').text(count);
                $('#bulk-update-modal').modal('show');
            });

            // Handle individual payment update
            $('#update-payment-form').submit(function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.text();

                submitBtn.prop('disabled', true).text('Updating...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'PUT',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#update-payment-modal').modal('hide');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message ||
                                'Failed to update payment status.');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'An error occurred while updating payment status.';
                        toastr.error(message);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // Handle bulk payment update
            $('#bulk-update-form').submit(function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.text();

                const checkedInvoices = $('.invoice-checkbox:checked');
                const invoiceIds = checkedInvoices.map(function() {
                    return $(this).val();
                }).get();

                submitBtn.prop('disabled', true).text('Updating...');

                // Build data object manually to ensure proper formatting
                const formData = {
                    payment_status: $('#bulk_payment_status').val(),
                    payment_date: $('#bulk_payment_date').val(),
                    remarks: $('#bulk_remarks').val(),
                    invoice_ids: invoiceIds
                };

                // Debug logging
                console.log('Bulk update data being sent:', formData);
                console.log('Payment status value:', $('#bulk_payment_status').val());
                console.log('Payment date value:', $('#bulk_payment_date').val());

                $.ajax({
                    url: '{{ route('invoices.payments.bulk-update') }}',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#bulk-update-modal').modal('hide');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message || 'Failed to bulk update invoices.');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'An error occurred while bulk updating invoices.';
                        toastr.error(message);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
    </script>
@endsection
