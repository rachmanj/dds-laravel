@extends('layouts.main')

@section('title_page')
    Invoices Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Invoices</li>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
    <!-- Bootstrap Switch -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/bootstrap-switch/css/bootstrap4-switch.min.css') }}">
    <!-- Date Range Picker -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.css') }}">

    <style>
        /* Start search card in collapsed state */
        .search-card .card-body {
            display: none;
        }

        .search-card.collapsed .card-body {
            display: block;
        }
    </style>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Invoices List</h3>
                            <div class="card-tools">
                                <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Add New Invoice
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if (session('import_errors') && count(session('import_errors')) > 0)
                                <div class="alert alert-warning alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"
                                        aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Import Warnings!</h5>
                                    <p>The import completed but some records had issues:</p>
                                    <ul>
                                        @foreach (session('import_errors') as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Advanced Search Panel -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="card card-outline card-info search-card">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-search"></i> Advanced Search
                                            </h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_invoice_number">Invoice Number</label>
                                                        <input type="text" class="form-control"
                                                            id="search_invoice_number"
                                                            placeholder="Search by invoice number">
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_po_no">PO Number</label>
                                                        <input type="text" class="form-control" id="search_po_no"
                                                            placeholder="Search by PO number">
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_type">Invoice Type</label>
                                                        <select class="form-control" id="search_type">
                                                            <option value="">All Types</option>
                                                            @foreach ($invoiceTypes as $type)
                                                                <option value="{{ $type->type_name }}">
                                                                    {{ $type->type_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_status">Status</label>
                                                        <select class="form-control" id="search_status">
                                                            <option value="">All Status</option>
                                                            <option value="open">Open</option>
                                                            <option value="verify">Verify</option>
                                                            <option value="return">Return</option>
                                                            <option value="sap">SAP</option>
                                                            <option value="close">Close</option>
                                                            <option value="cancel">Cancel</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_date_range">Date Range</label>
                                                        <input type="text" class="form-control" id="search_date_range"
                                                            placeholder="Select date range">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="show_all_records">
                                                            <input type="checkbox" id="show_all_records"
                                                                data-bootstrap-switch>
                                                            Show All Records (Admin Only)
                                                        </label>
                                                        <small class="form-text text-muted">
                                                            Toggle to view all invoices across all locations
                                                            (Admin/Superadmin only)
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 text-right">
                                                    <button type="button" class="btn btn-info" id="apply_search">
                                                        <i class="fas fa-search"></i> Apply Search
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="clear_search">
                                                        <i class="fas fa-times"></i> Clear Search
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <table id="invoices-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Supplier</th>
                                        <th>Type</th>
                                        <th>Invoice Date</th>
                                        <th>Receive Date</th>
                                        <th>PO Number</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Attachments</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>


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
    <!-- Date Range Picker -->
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Toastr
            if (typeof toastr !== 'undefined') {
                toastr.options = {
                    "closeButton": true,
                    "debug": false,
                    "newestOnTop": false,
                    "progressBar": true,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "5000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                };

                // Show session messages if exists
                @if (session('success'))
                    toastr.success('{{ session('success') }}');
                @endif

                @if (session('error'))
                    toastr.error('{{ session('error') }}');
                @endif

                @if (session('warning'))
                    toastr.warning('{{ session('warning') }}');
                @endif

                @if (session('info'))
                    toastr.info('{{ session('info') }}');
                @endif
            } else {
                console.error('Toastr not loaded');
            }

            // Initialize Bootstrap Switch
            $("input[data-bootstrap-switch]").each(function() {
                $(this).bootstrapSwitch();
            });

            // Initialize Date Range Picker
            $('#search_date_range').daterangepicker({
                locale: {
                    format: 'DD/MM/YYYY'
                },
                autoUpdateInput: false
            });

            // Handle date range picker events
            $('#search_date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format(
                    'DD/MM/YYYY'));
            });

            $('#search_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            // Initialize DataTable
            var table = $('#invoices-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('invoices.data') }}',
                    data: function(d) {
                        d.show_all = $('#show_all_records').is(':checked') ? 1 : 0;
                    }
                },
                columns: [{
                        data: 'invoice_number',
                        name: 'invoice_number'
                    },
                    {
                        data: 'supplier_name',
                        name: 'supplier_name'
                    },
                    {
                        data: 'type_name',
                        name: 'type_name'
                    },
                    {
                        data: 'formatted_invoice_date',
                        name: 'invoice_date'
                    },
                    {
                        data: 'formatted_receive_date',
                        name: 'receive_date'
                    },
                    {
                        data: 'po_no',
                        name: 'po_no'
                    },
                    {
                        data: 'formatted_amount',
                        name: 'amount'
                    },
                    {
                        data: 'status_badge',
                        name: 'status'
                    },
                    {
                        data: 'attachment_count',
                        name: 'attachments'
                    },
                    {
                        data: 'creator_name',
                        name: 'creator_name'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [4, 'desc']
                ],
                pageLength: 25,
                responsive: true
            });

            // Apply search
            $('#apply_search').click(function() {
                table.draw();
            });

            // Clear search
            $('#clear_search').click(function() {
                $('#search_invoice_number').val('');
                $('#search_po_no').val('');
                $('#search_type').val('');
                $('#search_status').val('');
                $('#search_date_range').val('');
                $('#show_all_records').bootstrapSwitch('state', false);
                table.search('').columns().search('').draw();
            });

            // Custom search functionality
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var invoiceNumber = $('#search_invoice_number').val().toLowerCase();
                var fakturNo = $('#search_faktur_no').val().toLowerCase();
                var poNo = $('#search_po_no').val().toLowerCase();
                var type = $('#search_type').val();
                var status = $('#search_status').val();
                var dateRange = $('#search_date_range').val();

                // Invoice number filter
                if (invoiceNumber && data[0].toLowerCase().indexOf(invoiceNumber) === -1) {
                    return false;
                }

                // Faktur number filter
                if (fakturNo && data[1].toLowerCase().indexOf(fakturNo) === -1) {
                    return false;
                }

                // PO Number filter
                if (poNo && data[5].toLowerCase().indexOf(poNo) === -1) {
                    return false;
                }

                // Type filter
                if (type && data[3] !== type) {
                    return false;
                }

                // Status filter
                if (status && data[8].indexOf(status) === -1) {
                    return false;
                }

                // Date range filter
                if (dateRange) {
                    var dates = dateRange.split(' - ');
                    var startDate = moment(dates[0], 'DD/MM/YYYY');
                    var endDate = moment(dates[1], 'DD/MM/YYYY');
                    var invoiceDate = moment(data[4], 'DD/MM/YYYY');

                    if (!invoiceDate.isBetween(startDate, endDate, 'day', '[]')) {
                        return false;
                    }
                }

                return true;
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

            // Edit now handled by direct link in actions (no modal)

            // Delete invoice (delegated for DataTables-rendered rows)
            $('#invoices-table').on('click', '.delete-invoice', function() {
                var invoiceId = $(this).data('id');
                var invoiceNumber = $(this).data('number');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to delete invoice "${invoiceNumber}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var deleteUrl = $(this).data('delete-url') || ('/invoices/' + invoiceId);
                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    table.ajax.reload(null, false);
                                    // Use toastr for consistency with other operations
                                    toastr.success('Invoice deleted successfully.');
                                } else {
                                    toastr.error(response.message ||
                                        'Failed to delete invoice.');
                                }
                            },
                            error: function() {
                                toastr.error(
                                    'An error occurred while deleting the invoice.');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
