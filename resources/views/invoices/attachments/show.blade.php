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

            <!-- Upload Files Card -->
            @can('inv-attachment-create')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cloud-upload-alt mr-2"></i>
                            Upload Files
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Dropzone -->
                        <form action="{{ route('invoices.attachments.store', $invoice) }}" class="dropzone"
                            id="invoice-dropzone">
                            @csrf
                            <div class="dz-message">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                                    <h4>Drag files here or click to browse</h4>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        Supported: PDF, JPG, PNG, GIF, WebP (Max 5MB each)
                                    </p>
                                </div>
                            </div>
                        </form>

                        <!-- File Queue (shows selected files before upload) -->
                        <div id="file-queue" class="mt-3" style="display:none;">
                            <h5><i class="fas fa-list"></i> Files Ready to Upload:</h5>
                            <div id="file-list" class="row"></div>
                            <button type="button" class="btn btn-primary btn-lg mt-3" id="upload-all-btn">
                                <i class="fas fa-upload"></i> Upload All Files
                            </button>
                        </div>
                    </div>
                </div>
            @endcan

            <!-- Attachments DataTable Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-paperclip mr-2"></i>
                        Attachments ({{ $invoice->attachments->count() }} files)
                    </h3>
                    <div class="card-tools">
                        <!-- Category Filter -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary category-filter active"
                                data-category="all">
                                All Documents
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary category-filter"
                                data-category="Invoice Copy">
                                Invoice Copy
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success category-filter"
                                data-category="Purchase Order">
                                Purchase Order
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info category-filter"
                                data-category="Supporting Document">
                                Supporting Document
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning category-filter"
                                data-category="Other">
                                Other
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="attachments-table" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">File Name</th>
                                    <th width="12%">File Type</th>
                                    <th width="10%">Size</th>
                                    <th width="15%">Category</th>
                                    <th width="12%">Uploaded By</th>
                                    <th width="12%">Upload Date</th>
                                    <th width="14%">Actions</th>
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
                                        <td>{!! $attachment->category_badge !!}</td>
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
                                                        data-category="{{ $attachment->category }}"
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
                                        <td colspan="8" class="text-center text-muted">
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
                            <div class="form-group">
                                <label for="edit_category">Category</label>
                                <select class="form-control" id="edit_category" name="category">
                                    <option value="">Select Category</option>
                                    <option value="Invoice Copy">Invoice Copy</option>
                                    <option value="Purchase Order">Purchase Order</option>
                                    <option value="Supporting Document">Supporting Document</option>
                                    <option value="Other">Other</option>
                                </select>
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
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
    <!-- Dropzone -->
    <link rel="stylesheet" href="{{ asset('css/dropzone/dropzone.css') }}">

    <style>
        /* Dropzone Styling */
        .dropzone {
            border: 2px dashed #007bff;
            border-radius: 10px;
            background: #f8f9fa;
            min-height: 200px;
            padding: 20px;
        }

        .dropzone.dz-drag-hover {
            border-color: #28a745;
            background: #d4edda;
        }

        .dropzone .dz-message {
            text-align: center;
            margin: 0;
        }

        /* File Preview Cards */
        .file-preview-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .file-preview-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .file-preview {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .file-preview img {
            max-width: 100%;
            max-height: 100px;
            border-radius: 5px;
        }

        /* Category Filter Buttons */
        .category-filter.active {
            background-color: #007bff;
            color: white;
        }

        /* Progress Bar */
        .progress {
            height: 20px;
            border-radius: 10px;
        }

        .progress-bar {
            border-radius: 10px;
        }

        /* File Queue */
        #file-queue {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #dee2e6;
        }

        /* Remove Button */
        .remove-file-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 12px;
            cursor: pointer;
        }

        .remove-file-btn:hover {
            background: #c82333;
        }
    </style>
@endsection

