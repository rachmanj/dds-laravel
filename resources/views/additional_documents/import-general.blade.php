@extends('layouts.main')

@section('title_page')
    Import General Documents
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('additional-documents.index') }}">Additional Documents</a></li>
    <li class="breadcrumb-item active">General Import</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 mb-3">
                    @can('import-additional-documents')
                        <a href="{{ route('additional-documents.import') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-alt mr-2"></i>ITO Import
                        </a>
                    @endcan
                    @can('import-general-documents')
                        <a href="{{ route('additional-documents.import-general') }}" class="btn btn-primary">
                            <i class="fas fa-file-import mr-2"></i>General Documents Import
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- General Import Form Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-upload mr-2"></i>
                                Import General Documents from Excel
                            </h3>
                        </div>
                        <div class="card-body">
                            @if (session('general_error'))
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"
                                        aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                                    {{ session('general_error') }}
                                </div>
                            @endif

                            @if (session('general_import_summary'))
                                <!-- General Import Summary Card -->
                                <div
                                    class="alert alert-{{ session('general_import_summary')['success_count'] > 0 ? 'success' : 'warning' }} alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"
                                        aria-hidden="true">&times;</button>
                                    <h5>
                                        <i
                                            class="icon fas {{ session('general_import_summary')['success_count'] > 0 ? 'fa-check' : 'fa-exclamation-triangle' }}"></i>
                                        General Import Summary
                                    </h5>

                                    @php
                                        $summary = session('general_import_summary');
                                    @endphp

                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td><strong>File:</strong></td>
                                                    <td>{{ $summary['file_name'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Imported At:</strong></td>
                                                    <td>{{ $summary['imported_at'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Document Type:</strong></td>
                                                    <td>{{ $summary['document_type'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Processed:</strong></td>
                                                    <td><span
                                                            class="badge badge-info">{{ $summary['total_processed'] }}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td><strong>Successfully Imported:</strong></td>
                                                    <td><span
                                                            class="badge badge-success">{{ $summary['success_count'] }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Skipped (Duplicates):</strong></td>
                                                    <td><span
                                                            class="badge badge-warning">{{ $summary['skipped_count'] }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Errors:</strong></td>
                                                    <td><span
                                                            class="badge badge-danger">{{ $summary['error_count'] }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Duplicate Check:</strong></td>
                                                    <td>{{ $summary['check_duplicates'] ? 'Enabled (' . $summary['duplicate_action'] . ')' : 'Disabled' }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

                                    @if (isset($summary['document_type_counts']))
                                        <div class="mt-3">
                                            <h6><i class="fas fa-chart-pie text-info"></i> Documents Created by Type:</h6>
                                            <div class="row">
                                                @foreach ($summary['document_type_counts'] as $type => $count)
                                                    <div class="col-md-4">
                                                        <span class="badge badge-primary">{{ $type }}:
                                                            {{ $count }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($summary['skipped_count'] > 0)
                                        <div class="mt-3">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                <strong>Duplicate Detection:</strong> {{ $summary['skipped_count'] }}
                                                document(s) were skipped because they already exist in the system.
                                                The system prevents importing duplicate document numbers to maintain data
                                                integrity.
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($summary['errors']))
                                        <div class="mt-3">
                                            <h6><i class="fas fa-exclamation-triangle text-warning"></i> Import Errors:</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Error</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach (array_slice($summary['errors'], 0, 10) as $error)
                                                            <tr>
                                                                <td class="text-danger">{{ $error }}</td>
                                                            </tr>
                                                        @endforeach
                                                        @if (count($summary['errors']) > 10)
                                                            <tr>
                                                                <td class="text-muted">
                                                                    ... and {{ count($summary['errors']) - 10 }} more
                                                                    errors
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <form action="{{ route('additional-documents.process-general-import') }}" method="POST"
                                enctype="multipart/form-data" id="generalImportForm">
                                @csrf

                                <!-- File Upload -->
                                <div class="form-group">
                                    <label for="general_file">Excel File <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="general_file" name="file"
                                                accept=".xlsx,.xls" required>
                                            <label class="custom-file-label" for="general_file">Choose file</label>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        Supported formats: .xlsx, .xls (Max size: 50MB)
                                    </small>
                                    @error('file')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Duplicate Handling Info -->
                                <div class="form-group">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <strong>Multi-Document Creation:</strong> Each row can create up to 3 documents (DO,
                                        GR, MR) based on which fields are populated.
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary" id="generalSubmitBtn">
                                        <i class="fas fa-upload mr-2"></i>
                                        Start General Import
                                    </button>
                                    <a href="{{ route('additional-documents.index') }}" class="btn btn-secondary ml-2">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Back to List
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- General Template Download Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-download mr-2"></i>
                                Download General Template
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Download the General Documents Excel template for DO/GR/MR imports.
                            </p>
                            <a href="{{ route('additional-documents.download-general-template') }}"
                                class="btn btn-success btn-block">
                                <i class="fas fa-file-excel mr-2"></i>
                                Download General Template
                            </a>
                        </div>
                    </div>

                    <!-- General Import Instructions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>
                                General Import Instructions
                            </h3>
                        </div>
                        <div class="card-body">
                            <h6>Required Fields:</h6>
                            <ul class="list-unstyled">
                                <li><strong>description</strong> - Document description/name</li>
                                <li><strong>At least one:</strong> do_no, gr_no, or mr_no</li>
                            </ul>

                            <h6>Document Types Created:</h6>
                            <ul class="list-unstyled">
                                <li><strong>DO</strong> - Delivery Order (from do_no/do_date)</li>
                                <li><strong>GR</strong> - Goods Receipt (from gr_no/gr_date)</li>
                                <li><strong>MR</strong> - Material Requisition (from mr_no/mr_date)</li>
                            </ul>

                            <h6>Field Mapping:</h6>
                            <ul class="list-unstyled">
                                <li><strong>description</strong> → remarks field</li>
                                <li><strong>do_no/gr_no/mr_no</strong> → document_number</li>
                                <li><strong>do_date/gr_date/mr_date</strong> → document_date</li>
                            </ul>

                            <h6>Date Formats Supported:</h6>
                            <ul class="list-unstyled">
                                <li>DD-Mon-YY (e.g., 10-Sep-25) - <strong>Primary format</strong></li>
                                <li>DD.MM.YYYY (e.g., 10.09.2025)</li>
                                <li>DD-MM-YYYY (e.g., 10-09-2025)</li>
                                <li>DD/MM/YYYY (e.g., 10/09/2025)</li>
                                <li>Excel serial numbers (auto-converted)</li>
                            </ul>

                            <h6>Important Notes:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-info-circle text-info"></i> Each row can create 1-3 documents</li>
                                <li><i class="fas fa-info-circle text-info"></i> Duplicate document numbers will be skipped
                                </li>
                                <li><i class="fas fa-info-circle text-info"></i> All documents assigned to your location
                                </li>
                                <li><i class="fas fa-info-circle text-info"></i> Document types auto-detected from field
                                    content</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize custom file input
            bsCustomFileInput.init();

            // Handle file input label updates
            $('#general_file').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName || 'Choose file');
            });

            // Form validation
            $('#generalImportForm').submit(function(e) {
                var file = $('#general_file')[0].files[0];
                if (!file) {
                    e.preventDefault();
                    alert('Please select a file to import.');
                    return false;
                }

                // Show loading state
                $('#generalSubmitBtn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-2"></i>Importing...');
            });

            // File size validation
            $('#general_file').change(function() {
                var file = this.files[0];
                var maxSize = 50 * 1024 * 1024; // 50MB

                if (file && file.size > maxSize) {
                    alert('File size must be less than 50MB.');
                    $(this).val('');
                    $(this).siblings('.custom-file-label').text('Choose file');
                }
            });

            // Show Toastr notification for success
            @if (session('general_import_success'))
                toastr.success("{{ session('general_import_success') }}", "General Import Completed", {
                    timeOut: 5000,
                    extendedTimeOut: 2000,
                    closeButton: true,
                    progressBar: true
                });
            @endif

            // Show Toastr notification for error
            @if (session('general_error'))
                toastr.error("{{ session('general_error') }}", "General Import Failed", {
                    timeOut: 5000,
                    extendedTimeOut: 2000,
                    closeButton: true,
                    progressBar: true
                });
            @endif
        });
    </script>
@endpush
