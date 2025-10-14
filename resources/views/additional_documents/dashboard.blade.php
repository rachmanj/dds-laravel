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

            <!-- Critical Alerts Banner -->
            <div class="row mb-4">
                <div class="col-12">
                    @if ($departmentAlerts['overdue_critical'] > 0)
                        <div class="alert alert-danger alert-dismissible">
                            <h4><i class="icon fas fa-exclamation-triangle"></i> Critical Alert!</h4>
                            <strong>{{ $departmentAlerts['overdue_critical'] }}</strong> documents have been in your
                            department for over 30 days and require immediate attention.
                            <a href="{{ route('additional-documents.index', ['age_filter' => '30_plus', 'status_filter' => 'available,in_transit']) }}"
                                class="btn btn-danger btn-sm ml-2">
                                <i class="fas fa-eye"></i> View Critical Documents
                            </a>
                        </div>
                    @endif

                    @if ($departmentAlerts['overdue_warning'] > 0)
                        <div class="alert alert-warning alert-dismissible">
                            <h4><i class="icon fas fa-clock"></i> Warning!</h4>
                            <strong>{{ $departmentAlerts['overdue_warning'] }}</strong> documents have been in your
                            department for 14-30 days.
                            <a href="{{ route('additional-documents.index', ['age_filter' => '15_30', 'status_filter' => 'available,in_transit']) }}"
                                class="btn btn-warning btn-sm ml-2">
                                <i class="fas fa-eye"></i> View Warning Documents
                            </a>
                        </div>
                    @endif

                    @if ($departmentAlerts['stuck_documents'] > 0)
                        <div class="alert alert-info alert-dismissible">
                            <h4><i class="icon fas fa-info-circle"></i> Attention!</h4>
                            <strong>{{ $departmentAlerts['stuck_documents'] }}</strong> documents have been available in
                            your department for over 7 days without being distributed.
                            <a href="{{ route('additional-documents.index', ['age_filter' => '8_14', 'status_filter' => 'available']) }}"
                                class="btn btn-info btn-sm ml-2">
                                <i class="fas fa-eye"></i> View Stuck Documents
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Enhanced Age & Status Metrics -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clock mr-2"></i>
                                Document Age in Current Department
                                <small class="text-muted">(Based on arrival at
                                    {{ auth()->user()->department->name ?? 'your department' }})</small>
                                @if ($departmentAlerts['overdue_critical'] > 0 || $departmentAlerts['overdue_warning'] > 0)
                                    <span class="badge badge-danger ml-2">Action Required</span>
                                @endif
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="refreshAgeAnalysis()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Enhanced Age Cards with Action Buttons -->
                            <div class="row">
                                @foreach ($ageAndStatus['age_breakdown'] as $age => $count)
                                    <div class="col-md-3 mb-3">
                                        <div
                                            class="info-box {{ $count > 0 && $age === '30_plus_days' ? 'bg-danger' : 'bg-light' }}">
                                            <span
                                                class="info-box-icon bg-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getAgeColor($age) }}">
                                                <i
                                                    class="fas fa-{{ $age === '0-7_days' ? 'check' : ($age === '8-14_days' ? 'clock' : ($age === '15-30_days' ? 'calendar' : 'exclamation-triangle')) }}"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">
                                                    {{ str_replace('_', ' ', $age) }}
                                                    @if ($age === '30_plus_days' && $count > 0)
                                                        <span class="badge badge-danger ml-1">URGENT</span>
                                                    @endif
                                                </span>
                                                <span class="info-box-number">{{ $count }}</span>
                                                <div class="progress">
                                                    <div class="progress-bar bg-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getAgeColor($age) }}"
                                                        style="width: {{ array_sum($ageAndStatus['age_breakdown']) > 0 ? ($count / array_sum($ageAndStatus['age_breakdown'])) * 100 : 0 }}%">
                                                    </div>
                                                </div>
                                                @if ($count > 0)
                                                    <a href="{{ route('additional-documents.index', ['age_filter' => str_replace('_', '_', $age)]) }}"
                                                        class="btn btn-sm btn-outline-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getAgeColor($age) }} mt-2">
                                                        <i class="fas fa-eye"></i> View Documents
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Enhanced Status by Age Table with Action Links -->
                            <h6 class="mt-4">
                                Status Breakdown by Age
                                <small class="text-muted">(Click counts to view specific documents)</small>
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Age Group</th>
                                            <th>Available</th>
                                            <th>In Transit</th>
                                            <th>Distributed</th>
                                            <th>Unaccounted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ageAndStatus['status_by_age'] as $age => $statuses)
                                            <tr
                                                class="{{ $age === '30_plus_days' && array_sum($statuses) > 0 ? 'table-danger' : '' }}">
                                                <td>
                                                    <strong>{{ str_replace('_', ' ', $age) }}</strong>
                                                    @if ($age === '30_plus_days' && array_sum($statuses) > 0)
                                                        <span class="badge badge-danger ml-1">CRITICAL</span>
                                                    @endif
                                                </td>
                                                @foreach (['available', 'in_transit', 'distributed', 'unaccounted_for'] as $status)
                                                    <td>
                                                        @if ($statuses[$status] > 0)
                                                            <a href="{{ route('additional-documents.index', ['age_filter' => str_replace('_', '_', $age), 'status_filter' => $status]) }}"
                                                                class="badge badge-{{ app('App\Http\Controllers\AdditionalDocumentDashboardController')->getStatusColor($status) }} badge-clickable">
                                                                {{ $statuses[$status] }}
                                                            </a>
                                                        @else
                                                            <span
                                                                class="badge badge-secondary">{{ $statuses[$status] }}</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td>
                                                    @if (array_sum($statuses) > 0)
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('additional-documents.index', ['age_filter' => str_replace('_', '_', $age)]) }}"
                                                                class="btn btn-outline-primary btn-xs">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @if ($age === '30_plus_days' && ($statuses['available'] > 0 || $statuses['in_transit'] > 0))
                                                                <a href="{{ route('distributions.create', ['urgent' => true, 'age_filter' => $age]) }}"
                                                                    class="btn btn-outline-danger btn-xs">
                                                                    <i class="fas fa-paper-plane"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Department-specific aging explanation -->
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> How Aging is Calculated</h6>
                                <p class="mb-0">
                                    Document aging is calculated based on when each document <strong>arrived at your current
                                        department</strong>,
                                    not when it was originally created or first received. This ensures accurate tracking of
                                    how long
                                    documents have been in your department's possession.
                                </p>
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
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
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

        // Enhanced refresh functionality
        function refreshAgeAnalysis() {
            // Show loading indicator
            const refreshBtn = document.querySelector('[onclick="refreshAgeAnalysis()"]');
            const originalText = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;

            // Reload the page after a short delay to show the loading state
            setTimeout(() => {
                location.reload();
            }, 500);
        }

        // Auto-refresh every 2 minutes for critical alerts
        setInterval(function() {
            // Only refresh if there are critical alerts
            const criticalAlerts = @json($departmentAlerts ?? []);
            if (criticalAlerts.overdue_critical > 0 || criticalAlerts.overdue_warning > 0) {
                location.reload();
            }
        }, 120000); // 2 minutes

        // Add click handlers for badge links
        document.addEventListener('DOMContentLoaded', function() {
            const clickableBadges = document.querySelectorAll('.badge-clickable');
            clickableBadges.forEach(badge => {
                badge.addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            });
        });

        // Auto-refresh every 5 minutes for general updates
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
@endpush