@section('scripts')
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <!-- Dropzone -->
    <script src="{{ asset('js/dropzone/dropzone-min.js') }}"></script>

    <script>
        // Completely disable DataTables auto-initialization
        if (typeof $.fn.dataTable !== 'undefined') {
            $.fn.dataTable = function() {
                return this;
            };
        }

        $(document).ready(function() {
            // Test if toastr is loaded
            if (typeof toastr !== 'undefined') {
                console.log('Toastr is loaded successfully');
            } else {
                console.error('Toastr is NOT loaded');
            }

            console.log('Page ready - attachments: {{ $invoice->attachments->count() }}');

            // Initialize Dropzone
            Dropzone.autoDiscover = false;
            console.log('Initializing Dropzone...');

            var myDropzone = new Dropzone("#invoice-dropzone", {
                url: "{{ route('invoices.attachments.store', $invoice) }}",
                paramName: "files",
                uploadMultiple: false,
                parallelUploads: 1,
                maxFilesize: 5, // MB
                acceptedFiles: ".pdf,.jpg,.jpeg,.png,.gif,.webp",
                addRemoveLinks: false,
                autoProcessQueue: false,
                dictDefaultMessage: "",
                dictRemoveFile: "Remove",
                dictCancelUpload: "Cancel",
                dictUploadCanceled: "Upload canceled.",
                dictInvalidFileType: "You can't upload files of this type.",
                dictFileTooBig: "File is too big (5MB). Max filesize: 5MB.",
                dictResponseError: "Server responded with error code.",
                dictCancelUploadConfirmation: "Are you sure you want to cancel this upload?",
                dictMaxFilesExceeded: "You can not upload any more files.",

                init: function() {
                    var dropzone = this;
                    var fileQueue = [];

                    // When files are added
                    dropzone.on("addedfile", function(file) {
                        console.log("File added:", file.name);

                        // Create file preview card
                        var fileCard = createFilePreviewCard(file);
                        $('#file-list').append(fileCard);
                        $('#file-queue').show();

                        // Store file data
                        fileQueue.push({
                            file: file,
                            category: 'Invoice Copy', // Default category
                            description: ''
                        });
                    });

                    // When files are removed
                    dropzone.on("removedfile", function(file) {
                        console.log("File removed:", file.name);
                        $('.file-preview-card[data-filename="' + file.name + '"]').remove();

                        // Remove from queue
                        fileQueue = fileQueue.filter(item => item.file !== file);

                        if (fileQueue.length === 0) {
                            $('#file-queue').hide();
                        }
                    });

                    // Handle upload all button
                    $('#upload-all-btn').click(function() {
                        if (fileQueue.length === 0) {
                            toastr.warning('No files to upload');
                            return;
                        }

                        uploadAllFiles(fileQueue, dropzone);
                    });

                    console.log('Dropzone initialized successfully!');
                }
            });

            console.log('Dropzone instance created:', myDropzone);

            // Create file preview card
            function createFilePreviewCard(file) {
                var fileIcon = getFileIcon(file.type);
                var fileSize = formatFileSize(file.size);

                var cardHtml = `
                    <div class="col-md-4 mb-3">
                        <div class="card file-preview-card" data-filename="${file.name}">
                            <div class="card-body p-2">
                                <div class="file-preview mb-2">
                                    ${fileIcon}
                                </div>
                                
                                <div class="file-info mb-2">
                                    <strong title="${file.name}">${truncateFilename(file.name)}</strong>
                                    <small class="text-muted d-block">${fileSize}</small>
                                </div>
                                
                                <div class="form-group mb-2">
                                    <select class="form-control form-control-sm file-category" data-filename="${file.name}">
                                        <option value="Invoice Copy">Invoice Copy</option>
                                        <option value="Purchase Order">Purchase Order</option>
                                        <option value="Supporting Document">Supporting Document</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mb-2">
                                    <textarea class="form-control form-control-sm file-description" 
                                        placeholder="Description (optional)" 
                                        data-filename="${file.name}"></textarea>
                                </div>
                                
                                <div class="progress mb-2" style="display: none;">
                                    <div class="progress-bar progress-bar-striped" style="width: 0%">0%</div>
                                </div>
                                
                                <button class="btn btn-sm btn-danger btn-block remove-file-btn" 
                                    data-filename="${file.name}">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                return cardHtml;
            }

            // Get file icon based on type
            function getFileIcon(mimeType) {
                if (mimeType.startsWith('image/')) {
                    return '<i class="fas fa-image fa-3x text-primary"></i>';
                } else if (mimeType === 'application/pdf') {
                    return '<i class="fas fa-file-pdf fa-3x text-danger"></i>';
                } else if (mimeType.includes('word')) {
                    return '<i class="fas fa-file-word fa-3x text-primary"></i>';
                } else if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
                    return '<i class="fas fa-file-excel fa-3x text-success"></i>';
                } else {
                    return '<i class="fas fa-file fa-3x text-secondary"></i>';
                }
            }

            // Format file size
            function formatFileSize(bytes) {
                if (bytes >= 1073741824) {
                    return (bytes / 1073741824).toFixed(2) + ' GB';
                } else if (bytes >= 1048576) {
                    return (bytes / 1048576).toFixed(2) + ' MB';
                } else if (bytes >= 1024) {
                    return (bytes / 1024).toFixed(2) + ' KB';
                } else {
                    return bytes + ' bytes';
                }
            }

            // Truncate filename if too long
            function truncateFilename(filename) {
                if (filename.length > 20) {
                    return filename.substring(0, 17) + '...';
                }
                return filename;
            }

            // Upload all files
            function uploadAllFiles(fileQueue, dropzone) {
                var uploadPromises = [];

                fileQueue.forEach(function(item, index) {
                    var file = item.file;
                    var category = $('.file-category[data-filename="' + file.name + '"]').val();
                    var description = $('.file-description[data-filename="' + file.name + '"]').val();

                    var formData = new FormData();
                    formData.append('files[]', file);
                    formData.append('category', category);
                    formData.append('description', description);
                    formData.append('_token', '{{ csrf_token() }}');

                    var progressBar = $('.file-preview-card[data-filename="' + file.name + '"] .progress');
                    var progressBarFill = progressBar.find('.progress-bar');

                    progressBar.show();

                    var uploadPromise = $.ajax({
                        url: "{{ route('invoices.attachments.store', $invoice) }}",
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        xhr: function() {
                            var xhr = new window.XMLHttpRequest();
                            xhr.upload.addEventListener("progress", function(evt) {
                                if (evt.lengthComputable) {
                                    var percentComplete = Math.round((evt.loaded / evt
                                        .total) * 100);
                                    progressBarFill.css('width', percentComplete + '%')
                                        .text(percentComplete + '%');
                                }
                            }, false);
                            return xhr;
                        },
                        success: function(response) {
                            console.log('Upload success:', response);
                            progressBarFill.removeClass('progress-bar-striped').addClass(
                                'bg-success');

                            // Add new row to DataTable
                            addRowToDataTable(response.attachments[0]);

                            // Remove file from dropzone and queue
                            dropzone.removeFile(file);
                        },
                        error: function(xhr) {
                            console.log('Upload error:', xhr);
                            progressBarFill.removeClass('progress-bar-striped').addClass(
                                'bg-danger');

                            let errorMessage = 'Upload failed.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            toastr.error(errorMessage);
                        }
                    });

                    uploadPromises.push(uploadPromise);
                });

                // Wait for all uploads to complete
                Promise.all(uploadPromises).then(function() {
                    toastr.success('All files uploaded successfully!');
                    $('#file-queue').hide();
                }).catch(function() {
                    toastr.error('Some files failed to upload');
                });
            }

            // Add new row to table
            function addRowToDataTable(attachment) {
                // Remove "no attachments" row if it exists
                $('#attachments-table tbody tr td[colspan]').parent().remove();

                // Extract file extension from file_name
                var fileExtension = attachment.file_name ? attachment.file_name.split('.').pop().toUpperCase() :
                    'FILE';

                // Format file size
                var formattedSize = formatFileSize(attachment.file_size);

                // Create category badge
                var categoryBadge = attachment.category ?
                    '<span class="badge badge-primary">' + attachment.category + '</span>' :
                    '<span class="badge badge-secondary">No Category</span>';

                var rowNumber = $('#attachments-table tbody tr').length + 1;

                var rowHtml = '<tr>' +
                    '<td>' + rowNumber + '</td>' +
                    '<td><i class="fas fa-file-pdf mr-2"></i>' + attachment.file_name +
                    (attachment.description ? '<br><small class="text-muted">' + attachment.description +
                        '</small>' : '') + '</td>' +
                    '<td><span class="badge badge-info">' + fileExtension + '</span></td>' +
                    '<td>' + formattedSize + '</td>' +
                    '<td>' + categoryBadge + '</td>' +
                    '<td>' + (attachment.uploader_name || 'Prana Dian') + '</td>' +
                    '<td>' + new Date().toLocaleDateString('en-GB') + ' ' + new Date().toLocaleTimeString('en-GB', {
                        hour: '2-digit',
                        minute: '2-digit'
                    }) + '</td>' +
                    '<td>' + createActionButtons(attachment) + '</td>' +
                    '</tr>';

                $('#attachments-table tbody').append(rowHtml);

                // Update attachment count in header
                var currentCount = $('#attachments-table tbody tr').length;
                $('.card-title').first().html('<i class="fas fa-paperclip mr-2"></i>Attachments (' + currentCount +
                    ' files)');
            }

            // Create action buttons HTML
            function createActionButtons(attachment) {
                var buttons = '';

                buttons += '<a href="/invoices/attachments/' + attachment.id +
                    '/download" class="btn btn-info btn-sm" title="Download"><i class="fas fa-download"></i></a> ';

                // Check if file can be previewed (PDF or image)
                if (attachment.mime_type && (attachment.mime_type.startsWith('image/') || attachment.mime_type ===
                        'application/pdf')) {
                    buttons += '<a href="/invoices/attachments/' + attachment.id +
                        '/preview" class="btn btn-primary btn-sm" title="Preview" target="_blank"><i class="fas fa-eye"></i></a> ';
                }

                buttons += '<button type="button" class="btn btn-warning btn-sm edit-attachment" data-id="' +
                    attachment.id + '" data-description="' + (attachment.description || '') + '" data-category="' +
                    (attachment.category || '') + '" data-update-url="/invoices/attachments/' + attachment.id +
                    '" title="Edit Description"><i class="fas fa-edit"></i></button> ';

                buttons += '<button type="button" class="btn btn-danger btn-sm delete-attachment" data-id="' +
                    attachment.id + '" data-filename="' + attachment.file_name +
                    '" data-delete-url="/invoices/attachments/' + attachment.id +
                    '" title="Delete"><i class="fas fa-trash"></i></button>';

                return buttons;
            }

            // Handle remove file button clicks
            $(document).on('click', '.remove-file-btn', function() {
                var filename = $(this).data('filename');
                var file = myDropzone.getFilesWithStatus(Dropzone.ADDED).find(f => f.name === filename);
                if (file) {
                    myDropzone.removeFile(file);
                }
            });

            // Handle category filter clicks
            $('.category-filter').click(function() {
                var category = $(this).data('category');

                // Update active button
                $('.category-filter').removeClass('active');
                $(this).addClass('active');

                // Filter table rows
                if (category === 'all') {
                    $('#attachments-table tbody tr').show();
                } else {
                    $('#attachments-table tbody tr').each(function() {
                        var rowCategory = $(this).find('td:eq(4)').text().trim();
                        if (rowCategory === category) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            });

            // Handle edit attachment
            $('.edit-attachment').click(function() {
                const id = $(this).data('id');
                const description = $(this).data('description');
                const category = $(this).data('category');
                const updateUrl = $(this).data('update-url');

                console.log('Edit button clicked:', {
                    id,
                    description,
                    category,
                    updateUrl
                });

                $('#edit_description').val(description);
                $('#edit_category').val(category);
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
                                // Remove row from table
                                var row = $(this).closest('tr');
                                row.remove();

                                // Update attachment count in header
                                var currentCount = $('#attachments-table tbody tr')
                                    .length;
                                $('.card-title').first().html(
                                    '<i class="fas fa-paperclip mr-2"></i>Attachments (' +
                                    currentCount + ' files)');
                            }.bind(this),
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
