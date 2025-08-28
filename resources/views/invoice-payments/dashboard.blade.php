@extends('invoice-payments.layout')

@section('payment-content')
    <!-- Payment Metrics Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($metrics['total_pending']) }}</h3>
                    <p>Invoices Pending Payment</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <a href="{{ route('invoices.payments.waiting') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($metrics['total_paid']) }}</h3>
                    <p>Invoices Paid</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="{{ route('invoices.payments.paid') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($metrics['overdue_count']) }}</h3>
                    <p>Overdue for Payment</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="{{ route('invoices.payments.waiting') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($metrics['average_payment_days'] ?? 0, 1) }}</h3>
                    <p>Avg. Payment Days</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <a href="{{ route('invoices.payments.paid') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-money-bill-wave mr-2"></i>Financial Summary
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Pending Amount</span>
                                    <span
                                        class="info-box-number">{{ number_format($metrics['total_amount_pending'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Paid Amount</span>
                                    <span
                                        class="info-box-number">{{ number_format($metrics['total_amount_paid'], 2) }}</span>
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
                        <i class="fas fa-chart-line mr-2"></i>Payment Performance
                    </h3>
                </div>
                <div class="card-body">
                    <div class="progress-group">
                        Payment Rate
                        <span class="float-right">
                            <b>{{ $metrics['total_paid'] + $metrics['total_pending'] > 0 ? round(($metrics['total_paid'] / ($metrics['total_paid'] + $metrics['total_pending'])) * 100, 1) : 0 }}%</b>
                        </span>
                        <div class="progress">
                            <div class="progress-bar bg-success"
                                style="width: {{ $metrics['total_paid'] + $metrics['total_pending'] > 0 ? ($metrics['total_paid'] / ($metrics['total_paid'] + $metrics['total_pending'])) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>

                    <div class="progress-group mt-3">
                        Overdue Rate
                        <span class="float-right">
                            <b>{{ $metrics['total_pending'] > 0 ? round(($metrics['overdue_count'] / $metrics['total_pending']) * 100, 1) : 0 }}%</b>
                        </span>
                        <div class="progress">
                            <div class="progress-bar bg-danger"
                                style="width: {{ $metrics['total_pending'] > 0 ? ($metrics['overdue_count'] / $metrics['total_pending']) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments & Overdue Alerts -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-2"></i>Recent Payments
                    </h3>
                </div>
                <div class="card-body p-0">
                    @if ($metrics['recent_payments']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Supplier</th>
                                        <th>Amount</th>
                                        <th>Paid By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($metrics['recent_payments'] as $invoice)
                                        <tr>
                                            <td>
                                                <strong>{{ $invoice->invoice_number }}</strong>
                                                @if ($invoice->po_no)
                                                    <br><small class="text-muted">PO: {{ $invoice->po_no }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $invoice->supplier->name ?? 'N/A' }}</td>
                                            <td>{{ $invoice->formatted_amount }}</td>
                                            <td>{{ $invoice->paidByUser->name ?? 'N/A' }}</td>
                                            <td>{{ $invoice->formatted_paid_at }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center p-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent payments found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Overdue Alerts
                    </h3>
                </div>
                <div class="card-body p-0">
                    @if ($metrics['overdue_invoices']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Supplier</th>
                                        <th>Days Overdue</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($metrics['overdue_invoices'] as $invoice)
                                        <tr>
                                            <td>
                                                <strong>{{ $invoice->invoice_number }}</strong>
                                                @if ($invoice->po_no)
                                                    <br><small class="text-muted">PO: {{ $invoice->po_no }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $invoice->supplier->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-danger">
                                                    {{ $invoice->days_since_received }} days
                                                </span>
                                            </td>
                                            <td>{{ $invoice->formatted_amount }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center p-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-success">No overdue invoices! Great job!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
