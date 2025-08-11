@extends('layouts.main')

@section('title_page')
    Edit Invoice - {{ $invoice->invoice_number }}
@endsection

@section('breadcrumb_title')
    invoices
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Invoice</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Edit Invoice: {{ $invoice->invoice_number }}</h3>
                        </div>
                        <form action="{{ route('invoices.update', $invoice) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_number">Invoice Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('invoice_number') is-invalid @enderror"
                                                id="invoice_number" name="invoice_number"
                                                value="{{ old('invoice_number', $invoice->invoice_number) }}" required>
                                            @error('invoice_number')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="faktur_no">Faktur No</label>
                                            <input type="text"
                                                class="form-control @error('faktur_no') is-invalid @enderror" id="faktur_no"
                                                name="faktur_no" value="{{ old('faktur_no', $invoice->faktur_no) }}">
                                            @error('faktur_no')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_date">Invoice Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('invoice_date') is-invalid @enderror"
                                                id="invoice_date" name="invoice_date"
                                                value="{{ old('invoice_date', $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '') }}"
                                                required>
                                            @error('invoice_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="receive_date">Receive Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('receive_date') is-invalid @enderror"
                                                id="receive_date" name="receive_date"
                                                value="{{ old('receive_date', $invoice->receive_date ? $invoice->receive_date->format('Y-m-d') : '') }}"
                                                required>
                                            @error('receive_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

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
                                                        {{ old('supplier_id', $invoice->supplier_id) == $supplier->id ? 'selected' : '' }}>
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
                                            <label for="type_id">Invoice Type <span class="text-danger">*</span></label>
                                            <select class="form-control @error('type_id') is-invalid @enderror"
                                                id="type_id" name="type_id" required>
                                                <option value="">Select Invoice Type</option>
                                                @foreach ($invoiceTypes as $type)
                                                    <option value="{{ $type->id }}"
                                                        {{ old('type_id', $invoice->type_id) == $type->id ? 'selected' : '' }}>
                                                        {{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('type_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="po_no">PO Number</label>
                                            <input type="text" class="form-control @error('po_no') is-invalid @enderror"
                                                id="po_no" name="po_no" value="{{ old('po_no', $invoice->po_no) }}"
                                                maxlength="30">
                                            @error('po_no')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency">Currency <span class="text-danger">*</span></label>
                                            <select class="form-control @error('currency') is-invalid @enderror"
                                                id="currency" name="currency" required>
                                                <option value="IDR"
                                                    {{ old('currency', $invoice->currency) == 'IDR' ? 'selected' : '' }}>
                                                    IDR</option>
                                                <option value="USD"
                                                    {{ old('currency', $invoice->currency) == 'USD' ? 'selected' : '' }}>
                                                    USD</option>
                                                <option value="EUR"
                                                    {{ old('currency', $invoice->currency) == 'EUR' ? 'selected' : '' }}>
                                                    EUR</option>
                                                <option value="SGD"
                                                    {{ old('currency', $invoice->currency) == 'SGD' ? 'selected' : '' }}>
                                                    SGD</option>
                                            </select>
                                            @error('currency')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="amount">Amount <span class="text-danger">*</span></label>
                                            <input type="text" name="amount_display" id="amount_display"
                                                class="form-control @error('amount') is-invalid @enderror"
                                                value="{{ old('amount', $invoice->amount) }}"
                                                onkeyup="formatNumber(this)" required>
                                            <input type="hidden" name="amount" id="amount"
                                                value="{{ old('amount', $invoice->amount) }}">
                                            @error('amount')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status">Status <span class="text-danger">*</span></label>
                                            <select class="form-control @error('status') is-invalid @enderror"
                                                id="status" name="status" required>
                                                <option value="open"
                                                    {{ old('status', $invoice->status) == 'open' ? 'selected' : '' }}>
                                                    Open</option>
                                                <option value="verify"
                                                    {{ old('status', $invoice->status) == 'verify' ? 'selected' : '' }}>
                                                    Verify</option>
                                                <option value="return"
                                                    {{ old('status', $invoice->status) == 'return' ? 'selected' : '' }}>
                                                    Return</option>
                                                <option value="sap"
                                                    {{ old('status', $invoice->status) == 'sap' ? 'selected' : '' }}>
                                                    SAP</option>
                                                <option value="close"
                                                    {{ old('status', $invoice->status) == 'close' ? 'selected' : '' }}>
                                                    Close</option>
                                                <option value="cancel"
                                                    {{ old('status', $invoice->status) == 'cancel' ? 'selected' : '' }}>
                                                    Cancel</option>
                                            </select>
                                            @error('status')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="receive_project">Receive Project</label>
                                            <select class="form-control @error('receive_project') is-invalid @enderror"
                                                id="receive_project" name="receive_project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('receive_project', $invoice->receive_project) == $project->code ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('receive_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_project">Invoice Project</label>
                                            <select class="form-control @error('invoice_project') is-invalid @enderror"
                                                id="invoice_project" name="invoice_project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('invoice_project', $invoice->invoice_project) == $project->code ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('invoice_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_project">Payment Project</label>
                                            <select class="form-control @error('payment_project') is-invalid @enderror"
                                                id="payment_project" name="payment_project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('payment_project', $invoice->payment_project) == $project->code || ($invoice->payment_project == null && $project->code == '001H') ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('payment_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
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
                                                            {{ old('cur_loc', $invoice->cur_loc) == $dept->location_code ||
                                                            (auth()->user()->department_location_code == $dept->location_code && !old('cur_loc') && $invoice->cur_loc == null)
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
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_date">Payment Date</label>
                                            <input type="date"
                                                class="form-control @error('payment_date') is-invalid @enderror"
                                                id="payment_date" name="payment_date"
                                                value="{{ old('payment_date', $invoice->payment_date ? $invoice->payment_date->format('Y-m-d') : '') }}">
                                            @error('payment_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sap_doc">SAP Document</label>
                                            <input type="text"
                                                class="form-control @error('sap_doc') is-invalid @enderror"
                                                id="sap_doc" name="sap_doc"
                                                value="{{ old('sap_doc', $invoice->sap_doc) }}">
                                            @error('sap_doc')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3">{{ old('remarks', $invoice->remarks) }}</textarea>
                                    @error('remarks')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Invoice
                                </button>
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> View Invoice
                                </a>
                                <a href="{{ route('invoices.index') }}" class="btn btn-info">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
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

            console.log('Invoice edit form loaded, jQuery version:', $.fn.jquery);

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
                    toastr.info('Updating invoice...', 'Please wait');
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
                            toastr.success(response.message || 'Invoice updated successfully.');
                            // Redirect to index page after short delay
                            setTimeout(function() {
                                window.location.href = '{{ route('invoices.index') }}';
                            }, 1500);
                        } else {
                            toastr.error(response.message || 'Failed to update invoice.');
                        }
                    },
                    error: function(xhr) {
                        // Clear the "Updating invoice..." message
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
                            toastr.error('An error occurred while updating the invoice.');
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

            // Set current location from user's department if available
            @if (auth()->user()->department_location_code)
                if (!$('#cur_loc').val()) {
                    $('#cur_loc').val('{{ auth()->user()->department_location_code }}');
                }
            @endif

            // Real-time invoice number validation per supplier (exclude current invoice)
            var validationTimeout;

            function validateInvoiceNumber() {
                var invoiceNumber = $('#invoice_number').val().trim();
                var supplierId = $('#supplier_id').val();
                var currentInvoiceId = {{ $invoice->id }};

                if (invoiceNumber && supplierId) {
                    clearTimeout(validationTimeout);
                    validationTimeout = setTimeout(function() {
                        // First check if session is still valid
                        $.ajax({
                            url: '{{ route('invoices.check-session') }}',
                            type: 'GET',
                            success: function() {
                                // Session is valid, proceed with validation
                                $.ajax({
                                    url: '{{ route('invoices.validate-invoice-number') }}',
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}',
                                        invoice_number: invoiceNumber,
                                        supplier_id: supplierId,
                                        exclude_id: currentInvoiceId
                                    },
                                    success: function(response) {
                                        var invoiceField = $('#invoice_number');
                                        var feedback = invoiceField.next(
                                            '.invalid-feedback');

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
                                    error: function(xhr) {
                                        if (xhr.status === 401 || xhr.status === 419) {
                                            // Session expired, redirect to login
                                            toastr.error(
                                                'Your session has expired. Redirecting to login page...'
                                            );
                                            setTimeout(function() {
                                                window.location.href =
                                                    '{{ route('login') }}';
                                            }, 1500);
                                        } else {
                                            console.log('Validation request failed');
                                        }
                                    }
                                });
                            },
                            error: function(xhr) {
                                if (xhr.status === 401 || xhr.status === 419) {
                                    // Session expired, redirect to login
                                    toastr.error(
                                        'Your session has expired. Redirecting to login page...');
                                    setTimeout(function() {
                                        window.location.href = '{{ route('login') }}';
                                    }, 1500);
                                }
                            }
                        });
                    }, 500); // Debounce validation
                }
            }

            // Trigger validation when invoice number or supplier changes
            $('#invoice_number').on('input', validateInvoiceNumber);
            $('#supplier_id').on('change', validateInvoiceNumber);

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
        }

        // Start initialization when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeInvoiceForm);
        } else {
            initializeInvoiceForm();
        }
    </script>
@endsection
