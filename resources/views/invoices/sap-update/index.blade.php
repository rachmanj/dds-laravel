@extends('layouts.main')

@section('title_page')
    SAP Update
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">SAP Update</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Tabs -->
                    <div class="card">
                        <div class="card-header p-0">
                            <ul class="nav nav-tabs" id="sap-update-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="dashboard-tab" data-toggle="tab" href="#dashboard"
                                        role="tab" aria-controls="dashboard" aria-selected="true">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="without-sap-tab" data-toggle="tab" href="#without-sap"
                                        role="tab" aria-controls="without-sap" aria-selected="false">
                                        <i class="fas fa-exclamation-triangle"></i> Without SAP Doc
                                        <span class="badge badge-warning ml-1" id="without-sap-count">0</span>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="with-sap-tab" data-toggle="tab" href="#with-sap" role="tab"
                                        aria-controls="with-sap" aria-selected="false">
                                        <i class="fas fa-check-circle"></i> With SAP Doc
                                        <span class="badge badge-success ml-1" id="with-sap-count">0</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="sap-update-tabContent">
                                <!-- Dashboard Tab -->
                                <div class="tab-pane fade show active" id="dashboard" role="tabpanel"
                                    aria-labelledby="dashboard-tab">
                                    @include('invoices.sap-update.tabs.dashboard')
                                </div>

                                <!-- Without SAP Doc Tab -->
                                <div class="tab-pane fade" id="without-sap" role="tabpanel" aria-labelledby="without-sap-tab">
                                    @include('invoices.sap-update.tabs.without-sap')
                                </div>

                                <!-- With SAP Doc Tab -->
                                <div class="tab-pane fade" id="with-sap" role="tabpanel" aria-labelledby="with-sap-tab">
                                    @include('invoices.sap-update.tabs.with-sap')
                                </div>
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
    <!-- Chart.js -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/chart.js/Chart.min.css') }}">

    <style>
        /* DataTables custom styling */
        div.dataTables_wrapper div.dataTables_length select {
            width: 75px;
            display: inline-block;
        }

        div.dataTables_wrapper div.dataTables_filter {
            text-align: right;
        }

        div.dataTables_wrapper div.dataTables_filter input {
            margin-left: 0.5em;
            display: inline-block;
            width: auto;
        }

        div.dataTables_wrapper div.dataTables_info {
            padding-top: 0.85em;
        }

        div.dataTables_wrapper div.dataTables_paginate {
            margin: 0;
            white-space: nowrap;
            text-align: right;
        }

        div.dataTables_wrapper div.dataTables_paginate ul.pagination {
            margin: 2px 0;
            white-space: nowrap;
            justify-content: flex-end;
        }

        /* Table styling */
        .table-bordered th,
        .table-bordered td {
            vertical-align: middle !important;
        }

        .table thead th {
            vertical-align: middle;
            border-bottom: 2px solid #dee2e6;
        }

        /* Tab styling */
        .nav-tabs .nav-link {
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            color: #007bff;
            font-weight: bold;
        }
    </style>
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <!-- Chart.js -->
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    
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

            // Tab change event - reinitialize DataTables when tab is shown
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
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
                            
                            // Reload tables and dashboard
                            if (typeof refreshDashboard === 'function') {
                                refreshDashboard();
                            }
                            
                            if (typeof withoutSapTable !== 'undefined') {
                                withoutSapTable.ajax.reload();
                            }
                            
                            if (typeof withSapTable !== 'undefined') {
                                withSapTable.ajax.reload();
                            }
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        if (response && response.errors && response.errors.sap_doc) {
                            $('#sap-doc-error').text(response.errors.sap_doc[0]).addClass('d-block');
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
                        $('#sap-doc-error').text('Error validating SAP document number.').addClass('d-block');
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