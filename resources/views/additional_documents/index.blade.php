@extends('layouts.main')

@section('title_page')
    Additional Documents Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Additional Documents</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Search and Filter Card -->
                    <div class="card search-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-search"></i> Search & Filter
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="search-form">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="search_number">Document Number</label>
                                            <input type="text" class="form-control" id="search_number"
                                                name="search_number" placeholder="Search by document number">
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
                                            <label for="search_vendor_code">Vendor Code</label>
                                            <input type="text" class="form-control" id="search_vendor_code"
                                                name="search_vendor_code" placeholder="Search by vendor code">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="search_content">Content Search</label>
                                            <input type="text" class="form-control" id="search_content"
                                                name="search_content" placeholder="Search in remarks and attachments">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
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
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filter_vendor_code">Vendor Code</label>
                                            <select class="form-control" id="filter_vendor_code" name="filter_vendor_code">
                                                <option value="">All Vendor Codes</option>
                                                @foreach ($vendorCodes ?? [] as $vendorCode)
                                                    <option value="{{ $vendorCode }}">{{ $vendorCode }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filter_location">Location</label>
                                            <select class="form-control" id="filter_location" name="filter_location">
                                                <option value="">All Locations</option>
                                                @foreach ($departments ?? [] as $dept)
                                                    <option value="{{ $dept->location_code }}">{{ $dept->location_code }} -
                                                        {{ $dept->name }}</option>
                                                @endforeach
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
                                            <label for="date_type">Date Type</label>
                                            <select class="form-control" id="date_type" name="date_type">
                                                <option value="created_at">Created Date</option>
                                                <option value="document_date">Document Date</option>
                                                <option value="receive_date">Receive Date</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="search_preset">Search Presets</label>
                                            <div class="input-group">
                                                <select class="form-control" id="search_preset" name="search_preset">
                                                    <option value="">Select Preset</option>
                                                    <option value="recent">Recent Documents (Last 30 days)</option>
                                                    <option value="open">Open Documents</option>
                                                    <option value="my_department">My Department Only</option>
                                                    <option value="this_month">This Month</option>
                                                    <option value="last_month">Last Month</option>
                                                </select>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-success"
                                                        id="save-preset" title="Save Current Search">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @can('see-all-record-switch')
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="show_all_records">
                                                    <input type="checkbox" id="show_all_records" data-bootstrap-switch>
                                                    Show All Records
                                                </label>
                                                <small class="form-text text-muted">
                                                    Toggle to view all documents across all locations
                                                </small>
                                            </div>
                                        </div>
                                    @endcan
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <button type="submit" class="btn btn-primary mr-2">
                                                        <i class="fas fa-search"></i> Search
                                                    </button>
                                                    <button type="button" class="btn btn-secondary mr-2"
                                                        id="reset-search">
                                                        <i class="fas fa-undo"></i> Reset
                                                    </button>
                                                    <button type="button" class="btn btn-info mr-2" id="export-results">
                                                        <i class="fas fa-download"></i> Export Results
                                                    </button>
                                                </div>
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle"></i>
                                                        Use multiple criteria for advanced filtering. Search presets help
                                                        save time.
                                                    </small>
                                                </div>
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
                                @can('import-additional-documents')
                                    <a href="{{ route('additional-documents.import') }}" class="btn btn-success btn-sm">
                                        <i class="fas fa-upload"></i> Import Documents
                                    </a>
                                @endcan
                                {{-- <a href="{{ route('additional-documents.download-template') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-download"></i> Download Template
                        </a> --}}
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 600px;">
                                <table id="documents-table"
                                    class="table table-bordered table-striped table-fixed-header compact-table">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>No</th>
                                            <th>Doc No</th>
                                            <th>DocDate</th>
                                            <th>Type</th>
                                            <th>PO No</th>
                                            <th>VendorCode</th>
                                            <th>Inv No</th>
                                            <th>RecDate</th>
                                            <th>CurLoc</th>
                                            <th>Days</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
    <!-- Bootstrap Switch -->
    <link rel="stylesheet"
        href="{{ asset('adminlte/plugins/bootstrap-switch/css/bootstrap3/bootstrap-switch.min.css') }}">

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

        /* Fixed table header styles */
        .table-fixed-header {
            position: relative;
        }

        .table-fixed-header thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #343a40;
            color: white;
        }

        .table-fixed-header thead th {
            background-color: #343a40 !important;
            color: white !important;
            border-color: #495057 !important;
            font-weight: bold;
            padding: 8px 4px;
            font-size: 0.85rem;
        }

        /* Compact table styling */
        .table-fixed-header tbody td {
            padding: 6px 4px;
            font-size: 0.8rem;
            vertical-align: middle;
        }

        .table-fixed-header tbody td.text-right {
            text-align: right;
        }

        .table-responsive {
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }

        /* Ensure proper scrolling */
        .table-responsive::-webkit-scrollbar {
            width: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Compact column widths and alignment */
        .compact-table th:nth-child(1),
        .compact-table td:nth-child(1) {
            width: 40px;
            text-align: right;
        }

        /* No - Right aligned */
        .compact-table th:nth-child(2),
        .compact-table td:nth-child(2) {
            width: 120px;
        }

        /* Document Number */
        .compact-table th:nth-child(3),
        .compact-table td:nth-child(3) {
            width: 100px;
            text-align: center;
        }

        /* PO Number - Center aligned */
        .compact-table th:nth-child(4),
        .compact-table td:nth-child(4) {
            width: 90px;
        }

        /* Vendor Code */
        .compact-table th:nth-child(5),
        .compact-table td:nth-child(5) {
            width: 80px;
        }

        /* Type */
        .compact-table th:nth-child(6),
        .compact-table td:nth-child(6) {
            width: 90px;
            text-align: center;
        }

        /* Document Date - Center aligned */
        .compact-table th:nth-child(7),
        .compact-table td:nth-child(7) {
            width: 90px;
            text-align: center;
        }

        /* Receive Date - Center aligned */
        .compact-table th:nth-child(8),
        .compact-table td:nth-child(8) {
            width: 100px;
            text-align: center;
        }

        /* Current Location - Center aligned */
        .compact-table th:nth-child(9),
        .compact-table td:nth-child(9) {
            width: 80px;
            text-align: center;
        }

        /* Status - Center aligned */
        .compact-table th:nth-child(10),
        .compact-table td:nth-child(10) {
            width: 60px;
        }

        /* Days */
        .compact-table th:nth-child(11),
        .compact-table td:nth-child(11) {
            width: 120px;
        }

        /* Actions */

        /* Additional compact styling */
        .compact-table {
            table-layout: fixed;
        }

        .compact-table td,
        .compact-table th {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Allow text wrapping for specific columns */
        .compact-table td:nth-child(2),
        .compact-table th:nth-child(2) {
            /* Document Number */
            white-space: normal;
            word-wrap: break-word;
        }

        .compact-table td:nth-child(3),
        .compact-table th:nth-child(3) {
            /* PO Number */
            white-space: normal;
            word-wrap: break-word;
        }

        .compact-table td:nth-child(8),
        .compact-table th:nth-child(8) {
            /* Current Location */
            white-space: normal;
            word-wrap: break-word;
        }

        /* Ensure action buttons are compact */
        .compact-table .btn {
            padding: 2px 6px;
            font-size: 0.75rem;
            line-height: 1.2;
        }

        .compact-table .btn-group .btn {
            padding: 2px 4px;
        }
    </style>
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
            // Initialize search card in collapsed state (must be first)
            $('.search-card .card-body').hide();

            // Initialize Bootstrap Switch
            $("input[data-bootstrap-switch]").each(function() {
                $(this).bootstrapSwitch();
            });

            // Initialize Date Range Picker
            $('#date_range').daterangepicker({
                opens: 'left',
                locale: {
                    format: 'DD/MM/YYYY'
                }
            });

            // Clear date range on page load and ensure it's empty by default
            $('#date_range').val('');

            // Get URL parameters
            function getUrlParameter(name) {
                name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
                var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                var results = regex.exec(location.search);
                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            }

            // Initialize DataTable
            var table = $('#documents-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('additional-documents.data') }}',
                    data: function(d) {
                        d.search_number = $('#search_number').val();
                        d.search_po_no = $('#search_po_no').val();
                        d.search_vendor_code = $('#search_vendor_code').val();
                        d.search_content = $('#search_content').val();
                        d.filter_type = $('#filter_type').val();
                        d.filter_status = $('#filter_status').val();
                        d.filter_vendor_code = $('#filter_vendor_code').val();
                        d.date_range = $('#date_range').val();
                        d.show_all = $('#show_all_records').length > 0 && $('#show_all_records').is(
                            ':checked') ? 1 : 0;
                        // Pass age_filter from URL
                        var ageFilter = getUrlParameter('age_filter');
                        if (ageFilter) {
                            d.age_filter = ageFilter;
                        }
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '40px',
                        className: 'text-right'
                    },
                    {
                        data: 'document_number',
                        name: 'document_number',
                        width: '120px'
                    },
                    {
                        data: 'document_date',
                        name: 'document_date',
                        width: '90px',
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (data) {
                                return moment(data).format('DD-MMM-YY');
                            }
                            return '-';
                        }
                    },
                    {
                        data: 'type.type_name',
                        name: 'type.type_name',
                        width: '80px'
                    },
                    {
                        data: 'po_no',
                        name: 'po_no',
                        width: '100px',
                        className: 'text-center'
                    },
                    {
                        data: 'vendor_code',
                        name: 'vendor_code',
                        width: '90px',
                        render: function(data, type, row) {
                            if (data) {
                                return '<span class="badge badge-secondary">' + data + '</span>';
                            }
                            return '<span class="text-muted">-</span>';
                        }
                    },
                    {
                        data: 'invoice_numbers',
                        name: 'invoice_numbers',
                        orderable: false,
                        searchable: false,
                        width: '120px'
                    },
                    {
                        data: 'receive_date',
                        name: 'receive_date',
                        width: '90px',
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (data) {
                                return moment(data).format('DD-MMM-YY');
                            }
                            return '-';
                        }
                    },
                    {
                        data: 'cur_loc',
                        name: 'cur_loc',
                        width: '100px',
                        className: 'text-center'
                    },
                    {
                        data: 'days_difference',
                        name: 'days_difference',
                        className: 'text-right',
                        width: '60px'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: '120px'
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
                if ($('#show_all_records').length > 0) {
                    $('#show_all_records').bootstrapSwitch('state', false);
                }
                table.ajax.reload();
            });

            // Toggle show all records
            if ($('#show_all_records').length > 0) {
                $('#show_all_records').on('switchChange.bootstrapSwitch', function() {
                    table.ajax.reload();
                });
            }

            // Handle search card collapse
            $('.search-card .card-tools button').click(function() {
                var card = $(this).closest('.search-card');
                var cardBody = card.find('.card-body');
                var icon = $(this).find('i');

                if (cardBody.is(':visible')) {
                    cardBody.hide();
                    icon.removeClass('fa-minus').addClass('fa-plus');
                } else {
                    cardBody.show();
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
                            url: '{{ url('additional-documents') }}/' + documentId,
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
                window.location.href = '{{ url('additional-documents') }}/' + documentId;
            });

            // ENHANCED SEARCH FEATURES

            // Initialize date range picker with enhanced options
            $('#date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    applyLabel: 'Apply',
                    format: 'DD/MM/YYYY',
                    separator: ' - ',
                    customRangeLabel: 'Custom Range'
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')],
                    'This Quarter': [moment().startOf('quarter'), moment().endOf('quarter')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')]
                },
                opens: 'left',
                drops: 'down'
            });

            // Handle date range selection
            $('#date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format(
                    'DD/MM/YYYY'));
                table.ajax.reload();
            });

            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                table.ajax.reload();
            });

            // Search presets functionality
            $('#save-preset').on('click', function() {
                var presetName = prompt('Enter a name for this search preset:');
                if (presetName && presetName.trim() !== '') {
                    saveSearchPreset(presetName.trim());
                }
            });

            $('#load-preset').on('change', function() {
                var presetId = $(this).val();
                if (presetId) {
                    loadSearchPreset(presetId);
                }
            });

            // Export results functionality
            $('#export-results').on('click', function() {
                exportSearchResults();
            });

            // Real-time search with debouncing
            var searchTimeout;
            $('#search-form input, #search-form select').on('input change', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    table.ajax.reload();
                }, 500); // 500ms delay
            });

            // Advanced search functions
            function saveSearchPreset(name) {
                var formData = $('#search-form').serialize();

                $.ajax({
                    url: '{{ route('additional-documents.search-presets.store') }}',
                    method: 'POST',
                    data: {
                        name: name,
                        filters: formData,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toastr.success('Search preset saved successfully!');
                        // Reload presets dropdown
                        loadSearchPresets();
                    },
                    error: function(xhr) {
                        toastr.error('Failed to save search preset');
                    }
                });
            }

            function loadSearchPreset(presetId) {
                $.ajax({
                    url: '{{ route('additional-documents.search-presets.show', ':id') }}'.replace(':id',
                        presetId),
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Populate form with preset data
                            var filters = response.data.filters;
                            Object.keys(filters).forEach(function(key) {
                                var element = $('[name="' + key + '"]');
                                if (element.length) {
                                    if (element.is('input[type="checkbox"]')) {
                                        element.prop('checked', filters[key] === 'on');
                                    } else {
                                        element.val(filters[key]);
                                    }
                                }
                            });

                            // Trigger search
                            table.ajax.reload();
                            toastr.success('Search preset loaded successfully!');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to load search preset');
                    }
                });
            }

            function loadSearchPresets() {
                $.ajax({
                    url: '{{ route('additional-documents.search-presets.index') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="">Select a preset...</option>';
                            response.data.forEach(function(preset) {
                                options += '<option value="' + preset.id + '">' + preset.name +
                                    '</option>';
                            });
                            $('#load-preset').html(options);
                        }
                    }
                });
            }

            function exportSearchResults() {
                var formData = $('#search-form').serialize();
                var exportUrl = '{{ route('additional-documents.export') }}?' + formData;

                // Show loading state
                toastr.info('Preparing export...');

                // Create temporary link for download
                var link = document.createElement('a');
                link.href = exportUrl;
                link.download = 'additional_documents_' + moment().format('YYYY-MM-DD_HH-mm-ss') + '.xlsx';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                toastr.success('Export completed!');
            }

            // Initialize search presets on page load
            loadSearchPresets();

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
