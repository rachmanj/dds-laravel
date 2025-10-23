@extends('layouts.main')

@section('title_page')
    Department Distribution History
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('distributions.index') }}">Distributions</a></li>
    <li class="breadcrumb-item active">Department History</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Enhanced Statistics Cards -->
            <div class="row mb-3">
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $stats['total_sent'] }}</h3>
                            <p>Total Sent</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $stats['total_received'] }}</h3>
                            <p>Total Received</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $stats['pending_sent'] }}</h3>
                            <p>Pending Sent</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ $stats['pending_received'] }}</h3>
                            <p>Pending Received</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>{{ $stats['avg_days_before_distribution'] }}</h3>
                            <p>Avg Days Before Distribution</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-dark">
                        <div class="inner">
                            <h3>{{ $stats['avg_processing_days'] }}</h3>
                            <p>Avg Processing Days</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trends Chart -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-chart-line"></i> Monthly Distribution Trends (Last 6 Months)
                            </h4>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyTrendsChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-building"></i> {{ auth()->user()->department->name }} Distribution History
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('distributions.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to Distributions
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filter and Search -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary active" data-filter="all">
                                            All
                                        </button>
                                        <button type="button" class="btn btn-outline-success" data-filter="outgoing">
                                            Outgoing
                                        </button>
                                        <button type="button" class="btn btn-outline-info" data-filter="incoming">
                                            Incoming
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="searchInput"
                                            placeholder="Search distributions...">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="startDate" placeholder="Start Date">
                                        <input type="date" class="form-control" id="endDate" placeholder="End Date">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="filterByDate">
                                                <i class="fas fa-filter"></i> Filter
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" id="clearDateFilter">
                                                <i class="fas fa-times"></i> Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Distributions Table -->
                            @if ($distributions->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Distribution #</th>
                                                <th>Type</th>
                                                <th>Direction</th>
                                                <th>Department</th>
                                                <th>Status</th>
                                                <th>Created By</th>
                                                <th>Created</th>
                                                <th>Last Action</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($distributions as $distribution)
                                                <tr
                                                    data-direction="{{ $distribution->origin_department_id == auth()->user()->department_id ? 'outgoing' : 'incoming' }}">
                                                    <td>
                                                        <strong>{{ $distribution->distribution_number }}</strong>
                                                    </td>
                                                    <td>{{ $distribution->type->name ?? 'N/A' }}</td>
                                                    <td>
                                                        @if ($distribution->origin_department_id == auth()->user()->department_id)
                                                            <span class="badge badge-success">
                                                                <i class="fas fa-arrow-up"></i> Outgoing
                                                            </span>
                                                        @else
                                                            <span class="badge badge-info">
                                                                <i class="fas fa-arrow-down"></i> Incoming
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($distribution->origin_department_id == auth()->user()->department_id)
                                                            <span class="badge badge-info">
                                                                {{ $distribution->destinationDepartment->name }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-info">
                                                                {{ $distribution->originDepartment->name }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @switch($distribution->status)
                                                            @case('draft')
                                                                <span class="badge badge-secondary">Draft</span>
                                                            @break

                                                            @case('verified_by_sender')
                                                                <span class="badge badge-warning">Verified by Sender</span>
                                                            @break

                                                            @case('sent')
                                                                <span class="badge badge-primary">Sent</span>
                                                            @break

                                                            @case('received')
                                                                <span class="badge badge-info">Received</span>
                                                            @break

                                                            @case('verified_by_receiver')
                                                                <span class="badge badge-success">Verified by Receiver</span>
                                                            @break

                                                            @case('completed')
                                                                <span class="badge badge-success">Completed</span>
                                                            @break
                                                        @endswitch
                                                    </td>
                                                    <td>{{ $distribution->creator->name }}</td>
                                                    <td>{{ $distribution->created_at->format('d-M-Y H:i') }}</td>
                                                    <td>
                                                        @if ($distribution->histories->count() > 0)
                                                            <small class="text-muted">
                                                                {{ $distribution->histories->first()->time_ago }}
                                                            </small>
                                                        @else
                                                            <span class="text-muted">No actions</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('distributions.show', $distribution) }}"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        @can('view-document-distribution-history')
                                                            @foreach ($distribution->documents as $doc)
                                                                <a href="{{ route('distributions.document.distribution-history', ['document_type' => class_basename($doc->document_type) === 'Invoice' ? 'invoice' : 'additional-document', 'document_id' => $doc->document_id]) }}"
                                                                    class="btn btn-sm btn-info" title="View Document History">
                                                                    <i class="fas fa-route"></i> Doc
                                                                </a>
                                                            @endforeach
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $distributions->links() }}
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Department Distribution History</h5>
                                    <p class="text-muted">Your department hasn't been involved in any distributions yet.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- JavaScript for Filtering, Search, and Chart -->
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            // Filter functionality
            $('[data-filter]').click(function() {
                const filter = $(this).data('filter');

                $('[data-filter]').removeClass('active');
                $(this).addClass('active');

                if (filter === 'all') {
                    $('tbody tr').show();
                } else {
                    $('tbody tr').hide();
                    $('tbody tr[data-direction="' + filter + '"]').show();
                }
            });

            // Search functionality
            $('#searchInput').on('keyup', function() {
                const value = $(this).val().toLowerCase();
                $('tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Date filtering functionality
            $('#filterByDate').click(function() {
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();

                if (!startDate || !endDate) {
                    alert('Please select both start and end dates');
                    return;
                }

                $('tbody tr').each(function() {
                    const row = $(this);
                    const createdDate = row.find('td:eq(6)').text(); // Created date column
                    const dateMatch = isDateInRange(createdDate, startDate, endDate);
                    row.toggle(dateMatch);
                });
            });

            $('#clearDateFilter').click(function() {
                $('#startDate').val('');
                $('#endDate').val('');
                $('tbody tr').show();
            });

            // Helper function to check if date is in range
            function isDateInRange(dateString, startDate, endDate) {
                const date = new Date(dateString);
                const start = new Date(startDate);
                const end = new Date(endDate);

                // Set time to start/end of day for accurate comparison
                start.setHours(0, 0, 0, 0);
                end.setHours(23, 59, 59, 999);

                return date >= start && date <= end;
            }

            // Monthly Trends Chart
            const ctx = document.getElementById('monthlyTrendsChart').getContext('2d');
            const monthlyData = @json($monthlyStats);

            const labels = Object.values(monthlyData).map(item => item.month);
            const sentData = Object.values(monthlyData).map(item => item.sent);
            const receivedData = Object.values(monthlyData).map(item => item.received);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Sent',
                            data: sentData,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1,
                            fill: true
                        },
                        {
                            label: 'Received',
                            data: receivedData,
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.1,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribution Volume Trends'
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Distributions'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
