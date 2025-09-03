@extends('layouts.main')

@section('title_page', 'Additional Document Status Management')

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.document-status.index') }}">Document Status</a></li>
    <li class="breadcrumb-item active">Additional Document Status</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Status Overview Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $statusCounts['available'] ?? 0 }}</h3>
                            <p>Available for Distribution</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $statusCounts['in_transit'] ?? 0 }}</h3>
                            <p>In Transit</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ $statusCounts['distributed'] ?? 0 }}</h3>
                            <p>Distributed</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $statusCounts['unaccounted_for'] ?? 0 }}</h3>
                            <p>Unaccounted For</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Additional Document Status Management</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.document-status.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to Overview
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('admin.document-status.invoices') }}">
                                            <i class="fas fa-file-invoice"></i> Invoice Status Management
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link active"
                                            href="{{ route('admin.document-status.additional-documents') }}">
                                            <i class="fas fa-file-alt"></i> Additional Document Status Management
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Filters & Search</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.document-status.additional-documents') }}"
                                id="filterForm">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="status">Status Filter</label>
                                            <select name="status" id="status" class="form-control">
                                                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>
                                                    All Statuses</option>
                                                <option value="available"
                                                    {{ $statusFilter === 'available' ? 'selected' : '' }}>Available
                                                </option>
                                                <option value="in_transit"
                                                    {{ $statusFilter === 'in_transit' ? 'selected' : '' }}>In Transit
                                                </option>
                                                <option value="distributed"
                                                    {{ $statusFilter === 'distributed' ? 'selected' : '' }}>Distributed
                                                </option>
                                                <option value="unaccounted_for"
                                                    {{ $statusFilter === 'unaccounted_for' ? 'selected' : '' }}>
                                                    Unaccounted For</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="search">Search</label>
                                            <input type="text" name="search" id="search" class="form-control"
                                                placeholder="Search by document number, PO..." value="{{ $search }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-search"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Documents Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                Additional Documents
                                <span class="badge badge-primary">{{ $additionalDocuments->total() }}</span>
                            </h3>
                            <div class="card-tools">
                                @if ($statusFilter === 'unaccounted_for' || $statusFilter === 'all')
                                    <button type="button" class="btn btn-success btn-sm" id="bulkResetAdditionalDocs">
                                        <i class="fas fa-sync-alt"></i> Bulk Reset to Available
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table id="additional-docs-table" class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th width="30">
                                            @if ($statusFilter === 'unaccounted_for' || $statusFilter === 'all')
                                                <input type="checkbox" id="selectAllAdditionalDocs">
                                            @endif
                                        </th>
                                        <th>Document #</th>
                                        <th>Type</th>
                                        <th>PO Number</th>
                                        <th>Project</th>
                                        <th>Current Location</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th width="150">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($additionalDocuments as $doc)
                                        <tr>
                                            <td>
                                                @if ($statusFilter === 'unaccounted_for' || $statusFilter === 'all')
                                                    <input type="checkbox" class="additional-doc-checkbox"
                                                        value="{{ $doc->id }}">
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $doc->document_number }}</strong>
                                                @if ($doc->po_no)
                                                    <br><small class="text-muted">PO: {{ $doc->po_no }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $doc->type->type_name ?? 'N/A' }}</td>
                                            <td>{{ $doc->po_no ?? 'N/A' }}</td>
                                            <td>{{ $doc->project ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ $doc->cur_loc ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                @switch($doc->distribution_status)
                                                    @case('available')
                                                        <span class="badge badge-success">Available</span>
                                                    @break

                                                    @case('in_transit')
                                                        <span class="badge badge-info">In Transit</span>
                                                    @break

                                                    @case('distributed')
                                                        <span class="badge badge-primary">Distributed</span>
                                                    @break

                                                    @case('unaccounted_for')
                                                        <span class="badge badge-warning">Unaccounted For</span>
                                                    @break

                                                    @default
                                                        <span class="badge badge-secondary">{{ $doc->distribution_status }}</span>
                                                @endswitch
                                            </td>
                                            <td>{{ $doc->created_at->format('d M Y') }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary reset-status-btn"
                                                    data-document-id="{{ $doc->id }}"
                                                    data-document-type="additional_document"
                                                    data-current-status="{{ $doc->distribution_status }}">
                                                    <i class="fas fa-edit"></i> Reset Status
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                                                    <p>No additional documents found with the current filters.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($additionalDocuments->hasPages())
                                <div class="card-footer clearfix">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                Showing {{ $additionalDocuments->firstItem() }} to
                                                {{ $additionalDocuments->lastItem() }} of
                                                {{ $additionalDocuments->total() }} results
                                            </small>
                                        </div>
                                        <div>
                                            {{ $additionalDocuments->appends(request()->query())->links('pagination::bootstrap-4') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </section>

        <!-- Reset Status Modal -->
        <div class="modal fade" id="resetStatusModal" tabindex="-1" role="dialog" aria-labelledby="resetStatusModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="resetStatusModalLabel">Reset Additional Document Status</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="resetStatusForm">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="newStatus">New Status</label>
                                <select name="new_status" id="newStatus" class="form-control" required>
                                    <option value="">Select Status</option>
                                    <option value="available">Available</option>
                                    <option value="in_transit">In Transit</option>
                                    <option value="distributed">Distributed</option>
                                    <option value="unaccounted_for">Unaccounted For</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="reason">Reason for Status Change</label>
                                <textarea name="reason" id="reason" class="form-control" rows="3"
                                    placeholder="Please provide a reason for this status change..." required></textarea>
                                <small class="form-text text-muted">This will be logged for audit purposes.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bulk Reset Modal -->
        <div class="modal fade" id="bulkResetModal" tabindex="-1" role="dialog" aria-labelledby="bulkResetModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkResetModalLabel">Bulk Reset Additional Document Statuses</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="bulkResetForm">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> Bulk operations can only reset documents with status "Unaccounted For" to
                                "Available".
                            </div>
                            <div class="form-group">
                                <label for="bulkReason">Reason for Bulk Status Reset</label>
                                <textarea name="reason" id="bulkReason" class="form-control" rows="3"
                                    placeholder="Please provide a reason for this bulk status reset..." required></textarea>
                                <small class="form-text text-muted">This will be logged for audit purposes.</small>
                            </div>
                            <div class="form-group">
                                <label>Selected Documents</label>
                                <div id="selectedDocumentsList" class="border p-2 bg-light">
                                    <p class="text-muted mb-0">No documents selected</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Reset Selected Documents</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection

    @section('styles')
        <!-- Toastr -->
        <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
        <style>
            .nav-tabs-custom {
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
            }

            .nav-tabs-custom .nav-tabs {
                border-bottom: 1px solid #dee2e6;
                background-color: #f8f9fa;
            }

            .nav-tabs-custom .nav-tabs .nav-link {
                border: none;
                border-radius: 0;
                color: #495057;
                padding: 0.75rem 1rem;
            }

            .nav-tabs-custom .nav-tabs .nav-link:hover {
                background-color: #e9ecef;
                border-color: transparent;
            }

            .nav-tabs-custom .nav-tabs .nav-link.active {
                background-color: #fff;
                border-bottom: 2px solid #007bff;
                color: #007bff;
            }

            /* Fix pagination arrow size and style */
            .pagination .page-link {
                font-size: 14px !important;
                padding: 0.375rem 0.75rem !important;
                line-height: 1.25 !important;
            }

            /* Hide large SVG icons in pagination */
            .pagination .page-link svg {
                display: none !important;
            }

            /* Replace with text-based arrows */
            .pagination .page-item:first-child .page-link {
                font-size: 14px !important;
            }

            .pagination .page-item:first-child .page-link::after {
                content: "‹ Previous" !important;
                font-size: 14px !important;
            }

            .pagination .page-item:last-child .page-link {
                font-size: 14px !important;
            }

            .pagination .page-item:last-child .page-link::after {
                content: "Next ›" !important;
                font-size: 14px !important;
            }

            /* Ensure pagination doesn't create large elements */
            .pagination {
                font-size: 14px !important;
            }

            .pagination .page-item .page-link {
                max-height: 38px !important;
                overflow: hidden !important;
            }
        </style>
    @endsection

    @section('scripts')
        <!-- Toastr -->
        <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
        <script>
            $(document).ready(function() {
                console.log('Additional document status page loaded successfully');

                // Initialize Toastr
                if (typeof toastr !== 'undefined') {
                    toastr.options = {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-top-right",
                        timeOut: 5000,
                        extendedTimeOut: 1000,
                        preventDuplicates: true
                    };
                }

                // Select all additional documents functionality
                $('#selectAllAdditionalDocs').change(function() {
                    $('.additional-doc-checkbox').prop('checked', $(this).is(':checked'));
                    updateSelectedDocumentsList();
                });

                // Individual checkbox change
                $('.additional-doc-checkbox').change(function() {
                    updateSelectedDocumentsList();
                });

                // Reset status button click
                $('.reset-status-btn').click(function() {
                    const documentId = $(this).data('document-id');
                    const documentType = $(this).data('document-type');
                    const currentStatus = $(this).data('current-status');

                    $('#resetStatusModal').data('document-id', documentId);
                    $('#resetStatusModal').data('document-type', documentType);
                    $('#newStatus').val(currentStatus);
                    $('#resetStatusModal').modal('show');
                });

                // Bulk reset button click
                $('#bulkResetAdditionalDocs').click(function() {
                    const selectedIds = $('.additional-doc-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();

                    if (selectedIds.length === 0) {
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Please select at least one document to reset.');
                        } else {
                            alert('Please select at least one document to reset.');
                        }
                        return;
                    }

                    updateSelectedDocumentsList();
                    $('#bulkResetModal').modal('show');
                });

                // Update selected documents list
                function updateSelectedDocumentsList() {
                    const selectedIds = $('.additional-doc-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();

                    if (selectedIds.length === 0) {
                        $('#selectedDocumentsList').html('<p class="text-muted mb-0">No documents selected</p>');
                    } else {
                        $('#selectedDocumentsList').html(
                            `<p class="mb-0"><strong>${selectedIds.length}</strong> document(s) selected</p>`);
                    }
                }

                // Reset status form submission
                $('#resetStatusForm').submit(function(e) {
                    e.preventDefault();

                    const documentId = $('#resetStatusModal').data('document-id');
                    const documentType = $('#resetStatusModal').data('document-type');
                    const newStatus = $('#newStatus').val();
                    const reason = $('#reason').val();

                    if (!newStatus || !reason) {
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Please fill in all required fields.');
                        } else {
                            alert('Please fill in all required fields.');
                        }
                        return;
                    }

                    $.ajax({
                        url: '{{ route('admin.document-status.reset') }}',
                        method: 'POST',
                        data: {
                            document_id: documentId,
                            document_type: documentType,
                            new_status: newStatus,
                            reason: reason,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#resetStatusModal').modal('hide');
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('Status updated successfully');
                                }
                                location.reload();
                            } else {
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('Error: ' + response.message);
                                } else {
                                    alert('Error: ' + response.message);
                                }
                            }
                        },
                        error: function(xhr) {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Error updating status. Please try again.');
                            } else {
                                alert('Error updating status. Please try again.');
                            }
                        }
                    });
                });

                // Bulk reset form submission
                $('#bulkResetForm').submit(function(e) {
                    e.preventDefault();

                    const selectedIds = $('.additional-doc-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();
                    const reason = $('#bulkReason').val();

                    if (selectedIds.length === 0 || !reason) {
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Please select documents and provide a reason.');
                        } else {
                            alert('Please select documents and provide a reason.');
                        }
                        return;
                    }

                    $.ajax({
                        url: '{{ route('admin.document-status.bulk-reset') }}',
                        method: 'POST',
                        data: {
                            document_ids: selectedIds,
                            document_type: 'additional_document',
                            reason: reason,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#bulkResetModal').modal('hide');
                                // Show success message with toastr
                                if (response.updated_count > 0) {
                                    const successMsg =
                                        `Successfully updated ${response.updated_count} document(s).`;
                                    if (response.skipped_count > 0) {
                                        successMsg +=
                                            ` Skipped ${response.skipped_count} document(s) (not eligible for bulk reset).`;
                                    }
                                    if (typeof toastr !== 'undefined') {
                                        toastr.success(successMsg);
                                    }
                                }
                                // Reload after a short delay to show the notification
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('Error: ' + response.message);
                                } else {
                                    alert('Error: ' + response.message);
                                }
                            }
                        },
                        error: function(xhr) {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Error performing bulk reset. Please try again.');
                            } else {
                                alert('Error performing bulk reset. Please try again.');
                            }
                        }
                    });
                });
            });
        </script>
    @endsection
