@extends('layouts.main')

@section('title_page')
    Invoice Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('content')
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <!-- Invoice Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-invoice"></i> Invoice Information
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Invoice Number:</strong></td>
                                            <td>{{ $invoice->invoice_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Faktur No:</strong></td>
                                            <td>{{ $invoice->faktur_no ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Invoice Date:</strong></td>
                                            <td>{{ $invoice->formatted_invoice_date }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Receive Date:</strong></td>
                                            <td>{{ $invoice->formatted_receive_date }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Supplier:</strong></td>
                                            <td>{{ $invoice->supplier ? $invoice->supplier->name : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>PO Number:</strong></td>
                                            <td>{{ $invoice->po_no ?: '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Invoice Type:</strong></td>
                                            <td>{{ $invoice->type ? $invoice->type->type_name : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Currency:</strong></td>
                                            <td>{{ $invoice->currency }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Amount:</strong></td>
                                            <td>{{ $invoice->formatted_amount }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>{!! $invoice->status_badge !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Current Location:</strong></td>
                                            <td>{{ $invoice->cur_loc }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Payment Date:</strong></td>
                                            <td>{{ $invoice->formatted_payment_date }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6><strong>Project Information:</strong></h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Receive Project:</strong><br>
                                            @if ($invoice->receiveProjectInfo)
                                                <span class="badge badge-info">{{ $invoice->receive_project }}</span><br>
                                                <small>{{ $invoice->receiveProjectInfo->name }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Invoice Project:</strong><br>
                                            @if ($invoice->invoiceProjectInfo)
                                                <span class="badge badge-info">{{ $invoice->invoice_project }}</span><br>
                                                <small>{{ $invoice->invoiceProjectInfo->name }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Payment Project:</strong><br>
                                            @if ($invoice->paymentProjectInfo)
                                                <span class="badge badge-info">{{ $invoice->payment_project }}</span><br>
                                                <small>{{ $invoice->paymentProjectInfo->name }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($invoice->remarks)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6><strong>Remarks:</strong></h6>
                                        <p class="text-muted">{{ $invoice->remarks }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6><strong>Additional Information:</strong></h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>SAP Document:</strong> {{ $invoice->sap_doc ?: '-' }}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Created By:</strong>
                                            {{ $invoice->creator ? $invoice->creator->name : '-' }}
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <strong>Created At:</strong>
                                            {{ $invoice->created_at->format('d/m/Y H:i') }}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Updated At:</strong>
                                            {{ $invoice->updated_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Attachments -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-paperclip"></i> Attachments
                                <span class="badge badge-info">{{ $invoice->attachments->count() }}</span>
                            </h3>
                        </div>
                        <div class="card-body">
                            @if ($invoice->attachments->count() > 0)
                                <div class="attachment-list">
                                    @foreach ($invoice->attachments as $attachment)
                                        <div
                                            class="attachment-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                            <div class="attachment-info">
                                                <i class="{{ $attachment->file_icon }} text-primary"></i>
                                                <span class="ml-2">{{ $attachment->file_name }}</span>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $attachment->formatted_file_size }} •
                                                    Uploaded by
                                                    {{ $attachment->uploader ? $attachment->uploader->name : 'Unknown' }}
                                                </small>
                                                @if ($attachment->description)
                                                    <br>
                                                    <small class="text-info">{{ $attachment->description }}</small>
                                                @endif
                                            </div>
                                            <div class="attachment-actions">
                                                <a href="{{ route('invoices.attachments.show', $invoice) }}"
                                                    class="btn btn-sm btn-info" target="_blank" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('invoices.attachments.download', $attachment) }}"
                                                    class="btn btn-sm btn-success" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-warning edit-attachment"
                                                    data-id="{{ $attachment->id }}"
                                                    data-description="{{ $attachment->description }}" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-attachment"
                                                    data-id="{{ $attachment->id }}"
                                                    data-filename="{{ $attachment->file_name }}" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted text-center">No attachments uploaded yet.</p>
                            @endif

                            <!-- Upload Form -->
                            <hr>
                            <h6><strong>Upload New Files:</strong></h6>
                            <form action="{{ route('invoices.attachments.store', $invoice) }}" method="POST"
                                enctype="multipart/form-data" id="uploadForm">
                                @csrf
                                <div class="form-group">
                                    <label for="files">Select Files</label>
                                    <input type="file" class="form-control-file" id="files" name="files[]" multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required>
                                    <small class="form-text text-muted">
                                        Maximum file size: 5MB. Supported formats: PDF, Images (JPG, PNG, GIF, WebP)
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label for="description">Description (Optional)</label>
                                    <input type="text" class="form-control" id="description" name="description"
                                        placeholder="Brief description of the files">
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-upload"></i> Upload Files
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Linked Additional Documents -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-link"></i> Linked Additional Documents
                                <span class="badge badge-info">{{ $invoice->additionalDocuments->count() }}</span>
                            </h3>
                        </div>
                        <div class="card-body">
                            @if ($invoice->additionalDocuments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Document No</th>
                                                <th>Type</th>
                                                <th>Date</th>
                                                <th>PO No</th>
                                                <th>Cur Loc</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($invoice->additionalDocuments as $doc)
                                                <tr>
                                                    <td>{{ $doc->document_number }}</td>
                                                    <td>{{ optional($doc->type)->type_name }}</td>
                                                    <td>{{ optional($doc->document_date)->format('Y-m-d') }}</td>
                                                    <td>{{ $doc->po_no }}</td>
                                                    <td><span class="badge badge-secondary">{{ $doc->cur_loc }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">No additional documents linked.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- modals --}}
    <div class="modal fade" id="editAttachmentModal" tabindex="-1" role="dialog"
        aria-labelledby="editAttachmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAttachmentModalLabel">Edit Attachment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editAttachmentId">
                    <div class="form-group">
                        <label for="editAttachmentDescription">Description</label>
                        <textarea class="form-control" id="editAttachmentDescription" rows="3"
                            placeholder="Add a description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditAttachmentBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // File upload form submission
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // Reload the page to show new attachments
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message);
                            if (response.errors) {
                                response.errors.forEach(function(error) {
                                    toastr.error(error);
                                });
                            }
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                toastr.error(value[0]);
                            });
                        } else {
                            toastr.error('An error occurred while uploading files.');
                        }
                    }
                });
            });

            // Delete attachment
            $('.delete-attachment').on('click', function() {
                var attachmentId = $(this).data('id');
                var filename = $(this).data('filename');

                if (confirm('Are you sure you want to delete "' + filename + '"?')) {
                    $.ajax({
                        url: '/invoices/attachments/' + attachmentId,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // Reload the page to update attachments
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            }
                        },
                        error: function() {
                            toastr.error('An error occurred while deleting the attachment.');
                        }
                    });
                }
            });

            // File input change event
            $('#files').on('change', function() {
                var files = this.files;
                var maxPerFile = 5 * 1024 * 1024; // 5MB
                for (var i = 0; i < files.length; i++) {
                    if (files[i].size > maxPerFile) {
                        alert('Each file must be 5MB or less.');
                        $(this).val('');
                        break;
                    }
                }
            });

            // Edit attachment: open modal
            $(document).on('click', '.edit-attachment', function() {
                var id = $(this).data('id');
                var desc = $(this).data('description') || '';
                $('#editAttachmentId').val(id);
                $('#editAttachmentDescription').val(desc);
                $('#editAttachmentModal').modal('show');
            });

            // Save attachment update via AJAX
            $('#saveEditAttachmentBtn').on('click', function() {
                var id = $('#editAttachmentId').val();
                var desc = $('#editAttachmentDescription').val();
                $.ajax({
                    url: '/invoices/attachments/' + id,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT',
                        description: desc
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || 'Attachment updated');
                            location.reload();
                        } else {
                            toastr.error('Failed to update attachment');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to update attachment');
                    }
                });
            });
        });
    </script>
@endsection
