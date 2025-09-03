@extends('layouts.main')

@section('title_page', 'Document Status Management')

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Document Status</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            @if (isset($invoices) && isset($additionalDocuments) && isset($statusCounts))
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

                <!-- Filters and Search -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Filters & Search</h3>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="{{ route('admin.document-status.index') }}" id="filterForm">
                                    <div class="row">
                                        <div class="col-md-3">
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
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="document_type">Document Type</label>
                                                <select name="document_type" id="document_type" class="form-control">
                                                    <option value="all" {{ $documentType === 'all' ? 'selected' : '' }}>
                                                        All Types</option>
                                                    <option value="invoice"
                                                        {{ $documentType === 'invoice' ? 'selected' : '' }}>Invoices
                                                    </option>
                                                    <option value="additional_document"
                                                        {{ $documentType === 'additional_document' ? 'selected' : '' }}>
                                                        Additional Documents</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="search">Search</label>
                                                <input type="text" name="search" id="search" class="form-control"
                                                    placeholder="Search by document number, PO, supplier..."
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
                                                        data-document-id="{{ $invoice->id }}"
                                                        data-document-type="invoice"
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
                                            <button type="button" class="btn btn-success btn-sm"
                                                id="bulkResetAdditionalDocs">
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
                                                                <span
                                                                    class="badge badge-secondary">{{ $doc->distribution_status }}</span>
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
                    @else
                        <div class="alert alert-danger">
                            <h4><i class="fas fa-exclamation-triangle"></i> Error Loading Data</h4>
                            <p>Unable to load document status data. Please try refreshing the page or contact support if the problem
                                persists.</p>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Reset Status Modal -->
            <div class="modal fade" id="resetStatusModal" tabindex="-1" role="dialog" aria-labelledby="resetStatusModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="resetStatusModalLabel">Reset Document Status</h5>
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
                            <h5 class="modal-title" id="bulkResetModalLabel">Bulk Reset Document Statuses</h5>
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
            <style>
                .status-badge {
                    display: inline-block;
                    padding: 0.25em 0.6em;
                    font-size: 75%;
                    font-weight: 700;
                    line-height: 1;
                    text-align: center;
                    white-space: nowrap;
                    vertical-align: baseline;
                    border-radius: 0.25rem;
                }

                .status-available {
                    background-color: #28a745;
                    color: white;
                }

                .status-in_transit {
                    background-color: #17a2b8;
                    color: white;
                }

                .status-distributed {
                    background-color: #007bff;
                    color: white;
                }

                .status-unaccounted_for {
                    background-color: #ffc107;
                    color: black;
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
            <script>
                $(document).ready(function() {
                    console.log('Document status page loaded successfully');

                    // Basic modal functionality
                    $('.reset-status-btn').click(function() {
                        const documentId = $(this).data('document-id');
                        const documentType = $(this).data('document-type');
                        const currentStatus = $(this).data('current-status');

                        $('#resetStatusModal').data('document-id', documentId);
                        $('#resetStatusModal').data('document-type', documentType);
                        $('#newStatus').val(currentStatus);
                        $('#resetStatusModal').modal('show');
                    });

                    // Basic form submission
                    $('#resetStatusForm').submit(function(e) {
                        e.preventDefault();
                        alert('Status reset functionality - form submitted');
                    });
                });
            </script>
        @endsection
