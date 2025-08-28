@extends('invoice-payments.layout')

@section('payment-content')
    <!-- Search and Filter -->
    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" action="{{ route('invoices.payments.paid') }}" class="form-inline">
                <div class="input-group mr-2">
                    <input type="text" name="search" class="form-control" placeholder="Search invoice, PO, or supplier..."
                        value="{{ request('search') }}">
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
@endsection

@section('scripts')
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
        });
    </script>
@endsection
