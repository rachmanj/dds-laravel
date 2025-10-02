@extends('layouts.main')

@section('title_page')
    Create New Invoice
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <style>
        .linked-invoices-badge {
            cursor: help;
        }

        .linked-invoices-badge.badge-success {
            background-color: #28a745;
        }

        .linked-invoices-badge.badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .linked-invoices-badge.badge-info {
            background-color: #17a2b8;
        }

        /* Enhanced Validation Feedback Styles */
        .form-group {
            position: relative;
        }

        .validation-spinner,
        .validation-result,
        .sap-validation-spinner,
        .sap-validation-result {
            display: inline-block;
            margin-left: 8px;
            font-size: 0.875rem;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-50%) translateX(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(-50%) translateX(0);
            }
        }

        .validation-spinner i,
        .sap-validation-spinner i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Success state for input fields */
        .form-control.is-valid {
            border-color: #28a745;
            padding-right: calc(1.5em + 0.75rem);
            background-image: none;
        }

        .form-control.is-valid:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        /* Invalid state enhancement */
        .form-control.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: none;
        }

        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        /* Validation icons positioning */
        #invoice_number,
        #sap_doc {
            padding-right: 120px !important;
        }

        .validation-result,
        .sap-validation-result {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            z-index: 5;
        }

        .validation-spinner,
        .sap-validation-spinner {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            z-index: 5;
        }
    </style>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- ENHANCEMENT: Keyboard Shortcuts Help Alert -->
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong><i class="fas fa-keyboard"></i> Keyboard Shortcuts:</strong>
                        <span class="ml-3">
                            <kbd>Tab</kbd> Navigate fields |
                            <kbd>Ctrl+S</kbd> Save invoice |
                            <kbd>Esc</kbd> Cancel & return |
                            <kbd>Ctrl+Enter</kbd> in PO field = Search docs
                        </span>
                    </div>
                </div>
            </div>

            <!-- PHASE 2 ENHANCEMENT: Quick Fill from Recent Invoices -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline card-info">
                        <div class="card-body p-2">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <strong><i class="fas fa-history"></i> Quick Fill:</strong>
                                </div>
                                <div class="col-md-10">
                                    <select id="recent-invoices" class="form-control">
                                        <option value="">Select from your recent invoices to auto-fill...</option>
                                    </select>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Select a recent invoice to auto-fill supplier,
                                        type, currency, and projects. You can then adjust dates and amounts.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ENHANCEMENT: Form Progress Indicator -->
            <div class="row">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><i class="fas fa-tasks"></i> Form Progress:</strong>
                                    <span id="progress-text" class="ml-2">0/8 required fields completed</span>
                                </div>
                                <div class="progress" style="width: 300px; height: 25px;">
                                    <div class="progress-bar progress-bar-striped" id="form-progress-bar" role="progressbar"
                                        style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                        0%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Invoice Information</h3>
                            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-info float-right">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <form action="{{ route('invoices.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <!-- Basic Invoice Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="supplier_id">Supplier <span class="text-danger">*</span></label>
                                            <select
                                                class="form-control select2bs4 @error('supplier_id') is-invalid @enderror"
                                                id="supplier_id" name="supplier_id" required>
                                                <option value="">Select Supplier</option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}"
                                                        data-sap-code="{{ $supplier->sap_code ?? '' }}"
                                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}@if ($supplier->sap_code)
                                                            ({{ $supplier->sap_code }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('supplier_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_number">Invoice Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('invoice_number') is-invalid @enderror"
                                                id="invoice_number" name="invoice_number"
                                                value="{{ old('invoice_number') }}" required>
                                            @error('invoice_number')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Dates and PO Number -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="invoice_date">Invoice Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('invoice_date') is-invalid @enderror"
                                                id="invoice_date" name="invoice_date" value="{{ old('invoice_date') }}"
                                                required>
                                            @error('invoice_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="receive_date">Receive Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('receive_date') is-invalid @enderror"
                                                id="receive_date" name="receive_date" value="{{ old('receive_date') }}"
                                                required>
                                            @error('receive_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="po_no">
                                                PO Number
                                                <i class="fas fa-question-circle text-info ml-1" data-toggle="tooltip"
                                                    data-placement="top"
                                                    title="Purchase Order number. Enter and click search to automatically find and link related additional documents (ITO, BAST, BAPP)."></i>
                                            </label>
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control @error('po_no') is-invalid @enderror"
                                                    id="po_no" name="po_no" value="{{ old('po_no') }}"
                                                    maxlength="30" placeholder="Enter PO number">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-info"
                                                        id="po-suggestions-btn" title="Get PO Suggestions">
                                                        <i class="fas fa-lightbulb"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        id="search-docs-btn" title="Search Additional Documents">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @error('po_no')
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> Enter PO number and click search to find
                                                related additional documents
                                            </small>
                                        </div>
                                    </div>
                                </div>


                                <!-- Invoice Type, Currency, and Amount -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="type_id">
                                                Invoice Type <span class="text-danger">*</span>
                                                <i class="fas fa-question-circle text-info ml-1" data-toggle="tooltip"
                                                    data-placement="top"
                                                    title="Category of the invoice: Service, Item (goods), Rental, Catering, Consultants, Expedition, or Others."></i>
                                            </label>
                                            <select class="form-control @error('type_id') is-invalid @enderror"
                                                id="type_id" name="type_id" required>
                                                <option value="">Select Invoice Type</option>
                                                @foreach ($invoiceTypes as $type)
                                                    <option value="{{ $type->id }}"
                                                        {{ old('type_id') == $type->id ? 'selected' : '' }}>
                                                        {{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('type_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="currency">
                                                Currency <span class="text-danger">*</span>
                                                <i class="fas fa-question-circle text-info ml-1" data-toggle="tooltip"
                                                    data-placement="top"
                                                    title="Invoice currency. The amount prefix will update automatically to match your selection."></i>
                                            </label>
                                            <select class="form-control @error('currency') is-invalid @enderror"
                                                id="currency" name="currency" required>
                                                <option value="IDR" {{ old('currency') == 'IDR' ? 'selected' : '' }}>
                                                    IDR
                                                </option>
                                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>
                                                    USD
                                                </option>
                                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>
                                                    EUR
                                                </option>
                                                <option value="SGD" {{ old('currency') == 'SGD' ? 'selected' : '' }}>
                                                    SGD
                                                </option>
                                            </select>
                                            @error('currency')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="amount">
                                                Amount <span class="text-danger">*</span>
                                                <i class="fas fa-question-circle text-info ml-1" data-toggle="tooltip"
                                                    data-placement="top"
                                                    title="Invoice total amount. Enter numbers only - thousand separators will be added automatically."></i>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="currency-prefix">IDR</span>
                                                </div>
                                                <input type="text" name="amount_display" id="amount_display"
                                                    class="form-control @error('amount') is-invalid @enderror"
                                                    value="{{ old('amount') }}" onkeyup="formatNumber(this)"
                                                    placeholder="0.00" required>
                                                <input type="hidden" name="amount" id="amount"
                                                    value="{{ old('amount') }}">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-info"
                                                        id="calculator-btn" data-toggle="tooltip"
                                                        title="Quick Calculator">
                                                        <i class="fas fa-calculator"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @error('amount')
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Information -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="receive_project">Receive Project</label>
                                            <input type="text" class="form-control" id="receive_project"
                                                name="receive_project" value="{{ auth()->user()->project ?? '' }}"
                                                readonly>
                                            <small class="form-text text-muted">Automatically set to your department's
                                                project</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="invoice_project">
                                                Invoice Project <span class="text-danger">*</span>
                                                <i class="fas fa-question-circle text-info ml-1" data-toggle="tooltip"
                                                    data-placement="top"
                                                    title="The project code associated with this invoice. Usually matches your current location's project and will auto-populate when you select a location."></i>
                                            </label>
                                            <select class="form-control @error('invoice_project') is-invalid @enderror"
                                                id="invoice_project" name="invoice_project" required>
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('invoice_project') == $project->code ? 'selected' : '' }}>
                                                        {{ $project->code }}@if ($project->owner)
                                                            - {{ $project->owner }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('invoice_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="payment_project">
                                                Payment Project
                                                <i class="fas fa-question-circle text-info ml-1" data-toggle="tooltip"
                                                    data-placement="top"
                                                    title="The project code that will be charged for this invoice payment. Defaults to 001H (Head Office)."></i>
                                            </label>
                                            <select class="form-control @error('payment_project') is-invalid @enderror"
                                                id="payment_project" name="payment_project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('payment_project') == $project->code || $project->code == '001H' ? 'selected' : '' }}>
                                                        {{ $project->code }}@if ($project->owner)
                                                            - {{ $project->owner }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('payment_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Location and Additional Information -->
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="cur_loc">Current Location <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control @error('cur_loc') is-invalid @enderror"
                                                id="cur_loc" name="cur_loc"
                                                {{ !auth()->user()->hasRole(['superadmin', 'admin'])? 'disabled': '' }}
                                                required>
                                                <option value="">Select Location</option>
                                                @foreach ($departments as $dept)
                                                    @if ($dept->location_code)
                                                        <option value="{{ $dept->location_code }}"
                                                            {{ old('cur_loc') == $dept->location_code ||
                                                            (auth()->user()->department_location_code == $dept->location_code && !old('cur_loc'))
                                                                ? 'selected'
                                                                : '' }}>
                                                            {{ $dept->location_code }} - {{ $dept->name }}
                                                            ({{ $dept->project }})
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @error('cur_loc')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                @if (!auth()->user()->hasRole(['superadmin', 'admin']))
                                                    This is set to your department's location and cannot be changed.
                                                @else
                                                    You can change the location as you have administrative privileges.
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="faktur_no">
                                                Faktur No
                                                <i class="fas fa-question-circle text-info ml-1" data-toggle="tooltip"
                                                    data-placement="top"
                                                    title="Tax invoice number from the supplier. This is optional and can be added later."></i>
                                            </label>
                                            <input type="text"
                                                class="form-control @error('faktur_no') is-invalid @enderror"
                                                id="faktur_no" name="faktur_no" value="{{ old('faktur_no') }}"
                                                placeholder="e.g., 010.000-25.00000123">
                                            @error('faktur_no')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="sap_doc">
                                                SAP Document
                                                <i class="fas fa-question-circle text-info ml-1" data-toggle="tooltip"
                                                    data-placement="top"
                                                    title="SAP system reference number for tracking. Optional - can be added now or updated later via SAP Update feature."></i>
                                            </label>
                                            <input type="text"
                                                class="form-control @error('sap_doc') is-invalid @enderror"
                                                id="sap_doc" name="sap_doc" value="{{ old('sap_doc') }}"
                                                placeholder="e.g., 5000012345">
                                            @error('sap_doc')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks -->
                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea>
                                    @error('remarks')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>


                                <!-- Link Additional Documents (optional) -->
                                <div class="card card-outline card-secondary mt-3 collapsed-card"
                                    id="additional-docs-card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-link"></i> Link Additional Documents
                                            <span class="badge badge-secondary">Optional</span>
                                        </h3>
                                        <div class="card-tools">
                                            @if (auth()->user()->can('on-the-fly-addoc-feature'))
                                                <button type="button" class="btn btn-sm btn-success mr-2"
                                                    id="create-doc-btn">
                                                    <i class="fas fa-plus"></i> Create New Document
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-secondary mr-2"
                                                id="refresh-docs-btn" style="display:none;">
                                                <i class="fas fa-sync-alt"></i> Refresh
                                            </button>
                                            <span class="badge badge-info mr-2" id="selected-count">Selected: 0</span>
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body" style="display: none;">
                                        <div id="additional-docs-loading" style="display:none;">
                                            <i class="fas fa-spinner fa-spin"></i> Searching by PO No...
                                        </div>
                                        <div class="table-responsive" id="additional-docs-table-wrapper"
                                            style="display:none;">
                                            <table class="table table-sm table-bordered" id="additional-docs-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:32px;"><input type="checkbox"
                                                                id="select-all-docs"></th>
                                                        <th>Document No</th>
                                                        <th>Type</th>
                                                        <th>Date</th>
                                                        <th>PO No</th>
                                                        <th>Current Location</th>
                                                        <th>Remarks</th>
                                                        <th>Linked Invoices</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                            <small class="text-muted">Showing up to 50 results.</small>
                                        </div>
                                        <div id="additional-docs-empty" class="text-muted" style="display:none;">
                                            No documents found for this PO No. Adjust PO or link manually later.
                                        </div>
                                        <div id="selected-docs-wrapper" class="mt-3" style="display:none;">
                                            <h6>Currently selected</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped" id="selected-docs-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Document No</th>
                                                            <th>Type</th>
                                                            <th>Date</th>
                                                            <th>PO No</th>
                                                            <th>Current Location</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button type="button" class="btn btn-outline-info btn-lg mr-2"
                                            id="preview-invoice-btn">
                                            <i class="fas fa-eye"></i> Preview
                                        </button>
                                        <button type="submit" class="btn btn-success btn-lg mr-2"
                                            id="save-and-close-btn">
                                            <i class="fas fa-save"></i> Save and Close
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-lg mr-2" id="save-and-new-btn">
                                            <i class="fas fa-plus"></i> Save and New
                                        </button>
                                        <a href="{{ route('invoices.index') }}"
                                            class="btn btn-outline-secondary btn-lg ml-2" id="cancel-btn">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                    <div id="save-status" class="text-muted" style="display:none;">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <strong>Creating invoice...</strong>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle"></i>
                                    Tip: Press <kbd>Ctrl+S</kbd> to save or <kbd>Esc</kbd> to cancel
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Create Additional Document Modal -->
    <div class="modal fade" id="create-doc-modal" tabindex="-1" role="dialog"
        aria-labelledby="create-doc-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="create-doc-modal-label">Create New Additional
                        Document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="create-doc-form">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_type_id">Document Type <span class="text-danger">*</span></label>
                                    <select class="form-control select2bs4" id="doc_type_id" name="document_type_id"
                                        required>
                                        <option value="">Select Document Type</option>
                                        @forelse ($additionalDocumentTypes ?? [] as $type)
                                            <option value="{{ $type->id }}">
                                                {{ $type->type_name }}</option>
                                        @empty
                                            <option value="">No document types available - Please contact admin
                                            </option>
                                        @endforelse
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_number">Document Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="doc_number" name="document_number"
                                        required maxlength="255">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_date">Document Date</label>
                                    <input type="date" class="form-control" id="doc_date" name="document_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_receive_date">Document Receive Date</label>
                                    <input type="date" class="form-control" id="doc_receive_date"
                                        name="document_receive_date">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_cur_loc">Current Location <span class="text-danger">*</span></label>
                                    <select class="form-control select2bs4" id="doc_cur_loc" name="cur_loc" required>
                                        <option value="">Select Location</option>
                                        <option value="000HLOG">000HLOG (Logistic)</option>
                                        @if (auth()->user() && auth()->user()->department && auth()->user()->department->location_code)
                                            <option value="{{ auth()->user()->department->location_code }}" selected>
                                                {{ auth()->user()->department->location_code }}
                                                ({{ auth()->user()->department->name }})
                                            </option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_po_no">PO Number</label>
                                    <input type="text" class="form-control" id="doc_po_no" name="po_no"
                                        maxlength="255">
                                    <small class="form-text text-muted">This document will be
                                        automatically attached to the current invoice.</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doc_project">Project</label>
                                    <select class="form-control select2bs4" id="doc_project" name="project">
                                        <option value="">Select Project</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->code }}"
                                                {{ auth()->user()->project == $project->code ? 'selected' : '' }}>
                                                {{ $project->code }} - {{ $project->owner }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Optional: Assign to specific project</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="create-doc-submit">
                            <i class="fas fa-plus"></i> Create Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        // Define formatNumber function globally
        function formatNumber(input) {
            // Remove any non-digit characters except dots
            let value = input.value.replace(/[^\d.]/g, '');
            // Ensure only one decimal point
            let parts = value.split('.');
            if (parts.length > 2) {
                parts = [parts[0], parts.slice(1).join('')];
            }
            // Add thousand separators to display
            let displayValue = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            if (parts.length > 1) {
                displayValue += '.' + parts[1];
            }
            input.value = displayValue;

            // Update hidden field with clean numeric value
            let numericValue = parts.join('.');
            document.getElementById('amount').value = numericValue;
        }

        // Wait for jQuery to be available
        function initializeInvoiceForm() {
            if (typeof $ === 'undefined') {
                // jQuery not ready yet, wait a bit more
                setTimeout(initializeInvoiceForm, 100);
                return;
            }


            // Global AJAX error handler for session timeouts
            $(document).ajaxError(function(event, xhr, settings) {
                // Skip session check endpoint to avoid infinite loops
                if (settings.url.includes('check-session')) {
                    return;
                }

                if (xhr.status === 401 || xhr.status === 419) {
                    // Clear any existing toasts
                    if (typeof toastr !== 'undefined') {
                        toastr.clear();
                        toastr.remove();
                        toastr.error('Your session has expired. Redirecting to login page...');
                    }

                    // Prevent further AJAX requests
                    $(document).off('ajaxSend');

                    // Redirect to login page
                    setTimeout(function() {
                        window.location.href = '{{ route('login') }}';
                    }, 1500);

                    // Prevent default form submissions
                    $(document).on('submit', 'form', function(e) {
                        e.preventDefault();
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Your session has expired. Please wait for redirect...');
                        }
                        return false;
                    });
                }
            });

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

                // Show session messages if any
                @if (session('success'))
                    toastr.success("{{ session('success') }}");
                @endif

                @if (session('error'))
                    toastr.error("{{ session('error') }}");
                @endif

                @if (session('warning'))
                    toastr.warning("{{ session('warning') }}");
                @endif

                @if (session('info'))
                    toastr.info("{{ session('info') }}");
                @endif
            } else {
                console.error('Toastr not loaded');
            }

            // ENHANCEMENT: Initialize Select2 for all select fields
            try {
                if (typeof $.fn.select2 !== 'undefined') {
                    // Supplier dropdown with search
                    $('#supplier_id').select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select Supplier',
                        allowClear: true,
                        width: '100%'
                    });

                    // Invoice Type dropdown
                    $('#type_id').select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select Invoice Type',
                        allowClear: true,
                        width: '100%',
                        minimumResultsForSearch: -1 // Disable search for short lists
                    });

                    // Invoice Project dropdown
                    $('#invoice_project').select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select Project',
                        allowClear: true,
                        width: '100%'
                    });

                    // Payment Project dropdown
                    $('#payment_project').select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select Project',
                        allowClear: true,
                        width: '100%'
                    });

                    // Current Location dropdown (if enabled for admin)
                    if (!$('#cur_loc').prop('disabled')) {
                        $('#cur_loc').select2({
                            theme: 'bootstrap4',
                            placeholder: 'Select Location',
                            width: '100%'
                        });
                    }

                } else {
                    console.error('Select2 plugin not loaded');
                }
            } catch (error) {
                console.error('Error initializing Select2:', error);
            }

            // Check if session is still valid before form submission
            window.checkSessionAndSubmitForm = function(form) {
                // First check if session is still valid
                $.ajax({
                    url: '{{ route('invoices.check-session') }}',
                    type: 'GET',
                    success: function(response) {
                        // Session is valid, proceed with form submission
                        window.submitFormWithAjax(form);
                    },
                    error: function(xhr) {
                        if (xhr.status === 401 || xhr.status === 419) {
                            // Session expired, redirect to login
                            toastr.error('Your session has expired. Redirecting to login page...');
                            setTimeout(function() {
                                window.location.href = '{{ route('login') }}';
                            }, 1500);
                        } else {
                            // Other error, try regular form submission
                            form.submit();
                        }
                    }
                });
            };

            // Simplified form submission function - NO DUPLICATE GUARD
            window.submitFormWithAjax = function(form) {

                // Validate required fields
                var isValid = true;
                $(form).find('[required]').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Please fill in all required fields.');
                    }
                    return false;
                }

                // Format amount if needed
                if (typeof formatNumber === 'function' && $('#amount_display').length > 0) {
                    formatNumber(document.getElementById('amount_display'));
                }

                // Update submit buttons and show loading animation
                var buttonText = window.saveAction === 'close' ? 'Saving and Closing...' : 'Saving and Creating New...';
                $('#save-and-close-btn, #save-and-new-btn')
                    .prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin"></i> ' + buttonText);

                // Show save status
                $('#save-status').show();

                // Show loading message
                if (typeof toastr !== 'undefined') {
                    toastr.info('Creating invoice...', 'Please wait');
                }

                // Prepare form data
                var formData = new FormData(form);
                var url = $(form).attr('action');
                var method = $(form).attr('method');

                // Submit via AJAX
                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    timeout: 30000,
                    success: function(response) {

                        if (response.success) {
                            // Clear draft
                            localStorage.removeItem('invoice_create_draft');

                            if (window.saveAction === 'close') {
                                // Save and Close: Redirect to index
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('Invoice created successfully. Redirecting...');
                                }
                                setTimeout(function() {
                                    window.location.href = '{{ route('invoices.index') }}';
                                }, 1500);
                            } else {
                                // Save and New: Reset form
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('Invoice created successfully. Preparing new form...');
                                }
                                setTimeout(function() {
                                    resetFormForNewInvoice();
                                }, 1000);
                            }
                        } else {
                            if (typeof toastr !== 'undefined') {
                                toastr.error(response.message || 'Failed to create invoice.');
                            }
                        }
                    },
                    complete: function() {
                        // Reset button states and hide save status
                        $('#save-and-close-btn, #save-and-new-btn')
                            .prop('disabled', false)
                            .html('<i class="fas fa-save"></i> Save and Close')
                            .eq(1).html('<i class="fas fa-plus"></i> Save and New');
                        $('#save-status').hide();
                    },
                    error: function(xhr) {

                        // Reset button states and hide save status on error
                        $('#save-and-close-btn, #save-and-new-btn')
                            .prop('disabled', false)
                            .html('<i class="fas fa-save"></i> Save and Close')
                            .eq(1).html('<i class="fas fa-plus"></i> Save and New');
                        $('#save-status').hide();

                        if (typeof toastr !== 'undefined') {
                            toastr.clear();
                        }

                        if (xhr.status === 401 || xhr.status === 419) {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Session expired. Redirecting to login...');
                            }
                            setTimeout(function() {
                                window.location.href = '{{ route('login') }}';
                            }, 1500);
                        } else if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                $('#' + field).addClass('is-invalid');
                                $('#' + field + '-error').text(messages[0]);
                            });
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Please correct the validation errors.');
                            }
                        } else {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('An error occurred while creating the invoice.');
                            }
                        }
                    }
                });
            }

            // Simplified form submission handler - NO DUPLICATE GUARD
            $('form[action="{{ route('invoices.store') }}"]').on('submit', function(e) {
                e.preventDefault();

                // Simple validation
                var isValid = updateValidationSummary();
                if (!isValid) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Please fix the errors highlighted below', 'Validation Error');
                    }
                    $('html, body').animate({
                        scrollTop: $('.validation-summary').offset().top - 100
                    }, 300);
                    return false;
                }

                // Ensure receive_project has a value
                if (!$('#receive_project').val()) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(
                            'Receive Project is required and should be automatically set to your department project.'
                        );
                    }
                    return false;
                }

                // Direct submission - no session check
                window.submitFormWithAjax(this);
            });

            // Set default dates
            if (!$('#invoice_date').val()) {
                $('#invoice_date').val(new Date().toISOString().split('T')[0]);
            }
            if (!$('#receive_date').val()) {
                $('#receive_date').val(new Date().toISOString().split('T')[0]);
            }

            // Set current location from user's department if available
            @if (auth()->user()->department_location_code)
                if (!$('#cur_loc').val()) {
                    $('#cur_loc').val('{{ auth()->user()->department_location_code }}');
                }
            @endif

            // ENHANCEMENT: Real-time invoice number validation with visual feedback
            var validationTimeout;

            function validateInvoiceNumber() {
                var invoiceNumber = $('#invoice_number').val().trim();
                var supplierId = $('#supplier_id').val();

                // Clear any existing validation indicators
                $('.validation-spinner, .validation-result').remove();
                $('#invoice_number').removeClass('is-valid is-invalid');

                if (invoiceNumber && supplierId) {
                    // Show loading spinner while validating
                    $('#invoice_number').after(
                        '<span class="validation-spinner text-muted small ml-2" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">' +
                        '<i class="fas fa-spinner fa-spin"></i> Checking...</span>'
                    );

                    clearTimeout(validationTimeout);
                    validationTimeout = setTimeout(function() {
                        $.ajax({
                            url: '{{ route('invoices.validate-invoice-number') }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                invoice_number: invoiceNumber,
                                supplier_id: supplierId
                            },
                            success: function(response) {
                                // Remove loading spinner
                                $('.validation-spinner').remove();

                                var invoiceField = $('#invoice_number');
                                var feedback = invoiceField.next('.invalid-feedback');

                                if (response.is_duplicate) {
                                    // Show error state
                                    invoiceField.removeClass('is-valid').addClass('is-invalid');

                                    // Add error icon indicator
                                    invoiceField.after(
                                        '<span class="validation-result text-danger small ml-2" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">' +
                                        '<i class="fas fa-times-circle"></i> Duplicate</span>'
                                    );

                                    // Update or create error message
                                    if (feedback.length === 0) {
                                        invoiceField.after(
                                            '<span class="invalid-feedback d-block">This invoice number is already used for this supplier.</span>'
                                        );
                                    } else {
                                        feedback.text(
                                            'This invoice number is already used for this supplier.'
                                        );
                                    }
                                } else {
                                    // Show success state
                                    invoiceField.removeClass('is-invalid').addClass('is-valid');
                                    feedback.remove();

                                    // Add success icon indicator
                                    invoiceField.after(
                                        '<span class="validation-result text-success small ml-2" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">' +
                                        '<i class="fas fa-check-circle"></i> Available</span>'
                                    );
                                }
                            },
                            error: function() {
                                $('.validation-spinner').remove();
                            }
                        });
                    }, 500); // Debounce validation
                } else if (invoiceNumber && !supplierId) {
                    // Show warning if invoice number entered but no supplier selected
                    $('#invoice_number').after(
                        '<span class="validation-result text-warning small ml-2" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">' +
                        '<i class="fas fa-exclamation-circle"></i> Select supplier first</span>'
                    );
                }
            }

            // Trigger validation when invoice number or supplier changes
            $('#invoice_number').on('input', validateInvoiceNumber);
            $('#supplier_id').on('change', validateInvoiceNumber);

            // ENHANCEMENT: Real-time SAP document validation with visual feedback
            var sapValidationTimeout;

            function validateSapDoc() {
                var sapDoc = $('#sap_doc').val().trim();

                // Clear any existing validation indicators
                $('.sap-validation-spinner, .sap-validation-result').remove();
                $('#sap_doc').removeClass('is-valid is-invalid');

                if (sapDoc.length > 0) {
                    // Show loading spinner while validating
                    $('#sap_doc').after(
                        '<span class="sap-validation-spinner text-muted small ml-2" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">' +
                        '<i class="fas fa-spinner fa-spin"></i> Checking...</span>'
                    );

                    clearTimeout(sapValidationTimeout);
                    sapValidationTimeout = setTimeout(function() {
                        $.ajax({
                            url: '{{ route('invoices.validate-sap-doc') }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                sap_doc: sapDoc,
                                invoice_id: null // Create form has no current invoice
                            },
                            success: function(response) {
                                // Remove loading spinner
                                $('.sap-validation-spinner').remove();

                                var sapField = $('#sap_doc');
                                var feedback = sapField.next('.invalid-feedback');

                                if (!response.valid) {
                                    // Show error state
                                    sapField.removeClass('is-valid').addClass('is-invalid');

                                    // Add error icon indicator
                                    sapField.after(
                                        '<span class="sap-validation-result text-danger small ml-2" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">' +
                                        '<i class="fas fa-times-circle"></i> Duplicate</span>'
                                    );

                                    // Update or create error message
                                    if (feedback.length === 0) {
                                        sapField.after(
                                            '<span class="invalid-feedback d-block">' + response
                                            .message + '</span>'
                                        );
                                    } else {
                                        feedback.text(response.message);
                                    }
                                } else {
                                    // Show success state
                                    sapField.removeClass('is-invalid').addClass('is-valid');
                                    feedback.remove();

                                    // Add success icon indicator
                                    sapField.after(
                                        '<span class="sap-validation-result text-success small ml-2" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">' +
                                        '<i class="fas fa-check-circle"></i> Available</span>'
                                    );
                                }
                            },
                            error: function() {
                                $('.sap-validation-spinner').remove();
                            }
                        });
                    }, 500); // Debounce validation
                } else {
                    // Clear validation if field is empty
                    $('#sap_doc').removeClass('is-invalid is-valid');
                    $('#sap_doc').next('.invalid-feedback').remove();
                }
            }

            // Trigger SAP validation when sap_doc changes
            $('#sap_doc').on('input', validateSapDoc);

            // ENHANCEMENT: Date Field Logical Validation

            // Ensure Receive Date cannot be before Invoice Date
            $('#invoice_date').on('change', function() {
                var invoiceDate = $(this).val();
                var receiveDate = $('#receive_date').val();

                if (invoiceDate) {
                    // Set minimum receive date to invoice date
                    $('#receive_date').attr('min', invoiceDate);

                    // Auto-adjust if receive date is before invoice date
                    if (receiveDate && receiveDate < invoiceDate) {
                        $('#receive_date').val(invoiceDate);
                        toastr.info('Receive date adjusted to match invoice date', 'Date Validation');
                    }

                    // Validate date is not too far in future (max 1 year)
                    var maxDate = new Date();
                    maxDate.setFullYear(maxDate.getFullYear() + 1);
                    var selectedDate = new Date(invoiceDate);

                    if (selectedDate > maxDate) {
                        $(this).val('');
                        toastr.warning('Invoice date cannot be more than 1 year in the future', 'Date Validation');
                    }
                }
            });

            // Validate Receive Date when changed
            $('#receive_date').on('change', function() {
                var receiveDate = $(this).val();
                var invoiceDate = $('#invoice_date').val();

                if (receiveDate && invoiceDate) {
                    // Receive date cannot be before invoice date
                    if (receiveDate < invoiceDate) {
                        $(this).val(invoiceDate);
                        toastr.warning(
                            'Receive date cannot be earlier than invoice date. Auto-adjusted to invoice date.',
                            'Date Validation');
                    }

                    // Validate not too far in future
                    var maxDate = new Date();
                    maxDate.setFullYear(maxDate.getFullYear() + 1);
                    var selectedDate = new Date(receiveDate);

                    if (selectedDate > maxDate) {
                        $(this).val('');
                        toastr.warning('Receive date cannot be more than 1 year in the future', 'Date Validation');
                    }
                }

                // Check payment date if exists
                if (receiveDate && $('#payment_date').val()) {
                    if ($('#payment_date').val() < receiveDate) {
                        $('#payment_date').val('');
                        toastr.warning('Payment date cannot be earlier than receive date.');
                    }
                }
            });

            // Payment date validation
            $('#payment_date').on('change', function() {
                var paymentDate = $(this).val();
                var receiveDate = $('#receive_date').val();
                if (paymentDate && receiveDate && paymentDate < receiveDate) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Payment date cannot be earlier than receive date.');
                    }
                    $(this).val('');
                }
            });

            // Format existing amount value on page load
            @if (old('amount'))
                var oldAmount = '{{ old('amount') }}';
                if (oldAmount && !isNaN(oldAmount)) {
                    formatNumber(document.getElementById('amount_display')); // Format the display field
                    document.getElementById('amount').value = oldAmount; // Populate the hidden field
                }
            @endif

            // Handle cur_loc select enabling/disabling based on user role
            @if (!auth()->user()->hasRole(['superadmin', 'admin']))
                // For non-admin users, ensure cur_loc is set to their department location
                var userLocation = '{{ auth()->user()->department_location_code }}';
                if (userLocation && !$('#cur_loc').val()) {
                    $('#cur_loc').val(userLocation);
                }
            @endif

            // Add hidden input for disabled select to ensure form submission works
            if ($('#cur_loc').prop('disabled')) {
                var hiddenInput = $('<input>').attr({
                    type: 'hidden',
                    name: 'cur_loc',
                    value: $('#cur_loc').val()
                });
                $('#cur_loc').after(hiddenInput);
            }

            // ---------- Additional Documents Linking ----------
            var selectedDocs = {};

            function updateSelectedCounter() {
                var count = Object.keys(selectedDocs).length;
                $('#selected-count').text('Selected: ' + count);
                if (count > 0) {
                    $('#selected-docs-wrapper').show();
                } else {
                    $('#selected-docs-wrapper').hide();
                }
            }

            function renderSelectedTable() {
                var tbody = $('#selected-docs-table tbody');
                tbody.empty();
                Object.values(selectedDocs).forEach(function(doc) {
                    var row = '<tr>' +
                        '<td>' + doc.document_number + '</td>' +
                        '<td>' + (doc.type_name || '-') + '</td>' +
                        '<td>' + (doc.document_date || '-') + '</td>' +
                        '<td>' + (doc.po_no || '-') + '</td>' +
                        '<td><span class="badge badge-secondary">' + (doc.cur_loc || '-') + '</span></td>' +
                        '<td><button type="button" class="btn btn-xs btn-danger remove-doc" data-id="' + doc.id +
                        '"><i class="fas fa-times"></i></button></td>' +
                        '</tr>' +
                        '<input type="hidden" name="additional_document_ids[]" value="' + doc.id + '">';
                    tbody.append(row);
                });
                updateSelectedCounter();
            }

            function renderResultsTable(docs) {
                var tbody = $('#additional-docs-table tbody');
                tbody.empty();
                docs.forEach(function(doc) {
                    var checked = selectedDocs[doc.id] ? 'checked' : '';

                    // Build linked invoices display
                    var linkedInvoicesHtml = '';
                    if (doc.linked_invoices_count > 0) {
                        var badgeClass = doc.linked_invoices_count > 1 ? 'badge-warning' : 'badge-info';
                        var tooltipText = 'Linked to: ' + doc.linked_invoices_list.join(', ');
                        linkedInvoicesHtml = '<span class="badge linked-invoices-badge ' + badgeClass +
                            '" data-toggle="tooltip" title="' + tooltipText + '">' +
                            doc.linked_invoices_count + ' invoice' + (doc.linked_invoices_count > 1 ? 's' : '') +
                            '</span>';
                    } else {
                        linkedInvoicesHtml = '<span class="text-muted">None</span>';
                    }

                    // Build location badge with color coding
                    var locationBadgeClass = 'badge-secondary';
                    var locationTooltip = '';

                    if (doc.is_in_user_department) {
                        locationBadgeClass = 'badge-success';
                        locationTooltip = 'Document is in your department';
                    } else if (doc.cur_loc && doc.cur_loc !== '-') {
                        locationBadgeClass = 'badge-danger';
                        locationTooltip = 'Document is in another department: ' + doc.cur_loc;
                    }

                    var locationHtml = '<span class="badge ' + locationBadgeClass +
                        '" data-toggle="tooltip" title="' + locationTooltip + '">' +
                        (doc.cur_loc || '-') + '</span>';

                    var row = '<tr data-id="' + doc.id + '">' +
                        '<td><input type="checkbox" class="doc-checkbox" data-id="' + doc.id + '" ' + checked +
                        '></td>' +
                        '<td>' + doc.document_number + '</td>' +
                        '<td>' + (doc.type_name || '-') + '</td>' +
                        '<td>' + (doc.document_date || '-') + '</td>' +
                        '<td>' + (doc.po_no || '-') + '</td>' +
                        '<td>' + locationHtml + '</td>' +
                        '<td>' + (doc.remarks || '') + '</td>' +
                        '<td>' + linkedInvoicesHtml + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });

                // Initialize tooltips for the new badges
                $('[data-toggle="tooltip"]').tooltip();

                $('#additional-docs-table-wrapper').toggle(docs.length > 0);
                $('#additional-docs-empty').toggle(docs.length === 0);
                $('#additional-docs-card').show();

                // Show refresh button when we have results
                $('#refresh-docs-btn').toggle(docs.length > 0);
            }

            function searchAdditionalDocuments() {
                var po = $('#po_no').val().trim();
                if (!po) {
                    $('#additional-docs-card').hide();
                    return;
                }

                // ENHANCEMENT: Expand the card if it's collapsed
                if ($('#additional-docs-card').hasClass('collapsed-card')) {
                    $('#additional-docs-card').find('[data-card-widget="collapse"]').click();
                }

                $('#additional-docs-card').show();
                $('#additional-docs-loading').show();
                $('#additional-docs-table-wrapper').hide();
                $('#additional-docs-empty').hide();

                $.ajax({
                    url: '{{ route('invoices.search-additional-documents') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        po_no: po,
                        current_invoice_id: null // Create form has no current invoice
                    },
                    success: function(resp) {
                        $('#additional-docs-loading').hide();
                        if (resp.success) {
                            renderResultsTable(resp.documents || []);
                            renderSelectedTable();
                            if ((resp.documents || []).length === 0 && typeof toastr !== 'undefined') {
                                toastr.info('No additional documents found for this PO.');
                            }
                        } else {
                            if (typeof toastr !== 'undefined') toastr.error('Search failed');
                        }
                    },
                    error: function() {
                        $('#additional-docs-loading').hide();
                        if (typeof toastr !== 'undefined') toastr.error(
                            'Failed to search additional documents');
                    }
                });
            }

            // Handle blur on PO number
            $('#po_no').on('blur', searchAdditionalDocuments);

            // ENHANCEMENT: PO Search Button - Manual search trigger
            $('#search-docs-btn').on('click', function() {
                var po = $('#po_no').val().trim();
                if (!po) {
                    toastr.warning('Please enter a PO number first.');
                    $('#po_no').focus();
                    return;
                }
                searchAdditionalDocuments();
                toastr.info('Searching for documents with PO: ' + po);
            });

            // ENHANCEMENT: PO Suggestions Button - Get PO suggestions based on supplier
            $('#po-suggestions-btn').on('click', function() {
                var supplierId = $('#supplier_id').val();
                if (!supplierId) {
                    toastr.warning('Please select a supplier first to get PO suggestions.');
                    $('#supplier_id').focus();
                    return;
                }
                loadPoSuggestions(supplierId);
            });

            // Allow Enter key to trigger search without leaving field
            $('#po_no').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    var po = $(this).val().trim();
                    if (po) {
                        searchAdditionalDocuments();
                    }
                }
            });

            // ENHANCEMENT: Update currency prefix when currency changes
            $('#currency').on('change', function() {
                var selectedCurrency = $(this).val();
                $('#currency-prefix').text(selectedCurrency);
            });

            // ENHANCEMENT: Smart Field Dependencies - Auto-populate Invoice Project based on Current Location
            $('#cur_loc').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var locationText = selectedOption.text();

                // Extract project code from location text (e.g., "000HACC - Accounting (000H)" -> "000H")
                var projectMatch = locationText.match(/\(([^)]+)\)$/);
                if (projectMatch && projectMatch[1]) {
                    var projectCode = projectMatch[1];

                    // Auto-select invoice project if not already selected
                    if (!$('#invoice_project').val()) {
                        $('#invoice_project').val(projectCode);
                        toastr.info('Invoice Project auto-set to ' + projectCode + ' based on selected location');
                    }
                }
            });

            // ENHANCEMENT: Initialize currency prefix on page load
            var initialCurrency = $('#currency').val();
            if (initialCurrency) {
                $('#currency-prefix').text(initialCurrency);
            }

            // ENHANCEMENT: Initialize tooltips for help icons
            $('[data-toggle="tooltip"]').tooltip({
                trigger: 'hover',
                html: true,
                boundary: 'window'
            });

            // ========== PHASE 1 UX IMPROVEMENTS ==========

            // IMPROVEMENT 1: Supplier-Specific Defaults
            $('#supplier_id').on('change', function() {
                var supplierId = $(this).val();

                if (!supplierId) return;

                $.ajax({
                    url: '/invoices/supplier-defaults/' + supplierId,
                    type: 'GET',
                    success: function(data) {

                        // Auto-suggest currency if not already set
                        if (data.common_currency && !$('#currency').val()) {
                            $('#currency').val(data.common_currency).trigger('change');
                            toastr.info('Currency set to ' + data.common_currency +
                                ' (commonly used with this supplier)', 'Auto-filled');
                        }

                        // Show last invoice type as hint
                        if (data.last_type_name) {
                            var typeHint = $('#type_id').parent().find('.supplier-type-hint');
                            if (typeHint.length === 0) {
                                $('#type_id').parent().append(
                                    '<small class="form-text text-info supplier-type-hint">' +
                                    '<i class="fas fa-info-circle"></i> Last used: <strong>' + data
                                    .last_type_name + '</strong>' +
                                    '</small>'
                                );
                            } else {
                                typeHint.html(
                                    '<i class="fas fa-info-circle"></i> Last used: <strong>' + data
                                    .last_type_name + '</strong>'
                                );
                            }
                        }

                        // Auto-suggest payment project if consistent
                        if (data.common_payment_project && !$('#payment_project').val() && data
                            .total_invoices >= 3) {
                            $('#payment_project').val(data.common_payment_project).trigger('change');
                            toastr.info('Payment project set based on your history with this supplier',
                                'Auto-filled');
                        }
                    },
                    error: function() {}
                });
            });

            // IMPROVEMENT 2: Duplicate Invoice Warning
            var duplicateCheckTimeout = null;

            function checkForDuplicateInvoice() {
                var supplierId = $('#supplier_id').val();
                var fakturNo = $('#faktur_no').val().trim();

                if (!supplierId || !fakturNo) {
                    return;
                }

                // Clear previous timeout
                if (duplicateCheckTimeout) {
                    clearTimeout(duplicateCheckTimeout);
                }

                // Debounce the check
                duplicateCheckTimeout = setTimeout(function() {
                    $.ajax({
                        url: '{{ route('invoices.check-duplicate') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            supplier_id: supplierId,
                            faktur_no: fakturNo
                        },
                        success: function(data) {
                            if (data.exists) {
                                // Show beautiful warning dialog
                                Swal.fire({
                                    title: 'Possible Duplicate Invoice',
                                    html: '<div class="text-left">' +
                                        '<p>An invoice with this Faktur Number already exists for this supplier:</p>' +
                                        '<div class="alert alert-warning mt-2 mb-2">' +
                                        '<table class="table table-sm mb-0">' +
                                        '<tr><th width="40%">Invoice Number:</th><td><strong>' +
                                        data.existing.invoice_number + '</strong></td></tr>' +
                                        '<tr><th>Faktur Number:</th><td><strong>' + data
                                        .existing.faktur_no + '</strong></td></tr>' +
                                        '<tr><th>Date:</th><td>' + data.existing.invoice_date +
                                        '</td></tr>' +
                                        '<tr><th>Amount:</th><td>' + data.existing.currency +
                                        ' ' + data.existing.amount_formatted + '</td></tr>' +
                                        '<tr><th>Status:</th><td><span class="badge badge-info">' +
                                        data.existing.status + '</span></td></tr>' +
                                        '</table>' +
                                        '</div>' +
                                        '<p class="mt-2"><strong>Are you sure you want to continue?</strong></p>' +
                                        '<p class="text-muted small">Note: This might be a duplicate entry. Please verify before proceeding.</p>' +
                                        '</div>',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: '<i class="fas fa-check"></i> Yes, Continue Anyway',
                                    cancelButtonText: '<i class="fas fa-times"></i> Cancel & Review',
                                    confirmButtonColor: '#ffc107',
                                    cancelButtonColor: '#6c757d',
                                    reverseButtons: true,
                                    width: '600px',
                                    customClass: {
                                        confirmButton: 'btn btn-warning btn-lg',
                                        cancelButton: 'btn btn-secondary btn-lg'
                                    }
                                }).then((result) => {
                                    if (!result.isConfirmed) {
                                        // User wants to review - highlight the faktur field
                                        $('#faktur_no').focus().select();
                                    }
                                });
                            }
                        },
                        error: function() {}
                    });
                }, 800); // Debounce 800ms
            }

            // Trigger duplicate check on blur and when both fields are filled
            $('#faktur_no').on('blur', checkForDuplicateInvoice);
            $('#supplier_id').on('change', function() {
                if ($('#faktur_no').val().trim()) {
                    checkForDuplicateInvoice();
                }
            });

            // IMPROVEMENT 3: Validation Summary Panel
            var validationPanel = $(
                '<div class="validation-summary alert alert-danger" style="display:none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 400px; max-width: 600px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">' +
                '<button type="button" class="close" aria-label="Close" onclick="$(this).parent().fadeOut()">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '<h5><i class="fas fa-exclamation-triangle"></i> Please Fix These Errors:</h5>' +
                '<ul id="validation-error-list" class="mb-0 pl-3"></ul>' +
                '</div>');

            $('body').append(validationPanel);

            function updateValidationSummary() {
                var errors = [];
                var errorList = $('#validation-error-list');

                // Check all required fields
                $('[required]:visible').each(function() {
                    var field = $(this);
                    var value = field.val();
                    var label = field.closest('.form-group').find('label').first().clone().children().remove().end()
                        .text().trim();

                    if (!value || value === '' || value === null) {
                        errors.push({
                            field: field,
                            label: label || field.attr('name'),
                            message: label + ' is required'
                        });
                    }
                });

                // Check for validation errors (is-invalid class)
                $('.is-invalid:visible').each(function() {
                    var field = $(this);
                    var label = field.closest('.form-group').find('label').first().clone().children().remove().end()
                        .text().trim();
                    var errorMsg = field.siblings('.invalid-feedback').text();

                    if (errorMsg) {
                        errors.push({
                            field: field,
                            label: label || field.attr('name'),
                            message: errorMsg
                        });
                    }
                });

                if (errors.length > 0) {
                    errorList.empty();
                    errors.forEach(function(error, index) {
                        var li = $('<li style="cursor: pointer; margin-bottom: 5px;"></li>')
                            .html('<i class="fas fa-arrow-right mr-1"></i> ' + error.message)
                            .on('click', function() {
                                error.field.focus();
                                $('html, body').animate({
                                    scrollTop: error.field.offset().top - 100
                                }, 300);
                            });
                        errorList.append(li);
                    });

                    $('.validation-summary').fadeIn();
                    return false;
                } else {
                    $('.validation-summary').fadeOut();
                    return true;
                }
            }

            // Update validation summary on field changes
            $('form :input').on('blur change', function() {
                // Only show summary if user has tried to interact with form
                if ($(this).val() || $('.is-invalid').length > 0) {
                    updateValidationSummary();
                }
            });

            // Validate before form submission - REMOVED DUPLICATE HANDLER
            // This validation is now handled in the main form submission handler

            // ========== END PHASE 1 UX IMPROVEMENTS ==========

            // ========== PHASE 2 UX IMPROVEMENTS ==========

            // IMPROVEMENT 1: Quick Fill from Recent Invoices
            var recentInvoicesData = [];

            // Load recent invoices on page load
            $.ajax({
                url: '{{ route('invoices.recent-for-autofill') }}',
                type: 'GET',
                success: function(data) {
                    if (data.success && data.invoices.length > 0) {
                        recentInvoicesData = data.invoices;

                        data.invoices.forEach(function(inv) {
                            $('#recent-invoices').append(
                                $('<option></option>')
                                .val(inv.id)
                                .text(inv.faktur_no ? inv.faktur_no + ' - ' + inv.supplier_name +
                                    ' (' + inv.created_at + ')' :
                                    inv.invoice_number + ' - ' + inv.supplier_name + ' (' + inv
                                    .created_at + ')')
                                .data('invoice', inv)
                            );
                        });

                        // Initialize Select2 for recent invoices
                        $('#recent-invoices').select2({
                            theme: 'bootstrap4',
                            placeholder: 'Select from your recent invoices...',
                            allowClear: true,
                            width: '100%'
                        });
                    } else {
                        $('#recent-invoices').parent().parent().parent().parent().hide();
                    }
                },
                error: function() {
                    $('#recent-invoices').parent().parent().parent().parent().hide();
                }
            });

            // Handle recent invoice selection
            $('#recent-invoices').on('change', function() {
                var selectedId = $(this).val();
                if (!selectedId) return;

                var invoice = $(this).find('option:selected').data('invoice');
                if (!invoice) return;

                Swal.fire({
                    title: 'Quick Fill from Recent Invoice',
                    html: '<div class="text-left">' +
                        '<p>Auto-fill form with data from:</p>' +
                        '<div class="alert alert-info mt-2 mb-2">' +
                        '<table class="table table-sm mb-0">' +
                        '<tr><th width="40%">Invoice:</th><td><strong>' + invoice.invoice_number +
                        '</strong></td></tr>' +
                        '<tr><th>Supplier:</th><td>' + invoice.supplier_name + '</td></tr>' +
                        '<tr><th>Type:</th><td>' + invoice.type_name + '</td></tr>' +
                        '<tr><th>Currency:</th><td>' + invoice.currency + '</td></tr>' +
                        '<tr><th>Amount:</th><td>' + invoice.currency + ' ' + invoice.amount_formatted +
                        '</td></tr>' +
                        '</table>' +
                        '</div>' +
                        '<p class="mt-2"><strong>Note:</strong> Dates and amounts will NOT be copied. Only supplier, type, currency, and projects will be filled.</p>' +
                        '</div>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check"></i> Yes, Auto-Fill',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#17a2b8',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    width: '600px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Auto-fill fields
                        $('#supplier_id').val(invoice.supplier_id).trigger('change');
                        $('#type_id').val(invoice.type_id).trigger('change');
                        $('#currency').val(invoice.currency).trigger('change');
                        $('#invoice_project').val(invoice.invoice_project).trigger('change');
                        $('#payment_project').val(invoice.payment_project).trigger('change');

                        // Show success message
                        toastr.success(
                            'Form auto-filled from recent invoice. Please update dates and amounts.',
                            'Quick Fill Complete');

                        // Focus on invoice number field
                        setTimeout(function() {
                            $('#faktur_no').focus();
                        }, 500);
                    }

                    // Reset dropdown
                    $('#recent-invoices').val('').trigger('change');
                });
            });

            // IMPROVEMENT 2: Amount Calculator Widget
            $('#calculator-btn').on('click', function() {
                var currentAmount = parseFloat($('#amount').val()) || 0;

                Swal.fire({
                    title: '<i class="fas fa-calculator"></i> Quick Calculator',
                    html: '<div class="calculator-widget">' +
                        '<div class="form-group">' +
                        '<label>Base Amount:</label>' +
                        '<input type="text" id="calc-base" class="form-control form-control-lg text-right" value="' +
                        currentAmount.toFixed(2) + '">' +
                        '</div>' +
                        '<div class="row mb-3">' +
                        '<div class="col-4"><button type="button" class="btn btn-block btn-success calc-action" data-action="add" data-value="10">+10%</button></div>' +
                        '<div class="col-4"><button type="button" class="btn btn-block btn-warning calc-action" data-action="subtract" data-value="10">-10%</button></div>' +
                        '<div class="col-4"><button type="button" class="btn btn-block btn-info calc-action" data-action="add" data-value="11">+11% (VAT)</button></div>' +
                        '</div>' +
                        '<div class="row mb-3">' +
                        '<div class="col-4"><button type="button" class="btn btn-block btn-success calc-action" data-action="multiply" data-value="2">×2</button></div>' +
                        '<div class="col-4"><button type="button" class="btn btn-block btn-success calc-action" data-action="divide" data-value="2">÷2</button></div>' +
                        '<div class="col-4"><button type="button" class="btn btn-block btn-secondary" id="calc-clear">Clear</button></div>' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label>Result:</label>' +
                        '<input type="text" id="calc-result" class="form-control form-control-lg text-right font-weight-bold" readonly style="background-color: #e9ecef; font-size: 1.25rem;">' +
                        '</div>' +
                        '</div>',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check"></i> Use This Amount',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    width: '500px',
                    didOpen: function() {
                        // Initialize calculator
                        $('#calc-result').val(currentAmount.toFixed(2));

                        // Calculate button actions
                        $('.calc-action').on('click', function() {
                            var base = parseFloat($('#calc-base').val()) || 0;
                            var action = $(this).data('action');
                            var value = parseFloat($(this).data('value'));
                            var result = 0;

                            switch (action) {
                                case 'add':
                                    result = base + (base * value / 100);
                                    break;
                                case 'subtract':
                                    result = base - (base * value / 100);
                                    break;
                                case 'multiply':
                                    result = base * value;
                                    break;
                                case 'divide':
                                    result = base / value;
                                    break;
                            }

                            $('#calc-result').val(result.toFixed(2));
                        });

                        // Clear button
                        $('#calc-clear').on('click', function() {
                            $('#calc-base').val('0.00');
                            $('#calc-result').val('0.00');
                        });

                        // Update result when base changes
                        $('#calc-base').on('input', function() {
                            var base = parseFloat($(this).val()) || 0;
                            $('#calc-result').val(base.toFixed(2));
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        var calculatedAmount = parseFloat($('#calc-result').val()) || 0;
                        $('#amount').val(calculatedAmount);
                        $('#amount_display').val(calculatedAmount.toFixed(2)).trigger('input');
                        toastr.success('Amount updated from calculator', 'Amount Set');
                    }
                });
            });

            // IMPROVEMENT 3: Invoice Preview Before Submit
            $('#preview-invoice-btn').on('click', function() {
                // Validate required fields first
                var requiredFilled = true;
                var missingFields = [];

                $('[required]:visible').each(function() {
                    if (!$(this).val() || $(this).val() === '') {
                        requiredFilled = false;
                        var label = $(this).closest('.form-group').find('label').first().clone().children()
                            .remove().end().text().trim();
                        missingFields.push(label);
                    }
                });

                if (!requiredFilled) {
                    toastr.warning('Please fill all required fields before previewing', 'Cannot Preview');
                    return;
                }

                // Generate preview HTML
                var previewHTML = '<div class="invoice-preview text-left">' +
                    '<table class="table table-bordered table-sm">' +
                    '<thead class="bg-light"><tr><th colspan="2" class="text-center"><h5 class="mb-0">Invoice Information</h5></th></tr></thead>' +
                    '<tbody>' +
                    '<tr><th width="35%"><i class="fas fa-building"></i> Supplier:</th><td><strong>' + $(
                        '#supplier_id option:selected').text() + '</strong></td></tr>' +
                    '<tr><th><i class="fas fa-file-invoice"></i> Invoice Number:</th><td><strong>' + $('#faktur_no')
                    .val() + '</strong></td></tr>' +
                    '<tr><th><i class="fas fa-calendar"></i> Invoice Date:</th><td>' + $('#invoice_date').val() +
                    '</td></tr>' +
                    '<tr><th><i class="fas fa-calendar-check"></i> Receive Date:</th><td>' + $('#receive_date')
                    .val() + '</td></tr>' +
                    '<tr><th><i class="fas fa-tag"></i> Type:</th><td>' + $('#type_id option:selected').text() +
                    '</td></tr>' +
                    '<tr><th><i class="fas fa-money-bill-wave"></i> Currency:</th><td>' + $('#currency').val() +
                    '</td></tr>' +
                    '<tr><th><i class="fas fa-dollar-sign"></i> Amount:</th><td class="text-right"><strong class="text-success" style="font-size: 1.1rem;">' +
                    $('#currency').val() + ' ' + $('#amount_display').val() + '</strong></td></tr>' +
                    '<tr><th><i class="fas fa-project-diagram"></i> Invoice Project:</th><td>' + $(
                        '#invoice_project option:selected').text() + '</td></tr>' +
                    '<tr><th><i class="fas fa-credit-card"></i> Payment Project:</th><td>' + $(
                        '#payment_project option:selected').text() + '</td></tr>' +
                    '<tr><th><i class="fas fa-map-marker-alt"></i> Current Location:</th><td>' + $(
                        '#cur_loc option:selected').text() + '</td></tr>';

                // Add optional fields if filled
                if ($('#po_no').val()) {
                    previewHTML += '<tr><th><i class="fas fa-file-alt"></i> PO Number:</th><td>' + $('#po_no')
                        .val() + '</td></tr>';
                }
                if ($('#faktur_no').val()) {
                    previewHTML += '<tr><th><i class="fas fa-receipt"></i> Faktur No:</th><td>' + $('#faktur_no')
                        .val() + '</td></tr>';
                }
                if ($('#sap_doc').val()) {
                    previewHTML += '<tr><th><i class="fas fa-barcode"></i> SAP Document:</th><td>' + $('#sap_doc')
                        .val() + '</td></tr>';
                }
                if ($('#remarks').val()) {
                    previewHTML += '<tr><th><i class="fas fa-comment"></i> Remarks:</th><td>' + $('#remarks')
                        .val() + '</td></tr>';
                }

                // Add selected documents count
                var selectedCount = Object.keys(selectedDocs).length;
                if (selectedCount > 0) {
                    previewHTML +=
                        '<tr><th><i class="fas fa-link"></i> Linked Documents:</th><td><span class="badge badge-info">' +
                        selectedCount + ' document(s)</span></td></tr>';
                }

                previewHTML += '</tbody></table></div>';

                Swal.fire({
                    title: '<i class="fas fa-eye"></i> Invoice Preview',
                    html: previewHTML,
                    width: '700px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check-circle"></i> Looks Good, Submit!',
                    cancelButtonText: '<i class="fas fa-edit"></i> Edit Invoice',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-success btn-lg',
                        cancelButton: 'btn btn-secondary btn-lg'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit the form
                        $('form[action="{{ route('invoices.store') }}"]').submit();
                    } else if (result.isDismissed) {
                        // User wants to edit
                        toastr.info('Review and update your invoice as needed', 'Edit Mode');
                    }
                });
            });

            // ========== END PHASE 2 UX IMPROVEMENTS ==========

            // ENHANCEMENT: Keyboard Shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl+S to submit form
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();

                    // Check if form has required fields filled
                    var requiredFilled = true;
                    $('[required]:visible').each(function() {
                        if (!$(this).val() || $(this).val() === '') {
                            requiredFilled = false;
                            return false;
                        }
                    });

                    if (requiredFilled) {
                        $('form[action="{{ route('invoices.store') }}"]').submit();
                    } else {
                        toastr.warning('Please complete all required fields first', 'Cannot Save');
                    }
                }

                // Escape to cancel and return to list
                if (e.key === 'Escape' && !$('.modal').hasClass('show') && !$('.swal2-container').length) {
                    e.preventDefault();
                    window.location.href = '{{ route('invoices.index') }}';
                }
            });

            // Ctrl+Enter in PO field to trigger search
            $('#po_no').on('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    $('#search-docs-btn').click();
                }
            });

            // ENHANCEMENT: Form Progress Indicator
            function updateFormProgress() {
                var requiredFields = $('[required]:visible').not('#receive_project'); // Exclude auto-filled
                var filledFields = requiredFields.filter(function() {
                    var val = $(this).val();
                    return val !== '' && val !== null && val.toString().trim() !== '';
                });

                var total = requiredFields.length;
                var filled = filledFields.length;
                var percentage = total > 0 ? Math.round((filled / total) * 100) : 0;

                // Update progress bar
                $('#form-progress-bar')
                    .css('width', percentage + '%')
                    .attr('aria-valuenow', percentage)
                    .text(percentage + '%')
                    .removeClass('bg-danger bg-warning bg-success')
                    .addClass(
                        percentage < 40 ? 'bg-danger' :
                        percentage < 80 ? 'bg-warning' :
                        'bg-success'
                    );

                // Add animation when progress increases
                if (percentage === 100) {
                    $('#form-progress-bar').addClass('progress-bar-animated');
                }

                $('#progress-text').text(filled + '/' + total + ' required fields completed');
            }

            // Update progress on any field change
            $('form :input').on('change input blur', updateFormProgress);

            // Initial progress update
            setTimeout(updateFormProgress, 1000);

            // SIMPLIFIED: Basic state management
            window.saveAction = 'close'; // Default action

            // Function to clear all validation states and cached errors
            function clearAllValidationStates() {
                // Clear validation classes
                $('.is-invalid, .is-valid').removeClass('is-invalid is-valid');

                // Remove all validation result elements
                $('.validation-result, .validation-spinner, .sap-validation-result, .sap-validation-spinner').remove();

                // Remove any error messages
                $('.invalid-feedback, .validation-error').remove();

                // Clear any cached validation data
                if (window.validationCache) {
                    window.validationCache = {};
                }

            }

            // Call clearAllValidationStates on page load
            clearAllValidationStates();

            // REMOVED: isSubmitting flag management - no longer needed

            // Function to reset form for new invoice
            function resetFormForNewInvoice() {

                // 1. Clear all validation states first
                clearAllValidationStates();

                // 2. Reset form fields using native reset
                $('form')[0].reset();

                // 3. Clear Select2 dropdowns properly
                $('#supplier_id').val(null).trigger('change');
                $('#invoice_type').val(null).trigger('change');
                $('#invoice_project').val(null).trigger('change');
                $('#payment_project').val(null).trigger('change');
                $('#cur_loc').val(null).trigger('change');

                // 4. Reset custom fields
                $('#amount_display').val('0.00');
                $('#amount').val('');
                $('#invoice_number').val('');
                $('#po_no').val('');
                $('#faktur_no').val('');
                $('#sap_doc').val('');
                $('#remarks').val('');

                // 5. Set default dates
                var today = new Date().toISOString().split('T')[0];
                $('#invoice_date').val(today);
                $('#receive_date').val(today);

                // 6. Set current location from user's department if available
                @if (auth()->user()->department_location_code)
                    $('#cur_loc').val('{{ auth()->user()->department_location_code }}').trigger('change');
                @endif

                // 7. Reset currency to IDR
                $('#currency').val('IDR');

                // 8. Clear any selected additional documents
                selectedDocs = {};
                $('#selected-count').text('Selected: 0');

                // 9. Reset save action to default
                window.saveAction = 'close';

                // 10. Update form progress indicator
                updateFormProgress();

                // 11. Focus on invoice number field
                $('#invoice_number').focus();

                // 12. Show success message
                if (typeof toastr !== 'undefined') {
                    toastr.info('Form reset. You can now create a new invoice.');
                }

            }

            // Handle Save and Close button - SIMPLIFIED
            $('#save-and-close-btn').on('click', function(e) {
                window.saveAction = 'close';
            });

            // Handle Save and New button - SIMPLIFIED
            $('#save-and-new-btn').on('click', function(e) {
                window.saveAction = 'new';
            });

            // REMOVED: Complex function override with duplicate guard
            // Now using direct submission without guards

            // ENHANCEMENT: Row checkbox toggle with SweetAlert2 warning for linked documents
            $(document).on('change', '.doc-checkbox', function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                var checkbox = this;

                if (this.checked) {
                    // Check if document is already linked to other invoices
                    var linkedInvoicesCell = row.find('td').eq(7); // Linked Invoices column
                    var linkedBadge = linkedInvoicesCell.find('.badge');

                    if (linkedBadge.length > 0 && !linkedBadge.hasClass('text-muted')) {
                        var badgeText = linkedBadge.text().trim();
                        var countMatch = badgeText.match(/\d+/);

                        if (countMatch) {
                            var count = parseInt(countMatch[0]);

                            if (count > 0) {
                                // Show SweetAlert2 confirmation for already-linked documents
                                var docNumber = row.find('td').eq(1).text();
                                var linkedInvoicesList = linkedBadge.attr('title') || linkedBadge.data(
                                    'original-title') || '';
                                linkedInvoicesList = linkedInvoicesList.replace('Linked to: ', '');

                                Swal.fire({
                                    title: 'Document Already Linked',
                                    html: '<div class="text-left">' +
                                        '<p>This document (<strong>' + docNumber +
                                        '</strong>) is already linked to <strong>' + count +
                                        '</strong> other invoice(s):</p>' +
                                        '<div class="alert alert-warning mt-2 mb-2">' +
                                        '<i class="fas fa-link"></i> <strong>Currently linked to:</strong><br>' +
                                        '<span class="small">' + linkedInvoicesList + '</span>' +
                                        '</div>' +
                                        '<p class="mt-3">Linking this document to multiple invoices is allowed, but please confirm you want to proceed.</p>' +
                                        '</div>',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: '<i class="fas fa-link"></i> Yes, Link Anyway',
                                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                                    confirmButtonColor: '#ffc107',
                                    cancelButtonColor: '#6c757d',
                                    reverseButtons: true,
                                    width: '600px'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // User confirmed, proceed with adding to selectedDocs
                                        var data = {
                                            id: id,
                                            document_number: row.find('td').eq(1).text(),
                                            type_name: row.find('td').eq(2).text(),
                                            document_date: row.find('td').eq(3).text(),
                                            po_no: row.find('td').eq(4).text(),
                                            cur_loc: row.find('td').eq(5).text().trim(),
                                            remarks: row.find('td').eq(6).text()
                                        };
                                        selectedDocs[id] = data;
                                        renderSelectedTable();
                                        toastr.success('Document linked successfully', 'Document Added');
                                    } else {
                                        // User cancelled, uncheck the checkbox
                                        $(checkbox).prop('checked', false);
                                    }
                                });

                                return; // Exit early, SweetAlert will handle the rest
                            }
                        }
                    }

                    // If not linked or no warning needed, proceed normally
                    var data = {
                        id: id,
                        document_number: row.find('td').eq(1).text(),
                        type_name: row.find('td').eq(2).text(),
                        document_date: row.find('td').eq(3).text(),
                        po_no: row.find('td').eq(4).text(),
                        cur_loc: row.find('td').eq(5).text().trim(),
                        remarks: row.find('td').eq(6).text()
                    };
                    selectedDocs[id] = data;
                    renderSelectedTable();
                } else {
                    delete selectedDocs[id];
                    renderSelectedTable();
                }
            });

            // Select all
            $(document).on('change', '#select-all-docs', function() {
                var checked = this.checked;
                $('#additional-docs-table tbody .doc-checkbox').each(function() {
                    $(this).prop('checked', checked).trigger('change');
                });
            });

            // Remove from selected list
            $(document).on('click', '.remove-doc', function() {
                var id = $(this).data('id');
                delete selectedDocs[id];
                $('#additional-docs-table tbody .doc-checkbox[data-id="' + id + '"]').prop('checked', false);
                renderSelectedTable();
            });

            // Refresh additional documents table
            $(document).on('click', '#refresh-docs-btn', function() {
                var po = $('#po_no').val().trim();
                if (po) {
                    // Clear current selections before refreshing
                    selectedDocs = {};
                    renderSelectedTable();

                    // Re-run the search
                    searchAdditionalDocuments();

                    if (typeof toastr !== 'undefined') {
                        toastr.info('Refreshing additional documents...');
                    }
                }
            });

            // ---------- On-the-fly Additional Document Creation ----------

            // Initialize modal functionality (feature working successfully)

            // Open create document modal
            $(document).on('click', '#create-doc-btn', function() {
                var modal = $('#create-doc-modal');
                var form = $('#create-doc-form');

                // Pre-fill PO number if available
                var po = $('#po_no').val().trim();
                if (po) {
                    $('#doc_po_no').val(po);
                }

                // Reset form
                if (form[0] && typeof form[0].reset === 'function') {
                    form[0].reset();
                    // Re-fill PO number after reset
                    if (po) {
                        $('#doc_po_no').val(po);
                    }
                }

                // Show modal
                modal.modal('show');
            });

            // Handle form submission (use more flexible selector)
            $(document).on('submit', '#create-doc-form, #create-doc-modal form', function(e) {
                e.preventDefault();

                var submitBtn = $('#create-doc-submit');
                var originalText = submitBtn.html();

                // Disable submit button and show loading
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

                // Get form data
                var formData = {
                    _token: '{{ csrf_token() }}',
                    document_type_id: $('#doc_type_id').val(),
                    document_number: $('#doc_number').val(),
                    document_date: $('#doc_date').val(),
                    document_receive_date: $('#doc_receive_date').val(),
                    cur_loc: $('#doc_cur_loc').val(),
                    po_no: $('#doc_po_no').val(),
                    project: $('#doc_project').val()
                };

                // Validate required fields
                if (!formData.document_type_id || !formData.document_number || !formData.cur_loc) {
                    toastr.error('Please fill in all required fields.');
                    submitBtn.prop('disabled', false).html(originalText);
                    return false;
                }

                $.ajax({
                    url: '{{ route('additional-documents.on-the-fly') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            toastr.success(response.message);

                            // Close modal
                            $('#create-doc-modal').modal('hide');

                            // Auto-select the newly created document
                            var newDoc = response.document;
                            selectedDocs[newDoc.id] = {
                                id: newDoc.id,
                                document_number: newDoc.document_number,
                                type_name: newDoc.document_type,
                                document_date: newDoc.document_date || '-',
                                po_no: newDoc.po_no || '-',
                                cur_loc: newDoc.cur_loc,
                                remarks: '-'
                            };

                            // Refresh the additional documents table to show the new document
                            searchAdditionalDocuments();

                            // Update selected count
                            renderSelectedTable();

                            // Show info message about auto-attachment
                            toastr.info(
                                'The newly created document has been automatically selected and will be attached to this invoice.'
                            );

                        } else {
                            toastr.error(response.message || 'Failed to create document.');
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Failed to create document.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        // Re-enable submit button
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // ---------- End On-the-fly Additional Document Creation ----------
            // ---------- End Additional Documents Linking ----------

            // Ensure receive_project is properly set and readonly
            var userProject = '{{ auth()->user()->project ?? '' }}';
            if (userProject) {
                $('#receive_project').val(userProject);
            }

            // Add form submission validation - REMOVED DUPLICATE HANDLER
            // This validation is now handled in the main form submission handler

            // ENHANCEMENT: PO Suggestions Function
            function loadPoSuggestions(supplierId) {
                // First get the supplier's SAP code
                $.ajax({
                    url: '/suppliers/' + supplierId,
                    method: 'GET',
                    success: function(supplierResponse) {
                        if (supplierResponse.success && supplierResponse.data.sap_code) {
                            var sapCode = supplierResponse.data.sap_code;

                            // Now get PO suggestions based on SAP code
                            $.ajax({
                                url: '/suppliers/po-suggestions',
                                method: 'POST',
                                data: {
                                    vendor_code: sapCode,
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(response) {
                                    if (response.success && response.data.suggestions.length > 0) {
                                        showPoSuggestionsModal(response.data.suggestions, sapCode);
                                    } else {
                                        toastr.info(
                                            'No PO suggestions found for supplier SAP code: ' +
                                            sapCode);
                                    }
                                },
                                error: function() {
                                    toastr.error('Failed to load PO suggestions');
                                }
                            });
                        } else {
                            toastr.warning('Selected supplier has no SAP code. Cannot provide PO suggestions.');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to get supplier information');
                    }
                });
            }

            function showPoSuggestionsModal(suggestions, sapCode) {
                let modalHtml = `
                    <div class="modal fade" id="po-suggestions-modal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-lightbulb mr-2"></i>
                                        PO Suggestions for SAP Code: ${sapCode}
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-info-circle"></i>
                                        Select a PO number to auto-fill the PO field
                                    </p>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>PO Number</th>
                                                    <th>Document Number</th>
                                                    <th>Document Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                `;

                suggestions.forEach(function(suggestion) {
                    modalHtml += `
                        <tr>
                            <td><code>${suggestion.po_no}</code></td>
                            <td>${suggestion.document_number}</td>
                            <td>${suggestion.document_date}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary select-po-suggestion" 
                                    data-po-no="${suggestion.po_no}">
                                    <i class="fas fa-check"></i> Select
                                </button>
                            </td>
                        </tr>
                    `;
                });

                modalHtml += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remove existing modal if any
                $('#po-suggestions-modal').remove();

                // Add modal to body
                $('body').append(modalHtml);

                // Show modal
                $('#po-suggestions-modal').modal('show');

                // Handle PO selection
                $('.select-po-suggestion').click(function() {
                    const poNo = $(this).data('po-no');
                    $('#po_no').val(poNo);
                    $('#po-suggestions-modal').modal('hide');
                    toastr.success('PO number set to: ' + poNo);

                    // Auto-trigger search for additional documents
                    setTimeout(function() {
                        searchAdditionalDocuments();
                    }, 500);
                });
            }

            // ENHANCEMENT: Auto-save Draft Feature - DISABLED
            // var DRAFT_KEY = 'invoice_create_draft';
            // var AUTO_SAVE_INTERVAL = 30000; // 30 seconds

            // Function to save current form data to localStorage - DISABLED
            function saveDraft() {
                // Auto-save feature disabled
                return;
            }

            // Function to restore draft from localStorage
            function restoreDraft() {
                var draftJson = localStorage.getItem(DRAFT_KEY);
                if (!draftJson) return;

                try {
                    var draft = JSON.parse(draftJson);
                    var draftDate = new Date(draft.timestamp);
                    var minutesAgo = Math.round((new Date() - draftDate) / 60000);

                    // Ask user if they want to restore
                    Swal.fire({
                        title: 'Draft Found',
                        html: 'Found an unsaved invoice draft from ' + minutesAgo +
                            ' minutes ago.<br>Do you want to restore it?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Restore Draft',
                        cancelButtonText: 'No, Start Fresh',
                        confirmButtonColor: '#007bff',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Restore form fields
                            if (draft.supplier_id) $('#supplier_id').val(draft.supplier_id).trigger('change');
                            if (draft.invoice_number) $('#invoice_number').val(draft.invoice_number);
                            if (draft.invoice_date) $('#invoice_date').val(draft.invoice_date);
                            if (draft.receive_date) $('#receive_date').val(draft.receive_date);
                            if (draft.po_no) $('#po_no').val(draft.po_no);
                            if (draft.type_id) $('#type_id').val(draft.type_id);
                            if (draft.currency) {
                                $('#currency').val(draft.currency).trigger('change');
                            }
                            if (draft.amount) {
                                $('#amount').val(draft.amount);
                                $('#amount_display').val(draft.amount);
                                formatNumber(document.getElementById('amount_display'));
                            }
                            if (draft.invoice_project) $('#invoice_project').val(draft.invoice_project);
                            if (draft.payment_project) $('#payment_project').val(draft.payment_project);
                            if (draft.faktur_no) $('#faktur_no').val(draft.faktur_no);
                            if (draft.sap_doc) $('#sap_doc').val(draft.sap_doc);
                            if (draft.remarks) $('#remarks').val(draft.remarks);

                            toastr.success('Draft restored successfully!');

                            // If PO number exists, trigger search for additional documents
                            if (draft.po_no) {
                                setTimeout(function() {
                                    searchAdditionalDocuments();
                                }, 500);
                            }
                        } else {
                            // User chose to start fresh, clear the draft
                            localStorage.removeItem(DRAFT_KEY);
                            toastr.info('Starting with a fresh form');
                        }
                    });
                } catch (error) {
                    console.error('Error restoring draft:', error);
                    localStorage.removeItem(DRAFT_KEY);
                }
            }

            // Auto-save every 30 seconds - DISABLED
            // var autoSaveTimer = setInterval(saveDraft, AUTO_SAVE_INTERVAL);

            // Save draft when user makes changes (debounced) - DISABLED
            // var saveTimeout;
            // $('form :input').on('change input', function() {
            //     clearTimeout(saveTimeout);
            //     saveTimeout = setTimeout(saveDraft, 2000); // Save 2 seconds after last change
            // });

            // Clear draft on successful submission - DISABLED
            // var originalAjaxSuccess = submitFormWithAjax;

            // Restore draft on page load - DISABLED
            // setTimeout(restoreDraft, 1000);

            // Clear draft when form is successfully submitted - DISABLED
            // $(document).on('invoice-created-success', function() {
            //     localStorage.removeItem(DRAFT_KEY);
            //     clearInterval(autoSaveTimer);
            // });

            // ENHANCEMENT: Manual save draft button - DISABLED
        }

        // Start initialization when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeInvoiceForm);
        } else {
            initializeInvoiceForm();
        }
    </script>
@endsection
