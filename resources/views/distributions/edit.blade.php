@extends('layouts.main')

@section('title_page', 'Edit Distribution')
@section('breadcrumb_title', 'Edit Distribution')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i>
                        Edit Distribution: {{ $distribution->distribution_number }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('distributions.show', $distribution) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Details
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if ($distribution->status !== 'draft')
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> This distribution cannot be edited because it's no longer in draft
                            status.
                            Current status: <span
                                class="badge {{ $distribution->status_badge_class }}">{{ $distribution->status_display }}</span>
                        </div>
                    @else
                        <form id="distributionEditForm" action="{{ route('distributions.update', $distribution) }}"
                            method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Distribution Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="type_id">Distribution Type <span class="text-danger">*</span></label>
                                        <select class="form-control" id="type_id" name="type_id" required>
                                            <option value="">Select Distribution Type</option>
                                            @foreach ($distributionTypes as $type)
                                                <option value="{{ $type->id }}"
                                                    {{ $distribution->type_id == $type->id ? 'selected' : '' }}>
                                                    {{ $type->name }} ({{ $type->code }})
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
                                                    <option value="{{ $dept->id }}"
                                                        {{ $distribution->destination_department_id == $dept->id ? 'selected' : '' }}>
                                                        {{ $dept->name }} ({{ $dept->location_code }})
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
                                        <label for="document_type">Document Type <span class="text-danger">*</span></label>
                                        <select class="form-control" id="document_type" name="document_type" required
                                            disabled>
                                            <option value="invoice"
                                                {{ $distribution->document_type === 'invoice' ? 'selected' : '' }}>Invoice
                                            </option>
                                            <option value="additional_document"
                                                {{ $distribution->document_type === 'additional_document' ? 'selected' : '' }}>
                                                Additional Document</option>
                                        </select>
                                        <small class="form-text text-muted">Document type cannot be changed after
                                            creation</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="notes">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                            placeholder="Optional notes about this distribution">{{ $distribution->notes }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Current Documents -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5>Current Documents</h5>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Note:</strong> Documents cannot be added or removed after creation. You can
                                        only edit distribution details.
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Document</th>
                                                    <th>Type</th>
                                                    <th>Details</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($distribution->documents as $doc)
                                                    <tr>
                                                        <td>
                                                            <strong>
                                                                @if ($doc->document_type === 'App\Models\Invoice')
                                                                    {{ $doc->document->invoice_number ?? 'N/A' }}
                                                                @else
                                                                    {{ $doc->document->document_number ?? 'N/A' }}
                                                                @endif
                                                            </strong>
                                                        </td>
                                                        <td>
                                                            @if ($doc->document_type === 'App\Models\Invoice')
                                                                Invoice
                                                            @else
                                                                Additional Document
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($doc->document_type === 'App\Models\Invoice')
                                                                Supplier: {{ $doc->document->supplier->name ?? 'N/A' }}<br>
                                                                PO: {{ $doc->document->po_no ?? 'N/A' }}
                                                            @else
                                                                Type: {{ $doc->document->type->name ?? 'N/A' }}<br>
                                                                Project: {{ $doc->document->project ?? 'N/A' }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-secondary">Attached</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary" id="updateBtn">
                                        <i class="fas fa-save"></i> Update Distribution
                                    </button>
                                    <a href="{{ route('distributions.show', $distribution) }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/css/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#type_id, #destination_department_id').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option'
            });

            // Form submission
            $('#distributionEditForm').submit(function(e) {
                e.preventDefault();

                // Disable submit button
                $('#updateBtn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Updating...');

                // Submit form
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                window.location.href =
                                    '{{ route('distributions.show', $distribution) }}';
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Failed to update distribution');
                            $('#updateBtn').prop('disabled', false).html(
                                '<i class="fas fa-save"></i> Update Distribution');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                var input = $('#' + field);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(messages[0]);
                            });
                        } else {
                            toastr.error('Failed to update distribution');
                        }
                        $('#updateBtn').prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Update Distribution');
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
