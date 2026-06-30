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
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Current Documents</h5>
                                        <button type="button" class="btn btn-success btn-sm" id="addDocumentsBtn">
                                            <i class="fas fa-plus"></i> Add Documents
                                        </button>
                                    </div>

                                    <div id="currentDocumentsContainer">
                                        @include('distributions.partials.edit-documents-table', [
                                            'documentsForEdit' => $documentsForEdit,
                                            'documentType' => $distribution->document_type,
                                        ])
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

    <div class="modal fade" id="addDocumentsModal" tabindex="-1" role="dialog" aria-labelledby="addDocumentsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDocumentsModalLabel">
                        <i class="fas fa-plus"></i> Add Documents
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="availableDocumentsLoading" class="text-center py-4" style="display: none;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2 mb-0">Loading available documents...</p>
                    </div>
                    <div id="availableDocumentsEmpty" class="alert alert-info mb-0" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        No additional documents are available to add.
                    </div>
                    <div id="availableDocumentsList" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAllAvailableDocuments">
                                        </th>
                                        <th>Document</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody id="availableDocumentsTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmAddDocumentsBtn" disabled>
                        <i class="fas fa-plus"></i> Add Selected
                    </button>
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
            const documentType = @json($distribution->document_type);
            const availableDocumentsUrl = @json(route('distributions.available-documents', $distribution));
            const attachDocumentsUrl = @json(route('distributions.documents.attach', $distribution));
            const detachDocumentUrlTemplate = @json(route('distributions.documents.detach', [$distribution, '__ID__']));
            let currentDocuments = @json($documentsForEdit);

            $('#type_id, #destination_department_id').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option'
            });

            function escapeHtml(value) {
                return $('<div>').text(value ?? '').html();
            }

            function renderCurrentDocuments(documents) {
                currentDocuments = documents;

                const invoices = documents.invoices || (documentType === 'invoice' ? documents : []);
                const standaloneAdditionalDocuments = documents.standalone_additional_documents ||
                    (documentType === 'additional_document' ? documents : []);
                const hasDocuments = invoices.length > 0 || standaloneAdditionalDocuments.length > 0;

                if (!hasDocuments) {
                    $('#currentDocumentsContainer').html(
                        '<div class="alert alert-warning mb-0" id="noDocumentsAlert">' +
                        '<i class="fas fa-exclamation-triangle"></i> No documents attached to this distribution yet.' +
                        '</div>'
                    );
                    return;
                }

                let rows = '';

                function appendRemoveButton(distributionDocumentId, documentNumber) {
                    const label = 'Remove ' + documentNumber;

                    return '<td><button type="button" class="btn btn-outline-danger btn-sm remove-document-btn px-2" ' +
                        'data-distribution-document-id="' + distributionDocumentId + '" ' +
                        'data-document-number="' + escapeHtml(documentNumber) + '" ' +
                        'title="' + escapeHtml(label) + '" aria-label="' + escapeHtml(label) + '">' +
                        '<i class="fas fa-trash"></i></button></td>';
                }

                if (documentType === 'invoice') {
                    invoices.forEach(function(document) {
                        rows += '<tr data-distribution-document-id="' + document.id + '">' +
                            '<td><strong>' + escapeHtml(document.number) + '</strong></td>' +
                            '<td>Invoice</td>' +
                            '<td>' + escapeHtml(document.details) + '</td>' +
                            appendRemoveButton(document.id, document.number) + '</tr>';

                        (document.additional_documents || []).forEach(function(additionalDocument) {
                            rows += '<tr class="table-secondary" data-distribution-document-id="' +
                                additionalDocument.id + '">' +
                                '<td class="pl-4"><i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>' +
                                '<strong>' + escapeHtml(additionalDocument.number) + '</strong></td>' +
                                '<td>Additional Document</td>' +
                                '<td>' + escapeHtml(additionalDocument.details) + '</td>' +
                                appendRemoveButton(additionalDocument.id, additionalDocument.number) + '</tr>';
                        });
                    });

                    if (standaloneAdditionalDocuments.length > 0) {
                        rows += '<tr class="table-active"><td colspan="4">' +
                            '<strong><i class="fas fa-file-alt"></i> Other Additional Documents</strong>' +
                            '<small class="text-muted ml-2">Not linked to a selected invoice</small></td></tr>';

                        standaloneAdditionalDocuments.forEach(function(document) {
                            rows += '<tr data-distribution-document-id="' + document.id + '">' +
                                '<td><strong>' + escapeHtml(document.number) + '</strong></td>' +
                                '<td>Additional Document</td>' +
                                '<td>' + escapeHtml(document.details) + '</td>' +
                                appendRemoveButton(document.id, document.number) + '</tr>';
                        });
                    }
                } else {
                    standaloneAdditionalDocuments.forEach(function(document) {
                        rows += '<tr data-distribution-document-id="' + document.id + '">' +
                            '<td><strong>' + escapeHtml(document.number) + '</strong></td>' +
                            '<td>Additional Document</td>' +
                            '<td>' + escapeHtml(document.details) + '</td>' +
                            appendRemoveButton(document.id, document.number) + '</tr>';
                    });
                }

                $('#currentDocumentsContainer').html(
                    '<div class="table-responsive">' +
                    '<table class="table table-bordered table-hover" id="currentDocumentsTable">' +
                    '<thead><tr><th>Document</th><th>Type</th><th>Details</th><th width="56">Actions</th></tr></thead>' +
                    '<tbody>' + rows + '</tbody></table></div>'
                );
            }

            function resetAvailableDocumentsModal() {
                $('#availableDocumentsLoading').hide();
                $('#availableDocumentsEmpty').hide();
                $('#availableDocumentsList').hide();
                $('#availableDocumentsTableBody').empty();
                $('#selectAllAvailableDocuments').prop('checked', false);
                $('#confirmAddDocumentsBtn').prop('disabled', true);
            }

            function renderAvailableDocuments(documents) {
                resetAvailableDocumentsModal();

                if (!documents.length) {
                    $('#availableDocumentsEmpty').show();
                    return;
                }

                let rows = '';
                documents.forEach(function(document) {
                    rows += '<tr>' +
                        '<td><input type="checkbox" class="available-document-checkbox" value="' + document.id + '"></td>' +
                        '<td><strong>' + escapeHtml(document.number) + '</strong></td>' +
                        '<td>' + escapeHtml(document.details) + '</td></tr>';
                });

                $('#availableDocumentsTableBody').html(rows);
                $('#availableDocumentsList').show();
            }

            function updateAddSelectedButtonState() {
                const selectedCount = $('.available-document-checkbox:checked').length;
                $('#confirmAddDocumentsBtn').prop('disabled', selectedCount === 0);
            }

            $('#addDocumentsBtn').on('click', function() {
                resetAvailableDocumentsModal();
                $('#addDocumentsModal').modal('show');
                $('#availableDocumentsLoading').show();

                $.get(availableDocumentsUrl)
                    .done(function(response) {
                        if (response.success) {
                            renderAvailableDocuments(response.documents || []);
                        } else {
                            toastr.error(response.message || 'Failed to load available documents');
                            $('#addDocumentsModal').modal('hide');
                        }
                    })
                    .fail(function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to load available documents';
                        toastr.error(message);
                        $('#addDocumentsModal').modal('hide');
                    })
                    .always(function() {
                        $('#availableDocumentsLoading').hide();
                    });
            });

            $(document).on('change', '#selectAllAvailableDocuments', function() {
                $('.available-document-checkbox').prop('checked', $(this).is(':checked'));
                updateAddSelectedButtonState();
            });

            $(document).on('change', '.available-document-checkbox', function() {
                const total = $('.available-document-checkbox').length;
                const checked = $('.available-document-checkbox:checked').length;
                $('#selectAllAvailableDocuments').prop('checked', total > 0 && total === checked);
                updateAddSelectedButtonState();
            });

            $('#confirmAddDocumentsBtn').on('click', function() {
                const documentIds = $('.available-document-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (!documentIds.length) {
                    return;
                }

                const $button = $(this);
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');

                $.ajax({
                    url: attachDocumentsUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        document_ids: documentIds
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            renderCurrentDocuments(response.documents || []);
                            $('#addDocumentsModal').modal('hide');
                        } else {
                            toastr.error(response.message || 'Failed to add documents');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            const firstError = Object.values(xhr.responseJSON.errors)[0]?.[0];
                            toastr.error(firstError || 'Validation failed');
                        } else {
                            toastr.error(xhr.responseJSON?.message || 'Failed to add documents');
                        }
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('<i class="fas fa-plus"></i> Add Selected');
                    }
                });
            });

            $(document).on('click', '.remove-document-btn', function() {
                const distributionDocumentId = $(this).data('distribution-document-id');
                const documentNumber = $(this).data('document-number');
                const detachUrl = detachDocumentUrlTemplate.replace('__ID__', distributionDocumentId);

                if (!confirm('Remove ' + documentNumber + ' from this distribution?')) {
                    return;
                }

                const $button = $(this);
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: detachUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            renderCurrentDocuments(response.documents || []);
                        } else {
                            toastr.error(response.message || 'Failed to remove document');
                            $button.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to remove document');
                        $button.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    }
                });
            });

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
