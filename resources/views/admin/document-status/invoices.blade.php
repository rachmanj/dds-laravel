@extends('layouts.main')

@section('title_page', 'Invoice Status Management')

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.document-status.index') }}">Document Status</a></li>
    <li class="breadcrumb-item active">Invoice Status</li>
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
                            <h3 class="card-title">Invoice Status Management</h3>
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
                                        <a class="nav-link active" href="{{ route('admin.document-status.invoices') }}">
                                            <i class="fas fa-file-invoice"></i> Invoice Status Management
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link"
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
                            <form method="GET" action="{{ route('admin.document-status.invoices') }}" id="filterForm">
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
                                                placeholder="Search by invoice number, PO, supplier..."
                                                value="{{ $search }}">
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

            <!-- Invoices Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                Invoices
                                <span class="badge badge-primary">{{ $invoices->total() }}</span>
                            </h3>
                            <div class="card-tools">
                                @if ($statusFilter === 'unaccounted_for' || $statusFilter === 'all')
                                    <button type="button" class="btn btn-success btn-sm" id="bulkResetInvoices">
                                        <i class="fas fa-sync-alt"></i> Bulk Reset to Available
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table id="invoices-table" class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th width="30">
                                            @if ($statusFilter === 'unaccounted_for' || $statusFilter === 'all')
                                                <input type="checkbox" id="selectAllInvoices">
                                            @endif
                                        </th>
                                        <th>Invoice #</th>
                                        <th>PO Number</th>
                                        <th>Supplier</th>
                                        <th>Project</th>
                                        <th>Current Location</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th width="150">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $invoice)
                                        <tr>
                                            <td>
                                                @if ($statusFilter === 'unaccounted_for' || $statusFilter === 'all')
                                                    <input type="checkbox" class="invoice-checkbox"
                                                        value="{{ $invoice->id }}">
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $invoice->invoice_number }}</strong>
                                                @if ($invoice->po_no)
                                                    <br><small class="text-muted">PO: {{ $invoice->po_no }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $invoice->po_no ?? 'N/A' }}</td>
                                            <td>{{ $invoice->supplier->name ?? 'N/A' }}</td>
                                            <td>{{ $invoice->invoice_project ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ $invoice->cur_loc ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                @switch($invoice->distribution_status)
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
                                                        <span
                                                            class="badge badge-secondary">{{ $invoice->distribution_status }}</span>
                                                @endswitch
                                            </td>
                                            <td>{{ $invoice->created_at->format('d M Y') }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary reset-status-btn"
                                                    data-document-id="{{ $invoice->id }}" data-document-type="invoice"
                                                    data-current-status="{{ $invoice->distribution_status }}">
                                                    <i class="fas fa-edit"></i> Reset Status
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                                    <p>No invoices found with the current filters.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($invoices->hasPages())
                                <div class="card-footer clearfix">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of
                                                {{ $invoices->total() }} results
                                            </small>
                                        </div>
                                        <div>
                                            {{ $invoices->appends(request()->query())->links('pagination::bootstrap-4') }}
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
                        <h5 class="modal-title" id="resetStatusModalLabel">Reset Invoice Status</h5>
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
                        <h5 class="modal-title" id="bulkResetModalLabel">Bulk Reset Invoice Statuses</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="bulkResetForm">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> Bulk operations can only reset invoices with status "Unaccounted For" to
                                "Available".
                            </div>
                            <div class="form-group">
                                <label for="bulkReason">Reason for Bulk Status Reset</label>
                                <textarea name="reason" id="bulkReason" class="form-control" rows="3"
                                    placeholder="Please provide a reason for this bulk status reset..." required></textarea>
                                <small class="form-text text-muted">This will be logged for audit purposes.</small>
                            </div>
                            <div class="form-group">
                                <label>Selected Invoices</label>
                                <div id="selectedDocumentsList" class="border p-2 bg-light">
                                    <p class="text-muted mb-0">No invoices selected</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Reset Selected Invoices</button>
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
                console.log('Invoice document status page loaded successfully');

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

                // Select all invoices functionality
                $('#selectAllInvoices').change(function() {
                    $('.invoice-checkbox').prop('checked', $(this).is(':checked'));
                    updateSelectedDocumentsList();
                });

                // Individual checkbox change
                $('.invoice-checkbox').change(function() {
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
                $('#bulkResetInvoices').click(function() {
                    const selectedIds = $('.invoice-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();

                    if (selectedIds.length === 0) {
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Please select at least one invoice to reset.');
                        } else {
                            alert('Please select at least one invoice to reset.');
                        }
                        return;
                    }

                    updateSelectedDocumentsList();
                    $('#bulkResetModal').modal('show');
                });

                // Update selected documents list
                function updateSelectedDocumentsList() {
                    const selectedIds = $('.invoice-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();

                    if (selectedIds.length === 0) {
                        $('#selectedDocumentsList').html('<p class="text-muted mb-0">No invoices selected</p>');
                    } else {
                        $('#selectedDocumentsList').html(
                            `<p class="mb-0"><strong>${selectedIds.length}</strong> invoice(s) selected</p>`);
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

                    const selectedIds = $('.invoice-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();
                    const reason = $('#bulkReason').val();

                    if (selectedIds.length === 0 || !reason) {
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Please select invoices and provide a reason.');
                        } else {
                            alert('Please select invoices and provide a reason.');
                        }
                        return;
                    }

                    $.ajax({
                        url: '{{ route('admin.document-status.bulk-reset') }}',
                        method: 'POST',
                        data: {
                            document_ids: selectedIds,
                            document_type: 'invoice',
                            reason: reason,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#bulkResetModal').modal('hide');
                                // Show success message with toastr
                                if (response.updated_count > 0) {
                                    const successMsg =
                                        `Successfully updated ${response.updated_count} invoice(s).`;
                                    if (response.skipped_count > 0) {
                                        successMsg +=
                                            ` Skipped ${response.skipped_count} invoice(s) (not eligible for bulk reset).`;
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
