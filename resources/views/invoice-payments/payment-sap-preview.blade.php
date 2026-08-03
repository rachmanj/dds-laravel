@extends('layouts.main')

@section('title_page')
    SAP Outgoing Payment Preview
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.payments.paid') }}">Paid Invoices</a></li>
    <li class="breadcrumb-item active">SAP Payment Preview</li>
@endsection

@section('content')
    <div class="content">
        <div class="container-fluid">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-5">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Invoice Summary</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th>Invoice No</th>
                                    <td>{{ $invoice->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <th>Supplier</th>
                                    <td>{{ $invoice->supplier?->name }} ({{ $invoice->supplier?->sap_code }})</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>{{ $invoice->formatted_amount }}</td>
                                </tr>
                                <tr>
                                    <th>Payment Date</th>
                                    <td>{{ $invoice->formatted_payment_date }}</td>
                                </tr>
                                <tr>
                                    <th>AP Invoice DocEntry</th>
                                    <td>{{ $paymentPreview['ap_invoice']['doc_entry'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>AP Invoice DocNum</th>
                                    <td>{{ $paymentPreview['ap_invoice']['doc_num'] ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <form id="sap-payment-submit-form" action="{{ route('invoices.submit-payment-to-sap', $invoice) }}" method="POST">
                        @csrf

                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Outgoing Payment Details</h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    This will create a SAP B1 Outgoing Payment linked to the posted AP Invoice
                                    (DocEntry <strong>{{ $paymentPreview['ap_invoice']['doc_entry'] }}</strong>)
                                    via <code>PaymentInvoices</code> with <code>InvoiceType = it_PurchaseInvoice</code>.
                                </div>

                                <div class="form-group">
                                    <label for="payment_means">Payment Means <span class="text-danger">*</span></label>
                                    <select name="payment_means" id="payment_means" class="form-control" required>
                                        <option value="transfer" {{ $paymentMeans === 'transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="cash" {{ $paymentMeans === 'cash' ? 'selected' : '' }}>Cash</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="account_code">G/L Account Code <span class="text-danger">*</span></label>
                                    <input type="text" name="account_code" id="account_code" class="form-control"
                                        value="{{ old('account_code', $accountCode) }}" maxlength="15" required
                                        placeholder="e.g. 110020 or 120030">
                                    <small class="form-text text-muted">
                                        Cash account for Cash means, or bank transfer account for Transfer means.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="payment_date">Payment Date</label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control"
                                        value="{{ old('payment_date', $paymentDate) }}">
                                </div>

                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <strong>Amount to apply:</strong> {{ $invoice->formatted_amount }}
                                        <br>
                                        <strong>Linked AP Invoice:</strong> DocEntry {{ $paymentPreview['ap_invoice']['doc_entry'] }}
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <a href="{{ route('invoices.payments.paid') }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success" id="sap-payment-submit-btn">
                                    Confirm &amp; Submit Payment to SAP B1
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('invoices.partials.sap-status-monitor')
@endsection

@section('scripts')
    <script>
        (function() {
            const invoiceId = {{ $invoice->id }};
            const invoiceNumber = @json($invoice->invoice_number);
            const submitUrl = @json(route('invoices.submit-payment-to-sap', $invoice));
            const statusUrl = @json(route('invoices.payment-sap-status', $invoice));
            const invoiceUrl = @json(route('invoices.payments.paid'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function submitPaymentToSap(form) {
                const submitBtn = document.getElementById('sap-payment-submit-btn');
                const formData = new FormData(form);

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';

                DdsSapStatusMonitor.showOverlay(
                    'Submitting to SAP',
                    'Queueing Outgoing Payment for SAP B1…',
                    true
                );

                fetch(submitUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: formData,
                    })
                    .then(function(response) {
                        return response.json().then(function(data) {
                            return {
                                ok: response.ok,
                                status: response.status,
                                data: data,
                            };
                        });
                    })
                    .then(function(result) {
                        if (!result.ok) {
                            const message = result.data.message ||
                                (result.data.errors ?
                                    Object.values(result.data.errors).flat().join(' ') :
                                    'Submission failed');
                            throw new Error(message);
                        }

                        DdsSapStatusMonitor.showOverlay(
                            'Processing in SAP',
                            'Waiting for SAP B1 to create the Outgoing Payment…',
                            true
                        );

                        return DdsSapStatusMonitor.poll({
                            invoiceId: invoiceId,
                            statusUrl: statusUrl,
                        });
                    })
                    .then(function(data) {
                        DdsSapStatusMonitor.showOverlayResult(data, invoiceUrl);
                        submitBtn.innerHTML = data.sap_status === 'posted' ?
                            '<i class="fas fa-check"></i> Payment Posted to SAP' :
                            '<i class="fas fa-times"></i> SAP Payment Failed';
                    })
                    .catch(function(error) {
                        DdsSapStatusMonitor.showOverlay(
                            'Submission failed',
                            error.message || 'Unable to submit payment to SAP.',
                            false
                        );
                        document.getElementById('sap-status-spinner').classList.add('d-none');
                        document.getElementById('sap-status-result').classList.remove('d-none');
                        document.getElementById('sap-status-badge').innerHTML =
                            '<span class="badge bg-danger">Submission failed</span>';
                        document.getElementById('sap-status-detail').textContent = error.message ||
                            'Unable to submit payment to SAP.';
                        document.getElementById('sap-status-invoice-link').href = invoiceUrl;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Confirm &amp; Submit Payment to SAP B1';
                    });
            }

            document.getElementById('sap-payment-submit-form').addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Submit Payment to SAP B1?',
                    html: 'Create Outgoing Payment for invoice <strong>' + invoiceNumber + '</strong> ' +
                        'linked to AP Invoice DocEntry <strong>{{ $paymentPreview['ap_invoice']['doc_entry'] }}</strong>?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, submit payment',
                    cancelButtonText: 'Cancel',
                }).then(function(result) {
                    if (result.isConfirmed) {
                        submitPaymentToSap(e.target);
                    }
                });
            });
        })();
    </script>
@endsection
