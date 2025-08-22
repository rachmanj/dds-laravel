@extends('layouts.main')

@section('title_page')
    Distributions Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('distributions.index') }}">Distributions</a></li>
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
                                <i class="fas fa-truck mr-2"></i>
                                Distributions Dashboard
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="exportDistributionData()">
                                    <i class="fas fa-download"></i> Export Report
                                </button>
                                <a href="{{ route('distributions.create') }}" class="btn btn-sm btn-success ml-2">
                                    <i class="fas fa-plus"></i> New Distribution
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5>Welcome to your Distributions Workflow Center</h5>
                                    <p class="text-muted">
                                        Monitor distribution status, track workflow performance, and manage pending actions.
                                        @if (auth()->user()->department)
                                            Currently viewing data for
                                            <strong>{{ auth()->user()->department->name }}</strong> department.
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3>{{ array_sum($statusOverview) }}</h3>
                                            <p>Total Distributions</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribution Status Overview -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>
                                Distribution Status Overview
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($statusOverview as $status => $count)
                                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                                        <div class="info-box">
                                            <span
                                                class="info-box-icon bg-{{ app('App\Http\Controllers\DistributionDashboardController')->getStatusColor($status) }} elevation-1">
                                                <i class="fas fa-truck"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span
                                                    class="info-box-text">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                                                <span class="info-box-number">{{ $count }}</span>
                                                <div class="progress">
                                                    <div class="progress-bar bg-{{ app('App\Http\Controllers\DistributionDashboardController')->getStatusColor($status) }}"
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

            <!-- Workflow Performance & Pending Actions -->
            <div class="row">
                <!-- Workflow Performance -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-2"></i>
                                Workflow Performance
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h3>{{ $workflowMetrics['total_completed'] }}</h3>
                                            <p>Completed Distributions</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-flag-checkered"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3>{{ $workflowMetrics['average_completion_time'] }}</h3>
                                            <p>Avg Hours to Complete</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stage Performance -->
                            <h6 class="mt-3">Stage Performance (Hours)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tbody>
                                        @foreach ($workflowMetrics['stage_metrics'] as $stage => $hours)
                                            <tr>
                                                <td>{{ ucwords(str_replace('_', ' ', $stage)) }}</td>
                                                <td><span class="badge badge-info">{{ $hours }}h</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Actions -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Pending Actions
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if ($pendingActions['sender_verification'] > 0)
                                    <div class="col-md-6 mb-3">
                                        <div class="small-box bg-warning">
                                            <div class="inner">
                                                <h3>{{ $pendingActions['sender_verification'] }}</h3>
                                                <p>Need Sender Verification</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                            <a href="{{ route('distributions.index') }}?status=draft"
                                                class="small-box-footer">
                                                Take Action <i class="fas fa-arrow-circle-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                @if ($pendingActions['waiting_to_send'] > 0)
                                    <div class="col-md-6 mb-3">
                                        <div class="small-box bg-info">
                                            <div class="inner">
                                                <h3>{{ $pendingActions['waiting_to_send'] }}</h3>
                                                <p>Ready to Send</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-paper-plane"></i>
                                            </div>
                                            <a href="{{ route('distributions.index') }}?status=verified_by_sender"
                                                class="small-box-footer">
                                                Take Action <i class="fas fa-arrow-circle-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                @if ($pendingActions['waiting_to_receive'] > 0)
                                    <div class="col-md-6 mb-3">
                                        <div class="small-box bg-primary">
                                            <div class="inner">
                                                <h3>{{ $pendingActions['waiting_to_receive'] }}</h3>
                                                <p>Waiting to Receive</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-download"></i>
                                            </div>
                                            <a href="{{ route('distributions.index') }}?status=sent"
                                                class="small-box-footer">
                                                Take Action <i class="fas fa-arrow-circle-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                @if ($pendingActions['receiver_verification'] > 0)
                                    <div class="col-md-6 mb-3">
                                        <div class="small-box bg-success">
                                            <div class="inner">
                                                <h3>{{ $pendingActions['receiver_verification'] }}</h3>
                                                <p>Need Receiver Verification</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                            <a href="{{ route('distributions.index') }}?status=received"
                                                class="small-box-footer">
                                                Take Action <i class="fas fa-arrow-circle-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if (array_sum($pendingActions) == 0)
                                <div class="text-center text-success">
                                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                                    <h5>All Caught Up!</h5>
                                    <p>No pending actions at the moment.</p>
                                </div>
                            @endif
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
                                Distribution Types Breakdown
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="distributionTypeChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Department Performance
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="departmentPerformanceChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>
                                Recent Activity
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('distributions.index') }}" class="btn btn-sm btn-primary">
                                    View All Distributions
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                @forelse($recentActivity as $activity)
                                    <div>
                                        <i
                                            class="fas fa-{{ app('App\Http\Controllers\DistributionDashboardController')->getActivityIcon($activity->action) }} bg-{{ app('App\Http\Controllers\DistributionDashboardController')->getActivityColor($activity->action) }}"></i>
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
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Distribution Type Chart
        const distributionTypeCtx = document.getElementById('distributionTypeChart').getContext('2d');
        const distributionTypeChart = new Chart(distributionTypeCtx, {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($typeBreakdown)),
                datasets: [{
                    data: @json(array_values($typeBreakdown)),
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6c757d', '#fd7e14'],
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

        // Department Performance Chart
        const departmentPerformanceCtx = document.getElementById('departmentPerformanceChart').getContext('2d');
        const departmentPerformanceChart = new Chart(departmentPerformanceCtx, {
            type: 'bar',
            data: {
                labels: @json(array_keys($departmentPerformance)),
                datasets: [{
                    label: 'Created',
                    data: @json(array_column($departmentPerformance, 'created')),
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }, {
                    label: 'Received',
                    data: @json(array_column($departmentPerformance, 'received')),
                    backgroundColor: 'rgba(23, 162, 184, 0.8)',
                    borderColor: '#17a2b8',
                    borderWidth: 1
                }, {
                    label: 'Completed',
                    data: @json(array_column($departmentPerformance, 'completed')),
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: '#ffc107',
                    borderWidth: 1
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
        function exportDistributionData() {
            const data = {
                statusOverview: @json($statusOverview),
                workflowMetrics: @json($workflowMetrics),
                pendingActions: @json($pendingActions),
                departmentPerformance: @json($departmentPerformance),
                typeBreakdown: @json($typeBreakdown),
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
            a.download = `distributions-dashboard-${new Date().toISOString().split('T')[0]}.json`;
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
