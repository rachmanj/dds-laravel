@extends('layouts.main')

@section('title_page')
    Edit Additional Document
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('additional-documents.index') }}">Additional
            Documents</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">

    <style>
        .linked-invoices-badge {
            cursor: help;
        }

        .linked-invoices-badge.badge-success {
            background-color: #28a745;
        }

        .linked-invoices-badge.badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .linked-invoices-badge.badge-info {
            background-color: #17a2b8;
        }

        /* Enhanced Validation Feedback Styles */
        .form-group {
            position: relative;
        }

        .validation-spinner,
        .validation-result,
        .sap-validation-spinner,
        .sap-validation-result {
            display: inline-block;
            margin-left: 8px;
            font-size: 0.875rem;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .validation-spinner,
        .sap-validation-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .validation-result.success,
        .sap-validation-result.success {
            color: #28a745;
        }

        .validation-result.error,
        .sap-validation-result.error {
            color: #dc3545;
        }

        .validation-result.warning,
        .sap-validation-result.warning {
            color: #ffc107;
        }

        /* Enhanced form field styling */
        .form-control.is-valid {
            border-color: #28a745;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.94-.94 1.44-1.44L6.7 2.3l.94.94L4.68 6.2l-.94.94-.94-.94z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6.6.6.6-.6.6.6-.6.6.6.6-.6.6-.6-.6-.6.6-.6-.6.6-.6-.6-.6z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        /* Modal enhancements */
        .supplier-row:hover {
            background-color: #f8f9fa;
        }

        .supplier-row td {
            vertical-align: middle;
        }

        /* Search input enhancements */
        #supplier-search:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        /* Results count styling */
        #results-count {
            font-weight: 500;
        }

        /* Button enhancements */
        .btn-group-sm .btn {
            font-size: 0.75rem;
        }

        /* Sticky header for table */
        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 10;
        }
    </style>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- ENHANCEMENT: Form Progress Indicator -->
            <div class="row">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><i class="fas fa-tasks"></i> Form Progress:</strong>
                                    <span id="progress-text" class="ml-2">0/8 required fields completed</span>
                                </div>
                                <div class="progress" style="width: 300px; height: 25px;">
                                    <div class="progress-bar progress-bar-striped" id="form-progress-bar" role="progressbar"
                                        style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                        0%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Additional Document Information</h3>
                            <a href="{{ route('additional-documents.index') }}" class="btn btn-sm btn-info float-right">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <form action="{{ route('additional-documents.update', $additionalDocument) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <!-- Basic Information -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="type_id">Document Type <span class="text-danger">*</span></label>
                                            <select class="form-control select2bs4 @error('type_id') is-invalid @enderror"
                                                id="type_id" name="type_id" required>
                                                <option value="">Select Document Type</option>
                                                @foreach ($documentTypes as $type)
                                                    <option value="{{ $type->id }}"
                                                        {{ old('type_id', $additionalDocument->type_id) == $type->id ? 'selected' : '' }}>
                                                        {{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('type_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="document_number">Document Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('document_number') is-invalid @enderror"
                                                id="document_number" name="document_number"
                                                value="{{ old('document_number', $additionalDocument->document_number) }}"
                                                required>
                                            @error('document_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="po_no">PO Number</label>
                                            <input type="text" class="form-control @error('po_no') is-invalid @enderror"
                                                id="po_no" name="po_no"
                                                value="{{ old('po_no', $additionalDocument->po_no) }}" maxlength="50">
                                            @error('po_no')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="vendor_code">Vendor Code</label>
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control @error('vendor_code') is-invalid @enderror"
                                                    id="vendor_code" name="vendor_code"
                                                    value="{{ old('vendor_code', $additionalDocument->vendor_code) }}"
                                                    placeholder="Enter vendor code">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-info"
                                                        id="vendor-suggestions-btn" title="Get SAP Code Suggestions">
                                                        <i class="fas fa-clipboard-list"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @error('vendor_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="project">Project</label>
                                            <select class="form-control select2bs4 @error('project') is-invalid @enderror"
                                                id="project" name="project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('project', $additionalDocument->project) == $project->code ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->owner }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('project')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Dates and Document Details -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="document_date">Document Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('document_date') is-invalid @enderror"
                                                id="document_date" name="document_date"
                                                value="{{ old('document_date', $additionalDocument->document_date ? $additionalDocument->document_date->format('Y-m-d') : '') }}"
                                                required>
                                            @error('document_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="receive_date">Receive Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('receive_date') is-invalid @enderror"
                                                id="receive_date" name="receive_date"
                                                value="{{ old('receive_date', $additionalDocument->receive_date ? $additionalDocument->receive_date->format('Y-m-d') : '') }}"
                                                required>
                                            @error('receive_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="cur_loc">Current Location</label>
                                            @php
                                                $hasDistributions = $additionalDocument->hasBeenDistributed();
                                                $canChangeLocation = $additionalDocument->canChangeLocationManually();
                                            @endphp
                                            @if (auth()->user()->hasAnyRole(['superadmin', 'admin', 'accounting']))
                                                <select
                                                    class="form-control select2bs4 @error('cur_loc') is-invalid @enderror"
                                                    id="cur_loc" name="cur_loc"
                                                    {{ !$canChangeLocation ? 'disabled' : '' }}>
                                                    <option value="">Select Location</option>
                                                    @foreach ($departments as $department)
                                                        <option value="{{ $department->location_code }}"
                                                            {{ old('cur_loc', $additionalDocument->cur_loc) == $department->location_code ? 'selected' : '' }}>
                                                            {{ $department->location_code }} - {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if (!$canChangeLocation)
                                                    <input type="hidden" name="cur_loc"
                                                        value="{{ $additionalDocument->cur_loc }}">
                                                    <small class="text-warning">
                                                        <i class="fas fa-lock"></i> Location locked - This document has
                                                        distribution history.
                                                        Location can only be changed through the distribution process.
                                                    </small>
                                                @endif
                                            @else
                                                <input type="text" class="form-control" id="cur_loc"
                                                    value="{{ $additionalDocument->cur_loc ?? 'Not assigned' }}"
                                                    placeholder="{{ $additionalDocument->cur_loc ?? 'No location assigned' }}"
                                                    disabled>
                                            @endif
                                            @error('cur_loc')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Location and File Upload -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="remarks">Remarks</label>
                                            <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3"
                                                placeholder="Enter any additional notes or comments about this document...">{{ old('remarks', $additionalDocument->remarks) }}</textarea>
                                            @error('remarks')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="attachment">Attachment</label>
                                            @if ($additionalDocument->attachment)
                                                <div class="mb-2">
                                                    <strong>Current Attachment:</strong>
                                                    <a href="{{ route('additional-documents.preview', $additionalDocument) }}"
                                                        class="btn btn-sm btn-info ml-2" target="_blank">
                                                        <i class="fas fa-eye"></i> Preview Current
                                                    </a>
                                                </div>
                                            @endif
                                            <div class="custom-file">
                                                <input type="file"
                                                    class="custom-file-input @error('attachment') is-invalid @enderror"
                                                    id="attachment" name="attachment"
                                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                                <label class="custom-file-label" for="attachment">Choose new file
                                                    (optional)</label>
                                            </div>
                                            @error('attachment')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Change Tracking Summary -->
                                <div class="change-summary" id="change-summary" style="display: none;">
                                    <h6><i class="fas fa-edit"></i> Changes Summary</h6>
                                    <div id="changes-list"></div>
                                </div>

                                <!-- Advanced Metadata Display -->
                                <div class="metadata-section">
                                    <h6><i class="fas fa-info-circle"></i> Document Metadata</h6>
                                    <div class="metadata-grid">
                                        <div class="metadata-item">
                                            <div class="metadata-label">Created By</div>
                                            <div class="metadata-value">
                                                {{ $additionalDocument->creator->name ?? 'Unknown' }}</div>
                                            @if ($additionalDocument->creator && $additionalDocument->creator->department)
                                                <div class="metadata-timestamp">
                                                    {{ $additionalDocument->creator->department->name }}</div>
                                            @endif
                                        </div>
                                        <div class="metadata-item">
                                            <div class="metadata-label">Created Date</div>
                                            <div class="metadata-value">
                                                {{ $additionalDocument->created_at->format('d/m/Y H:i') }}</div>
                                            <div class="metadata-timestamp">
                                                {{ $additionalDocument->created_at->diffForHumans() }}</div>
                                        </div>
                                        <div class="metadata-item">
                                            <div class="metadata-label">Last Updated</div>
                                            <div class="metadata-value">
                                                {{ $additionalDocument->updated_at->format('d/m/Y H:i') }}</div>
                                            @if ($additionalDocument->updated_at != $additionalDocument->created_at)
                                                <div class="metadata-timestamp">Modified
                                                    {{ $additionalDocument->updated_at->diffForHumans() }}</div>
                                            @endif
                                        </div>
                                        <div class="metadata-item">
                                            <div class="metadata-label">Status</div>
                                            <div class="metadata-value">
                                                <span
                                                    class="badge badge-{{ $additionalDocument->status === 'open' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($additionalDocument->status) }}
                                                </span>
                                            </div>
                                            @if ($additionalDocument->status === 'closed')
                                                <div class="metadata-timestamp">Document is archived</div>
                                            @endif
                                        </div>
                                        @if ($additionalDocument->vendor_code)
                                            <div class="metadata-item">
                                                <div class="metadata-label">Vendor Code</div>
                                                <div class="metadata-value">
                                                    <span
                                                        class="badge badge-secondary sap-code-badge">{{ $additionalDocument->vendor_code }}</span>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($additionalDocument->po_no)
                                            <div class="metadata-item">
                                                <div class="metadata-label">PO Number</div>
                                                <div class="metadata-value">{{ $additionalDocument->po_no }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Document Location Info -->
                                @if ($additionalDocument->cur_loc)
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-warning">
                                                <h6><i class="fas fa-map-marker-alt"></i> Location Information</h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Current Location:</strong>
                                                        {{ $additionalDocument->cur_loc }}
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Location Type:</strong>
                                                        @if (str_contains($additionalDocument->cur_loc, 'HLOG'))
                                                            <span class="badge badge-info">Logistics</span>
                                                        @elseif(str_contains($additionalDocument->cur_loc, 'HACC'))
                                                            <span class="badge badge-warning">Accounting</span>
                                                        @elseif(str_contains($additionalDocument->cur_loc, 'HFIN'))
                                                            <span class="badge badge-success">Finance</span>
                                                        @else
                                                            <span class="badge badge-secondary">Other</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Document
                                </button>
                                <a href="{{ route('additional-documents.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <!-- Custom File Input -->
    <script src="{{ asset('adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize custom file input
            bsCustomFileInput.init();

            // Initialize Select2 Bootstrap 4
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select Document Type',
                allowClear: true,
                width: '100%'
            });

            // Initialize Toastr
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "5000"
            };

            // Enhanced validation feedback
            initializeEnhancedValidation();

            // Initialize progress tracking
            initializeProgressTracking();

            // ===== CHANGE TRACKING =====
            const originalValues = {
                'type_id': '{{ $additionalDocument->type_id }}',
                'document_number': '{{ $additionalDocument->document_number }}',
                'document_date': '{{ $additionalDocument->document_date ? $additionalDocument->document_date->format('Y-m-d') : '' }}',
                'receive_date': '{{ $additionalDocument->receive_date ? $additionalDocument->receive_date->format('Y-m-d') : '' }}',
                'po_no': '{{ $additionalDocument->po_no }}',
                'vendor_code': '{{ $additionalDocument->vendor_code }}',
                'project': '{{ $additionalDocument->project }}',
                'cur_loc': '{{ $additionalDocument->cur_loc }}',
                'remarks': '{{ $additionalDocument->remarks }}'
            };

            const fieldLabels = {
                'type_id': 'Document Type',
                'document_number': 'Document Number',
                'document_date': 'Document Date',
                'receive_date': 'Receive Date',
                'po_no': 'PO Number',
                'vendor_code': 'Vendor Code',
                'project': 'Project',
                'cur_loc': 'Current Location',
                'remarks': 'Remarks'
            };

            function trackChanges() {
                const changes = [];

                $('input, select, textarea').each(function() {
                    const fieldName = $(this).attr('name');
                    if (fieldName && originalValues[fieldName] !== undefined) {
                        const currentValue = $(this).val();
                        const originalValue = originalValues[fieldName];

                        if (currentValue !== originalValue) {
                            changes.push({
                                field: fieldName,
                                label: fieldLabels[fieldName] || fieldName,
                                oldValue: originalValue || '(empty)',
                                newValue: currentValue || '(empty)'
                            });

                            // Add visual indicator
                            $(this).addClass('field-changed');
                            if (!$(this).parent().find('.change-indicator').length) {
                                $(this).parent().append('<div class="change-indicator"></div>');
                            }
                        } else {
                            $(this).removeClass('field-changed');
                            $(this).parent().find('.change-indicator').remove();
                        }
                    }
                });

                // Check if a new file is selected
                if ($('#attachment')[0].files.length > 0) {
                    changes.push({
                        field: 'attachment',
                        label: 'Attachment',
                        oldValue: 'Current file',
                        newValue: 'New file selected'
                    });
                }

                updateChangeSummary(changes);
            }

            function updateChangeSummary(changes) {
                const summary = $('#change-summary');
                const changesList = $('#changes-list');

                if (changes.length > 0) {
                    summary.show();
                    changesList.empty();

                    changes.forEach(function(change) {
                        changesList.append(`
                            <div class="change-item">
                                <span class="change-field">${change.label}</span>
                                <span class="change-value">${change.oldValue} → ${change.newValue}</span>
                            </div>
                        `);
                    });
                } else {
                    summary.hide();
                }
            }

            // Track changes on input
            $('input, select, textarea').on('input change', function() {
                trackChanges();
            });

            // ===== REAL-TIME VALIDATION =====

            // Document Number Validation
            $('#document_number').on('input', function() {
                const value = $(this).val();
                const field = $(this);

                if (value.length < 3) {
                    field.removeClass('is-valid is-warning').addClass('is-invalid');
                    showFieldFeedback(field, 'Document number must be at least 3 characters long.',
                        'error');
                } else if (value.length > 50) {
                    field.removeClass('is-valid is-invalid').addClass('is-warning');
                    showFieldFeedback(field,
                        'Document number is quite long. Consider using a shorter identifier.', 'warning'
                    );
                } else {
                    field.removeClass('is-invalid is-warning').addClass('is-valid');
                    showFieldFeedback(field, 'Document number looks good!', 'success');
                }
            });

            // Vendor Code Validation
            $('#vendor_code').on('input', function() {
                const value = $(this).val();
                const field = $(this);

                if (value.length > 0) {
                    if (value.length < 3) {
                        field.removeClass('is-valid is-warning').addClass('is-invalid');
                        showFieldFeedback(field, 'Vendor code should be at least 3 characters long.',
                            'error');
                    } else if (!/^[A-Z0-9]+$/.test(value)) {
                        field.removeClass('is-valid is-invalid').addClass('is-warning');
                        showFieldFeedback(field,
                            'Vendor code should contain only uppercase letters and numbers.', 'warning');
                    } else {
                        field.removeClass('is-invalid is-warning').addClass('is-valid');
                        showFieldFeedback(field, 'Vendor code format looks good!', 'success');
                    }
                } else {
                    field.removeClass('is-valid is-invalid is-warning');
                    clearFieldFeedback(field);
                }
            });

            // PO Number Validation
            $('#po_no').on('input', function() {
                const value = $(this).val();
                const field = $(this);

                if (value.length > 0) {
                    if (value.length < 3) {
                        field.removeClass('is-valid is-warning').addClass('is-invalid');
                        showFieldFeedback(field, 'PO number should be at least 3 characters long.',
                            'error');
                    } else {
                        field.removeClass('is-invalid is-warning').addClass('is-valid');
                        showFieldFeedback(field, 'PO number looks good!', 'success');
                    }
                } else {
                    field.removeClass('is-valid is-invalid is-warning');
                    clearFieldFeedback(field);
                }
            });

            // Date Validation
            $('#document_date, #receive_date').on('change', function() {
                const documentDate = $('#document_date').val();
                const receiveDate = $('#receive_date').val();
                const receiveField = $('#receive_date');

                if (documentDate && receiveDate) {
                    if (new Date(receiveDate) < new Date(documentDate)) {
                        receiveField.removeClass('is-valid is-warning').addClass('is-invalid');
                        showFieldFeedback(receiveField,
                            'Receive date cannot be earlier than document date.', 'error');
                    } else {
                        receiveField.removeClass('is-invalid is-warning').addClass('is-valid');
                        showFieldFeedback(receiveField, 'Date validation passed!', 'success');
                    }
                } else {
                    receiveField.removeClass('is-valid is-invalid is-warning');
                    clearFieldFeedback(receiveField);
                }
            });

            function showFieldFeedback(field, message, type) {
                clearFieldFeedback(field);

                const feedbackClass = type === 'error' ? 'invalid-feedback' :
                    type === 'warning' ? 'warning-feedback' : 'valid-feedback';

                field.after(`<div class="${feedbackClass}">${message}</div>`);
            }

            function clearFieldFeedback(field) {
                field.next('.valid-feedback, .invalid-feedback, .warning-feedback').remove();
            }

            // ===== SAP CODE SUGGESTIONS =====
            $('#vendor-suggestions-btn').on('click', function() {
                loadSapCodeSuggestions();
            });

            function loadSapCodeSuggestions() {
                $.ajax({
                    url: '/suppliers/sap-codes',
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            showSapCodeModal(response.data);
                        } else {
                            toastr.warning('No SAP codes found in the system');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to load SAP code suggestions');
                    }
                });
            }

            function showSapCodeModal(sapCodes) {
                // Store original data for filtering
                window.sapCodesData = sapCodes;

                let modalHtml = `
                    <div class="modal fade" id="sap-codes-modal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">
                                        <i class="fas fa-clipboard-list mr-2"></i>
                                        Available SAP Codes
                                    </h5>
                                    <button type="button" class="close text-white" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="sap-search" placeholder="Search SAP codes...">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" id="clear-search">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-outline-primary btn-sm" id="sort-sap-asc">
                                                    <i class="fas fa-sort-alpha-down"></i> A-Z
                                                </button>
                                                <button type="button" class="btn btn-outline-primary btn-sm" id="sort-sap-desc">
                                                    <i class="fas fa-sort-alpha-up"></i> Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="sap-codes-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>SAP Code</th>
                                                    <th>Supplier Name</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="sap-codes-tbody">
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-3">
                                        <small class="text-muted" id="sap-results-count">0 results</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remove existing modal if any
                $('#sap-codes-modal').remove();

                // Add modal to body
                $('body').append(modalHtml);

                // Show modal
                $('#sap-codes-modal').modal('show');

                // Populate table
                populateSapCodesTable(sapCodes);

                // Setup search functionality
                setupSapSearch();
            }

            function populateSapCodesTable(sapCodes) {
                const tbody = $('#sap-codes-tbody');
                tbody.empty();

                sapCodes.forEach(function(supplier) {
                    const row = `
                        <tr class="supplier-row" data-sap-code="${supplier.sap_code}">
                            <td>
                                <span class="badge badge-primary sap-code-badge">${supplier.sap_code}</span>
                            </td>
                            <td>${supplier.name}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary select-sap-code" 
                                        data-sap-code="${supplier.sap_code}">
                                    <i class="fas fa-check"></i> Select
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });

                updateSapResultsCount(sapCodes.length);
            }

            function setupSapSearch() {
                $('#sap-search').on('input', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    const rows = $('#sap-codes-tbody tr');
                    let visibleCount = 0;

                    rows.each(function() {
                        const sapCode = $(this).find('.sap-code-badge').text().toLowerCase();
                        const supplierName = $(this).find('td:nth-child(2)').text().toLowerCase();

                        if (sapCode.includes(searchTerm) || supplierName.includes(searchTerm)) {
                            $(this).show();
                            visibleCount++;
                        } else {
                            $(this).hide();
                        }
                    });

                    updateSapResultsCount(visibleCount);
                });

                $('#clear-search').on('click', function() {
                    $('#sap-search').val('');
                    $('#sap-codes-tbody tr').show();
                    updateSapResultsCount(window.sapCodesData.length);
                });

                // Sorting
                $('#sort-sap-asc').on('click', function() {
                    sortSapCodes('asc');
                });

                $('#sort-sap-desc').on('click', function() {
                    sortSapCodes('desc');
                });

                // Selection
                $(document).on('click', '.select-sap-code', function() {
                    const sapCode = $(this).data('sap-code');
                    $('#vendor_code').val(sapCode).trigger('input');
                    $('#sap-codes-modal').modal('hide');
                    toastr.success(`SAP code ${sapCode} selected`);
                });
            }

            function sortSapCodes(direction) {
                const tbody = $('#sap-codes-tbody');
                const rows = tbody.find('tr').toArray();

                rows.sort(function(a, b) {
                    const sapCodeA = $(a).find('.sap-code-badge').text();
                    const sapCodeB = $(b).find('.sap-code-badge').text();

                    if (direction === 'asc') {
                        return sapCodeA.localeCompare(sapCodeB);
                    } else {
                        return sapCodeB.localeCompare(sapCodeA);
                    }
                });

                tbody.empty().append(rows);
            }

            function updateSapResultsCount(count) {
                $('#sap-results-count').text(`${count} result${count !== 1 ? 's' : ''}`);
            }

            // ===== FORM SUBMISSION =====
            $('form').on('submit', function(e) {
                const changes = [];

                $('input, select, textarea').each(function() {
                    const fieldName = $(this).attr('name');
                    if (fieldName && originalValues[fieldName] !== undefined) {
                        const currentValue = $(this).val();
                        const originalValue = originalValues[fieldName];

                        if (currentValue !== originalValue) {
                            changes.push(fieldLabels[fieldName] || fieldName);
                        }
                    }
                });

                if ($('#attachment')[0].files.length > 0) {
                    changes.push('Attachment');
                }

                if (changes.length > 0) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirm Changes',
                        html: `You are about to update the following fields:<br><br><strong>${changes.join(', ')}</strong><br><br>Are you sure you want to proceed?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, update it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('form').off('submit').submit();
                        }
                    });
                }
            });

            // Show success message if exists
            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @endif

            // Show error message if exists
            @if (session('error'))
                toastr.error('{{ session('error') }}');
            @endif

            // Initial change tracking
            trackChanges();
        });

        // Enhanced Validation Feedback System
        function initializeEnhancedValidation() {
            // Document Number validation
            $('#document_number').on('input blur', function() {
                validateDocumentNumber($(this));
            });

            // PO Number validation
            $('#po_no').on('input blur', function() {
                validatePONumber($(this));
            });

            // Vendor Code validation
            $('#vendor_code').on('input blur', function() {
                validateVendorCodeField($(this));
            });

            // Document Date validation
            $('#document_date').on('change blur', function() {
                validateDocumentDate($(this));
            });

            // Receive Date validation
            $('#receive_date').on('change blur', function() {
                validateReceiveDate($(this));
            });

            // Form submission validation
            $('form').on('submit', function(e) {
                if (!validateFormBeforeSubmit()) {
                    e.preventDefault();
                    showValidationSummary();
                }
            });
        }

        function validateDocumentNumber(field) {
            const value = field.val().trim();
            const feedback = field.siblings('.invalid-feedback');

            // Remove existing validation classes
            field.removeClass('is-valid is-invalid');
            feedback.remove();

            if (value === '') {
                showFieldError(field, 'Document number is required');
                return false;
            }

            if (value.length < 3) {
                showFieldError(field, 'Document number must be at least 3 characters');
                return false;
            }

            // Check for duplicates (simplified check)
            if (value.length >= 3) {
                showFieldSuccess(field, 'Document number format looks good');
            }

            return true;
        }

        function validatePONumber(field) {
            const value = field.val().trim();

            // Remove existing validation classes
            field.removeClass('is-valid is-invalid');
            field.siblings('.invalid-feedback').remove();

            if (value === '') {
                // PO Number is optional, so empty is valid
                return true;
            }

            if (value.length < 3) {
                showFieldError(field, 'PO number should be at least 3 characters if provided');
                return false;
            }

            // Check for common PO patterns
            const poPattern = /^[A-Z0-9\-_]+$/i;
            if (!poPattern.test(value)) {
                showFieldError(field, 'PO number should contain only letters, numbers, hyphens, and underscores');
                return false;
            }

            showFieldSuccess(field, 'PO number format looks good');
            return true;
        }

        function validateVendorCodeField(field) {
            const value = field.val().trim();

            // Remove existing validation classes
            field.removeClass('is-valid is-invalid');
            field.siblings('.invalid-feedback').remove();

            if (value === '') {
                // Vendor code is optional
                return true;
            }

            if (value.length < 3) {
                showFieldError(field, 'Vendor code should be at least 3 characters if provided');
                return false;
            }

            // Check for SAP code pattern (typically uppercase letters and numbers)
            const sapPattern = /^[A-Z0-9]+$/;
            if (!sapPattern.test(value)) {
                showFieldError(field, 'Vendor code should contain only uppercase letters and numbers');
                return false;
            }

            showFieldSuccess(field, 'Vendor code format looks good');

            // Trigger validation with backend
            if (value.length >= 3) {
                validateVendorCode(value);
            }

            return true;
        }

        function validateDocumentDate(field) {
            const value = field.val();
            const today = new Date();
            const selectedDate = new Date(value);

            // Remove existing validation classes
            field.removeClass('is-valid is-invalid');
            field.siblings('.invalid-feedback').remove();

            if (value === '') {
                showFieldError(field, 'Document date is required');
                return false;
            }

            if (selectedDate > today) {
                showFieldError(field, 'Document date cannot be in the future');
                return false;
            }

            // Check if date is too old (more than 1 year)
            const oneYearAgo = new Date();
            oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);

            if (selectedDate < oneYearAgo) {
                showFieldWarning(field, 'Document date is more than 1 year old. Please verify this is correct.');
            } else {
                showFieldSuccess(field, 'Document date is valid');
            }

            // Check if date is a business day (Monday-Friday)
            const dayOfWeek = selectedDate.getDay();
            if (dayOfWeek === 0 || dayOfWeek === 6) { // Sunday = 0, Saturday = 6
                showFieldWarning(field, 'Document date falls on a weekend. Please verify this is correct.');
            }

            return true;
        }

        function validateReceiveDate(field) {
            const value = field.val();
            const documentDate = new Date($('#document_date').val());
            const selectedDate = new Date(value);

            // Remove existing validation classes
            field.removeClass('is-valid is-invalid');
            field.siblings('.invalid-feedback').remove();

            if (value === '') {
                showFieldError(field, 'Receive date is required');
                return false;
            }

            if (selectedDate > new Date()) {
                showFieldError(field, 'Receive date cannot be in the future');
                return false;
            }

            // Check if receive date is before document date
            if ($('#document_date').val() && selectedDate < documentDate) {
                showFieldWarning(field, 'Receive date is before document date. Please verify this is correct.');
            } else {
                showFieldSuccess(field, 'Receive date is valid');
            }

            // Check if date is a business day (Monday-Friday)
            const dayOfWeek = selectedDate.getDay();
            if (dayOfWeek === 0 || dayOfWeek === 6) { // Sunday = 0, Saturday = 6
                showFieldWarning(field, 'Receive date falls on a weekend. Please verify this is correct.');
            }

            return true;
        }

        function showFieldError(field, message) {
            field.addClass('is-invalid');
            field.after(`<div class="invalid-feedback">${message}</div>`);
        }

        function showFieldSuccess(field, message) {
            field.addClass('is-valid');
            field.after(`<div class="valid-feedback">${message}</div>`);
        }

        function showFieldWarning(field, message) {
            field.addClass('is-warning');
            field.after(`<div class="warning-feedback text-warning small">${message}</div>`);
        }

        function validateFormBeforeSubmit() {
            let isValid = true;

            // Validate required fields
            const requiredFields = [{
                    field: '#type_id',
                    message: 'Please select a document type'
                },
                {
                    field: '#document_number',
                    message: 'Document number is required'
                },
                {
                    field: '#document_date',
                    message: 'Document date is required'
                },
                {
                    field: '#receive_date',
                    message: 'Receive date is required'
                }
            ];

            requiredFields.forEach(function(item) {
                const field = $(item.field);
                if (!field.val() || field.val().trim() === '') {
                    showFieldError(field, item.message);
                    isValid = false;
                }
            });

            // Validate optional fields
            validateDocumentNumber($('#document_number'));
            validatePONumber($('#po_no'));
            validateVendorCodeField($('#vendor_code'));
            validateDocumentDate($('#document_date'));
            validateReceiveDate($('#receive_date'));

            return isValid;
        }

        function showValidationSummary() {
            const errorCount = $('.is-invalid').length;
            const warningCount = $('.is-warning').length;

            let message = '';
            if (errorCount > 0) {
                message += `${errorCount} field(s) have errors. `;
            }
            if (warningCount > 0) {
                message += `${warningCount} field(s) have warnings. `;
            }

            if (errorCount > 0) {
                toastr.error(message + 'Please fix the errors before submitting.');
            } else if (warningCount > 0) {
                toastr.warning(message + 'Please review the warnings before submitting.');
            }

            // Scroll to first error
            const firstError = $('.is-invalid').first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
                firstError.focus();
            }
        }

        // ===== PROGRESS TRACKING FUNCTIONS =====

        function initializeProgressTracking() {
            // Update progress on any field change
            $('form :input').on('change input blur', updateFormProgress);

            // Initial progress update
            setTimeout(updateFormProgress, 1000);
        }

        function updateFormProgress() {
            // Define required fields
            const requiredFields = [
                '#type_id',
                '#document_number',
                '#document_date',
                '#receive_date',
                '#cur_loc',
                '#remarks',
                '#attachment',
                '#vendor_code'
            ];

            let filled = 0;
            let total = requiredFields.length;

            requiredFields.forEach(function(field) {
                const element = $(field);
                if (element.length && element.val() && element.val().trim() !== '') {
                    filled++;
                }
            });

            var percentage = total > 0 ? Math.round((filled / total) * 100) : 0;

            // Update progress bar
            $('#form-progress-bar')
                .css('width', percentage + '%')
                .attr('aria-valuenow', percentage)
                .text(percentage + '%')
                .removeClass('bg-danger bg-warning bg-success')
                .addClass(
                    percentage >= 100 ? 'bg-success' :
                    percentage >= 75 ? 'bg-info' :
                    percentage >= 50 ? 'bg-warning' : 'bg-danger'
                );

            // Add animation when progress increases
            if (percentage === 100) {
                $('#form-progress-bar').addClass('progress-bar-animated');
            }

            $('#progress-text').text(filled + '/' + total + ' required fields completed');
        }
    </script>
@endsection
