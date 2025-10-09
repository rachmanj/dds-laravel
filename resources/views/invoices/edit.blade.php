@extends('layouts.main')

@section('title_page')
    Edit Invoice - {{ $invoice->invoice_number }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
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

        /* Validation Summary Panel */
        .validation-summary-panel {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #dc3545;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1050;
            max-width: 90%;
            min-width: 300px;
            display: none;
        }

        .validation-summary-panel.show {
            display: block;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .validation-summary-panel h6 {
            margin: 0 0 10px 0;
            font-weight: bold;
        }

        .validation-summary-panel ul {
            margin: 0;
            padding-left: 20px;
        }

        .validation-summary-panel li {
            margin-bottom: 5px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .validation-summary-panel li:hover {
            color: #ffc107;
        }

        /* Form Progress Indicator */
        .form-progress-container {
            margin-bottom: 20px;
        }

        .form-progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .form-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #dc3545 0%, #ffc107 50%, #28a745 100%);
            transition: width 0.3s ease;
            border-radius: 4px;
        }

        .form-progress-text {
            text-align: center;
            margin-top: 5px;
            font-size: 0.9em;
            color: #6c757d;
        }

        /* Amount Calculator Widget */
        .amount-calculator {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
        }

        .amount-calculator.show {
            display: block;
        }

        .calculator-input-group {
            margin-bottom: 15px;
        }

        .calculator-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }

        .calculator-result {
            background: #f8f9fa;
            border: 2px solid #28a745;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 15px;
        }

        .calculator-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        /* Enhanced submit button */
        .btn-update-enhanced {
            position: relative;
            overflow: hidden;
        }

        .btn-update-enhanced .btn-text {
            transition: opacity 0.3s;
        }

        .btn-update-enhanced .btn-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .btn-update-enhanced.loading .btn-text {
            opacity: 0;
        }

        .btn-update-enhanced.loading .btn-spinner {
            opacity: 1;
        }
    </style>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Edit Invoice: {{ $invoice->invoice_number }}</h3>
                            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-info float-right">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <form action="{{ route('invoices.update', $invoice) }}" method="POST" id="invoice-edit-form">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <!-- Form Progress Indicator -->
                                <div class="form-progress-container">
                                    <div class="form-progress-bar">
                                        <div class="form-progress-fill" id="form-progress-fill" style="width: 0%"></div>
                                    </div>
                                    <div class="form-progress-text" id="form-progress-text">Form Progress: 0% Complete</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_number">Invoice Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('invoice_number') is-invalid @enderror"
                                                id="invoice_number" name="invoice_number"
                                                value="{{ old('invoice_number', $invoice->invoice_number) }}" required>
                                            @error('invoice_number')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="faktur_no">Faktur No</label>
                                            <input type="text"
                                                class="form-control @error('faktur_no') is-invalid @enderror" id="faktur_no"
                                                name="faktur_no" value="{{ old('faktur_no', $invoice->faktur_no) }}">
                                            @error('faktur_no')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_date">Invoice Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('invoice_date') is-invalid @enderror"
                                                id="invoice_date" name="invoice_date"
                                                value="{{ old('invoice_date', $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '') }}"
                                                required>
                                            @error('invoice_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="receive_date">Receive Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('receive_date') is-invalid @enderror"
                                                id="receive_date" name="receive_date"
                                                value="{{ old('receive_date', $invoice->receive_date ? $invoice->receive_date->format('Y-m-d') : '') }}"
                                                required>
                                            @error('receive_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

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
                                                        {{ old('supplier_id', $invoice->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
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
                                            <label for="type_id">Invoice Type <span class="text-danger">*</span></label>
                                            <select class="form-control @error('type_id') is-invalid @enderror"
                                                id="type_id" name="type_id" required>
                                                <option value="">Select Invoice Type</option>
                                                @foreach ($invoiceTypes as $type)
                                                    <option value="{{ $type->id }}"
                                                        {{ old('type_id', $invoice->type_id) == $type->id ? 'selected' : '' }}>
                                                        {{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('type_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="po_no">PO Number</label>
                                            <input type="text" class="form-control @error('po_no') is-invalid @enderror"
                                                id="po_no" name="po_no"
                                                value="{{ old('po_no', $invoice->po_no) }}" maxlength="30">
                                            @error('po_no')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency">Currency <span class="text-danger">*</span></label>
                                            <select class="form-control @error('currency') is-invalid @enderror"
                                                id="currency" name="currency" required>
                                                <option value="IDR"
                                                    {{ old('currency', $invoice->currency) == 'IDR' ? 'selected' : '' }}>
                                                    IDR</option>
                                                <option value="USD"
                                                    {{ old('currency', $invoice->currency) == 'USD' ? 'selected' : '' }}>
                                                    USD</option>
                                                <option value="EUR"
                                                    {{ old('currency', $invoice->currency) == 'EUR' ? 'selected' : '' }}>
                                                    EUR</option>
                                                <option value="SGD"
                                                    {{ old('currency', $invoice->currency) == 'SGD' ? 'selected' : '' }}>
                                                    SGD</option>
                                            </select>
                                            @error('currency')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="amount">Amount <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="currency-prefix">IDR</span>
                                                </div>
                                                <input type="text" name="amount_display" id="amount_display"
                                                    class="form-control @error('amount') is-invalid @enderror"
                                                    value="{{ old('amount', $invoice->amount) }}"
                                                    onkeyup="formatNumber(this)" required>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        id="calculator-btn">
                                                        <i class="fas fa-calculator"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <input type="hidden" name="amount" id="amount"
                                                value="{{ old('amount', $invoice->amount) }}">
                                            @error('amount')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror

                                            <!-- Amount Calculator Widget -->
                                            <div class="amount-calculator" id="amount-calculator">
                                                <div class="calculator-input-group">
                                                    <label for="calc-base-amount">Base Amount:</label>
                                                    <input type="text" id="calc-base-amount" class="form-control"
                                                        placeholder="Enter amount">
                                                </div>
                                                <div class="calculator-buttons">
                                                    <button type="button" class="btn btn-sm btn-outline-primary calc-btn"
                                                        data-action="+10">+10%</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary calc-btn"
                                                        data-action="-10">-10%</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary calc-btn"
                                                        data-action="+11">+11%</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary calc-btn"
                                                        data-action="x2">×2</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary calc-btn"
                                                        data-action="/2">÷2</button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-secondary calc-btn"
                                                        data-action="clear">Clear</button>
                                                </div>
                                                <div class="calculator-result" id="calc-result">0</div>
                                                <div class="calculator-actions">
                                                    <button type="button" class="btn btn-success"
                                                        id="apply-calc-result">Apply</button>
                                                    <button type="button" class="btn btn-secondary"
                                                        id="close-calculator">Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status">Status <span class="text-danger">*</span></label>
                                            <select class="form-control @error('status') is-invalid @enderror"
                                                id="status" name="status" required>
                                                <option value="open"
                                                    {{ old('status', $invoice->status) == 'open' ? 'selected' : '' }}>
                                                    Open</option>
                                                <option value="verify"
                                                    {{ old('status', $invoice->status) == 'verify' ? 'selected' : '' }}>
                                                    Verify</option>
                                                <option value="return"
                                                    {{ old('status', $invoice->status) == 'return' ? 'selected' : '' }}>
                                                    Return</option>
                                                <option value="sap"
                                                    {{ old('status', $invoice->status) == 'sap' ? 'selected' : '' }}>
                                                    SAP</option>
                                                <option value="close"
                                                    {{ old('status', $invoice->status) == 'close' ? 'selected' : '' }}>
                                                    Close</option>
                                                <option value="cancel"
                                                    {{ old('status', $invoice->status) == 'cancel' ? 'selected' : '' }}>
                                                    Cancel</option>
                                            </select>
                                            @error('status')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="receive_project">Receive Project</label>
                                            <select class="form-control @error('receive_project') is-invalid @enderror"
                                                id="receive_project" name="receive_project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('receive_project', $invoice->receive_project) == $project->code ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('receive_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_project">Invoice Project</label>
                                            <select class="form-control @error('invoice_project') is-invalid @enderror"
                                                id="invoice_project" name="invoice_project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('invoice_project', $invoice->invoice_project) == $project->code ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('invoice_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_project">Payment Project</label>
                                            <select class="form-control @error('payment_project') is-invalid @enderror"
                                                id="payment_project" name="payment_project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('payment_project', $invoice->payment_project) == $project->code || ($invoice->payment_project == null && $project->code == '001H') ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('payment_project')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cur_loc">Current Location <span
                                                    class="text-danger">*</span></label>
                                            @php
                                                $hasDistributions = $invoice->hasBeenDistributed();
                                                $canChangeLocation = $invoice->canChangeLocationManually();
                                                $isDisabledByRole = !auth()
                                                    ->user()
                                                    ->hasRole(['superadmin', 'admin']);
                                                $isDisabledByDistribution = !$canChangeLocation;
                                                $isDisabled = $isDisabledByRole || $isDisabledByDistribution;
                                            @endphp
                                            <select class="form-control @error('cur_loc') is-invalid @enderror"
                                                id="cur_loc" name="cur_loc" {{ $isDisabled ? 'disabled' : '' }}
                                                required>
                                                <option value="">Select Location</option>
                                                @foreach ($departments as $dept)
                                                    @if ($dept->location_code)
                                                        <option value="{{ $dept->location_code }}"
                                                            {{ old('cur_loc', $invoice->cur_loc) == $dept->location_code ||
                                                            (auth()->user()->department_location_code == $dept->location_code && !old('cur_loc') && $invoice->cur_loc == null)
                                                                ? 'selected'
                                                                : '' }}>
                                                            {{ $dept->location_code }} - {{ $dept->name }}
                                                            ({{ $dept->project }})
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @if ($isDisabledByDistribution)
                                                <input type="hidden" name="cur_loc" value="{{ $invoice->cur_loc }}">
                                            @endif
                                            @error('cur_loc')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                @if ($isDisabledByDistribution)
                                                    <span class="text-warning">
                                                        <i class="fas fa-lock"></i> Location locked - This invoice has
                                                        distribution history.
                                                        Location can only be changed through the distribution process.
                                                    </span>
                                                @elseif (!auth()->user()->hasRole(['superadmin', 'admin']))
                                                    This is set to your department's location and cannot be changed.
                                                @else
                                                    You can change the location as you have administrative privileges.
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_date">Payment Date</label>
                                            <input type="date"
                                                class="form-control @error('payment_date') is-invalid @enderror"
                                                id="payment_date" name="payment_date"
                                                value="{{ old('payment_date', $invoice->payment_date ? $invoice->payment_date->format('Y-m-d') : '') }}">
                                            @error('payment_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sap_doc">SAP Document</label>
                                            <input type="text"
                                                class="form-control @error('sap_doc') is-invalid @enderror"
                                                id="sap_doc" name="sap_doc"
                                                value="{{ old('sap_doc', $invoice->sap_doc) }}">
                                            @error('sap_doc')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3">{{ old('remarks', $invoice->remarks) }}</textarea>
                                    @error('remarks')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Link Additional Documents (optional) -->
                                <div class="card card-outline card-secondary mt-3" id="additional-docs-card">
                                    <div class="card-header">
                                        <h3 class="card-title">Link Additional Documents (optional)</h3>
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
                                            <span class="badge badge-info" id="selected-count">Selected: 0</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
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
                                <button type="button" class="btn btn-info mr-2" id="preview-btn">
                                    <i class="fas fa-eye"></i> Preview Changes
                                </button>
                                <button type="submit" class="btn btn-primary btn-update-enhanced" id="update-btn">
                                    <span class="btn-text">
                                        <i class="fas fa-save"></i> Update Invoice
                                    </span>
                                    <span class="btn-spinner">
                                        <i class="fas fa-spinner fa-spin"></i> Updating...
                                    </span>
                                </button>
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> View Invoice
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Validation Summary Panel -->
    <div class="validation-summary-panel" id="validation-summary-panel">
        <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
        <ul id="validation-errors-list"></ul>
    </div>

    <!-- Create Additional Document Modal -->
    <div class="modal fade" id="create-doc-modal" tabindex="-1" role="dialog"
        aria-labelledby="create-doc-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="create-doc-modal-label">Create New Additional Document</h5>
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
                                        @foreach ($additionalDocumentTypes ?? [] as $type)
                                            <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                                        @endforeach
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
                                        @if (auth()->user()->department && auth()->user()->department->location_code)
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
                                        maxlength="255" value="{{ old('po_no', $invoice->po_no) }}">
                                    <small class="form-text text-muted">This document will be automatically attached to the
                                        current invoice.</small>
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
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

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

            console.log('Invoice edit form loaded, jQuery version:', $.fn.jquery);

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

            // Initialize Select2 Bootstrap 4 for supplier
            console.log('Initializing Select2 for supplier');
            try {
                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select2bs4').select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select Supplier',
                        allowClear: true,
                        width: '100%'
                    });
                    console.log('Select2 initialized successfully');
                } else {
                    console.error('Select2 plugin not loaded');
                }
            } catch (error) {
                console.error('Error initializing Select2:', error);
            }

            // Check if session is still valid before form submission
            function checkSessionAndSubmitForm(form) {
                // First check if session is still valid
                $.ajax({
                    url: '{{ route('invoices.check-session') }}',
                    type: 'GET',
                    success: function(response) {
                        // Session is valid, proceed with form submission
                        submitFormWithAjax(form);
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
            }

            // Function to submit form with AJAX
            function submitFormWithAjax(form) {
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
                    console.log('Validation failed, showing error');
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Please fill in all required fields.');
                    }
                    return false;
                }

                // Show loading message
                console.log('Validation passed, showing info');
                if (typeof toastr !== 'undefined') {
                    toastr.info('Updating invoice...', 'Please wait');
                }

                // Submit form via AJAX
                var formData = new FormData(form);
                var url = $(form).attr('action');
                var method = $(form).attr('method');

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || 'Invoice updated successfully.');
                            // Redirect to index page after short delay
                            setTimeout(function() {
                                window.location.href = '{{ route('invoices.index') }}';
                            }, 1500);
                        } else {
                            toastr.error(response.message || 'Failed to update invoice.');
                        }
                    },
                    error: function(xhr) {
                        // Clear the "Updating invoice..." message
                        toastr.clear();

                        if (xhr.status === 401 || xhr.status === 419) {
                            // Unauthorized or CSRF token mismatch (session expired)
                            toastr.error('Your session has expired. Redirecting to login page...');
                            setTimeout(function() {
                                window.location.href = '{{ route('login') }}';
                            }, 1500);
                        } else if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                $('#' + field).addClass('is-invalid');
                                $('#' + field + '-error').text(messages[0]);
                            });
                            toastr.error('Please correct the validation errors.');
                        } else {
                            toastr.error('An error occurred while updating the invoice.');
                            console.error('Error details:', xhr);
                        }
                    }
                });
            }

            // Handle form submission
            $('form').on('submit', function(e) {
                e.preventDefault();
                console.log('Form submission started');
                checkSessionAndSubmitForm(this);
            });

            // Set current location from user's department if available
            @if (auth()->user()->department_location_code)
                if (!$('#cur_loc').val()) {
                    $('#cur_loc').val('{{ auth()->user()->department_location_code }}');
                }
            @endif

            // Real-time invoice number validation per supplier (exclude current invoice)
            var validationTimeout;

            function validateInvoiceNumber() {
                var invoiceNumber = $('#invoice_number').val().trim();
                var supplierId = $('#supplier_id').val();
                var currentInvoiceId = {{ $invoice->id }};

                if (invoiceNumber && supplierId) {
                    clearTimeout(validationTimeout);
                    validationTimeout = setTimeout(function() {
                        // First check if session is still valid
                        $.ajax({
                            url: '{{ route('invoices.check-session') }}',
                            type: 'GET',
                            success: function() {
                                // Session is valid, proceed with validation
                                $.ajax({
                                    url: '{{ route('invoices.validate-invoice-number') }}',
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}',
                                        invoice_number: invoiceNumber,
                                        supplier_id: supplierId,
                                        exclude_id: currentInvoiceId
                                    },
                                    success: function(response) {
                                        var invoiceField = $('#invoice_number');
                                        var feedback = invoiceField.next(
                                            '.invalid-feedback');

                                        if (response.is_duplicate) {
                                            invoiceField.addClass('is-invalid');
                                            if (feedback.length === 0) {
                                                invoiceField.after(
                                                    '<span class="invalid-feedback">This invoice number is already used for this supplier.</span>'
                                                );
                                            } else {
                                                feedback.text(
                                                    'This invoice number is already used for this supplier.'
                                                );
                                            }
                                        } else {
                                            invoiceField.removeClass('is-invalid');
                                            feedback.remove();
                                        }
                                    },
                                    error: function(xhr) {
                                        if (xhr.status === 401 || xhr.status === 419) {
                                            // Session expired, redirect to login
                                            toastr.error(
                                                'Your session has expired. Redirecting to login page...'
                                            );
                                            setTimeout(function() {
                                                window.location.href =
                                                    '{{ route('login') }}';
                                            }, 1500);
                                        } else {
                                            console.log('Validation request failed');
                                        }
                                    }
                                });
                            },
                            error: function(xhr) {
                                if (xhr.status === 401 || xhr.status === 419) {
                                    // Session expired, redirect to login
                                    toastr.error(
                                        'Your session has expired. Redirecting to login page...');
                                    setTimeout(function() {
                                        window.location.href = '{{ route('login') }}';
                                    }, 1500);
                                }
                            }
                        });
                    }, 500); // Debounce validation
                }
            }

            // Trigger validation when invoice number or supplier changes
            $('#invoice_number').on('input', validateInvoiceNumber);
            $('#supplier_id').on('change', validateInvoiceNumber);

            // Real-time SAP document validation (exclude current invoice)
            var sapValidationTimeout;

            function validateSapDoc() {
                var sapDoc = $('#sap_doc').val().trim();
                var currentInvoiceId = {{ $invoice->id }};

                if (sapDoc.length > 0) {
                    clearTimeout(sapValidationTimeout);
                    sapValidationTimeout = setTimeout(function() {
                        // First check if session is still valid
                        $.ajax({
                            url: '{{ route('invoices.check-session') }}',
                            type: 'GET',
                            success: function() {
                                // Session is valid, proceed with validation
                                $.ajax({
                                    url: '{{ route('invoices.validate-sap-doc') }}',
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}',
                                        sap_doc: sapDoc,
                                        invoice_id: currentInvoiceId
                                    },
                                    success: function(response) {
                                        var sapField = $('#sap_doc');
                                        var feedback = sapField.next('.invalid-feedback');

                                        if (!response.valid) {
                                            sapField.addClass('is-invalid');
                                            if (feedback.length === 0) {
                                                sapField.after(
                                                    '<span class="invalid-feedback">' +
                                                    response.message + '</span>'
                                                );
                                            } else {
                                                feedback.text(response.message);
                                            }
                                        } else {
                                            sapField.removeClass('is-invalid');
                                            feedback.remove();
                                        }
                                    },
                                    error: function(xhr) {
                                        if (xhr.status === 401 || xhr.status === 419) {
                                            // Session expired, redirect to login
                                            toastr.error(
                                                'Your session has expired. Redirecting to login page...'
                                            );
                                            setTimeout(function() {
                                                window.location.href =
                                                    '{{ route('login') }}';
                                            }, 1500);
                                        } else {
                                            console.log('SAP validation request failed');
                                        }
                                    }
                                });
                            },
                            error: function(xhr) {
                                if (xhr.status === 401 || xhr.status === 419) {
                                    // Session expired, redirect to login
                                    toastr.error(
                                        'Your session has expired. Redirecting to login page...');
                                    setTimeout(function() {
                                        window.location.href = '{{ route('login') }}';
                                    }, 1500);
                                }
                            }
                        });
                    }, 500); // Debounce validation
                } else {
                    // Clear validation if field is empty
                    $('#sap_doc').removeClass('is-invalid').next('.invalid-feedback').remove();
                }
            }

            // Trigger SAP validation when sap_doc changes
            $('#sap_doc').on('input', validateSapDoc);

            // Payment date validation
            $('#receive_date').on('change', function() {
                var receiveDate = $(this).val();
                if (receiveDate && $('#payment_date').val()) {
                    if ($('#payment_date').val() < receiveDate) {
                        $('#payment_date').val('');
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Payment date cannot be earlier than receive date.');
                        }
                    }
                }
            });

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

            // Preload existing selections from server-rendered data if available
            @if (isset($invoice))
                @php
                    $preselected = $invoice->additionalDocuments
                        ? $invoice->additionalDocuments
                            ->map(function ($d) {
                                return [
                                    'id' => $d->id,
                                    'document_number' => $d->document_number,
                                    'type_name' => optional($d->type)->type_name,
                                    'document_date' => optional($d->document_date)->format('Y-m-d'),
                                    'po_no' => $d->po_no,
                                    'cur_loc' => $d->cur_loc,
                                    'remarks' => $d->remarks,
                                ];
                            })
                            ->toArray()
                        : [];
                @endphp
                var preselected = @json($preselected ?? []);
                preselected.forEach(function(doc) {
                    selectedDocs[doc.id] = doc;
                });
            @endif

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
                    // Pre-check if document is already linked to current invoice
                    if (doc.is_linked_to_current && !selectedDocs[doc.id]) {
                        selectedDocs[doc.id] = {
                            id: doc.id,
                            document_number: doc.document_number,
                            type_name: doc.type_name,
                            document_date: doc.document_date,
                            po_no: doc.po_no,
                            cur_loc: doc.cur_loc,
                            remarks: doc.remarks
                        };
                    }

                    var checked = selectedDocs[doc.id] ? 'checked' : '';

                    // Build linked invoices display
                    var linkedInvoicesHtml = '';
                    if (doc.linked_invoices_count > 0) {
                        var badgeClass = doc.linked_invoices_count > 1 ? 'badge-warning' : 'badge-info';
                        var tooltipText = 'Linked to: ' + doc.linked_invoices_list.join(', ');

                        // Check if this document is already linked to current invoice
                        if (doc.is_linked_to_current) {
                            badgeClass = 'badge-success';
                            tooltipText += ' (including current invoice: {{ $invoice->invoice_number }})';
                        }

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
                        current_invoice_id: {{ $invoice->id }} // Current invoice being edited
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

            // Initial render of preselected (edit)
            renderSelectedTable();

            // Auto-search on first load if po_no exists
            if ($('#po_no').val()) {
                searchAdditionalDocuments();
            }

            // Handle blur on PO number
            $('#po_no').on('blur', searchAdditionalDocuments);

            // Row checkbox toggle
            $(document).on('change', '.doc-checkbox', function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                if (this.checked) {
                    // Check if document is already linked to other invoices
                    var linkedInvoicesCell = row.find('td').eq(7); // Linked Invoices column
                    var linkedBadge = linkedInvoicesCell.find('.badge');
                    if (linkedBadge.length > 0 && !linkedBadge.hasClass('badge-success')) {
                        var count = parseInt(linkedBadge.text().match(/\d+/)[0]);
                        if (count > 0 && typeof toastr !== 'undefined') {
                            toastr.warning('This document is already linked to ' + count + ' other invoice(s).');
                        }
                    }

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
                } else {
                    delete selectedDocs[id];
                }
                renderSelectedTable();
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

            // Open create document modal
            $(document).on('click', '#create-doc-btn', function() {
                // Pre-fill PO number if available
                var po = $('#po_no').val().trim();
                if (po) {
                    $('#doc_po_no').val(po);
                }

                // Reset form
                $('#create-doc-form')[0].reset();

                // Show modal
                $('#create-doc-modal').modal('show');
            });

            // Handle form submission
            $(document).on('submit', '#create-doc-form', function(e) {
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
                        submitBtn.prop('disabled', false).html(originalText);
                    },
                    complete: function() {
                        // Re-enable submit button
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // ---------- End On-the-fly Additional Document Creation ----------
            // ---------- End Additional Documents Linking ----------

            // ---------- Phase 1: High Priority Improvements ----------

            // 1. Validation Summary Panel
            function updateValidationSummary() {
                var errors = [];
                var requiredFields = [{
                        id: 'invoice_number',
                        label: 'Invoice Number'
                    },
                    {
                        id: 'invoice_date',
                        label: 'Invoice Date'
                    },
                    {
                        id: 'receive_date',
                        label: 'Receive Date'
                    },
                    {
                        id: 'supplier_id',
                        label: 'Supplier'
                    },
                    {
                        id: 'type_id',
                        label: 'Invoice Type'
                    },
                    {
                        id: 'currency',
                        label: 'Currency'
                    },
                    {
                        id: 'amount_display',
                        label: 'Amount'
                    },
                    {
                        id: 'status',
                        label: 'Status'
                    },
                    {
                        id: 'cur_loc',
                        label: 'Current Location'
                    }
                ];

                requiredFields.forEach(function(field) {
                    var element = $('#' + field.id);
                    if (!element.val() || element.val().trim() === '') {
                        errors.push({
                            field: field.id,
                            label: field.label,
                            message: field.label + ' is required'
                        });
                    }
                });

                // Check for validation errors
                $('.is-invalid').each(function() {
                    var fieldId = $(this).attr('id');
                    var label = $(this).closest('.form-group').find('label').text().replace('*', '').trim();
                    var errorMessage = $(this).next('.invalid-feedback').text();
                    if (errorMessage) {
                        errors.push({
                            field: fieldId,
                            label: label,
                            message: errorMessage
                        });
                    }
                });

                var panel = $('#validation-summary-panel');
                var errorsList = $('#validation-errors-list');

                if (errors.length > 0) {
                    errorsList.empty();
                    errors.forEach(function(error) {
                        errorsList.append('<li data-field="' + error.field + '">' + error.message + '</li>');
                    });
                    panel.addClass('show');
                } else {
                    panel.removeClass('show');
                }

                return errors.length;
            }

            // Click-to-scroll functionality
            $(document).on('click', '#validation-errors-list li', function() {
                var fieldId = $(this).data('field');
                var field = $('#' + fieldId);
                if (field.length) {
                    $('html, body').animate({
                        scrollTop: field.offset().top - 100
                    }, 500);
                    field.focus();
                }
            });

            // 2. Supplier-Specific Defaults Loading
            function loadSupplierDefaults(supplierId) {
                if (!supplierId) return;

                $.ajax({
                    url: '/invoices/supplier-defaults/' + supplierId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            var data = response.data;

                            // Auto-fill currency if user has history with this supplier
                            if (data.preferred_currency && !$('#currency').val()) {
                                $('#currency').val(data.preferred_currency).trigger('change');
                                updateCurrencyPrefix();
                                if (typeof toastr !== 'undefined') {
                                    toastr.info('Currency set to ' + data.preferred_currency +
                                        ' based on your history with this supplier.');
                                }
                            }

                            // Show hint for last invoice type used
                            if (data.last_invoice_type && !$('#type_id').val()) {
                                if (typeof toastr !== 'undefined') {
                                    toastr.info('Last invoice type used: ' + data.last_invoice_type);
                                }
                            }

                            // Auto-fill payment project if consistently used
                            if (data.preferred_payment_project && !$('#payment_project').val()) {
                                $('#payment_project').val(data.preferred_payment_project).trigger('change');
                                if (typeof toastr !== 'undefined') {
                                    toastr.info('Payment project set to ' + data.preferred_payment_project +
                                        ' (used in ' + data.payment_project_count + ' previous invoices).');
                                }
                            }
                        }
                    },
                    error: function() {
                        console.log('Could not load supplier defaults');
                    }
                });
            }

            // 3. Enhanced Duplicate Warning
            var duplicateCheckTimeout;

            function checkForDuplicate() {
                var fakturNo = $('#faktur_no').val().trim();
                var supplierId = $('#supplier_id').val();
                var currentInvoiceId = {{ $invoice->id }};

                if (fakturNo && supplierId) {
                    clearTimeout(duplicateCheckTimeout);
                    duplicateCheckTimeout = setTimeout(function() {
                        $.ajax({
                            url: '{{ route('invoices.check-duplicate') }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                faktur_no: fakturNo,
                                supplier_id: supplierId,
                                exclude_id: currentInvoiceId
                            },
                            success: function(response) {
                                if (response.is_duplicate && typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: 'Duplicate Invoice Warning',
                                        html: '<div class="text-left">' +
                                            '<p><strong>This faktur number already exists for this supplier:</strong></p>' +
                                            '<ul class="text-left">' +
                                            '<li><strong>Invoice:</strong> ' + response
                                            .existing_invoice.invoice_number + '</li>' +
                                            '<li><strong>Faktur No:</strong> ' + response
                                            .existing_invoice.faktur_no + '</li>' +
                                            '<li><strong>Date:</strong> ' + response
                                            .existing_invoice.invoice_date + '</li>' +
                                            '<li><strong>Amount:</strong> ' + response
                                            .existing_invoice.amount_formatted + '</li>' +
                                            '<li><strong>Status:</strong> ' + response
                                            .existing_invoice.status + '</li>' +
                                            '</ul>' +
                                            '<p class="text-warning mt-3"><i class="fas fa-exclamation-triangle"></i> Are you sure you want to continue?</p>' +
                                            '</div>',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonText: 'Yes, Continue',
                                        cancelButtonText: 'Cancel',
                                        confirmButtonColor: '#d33',
                                        cancelButtonColor: '#3085d6'
                                    }).then((result) => {
                                        if (!result.isConfirmed) {
                                            $('#faktur_no').focus();
                                        }
                                    });
                                }
                            },
                            error: function() {
                                console.log('Duplicate check failed');
                            }
                        });
                    }, 800); // Debounce
                }
            }

            // 4. Form Progress Indicator
            function updateFormProgress() {
                var totalFields = 9; // Number of required fields
                var filledFields = 0;

                var requiredFields = [
                    'invoice_number', 'invoice_date', 'receive_date', 'supplier_id',
                    'type_id', 'currency', 'amount_display', 'status', 'cur_loc'
                ];

                requiredFields.forEach(function(fieldId) {
                    var field = $('#' + fieldId);
                    if (field.val() && field.val().trim() !== '') {
                        filledFields++;
                    }
                });

                var percentage = Math.round((filledFields / totalFields) * 100);
                $('#form-progress-fill').css('width', percentage + '%');
                $('#form-progress-text').text('Form Progress: ' + percentage + '% Complete');

                // Update validation summary
                updateValidationSummary();
            }

            // 5. Amount Calculator Widget
            function initializeCalculator() {
                var calculator = $('#amount-calculator');
                var baseAmountInput = $('#calc-base-amount');
                var resultDisplay = $('#calc-result');
                var currentAmount = $('#amount_display').val().replace(/,/g, '');

                // Pre-fill with current amount
                if (currentAmount && !isNaN(currentAmount)) {
                    baseAmountInput.val(currentAmount);
                }

                // Calculator button clicks
                $('.calc-btn').on('click', function() {
                    var action = $(this).data('action');
                    var baseAmount = parseFloat(baseAmountInput.val().replace(/,/g, '')) || 0;
                    var result = 0;

                    switch (action) {
                        case '+10':
                            result = baseAmount * 1.10;
                            break;
                        case '-10':
                            result = baseAmount * 0.90;
                            break;
                        case '+11':
                            result = baseAmount * 1.11; // VAT
                            break;
                        case 'x2':
                            result = baseAmount * 2;
                            break;
                        case '/2':
                            result = baseAmount / 2;
                            break;
                        case 'clear':
                            baseAmountInput.val('');
                            result = 0;
                            break;
                    }

                    resultDisplay.text(result.toLocaleString());
                });

                // Apply result
                $('#apply-calc-result').on('click', function() {
                    var result = resultDisplay.text().replace(/,/g, '');
                    if (result && !isNaN(result)) {
                        $('#amount_display').val(result);
                        formatNumber(document.getElementById('amount_display'));
                        updateFormProgress();
                        calculator.removeClass('show');
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Amount updated successfully!');
                        }
                    }
                });

                // Close calculator
                $('#close-calculator').on('click', function() {
                    calculator.removeClass('show');
                });
            }

            // 6. Keyboard Shortcuts
            function initializeKeyboardShortcuts() {
                $(document).on('keydown', function(e) {
                    // Ctrl+S to save
                    if (e.ctrlKey && e.key === 's') {
                        e.preventDefault();
                        $('#invoice-edit-form').submit();
                    }

                    // Esc to cancel
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        window.location.href = '{{ route('invoices.show', $invoice) }}';
                    }
                });
            }

            // 7. Enhanced Submit Button
            function initializeEnhancedSubmit() {
                $('#invoice-edit-form').on('submit', function(e) {
                    var errors = updateValidationSummary();
                    if (errors > 0) {
                        e.preventDefault();
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Please fix all validation errors before submitting.');
                        }
                        return false;
                    }

                    var submitBtn = $('#update-btn');
                    submitBtn.addClass('loading').prop('disabled', true);
                });
            }

            // 8. Currency Prefix Update
            function updateCurrencyPrefix() {
                var currency = $('#currency').val();
                $('#currency-prefix').text(currency || 'IDR');
            }

            // Initialize all Phase 1 features
            function initializePhase1Features() {
                // Load supplier defaults when supplier changes
                $('#supplier_id').on('change', function() {
                    loadSupplierDefaults($(this).val());
                    updateFormProgress();
                });

                // Check for duplicates when faktur number changes
                $('#faktur_no').on('input', checkForDuplicate);

                // Update progress on any field change
                $('input, select, textarea').on('change input', updateFormProgress);

                // Initialize calculator
                $('#calculator-btn').on('click', function() {
                    $('#amount-calculator').toggleClass('show');
                });

                // Initialize all components
                initializeCalculator();
                initializeKeyboardShortcuts();
                initializeEnhancedSubmit();
                updateCurrencyPrefix();
                updateFormProgress();

                // Load supplier defaults on page load if supplier is already selected
                if ($('#supplier_id').val()) {
                    loadSupplierDefaults($('#supplier_id').val());
                }
            }

            // 9. Invoice Preview Before Update
            function initializePreviewFeature() {
                $('#preview-btn').on('click', function() {
                    var errors = updateValidationSummary();
                    if (errors > 0) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Please fix all validation errors before previewing.');
                        }
                        return false;
                    }

                    // Collect form data
                    var formData = {
                        invoice_number: $('#invoice_number').val(),
                        faktur_no: $('#faktur_no').val(),
                        invoice_date: $('#invoice_date').val(),
                        receive_date: $('#receive_date').val(),
                        supplier: $('#supplier_id option:selected').text(),
                        type: $('#type_id option:selected').text(),
                        po_no: $('#po_no').val(),
                        currency: $('#currency').val(),
                        amount: $('#amount_display').val(),
                        status: $('#status option:selected').text(),
                        receive_project: $('#receive_project option:selected').text(),
                        invoice_project: $('#invoice_project option:selected').text(),
                        payment_project: $('#payment_project option:selected').text(),
                        cur_loc: $('#cur_loc option:selected').text(),
                        payment_date: $('#payment_date').val(),
                        sap_doc: $('#sap_doc').val(),
                        remarks: $('#remarks').val()
                    };

                    // Create preview HTML
                    var previewHtml = '<div class="table-responsive">' +
                        '<table class="table table-bordered">' +
                        '<thead class="thead-light">' +
                        '<tr><th colspan="2" class="text-center"><h5><i class="fas fa-file-invoice"></i> Invoice Preview</h5></th></tr>' +
                        '</thead>' +
                        '<tbody>' +
                        '<tr><td><i class="fas fa-hashtag text-primary"></i> Invoice Number</td><td><strong>' +
                        formData.invoice_number + '</strong></td></tr>' +
                        '<tr><td><i class="fas fa-receipt text-info"></i> Faktur No</td><td>' + (formData
                            .faktur_no || '-') + '</td></tr>' +
                        '<tr><td><i class="fas fa-calendar text-success"></i> Invoice Date</td><td>' + formData
                        .invoice_date + '</td></tr>' +
                        '<tr><td><i class="fas fa-calendar-check text-warning"></i> Receive Date</td><td>' +
                        formData.receive_date + '</td></tr>' +
                        '<tr><td><i class="fas fa-truck text-primary"></i> Supplier</td><td>' + formData.supplier +
                        '</td></tr>' +
                        '<tr><td><i class="fas fa-tag text-info"></i> Invoice Type</td><td>' + formData.type +
                        '</td></tr>' +
                        '<tr><td><i class="fas fa-file-alt text-secondary"></i> PO Number</td><td>' + (formData
                            .po_no || '-') + '</td></tr>' +
                        '<tr><td><i class="fas fa-money-bill text-success"></i> Currency</td><td>' + formData
                        .currency + '</td></tr>' +
                        '<tr><td><i class="fas fa-dollar-sign text-success"></i> Amount</td><td><strong class="text-success">' +
                        formData.currency + ' ' + formData.amount + '</strong></td></tr>' +
                        '<tr><td><i class="fas fa-info-circle text-info"></i> Status</td><td><span class="badge badge-primary">' +
                        formData.status + '</span></td></tr>' +
                        '<tr><td><i class="fas fa-building text-primary"></i> Current Location</td><td>' + formData
                        .cur_loc + '</td></tr>' +
                        '<tr><td><i class="fas fa-project-diagram text-secondary"></i> Receive Project</td><td>' + (
                            formData.receive_project || '-') + '</td></tr>' +
                        '<tr><td><i class="fas fa-project-diagram text-secondary"></i> Invoice Project</td><td>' + (
                            formData.invoice_project || '-') + '</td></tr>' +
                        '<tr><td><i class="fas fa-project-diagram text-secondary"></i> Payment Project</td><td>' + (
                            formData.payment_project || '-') + '</td></tr>' +
                        '<tr><td><i class="fas fa-calendar text-warning"></i> Payment Date</td><td>' + (formData
                            .payment_date || '-') + '</td></tr>' +
                        '<tr><td><i class="fas fa-file text-info"></i> SAP Document</td><td>' + (formData.sap_doc ||
                            '-') + '</td></tr>' +
                        '<tr><td><i class="fas fa-comment text-secondary"></i> Remarks</td><td>' + (formData
                            .remarks || '-') + '</td></tr>' +
                        '</tbody>' +
                        '</table>' +
                        '</div>';

                    // Show preview dialog
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Invoice Preview',
                            html: previewHtml,
                            width: '700px',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-save"></i> Update Invoice',
                            cancelButtonText: '<i class="fas fa-edit"></i> Continue Editing',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#6c757d',
                            showCloseButton: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $('#invoice-edit-form').submit();
                            }
                        });
                    }
                });
            }

            // Initialize all Phase 1 features
            initializePhase1Features();
            initializePreviewFeature();

            // ---------- End Phase 1: High Priority Improvements ----------
        }

        // Start initialization when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeInvoiceForm);
        } else {
            initializeInvoiceForm();
        }
    </script>
@endsection
