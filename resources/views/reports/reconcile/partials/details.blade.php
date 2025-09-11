<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">External Invoice Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Invoice Number</th>
                        <td>{{ $reconcile->invoice_no }}</td>
                    </tr>
                    <tr>
                        <th>Invoice Date</th>
                        <td>{{ $reconcile->invoice_date ? $reconcile->invoice_date->format('Y-m-d') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Supplier</th>
                        <td>{{ $reconcile->supplier ? $reconcile->supplier->name : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Uploaded By</th>
                        <td>{{ $reconcile->user ? $reconcile->user->name : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Uploaded At</th>
                        <td>{{ $reconcile->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header {{ $matchingInvoice ? 'bg-success' : 'bg-danger' }} text-white">
                <h5 class="card-title mb-0">Internal Invoice Details</h5>
            </div>
            <div class="card-body">
                @if ($matchingInvoice)
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Invoice Number</th>
                            <td>{{ $matchingInvoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <th>Faktur Number</th>
                            <td>{{ $matchingInvoice->faktur_no ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Supplier</th>
                            <td>{{ $matchingInvoice->supplier ? $matchingInvoice->supplier->name : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Receive Date</th>
                            <td>{{ $matchingInvoice->receive_date ? $matchingInvoice->receive_date->format('Y-m-d') : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td>{{ number_format($matchingInvoice->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>SAP Document</th>
                            <td>{{ $matchingInvoice->sap_doc ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Payment Date</th>
                            <td>{{ $matchingInvoice->payment_date ? $matchingInvoice->payment_date->format('Y-m-d') : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>{{ $matchingInvoice->status }}</td>
                        </tr>
                    </table>
                @else
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> No matching invoice found in the system.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
