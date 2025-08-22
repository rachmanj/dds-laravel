@extends('layouts.main')

@section('title_page')
    Invoices Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-invoice-dollar mr-2"></i>
                                Invoices Dashboard
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportInvoiceData()">
                                    <i class="fas fa-download"></i> Export Report
                                </button>
                                <a href="{{ route('invoices.create') }}" class="btn btn-sm btn-success ml-2">
                                    <i class="fas fa-plus"></i> New Invoice
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5>Welcome to your Invoices Financial Center</h5>
                                    <p class="text-muted">
                                        Monitor invoice status, track financial metrics, and analyze supplier performance.
                                        @if (auth()->user()->department)
                                            Currently viewing data for
                                            <strong>{{ auth()->user()->department->name }}</strong> department.
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3>{{ array_sum($statusOverview ?? []) }}</h3>
                                            <p>Total Invoices</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Status Overview -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>
                                Invoice Status Overview
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($statusOverview ?? [] as $status => $count)
                                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                                        <div class="info-box">
                                            <span
                                                class="info-box-icon bg-{{ app('App\Http\Controllers\InvoiceDashboardController')->getStatusColor($status) }} elevation-1">
                                                <i
                                                    class="fas fa-{{ app('App\Http\Controllers\InvoiceDashboardController')->getStatusIcon($status) }}"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">{{ ucwords($status) }}</span>
                                                <span class="info-box-number">{{ $count }}</span>
                                                <div class="progress">
                                                    <div class="progress-bar bg-{{ app('App\Http\Controllers\InvoiceDashboardController')->getStatusColor($status) }}"
                                                        style="width: {{ array_sum($statusOverview ?? []) > 0 ? ($count / array_sum($statusOverview ?? [])) * 100 : 0 }}%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Metrics & Processing Metrics -->
            <div class="row">
                <!-- Financial Metrics -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-2"></i>
                                Financial Metrics (Current Month)
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h3>{{ number_format($financialMetrics['total_amount'] ?? 0, 2) }}</h3>
                                            <p>Total Amount</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3>{{ number_format($financialMetrics['total_paid'] ?? 0, 2) }}</h3>
                                            <p>Total Paid</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h3>{{ number_format($financialMetrics['total_pending'] ?? 0, 2) }}</h3>
                                            <p>Total Pending</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small-box bg-primary">
                                        <div class="inner">
                                            <h3>{{ $financialMetrics['payment_rate'] ?? 0 }}%</h3>
                                            <p>Payment Rate</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if (($financialMetrics['overdue_count'] ?? 0) > 0)
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Overdue Invoices Alert</h6>
                                    <p class="mb-0">
                                        <strong>{{ $financialMetrics['overdue_count'] ?? 0 }}</strong> invoices overdue by
                                        more
                                        than 30 days
                                        (Total: {{ number_format($financialMetrics['overdue_amount'] ?? 0, 2) }})
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Processing Metrics -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cogs mr-2"></i>
                                Processing Metrics
                            </h3>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3">Average Processing Time (Hours)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tbody>
                                        @foreach ($processingMetrics['stage_metrics'] ?? [] as $stage => $hours)
                                            <tr>
                                                <td>{{ ucwords(str_replace('_', ' ', $stage)) }}</td>
                                                <td><span class="badge badge-info">{{ $hours }}h</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="small-box bg-secondary">
                                        <div class="inner">
                                            <h3>{{ $processingMetrics['total_processed'] ?? 0 }}</h3>
                                            <p>Total Processed</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-tasks"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3>{{ number_format($financialMetrics['average_amount'] ?? 0, 2) }}</h3>
                                            <p>Average Amount</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-calculator"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribution Status & Invoice Types -->
            <div class="row">
                <!-- Distribution Status -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-truck mr-2"></i>
                                Distribution Status
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($distributionStatus['status_counts'] ?? [] as $status => $count)
                                    <div class="col-md-6 mb-3">
                                        <div
                                            class="small-box bg-{{ app('App\Http\Controllers\InvoiceDashboardController')->getDistributionStatusColor($status) }}">
                                            <div class="inner">
                                                <h3>{{ $count }}</h3>
                                                <p>{{ ucwords(str_replace('_', ' ', $status)) }}</p>
                                            </div>
                                            <div class="icon">
                                                <i
                                                    class="fas fa-{{ $status === 'available' ? 'check' : ($status === 'in_transit' ? 'truck' : ($status === 'distributed' ? 'download' : 'exclamation-triangle')) }}"></i>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Age Breakdown -->
                            <h6 class="mt-3">Invoice Age Breakdown</h6>
                            <div class="row">
                                @foreach ($distributionStatus['age_breakdown'] ?? [] as $age => $count)
                                    <div class="col-md-4">
                                        <div class="info-box bg-light">
                                            <span
                                                class="info-box-icon bg-{{ $age === '0-7_days' ? 'success' : ($age === '8-14_days' ? 'warning' : 'danger') }}">
                                                <i
                                                    class="fas fa-{{ $age === '0-7_days' ? 'check' : ($age === '8-14_days' ? 'clock' : 'exclamation') }}"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">{{ str_replace('_', ' ', $age) }}</span>
                                                <span class="info-box-number">{{ $count }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Types -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>
                                Invoice Types Breakdown
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="invoiceTypeChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supplier Analysis -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-building mr-2"></i>
                                Supplier Analysis
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-primary">
                                    View All Invoices
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Top Suppliers -->
                                <div class="col-md-6">
                                    <h6>Top Suppliers by Invoice Count</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Supplier</th>
                                                    <th>Invoices</th>
                                                    <th>Total Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (array_slice($supplierAnalysis['top_suppliers'] ?? [], 0, 5) as $supplier => $data)
                                                    <tr>
                                                        <td>{{ $supplier }}</td>
                                                        <td><span
                                                                class="badge badge-info">{{ $data['invoice_count'] ?? 0 }}</span>
                                                        </td>
                                                        <td>{{ number_format($data['total_amount'] ?? 0, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Supplier Performance -->
                                <div class="col-md-6">
                                    <h6>Supplier Payment Performance</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Supplier</th>
                                                    <th>Payment Rate</th>
                                                    <th>Invoices</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (array_slice($supplierAnalysis['supplier_performance'] ?? [], 0, 5) as $supplier => $data)
                                                    <tr>
                                                        <td>{{ $supplier }}</td>
                                                        <td>
                                                            <span
                                                                class="badge badge-{{ ($data['payment_rate'] ?? 0) >= 80 ? 'success' : (($data['payment_rate'] ?? 0) >= 60 ? 'warning' : 'danger') }}">
                                                                {{ $data['payment_rate'] ?? 0 }}%
                                                            </span>
                                                        </td>
                                                        <td>{{ $data['total_invoices'] ?? 0 }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Invoice Type Chart
        const invoiceTypeCtx = document.getElementById('invoiceTypeChart').getContext('2d');
        const invoiceTypeChart = new Chart(invoiceTypeCtx, {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($typeBreakdown ?? [])),
                datasets: [{
                    data: @json(array_column($typeBreakdown ?? [], 'count')),
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6c757d', '#fd7e14',
                        '#6f42c1', '#e83e8c'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Export function
        function exportInvoiceData() {
            const data = {
                statusOverview: @json($statusOverview ?? []),
                financialMetrics: @json($financialMetrics ?? []),
                processingMetrics: @json($processingMetrics ?? []),
                distributionStatus: @json($distributionStatus ?? []),
                supplierAnalysis: @json($supplierAnalysis ?? []),
                typeBreakdown: @json($typeBreakdown ?? []),
                exportDate: new Date().toISOString(),
                user: '{{ auth()->user()->name }}',
                department: '{{ auth()->user()->department->name ?? 'Not Assigned' }}'
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], {
                type: 'application/json'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `invoices-dashboard-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        // Auto-refresh every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
@endpush
