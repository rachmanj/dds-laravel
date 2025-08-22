@extends('layouts.main')

@section('title', 'Import Additional Documents')

@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Import Additional Documents</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('additional-documents.index') }}">Additional
                                    Documents</a></li>
                            <li class="breadcrumb-item active">Import</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Import Form Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-upload mr-2"></i>
                                    Import Documents from Excel
                                </h3>
                            </div>
                            <div class="card-body">
                                @if (session('error'))
                                    <div class="alert alert-danger alert-dismissible">
                                        <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">&times;</button>
                                        <h5><i class="icon fas fa-ban"></i> Error!</h5>
                                        {{ session('error') }}
                                    </div>
                                @endif

                                @if (session('import_summary'))
                                    <!-- Import Summary Card -->
                                    <div class="alert alert-success alert-dismissible">
                                        <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">&times;</button>
                                        <h5><i class="icon fas fa-check"></i> Import Summary</h5>

                                        @php
                                            $summary = session('import_summary');
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
                                                        <td><strong>Skipped:</strong></td>
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

                                        @if (!empty($summary['errors']))
                                            <div class="mt-3">
                                                <h6><i class="fas fa-exclamation-triangle text-warning"></i> Import Errors:
                                                </h6>
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

                                <form action="{{ route('additional-documents.process-import') }}" method="POST"
                                    enctype="multipart/form-data" id="importForm">
                                    @csrf

                                    <!-- File Upload -->
                                    <div class="form-group">
                                        <label for="file">Excel File <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="file"
                                                    name="file" accept=".xlsx,.xls" required>
                                                <label class="custom-file-label" for="file">Choose file</label>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Supported formats: .xlsx, .xls (Max size: 10MB)
                                        </small>
                                        @error('file')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Document Type Selection -->
                                    <div class="form-group">
                                        <label for="document_type_id">Document Type (Optional)</label>
                                        <select class="form-control select2" id="document_type_id" name="document_type_id">
                                            <option value="">Auto-detect from Excel</option>
                                            @foreach ($documentTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">
                                            If not selected, the system will try to auto-detect document types from the
                                            Excel file
                                        </small>
                                    </div>

                                    <!-- Duplicate Handling Info -->
                                    <div class="form-group">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong>Duplicate Handling:</strong> Records with duplicate document numbers
                                            will be automatically skipped.
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="fas fa-upload mr-2"></i>
                                            Start Import
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
                        <!-- Template Download Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-download mr-2"></i>
                                    Download Template
                                </h3>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    Download our Excel template to ensure your data is formatted correctly for import.
                                </p>
                                <a href="{{ route('additional-documents.download-template') }}"
                                    class="btn btn-success btn-block">
                                    <i class="fas fa-file-excel mr-2"></i>
                                    Download Template
                                </a>
                            </div>
                        </div>

                        <!-- Import Instructions Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Import Instructions
                                </h3>
                            </div>
                            <div class="card-body">
                                <h6>Required Fields:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>ito_no</strong> - ITO number (unique document identifier)</li>
                                    <li><strong>ito_date</strong> - ITO date (DD.MM.YYYY format)</li>
                                </ul>

                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Column Headers:</strong> The system will automatically recognize various column
                                    header formats (with or without spaces/underscores).
                                </div>

                                <h6>Optional Fields:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>po_no</strong> - Purchase order number</li>
                                    <li><strong>grpo_no</strong> - GRPO number</li>
                                    <li><strong>origin_wh</strong> - Origin warehouse code</li>
                                    <li><strong>destinatic</strong> - Destination warehouse code</li>
                                    <li><strong>ito_remar</strong> - ITO remarks/notes</li>
                                    <li><strong>ito_create</strong> - ITO creation date</li>
                                    <li><strong>User Nam</strong> - User name</li>
                                    <li><strong>Printed</strong> - Print status</li>
                                    <li><strong>iti_no</strong> - ITI number</li>
                                    <li><strong>iti_date</strong> - ITI date</li>
                                    <li><strong>delivery_s</strong> - Delivery status</li>
                                    <li><strong>delivery_1</strong> - Delivery date</li>
                                    <li><strong>Send To Si</strong> - Send to site date</li>
                                    <li><strong>Send To B</strong> - Send to branch date</li>
                                    <li><strong>Send To A</strong> - Send to agent date</li>
                                    <li><strong>TA Numbe</strong> - TA number</li>
                                    <li><strong>Journal Remarks</strong> - Journal remarks</li>
                                </ul>

                                <h6>Date Formats Supported:</h6>
                                <ul class="list-unstyled">
                                    <li>DD.MM.YYYY (e.g., 20.08.2025) - <strong>Primary format</strong></li>
                                    <li>DD-MM-YYYY (e.g., 20-08-2025)</li>
                                    <li>DD/MM/YYYY (e.g., 20/08/2025)</li>
                                    <li>YYYY-MM-DD (e.g., 2025-08-20)</li>
                                </ul>

                                <h6>Important Notes:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-info-circle text-info"></i> The system will automatically detect
                                        ITO document type</li>
                                    <li><i class="fas fa-info-circle text-info"></i> Duplicate ITO numbers will be
                                        automatically skipped</li>
                                    <li><i class="fas fa-info-circle text-info"></i> All documents will be assigned to your
                                        current location</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap4'
            });

            // Initialize custom file input
            bsCustomFileInput.init();



            // Form validation
            $('#importForm').submit(function(e) {
                var file = $('#file')[0].files[0];
                if (!file) {
                    e.preventDefault();
                    alert('Please select a file to import.');
                    return false;
                }

                // Show loading state
                $('#submitBtn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-2"></i>Importing...');
            });

            // File size validation
            $('#file').change(function() {
                var file = this.files[0];
                var maxSize = 10 * 1024 * 1024; // 10MB

                if (file && file.size > maxSize) {
                    alert('File size must be less than 10MB.');
                    $(this).val('');
                    $('.custom-file-label').text('Choose file');
                }
            });

            // Show Toastr notification for import success
            @if (session('import_success'))
                toastr.success("{{ session('import_success') }}", "Import Completed", {
                    timeOut: 5000,
                    extendedTimeOut: 2000,
                    closeButton: true,
                    progressBar: true
                });
            @endif

            // Show Toastr notification for import error
            @if (session('error'))
                toastr.error("{{ session('error') }}", "Import Failed", {
                    timeOut: 5000,
                    extendedTimeOut: 2000,
                    closeButton: true,
                    progressBar: true
                });
            @endif
        });
    </script>
@endpush
