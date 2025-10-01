@extends('layouts.main')

@section('title_page')
    Create New Invoice
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

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
    </style>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Invoice Information</h3>
                            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-info float-right">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <form action="{{ route('invoices.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <!-- Basic Invoice Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="supplier_id">Supplier <span class="text-danger">*</span></label>
                                            <select
                                                class="form-control select2bs4 @error('supplier_id') is-invalid @enderror"
                                                id="supplier_id" name="supplier_id" required>
                                                <option value="">Select Supplier</option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}"
                                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('supplier_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_number">Invoice Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('invoice_number') is-invalid @enderror"
                                                id="invoice_number" name="invoice_number"
                                                value="{{ old('invoice_number') }}" required>
                                            @error('invoice_number')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Dates and PO Number -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="invoice_date">Invoice Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('invoice_date') is-invalid @enderror"
                                                id="invoice_date" name="invoice_date" value="{{ old('invoice_date') }}"
                                                required>
                                            @error('invoice_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="receive_date">Receive Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('receive_date') is-invalid @enderror"
                                                id="receive_date" name="receive_date" value="{{ old('receive_date') }}"
                                                required>
                                            @error('receive_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="po_no">PO Number</label>
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control @error('po_no') is-invalid @enderror" id="po_no"
                                                    name="po_no" value="{{ old('po_no') }}" maxlength="30"
                                                    placeholder="Enter PO number">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        id="search-docs-btn" title="Search Additional Documents">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @error('po_no')
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> Enter PO number and click search to find
                                                related additional documents
                                            </small>
                                        </div>
                                    </div>
                                </div>


                                <!-- Invoice Type, Currency, and Amount -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="type_id">Invoice Type <span class="text-danger">*</span></label>
                                            <select class="form-control @error('type_id') is-invalid @enderror"
                                                id="type_id" name="type_id" required>
                                                <option value="">Select Invoice Type</option>
                                                @foreach ($invoiceTypes as $type)
                                                    <option value="{{ $type->id }}"
                                                        {{ old('type_id') == $type->id ? 'selected' : '' }}>
                                                        {{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('type_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="currency">Currency <span class="text-danger">*</span></label>
                                            <select class="form-control @error('currency') is-invalid @enderror"
                                                id="currency" name="currency" required>
                                                <option value="IDR" {{ old('currency') == 'IDR' ? 'selected' : '' }}>
                                                    IDR
                                                </option>
                                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>
                                                    USD
                                                </option>
                                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>
                                                    EUR
                                                </option>
                                                <option value="SGD" {{ old('currency') == 'SGD' ? 'selected' : '' }}>
                                                    SGD
                                                </option>
                                            </select>
                                            @error('currency')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="amount">Amount <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="currency-prefix">IDR</span>
                                                </div>
                                                <input type="text" name="amount_display" id="amount_display"
                                                    class="form-control @error('amount') is-invalid @enderror"
                                                    value="{{ old('amount') }}" onkeyup="formatNumber(this)"
                                                    placeholder="0.00" required>
                                                <input type="hidden" name="amount" id="amount"
                                                    value="{{ old('amount') }}">
                                            </div>
                                            @error('amount')
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Information -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="receive_project">Receive Project</label>
                                            <input type="text" class="form-control" id="receive_project"
                                                name="receive_project" value="{{ auth()->user()->project ?? '' }}"
                                                readonly>
                                            <small class="form-text text-muted">Automatically set to your department's
                                                project</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="invoice_project">Invoice Project</label>
                                            <select class="form-control @error('invoice_project') is-invalid @enderror"
                                                id="invoice_project" name="invoice_project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('invoice_project') == $project->code ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('invoice_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="payment_project">Payment Project</label>
                                            <select class="form-control @error('payment_project') is-invalid @enderror"
                                                id="payment_project" name="payment_project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('payment_project') == $project->code || $project->code == '001H' ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('payment_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Location and Additional Information -->
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="cur_loc">Current Location <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control @error('cur_loc') is-invalid @enderror"
                                                id="cur_loc" name="cur_loc"
                                                {{ !auth()->user()->hasRole(['superadmin', 'admin'])? 'disabled': '' }}
                                                required>
                                                <option value="">Select Location</option>
                                                @foreach ($departments as $dept)
                                                    @if ($dept->location_code)
                                                        <option value="{{ $dept->location_code }}"
                                                            {{ old('cur_loc') == $dept->location_code ||
                                                            (auth()->user()->department_location_code == $dept->location_code && !old('cur_loc'))
                                                                ? 'selected'
                                                                : '' }}>
                                                            {{ $dept->location_code }} - {{ $dept->name }}
                                                            ({{ $dept->project }})
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @error('cur_loc')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                @if (!auth()->user()->hasRole(['superadmin', 'admin']))
                                                    This is set to your department's location and cannot be changed.
                                                @else
                                                    You can change the location as you have administrative privileges.
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="faktur_no">Faktur No</label>
                                            <input type="text"
                                                class="form-control @error('faktur_no') is-invalid @enderror"
                                                id="faktur_no" name="faktur_no" value="{{ old('faktur_no') }}">
                                            @error('faktur_no')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="sap_doc">SAP Document</label>
                                            <input type="text"
                                                class="form-control @error('sap_doc') is-invalid @enderror"
                                                id="sap_doc" name="sap_doc" value="{{ old('sap_doc') }}">
                                            @error('sap_doc')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks -->
                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea>
                                    @error('remarks')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>


                                <!-- Link Additional Documents (optional) -->
                                <div class="card card-outline card-secondary mt-3" id="additional-docs-card">
                                    <div class="card-header">
                                        <h3 class="card-title">Link Additional Documents (optional)</h3>
                                        <div class="card-tools">
                                            @if (auth()->user()->can('on-the-fly-addoc-feature'))
                                                <button type="button" class="btn btn-sm btn-success mr-2"
                                                    id="create-doc-btn">
                                                    <i class="fas fa-plus"></i> Create New Document
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-secondary mr-2"
                                                id="refresh-docs-btn" style="display:none;">
                                                <i class="fas fa-sync-alt"></i> Refresh
                                            </button>
                                            <span class="badge badge-info" id="selected-count">Selected: 0</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="additional-docs-loading" style="display:none;">
                                            <i class="fas fa-spinner fa-spin"></i> Searching by PO No...
                                        </div>
                                        <div class="table-responsive" id="additional-docs-table-wrapper"
                                            style="display:none;">
                                            <table class="table table-sm table-bordered" id="additional-docs-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:32px;"><input type="checkbox"
                                                                id="select-all-docs"></th>
                                                        <th>Document No</th>
                                                        <th>Type</th>
                                                        <th>Date</th>
                                                        <th>PO No</th>
                                                        <th>Current Location</th>
                                                        <th>Remarks</th>
                                                        <th>Linked Invoices</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                            <small class="text-muted">Showing up to 50 results.</small>
                                        </div>
                                        <div id="additional-docs-empty" class="text-muted" style="display:none;">
                                            No documents found for this PO No. Adjust PO or link manually later.
                                        </div>
                                        <div id="selected-docs-wrapper" class="mt-3" style="display:none;">
                                            <h6>Currently selected</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped" id="selected-docs-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Document No</th>
                                                            <th>Type</th>
                                                            <th>Date</th>
                                                            <th>PO No</th>
                                                            <th>Current Location</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Invoice
                                </button>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Create Additional Document Modal -->
    <div class="modal fade" id="create-doc-modal" tabindex="-1" role="dialog"
        aria-labelledby="create-doc-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="create-doc-modal-label">Create New Additional
                        Document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="create-doc-form">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_type_id">Document Type <span class="text-danger">*</span></label>
                                    <select class="form-control select2bs4" id="doc_type_id" name="document_type_id"
                                        required>
                                        <option value="">Select Document Type</option>
                                        @forelse ($additionalDocumentTypes ?? [] as $type)
                                            <option value="{{ $type->id }}">
                                                {{ $type->type_name }}</option>
                                        @empty
                                            <option value="">No document types available - Please contact admin
                                            </option>
                                        @endforelse
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_number">Document Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="doc_number" name="document_number"
                                        required maxlength="255">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_date">Document Date</label>
                                    <input type="date" class="form-control" id="doc_date" name="document_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_receive_date">Document Receive Date</label>
                                    <input type="date" class="form-control" id="doc_receive_date"
                                        name="document_receive_date">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_cur_loc">Current Location <span class="text-danger">*</span></label>
                                    <select class="form-control select2bs4" id="doc_cur_loc" name="cur_loc" required>
                                        <option value="">Select Location</option>
                                        <option value="000HLOG">000HLOG (Logistic)</option>
                                        @if (auth()->user() && auth()->user()->department && auth()->user()->department->location_code)
                                            <option value="{{ auth()->user()->department->location_code }}" selected>
                                                {{ auth()->user()->department->location_code }}
                                                ({{ auth()->user()->department->name }})
                                            </option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_po_no">PO Number</label>
                                    <input type="text" class="form-control" id="doc_po_no" name="po_no"
                                        maxlength="255">
                                    <small class="form-text text-muted">This document will be
                                        automatically attached to the current invoice.</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_project">Project</label>
                                    <select class="form-control select2bs4" id="doc_project" name="project">
                                        <option value="">Select Project</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->code }}"
                                                {{ auth()->user()->project == $project->code ? 'selected' : '' }}>
                                                {{ $project->code }} - {{ $project->owner }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Optional: Assign to specific project</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="create-doc-submit">
                            <i class="fas fa-plus"></i> Create Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        // Define formatNumber function globally
        function formatNumber(input) {
            // Remove any non-digit characters except dots
            let value = input.value.replace(/[^\d.]/g, '');
            // Ensure only one decimal point
            let parts = value.split('.');
            if (parts.length > 2) {
                parts = [parts[0], parts.slice(1).join('')];
            }
            // Add thousand separators to display
            let displayValue = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            if (parts.length > 1) {
                displayValue += '.' + parts[1];
            }
            input.value = displayValue;

            // Update hidden field with clean numeric value
            let numericValue = parts.join('.');
            document.getElementById('amount').value = numericValue;
        }

        // Wait for jQuery to be available
        function initializeInvoiceForm() {
            if (typeof $ === 'undefined') {
                // jQuery not ready yet, wait a bit more
                setTimeout(initializeInvoiceForm, 100);
                return;
            }

            console.log('Invoice create form loaded, jQuery version:', $.fn.jquery);

            // Global AJAX error handler for session timeouts
            $(document).ajaxError(function(event, xhr, settings) {
                // Skip session check endpoint to avoid infinite loops
                if (settings.url.includes('check-session')) {
                    return;
                }

                if (xhr.status === 401 || xhr.status === 419) {
                    // Clear any existing toasts
                    if (typeof toastr !== 'undefined') {
                        toastr.clear();
                        toastr.remove();
                        toastr.error('Your session has expired. Redirecting to login page...');
                    }

                    // Prevent further AJAX requests
                    $(document).off('ajaxSend');

                    // Redirect to login page
                    setTimeout(function() {
                        window.location.href = '{{ route('login') }}';
                    }, 1500);

                    // Prevent default form submissions
                    $(document).on('submit', 'form', function(e) {
                        e.preventDefault();
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Your session has expired. Please wait for redirect...');
                        }
                        return false;
                    });
                }
            });

            // Initialize Toastr
            if (typeof toastr !== 'undefined') {
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

                // Show session messages if any
                @if (session('success'))
                    toastr.success("{{ session('success') }}");
                @endif

                @if (session('error'))
                    toastr.error("{{ session('error') }}");
                @endif

                @if (session('warning'))
                    toastr.warning("{{ session('warning') }}");
                @endif

                @if (session('info'))
                    toastr.info("{{ session('info') }}");
                @endif
            } else {
                console.error('Toastr not loaded');
            }

            // Initialize Select2 Bootstrap 4 for supplier
            console.log('Initializing Select2 for supplier');
            try {
                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select2bs4').select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select Supplier',
                        allowClear: true,
                        width: '100%'
                    });
                    console.log('Select2 initialized successfully');
                } else {
                    console.error('Select2 plugin not loaded');
                }
            } catch (error) {
                console.error('Error initializing Select2:', error);
            }

            // Check if session is still valid before form submission
            function checkSessionAndSubmitForm(form) {
                // First check if session is still valid
                $.ajax({
                    url: '{{ route('invoices.check-session') }}',
                    type: 'GET',
                    success: function(response) {
                        // Session is valid, proceed with form submission
                        submitFormWithAjax(form);
                    },
                    error: function(xhr) {
                        if (xhr.status === 401 || xhr.status === 419) {
                            // Session expired, redirect to login
                            toastr.error('Your session has expired. Redirecting to login page...');
                            setTimeout(function() {
                                window.location.href = '{{ route('login') }}';
                            }, 1500);
                        } else {
                            // Other error, try regular form submission
                            form.submit();
                        }
                    }
                });
            }

            // Function to submit form with AJAX
            function submitFormWithAjax(form) {
                // Validate required fields
                var isValid = true;
                $(form).find('[required]').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    console.log('Validation failed, showing error');
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Please fill in all required fields.');
                    }
                    return false;
                }

                // Show loading message
                console.log('Validation passed, showing info');
                if (typeof toastr !== 'undefined') {
                    toastr.info('Creating invoice...', 'Please wait');
                }

                // Submit form via AJAX
                var formData = new FormData(form);
                var url = $(form).attr('action');
                var method = $(form).attr('method');

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Clear draft after successful submission
                            localStorage.removeItem('invoice_create_draft');
                            $(document).trigger('invoice-created-success');

                            toastr.success(response.message || 'Invoice created successfully.');
                            // Redirect to index page after short delay
                            setTimeout(function() {
                                window.location.href = '{{ route('invoices.index') }}';
                            }, 1500);
                        } else {
                            toastr.error(response.message || 'Failed to create invoice.');
                        }
                    },
                    error: function(xhr) {
                        // Clear the "Creating invoice..." message
                        toastr.clear();

                        if (xhr.status === 401 || xhr.status === 419) {
                            // Unauthorized or CSRF token mismatch (session expired)
                            toastr.error('Your session has expired. Redirecting to login page...');
                            setTimeout(function() {
                                window.location.href = '{{ route('login') }}';
                            }, 1500);
                        } else if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                $('#' + field).addClass('is-invalid');
                                $('#' + field + '-error').text(messages[0]);
                            });
                            toastr.error('Please correct the validation errors.');
                        } else {
                            toastr.error('An error occurred while creating the invoice.');
                            console.error('Error details:', xhr);
                        }
                    }
                });
            }

            // Handle form submission
            $('form').on('submit', function(e) {
                e.preventDefault();
                console.log('Form submission started');
                checkSessionAndSubmitForm(this);
            });

            // Set default dates
            if (!$('#invoice_date').val()) {
                $('#invoice_date').val(new Date().toISOString().split('T')[0]);
            }
            if (!$('#receive_date').val()) {
                $('#receive_date').val(new Date().toISOString().split('T')[0]);
            }

            // Set current location from user's department if available
            @if (auth()->user()->department_location_code)
                if (!$('#cur_loc').val()) {
                    $('#cur_loc').val('{{ auth()->user()->department_location_code }}');
                }
            @endif

            // Real-time invoice number validation per supplier
            var validationTimeout;

            function validateInvoiceNumber() {
                var invoiceNumber = $('#invoice_number').val().trim();
                var supplierId = $('#supplier_id').val();

                if (invoiceNumber && supplierId) {
                    clearTimeout(validationTimeout);
                    validationTimeout = setTimeout(function() {
                        $.ajax({
                            url: '{{ route('invoices.validate-invoice-number') }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                invoice_number: invoiceNumber,
                                supplier_id: supplierId
                            },
                            success: function(response) {
                                var invoiceField = $('#invoice_number');
                                var feedback = invoiceField.next('.invalid-feedback');

                                if (response.is_duplicate) {
                                    invoiceField.addClass('is-invalid');
                                    if (feedback.length === 0) {
                                        invoiceField.after(
                                            '<span class="invalid-feedback">This invoice number is already used for this supplier.</span>'
                                        );
                                    } else {
                                        feedback.text(
                                            'This invoice number is already used for this supplier.'
                                        );
                                    }
                                } else {
                                    invoiceField.removeClass('is-invalid');
                                    feedback.remove();
                                }
                            },
                            error: function() {
                                console.log('Validation request failed');
                            }
                        });
                    }, 500); // Debounce validation
                }
            }

            // Trigger validation when invoice number or supplier changes
            $('#invoice_number').on('input', validateInvoiceNumber);
            $('#supplier_id').on('change', validateInvoiceNumber);

            // Real-time SAP document validation
            var sapValidationTimeout;

            function validateSapDoc() {
                var sapDoc = $('#sap_doc').val().trim();

                if (sapDoc.length > 0) {
                    clearTimeout(sapValidationTimeout);
                    sapValidationTimeout = setTimeout(function() {
                        $.ajax({
                            url: '{{ route('invoices.validate-sap-doc') }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                sap_doc: sapDoc,
                                invoice_id: null // Create form has no current invoice
                            },
                            success: function(response) {
                                var sapField = $('#sap_doc');
                                var feedback = sapField.next('.invalid-feedback');

                                if (!response.valid) {
                                    sapField.addClass('is-invalid');
                                    if (feedback.length === 0) {
                                        sapField.after(
                                            '<span class="invalid-feedback">' + response.message +
                                            '</span>'
                                        );
                                    } else {
                                        feedback.text(response.message);
                                    }
                                } else {
                                    sapField.removeClass('is-invalid');
                                    feedback.remove();
                                }
                            },
                            error: function() {
                                console.log('SAP validation request failed');
                            }
                        });
                    }, 500); // Debounce validation
                } else {
                    // Clear validation if field is empty
                    $('#sap_doc').removeClass('is-invalid').next('.invalid-feedback').remove();
                }
            }

            // Trigger SAP validation when sap_doc changes
            $('#sap_doc').on('input', validateSapDoc);

            // Payment date validation
            $('#receive_date').on('change', function() {
                var receiveDate = $(this).val();
                if (receiveDate && $('#payment_date').val()) {
                    if ($('#payment_date').val() < receiveDate) {
                        $('#payment_date').val('');
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Payment date cannot be earlier than receive date.');
                        }
                    }
                }
            });

            $('#payment_date').on('change', function() {
                var paymentDate = $(this).val();
                var receiveDate = $('#receive_date').val();
                if (paymentDate && receiveDate && paymentDate < receiveDate) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Payment date cannot be earlier than receive date.');
                    }
                    $(this).val('');
                }
            });

            // Format existing amount value on page load
            @if (old('amount'))
                var oldAmount = '{{ old('amount') }}';
                if (oldAmount && !isNaN(oldAmount)) {
                    formatNumber(document.getElementById('amount_display')); // Format the display field
                    document.getElementById('amount').value = oldAmount; // Populate the hidden field
                }
            @endif

            // Handle cur_loc select enabling/disabling based on user role
            @if (!auth()->user()->hasRole(['superadmin', 'admin']))
                // For non-admin users, ensure cur_loc is set to their department location
                var userLocation = '{{ auth()->user()->department_location_code }}';
                if (userLocation && !$('#cur_loc').val()) {
                    $('#cur_loc').val(userLocation);
                }
            @endif

            // Add hidden input for disabled select to ensure form submission works
            if ($('#cur_loc').prop('disabled')) {
                var hiddenInput = $('<input>').attr({
                    type: 'hidden',
                    name: 'cur_loc',
                    value: $('#cur_loc').val()
                });
                $('#cur_loc').after(hiddenInput);
            }

            // ---------- Additional Documents Linking ----------
            var selectedDocs = {};

            function updateSelectedCounter() {
                var count = Object.keys(selectedDocs).length;
                $('#selected-count').text('Selected: ' + count);
                if (count > 0) {
                    $('#selected-docs-wrapper').show();
                } else {
                    $('#selected-docs-wrapper').hide();
                }
            }

            function renderSelectedTable() {
                var tbody = $('#selected-docs-table tbody');
                tbody.empty();
                Object.values(selectedDocs).forEach(function(doc) {
                    var row = '<tr>' +
                        '<td>' + doc.document_number + '</td>' +
                        '<td>' + (doc.type_name || '-') + '</td>' +
                        '<td>' + (doc.document_date || '-') + '</td>' +
                        '<td>' + (doc.po_no || '-') + '</td>' +
                        '<td><span class="badge badge-secondary">' + (doc.cur_loc || '-') + '</span></td>' +
                        '<td><button type="button" class="btn btn-xs btn-danger remove-doc" data-id="' + doc.id +
                        '"><i class="fas fa-times"></i></button></td>' +
                        '</tr>' +
                        '<input type="hidden" name="additional_document_ids[]" value="' + doc.id + '">';
                    tbody.append(row);
                });
                updateSelectedCounter();
            }

            function renderResultsTable(docs) {
                var tbody = $('#additional-docs-table tbody');
                tbody.empty();
                docs.forEach(function(doc) {
                    var checked = selectedDocs[doc.id] ? 'checked' : '';

                    // Build linked invoices display
                    var linkedInvoicesHtml = '';
                    if (doc.linked_invoices_count > 0) {
                        var badgeClass = doc.linked_invoices_count > 1 ? 'badge-warning' : 'badge-info';
                        var tooltipText = 'Linked to: ' + doc.linked_invoices_list.join(', ');
                        linkedInvoicesHtml = '<span class="badge linked-invoices-badge ' + badgeClass +
                            '" data-toggle="tooltip" title="' + tooltipText + '">' +
                            doc.linked_invoices_count + ' invoice' + (doc.linked_invoices_count > 1 ? 's' : '') +
                            '</span>';
                    } else {
                        linkedInvoicesHtml = '<span class="text-muted">None</span>';
                    }

                    // Build location badge with color coding
                    var locationBadgeClass = 'badge-secondary';
                    var locationTooltip = '';

                    if (doc.is_in_user_department) {
                        locationBadgeClass = 'badge-success';
                        locationTooltip = 'Document is in your department';
                    } else if (doc.cur_loc && doc.cur_loc !== '-') {
                        locationBadgeClass = 'badge-danger';
                        locationTooltip = 'Document is in another department: ' + doc.cur_loc;
                    }

                    var locationHtml = '<span class="badge ' + locationBadgeClass +
                        '" data-toggle="tooltip" title="' + locationTooltip + '">' +
                        (doc.cur_loc || '-') + '</span>';

                    var row = '<tr data-id="' + doc.id + '">' +
                        '<td><input type="checkbox" class="doc-checkbox" data-id="' + doc.id + '" ' + checked +
                        '></td>' +
                        '<td>' + doc.document_number + '</td>' +
                        '<td>' + (doc.type_name || '-') + '</td>' +
                        '<td>' + (doc.document_date || '-') + '</td>' +
                        '<td>' + (doc.po_no || '-') + '</td>' +
                        '<td>' + locationHtml + '</td>' +
                        '<td>' + (doc.remarks || '') + '</td>' +
                        '<td>' + linkedInvoicesHtml + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });

                // Initialize tooltips for the new badges
                $('[data-toggle="tooltip"]').tooltip();

                $('#additional-docs-table-wrapper').toggle(docs.length > 0);
                $('#additional-docs-empty').toggle(docs.length === 0);
                $('#additional-docs-card').show();

                // Show refresh button when we have results
                $('#refresh-docs-btn').toggle(docs.length > 0);
            }

            function searchAdditionalDocuments() {
                var po = $('#po_no').val().trim();
                if (!po) {
                    $('#additional-docs-card').hide();
                    return;
                }
                $('#additional-docs-card').show();
                $('#additional-docs-loading').show();
                $('#additional-docs-table-wrapper').hide();
                $('#additional-docs-empty').hide();

                $.ajax({
                    url: '{{ route('invoices.search-additional-documents') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        po_no: po,
                        current_invoice_id: null // Create form has no current invoice
                    },
                    success: function(resp) {
                        $('#additional-docs-loading').hide();
                        if (resp.success) {
                            renderResultsTable(resp.documents || []);
                            renderSelectedTable();
                            if ((resp.documents || []).length === 0 && typeof toastr !== 'undefined') {
                                toastr.info('No additional documents found for this PO.');
                            }
                        } else {
                            if (typeof toastr !== 'undefined') toastr.error('Search failed');
                        }
                    },
                    error: function() {
                        $('#additional-docs-loading').hide();
                        if (typeof toastr !== 'undefined') toastr.error(
                            'Failed to search additional documents');
                    }
                });
            }

            // Handle blur on PO number
            $('#po_no').on('blur', searchAdditionalDocuments);

            // ENHANCEMENT: PO Search Button - Manual search trigger
            $('#search-docs-btn').on('click', function() {
                var po = $('#po_no').val().trim();
                if (!po) {
                    toastr.warning('Please enter a PO number first.');
                    $('#po_no').focus();
                    return;
                }
                searchAdditionalDocuments();
                toastr.info('Searching for documents with PO: ' + po);
            });

            // Allow Enter key to trigger search without leaving field
            $('#po_no').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    var po = $(this).val().trim();
                    if (po) {
                        searchAdditionalDocuments();
                    }
                }
            });

            // ENHANCEMENT: Update currency prefix when currency changes
            $('#currency').on('change', function() {
                var selectedCurrency = $(this).val();
                $('#currency-prefix').text(selectedCurrency);
            });

            // ENHANCEMENT: Smart Field Dependencies - Auto-populate Invoice Project based on Current Location
            $('#cur_loc').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var locationText = selectedOption.text();

                // Extract project code from location text (e.g., "000HACC - Accounting (000H)" -> "000H")
                var projectMatch = locationText.match(/\(([^)]+)\)$/);
                if (projectMatch && projectMatch[1]) {
                    var projectCode = projectMatch[1];

                    // Auto-select invoice project if not already selected
                    if (!$('#invoice_project').val()) {
                        $('#invoice_project').val(projectCode);
                        toastr.info('Invoice Project auto-set to ' + projectCode + ' based on selected location');
                    }
                }
            });

            // ENHANCEMENT: Initialize currency prefix on page load
            var initialCurrency = $('#currency').val();
            if (initialCurrency) {
                $('#currency-prefix').text(initialCurrency);
            }

            // Row checkbox toggle
            $(document).on('change', '.doc-checkbox', function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                if (this.checked) {
                    // Check if document is already linked to other invoices
                    var linkedInvoicesCell = row.find('td').eq(7); // Linked Invoices column
                    var linkedBadge = linkedInvoicesCell.find('.badge');
                    if (linkedBadge.length > 0 && !linkedBadge.hasClass('badge-success')) {
                        var count = parseInt(linkedBadge.text().match(/\d+/)[0]);
                        if (count > 0 && typeof toastr !== 'undefined') {
                            toastr.warning('This document is already linked to ' + count + ' other invoice(s).');
                        }
                    }

                    var data = {
                        id: id,
                        document_number: row.find('td').eq(1).text(),
                        type_name: row.find('td').eq(2).text(),
                        document_date: row.find('td').eq(3).text(),
                        po_no: row.find('td').eq(4).text(),
                        cur_loc: row.find('td').eq(5).text().trim(),
                        remarks: row.find('td').eq(6).text()
                    };
                    selectedDocs[id] = data;
                } else {
                    delete selectedDocs[id];
                }
                renderSelectedTable();
            });

            // Select all
            $(document).on('change', '#select-all-docs', function() {
                var checked = this.checked;
                $('#additional-docs-table tbody .doc-checkbox').each(function() {
                    $(this).prop('checked', checked).trigger('change');
                });
            });

            // Remove from selected list
            $(document).on('click', '.remove-doc', function() {
                var id = $(this).data('id');
                delete selectedDocs[id];
                $('#additional-docs-table tbody .doc-checkbox[data-id="' + id + '"]').prop('checked', false);
                renderSelectedTable();
            });

            // Refresh additional documents table
            $(document).on('click', '#refresh-docs-btn', function() {
                var po = $('#po_no').val().trim();
                if (po) {
                    // Clear current selections before refreshing
                    selectedDocs = {};
                    renderSelectedTable();

                    // Re-run the search
                    searchAdditionalDocuments();

                    if (typeof toastr !== 'undefined') {
                        toastr.info('Refreshing additional documents...');
                    }
                }
            });

            // ---------- On-the-fly Additional Document Creation ----------

            // Initialize modal functionality (feature working successfully)

            // Open create document modal
            $(document).on('click', '#create-doc-btn', function() {
                var modal = $('#create-doc-modal');
                var form = $('#create-doc-form');

                // Pre-fill PO number if available
                var po = $('#po_no').val().trim();
                if (po) {
                    $('#doc_po_no').val(po);
                }

                // Reset form
                if (form[0] && typeof form[0].reset === 'function') {
                    form[0].reset();
                    // Re-fill PO number after reset
                    if (po) {
                        $('#doc_po_no').val(po);
                    }
                }

                // Show modal
                modal.modal('show');
            });

            // Handle form submission (use more flexible selector)
            $(document).on('submit', '#create-doc-form, #create-doc-modal form', function(e) {
                e.preventDefault();

                var submitBtn = $('#create-doc-submit');
                var originalText = submitBtn.html();

                // Disable submit button and show loading
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

                // Get form data
                var formData = {
                    _token: '{{ csrf_token() }}',
                    document_type_id: $('#doc_type_id').val(),
                    document_number: $('#doc_number').val(),
                    document_date: $('#doc_date').val(),
                    document_receive_date: $('#doc_receive_date').val(),
                    cur_loc: $('#doc_cur_loc').val(),
                    po_no: $('#doc_po_no').val(),
                    project: $('#doc_project').val()
                };

                // Validate required fields
                if (!formData.document_type_id || !formData.document_number || !formData.cur_loc) {
                    toastr.error('Please fill in all required fields.');
                    submitBtn.prop('disabled', false).html(originalText);
                    return false;
                }

                $.ajax({
                    url: '{{ route('additional-documents.on-the-fly') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            toastr.success(response.message);

                            // Close modal
                            $('#create-doc-modal').modal('hide');

                            // Auto-select the newly created document
                            var newDoc = response.document;
                            selectedDocs[newDoc.id] = {
                                id: newDoc.id,
                                document_number: newDoc.document_number,
                                type_name: newDoc.document_type,
                                document_date: newDoc.document_date || '-',
                                po_no: newDoc.po_no || '-',
                                cur_loc: newDoc.cur_loc,
                                remarks: '-'
                            };

                            // Refresh the additional documents table to show the new document
                            searchAdditionalDocuments();

                            // Update selected count
                            renderSelectedTable();

                            // Show info message about auto-attachment
                            toastr.info(
                                'The newly created document has been automatically selected and will be attached to this invoice.'
                            );

                        } else {
                            toastr.error(response.message || 'Failed to create document.');
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Failed to create document.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        // Re-enable submit button
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // ---------- End On-the-fly Additional Document Creation ----------
            // ---------- End Additional Documents Linking ----------

            // Ensure receive_project is properly set and readonly
            var userProject = '{{ auth()->user()->project ?? '' }}';
            if (userProject) {
                $('#receive_project').val(userProject);
            }

            // Add form submission validation
            $('form').on('submit', function(e) {
                // Ensure receive_project has a value
                if (!$('#receive_project').val()) {
                    e.preventDefault();
                    toastr.error(
                        'Receive Project is required and should be automatically set to your department project.'
                    );
                    return false;
                }
            });

            // ENHANCEMENT: Auto-save Draft Feature
            var DRAFT_KEY = 'invoice_create_draft';
            var AUTO_SAVE_INTERVAL = 30000; // 30 seconds

            // Function to save current form data to localStorage
            function saveDraft() {
                var formData = {
                    supplier_id: $('#supplier_id').val(),
                    invoice_number: $('#invoice_number').val(),
                    invoice_date: $('#invoice_date').val(),
                    receive_date: $('#receive_date').val(),
                    po_no: $('#po_no').val(),
                    type_id: $('#type_id').val(),
                    currency: $('#currency').val(),
                    amount: $('#amount').val(),
                    invoice_project: $('#invoice_project').val(),
                    payment_project: $('#payment_project').val(),
                    faktur_no: $('#faktur_no').val(),
                    sap_doc: $('#sap_doc').val(),
                    remarks: $('#remarks').val(),
                    additional_document_ids: Object.keys(selectedDocs),
                    timestamp: new Date().toISOString()
                };

                // Only save if at least one field has data
                var hasData = Object.values(formData).some(function(val) {
                    return val && val.length > 0;
                });

                if (hasData) {
                    localStorage.setItem(DRAFT_KEY, JSON.stringify(formData));
                    console.log('Draft auto-saved at ' + new Date().toLocaleTimeString());
                }
            }

            // Function to restore draft from localStorage
            function restoreDraft() {
                var draftJson = localStorage.getItem(DRAFT_KEY);
                if (!draftJson) return;

                try {
                    var draft = JSON.parse(draftJson);
                    var draftDate = new Date(draft.timestamp);
                    var minutesAgo = Math.round((new Date() - draftDate) / 60000);

                    // Ask user if they want to restore
                    Swal.fire({
                        title: 'Draft Found',
                        html: 'Found an unsaved invoice draft from ' + minutesAgo +
                            ' minutes ago.<br>Do you want to restore it?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Restore Draft',
                        cancelButtonText: 'No, Start Fresh',
                        confirmButtonColor: '#007bff',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Restore form fields
                            if (draft.supplier_id) $('#supplier_id').val(draft.supplier_id).trigger('change');
                            if (draft.invoice_number) $('#invoice_number').val(draft.invoice_number);
                            if (draft.invoice_date) $('#invoice_date').val(draft.invoice_date);
                            if (draft.receive_date) $('#receive_date').val(draft.receive_date);
                            if (draft.po_no) $('#po_no').val(draft.po_no);
                            if (draft.type_id) $('#type_id').val(draft.type_id);
                            if (draft.currency) {
                                $('#currency').val(draft.currency).trigger('change');
                            }
                            if (draft.amount) {
                                $('#amount').val(draft.amount);
                                $('#amount_display').val(draft.amount);
                                formatNumber(document.getElementById('amount_display'));
                            }
                            if (draft.invoice_project) $('#invoice_project').val(draft.invoice_project);
                            if (draft.payment_project) $('#payment_project').val(draft.payment_project);
                            if (draft.faktur_no) $('#faktur_no').val(draft.faktur_no);
                            if (draft.sap_doc) $('#sap_doc').val(draft.sap_doc);
                            if (draft.remarks) $('#remarks').val(draft.remarks);

                            toastr.success('Draft restored successfully!');

                            // If PO number exists, trigger search for additional documents
                            if (draft.po_no) {
                                setTimeout(function() {
                                    searchAdditionalDocuments();
                                }, 500);
                            }
                        } else {
                            // User chose to start fresh, clear the draft
                            localStorage.removeItem(DRAFT_KEY);
                            toastr.info('Starting with a fresh form');
                        }
                    });
                } catch (error) {
                    console.error('Error restoring draft:', error);
                    localStorage.removeItem(DRAFT_KEY);
                }
            }

            // Auto-save every 30 seconds
            var autoSaveTimer = setInterval(saveDraft, AUTO_SAVE_INTERVAL);

            // Save draft when user makes changes (debounced)
            var saveTimeout;
            $('form :input').on('change input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(saveDraft, 2000); // Save 2 seconds after last change
            });

            // Clear draft on successful submission
            var originalAjaxSuccess = submitFormWithAjax;

            // Restore draft on page load (after a short delay to let everything initialize)
            setTimeout(restoreDraft, 1000);

            // Clear draft when form is successfully submitted
            $(document).on('invoice-created-success', function() {
                localStorage.removeItem(DRAFT_KEY);
                clearInterval(autoSaveTimer);
                console.log('Draft cleared after successful submission');
            });

            // ENHANCEMENT: Manual save draft button (optional - shown in console for now)
            console.log('💾 Auto-save is enabled. Your work is automatically saved every 30 seconds.');
            console.log('💡 To manually clear draft: localStorage.removeItem("' + DRAFT_KEY + '")');
        }

        // Start initialization when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeInvoiceForm);
        } else {
            initializeInvoiceForm();
        }
    </script>
@endsection
