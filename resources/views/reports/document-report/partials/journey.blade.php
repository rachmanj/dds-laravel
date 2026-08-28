@php
    $document = $journey['document'];
    $distributions = $journey['distributions'];
    $stats = $journey['stats'];
    $departmentTimeStats = $journey['departmentTimeStats'];
    $documentType = $journey['documentType'];
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-route"></i> Distribution Journey</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Current Location:</strong> {{ $document->cur_loc ?? 'N/A' }}<br>
                <strong>Distribution Status:</strong>
                <span class="badge badge-{{ ($document->distribution_status ?? 'available') === 'available' ? 'success' : (($document->distribution_status ?? '') === 'in_transit' ? 'warning' : 'info') }}">
                    {{ ucfirst(str_replace('_', ' ', $document->distribution_status ?? 'available')) }}
                </span>
            </div>
            <div class="col-md-6">
                <strong>Total Distributions:</strong> {{ $stats['total_distributions'] }}<br>
                <strong>Departments Visited:</strong> {{ $stats['total_departments_visited'] }}
            </div>
        </div>

        @if ($distributions->count() > 0)
            <div class="row mb-3">
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $stats['journey_duration'] ?? 0 }}</h3>
                            <p>Total Journey Days</p>
                        </div>
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $stats['total_distance'] ?? 0 }}</h3>
                            <p>Total Transfers</p>
                        </div>
                        <div class="icon"><i class="fas fa-exchange-alt"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $stats['avg_time_per_department'] ?? 0 }}</h3>
                            <p>Avg Days per Dept</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ $stats['total_departments_visited'] }}</h3>
                            <p>Departments Visited</p>
                        </div>
                        <div class="icon"><i class="fas fa-building"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>{{ $stats['total_distributions'] }}</h3>
                            <p>Total Distributions</p>
                        </div>
                        <div class="icon"><i class="fas fa-truck"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-dark">
                        <div class="inner">
                            <h3>{{ isset($stats['journey_start']) ? $stats['journey_start']->format('d-M-Y') : 'N/A' }}</h3>
                            <p>Journey Started</p>
                        </div>
                        <div class="icon"><i class="fas fa-play"></i></div>
                    </div>
                </div>
            </div>

            @if (!empty($departmentTimeStats))
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Visits</th>
                                <th>Total Time (Days)</th>
                                <th>Average Time (Days)</th>
                                <th>First Visit</th>
                                <th>Last Visit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($departmentTimeStats as $dept)
                                <tr>
                                    <td><strong>{{ $dept['name'] }}</strong></td>
                                    <td><span class="badge badge-info">{{ $dept['visits'] }}</span></td>
                                    <td>{{ $dept['total_time'] }}</td>
                                    <td>{{ $dept['avg_time'] }}</td>
                                    <td>{{ $dept['first_visit'] ? $dept['first_visit']->format('d-M-Y') : 'N/A' }}</td>
                                    <td>{{ $dept['last_visit'] ? $dept['last_visit']->format('d-M-Y') : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <h5 class="mb-3"><i class="fas fa-history"></i> Timeline</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Distribution #</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Sent</th>
                            <th>Received</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($distributions as $distribution)
                            <tr>
                                <td>{{ $distribution->distribution_number }}</td>
                                <td>{{ $distribution->originDepartment->name ?? '-' }}</td>
                                <td>{{ $distribution->destinationDepartment->name ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $distribution->status === 'completed' ? 'success' : 'primary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $distribution->status)) }}
                                    </span>
                                </td>
                                <td>{{ $distribution->created_at->format('d-M-Y H:i') }}</td>
                                <td>{{ $distribution->sent_at?->format('d-M-Y H:i') ?? '-' }}</td>
                                <td>{{ $distribution->received_at?->format('d-M-Y H:i') ?? '-' }}</td>
                                <td>{{ $distribution->creator->name ?? '-' }}</td>
                            </tr>
                            @if ($distribution->histories->count() > 0)
                                <tr>
                                    <td colspan="8" class="bg-light small text-muted">
                                        @foreach ($distribution->histories->take(3) as $history)
                                            • {{ $history->action_display }} by {{ $history->user->name ?? 'N/A' }}
                                            ({{ $history->time_ago }})<br>
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">This document has not been distributed yet.</p>
        @endif
    </div>
</div>
