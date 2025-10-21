@extends('layouts.main')

@section('title_page')
    Distribution Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Distributions</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                @if (auth()->user()->hasRole(['superadmin', 'admin']))
                                    Distribution Management
                                @else
                                    Department Distributions
                                @endif
                            </h3>
                            <div class="card-tools">
                                @can('create-distributions')
                                    <a href="{{ route('distributions.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Create Distribution
                                    </a>
                                @endcan
                                @if (auth()->user()->department)
                                    <a href="{{ route('distributions.department-history') }}"
                                        class="btn btn-success btn-sm">
                                        <i class="fas fa-building"></i> Department History
                                    </a>
                                @endif
                                @can('view-distributions-numbering-stats')
                                    <a href="{{ route('distributions.numbering-stats') }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-chart-bar"></i> Numbering Stats
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            @if (!auth()->user()->hasRole(['superadmin', 'admin']))
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Note:</strong> You can see:
                                    <ul class="mb-0 mt-2">
                                        <li><strong>Incoming:</strong> Distributions sent TO your department (status: Sent)
                                            - ready to receive</li>
                                        <li><strong>Outgoing:</strong> Distributions FROM your department (status:
                                            Draft/Sent) - can edit drafts, monitor sent</li>
                                    </ul>
                                </div>
                            @endif

                            <!-- Search and Filter Panel -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="card collapsed-card">
                                        <div class="card-header">
                                            <h3 class="card-title">Search & Filters</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body" style="display: none;">
                                            <form id="searchForm">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="search_distribution_number">Distribution
                                                                Number</label>
                                                            <input type="text" class="form-control"
                                                                id="search_distribution_number"
                                                                placeholder="Search by number">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="search_status">Status</label>
                                                            <select class="form-control" id="search_status">
                                                                <option value="">All Statuses</option>
                                                                <option value="draft">Draft</option>
                                                                <option value="verified_by_sender">Verified by Sender
                                                                </option>
                                                                <option value="sent">Sent</option>
                                                                <option value="received">Received</option>
                                                                <option value="verified_by_receiver">Verified by Receiver
                                                                </option>
                                                                <option value="completed">Completed</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="search_type">Distribution Type</label>
                                                            <select class="form-control" id="search_type">
                                                                <option value="">All Types</option>
                                                                @foreach ($distributionTypes as $type)
                                                                    <option value="{{ $type->id }}">{{ $type->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="search_department">Department</label>
                                                            <select class="form-control" id="search_department">
                                                                <option value="">All Departments</option>
                                                                @foreach ($departments as $dept)
                                                                    <option value="{{ $dept->id }}">{{ $dept->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-md-12">
                                                        <button type="button" class="btn btn-primary" id="applySearch">
                                                            <i class="fas fa-search"></i> Apply Search
                                                        </button>
                                                        <button type="button" class="btn btn-secondary" id="clearSearch">
                                                            <i class="fas fa-times"></i> Clear Search
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Distributions Table -->
                            <div class="table-responsive">
                                @if ($distributions->count() > 0)
                                    <table id="distributions-table" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Distribution Number</th>
                                                <th>Type</th>
                                                <th>Origin</th>
                                                <th>Destination</th>
                                                <th>Status</th>
                                                <th>Documents</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($distributions as $distribution)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <strong>{{ $distribution->distribution_number }}</strong>
                                                        <br>
                                                        <small
                                                            class="text-muted">{{ $distribution->document_type }}</small>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge badge-info">{{ $distribution->type->name }}</span>
                                                    </td>
                                                    <td>{{ $distribution->originDepartment->name }}</td>
                                                    <td>{{ $distribution->destinationDepartment->name }}</td>
                                                    <td>
                                                        @php
                                                            $isIncoming =
                                                                $distribution->destination_department_id ===
                                                                auth()->user()->department->id;
                                                            $isOutgoing =
                                                                $distribution->origin_department_id ===
                                                                auth()->user()->department->id;
                                                        @endphp

                                                        <span class="badge {{ $distribution->status_badge_class }}">
                                                            {{ $distribution->status_display }}
                                                        </span>

                                                        @if ($isIncoming)
                                                            <span class="badge badge-info badge-sm ml-1">
                                                                <i class="fas fa-download"></i> Incoming
                                                            </span>
                                                        @elseif ($isOutgoing)
                                                            <span class="badge badge-warning badge-sm ml-1">
                                                                <i class="fas fa-upload"></i> Outgoing
                                                            </span>
                                                        @endif

                                                        <div class="progress mt-1" style="height: 3px;">
                                                            <div class="progress-bar bg-success"
                                                                style="width: {{ $distribution->workflow_progress }}%">
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge badge-secondary">{{ $distribution->documents->count() }}
                                                            docs</span>
                                                    </td>
                                                    <td>
                                                        {{ $distribution->created_at->format('d-M-Y H:i') }}
                                                        <br>
                                                        <small class="text-muted">by
                                                            {{ $distribution->creator->name }}</small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="{{ route('distributions.show', $distribution) }}"
                                                                class="btn btn-sm btn-info" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @if ($distribution->status === 'draft')
                                                                @can('edit-distributions')
                                                                    <a href="{{ route('distributions.edit', $distribution) }}"
                                                                        class="btn btn-sm btn-warning" title="Edit">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                @endcan
                                                                @can('delete-distributions')
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-danger delete-distribution"
                                                                        data-id="{{ $distribution->id }}"
                                                                        data-number="{{ $distribution->distribution_number }}"
                                                                        title="Delete">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                @endcan
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Department Distributions</h5>
                                <p class="text-muted">
                                    @if (auth()->user()->hasRole(['superadmin', 'admin']))
                                        There are no distributions in the system.
                                    @else
                                        There are no incoming distributions to receive or outgoing distributions to monitor.
                                    @endif
                                </p>
                            </div>
                            @endif

                            <!-- Pagination handled by DataTables -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete distribution <strong
                                    id="deleteDistributionNumber"></strong>?</p>
                            <p class="text-danger">This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
    </section>
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
            var table = $('#distributions-table').DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: 15,
                order: [
                    [0, 'asc']
                ],
                pagingType: 'full_numbers',
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
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });

            // Search functionality
            $('#applySearch').click(function() {
                var distributionNumber = $('#search_distribution_number').val();
                var status = $('#search_status').val();
                var type = $('#search_type').val();
                var department = $('#search_department').val();

                table.draw();

                // Custom filtering
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    var rowDistributionNumber = data[1];
                    var rowStatus = data[5];
                    var rowType = data[2];
                    var rowOrigin = data[3];

                    if (distributionNumber && !rowDistributionNumber.toLowerCase().includes(
                            distributionNumber.toLowerCase())) {
                        return false;
                    }
                    if (status && !rowStatus.toLowerCase().includes(status.toLowerCase())) {
                        return false;
                    }
                    if (type && !rowType.toLowerCase().includes(type.toLowerCase())) {
                        return false;
                    }
                    if (department && !rowOrigin.toLowerCase().includes(department.toLowerCase())) {
                        return false;
                    }
                    return true;
                });

                table.draw();
            });

            // Clear search
            $('#clearSearch').click(function() {
                $('#searchForm')[0].reset();
                $.fn.dataTable.ext.search.pop();
                table.draw();
            });

            // Delete distribution
            $('.delete-distribution').click(function() {
                var id = $(this).data('id');
                var number = $(this).data('number');

                $('#deleteDistributionNumber').text(number);
                $('#deleteModal').modal('show');

                $('#confirmDelete').off('click').on('click', function() {
                    $.ajax({
                        url: '{{ url('distributions') }}/' + id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                toastr.error(response.message ||
                                    'Failed to delete distribution');
                            }
                        },
                        error: function(xhr) {
                            toastr.error('Failed to delete distribution');
                        }
                    });
                    $('#deleteModal').modal('hide');
                });
            });
        });
    </script>
@endsection
