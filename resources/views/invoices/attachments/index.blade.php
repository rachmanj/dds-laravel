@extends('layouts.main')

@section('title_page')
    Invoice Attachments
@endsection

@section('breadcrumb_title')
    invoices / attachments
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Invoice Attachments</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
                        <li class="breadcrumb-item active">Attachments</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Info boxes -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-file-invoice"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Invoices</span>
                            <span class="info-box-number" id="total-invoices">0</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-paperclip"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Attachments</span>
                            <span class="info-box-number" id="total-attachments">0</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-hdd"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Size</span>
                            <span class="info-box-number" id="total-size">0 MB</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-calendar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Last Upload</span>
                            <span class="info-box-number" id="last-upload">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File type distribution and recent uploads -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">File Type Distribution</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0" id="file-type-distribution">
                                <li><span class="badge badge-success">Images</span> <span class="ml-2"
                                        id="dist-images">0</span></li>
                                <li class="mt-1"><span class="badge badge-danger">PDFs</span> <span class="ml-2"
                                        id="dist-pdfs">0</span></li>
                                <li class="mt-1"><span class="badge badge-secondary">Others</span> <span class="ml-2"
                                        id="dist-others">0</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Uploads</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>Invoice</th>
                                        <th>By</th>
                                        <th>Size</th>
                                        <th>When</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-uploads-body">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No data</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Invoices with Attachments</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-row">
                                    <div class="col-md-3">
                                        <input type="text" id="filter-invoice-number" class="form-control"
                                            placeholder="Invoice Number">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" id="filter-po-no" class="form-control"
                                            placeholder="PO Number">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" id="filter-supplier" class="form-control"
                                            placeholder="Supplier Name">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filter-status" class="form-control">
                                            <option value="">All Status</option>
                                            <option value="open">Open</option>
                                            <option value="verify">Verify</option>
                                            <option value="return">Return</option>
                                            <option value="sap">SAP</option>
                                            <option value="close">Close</option>
                                            <option value="cancel">Cancel</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row mt-2">
                                    <div class="col-md-3">
                                        <input type="date" id="filter-date-from" class="form-control"
                                            placeholder="Date From">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" id="filter-date-to" class="form-control"
                                            placeholder="Date To">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filter-has-attachments" class="form-control">
                                            <option value="">All Invoices</option>
                                            <option value="yes">Has Attachments</option>
                                            <option value="no">No Attachments</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button id="btn-apply-filters" class="btn btn-primary btn-block"><i
                                                class="fas fa-filter"></i> Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                            <table id="invoices-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice Number</th>
                                        <th>PO Number</th>
                                        <th>Supplier</th>
                                        <th>Amount</th>
                                        <th>Location</th>
                                        <th>Attachments</th>
                                        <th>Total Size</th>
                                        <th>Last Upload</th>
                                        <th>Last Uploader</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Attachments Modal -->
    <div class="modal fade" id="attachmentsModal" tabindex="-1" role="dialog" aria-labelledby="attachmentsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attachmentsModalLabel">Invoice Attachments</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="attachments-content">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Attachment Modal -->
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
                    <form id="editAttachmentForm">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveAttachmentBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
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

                // Show session messages if exists
                @if (session('success'))
                    toastr.success('{{ session('success') }}');
                @endif

                @if (session('error'))
                    toastr.error('{{ session('error') }}');
                @endif

                @if (session('warning'))
                    toastr.warning('{{ session('warning') }}');
                @endif

                @if (session('info'))
                    toastr.info('{{ session('info') }}');
                @endif
            }

            // Initialize DataTable
            var table = $('#invoices-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('invoices.attachments.data') }}",
                    data: function(d) {
                        d.invoice_number = $('#filter-invoice-number').val();
                        d.po_no = $('#filter-po-no').val();
                        d.supplier_name = $('#filter-supplier').val();
                        d.status = $('#filter-status').val();
                        d.date_from = $('#filter-date-from').val();
                        d.date_to = $('#filter-date-to').val();
                        d.has_attachments = $('#filter-has-attachments').val();
                    }
                },
                columns: [{
                        data: 'invoice_number',
                        name: 'invoice_number'
                    },
                    {
                        data: 'po_no',
                        name: 'po_no',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'supplier.name',
                        name: 'supplier.name',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        render: function(data) {
                            return parseFloat(data).toLocaleString('id-ID', {
                                minimumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'cur_loc',
                        name: 'cur_loc'
                    },
                    {
                        data: 'total_attachments',
                        name: 'total_attachments',
                        searchable: false,
                        render: function(data) {
                            return '<span class="badge badge-info">' + data + '</span>';
                        }
                    },
                    {
                        data: 'total_size',
                        name: 'total_size',
                        searchable: false
                    },
                    {
                        data: 'last_uploaded',
                        name: 'last_uploaded',
                        searchable: false
                    },
                    {
                        data: 'last_uploader',
                        name: 'last_uploader',
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [7, 'desc']
                ],
                pageLength: 25,
                responsive: true,
                drawCallback: function() {
                    updateInfoBoxes();
                }
            });

            $('#btn-apply-filters').on('click', function() {
                table.ajax.reload();
            });

            // View attachments button click
            $('#invoices-table').on('click', '.view-attachments', function() {
                var invoiceId = $(this).data('invoice-id');
                var invoiceNumber = $(this).data('invoice-number');

                $('#attachmentsModalLabel').text('Attachments for Invoice: ' + invoiceNumber);
                $('#attachmentsModal').modal('show');

                // Load attachments content
                loadAttachments(invoiceId);
            });

            // Edit attachment button click
            $(document).on('click', '.edit-attachment', function() {
                var attachmentId = $(this).data('attachment-id');
                var description = $(this).data('description');

                $('#editAttachmentForm').attr('action', '/invoices/attachments/' + attachmentId);
                $('#description').val(description);
                $('#editAttachmentModal').modal('show');
            });

            // Save attachment changes
            $('#saveAttachmentBtn').click(function() {
                var form = $('#editAttachmentForm');
                var url = form.attr('action');
                var data = form.serialize();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#editAttachmentModal').modal('hide');
                            toastr.success(response.message);

                            // Reload attachments
                            var invoiceId = $('#attachmentsModal').data('current-invoice-id');
                            if (invoiceId) {
                                loadAttachments(invoiceId);
                            }
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to update attachment. Please try again.');
                    }
                });
            });

            // Delete attachment button click
            $(document).on('click', '.delete-attachment', function() {
                var attachmentId = $(this).data('attachment-id');
                var fileName = $(this).data('file-name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete '" + fileName + "'?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteAttachment(attachmentId);
                    }
                });
            });

            function loadAttachments(invoiceId) {
                $('#attachmentsModal').data('current-invoice-id', invoiceId);
                $('#attachments-content').html(
                    '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-2">Loading attachments...</p></div>'
                );

                $.ajax({
                    url: '/api/invoices/' + invoiceId + '/attachments',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            var invoice = response.data.invoice;
                            var attachments = response.data.attachments;

                            var html = '<div class="row mb-3">';
                            html += '<div class="col-md-6">';
                            html += '<strong>Invoice:</strong> ' + invoice.invoice_number + '<br>';
                            html += '<strong>PO Number:</strong> ' + (invoice.po_no || '-') + '<br>';
                            html += '<strong>Supplier:</strong> ' + (invoice.supplier ? invoice.supplier
                                .name : '-');
                            html += '</div>';
                            html += '<div class="col-md-6">';
                            html += '<strong>Amount:</strong> ' + parseFloat(invoice.amount)
                                .toLocaleString('id-ID', {
                                    minimumFractionDigits: 2
                                }) + '<br>';
                            html += '<strong>Location:</strong> ' + invoice.cur_loc + '<br>';
                            html += '<strong>Total Attachments:</strong> ' + attachments.length;
                            html += '</div>';
                            html += '</div>';

                            html += '<div class="table-responsive">';
                            html += '<table class="table table-bordered table-striped">';
                            html += '<thead><tr>';
                            html += '<th>File Name</th>';
                            html += '<th>Type</th>';
                            html += '<th>Size</th>';
                            html += '<th>Description</th>';
                            html += '<th>Uploaded By</th>';
                            html += '<th>Upload Date</th>';
                            html += '<th>Actions</th>';
                            html += '</tr></thead><tbody>';

                            if (attachments.length > 0) {
                                attachments.forEach(function(attachment) {
                                    html += '<tr>';
                                    html += '<td>' + attachment.file_name + '</td>';
                                    html += '<td><span class="badge badge-' + getFileTypeBadge(
                                            attachment.mime_type) +
                                        '">' + getFileTypeName(attachment.mime_type) +
                                        '</span></td>';
                                    html += '<td>' + formatFileSize(attachment.file_size) +
                                        '</td>';
                                    html += '<td>' + (attachment.description || '-') + '</td>';
                                    html += '<td>' + (attachment.uploader ? attachment.uploader
                                        .name : '-') + '</td>';
                                    html += '<td>' + formatDate(attachment.created_at) +
                                        '</td>';
                                    html += '<td>';
                                    html +=
                                        '<button class="btn btn-sm btn-info mr-1 view-file" data-attachment-id="' +
                                        attachment.id + '"><i class="fas fa-eye"></i></button>';
                                    html +=
                                        '<button class="btn btn-sm btn-warning mr-1 edit-attachment" data-attachment-id="' +
                                        attachment.id + '" data-description="' + (attachment
                                            .description || '') +
                                        '"><i class="fas fa-edit"></i></button>';
                                    html +=
                                        '<button class="btn btn-sm btn-danger delete-attachment" data-attachment-id="' +
                                        attachment.id + '" data-file-name="' + attachment
                                        .file_name +
                                        '"><i class="fas fa-trash"></i></button>';
                                    html += '</td>';
                                    html += '</tr>';
                                });
                            } else {
                                html +=
                                    '<tr><td colspan="7" class="text-center">No attachments found</td></tr>';
                            }

                            html += '</tbody></table></div>';

                            $('#attachments-content').html(html);
                        } else {
                            $('#attachments-content').html(
                                '<div class="alert alert-danger">Failed to load attachments</div>');
                        }
                    },
                    error: function() {
                        $('#attachments-content').html(
                            '<div class="alert alert-danger">Failed to load attachments</div>');
                    }
                });
            }

            function deleteAttachment(attachmentId) {
                $.ajax({
                    url: '/invoices/attachments/' + attachmentId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);

                            // Reload attachments
                            var invoiceId = $('#attachmentsModal').data('current-invoice-id');
                            if (invoiceId) {
                                loadAttachments(invoiceId);
                            }

                            // Reload main table
                            table.ajax.reload();
                        } else {
                            toastr.error(response.message || 'Failed to delete attachment');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to delete attachment. Please try again.');
                    }
                });
            }

            function updateInfoBoxes() {
                $.ajax({
                    url: '/api/invoices/attachments/stats',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            var stats = response.data;
                            $('#total-invoices').text(stats.total_invoices);
                            $('#total-attachments').text(stats.total_attachments);
                            $('#total-size').text(stats.total_size);
                            $('#last-upload').text(stats.last_upload);

                            if (stats.file_type_distribution) {
                                $('#dist-images').text(stats.file_type_distribution.images || 0);
                                $('#dist-pdfs').text(stats.file_type_distribution.pdfs || 0);
                                $('#dist-others').text(stats.file_type_distribution.others || 0);
                            }

                            var tbody = $('#recent-uploads-body');
                            tbody.empty();
                            if (stats.recent_uploads && stats.recent_uploads.length) {
                                stats.recent_uploads.forEach(function(item) {
                                    var tr = $('<tr/>');
                                    tr.append('<td>' + item.file_name + '</td>');
                                    tr.append('<td>' + (item.invoice_number || '-') + '</td>');
                                    tr.append('<td>' + (item.uploaded_by || '-') + '</td>');
                                    tr.append('<td>' + item.file_size + '</td>');
                                    tr.append('<td>' + item.created_at + '</td>');
                                    tbody.append(tr);
                                });
                            } else {
                                tbody.append(
                                    '<tr><td colspan="5" class="text-center text-muted">No recent uploads</td></tr>'
                                );
                            }
                        }
                    }
                });
            }

            // View file button click
            $(document).on('click', '.view-file', function() {
                var attachmentId = $(this).data('attachment-id');
                window.open('/invoices/attachments/' + attachmentId, '_blank');
            });

            function getFileTypeBadge(mimeType) {
                if (mimeType && mimeType.startsWith('image/')) return 'success';
                if (mimeType === 'application/pdf') return 'danger';
                return 'secondary';
            }

            function getFileTypeName(mimeType) {
                if (mimeType && mimeType.startsWith('image/')) return 'Image';
                if (mimeType === 'application/pdf') return 'PDF';
                return 'File';
            }

            function formatFileSize(bytes) {
                if (!bytes) return '0 bytes';

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

            function formatDate(dateString) {
                if (!dateString) return '-';
                var date = new Date(dateString);
                return date.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            // Enforce 50MB max per file on client-side when uploading from detail page modal flows
            $(document).on('change', 'input[type="file"][name="files[]"]', function() {
                var files = this.files;
                var maxPerFile = 50 * 1024 * 1024; // 50MB
                for (var i = 0; i < files.length; i++) {
                    if (files[i].size > maxPerFile) {
                        toastr.error('Each file must be 50MB or less.');
                        this.value = '';
                        break;
                    }
                }
            });
        });
    </script>
@endsection
