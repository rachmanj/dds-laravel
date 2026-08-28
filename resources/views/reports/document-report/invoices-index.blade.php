@extends('layouts.main')

@section('title_page')
    All Invoice Report
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">All Invoice Report</li>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.css') }}">
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice"></i> All Invoice Report</h3>
                </div>
                <div class="card-body">
                    <div class="card card-outline card-info mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-search"></i> Invoice Filters</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_search_invoice_number">Invoice Number</label>
                                        <input type="text" class="form-control inv-filter" id="inv_search_invoice_number">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_search_supplier">Supplier</label>
                                        <select class="form-control select2bs4 inv-filter" id="inv_search_supplier">
                                            <option value="">All Suppliers</option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier->name }}">{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_search_po_no">PO Number</label>
                                        <input type="text" class="form-control inv-filter" id="inv_search_po_no">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_search_type">Invoice Type</label>
                                        <select class="form-control inv-filter" id="inv_search_type">
                                            <option value="">All Types</option>
                                            @foreach ($invoiceTypes as $type)
                                                <option value="{{ $type->type_name }}">{{ $type->type_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_search_status">Status</label>
                                        <select class="form-control inv-filter" id="inv_search_status">
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
                                        <label for="inv_search_invoice_project">Invoice Project</label>
                                        <select class="form-control inv-filter" id="inv_search_invoice_project">
                                            <option value="">All Projects</option>
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->code }}">{{ $project->code }} - {{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_filter_distribution_status">Distribution Status</label>
                                        <select class="form-control inv-filter" id="inv_filter_distribution_status">
                                            <option value="">All</option>
                                            <option value="available">Available</option>
                                            <option value="in_transit">In Transit</option>
                                            <option value="distributed">Distributed</option>
                                            <option value="unaccounted_for">Unaccounted</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_filter_location">Location</label>
                                        <select class="form-control inv-filter" id="inv_filter_location">
                                            <option value="">All Locations</option>
                                            @foreach ($departments as $dept)
                                                <option value="{{ $dept->location_code }}">{{ $dept->location_code }} - {{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_date_range">Date Range</label>
                                        <input type="text" class="form-control inv-filter" id="inv_date_range" placeholder="Select date range">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_date_type">Date Type</label>
                                        <select class="form-control inv-filter" id="inv_date_type">
                                            <option value="created_at">Created Date</option>
                                            <option value="invoice_date">Invoice Date</option>
                                            <option value="receive_date">Receive Date</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="inv_age_filter">Age Filter</label>
                                        <select class="form-control inv-filter" id="inv_age_filter">
                                            <option value="">All Ages</option>
                                            <option value="0-7_days">0-7 Days</option>
                                            <option value="8-14_days">8-14 Days</option>
                                            <option value="15_plus_days">15+ Days</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-group">
                                        <button type="button" class="btn btn-info" id="inv_apply_search">
                                            <i class="fas fa-search"></i> Apply
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="inv_clear_search">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                        <button type="button" class="btn btn-success" id="inv_export">
                                            <i class="fas fa-download"></i> Export
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="invoices-report-table" class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Supplier</th>
                                    <th>Type</th>
                                    <th>Invoice Date</th>
                                    <th>Receive Date</th>
                                    <th>PO No.</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>SAP Status</th>
                                    <th>Dist. Status</th>
                                    <th>Location</th>
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
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        $(function() {
            $('.select2bs4').select2({ theme: 'bootstrap4', width: '100%' });

            $('#inv_date_range').daterangepicker({
                autoUpdateInput: false,
                locale: { format: 'DD/MM/YYYY', cancelLabel: 'Clear' }
            }).on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            }).on('cancel.daterangepicker', function() {
                $(this).val('');
            });

            function invoiceFilterParams() {
                return {
                    search_invoice_number: $('#inv_search_invoice_number').val(),
                    search_supplier: $('#inv_search_supplier').val(),
                    search_po_no: $('#inv_search_po_no').val(),
                    search_type: $('#inv_search_type').val(),
                    search_status: $('#inv_search_status').val(),
                    search_invoice_project: $('#inv_search_invoice_project').val(),
                    filter_distribution_status: $('#inv_filter_distribution_status').val(),
                    filter_location: $('#inv_filter_location').val(),
                    date_range: $('#inv_date_range').val(),
                    date_type: $('#inv_date_type').val(),
                    age_filter: $('#inv_age_filter').val()
                };
            }

            const invoicesTable = $('#invoices-report-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('document-report.invoices-data') }}",
                    data: function(d) { Object.assign(d, invoiceFilterParams()); }
                },
                columns: [
                    { data: 'invoice_number', name: 'invoice_number' },
                    { data: 'supplier_name', name: 'supplier.name' },
                    { data: 'type_name', name: 'type.type_name' },
                    { data: 'formatted_invoice_date', name: 'invoice_date' },
                    { data: 'formatted_receive_date', name: 'receive_date' },
                    { data: 'po_no', name: 'po_no' },
                    { data: 'formatted_amount', name: 'amount' },
                    { data: 'status_badge', name: 'status', orderable: false },
                    { data: 'sap_status_badge', name: 'sap_status', orderable: false },
                    { data: 'distribution_status_badge', name: 'distribution_status', orderable: false },
                    { data: 'cur_loc', name: 'cur_loc' },
                    { data: 'days_difference', name: 'days_in_location', orderable: true },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[11, 'desc']],
                pageLength: 25,
                responsive: true
            });

            $('#inv_apply_search').on('click', function() { invoicesTable.ajax.reload(); });
            $('#inv_clear_search').on('click', function() {
                $('.inv-filter').val('').trigger('change');
                $('#inv_search_supplier').val('').trigger('change');
                invoicesTable.ajax.reload();
            });
            $('#inv_export').on('click', function() {
                window.location.href = "{{ route('document-report.invoices-export') }}?" + new URLSearchParams(invoiceFilterParams()).toString();
            });
        });
    </script>
@endsection
