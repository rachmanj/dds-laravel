@extends('layouts.main')

@section('title_page')
    All Additional Documents Report
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">All Additional Documents Report</li>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.css') }}">
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt"></i> All Additional Documents Report</h3>
                </div>
                <div class="card-body">
                    <div class="card card-outline card-info mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-search"></i> Additional Document Filters</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="adoc_search_number">Document Number</label>
                                        <input type="text" class="form-control adoc-filter" id="adoc_search_number">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="adoc_search_po_no">PO Number</label>
                                        <input type="text" class="form-control adoc-filter" id="adoc_search_po_no">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="adoc_search_vendor_code">Vendor Code</label>
                                        <input type="text" class="form-control adoc-filter" id="adoc_search_vendor_code">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="adoc_search_content">Content Search</label>
                                        <input type="text" class="form-control adoc-filter" id="adoc_search_content">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="adoc_filter_type">Document Type</label>
                                        <select class="form-control adoc-filter" id="adoc_filter_type">
                                            <option value="">All Types</option>
                                            @foreach ($documentTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="adoc_filter_status">Status</label>
                                        <select class="form-control adoc-filter" id="adoc_filter_status">
                                            <option value="">All Statuses</option>
                                            <option value="open">Open</option>
                                            <option value="closed">Closed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="adoc_filter_distribution_status">Distribution Status</label>
                                        <select class="form-control adoc-filter" id="adoc_filter_distribution_status">
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
                                        <label for="adoc_filter_vendor_code">Vendor Code</label>
                                        <select class="form-control adoc-filter" id="adoc_filter_vendor_code">
                                            <option value="">All Vendor Codes</option>
                                            @foreach ($vendorCodes as $vendorCode)
                                                <option value="{{ $vendorCode }}">{{ $vendorCode }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="adoc_filter_location">Location</label>
                                        <select class="form-control adoc-filter" id="adoc_filter_location">
                                            <option value="">All Locations</option>
                                            @foreach ($departments as $dept)
                                                <option value="{{ $dept->location_code }}">{{ $dept->location_code }} - {{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="adoc_age_filter">Age Filter</label>
                                        <select class="form-control adoc-filter" id="adoc_age_filter">
                                            <option value="">All Ages</option>
                                            <option value="0-7_days">0-7 Days</option>
                                            <option value="8-14_days">8-14 Days</option>
                                            <option value="15_plus_days">15+ Days</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="adoc_date_range">Date Range</label>
                                        <input type="text" class="form-control adoc-filter" id="adoc_date_range" placeholder="Select date range">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="adoc_date_type">Date Type</label>
                                        <select class="form-control adoc-filter" id="adoc_date_type">
                                            <option value="created_at">Created Date</option>
                                            <option value="document_date">Document Date</option>
                                            <option value="receive_date">Receive Date</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="adoc_search_preset">Search Preset</label>
                                        <select class="form-control adoc-filter" id="adoc_search_preset">
                                            <option value="">Select Preset</option>
                                            <option value="recent">Recent (Last 30 days)</option>
                                            <option value="open">Open Documents</option>
                                            <option value="this_month">This Month</option>
                                            <option value="last_month">Last Month</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-group">
                                        <button type="button" class="btn btn-info" id="adoc_apply_search">
                                            <i class="fas fa-search"></i> Apply
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="adoc_clear_search">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                        <button type="button" class="btn btn-success" id="adoc_export">
                                            <i class="fas fa-download"></i> Export
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="additional-documents-report-table" class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Doc No</th>
                                    <th>Doc Date</th>
                                    <th>Type</th>
                                    <th>PO No</th>
                                    <th>Vendor</th>
                                    <th>Inv No</th>
                                    <th>Rec Date</th>
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
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        $(function() {
            $('#adoc_date_range').daterangepicker({
                autoUpdateInput: false,
                locale: { format: 'DD/MM/YYYY', cancelLabel: 'Clear' }
            }).on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            }).on('cancel.daterangepicker', function() {
                $(this).val('');
            });

            function adocFilterParams() {
                return {
                    search_number: $('#adoc_search_number').val(),
                    search_po_no: $('#adoc_search_po_no').val(),
                    search_vendor_code: $('#adoc_search_vendor_code').val(),
                    search_content: $('#adoc_search_content').val(),
                    filter_type: $('#adoc_filter_type').val(),
                    filter_status: $('#adoc_filter_status').val(),
                    filter_distribution_status: $('#adoc_filter_distribution_status').val(),
                    filter_vendor_code: $('#adoc_filter_vendor_code').val(),
                    filter_location: $('#adoc_filter_location').val(),
                    date_range: $('#adoc_date_range').val(),
                    date_type: $('#adoc_date_type').val(),
                    search_preset: $('#adoc_search_preset').val(),
                    age_filter: $('#adoc_age_filter').val()
                };
            }

            const adocTable = $('#additional-documents-report-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('document-report.additional-documents-data') }}",
                    data: function(d) { Object.assign(d, adocFilterParams()); }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'document_number', name: 'document_number' },
                    { data: 'formatted_document_date', name: 'document_date' },
                    { data: 'type_name', name: 'type.type_name' },
                    { data: 'po_no', name: 'po_no' },
                    { data: 'vendor_code', name: 'vendor_code' },
                    { data: 'invoice_numbers', name: 'invoice_numbers', orderable: false, searchable: false },
                    { data: 'formatted_receive_date', name: 'receive_date' },
                    { data: 'distribution_status_badge', name: 'distribution_status', orderable: false },
                    { data: 'cur_loc', name: 'cur_loc' },
                    { data: 'days_difference', name: 'days_in_location', orderable: true },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[10, 'desc']],
                pageLength: 25,
                responsive: true
            });

            $('#adoc_apply_search').on('click', function() { adocTable.ajax.reload(); });
            $('#adoc_clear_search').on('click', function() {
                $('.adoc-filter').val('').trigger('change');
                adocTable.ajax.reload();
            });
            $('#adoc_export').on('click', function() {
                window.location.href = "{{ route('document-report.additional-documents-export') }}?" + new URLSearchParams(adocFilterParams()).toString();
            });
        });
    </script>
@endsection
