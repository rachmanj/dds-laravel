@extends('layouts.main')

@section('title_page')
    Create Distribution
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('distributions.index') }}">Distributions</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Create New Distribution</h3>
                            <div class="card-tools">
                                <a href="{{ route('distributions.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="distributionForm" action="{{ route('distributions.store') }}" method="POST">
                                @csrf

                                <!-- Distribution Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type_id">Distribution Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" id="type_id" name="type_id" required>
                                                <option value="">Select Distribution Type</option>
                                                @foreach ($distributionTypes as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}
                                                        ({{ $type->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="destination_department_id">Destination Department <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" id="destination_department_id"
                                                name="destination_department_id" required>
                                                <option value="">Select Destination Department</option>
                                                @foreach ($departments as $dept)
                                                    @if ($dept->id !== auth()->user()->department->id)
                                                        <option value="{{ $dept->id }}">{{ $dept->name }}
                                                            ({{ $dept->location_code }})
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="document_type">Document Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" id="document_type" name="document_type" required>
                                                <option value="">Select Document Type</option>
                                                <option value="invoice">Invoice</option>
                                                <option value="additional_document">Additional Document</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                                placeholder="Optional notes about this distribution"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Selection -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h5>Document Selection</h5>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Note:</strong> You can only distribute documents that are currently in
                                            your
                                            department location ({{ auth()->user()->department->location_code }}).
                                        </div>
                                    </div>
                                </div>

                                <!-- Invoice Selection -->
                                <div id="invoice-selection" style="display: none;">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title">Select Invoices</h6>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    id="selectAllInvoices">
                                                    Select All
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    id="deselectAllInvoices">
                                                    Deselect All
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th width="50">
                                                                <input type="checkbox" id="invoice-select-all">
                                                            </th>
                                                            <th>Invoice Number</th>
                                                            <th>Supplier</th>
                                                            <th>PO Number</th>
                                                            <th>Amount</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($invoices as $invoice)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" name="document_ids[]"
                                                                        value="{{ $invoice->id }}"
                                                                        class="invoice-checkbox" data-type="invoice">
                                                                </td>
                                                                <td>{{ $invoice->invoice_number }}</td>
                                                                <td>{{ $invoice->supplier->name ?? 'N/A' }}</td>
                                                                <td>{{ $invoice->po_no ?? 'N/A' }}</td>
                                                                <td>{{ $invoice->formatted_amount }}</td>
                                                                <td>
                                                                    <span class="badge {{ $invoice->status_badge_class }}">
                                                                        {{ ucfirst($invoice->status) }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Document Selection -->
                                <div id="additional-document-selection" style="display: none;">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title">Select Additional Documents</h6>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    id="selectAllAdditionalDocs">
                                                    Select All
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    id="deselectAllAdditionalDocs">
                                                    Deselect All
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th width="50">
                                                                <input type="checkbox" id="additional-doc-select-all">
                                                            </th>
                                                            <th>Document Number</th>
                                                            <th>Type</th>
                                                            <th>PO Number</th>
                                                            {{-- <th>Project</th> --}}
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($additionalDocuments as $doc)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" name="document_ids[]"
                                                                        value="{{ $doc->id }}"
                                                                        class="additional-doc-checkbox"
                                                                        data-type="additional_document">
                                                                </td>
                                                                <td>{{ $doc->document_number }}</td>
                                                                <td>{{ $doc->type->type_name ?? 'N/A' }}</td>
                                                                <td>{{ $doc->po_no ?? 'N/A' }}</td>
                                                                {{-- <td>{{ $doc->project ?? 'N/A' }}</td> --}}
                                                                <td>
                                                                    <span
                                                                        class="badge badge-{{ $doc->status === 'open' ? 'success' : 'secondary' }}">
                                                                        {{ ucfirst($doc->status) }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Distribution Preview -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="alert alert-warning" id="distribution-preview"
                                            style="display: none;">
                                            <h6><i class="fas fa-eye"></i> Distribution Preview</h6>
                                            <div id="preview-content"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="fas fa-save"></i> Create Distribution
                                        </button>
                                        <a href="{{ route('distributions.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </section>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/css/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            console.log('=== DISTRIBUTION CREATE PAGE LOADED ===');
            console.log('Available distribution types:', {{ $distributionTypes->count() }});
            console.log('Available departments:', {{ $departments->count() }});
            console.log('Available invoices:', {{ $invoices->count() }});
            console.log('Available additional documents:', {{ $additionalDocuments->count() }});
            console.log('Current user department:', '{{ auth()->user()->department->location_code ?? 'None' }}');

            // Initialize Select2
            $('#type_id, #destination_department_id').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option'
            });

            // Document type change handler
            $('#document_type').change(function() {
                var documentType = $(this).val();
                console.log('Document type changed to:', documentType);

                // Hide all selection sections
                $('#invoice-selection, #additional-document-selection').hide();

                // Show relevant selection section
                if (documentType === 'invoice') {
                    console.log('Showing invoice selection');
                    $('#invoice-selection').show();
                } else if (documentType === 'additional_document') {
                    console.log('Showing additional document selection');
                    $('#additional-document-selection').show();
                } else {
                    console.log('No document type selected, hiding all sections');
                }

                // Clear all checkboxes
                $('input[type="checkbox"]').prop('checked', false);
                console.log('Cleared all checkboxes');

                // Hide preview
                $('#distribution-preview').hide();
            });

            // Select all handlers
            $('#selectAllInvoices').click(function() {
                $('.invoice-checkbox').prop('checked', true);
                updatePreview();
            });

            $('#deselectAllInvoices').click(function() {
                $('.invoice-checkbox').prop('checked', false);
                updatePreview();
            });

            $('#selectAllAdditionalDocs').click(function() {
                $('.additional-doc-checkbox').prop('checked', true);
                updatePreview();
            });

            $('#deselectAllAdditionalDocs').click(function() {
                $('.additional-doc-checkbox').prop('checked', false);
                updatePreview();
            });

            // Checkbox change handlers
            $('.invoice-checkbox, .additional-doc-checkbox').change(function() {
                console.log('Checkbox changed:', this.name, this.value, this.checked);
                updatePreview();
            });

            // Update preview
            function updatePreview() {
                var selectedDocs = $('input[name="document_ids[]"]:checked');
                var documentType = $('#document_type').val();

                console.log('Updating preview - Selected docs:', selectedDocs.length, 'Document type:',
                    documentType);

                if (selectedDocs.length > 0 && documentType) {
                    var previewHtml = '<strong>Distribution Summary:</strong><br>';
                    previewHtml += 'Type: ' + $('#type_id option:selected').text() + '<br>';
                    previewHtml += 'Destination: ' + $('#destination_department_id option:selected').text() +
                        '<br>';
                    previewHtml += 'Documents: ' + selectedDocs.length + ' ' + documentType.replace('_', ' ') +
                        '(s) selected';

                    $('#preview-content').html(previewHtml);
                    $('#distribution-preview').show();
                    console.log('Preview updated and shown');
                } else {
                    $('#distribution-preview').hide();
                    console.log('Preview hidden - no documents selected or no document type');
                }
            }

            // Form submission
            $('#distributionForm').submit(function(e) {
                e.preventDefault();

                // Debug: Log form data before submission
                console.log('=== DISTRIBUTION FORM DEBUG ===');
                console.log('Form action:', $(this).attr('action'));
                console.log('CSRF token:', $('input[name="_token"]').val());

                // Log all form fields
                var formData = $(this).serializeArray();
                console.log('Form data (serializeArray):', formData);

                // Log individual field values
                console.log('type_id:', $('#type_id').val());
                console.log('destination_department_id:', $('#destination_department_id').val());
                console.log('document_type:', $('#document_type').val());
                console.log('notes:', $('#notes').val());

                // Log selected documents
                var selectedDocs = $('input[name="document_ids[]"]:checked');
                console.log('Selected documents count:', selectedDocs.length);
                console.log('Selected document IDs:', selectedDocs.map(function() {
                    return this.value;
                }).get());

                // Log document type visibility
                console.log('Invoice selection visible:', $('#invoice-selection').is(':visible'));
                console.log('Additional doc selection visible:', $('#additional-document-selection').is(
                    ':visible'));

                if (selectedDocs.length === 0) {
                    console.error('No documents selected!');
                    toastr.error('Please select at least one document to distribute');
                    return false;
                }

                // Validate required fields
                var requiredFields = ['type_id', 'destination_department_id', 'document_type'];
                var missingFields = [];

                requiredFields.forEach(function(field) {
                    var value = $('#' + field).val();
                    console.log('Field ' + field + ':', value);
                    if (!value) {
                        missingFields.push(field);
                    }
                });

                if (missingFields.length > 0) {
                    console.error('Missing required fields:', missingFields);
                    toastr.error('Please fill in all required fields: ' + missingFields.join(', '));
                    return false;
                }

                // Log final form data being sent
                var finalFormData = $(this).serialize();
                console.log('Final form data (serialize):', finalFormData);
                console.log('=== END DEBUG ===');

                // Disable submit button
                $('#submitBtn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Creating...');

                // Submit form
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: finalFormData,
                    beforeSend: function(xhr) {
                        console.log('AJAX request starting...');
                        console.log('Request headers:', xhr.getAllResponseHeaders());
                    },
                    success: function(response) {
                        console.log('AJAX success response:', response);
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                window.location.href = response.distribution ?
                                    '/distributions/' + response.distribution.id :
                                    '{{ route('distributions.index') }}';
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Failed to create distribution');
                            $('#submitBtn').prop('disabled', false).html(
                                '<i class="fas fa-save"></i> Create Distribution');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error details:');
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Response text:', xhr.responseText);
                        console.error('Response JSON:', xhr.responseJSON);
                        console.error('Status code:', xhr.status);

                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            console.log('Validation errors:', errors);
                            $.each(errors, function(field, messages) {
                                var input = $('#' + field);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(messages[0]);
                            });
                        } else {
                            toastr.error(
                                'Failed to create distribution. Check console for details.');
                        }
                        $('#submitBtn').prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Create Distribution');
                    }
                });
            });

            // Remove validation errors on input
            $('input, select, textarea').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('');
            });
        });
    </script>
@endsection
