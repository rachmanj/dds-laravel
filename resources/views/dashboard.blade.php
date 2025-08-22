@extends('layouts.main')

@section('title_page')
    Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/">Home</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- User Info Card -->
            @auth
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user mr-2"></i>
                                    Welcome back, {{ auth()->user()->name }}!
                                </h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="exportDashboardData()">
                                        <i class="fas fa-download"></i> Export Report
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                                                <p><strong>Roles:</strong>
                                                    @foreach (auth()->user()->roles as $role)
                                                        <span class="badge badge-primary">{{ $role->name }}</span>
                                                    @endforeach
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Department:</strong>
                                                    {{ auth()->user()->department->name ?? 'Not Assigned' }}</p>
                                                <p><strong>Location:</strong>
                                                    {{ auth()->user()->department_location_code ?? 'Not Assigned' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="user-panel">
                                            <img src="{{ asset('adminlte/dist/img/user2-160x160.jpg') }}"
                                                class="img-circle elevation-2" alt="User Image"
                                                style="width: 80px; height: 80px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endauth

            <!-- Critical Alerts -->
            @if (($metrics['overdue_documents'] ?? 0) > 0 || ($metrics['unaccounted_documents'] ?? 0) > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        @if (($metrics['overdue_documents'] ?? 0) > 0)
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <h5><i class="icon fas fa-exclamation-triangle"></i> Critical Alert!</h5>
                                <strong>{{ $metrics['overdue_documents'] }}</strong> documents have been in your department
                                for more than 14 days and require immediate attention.
                                <a href="{{ route('additional-documents.index') }}?show_overdue=1" class="alert-link">View
                                    Overdue Documents</a>
                            </div>
                        @endif

                        @if (($metrics['unaccounted_documents'] ?? 0) > 0)
                            <div class="alert alert-warning alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <h5><i class="icon fas fa-question-circle"></i> Discrepancy Alert!</h5>
                                <strong>{{ $metrics['unaccounted_documents'] }}</strong> documents are marked as missing or
                                damaged and need investigation.
                                <a href="{{ route('distributions.index') }}?has_discrepancies=1" class="alert-link">View
                                    Discrepancies</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Critical Workflow Metrics -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box {{ ($metrics['pending_distributions'] ?? 0) > 0 ? 'bg-warning' : 'bg-success' }}">
                        <span class="info-box-icon bg-warning elevation-1">
                            <i class="fas fa-truck"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pending Distributions</span>
                            <span class="info-box-number">{{ $metrics['pending_distributions'] ?? 0 }}</span>
                            <div class="progress">
                                <div class="progress-bar bg-warning"
                                    style="width: {{ min(($metrics['pending_distributions'] ?? 0) * 20, 100) }}%"></div>
                            </div>
                            <span class="progress-description">
                                @if (($metrics['pending_distributions'] ?? 0) > 0)
                                    <span class="text-warning">⚠️ Action Required</span>
                                @else
                                    <span class="text-success">✅ All Clear</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info elevation-1">
                            <i class="fas fa-shipping-fast"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">In Transit</span>
                            <span class="info-box-number">{{ $metrics['in_transit_documents'] ?? 0 }}</span>
                            <div class="progress">
                                <div class="progress-bar bg-info"
                                    style="width: {{ min(($metrics['in_transit_documents'] ?? 0) * 20, 100) }}%"></div>
                            </div>
                            <span class="progress-description">
                                Documents on the way
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box {{ ($metrics['overdue_documents'] ?? 0) > 0 ? 'bg-danger' : 'bg-success' }}">
                        <span class="info-box-icon bg-danger elevation-1">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Overdue Documents</span>
                            <span class="info-box-number">{{ $metrics['overdue_documents'] ?? 0 }}</span>
                            <div class="progress">
                                <div class="progress-bar bg-danger"
                                    style="width: {{ min(($metrics['overdue_documents'] ?? 0) * 20, 100) }}%"></div>
                            </div>
                            <span class="progress-description">
                                @if (($metrics['overdue_documents'] ?? 0) > 0)
                                    <span class="text-danger">🚨 Critical - >14 days</span>
                                @else
                                    <span class="text-success">✅ All Current</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box {{ ($metrics['unaccounted_documents'] ?? 0) > 0 ? 'bg-warning' : 'bg-success' }}">
                        <span class="info-box-icon bg-secondary elevation-1">
                            <i class="fas fa-question-circle"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Unaccounted</span>
                            <span class="info-box-number">{{ $metrics['unaccounted_documents'] ?? 0 }}</span>
                            <div class="progress">
                                <div class="progress-bar bg-secondary"
                                    style="width: {{ min(($metrics['unaccounted_documents'] ?? 0) * 20, 100) }}%"></div>
                            </div>
                            <span class="progress-description">
                                @if (($metrics['unaccounted_documents'] ?? 0) > 0)
                                    <span class="text-warning">⚠️ Investigation Needed</span>
                                @else
                                    <span class="text-success">✅ All Accounted For</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>
                                Document Status Distribution
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="documentStatusChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-2"></i>
                                Document Age Trend (Last 30 Days)
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="documentAgeTrendChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Age Breakdown -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>
                                Document Age Breakdown in Your Department
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-info">Total:
                                    {{ ($documentAgeBreakdown['0_7_days'] ?? 0) + ($documentAgeBreakdown['8_14_days'] ?? 0) + ($documentAgeBreakdown['15_plus_days'] ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h3>{{ $documentAgeBreakdown['0_7_days'] ?? 0 }}</h3>
                                            <p>Documents (0-7 days)</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <a href="{{ route('additional-documents.index') }}?age_filter=0_7"
                                            class="small-box-footer">
                                            View Details <i class="fas fa-arrow-circle-right"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h3>{{ $documentAgeBreakdown['8_14_days'] ?? 0 }}</h3>
                                            <p>Documents (8-14 days)</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <a href="{{ route('additional-documents.index') }}?age_filter=8_14"
                                            class="small-box-footer">
                                            View Details <i class="fas fa-arrow-circle-right"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small-box bg-danger">
                                        <div class="inner">
                                            <h3>{{ $documentAgeBreakdown['15_plus_days'] ?? 0 }}</h3>
                                            <p>Documents (15+ days)</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <a href="{{ route('additional-documents.index') }}?age_filter=15_plus"
                                            class="small-box-footer">
                                            View Details <i class="fas fa-arrow-circle-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bolt mr-2"></i>
                                Quick Actions
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @can('create-distributions')
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="{{ route('distributions.create') }}" class="text-decoration-none">
                                            <div class="card card-outline card-primary h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-paper-plane fa-3x text-primary mb-3"></i>
                                                    <h5 class="card-title">Create Distribution</h5>
                                                    <p class="card-text text-muted">Send documents to another department</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endcan

                                @if (($metrics['pending_distributions'] ?? 0) > 0)
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="{{ route('distributions.index') }}?status=sent"
                                            class="text-decoration-none">
                                            <div class="card card-outline card-warning h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-download fa-3x text-warning mb-3"></i>
                                                    <h5 class="card-title">Receive Documents</h5>
                                                    <p class="card-text text-muted">
                                                        {{ $metrics['pending_distributions'] }} pending distributions</p>
                                                    <span class="badge badge-warning">Action Required</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endif

                                @if (($metrics['overdue_documents'] ?? 0) > 0)
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="{{ route('additional-documents.index') }}?show_overdue=1"
                                            class="text-decoration-none">
                                            <div class="card card-outline card-danger h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                                                    <h5 class="card-title">View Overdue</h5>
                                                    <p class="card-text text-muted">{{ $metrics['overdue_documents'] }}
                                                        documents need attention</p>
                                                    <span class="badge badge-danger">Critical</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endif

                                @can('view-distributions')
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="{{ route('distributions.index') }}" class="text-decoration-none">
                                            <div class="card card-outline card-info h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-list fa-3x text-info mb-3"></i>
                                                    <h5 class="card-title">All Distributions</h5>
                                                    <p class="card-text text-muted">View and manage all distributions</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Distributions -->
            @if (($metrics['pending_distributions'] ?? 0) > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-clock mr-2"></i>
                                    Pending Distributions
                                </h3>
                                <div class="card-tools">
                                    <a href="{{ route('distributions.index') }}?status=sent"
                                        class="btn btn-sm btn-primary">
                                        View All
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Distribution #</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Type</th>
                                                <th>Sent Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($pendingDistributions as $distribution)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $distribution->distribution_number }}</strong>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge badge-info">{{ $distribution->originDepartment->name ?? 'N/A' }}</span>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge badge-warning">{{ $distribution->destinationDepartment->name ?? 'N/A' }}</span>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge badge-secondary">{{ $distribution->type->name ?? 'N/A' }}</span>
                                                    </td>
                                                    <td>
                                                        {{ $distribution->sent_at ? $distribution->sent_at->format('d-M-Y H:i') : 'N/A' }}
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('distributions.show', $distribution) }}"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
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
            @endif

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>
                                Recent Activity
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                @forelse($recentActivity as $activity)
                                    <div>
                                        <i
                                            class="fas fa-{{ $getActivityIcon($activity->action) }} bg-{{ $getActivityColor($activity->action) }}"></i>
                                        <div class="timeline-item">
                                            <span class="time">
                                                <i class="fas fa-clock"></i>
                                                {{ $activity->action_performed_at->diffForHumans() }}
                                            </span>
                                            <h3 class="timeline-header">
                                                {{ $activity->user->name ?? 'System' }} -
                                                {{ ucwords(str_replace('_', ' ', $activity->action)) }}
                                            </h3>
                                            <div class="timeline-body">
                                                @if ($activity->distribution)
                                                    Distribution:
                                                    <strong>{{ $activity->distribution->distribution_number }}</strong>
                                                    @if ($activity->old_status && $activity->new_status)
                                                        <br>Status:
                                                        {{ ucwords(str_replace('_', ' ', $activity->old_status)) }} →
                                                        {{ ucwords(str_replace('_', ' ', $activity->new_status)) }}
                                                    @endif
                                                @endif
                                                @if ($activity->notes)
                                                    <br><em>{{ $activity->notes }}</em>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div>
                                        <i class="fas fa-info bg-gray"></i>
                                        <div class="timeline-item">
                                            <div class="timeline-body">
                                                No recent activity to display.
                                            </div>
                                        </div>
                                    </div>
                                @endforelse
                                <div>
                                    <i class="fas fa-clock bg-gray"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role-Based Content -->
            <div class="row">
                @role('super-admin')
                    <div class="col-12">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-crown mr-2"></i>
                                    Super Admin Panel
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Super Admin Access!</h5>
                                    You have full access to all system features and can manage the entire application.
                                </div>
                                <p class="text-muted">You can manage users, roles, permissions, content, and system settings.
                                </p>
                            </div>
                        </div>
                    </div>
                @endrole

                @role('admin')
                    <div class="col-12">
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user-shield mr-2"></i>
                                    Admin Panel
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-success">
                                    <h5><i class="icon fas fa-check"></i> Admin Access!</h5>
                                    You have extensive permissions to manage most system features.
                                </div>
                                <p class="text-muted">You can manage users, content, and settings, but cannot modify roles.</p>
                            </div>
                        </div>
                    </div>
                @endrole
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Document Status Distribution Chart
        const documentStatusCtx = document.getElementById('documentStatusChart').getContext('2d');
        const documentStatusChart = new Chart(documentStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'In Transit', 'Distributed', 'Unaccounted'],
                datasets: [{
                    data: [
                        {{ ($metrics['pending_distributions'] ?? 0) + ($metrics['in_transit_documents'] ?? 0) }},
                        {{ $metrics['in_transit_documents'] ?? 0 }},
                        {{ ($documentAgeBreakdown['0_7_days'] ?? 0) + ($documentAgeBreakdown['8_14_days'] ?? 0) + ($documentAgeBreakdown['15_plus_days'] ?? 0) }},
                        {{ $metrics['unaccounted_documents'] ?? 0 }}
                    ],
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#6c757d'],
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

        // Document Age Trend Chart
        const documentAgeTrendCtx = document.getElementById('documentAgeTrendChart').getContext('2d');
        const documentAgeTrendChart = new Chart(documentAgeTrendCtx, {
            type: 'line',
            data: {
                labels: ['0-7 days', '8-14 days', '15+ days'],
                datasets: [{
                    label: 'Document Count',
                    data: [
                        {{ $documentAgeBreakdown['0_7_days'] ?? 0 }},
                        {{ $documentAgeBreakdown['8_14_days'] ?? 0 }},
                        {{ $documentAgeBreakdown['15_plus_days'] ?? 0 }}
                    ],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
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

        // Auto-refresh dashboard metrics every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000); // 5 minutes

        // Initialize tooltips
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Auto-dismiss alerts after 10 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 10000);

        // Export dashboard data function
        function exportDashboardData() {
            const data = {
                metrics: @json($metrics),
                documentAgeBreakdown: @json($documentAgeBreakdown),
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
            a.download = `dashboard-report-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        // Real-time notifications (simulated)
        function checkForUpdates() {
            // This would typically connect to a WebSocket or polling endpoint
            // For now, we'll simulate real-time updates
            console.log('Checking for updates...');
        }

        // Check for updates every 30 seconds
        setInterval(checkForUpdates, 30000);
    </script>
@endpush
