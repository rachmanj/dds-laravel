@extends('layouts.main')

@section('title_page')
    Invoice Attachments - {{ $invoice->invoice_number }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.attachments.index') }}">Invoices Attachments</a></li>
    <li class="breadcrumb-item active">Attachments</li>
@endsection

@section('content')
    <style>
        .btn-group .btn {
            margin-right: 2px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }
    </style>

    <div class="content">
        <div class="container-fluid">
            <!-- Invoice Information Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice mr-2"></i>
                        Invoice Information
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('invoices.attachments.index') }}" class="btn btn-sm btn-info">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Invoice
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Invoice Number:</strong></td>
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
                                    <td width="40%"><strong>Invoice Type:</strong></td>
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

                    <!-- Project Information Row -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="15%"><strong>Receive Project:</strong></td>
                                    <td>{{ $invoice->receive_project ?: '-' }}</td>
                                    <td width="15%"><strong>Invoice Project:</strong></td>
                                    <td>{{ $invoice->invoice_project ?: '-' }}</td>
                                    <td width="15%"><strong>Payment Project:</strong></td>
                                    <td>{{ $invoice->payment_project ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Remarks:</strong></td>
                                    <td colspan="5">{{ $invoice->remarks ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attachments DataTable Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-paperclip mr-2"></i>
                        Attachments ({{ $invoice->attachments->count() }} files)
                    </h3>
                    <div class="card-tools">
                        @can('inv-attachment-create')
                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                                data-target="#uploadAttachmentModal">
                                <i class="fas fa-upload mr-1"></i> Upload New
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="attachments-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">File Name</th>
                                    <th width="15%">File Type</th>
                                    <th width="10%">Size</th>
                                    <th width="15%">Uploaded By</th>
                                    <th width="15%">Upload Date</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoice->attachments as $index => $attachment)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <i class="{{ $attachment->file_icon }} mr-2"></i>
                                            {{ $attachment->file_name }}
                                            @if ($attachment->description)
                                                <br><small class="text-muted">{{ $attachment->description }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-info">{{ strtoupper($attachment->file_extension) }}</span>
                                        </td>
                                        <td>{{ $attachment->formatted_file_size }}</td>
                                        <td>{{ $attachment->uploader ? $attachment->uploader->name : '-' }}</td>
                                        <td>{{ $attachment->created_at->format('d-M-Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                @can('inv-attachment-view')
                                                    <a href="{{ route('invoices.attachments.download', $attachment) }}"
                                                        class="btn btn-info" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endcan

                                                @if ($attachment->isImage() || $attachment->isPdf())
                                                    <a href="{{ route('invoices.attachments.preview', $attachment) }}"
                                                        class="btn btn-primary" title="Preview" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif

                                                @can('inv-attachment-edit')
                                                    <button type="button" class="btn btn-warning edit-attachment"
                                                        data-id="{{ $attachment->id }}"
                                                        data-description="{{ $attachment->description }}"
                                                        data-update-url="{{ route('invoices.attachments.update', $attachment) }}"
                                                        title="Edit Description">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endcan

                                                @can('inv-attachment-delete')
                                                    <button type="button" class="btn btn-danger delete-attachment"
                                                        data-id="{{ $attachment->id }}"
                                                        data-filename="{{ $attachment->file_name }}"
                                                        data-delete-url="{{ route('invoices.attachments.destroy', $attachment) }}"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <br>No attachments found for this invoice
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Attachment Modal -->
    @can('inv-attachment-create')
        <div class="modal fade" id="uploadAttachmentModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="uploadAttachmentForm" action="{{ route('invoices.attachments.store', $invoice) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Upload New Attachment</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="files">Select Files</label>
                                <input type="file" class="form-control" id="files" name="files[]" multiple required>
                                <small class="form-text text-muted">
                                    Allowed types: PDF, Images (JPG, PNG, GIF, WebP). Max size: 5MB per file. You can select
                                    multiple files.
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="description">Description (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                    placeholder="Enter file description..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload mr-1"></i> Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <!-- Edit Attachment Modal -->
    @can('inv-attachment-edit')
        <div class="modal fade" id="editAttachmentModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="editAttachmentForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Attachment Description</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="edit_description">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"
                                    placeholder="Enter file description..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Test if toastr is loaded
            if (typeof toastr !== 'undefined') {
                console.log('Toastr is loaded successfully');
            } else {
                console.error('Toastr is NOT loaded');
            }

            // Initialize DataTable
            $('#attachments-table').DataTable({
                "pageLength": 25,
                "order": [
                    [5, "desc"]
                ], // Sort by upload date descending
                "language": {
                    "search": "Search attachments:",
                    "lengthMenu": "Show _MENU_ attachments per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ attachments",
                    "emptyTable": "No attachments found"
                }
            });

            // Handle edit attachment
            $('.edit-attachment').click(function() {
                const id = $(this).data('id');
                const description = $(this).data('description');
                const updateUrl = $(this).data('update-url');

                console.log('Edit button clicked:', {
                    id,
                    description,
                    updateUrl
                });

                $('#edit_description').val(description);
                $('#editAttachmentForm').attr('action', updateUrl);

                console.log('Opening edit modal...');
                $('#editAttachmentModal').modal('show');

                // Debug form action
                console.log('Form action set to:', $('#editAttachmentForm').attr('action'));
            });

            // Handle delete attachment
            $('.delete-attachment').click(function() {
                const id = $(this).data('id');
                const filename = $(this).data('filename');
                const deleteUrl = $(this).data('delete-url');

                console.log('Delete button clicked:', {
                    id,
                    filename,
                    deleteUrl
                });

                Swal.fire({
                    title: 'Delete Attachment?',
                    text: `Are you sure you want to delete "${filename}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Delete confirmed, sending AJAX request to:', deleteUrl);
                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                console.log('Delete success:', response);
                                // Use toastr for success notification instead of SweetAlert2
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('Attachment deleted successfully!');
                                } else {
                                    console.error('Toastr not loaded');
                                    alert('Attachment deleted successfully!');
                                }
                                // Reload the page to show updated data
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            },
                            error: function(xhr) {
                                console.log('Delete error:', xhr);
                                let errorMessage = 'Failed to delete attachment.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', errorMessage, 'error');
                            }
                        });
                    }
                });
            });

            // Handle upload form submission
            $('#uploadAttachmentForm').submit(function(e) {
                console.log('Upload form submitted');
                e.preventDefault();

                const formData = new FormData(this);
                console.log('Form action:', $(this).attr('action'));

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Upload success:', response);
                        $('#uploadAttachmentModal').modal('hide');
                        // Use toastr for success notification
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message || 'File uploaded successfully!');
                        } else {
                            console.error('Toastr not loaded');
                            alert(response.message || 'File uploaded successfully!');
                        }
                        // Reload the page to show new attachments
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        console.log('Upload error:', xhr);
                        let errorMessage = 'Upload failed.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        // Use toastr for error notification
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMessage);
                        } else {
                            console.error('Toastr not loaded');
                            alert(errorMessage);
                        }
                    }
                });
            });

            // Handle edit form submission
            $('#editAttachmentForm').submit(function(e) {
                console.log('Edit form submitted');
                e.preventDefault();

                console.log('Edit form action:', $(this).attr('action'));
                console.log('Edit form data:', $(this).serialize());

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'PUT',
                    data: $(this).serialize(),
                    success: function(response) {
                        console.log('Edit success:', response);
                        $('#editAttachmentModal').modal('hide');
                        // Use toastr for success notification
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Attachment updated successfully.');
                        } else {
                            console.error('Toastr not loaded');
                            alert('Attachment updated successfully.');
                        }
                        // Reload the page to show updated data
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        console.log('Edit error:', xhr);
                        let errorMessage = 'Update failed.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        // Use toastr for error notification
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMessage);
                        } else {
                            console.error('Toastr not loaded');
                            alert(errorMessage);
                        }
                    }
                });
            });
        });
    </script>
@endsection
