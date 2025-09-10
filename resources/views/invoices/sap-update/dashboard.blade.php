@extends('layouts.main')

@section('title_page')
    SAP Update Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">SAP Update</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Navigation Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-tachometer-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Dashboard</span>
                            <span class="info-box-number">Current View</span>
                        </div>
                        <a href="{{ route('invoices.sap-update.index') }}" class="info-box-footer bg-primary text-white">
                            <i class="fas fa-check-circle mr-1"></i> Active
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Without SAP Doc</span>
                            <span class="info-box-number" id="without-sap-count">0</span>
                        </div>
                        <a href="{{ route('invoices.sap-update.without-sap-page') }}" class="info-box-footer bg-light">
                            View <i class="fas fa-arrow-circle-right ml-1"></i>
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

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3 id="total-invoices">0</h3>
                            <p>Total Invoices</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="invoices-without-sap">0</h3>
                            <p>Without SAP Doc</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="invoices-with-sap">0</h3>
                            <p>With SAP Doc</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3 id="completion-percentage">0%</h3>
                            <p>Completion Rate</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Updates Over Time Chart -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">SAP Doc Updates Over Time (Last 30 Days)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="updatesChart" height="100"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Updates -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Updates</h3>
                        </div>
                        <div class="card-body">
                            <div id="recent-updates">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
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
    <!-- Chart.js -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/chart.js/Chart.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
@endsection

@section('scripts')
    <!-- Chart.js -->
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
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

            var updatesChart;

            // Load dashboard data
            loadDashboardData();

            function loadDashboardData() {
                $.ajax({
                    url: '{{ route('invoices.sap-update.dashboard-data') }}',
                    type: 'GET',
                    success: function(response) {
                        updateDashboardMetrics(response.metrics);
                        updateRecentUpdates(response.recent_updates);
                        createUpdatesChart(response.updates_over_time);
                    },
                    error: function(xhr) {
                        console.error('Failed to load dashboard data:', xhr);
                        toastr.error('Failed to load dashboard data. Please try refreshing the page.');
                    }
                });
            }

            function updateDashboardMetrics(metrics) {
                $('#total-invoices').text(metrics.total_invoices);
                $('#invoices-without-sap').text(metrics.invoices_without_sap);
                $('#without-sap-count').text(metrics.invoices_without_sap);
                $('#invoices-with-sap').text(metrics.invoices_with_sap);
                $('#with-sap-count').text(metrics.invoices_with_sap);
                $('#completion-percentage').text(metrics.completion_percentage + '%');
            }

            function updateRecentUpdates(updates) {
                let html = '';
                if (updates.length === 0) {
                    html = '<div class="text-muted">No recent updates</div>';
                } else {
                    updates.forEach(function(update) {
                        html += `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>${update.invoice_number}</strong><br>
                                <small class="text-muted">SAP: ${update.sap_doc}</small>
                            </div>
                            <small class="text-muted">${formatDate(update.updated_at)}</small>
                        </div>
                    `;
                    });
                }
                $('#recent-updates').html(html);
            }

            function createUpdatesChart(data) {
                const ctx = document.getElementById('updatesChart').getContext('2d');

                if (updatesChart) {
                    updatesChart.destroy();
                }

                updatesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(item => formatDate(item.date)),
                        datasets: [{
                            label: 'SAP Doc Updates',
                            data: data.map(item => item.count),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }

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
                            loadDashboardData();
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
        });
    </script>
@endsection
