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

    <style>
        /* Confirmation Modal Styles */
        .max-height-200 {
            max-height: 200px;
        }

        .overflow-auto {
            overflow-y: auto;
        }

        /* Department Location Indicators */
        .location-indicator {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 5px;
        }

        .location-current {
            background-color: #28a745;
            color: white;
        }

        .location-other {
            background-color: #6c757d;
            color: white;
        }

        .location-unavailable {
            background-color: #dc3545;
            color: white;
        }

        /* Document row styling based on location */
        .document-row-unavailable {
            opacity: 0.6;
            background-color: #f8f9fa;
        }

        .document-row-unavailable td {
            color: #6c757d;
        }

        /* Linked documents styling */
        .linked-document-item {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
            background-color: #f8f9fa;
        }

        .linked-document-item.selected {
            background-color: #e3f2fd;
            border-color: #2196f3;
        }

        /* Confirmation modal specific styles */
        #confirmationModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .document-summary-item {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .document-summary-item:last-child {
            border-bottom: none;
        }

        /* Floating Action Buttons */
        .floating-actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 6px;
            background: white;
            padding: 12px;
        }

        .floating-actions .btn {
            font-size: 16px;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .floating-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease;
        }

        .floating-actions .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .floating-actions .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        /* Add bottom padding to content to prevent overlap */
        .card-body {
            padding-bottom: 100px !important;
        }

        /* Responsive - hide on mobile */
        @media (max-width: 768px) {
            .floating-actions {
                bottom: 10px;
                right: 10px;
                left: 10px;
                justify-content: center;
                padding: 10px;
            }

            .floating-actions .btn {
                flex: 1;
                padding: 10px 16px;
                font-size: 14px;
            }
        }
    </style>
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
                                            <!-- Search and Filter Controls -->
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i
                                                                    class="fas fa-search"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control" id="invoice-search"
                                                            placeholder="Search invoices...">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-control" id="invoice-status-filter">
                                                        <option value="">All Status</option>
                                                        <option value="open">Open</option>
                                                        <option value="verify">Verify</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-control" id="invoice-supplier-filter">
                                                        <option value="">All Suppliers</option>
                                                        @foreach ($invoices->pluck('supplier')->unique()->sort() as $supplier)
                                                            <option value="{{ $supplier }}">{{ $supplier }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-outline-secondary btn-block"
                                                        id="clear-invoice-filters">
                                                        <i class="fas fa-times"></i> Clear
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover" id="invoice-table">
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
                                                            <th>Distribution Status</th>
                                                            <th>Location</th>
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
                                                                    <span
                                                                        class="badge {{ $invoice->status_badge_class }}">
                                                                        {{ ucfirst($invoice->status) }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    @if ($invoice->distribution_status === 'available')
                                                                        <span class="badge badge-success">
                                                                            <i class="fas fa-check-circle"></i> Available
                                                                        </span>
                                                                    @elseif($invoice->distribution_status === 'distributed')
                                                                        <span class="badge badge-info">
                                                                            <i class="fas fa-paper-plane"></i> Previously
                                                                            Distributed
                                                                        </span>
                                                                    @elseif($invoice->distribution_status === 'in_transit')
                                                                        <span class="badge badge-warning">
                                                                            <i class="fas fa-truck"></i> In Transit
                                                                        </span>
                                                                    @elseif($invoice->distribution_status === 'unaccounted_for')
                                                                        <span class="badge badge-danger">
                                                                            <i class="fas fa-exclamation-triangle"></i>
                                                                            Unaccounted
                                                                        </span>
                                                                    @else
                                                                        <span class="badge badge-secondary">
                                                                            {{ ucfirst($invoice->distribution_status) }}
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($invoice->cur_loc === auth()->user()->department->location_code)
                                                                        <span class="location-indicator location-current">
                                                                            <i class="fas fa-map-marker-alt"></i>
                                                                            {{ $invoice->cur_loc }}
                                                                        </span>
                                                                    @else
                                                                        <span class="location-indicator location-other">
                                                                            <i class="fas fa-map-marker-alt"></i>
                                                                            {{ $invoice->cur_loc }}
                                                                        </span>
                                                                    @endif
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
                                            <!-- Search and Filter Controls -->
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i
                                                                    class="fas fa-search"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control"
                                                            id="additional-doc-search" placeholder="Search documents...">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-control" id="additional-doc-status-filter">
                                                        <option value="">All Status</option>
                                                        <option value="open">Open</option>
                                                        <option value="verify">Verify</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-control" id="additional-doc-type-filter">
                                                        <option value="">All Types</option>
                                                        @foreach ($additionalDocuments->pluck('type.type_name')->unique()->filter()->sort() as $type)
                                                            <option value="{{ $type }}">{{ $type }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-outline-secondary btn-block"
                                                        id="clear-additional-doc-filters">
                                                        <i class="fas fa-times"></i> Clear
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover" id="additional-doc-table">
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
                                                            <th>Distribution Status</th>
                                                            <th>Location</th>
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
                                                                <td>
                                                                    @if ($doc->distribution_status === 'available')
                                                                        <span class="badge badge-success">
                                                                            <i class="fas fa-check-circle"></i> Available
                                                                        </span>
                                                                    @elseif($doc->distribution_status === 'distributed')
                                                                        <span class="badge badge-info">
                                                                            <i class="fas fa-paper-plane"></i> Previously
                                                                            Distributed
                                                                        </span>
                                                                    @elseif($doc->distribution_status === 'in_transit')
                                                                        <span class="badge badge-warning">
                                                                            <i class="fas fa-truck"></i> In Transit
                                                                        </span>
                                                                    @elseif($doc->distribution_status === 'unaccounted_for')
                                                                        <span class="badge badge-danger">
                                                                            <i class="fas fa-exclamation-triangle"></i>
                                                                            Unaccounted
                                                                        </span>
                                                                    @else
                                                                        <span class="badge badge-secondary">
                                                                            {{ ucfirst($doc->distribution_status) }}
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($doc->cur_loc === auth()->user()->department->location_code)
                                                                        <span class="location-indicator location-current">
                                                                            <i class="fas fa-map-marker-alt"></i>
                                                                            {{ $doc->cur_loc }}
                                                                        </span>
                                                                    @else
                                                                        <span class="location-indicator location-other">
                                                                            <i class="fas fa-map-marker-alt"></i>
                                                                            {{ $doc->cur_loc }}
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Documents Summary -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card" id="selected-documents-card" style="display: none;">
                                            <div class="card-header">
                                                <h6 class="card-title">
                                                    <i class="fas fa-list-check"></i> Selected Documents
                                                    <span class="badge badge-primary ml-2" id="selected-count">0</span>
                                                </h6>
                                                <div class="card-tools">
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        id="clear-all-selected">
                                                        <i class="fas fa-times"></i> Clear All
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="selected-documents-list">
                                                    <!-- Selected documents will be populated here -->
                                                </div>
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

                            </form>

                            <!-- Floating Action Buttons -->
                            <div class="floating-actions">
                                <button type="submit" form="distributionForm" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Create Distribution
                                </button>
                                <a href="{{ route('distributions.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </section>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog"
        aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">
                        <i class="fas fa-check-circle text-warning"></i> Confirm Distribution Creation
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Please review the distribution details below before confirming:</strong>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-tag"></i> Distribution Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td id="confirm-type"></td>
                                </tr>
                                <tr>
                                    <td><strong>Destination:</strong></td>
                                    <td id="confirm-destination"></td>
                                </tr>
                                <tr>
                                    <td><strong>Document Type:</strong></td>
                                    <td id="confirm-document-type"></td>
                                </tr>
                                <tr>
                                    <td><strong>Notes:</strong></td>
                                    <td id="confirm-notes"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-list-check"></i> Selected Documents</h6>
                            <div id="confirm-selected-documents" class="max-height-200 overflow-auto">
                                <!-- Selected documents will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Linked Documents Section -->
                    <div class="row mt-3" id="linked-documents-section" style="display: none;">
                        <div class="col-12">
                            <h6><i class="fas fa-link"></i> Automatically Linked Additional Documents</h6>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Note:</strong> The following additional documents will be automatically included
                                with the selected invoices:
                            </div>
                            <div id="confirm-linked-documents" class="max-height-200 overflow-auto">
                                <!-- Linked documents will be populated here -->
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="manage-linked-docs">
                                    <i class="fas fa-cog"></i> Manage Linked Documents
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmSubmit">
                        <i class="fas fa-check"></i> Confirm & Create Distribution
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Linked Documents Management Modal -->
    <div class="modal fade" id="linkedDocsModal" tabindex="-1" role="dialog" aria-labelledby="linkedDocsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="linkedDocsModalLabel">
                        <i class="fas fa-link"></i> Manage Linked Additional Documents
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Select which additional documents to include with your distribution:</strong>
                    </div>
                    <div id="linked-documents-list">
                        <!-- Linked documents checkboxes will be populated here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="saveLinkedDocs">
                        <i class="fas fa-save"></i> Save Selection
                    </button>
                </div>
            </div>
        </div>
    </div>
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

            // Search and Filter Functions
            function filterTable(tableId, searchTerm, statusFilter, typeFilter) {
                var table = $('#' + tableId + ' tbody tr');
                var visibleCount = 0;

                table.each(function() {
                    var row = $(this);
                    var invoiceNumber = row.find('td:nth-child(2)').text().toLowerCase();
                    var supplier = row.find('td:nth-child(3)').text().toLowerCase();
                    var status = row.find('td:nth-child(6)').text().toLowerCase();
                    var documentNumber = row.find('td:nth-child(2)').text().toLowerCase();
                    var docType = row.find('td:nth-child(3)').text().toLowerCase();

                    var matchesSearch = true;
                    var matchesStatus = true;
                    var matchesType = true;

                    // Search filter
                    if (searchTerm) {
                        if (tableId === 'invoice-table') {
                            matchesSearch = invoiceNumber.includes(searchTerm) || supplier.includes(
                                searchTerm);
                        } else {
                            matchesSearch = documentNumber.includes(searchTerm) || docType.includes(
                                searchTerm);
                        }
                    }

                    // Status filter
                    if (statusFilter) {
                        matchesStatus = status.includes(statusFilter);
                    }

                    // Type filter (for additional documents)
                    if (typeFilter && tableId === 'additional-doc-table') {
                        matchesType = docType.includes(typeFilter);
                    }

                    if (matchesSearch && matchesStatus && matchesType) {
                        row.show();
                        visibleCount++;
                    } else {
                        row.hide();
                    }
                });

                // Update visible count
                var container = tableId === 'invoice-table' ? '#invoice-selection' :
                    '#additional-document-selection';
                var countElement = $(container + ' .card-header h6');
                var originalText = countElement.text();
                var baseText = originalText.replace(/\(\d+\)/, '');
                countElement.text(baseText + ' (' + visibleCount + ')');
            }

            // Invoice search and filters
            $('#invoice-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                var statusFilter = $('#invoice-status-filter').val().toLowerCase();
                var supplierFilter = $('#invoice-supplier-filter').val().toLowerCase();
                filterTable('invoice-table', searchTerm, statusFilter, supplierFilter);
            });

            $('#invoice-status-filter, #invoice-supplier-filter').on('change', function() {
                var searchTerm = $('#invoice-search').val().toLowerCase();
                var statusFilter = $('#invoice-status-filter').val().toLowerCase();
                var supplierFilter = $('#invoice-supplier-filter').val().toLowerCase();
                filterTable('invoice-table', searchTerm, statusFilter, supplierFilter);
            });

            $('#clear-invoice-filters').on('click', function() {
                $('#invoice-search').val('');
                $('#invoice-status-filter').val('');
                $('#invoice-supplier-filter').val('');
                filterTable('invoice-table', '', '', '');
            });

            // Additional document search and filters
            $('#additional-doc-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                var statusFilter = $('#additional-doc-status-filter').val().toLowerCase();
                var typeFilter = $('#additional-doc-type-filter').val().toLowerCase();
                filterTable('additional-doc-table', searchTerm, statusFilter, typeFilter);
            });

            $('#additional-doc-status-filter, #additional-doc-type-filter').on('change', function() {
                var searchTerm = $('#additional-doc-search').val().toLowerCase();
                var statusFilter = $('#additional-doc-status-filter').val().toLowerCase();
                var typeFilter = $('#additional-doc-type-filter').val().toLowerCase();
                filterTable('additional-doc-table', searchTerm, statusFilter, typeFilter);
            });

            $('#clear-additional-doc-filters').on('click', function() {
                $('#additional-doc-search').val('');
                $('#additional-doc-status-filter').val('');
                $('#additional-doc-type-filter').val('');
                filterTable('additional-doc-table', '', '', '');
            });

            // Select all handlers
            $('#selectAllInvoices').click(function() {
                $('#invoice-table tbody tr:visible .invoice-checkbox').prop('checked', true);
                updateSelectedDocuments();
                updatePreview();
            });

            $('#deselectAllInvoices').click(function() {
                $('#invoice-table tbody tr:visible .invoice-checkbox').prop('checked', false);
                updateSelectedDocuments();
                updatePreview();
            });

            $('#selectAllAdditionalDocs').click(function() {
                $('#additional-doc-table tbody tr:visible .additional-doc-checkbox').prop('checked', true);
                updateSelectedDocuments();
                updatePreview();
            });

            $('#deselectAllAdditionalDocs').click(function() {
                $('#additional-doc-table tbody tr:visible .additional-doc-checkbox').prop('checked', false);
                updateSelectedDocuments();
                updatePreview();
            });

            // Checkbox change handlers
            $('.invoice-checkbox, .additional-doc-checkbox').change(function() {
                console.log('Checkbox changed:', this.name, this.value, this.checked);
                updateSelectedDocuments();
                updatePreview();
            });

            // Clear all selected documents
            $('#clear-all-selected').click(function() {
                $('input[name="document_ids[]"]:checked').prop('checked', false);
                updateSelectedDocuments();
                updatePreview();
            });

            // Update selected documents list
            function updateSelectedDocuments() {
                var selectedDocs = $('input[name="document_ids[]"]:checked');
                var documentType = $('#document_type').val();

                if (selectedDocs.length > 0 && documentType) {
                    var listHtml = '';
                    var selectedData = [];

                    selectedDocs.each(function() {
                        var checkbox = $(this);
                        var row = checkbox.closest('tr');
                        var documentNumber = row.find('td:nth-child(2)').text().trim();
                        var additionalInfo = '';

                        if (documentType === 'invoice') {
                            var supplier = row.find('td:nth-child(3)').text().trim();
                            var amount = row.find('td:nth-child(5)').text().trim();
                            additionalInfo = supplier + ' - ' + amount;
                        } else {
                            var type = row.find('td:nth-child(3)').text().trim();
                            additionalInfo = type;
                        }

                        selectedData.push({
                            id: checkbox.val(),
                            number: documentNumber,
                            info: additionalInfo,
                            type: documentType
                        });

                        listHtml +=
                            '<div class="selected-document-item mb-2 p-2 border rounded" data-id="' +
                            checkbox.val() + '">';
                        listHtml += '<div class="d-flex justify-content-between align-items-center">';
                        listHtml += '<div>';
                        listHtml += '<strong>' + documentNumber + '</strong><br>';
                        listHtml += '<small class="text-muted">' + additionalInfo + '</small>';
                        listHtml += '</div>';
                        listHtml +=
                            '<button type="button" class="btn btn-sm btn-outline-danger remove-document" data-id="' +
                            checkbox.val() + '">';
                        listHtml += '<i class="fas fa-times"></i>';
                        listHtml += '</button>';
                        listHtml += '</div>';
                        listHtml += '</div>';
                    });

                    $('#selected-documents-list').html(listHtml);
                    $('#selected-count').text(selectedDocs.length);
                    $('#selected-documents-card').show();

                    // Handle individual document removal
                    $('.remove-document').click(function() {
                        var documentId = $(this).data('id');
                        $('input[name="document_ids[]"][value="' + documentId + '"]').prop('checked',
                            false);
                        updateSelectedDocuments();
                        updatePreview();
                    });

                } else {
                    $('#selected-documents-card').hide();
                }
            }

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

            // Global variables for linked documents management
            var linkedDocuments = [];
            var selectedLinkedDocuments = [];

            // Form submission with confirmation dialog
            var isSubmitting = false; // Submission guard

            $('#distributionForm').submit(function(e) {
                e.preventDefault();

                // Show confirmation dialog instead of direct submission
                showConfirmationDialog();
            });

            // Show confirmation dialog
            function showConfirmationDialog() {
                // Validate required fields first
                var requiredFields = ['type_id', 'destination_department_id', 'document_type'];
                var missingFields = [];

                requiredFields.forEach(function(field) {
                    var value = $('#' + field).val();
                    if (!value) {
                        missingFields.push(field);
                    }
                });

                if (missingFields.length > 0) {
                    toastr.error('Please fill in all required fields: ' + missingFields.join(', '));
                    return false;
                }

                var selectedDocs = $('input[name="document_ids[]"]:checked');
                if (selectedDocs.length === 0) {
                    toastr.error('Please select at least one document to distribute');
                    return false;
                }

                // Populate confirmation dialog
                populateConfirmationDialog();

                // Check for linked documents if distributing invoices
                if ($('#document_type').val() === 'invoice') {
                    checkLinkedDocuments();
                } else {
                    $('#linked-documents-section').hide();
                }

                // Show modal
                $('#confirmationModal').modal('show');
            }

            // Populate confirmation dialog
            function populateConfirmationDialog() {
                // Distribution information
                $('#confirm-type').text($('#type_id option:selected').text() || 'Not selected');
                $('#confirm-destination').text($('#destination_department_id option:selected').text() ||
                    'Not selected');
                $('#confirm-document-type').text($('#document_type option:selected').text() || 'Not selected');
                $('#confirm-notes').text($('#notes').val() || 'No notes');

                // Selected documents
                var selectedDocs = $('input[name="document_ids[]"]:checked');
                var documentType = $('#document_type').val();
                var documentsHtml = '';

                selectedDocs.each(function() {
                    var checkbox = $(this);
                    var row = checkbox.closest('tr');
                    var documentNumber = row.find('td:nth-child(2)').text().trim();
                    var additionalInfo = '';

                    if (documentType === 'invoice') {
                        var supplier = row.find('td:nth-child(3)').text().trim();
                        var amount = row.find('td:nth-child(5)').text().trim();
                        additionalInfo = supplier + ' - ' + amount;
                    } else {
                        var type = row.find('td:nth-child(3)').text().trim();
                        additionalInfo = type;
                    }

                    documentsHtml += '<div class="document-summary-item">';
                    documentsHtml += '<strong>' + documentNumber + '</strong><br>';
                    documentsHtml += '<small class="text-muted">' + additionalInfo + '</small>';
                    documentsHtml += '</div>';
                });

                $('#confirm-selected-documents').html(documentsHtml);
            }

            // Check for linked additional documents
            function checkLinkedDocuments() {
                var selectedInvoiceIds = $('input[name="document_ids[]"]:checked').map(function() {
                    return this.value;
                }).get();

                if (selectedInvoiceIds.length === 0) {
                    $('#linked-documents-section').hide();
                    return;
                }

                // Make AJAX call to check for linked documents
                $.ajax({
                    url: '{{ route('distributions.check-linked-documents') }}',
                    type: 'POST',
                    data: {
                        _token: $('input[name="_token"]').val(),
                        invoice_ids: selectedInvoiceIds
                    },
                    success: function(response) {
                        if (response.success && response.linked_documents.length > 0) {
                            linkedDocuments = response.linked_documents;
                            selectedLinkedDocuments = [...linkedDocuments]; // Select all by default
                            populateLinkedDocuments();
                            $('#linked-documents-section').show();
                        } else {
                            $('#linked-documents-section').hide();
                        }
                    },
                    error: function() {
                        $('#linked-documents-section').hide();
                    }
                });
            }

            // Populate linked documents in confirmation dialog
            function populateLinkedDocuments() {
                var linkedDocsHtml = '';

                linkedDocuments.forEach(function(doc) {
                    var isSelected = selectedLinkedDocuments.some(function(selected) {
                        return selected.id === doc.id;
                    });

                    linkedDocsHtml += '<div class="document-summary-item">';
                    linkedDocsHtml += '<strong>' + doc.document_number + '</strong> ';
                    linkedDocsHtml += '<span class="badge badge-info">' + doc.type + '</span><br>';
                    linkedDocsHtml += '<small class="text-muted">PO: ' + (doc.po_no || 'N/A') + '</small>';
                    linkedDocsHtml += '</div>';
                });

                $('#confirm-linked-documents').html(linkedDocsHtml);
            }

            // Handle manage linked documents button
            $('#manage-linked-docs').click(function() {
                showLinkedDocumentsModal();
            });

            // Show linked documents management modal
            function showLinkedDocumentsModal() {
                var modalHtml = '';

                linkedDocuments.forEach(function(doc) {
                    var isSelected = selectedLinkedDocuments.some(function(selected) {
                        return selected.id === doc.id;
                    });

                    modalHtml += '<div class="linked-document-item ' + (isSelected ? 'selected' : '') +
                        '" data-id="' + doc.id + '">';
                    modalHtml += '<div class="form-check">';
                    modalHtml +=
                        '<input class="form-check-input linked-doc-checkbox" type="checkbox" id="linked-doc-' +
                        doc.id + '" value="' + doc.id + '" ' + (isSelected ? 'checked' : '') + '>';
                    modalHtml += '<label class="form-check-label" for="linked-doc-' + doc.id + '">';
                    modalHtml += '<strong>' + doc.document_number + '</strong><br>';
                    modalHtml += '<small class="text-muted">Type: ' + doc.type + ' | PO: ' + (doc.po_no ||
                        'N/A') + '</small>';
                    modalHtml += '</label>';
                    modalHtml += '</div>';
                    modalHtml += '</div>';
                });

                $('#linked-documents-list').html(modalHtml);
                $('#linkedDocsModal').modal('show');
            }

            // Handle linked document checkbox changes
            $(document).on('change', '.linked-doc-checkbox', function() {
                var docId = parseInt($(this).val());
                var isChecked = $(this).is(':checked');
                var item = $(this).closest('.linked-document-item');

                if (isChecked) {
                    // Add to selected
                    if (!selectedLinkedDocuments.some(function(doc) {
                            return doc.id === docId;
                        })) {
                        var doc = linkedDocuments.find(function(d) {
                            return d.id === docId;
                        });
                        if (doc) selectedLinkedDocuments.push(doc);
                    }
                    item.addClass('selected');
                } else {
                    // Remove from selected
                    selectedLinkedDocuments = selectedLinkedDocuments.filter(function(doc) {
                        return doc.id !== docId;
                    });
                    item.removeClass('selected');
                }
            });

            // Save linked documents selection
            $('#saveLinkedDocs').click(function() {
                $('#linkedDocsModal').modal('hide');
                populateLinkedDocuments(); // Refresh the confirmation dialog
            });

            // Handle confirmation submission
            $('#confirmSubmit').click(function() {
                $('#confirmationModal').modal('hide');
                submitDistribution();
            });

            // Original form submission logic (now called from confirmation)
            function submitDistribution() {

                // Prevent multiple submissions
                if (isSubmitting) {
                    console.log('Form submission already in progress, ignoring duplicate submit');
                    return false;
                }

                // Debug: Log form data before submission
                console.log('=== DISTRIBUTION FORM DEBUG ===');
                console.log('Form action:', $('#distributionForm').attr('action'));
                console.log('CSRF token:', $('input[name="_token"]').val());

                // Log all form fields
                var formData = $('#distributionForm').serializeArray();
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

                // Add linked documents to form data
                var finalFormData = $('#distributionForm').serialize();

                // Add selected linked documents
                if (selectedLinkedDocuments.length > 0) {
                    finalFormData += '&linked_document_ids=' + selectedLinkedDocuments.map(function(doc) {
                        return doc.id;
                    }).join(',');
                }

                console.log('Final form data (serialize):', finalFormData);
                console.log('Linked documents:', selectedLinkedDocuments);
                console.log('=== END DEBUG ===');

                // Set submission guard
                isSubmitting = true;

                // Disable submit button
                $('#submitBtn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Creating...');

                // Submit form
                $.ajax({
                    url: $('#distributionForm').attr('action'),
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
                                    '{{ url('distributions') }}/' + response
                                    .distribution.id :
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
            }

            // Remove validation errors on input
            $('input, select, textarea').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('');
            });
        });
    </script>
@endsection
