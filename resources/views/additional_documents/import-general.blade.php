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

                    <div class="card card-primary card-outline card-tabs">
                        <div class="card-header p-0 pt-1 border-bottom-0">
                            <ul class="nav nav-tabs" id="general-import-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="tab-upload-link" data-toggle="pill"
                                        href="#tab-upload" role="tab" aria-controls="tab-upload"
                                        aria-selected="true">
                                        <i class="fas fa-upload mr-1"></i> Upload Excel
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-paste-link" data-toggle="pill" href="#tab-paste"
                                        role="tab" aria-controls="tab-paste" aria-selected="false">
                                        <i class="fas fa-paste mr-1"></i> Paste Manual (Copas dari Excel)
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="general-import-tab-content">
                                <div class="tab-pane fade show active" id="tab-upload" role="tabpanel"
                                    aria-labelledby="tab-upload-link">
                                    <form action="{{ route('additional-documents.process-general-import') }}"
                                        method="POST" enctype="multipart/form-data" id="generalImportForm">
                                        @csrf

                                        <!-- File Upload -->
                                        <div class="form-group">
                                            <label for="general_file">Excel File <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="general_file"
                                                        name="file" accept=".xlsx,.xls" required>
                                                    <label class="custom-file-label" for="general_file">Choose
                                                        file</label>
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
                                                <strong>Multi-Document Creation:</strong> Each row can create up to 3
                                                documents (DO,
                                                GR, MR) based on which fields are populated.
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary" id="generalSubmitBtn">
                                                <i class="fas fa-upload mr-2"></i>
                                                Start General Import
                                            </button>
                                            <a href="{{ route('additional-documents.index') }}"
                                                class="btn btn-secondary ml-2">
                                                <i class="fas fa-arrow-left mr-2"></i>
                                                Back to List
                                            </a>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="tab-paste" role="tabpanel"
                                    aria-labelledby="tab-paste-link">
                                    <form action="{{ route('additional-documents.process-paste-general-import') }}"
                                        method="POST" id="pasteImportForm">
                                        @csrf

                                        <div class="form-group">
                                            <label for="paste_document_type_id">Jenis Dokumen <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control select2bs4 @error('document_type_id') is-invalid @enderror"
                                                id="paste_document_type_id" name="document_type_id" required>
                                                <option value="">Pilih jenis dokumen</option>
                                                @foreach ($documentTypes as $type)
                                                    <option value="{{ $type->id }}"
                                                        {{ (string) old('document_type_id') === (string) $type->id ? 'selected' : '' }}>
                                                        {{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('document_type_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="paste_project_id">Project</label>
                                            <select class="form-control select2bs4 @error('project_id') is-invalid @enderror"
                                                id="paste_project_id" name="project_id">
                                                <option value="">Pilih project (opsional)</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}"
                                                        {{ (string) old('project_id') === (string) $project->id ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->owner }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('project_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="paste_fallback_date">Tanggal Fallback</label>
                                            <input type="date"
                                                class="form-control @error('fallback_date') is-invalid @enderror"
                                                id="paste_fallback_date" name="fallback_date"
                                                value="{{ old('fallback_date', now()->format('Y-m-d')) }}">
                                            <small class="form-text text-muted">Dipakai jika baris tidak mencantumkan
                                                tanggal.</small>
                                            @error('fallback_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="paste_raw_text">Data Copas <span
                                                    class="text-danger">*</span></label>
                                            <textarea class="form-control @error('lines') is-invalid @enderror" id="paste_raw_text"
                                                rows="8"
                                                placeholder="109/BA-Rental Truck/ARKA/VIII/2026&#9;4-Sep-2026&#10;110/BA-Rental Truck/ARKA/VIII/2026&#9;5-Sep-2026"></textarea>
                                            <small class="form-text text-muted">Satu baris = satu dokumen. Kolom 1 =
                                                nomor dokumen, kolom 2 = tanggal (opsional, pisahkan dengan tab dari
                                                Excel).</small>
                                            @error('lines')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <button type="button" class="btn btn-info" id="pastePreviewBtn">
                                                <i class="fas fa-eye mr-2"></i>
                                                Preview
                                            </button>
                                        </div>

                                        <div id="pastePreviewSection" class="d-none">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0"><i class="fas fa-table mr-1"></i> Preview Import</h6>
                                                <span class="badge badge-primary" id="pasteImportCount">0 baris akan
                                                    di-import</span>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered" id="pastePreviewTable">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th style="width: 40px;"></th>
                                                            <th style="width: 50px;">No</th>
                                                            <th>Nomor dokumen</th>
                                                            <th>Tanggal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="pastePreviewBody"></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div id="pasteLinesContainer"></div>

                                        <div class="form-group mt-3">
                                            <button type="submit" class="btn btn-primary" id="pasteSubmitBtn">
                                                <i class="fas fa-file-import mr-2"></i>
                                                Import
                                            </button>
                                            <a href="{{ route('additional-documents.index') }}"
                                                class="btn btn-secondary ml-2">
                                                <i class="fas fa-arrow-left mr-2"></i>
                                                Back to List
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
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

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@section('scripts')
    <script src="{{ asset('adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize custom file input
            bsCustomFileInput.init();

            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            var pastePreviewRows = [];

            function escapeHtml(text) {
                return $('<div>').text(text).html();
            }

            function parsePasteLine(line) {
                var parts = line.split('\t');
                var documentNumber = (parts[0] || '').trim();
                var dateText = parts.length >= 2 ? parts[1] : '';

                return {
                    rawLine: line,
                    documentNumber: documentNumber,
                    dateText: dateText,
                    hasNumber: documentNumber.length > 0
                };
            }

            function syncPasteHiddenInputs() {
                var $container = $('#pasteLinesContainer');
                $container.empty();

                pastePreviewRows.forEach(function(row) {
                    if (row.checked) {
                        $('<input>', {
                            type: 'hidden',
                            name: 'lines[]',
                            value: row.rawLine
                        }).appendTo($container);
                    }
                });
            }

            function updatePasteImportCount() {
                var checkedCount = pastePreviewRows.filter(function(row) {
                    return row.checked;
                }).length;

                $('#pasteImportCount').text(checkedCount + ' baris akan di-import');
            }

            $('#pastePreviewBtn').on('click', function() {
                var rawText = $('#paste_raw_text').val() || '';
                var lines = rawText.split('\n');
                var $tbody = $('#pastePreviewBody');

                pastePreviewRows = [];
                $tbody.empty();

                lines.forEach(function(line, index) {
                    if (line.trim() === '') {
                        return;
                    }

                    var parsed = parsePasteLine(line);
                    var checked = parsed.hasNumber;

                    pastePreviewRows.push({
                        rawLine: parsed.rawLine,
                        checked: checked
                    });

                    var rowIndex = pastePreviewRows.length - 1;
                    var rowClass = parsed.hasNumber ? '' : 'table-danger';
                    var numberCell = parsed.hasNumber
                        ? escapeHtml(parsed.documentNumber)
                        : '<span class="text-danger">tanpa nomor</span>';

                    var $row = $('<tr>').addClass(rowClass);
                    $row.append(
                        '<td><input type="checkbox" class="paste-row-checkbox" data-row-index="' +
                        rowIndex + '"' + (checked ? ' checked' : '') +
                        (parsed.hasNumber ? '' : ' disabled') + '></td>'
                    );
                    $row.append('<td>' + (rowIndex + 1) + '</td>');
                    $row.append('<td>' + numberCell + '</td>');
                    $row.append('<td>' + escapeHtml(parsed.dateText) + '</td>');
                    $tbody.append($row);
                });

                $('#pastePreviewSection').removeClass('d-none');
                syncPasteHiddenInputs();
                updatePasteImportCount();
            });

            $('#pastePreviewBody').on('change', '.paste-row-checkbox', function() {
                var rowIndex = parseInt($(this).data('row-index'), 10);

                if (pastePreviewRows[rowIndex]) {
                    pastePreviewRows[rowIndex].checked = $(this).is(':checked');
                }

                syncPasteHiddenInputs();
                updatePasteImportCount();
            });

            $('#pasteImportForm').on('submit', function(e) {
                syncPasteHiddenInputs();

                if ($('#pasteLinesContainer input[name="lines[]"]').length === 0) {
                    e.preventDefault();
                    alert('Pilih minimal satu baris untuk di-import. Gunakan Preview terlebih dahulu.');
                    return false;
                }

                $('#pasteSubmitBtn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-2"></i>Importing...');
            });

            @if ($errors->has('document_type_id') || $errors->has('project_id') || $errors->has('fallback_date') || $errors->has('lines'))
                $('#tab-upload-link').removeClass('active');
                $('#tab-paste-link').addClass('active');
                $('#tab-upload').removeClass('show active');
                $('#tab-paste').addClass('show active');
            @endif

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
@endsection
