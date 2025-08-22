@extends('layouts.main')

@section('title_page')
    Additional Documents Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Additional Documents</li>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
    <!-- Bootstrap Switch -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/bootstrap-switch/css/bootstrap4-switch.min.css') }}">

    <style>
        .btn-group .btn {
            margin-right: 2px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .search-card .card-tools button {
            background: none;
            border: none;
            color: #6c757d;
        }

        .search-card .card-tools button:hover {
            color: #495057;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .distribution-info {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* Days column badge styling */
        .badge.badge-success {
            background-color: #28a745;
        }

        .badge.badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge.badge-danger {
            background-color: #dc3545;
        }

        .badge.badge-info {
            background-color: #17a2b8;
        }

        /* Date column styling */
        .date-column {
            white-space: nowrap;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
    </style>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Search and Filter Card -->
            <div class="card search-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-search"></i> Search & Filter
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="display: none;">
                    <form id="search-form">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search_number">Document Number</label>
                                    <input type="text" class="form-control" id="search_number" name="search_number"
                                        placeholder="Search by document number">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search_po_no">PO Number</label>
                                    <input type="text" class="form-control" id="search_po_no" name="search_po_no"
                                        placeholder="Search by PO number">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_type">Document Type</label>
                                    <select class="form-control" id="filter_type" name="filter_type">
                                        <option value="">All Types</option>
                                        @foreach ($documentTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_status">Status</label>
                                    <select class="form-control" id="filter_status" name="filter_status">
                                        <option value="">All Statuses</option>
                                        <option value="open">Open</option>
                                        <option value="closed">Closed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_range">Date Range</label>
                                    <input type="text" class="form-control" id="date_range" name="date_range"
                                        placeholder="Select date range">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="show_all_records">Show All Records</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="show_all_records">
                                        <label class="custom-control-label" for="show_all_records">Include in-transit
                                            documents</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="d-flex">
                                        <button type="submit" class="btn btn-primary mr-2">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="reset-search">
                                            <i class="fas fa-undo"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt"></i> Additional Documents
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('additional-documents.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Document
                        </a>
                        <a href="{{ route('additional-documents.import') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-upload"></i> Import Documents
                        </a>
                        {{-- <a href="{{ route('additional-documents.download-template') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-download"></i> Download Template
                        </a> --}}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="documents-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Document Number</th>
                                    <th>PO Number</th>
                                    <th>Type</th>
                                    <th>Document Date</th>
                                    <th>Receive Date</th>
                                    <th>Current Location</th>
                                    <th>Status</th>
                                    <th>Days</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <!-- Bootstrap Switch -->
    <script src="{{ asset('adminlte/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <!-- Moment.js -->
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
    <!-- Date Range Picker -->
    <script src="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Bootstrap Switch
            $('input[data-bootstrap-switch]').bootstrapSwitch();

            // Initialize Date Range Picker
            $('#date_range').daterangepicker({
                opens: 'left',
                locale: {
                    format: 'DD/MM/YYYY'
                }
            });

            // Clear date range on page load and ensure it's empty by default
            $('#date_range').val('');

            // Initialize DataTable
            var table = $('#documents-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('additional-documents.data') }}',
                    data: function(d) {
                        d.search_number = $('#search_number').val();
                        d.search_po_no = $('#search_po_no').val();
                        d.filter_type = $('#filter_type').val();
                        d.filter_status = $('#filter_status').val();
                        d.date_range = $('#date_range').val();
                        d.show_all_records = $('#show_all_records').is(':checked') ? 1 : 0;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'document_number',
                        name: 'document_number'
                    },
                    {
                        data: 'po_no',
                        name: 'po_no'
                    },
                    {
                        data: 'type.type_name',
                        name: 'type.type_name'
                    },
                    {
                        data: 'document_date',
                        name: 'document_date',
                        className: 'date-column',
                        render: function(data, type, row) {
                            if (data) {
                                return moment(data).format('DD-MMM-YYYY');
                            }
                            return '-';
                        }
                    },
                    {
                        data: 'receive_date',
                        name: 'receive_date',
                        className: 'date-column',
                        render: function(data, type, row) {
                            if (data) {
                                return moment(data).format('DD-MMM-YYYY');
                            }
                            return '-';
                        }
                    },
                    {
                        data: 'cur_loc',
                        name: 'cur_loc'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'days_difference',
                        name: 'days_difference'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [9, 'desc']
                ],
                pageLength: 25,
                responsive: true,
                language: {
                    // Using English language instead of CDN Indonesian file to avoid CORS issues
                    "emptyTable": "No data available in table",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "lengthMenu": "Show _MENU_ entries",
                    "loadingRecords": "Loading...",
                    "processing": "Processing...",
                    "search": "Search:",
                    "zeroRecords": "No matching records found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });

            // Search form submission
            $('#search-form').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // Reset search
            $('#reset-search').on('click', function() {
                $('#search-form')[0].reset();
                $('#date_range').val(''); // Clear date range input
                table.ajax.reload();
            });

            // Toggle show all records
            $('#show_all_records').on('switchChange.bootstrapSwitch', function() {
                table.ajax.reload();
            });

            // Handle search card collapse
            $('.search-card .card-tools button').click(function() {
                var card = $(this).closest('.search-card');
                var cardBody = card.find('.card-body');
                var icon = $(this).find('i');

                if (cardBody.is(':visible')) {
                    cardBody.slideUp();
                    icon.removeClass('fa-minus').addClass('fa-plus');
                } else {
                    cardBody.slideDown();
                    icon.removeClass('fa-plus').addClass('fa-minus');
                }
            });

            // Delete document with SweetAlert2
            $(document).on('click', '.delete-document', function(e) {
                e.preventDefault();
                var documentId = $(this).data('id');
                var documentNumber = $(this).data('number');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete document: " + documentNumber,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait while we delete the document.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Make AJAX delete request
                        $.ajax({
                            url: '/additional-documents/' + documentId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Deleted!',
                                    'Document has been deleted successfully.',
                                    'success'
                                ).then(() => {
                                    table.ajax.reload();
                                });
                            },
                            error: function(xhr) {
                                var errorMessage =
                                    'An error occurred while deleting the document.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }

                                Swal.fire(
                                    'Error!',
                                    errorMessage,
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            // Show document details - redirect to show page instead of modal
            $(document).on('click', '.show-document', function(e) {
                e.preventDefault();
                var documentId = $(this).data('id');

                // Redirect to the show page instead of opening modal
                window.location.href = '/additional-documents/' + documentId;
            });

            // Success message display
            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@endsection
