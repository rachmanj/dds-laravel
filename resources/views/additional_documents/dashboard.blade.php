@extends('layouts.main')

@section('title_page')
    Additional Documents Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('additional-documents.index') }}">Additional Documents</a></li>
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
                                <i class="fas fa-file-alt mr-2"></i>
                                Additional Documents Dashboard
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="exportAdditionalDocumentData()">
                                    <i class="fas fa-download"></i> Export Report
                                </button>
                                <a href="{{ route('additional-documents.create') }}" class="btn btn-sm btn-success ml-2">
                                    <i class="fas fa-plus"></i> New Document
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5>Welcome to your Additional Documents Management Center</h5>
                                    <p class="text-muted">
                                        Monitor document status, analyze types and sources, and track workflow efficiency.
                                        @if (auth()->user()->department)
                                            Currently viewing data for
                                            <strong>{{ auth()->user()->department->name }}</strong> department.
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3>{{ $workflowMetrics['total_documents'] }}</h3>
                                            <p>Total Documents</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Status Overview -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>
                                Document Status Overview
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($statusOverview as $status => $count)
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="info-box">
                                            <span
                                                class="info-box-icon bg-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getStatusColor($status) }} elevation-1">
                                                <i
                                                    class="fas fa-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getStatusIcon($status) }}"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span
                                                    class="info-box-text">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                                                <span class="info-box-number">{{ $count }}</span>
                                                <div class="progress">
                                                    <div class="progress-bar bg-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getStatusColor($status) }}"
                                                        style="width: {{ array_sum($statusOverview) > 0 ? ($count / array_sum($statusOverview)) * 100 : 0 }}%">
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

            <!-- Document Types & Sources -->
            <div class="row">
                <!-- Document Types -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>
                                Document Types Breakdown
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="documentTypeChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Document Sources -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Document Sources Analysis
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($typeAnalysis['source_breakdown'] as $source => $count)
                                    <div class="col-md-6 mb-3">
                                        <div
                                            class="small-box bg-{{ $source === 'ito_documents' ? 'info' : ($source === 'po_documents' ? 'success' : ($source === 'grpo_documents' ? 'warning' : 'secondary')) }}">
                                            <div class="inner">
                                                <h3>{{ $count }}</h3>
                                                <p>{{ ucwords(str_replace('_', ' ', $source)) }}</p>
                                            </div>
                                            <div class="icon">
                                                <i
                                                    class="fas fa-{{ $source === 'ito_documents' ? 'file-invoice' : ($source === 'po_documents' ? 'shopping-cart' : ($source === 'grpo_documents' ? 'truck' : 'file')) }}"></i>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Age & Status Metrics -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clock mr-2"></i>
                                Document Age & Status Analysis
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($ageAndStatus['age_breakdown'] as $age => $count)
                                    <div class="col-md-3 mb-3">
                                        <div class="info-box bg-light">
                                            <span
                                                class="info-box-icon bg-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getAgeColor($age) }}">
                                                <i
                                                    class="fas fa-{{ $age === '0-7_days' ? 'check' : ($age === '8-14_days' ? 'clock' : ($age === '15-30_days' ? 'calendar' : 'exclamation')) }}"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">{{ str_replace('_', ' ', $age) }}</span>
                                                <span class="info-box-number">{{ $count }}</span>
                                                <div class="progress">
                                                    <div class="progress-bar bg-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getAgeColor($age) }}"
                                                        style="width: {{ array_sum($ageAndStatus['age_breakdown']) > 0 ? ($count / array_sum($ageAndStatus['age_breakdown'])) * 100 : 0 }}%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Status by Age Table -->
                            <h6 class="mt-4">Status Breakdown by Age</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Age Group</th>
                                            <th>Available</th>
                                            <th>In Transit</th>
                                            <th>Distributed</th>
                                            <th>Unaccounted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ageAndStatus['status_by_age'] as $age => $statuses)
                                            <tr>
                                                <td><strong>{{ str_replace('_', ' ', $age) }}</strong></td>
                                                @foreach (['available', 'in_transit', 'distributed', 'unaccounted_for'] as $status)
                                                    <td>
                                                        <span
                                                            class="badge badge-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getStatusColor($status) }}">
                                                            {{ $statuses[$status] }}
                                                        </span>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workflow Metrics & PO Analysis -->
            <div class="row">
                <!-- Workflow Metrics -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-2"></i>
                                Workflow Metrics
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h3>{{ $workflowMetrics['distributed_documents'] }}</h3>
                                            <p>Distributed</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h3>{{ $workflowMetrics['in_transit_documents'] }}</h3>
                                            <p>In Transit</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3>{{ $workflowMetrics['distribution_efficiency'] }}%</h3>
                                            <p>Distribution Efficiency</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small-box bg-danger">
                                        <div class="inner">
                                            <h3>{{ $workflowMetrics['unaccounted_documents'] }}</h3>
                                            <p>Unaccounted</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Monthly Trend -->
                            <h6 class="mt-3">Monthly Document Creation Trend</h6>
                            <canvas id="monthlyTrendChart"
                                style="min-height: 200px; height: 200px; max-height: 200px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- PO Analysis -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                PO Number Analysis
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="small-box bg-primary">
                                        <div class="inner">
                                            <h3>{{ $poAnalysis['total_with_po'] }}</h3>
                                            <p>Documents with PO</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h3>{{ $poAnalysis['unique_po_count'] }}</h3>
                                            <p>Unique PO Numbers</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-hashtag"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3>{{ $poAnalysis['linked_to_invoices'] }}</h3>
                                            <p>Linked to Invoices</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-link"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h3>{{ $poAnalysis['linkage_rate'] }}%</h3>
                                            <p>Linkage Rate</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top PO Numbers -->
                            @if (count($poAnalysis['po_distribution']) > 0)
                                <h6 class="mt-3">Top PO Numbers by Document Count</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>PO Number</th>
                                                <th>Document Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($poAnalysis['po_distribution'] as $po => $count)
                                                <tr>
                                                    <td><code>{{ $po }}</code></td>
                                                    <td><span class="badge badge-info">{{ $count }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Analysis -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                Location & Movement Analysis
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('additional-documents.index') }}" class="btn btn-sm btn-primary">
                                    View All Documents
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Current Location -->
                                <div class="col-md-4">
                                    <h6>Current Location Breakdown</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                @foreach ($locationAnalysis['current_location'] as $location => $count)
                                                    <tr>
                                                        <td>{{ $location ?: 'Not Set' }}</td>
                                                        <td><span class="badge badge-info">{{ $count }}</span></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Origin Warehouse -->
                                <div class="col-md-4">
                                    <h6>Origin Warehouse Breakdown</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                @foreach ($locationAnalysis['origin_warehouse'] as $warehouse => $count)
                                                    <tr>
                                                        <td>{{ $warehouse ?: 'Not Set' }}</td>
                                                        <td><span class="badge badge-success">{{ $count }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Destination -->
                                <div class="col-md-4">
                                    <h6>Destination Breakdown</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                @foreach ($locationAnalysis['destination'] as $destination => $count)
                                                    <tr>
                                                        <td>{{ $destination ?: 'Not Set' }}</td>
                                                        <td><span class="badge badge-warning">{{ $count }}</span>
                                                        </td>
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
        // Document Type Chart
        const documentTypeCtx = document.getElementById('documentTypeChart').getContext('2d');
        const documentTypeChart = new Chart(documentTypeCtx, {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($typeAnalysis['type_breakdown'])),
                datasets: [{
                    data: @json(array_values($typeAnalysis['type_breakdown'])),
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

        // Monthly Trend Chart
        const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        const monthlyTrendChart = new Chart(monthlyTrendCtx, {
            type: 'line',
            data: {
                labels: @json(array_keys($workflowMetrics['monthly_breakdown'])),
                datasets: [{
                    label: 'Documents Created',
                    data: @json(array_values($workflowMetrics['monthly_breakdown'])),
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: '#28a745',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Export function
        function exportAdditionalDocumentData() {
            const data = {
                statusOverview: @json($statusOverview),
                typeAnalysis: @json($typeAnalysis),
                ageAndStatus: @json($ageAndStatus),
                locationAnalysis: @json($locationAnalysis),
                poAnalysis: @json($poAnalysis),
                workflowMetrics: @json($workflowMetrics),
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
            a.download = `additional-documents-dashboard-${new Date().toISOString().split('T')[0]}.json`;
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
