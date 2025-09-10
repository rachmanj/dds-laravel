@extends('layouts.main')

@section('title_page')
    Invoices Without SAP Document
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.sap-update.index') }}">SAP Update</a></li>
    <li class="breadcrumb-item active">Without SAP Doc</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Navigation Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-tachometer-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Dashboard</span>
                            <span class="info-box-number">Overview</span>
                        </div>
                        <a href="{{ route('invoices.sap-update.index') }}" class="info-box-footer bg-light">
                            View <i class="fas fa-arrow-circle-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Without SAP Doc</span>
                            <span class="info-box-number">Current View</span>
                        </div>
                        <a href="{{ route('invoices.sap-update.without-sap-page') }}"
                            class="info-box-footer bg-warning text-white">
                            <i class="fas fa-check-circle mr-1"></i> Active
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">With SAP Doc</span>
                            <span class="info-box-number" id="with-sap-count">0</span>
                        </div>
                        <a href="{{ route('invoices.sap-update.with-sap-page') }}" class="info-box-footer bg-light">
                            View <i class="fas fa-arrow-circle-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Invoices Without SAP Document Number</h3>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filter-invoice-number">Invoice Number</label>
                                        <input type="text" class="form-control" id="filter-invoice-number"
                                            placeholder="Invoice Number">
                                    </div>
                                </div>
                                {{-- <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filter-faktur-no">Faktur No</label>
                                        <input type="text" class="form-control" id="filter-faktur-no"
                                            placeholder="Faktur No">
                                    </div>
                                </div> --}}
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filter-po-no">PO Number</label>
                                        <input type="text" class="form-control" id="filter-po-no"
                                            placeholder="PO Number">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filter-type">Type</label>
                                        <select class="form-control" id="filter-type">
                                            <option value="">All Types</option>
                                            @foreach ($invoiceTypes as $type)
                                                <option value="{{ $type->type_name }}">{{ $type->type_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                {{-- <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filter-status">Status</label>
                                        <select class="form-control" id="filter-status">
                                            <option value="">All Status</option>
                                            <option value="open">Open</option>
                                            <option value="verify">Verify</option>
                                            <option value="return">Return</option>
                                            <option value="sap">SAP</option>
                                            <option value="close">Close</option>
                                            <option value="cancel">Cancel</option>
                                        </select>
                                    </div>
                                </div> --}}
                                {{-- <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filter-supplier">Supplier</label>
                                        <input type="text" class="form-control" id="filter-supplier"
                                            placeholder="Supplier">
                                    </div>
                                </div> --}}
                            </div>
                            <div class="row mb-3">
                                {{-- <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter-invoice-project">Invoice Project</label>
                                        <input type="text" class="form-control" id="filter-invoice-project"
                                            placeholder="Invoice Project">
                                    </div>
                                </div> --}}
                                <div class="col-md-3">
                                    <div class="form-group mt-4">
                                        <button type="button" class="btn btn-primary" id="apply-filters">
                                            <i class="fas fa-search"></i> Apply Filters
                                        </button>
                                        <button type="button" class="btn btn-secondary ml-2" id="clear-filters">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    @can('see-all-record-switch')
                                        <div class="form-group mt-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="show-all">
                                                <label class="form-check-label" for="show-all">
                                                    Show All Records
                                                </label>
                                            </div>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                            <!-- DataTable -->
                            <div class="table-responsive">
                                <table id="without-sap-table" class="table table-bordered table-striped w-100">
                                    <thead>
                                        <tr>
                                            <th>Invoice Number</th>
                                            <th>Faktur No</th>
                                            <th>PO Number</th>
                                            <th>Supplier</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                            <th>Created By</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Update SAP Doc Modal -->
    <div class="modal fade" id="updateSapModal" tabindex="-1" role="dialog" aria-labelledby="updateSapModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateSapModalLabel">Update SAP Document Number</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="updateSapForm">
                        <input type="hidden" id="invoice-id" name="invoice_id">
                        <div class="form-group">
                            <label for="invoice-number-display">Invoice Number:</label>
                            <input type="text" class="form-control" id="invoice-number-display" readonly>
                        </div>
                        <div class="form-group">
                            <label for="sap-doc-input">SAP Document Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sap-doc-input" name="sap_doc" maxlength="20"
                                required>
                            <div class="invalid-feedback" id="sap-doc-error"></div>
                            <small class="form-text text-muted">SAP document number must be unique.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-sap-doc">
                        <i class="fas fa-save"></i> Update SAP Doc
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Toastr
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

            // Load dashboard metrics for the navigation card
            $.ajax({
                url: '{{ route('invoices.sap-update.dashboard-data') }}',
                type: 'GET',
                success: function(response) {
                    $('#with-sap-count').text(response.metrics.invoices_with_sap);
                },
                error: function(xhr) {
                    console.error('Failed to load metrics:', xhr);
                }
            });

            // Initialize DataTable
            var table = $('#without-sap-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('invoices.sap-update.without-sap') }}',
                    data: function(d) {
                        d.search_invoice_number = $('#filter-invoice-number').val();
                        // d.search_faktur_no = $('#filter-faktur-no').val();
                        d.search_po_no = $('#filter-po-no').val();
                        d.search_type = $('#filter-type').val();
                        // d.search_status = $('#filter-status').val();
                        // d.search_supplier = $('#filter-supplier').val();
                        // d.search_invoice_project = $('#filter-invoice-project').val();
                        d.show_all = $('#show-all').is(':checked');
                    }
                },
                columns: [{
                        data: 'invoice_number',
                        name: 'invoice_number'
                    },
                    {
                        data: 'faktur_no',
                        name: 'faktur_no'
                    },
                    {
                        data: 'po_no',
                        name: 'po_no'
                    },
                    {
                        data: 'supplier_name',
                        name: 'supplier.name'
                    },
                    {
                        data: 'type_name',
                        name: 'type.type_name'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        render: function(data) {
                            return 'IDR ' + parseFloat(data).toLocaleString();
                        }
                    },
                    {
                        data: 'creator_name',
                        name: 'creator.name'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 25,
                responsive: true,
                language: {
                    processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
                    search: 'Search:',
                    lengthMenu: '_MENU_ records per page',
                    info: 'Showing _START_ to _END_ of _TOTAL_ records',
                    infoEmpty: 'No records available',
                    infoFiltered: '(filtered from _MAX_ total records)',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: '<i class="fas fa-chevron-right"></i>',
                        previous: '<i class="fas fa-chevron-left"></i>'
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                autoWidth: false
            });

            // Filter buttons
            $('#apply-filters').click(function() {
                table.ajax.reload();
            });

            $('#clear-filters').click(function() {
                $('#filter-invoice-number, #filter-po-no, #filter-type')
                    .val('');
                // $('#filter-faktur-no, #filter-status, #filter-supplier, #filter-invoice-project').val('');
                $('#show-all').prop('checked', false);
                table.ajax.reload();
            });

            // Show all records checkbox
            $('#show-all').change(function() {
                table.ajax.reload();
            });

            // SAP Doc validation on input
            $('#sap-doc-input').on('input', function() {
                const sapDoc = $(this).val();
                const invoiceId = $('#invoice-id').val();

                if (sapDoc.length > 0) {
                    validateSapDoc(sapDoc, invoiceId);
                } else {
                    $('#sap-doc-error').text('').removeClass('d-block');
                    $('#sap-doc-input').removeClass('is-invalid');
                }
            });

            // Save SAP Doc
            $('#save-sap-doc').click(function() {
                const invoiceId = $('#invoice-id').val();
                const sapDoc = $('#sap-doc-input').val();

                if (!sapDoc) {
                    $('#sap-doc-error').text('SAP document number is required.').addClass('d-block');
                    $('#sap-doc-input').addClass('is-invalid');
                    return;
                }

                $.ajax({
                    url: `/invoices/sap-update/${invoiceId}/update-sap-doc`,
                    type: 'PUT',
                    data: {
                        sap_doc: sapDoc,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#updateSapModal').modal('hide');
                            toastr.success(response.message);
                            table.ajax.reload();

                            // Update metrics
                            $.ajax({
                                url: '{{ route('invoices.sap-update.dashboard-data') }}',
                                type: 'GET',
                                success: function(response) {
                                    $('#with-sap-count').text(response.metrics
                                        .invoices_with_sap);
                                }
                            });
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        if (response && response.errors && response.errors.sap_doc) {
                            $('#sap-doc-error').text(response.errors.sap_doc[0]).addClass(
                                'd-block');
                            $('#sap-doc-input').addClass('is-invalid');
                        } else {
                            toastr.error('Failed to update SAP document number.');
                        }
                    }
                });
            });

            function validateSapDoc(sapDoc, invoiceId) {
                $.ajax({
                    url: '{{ route('invoices.sap-update.validate-sap-doc') }}',
                    type: 'POST',
                    data: {
                        sap_doc: sapDoc,
                        invoice_id: invoiceId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.valid) {
                            $('#sap-doc-error').text('').removeClass('d-block');
                            $('#sap-doc-input').removeClass('is-invalid');
                        } else {
                            $('#sap-doc-error').text(response.message).addClass('d-block');
                            $('#sap-doc-input').addClass('is-invalid');
                        }
                    },
                    error: function() {
                        $('#sap-doc-error').text('Error validating SAP document number.').addClass(
                            'd-block');
                        $('#sap-doc-input').addClass('is-invalid');
                    }
                });
            }

            // Update SAP Doc button clicks (delegated event)
            $(document).on('click', '.update-sap-btn', function() {
                const invoiceId = $(this).data('invoice-id');
                const invoiceNumber = $(this).data('invoice-number');
                const currentSap = $(this).data('current-sap') || '';

                $('#invoice-id').val(invoiceId);
                $('#invoice-number-display').val(invoiceNumber);
                $('#sap-doc-input').val(currentSap);
                $('#sap-doc-error').text('').removeClass('d-block');
                $('#sap-doc-input').removeClass('is-invalid');

                $('#updateSapModal').modal('show');
            });
        });
    </script>
@endsection
