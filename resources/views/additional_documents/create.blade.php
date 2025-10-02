@extends('layouts.main')

@section('title_page')
    Create Additional Document
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('additional-documents.index') }}">Additional
            Documents</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <!-- Enhanced Validation Styles -->
    <style>
        .is-warning {
            border-color: #ffc107 !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
        }

        .warning-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #856404;
        }

        .valid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #155724;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #721c24;
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
        .modal-xl {
            max-width: 90%;
        }

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

        /* Loading state for validation */
        .field-loading {
            position: relative;
        }

        .field-loading::after {
            content: '';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: translateY(-50%) rotate(0deg);
            }

            100% {
                transform: translateY(-50%) rotate(360deg);
            }
        }
    </style>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Document Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('additional-documents.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('additional-documents.store') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="type_id">Document Type <span class="text-danger">*</span></label>
                                            <select class="form-control select2bs4 @error('type_id') is-invalid @enderror"
                                                id="type_id" name="type_id" required>
                                                <option value="">Select Document Type</option>
                                                @foreach ($documentTypes as $type)
                                                    <option value="{{ $type->id }}"
                                                        {{ old('type_id') == $type->id ? 'selected' : '' }}>
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
                                                value="{{ old('document_number') }}" required>
                                            @error('document_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="po_no">PO Number</label>
                                            <input type="text" class="form-control @error('po_no') is-invalid @enderror"
                                                id="po_no" name="po_no" value="{{ old('po_no') }}" maxlength="50">
                                            @error('po_no')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="vendor_code">Vendor Code</label>
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control @error('vendor_code') is-invalid @enderror"
                                                    id="vendor_code" name="vendor_code" value="{{ old('vendor_code') }}"
                                                    maxlength="50" placeholder="Enter vendor code">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-info"
                                                        id="vendor-suggestions-btn" title="Get SAP Code Suggestions">
                                                        <i class="fas fa-clipboard-list"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> Should match supplier's SAP code for PO
                                                suggestions
                                            </small>
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
                                                        {{ old('project', $user->project) == $project->code ? 'selected' : '' }}>
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

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="document_date">Document Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('document_date') is-invalid @enderror"
                                                id="document_date" name="document_date"
                                                value="{{ old('document_date') }}" required>
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
                                                value="{{ old('receive_date', date('Y-m-d')) }}" required>
                                            @error('receive_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="cur_loc">Current Location</label>
                                            @if(auth()->user()->hasAnyRole(['superadmin', 'admin', 'accounting']))
                                                <select class="form-control @error('cur_loc') is-invalid @enderror" 
                                                        id="cur_loc" name="cur_loc" required>
                                                    <option value="">Select Location</option>
                                                    @foreach($departments as $dept)
                                                        <option value="{{ $dept->location_code }}" 
                                                                {{ old('cur_loc', $user->department_location_code) == $dept->location_code ? 'selected' : '' }}>
                                                            {{ $dept->location_code }} - {{ $dept->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle"></i> You can select any location as you have administrative privileges
                                                </small>
                                            @else
                                                <input type="text" class="form-control" id="cur_loc"
                                                    value="{{ $user->department_location_code ?? 'Not assigned' }}"
                                                    placeholder="{{ $user->department_location_code ?? 'No department assigned' }}"
                                                    disabled>
                                                <small class="form-text text-muted">
                                                    @if ($user->department_location_code)
                                                        This will be automatically set to your department's location:
                                                        {{ $user->department_location_code }}
                                                    @else
                                                        Please contact administrator to assign you to a department for proper
                                                        location tracking
                                                    @endif
                                                </small>
                                            @endif
                                            @error('cur_loc')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="remarks">Remarks</label>
                                            <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea>
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
                                            <div class="custom-file">
                                                <input type="file"
                                                    class="custom-file-input @error('attachment') is-invalid @enderror"
                                                    id="attachment" name="attachment"
                                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                                <label class="custom-file-label" for="attachment">Choose file</label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Allowed file types: PDF, DOC, DOCX, JPG, JPEG, PNG (Max: 2MB)
                                            </small>
                                            @error('attachment')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Document
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
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

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

            // Set default project to user's department project if available
            if (!$('#project').val() && '{{ $user->project }}') {
                $('#project').val('{{ $user->project }}').trigger('change');
            }

            // Set default document date to today if not set
            if (!$('#document_date').val()) {
                $('#document_date').val('{{ date('Y-m-d') }}');
            }

            // Vendor Code Suggestions
            $('#vendor-suggestions-btn').click(function() {
                loadSapCodeSuggestions();
            });

            // Auto-populate vendor code when project changes (if project has supplier info)
            $('#project').on('change', function() {
                // This could be enhanced to auto-populate based on project-supplier relationship
                // For now, we'll just show a hint
                if ($(this).val()) {
                    toastr.info(
                        'Tip: You can manually enter the vendor code or use the suggestions button');
                }
            });

            // Enhanced validation feedback
            initializeEnhancedValidation();
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
                    <div class="modal-dialog modal-xl" role="document">
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
                                <!-- Search and Filter Section -->
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-search"></i>
                                                </span>
                                            </div>
                                            <input type="text" class="form-control" id="supplier-search" 
                                                   placeholder="Search by supplier name or SAP code...">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" id="clear-search">
                                                    <i class="fas fa-times"></i> Clear
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted" id="results-count">Showing all ${sapCodes.length} suppliers</small>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary active" data-sort="name">
                                                    <i class="fas fa-sort-alpha-down"></i> Name
                                                </button>
                                                <button type="button" class="btn btn-outline-primary" data-sort="code">
                                                    <i class="fas fa-sort-alpha-down"></i> SAP Code
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Help Text -->
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Tip:</strong> Use the search box to quickly find suppliers. Click on any SAP code to auto-fill the vendor code field.
                                </div>
                                
                                <!-- Results Table -->
                                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                    <table class="table table-hover table-sm">
                                        <thead class="thead-light sticky-top">
                                            <tr>
                                                <th width="20%">
                                                    <i class="fas fa-code mr-1"></i>SAP Code
                                                </th>
                                                <th width="60%">
                                                    <i class="fas fa-building mr-1"></i>Supplier Name
                                                </th>
                                                <th width="20%" class="text-center">
                                                    <i class="fas fa-cog mr-1"></i>Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="supplier-results">
                                            <!-- Dynamic content will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- No Results Message -->
                                <div id="no-results" class="text-center py-4" style="display: none;">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No suppliers found</h5>
                                    <p class="text-muted">Try adjusting your search criteria</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fas fa-times mr-1"></i>Close
                                </button>
                                <small class="text-muted mr-auto">
                                    <i class="fas fa-keyboard mr-1"></i>Use arrow keys to navigate, Enter to select
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if any
            $('#sap-codes-modal').remove();

            // Add modal to body
            $('body').append(modalHtml);

            // Initialize the modal functionality
            initializeSapCodeModal();

            // Show modal
            $('#sap-codes-modal').modal('show');
        }

        function initializeSapCodeModal() {
            let currentSort = 'name';
            let filteredData = window.sapCodesData;

            // Populate initial results
            populateResults(filteredData);

            // Search functionality
            $('#supplier-search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase().trim();

                if (searchTerm === '') {
                    filteredData = window.sapCodesData;
                } else {
                    filteredData = window.sapCodesData.filter(function(supplier) {
                        return supplier.name.toLowerCase().includes(searchTerm) ||
                            supplier.sap_code.toLowerCase().includes(searchTerm);
                    });
                }

                populateResults(filteredData);
                updateResultsCount(filteredData.length);
            });

            // Clear search
            $('#clear-search').on('click', function() {
                $('#supplier-search').val('').trigger('input');
                $('#supplier-search').focus();
            });

            // Sorting functionality
            $('[data-sort]').on('click', function() {
                const sortBy = $(this).data('sort');

                // Update active button
                $('[data-sort]').removeClass('active');
                $(this).addClass('active');

                // Sort data
                filteredData.sort(function(a, b) {
                    if (sortBy === 'name') {
                        return a.name.localeCompare(b.name);
                    } else {
                        return a.sap_code.localeCompare(b.sap_code);
                    }
                });

                currentSort = sortBy;
                populateResults(filteredData);
            });

            // Handle SAP code selection
            $(document).on('click', '.select-sap-code', function() {
                const sapCode = $(this).data('sap-code');
                const supplierName = $(this).data('supplier-name');

                $('#vendor_code').val(sapCode);
                $('#sap-codes-modal').modal('hide');
                validateVendorCode(sapCode);
                toastr.success(`Vendor code set to: ${sapCode} (${supplierName})`);
            });

            // Keyboard navigation
            $('#supplier-search').on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('#sap-codes-modal').modal('hide');
                }
            });

            // Focus search on modal show
            $('#sap-codes-modal').on('shown.bs.modal', function() {
                $('#supplier-search').focus();
            });
        }

        function populateResults(data) {
            const tbody = $('#supplier-results');
            const noResults = $('#no-results');

            if (data.length === 0) {
                tbody.empty();
                noResults.show();
                return;
            }

            noResults.hide();
            tbody.empty();

            data.forEach(function(supplier, index) {
                const row = `
                    <tr class="supplier-row" data-index="${index}">
                        <td>
                            <code class="text-primary font-weight-bold">${supplier.sap_code}</code>
                        </td>
                        <td>
                            <span class="supplier-name">${supplier.name}</span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-primary select-sap-code" 
                                data-sap-code="${supplier.sap_code}" 
                                data-supplier-name="${supplier.name}"
                                title="Select ${supplier.sap_code}">
                                <i class="fas fa-check mr-1"></i>Select
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        function updateResultsCount(count) {
            const total = window.sapCodesData.length;
            if (count === total) {
                $('#results-count').text(`Showing all ${total} suppliers`);
            } else {
                $('#results-count').text(`Showing ${count} of ${total} suppliers`);
            }
        }

        function validateVendorCode(vendorCode) {
            if (!vendorCode) return;

            $.ajax({
                url: '/suppliers/validate-vendor-code',
                method: 'POST',
                data: {
                    vendor_code: vendorCode,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.match_found) {
                            if (response.data.multiple_matches) {
                                toastr.warning('Multiple suppliers have this SAP code: ' + response.data
                                    .suppliers.map(s => s.name).join(', '));
                            } else {
                                toastr.success('Vendor code matches supplier: ' + response.data.suppliers[0]
                                    .name);
                            }
                        } else {
                            toastr.info(
                                'Vendor code doesn\'t match any supplier\'s SAP code, but can still be saved'
                            );
                        }
                    }
                },
                error: function() {
                    toastr.error('Failed to validate vendor code');
                }
            });
        }

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
    </script>
@endsection
