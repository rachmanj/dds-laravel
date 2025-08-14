@extends('layouts.main')

@section('title_page', 'Distribution Numbering Statistics')
@section('breadcrumb_title', 'Distribution Numbering Statistics')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i>
                        Distribution Numbering Statistics
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('distributions.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Quick Sequence Generator -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-calculator"></i> Quick Sequence Generator
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form id="sequenceGeneratorForm" class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="year">Year</label>
                                                <select class="form-control" id="year" name="year" required>
                                                    @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                                                        <option value="{{ $y }}"
                                                            {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}
                                                        </option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="department">Department</label>
                                                <select class="form-control" id="department" name="department" required>
                                                    <option value="">Select Department</option>
                                                    @foreach ($departments as $dept)
                                                        <option value="{{ $dept->id }}">{{ $dept->name }}
                                                            ({{ $dept->location_code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="department_code">Department Code</label>
                                                <input type="text" class="form-control" id="department_code"
                                                    name="department_code" placeholder="e.g., IT, HR" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fas fa-search"></i> Get Next Sequence
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <div id="sequenceResult" class="mt-3" style="display: none;">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle"></i> Next Sequence Information</h6>
                                            <div id="sequenceResultContent"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Overview -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $stats['total_distributions'] ?? 0 }}</h3>
                                    <p>Total Distributions</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-share-alt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $stats['current_year_total'] ?? 0 }}</h3>
                                    <p>This Year</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $stats['departments_with_distributions'] ?? 0 }}</h3>
                                    <p>Active Departments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $stats['highest_sequence'] ?? 0 }}</h3>
                                    <p>Highest Sequence</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-sort-numeric-up"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yearly Statistics -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-line"></i> Yearly Distribution Counts
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Year</th>
                                                    <th>Total Distributions</th>
                                                    <th>Departments</th>
                                                    <th>Highest Sequence</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($yearlyStats as $year => $yearData)
                                                    <tr>
                                                        <td><strong>{{ $year }}</strong></td>
                                                        <td>
                                                            <span class="badge badge-info">{{ $yearData['total'] }}</span>
                                                        </td>
                                                        <td>{{ $yearData['departments'] }}</td>
                                                        <td>{{ $yearData['highest_sequence'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-building"></i> Department Statistics
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Department</th>
                                                    <th>Location Code</th>
                                                    <th>Total Distributions</th>
                                                    <th>This Year</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($departmentStats as $dept)
                                                    <tr>
                                                        <td><strong>{{ $dept['name'] }}</strong></td>
                                                        <td>
                                                            <span
                                                                class="badge badge-secondary">{{ $dept['location_code'] }}</span>
                                                        </td>
                                                        <td>{{ $dept['total'] }}</td>
                                                        <td>
                                                            <span
                                                                class="badge badge-info">{{ $dept['current_year'] }}</span>
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

                    <!-- Detailed Sequence Analysis -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-list-ol"></i> Sequence Analysis by Department & Year
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="sequenceTable">
                                            <thead>
                                                <tr>
                                                    <th>Year</th>
                                                    <th>Department</th>
                                                    <th>Location Code</th>
                                                    <th>Current Sequence</th>
                                                    <th>Next Available</th>
                                                    <th>Last Used</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($sequenceAnalysis as $analysis)
                                                    <tr>
                                                        <td><strong>{{ $analysis['year'] }}</strong></td>
                                                        <td>{{ $analysis['department_name'] }}</td>
                                                        <td>
                                                            <span
                                                                class="badge badge-secondary">{{ $analysis['location_code'] }}</span>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge badge-info">{{ $analysis['current_sequence'] }}</span>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge badge-success">{{ $analysis['next_sequence'] }}</span>
                                                        </td>
                                                        <td>
                                                            @if ($analysis['last_used'] && method_exists($analysis['last_used'], 'format'))
                                                                <small
                                                                    class="text-muted">{{ $analysis['last_used']->format('d-M-Y H:i') }}</small>
                                                            @else
                                                                <span class="text-muted">Never</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($analysis['current_sequence'] == 0)
                                                                <span class="badge badge-warning">No distributions
                                                                    yet</span>
                                                            @elseif($analysis['current_sequence'] > 999999)
                                                                <span class="badge badge-danger">Sequence limit
                                                                    reached</span>
                                                            @else
                                                                <span class="badge badge-success">Active</span>
                                                            @endif
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

                    <!-- Numbering Format Examples -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-info-circle"></i> Numbering Format Examples
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Format: {YY}/{ORIGIN_DEPARTMENT_CODE}/DDS/0001</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Example</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><code>25/IT/DDS/001</code></td>
                                                            <td>IT Department, 2025, Sequence 1</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>25/HR/DDS/015</code></td>
                                                            <td>HR Department, 2025, Sequence 15</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>24/FIN/DDS/999</code></td>
                                                            <td>Finance Department, 2024, Sequence 999</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Rules & Constraints</h6>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item">
                                                    <i class="fas fa-check text-success"></i>
                                                    <strong>Year:</strong> 2-digit format (24, 25, 26...)
                                                </li>
                                                <li class="list-group-item">
                                                    <i class="fas fa-check text-success"></i>
                                                    <strong>Department Code:</strong> 2-3 character location code
                                                </li>
                                                <li class="list-group-item">
                                                    <i class="fas fa-check text-success"></i>
                                                    <strong>DDS:</strong> Fixed identifier for Distribution Document System
                                                </li>
                                                <li class="list-group-item">
                                                    <i class="fas fa-check text-success"></i>
                                                    <strong>Sequence:</strong> Auto-incrementing number (001, 002, 003...)
                                                </li>
                                                <li class="list-group-item">
                                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                                    <strong>Limit:</strong> Maximum sequence number is 999,999
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#sequenceTable').DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: 25,
                order: [
                    [0, 'desc'],
                    [1, 'asc']
                ],
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

            // Department change handler
            $('#department').change(function() {
                var selectedOption = $(this).find('option:selected');
                var locationCode = selectedOption.text().match(/\(([^)]+)\)/);
                if (locationCode) {
                    $('#department_code').val(locationCode[1]);
                } else {
                    $('#department_code').val('');
                }
            });

            // Sequence generator form
            $('#sequenceGeneratorForm').submit(function(e) {
                e.preventDefault();

                var year = $('#year').val();
                var department = $('#department').val();

                if (!year || !department) {
                    toastr.error('Please select both year and department');
                    return false;
                }

                // Get next sequence
                $.ajax({
                    url: '{{ route('distributions.next-sequence') }}',
                    type: 'GET',
                    data: {
                        year: year,
                        department_id: department
                    },
                    success: function(response) {
                        if (response.success) {
                            var resultHtml = '<strong>Next Sequence:</strong> ' + response
                                .next_sequence + '<br>';
                            resultHtml += '<strong>Full Distribution Number:</strong> <code>' +
                                response.distribution_number + '</code><br>';
                            resultHtml += '<strong>Department Code:</strong> ' + $(
                                '#department_code').val() + '<br>';
                            resultHtml += '<strong>Year:</strong> ' + year;

                            $('#sequenceResultContent').html(resultHtml);
                            $('#sequenceResult').show();
                        } else {
                            toastr.error(response.message || 'Failed to get next sequence');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to get next sequence');
                    }
                });
            });
        });
    </script>
@endsection
