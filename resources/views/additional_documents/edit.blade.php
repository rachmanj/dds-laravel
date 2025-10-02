@extends('layouts.main')

@section('title_page')
    Edit Additional Document
@endsection

@section('breadcrumb_title')
    additional-documents / edit
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">

    <style>
        /* ===== ENHANCED VISUAL HIERARCHY ===== */

        /* Card Header Enhancement */
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            padding: 1.5rem 1.25rem;
        }

        .card-header .card-title {
            color: white;
            font-weight: 600;
            font-size: 1.4rem;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .card-header .card-title i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        /* Form Section Headers */
        .form-section-header {
            background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #007bff;
            padding: 0.75rem 1rem;
            margin: 1.5rem 0 1rem 0;
            border-radius: 0 0.375rem 0.375rem 0;
            font-weight: 600;
            color: #495057;
            font-size: 1.1rem;
        }

        .form-section-header i {
            margin-right: 0.5rem;
            color: #007bff;
        }

        /* Enhanced Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-group label .text-danger {
            font-weight: 700;
        }

        /* ===== IMPROVED FORM LAYOUT AND SPACING ===== */

        /* Better Row Spacing */
        .row {
            margin-bottom: 0.5rem;
        }

        .row:last-child {
            margin-bottom: 0;
        }

        /* Enhanced Form Controls */
        .form-control {
            border-radius: 0.5rem;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25), 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .form-control:hover {
            border-color: #ced4da;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
        }

        /* Enhanced Input Groups */
        .input-group {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .input-group .form-control {
            border-radius: 0;
            border-right: none;
            box-shadow: none;
        }

        .input-group .form-control:focus {
            box-shadow: none;
            transform: none;
        }

        .input-group-append .btn {
            border-radius: 0 0.5rem 0.5rem 0;
            border-left: none;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .input-group-append .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Enhanced Select2 Styling */
        .select2-container--bootstrap4 .select2-selection {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0.5rem;
            min-height: 48px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .select2-container--bootstrap4 .select2-selection:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25), 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* ===== ENHANCED TOOLTIPS AND HELP TEXT ===== */

        /* Enhanced Help Text */
        .form-text {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: #f8f9fa;
            border-radius: 0.375rem;
            border-left: 3px solid #17a2b8;
            color: #495057;
        }

        .form-text i {
            color: #17a2b8;
            margin-right: 0.25rem;
        }

        /* Enhanced Tooltips */
        .tooltip-enhanced {
            position: relative;
            cursor: help;
        }

        .tooltip-enhanced::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .tooltip-enhanced::before {
            content: '';
            position: absolute;
            bottom: 115%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #333;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .tooltip-enhanced:hover::after,
        .tooltip-enhanced:hover::before {
            opacity: 1;
            visibility: visible;
        }

        /* ===== REAL-TIME VALIDATION STYLES ===== */

        /* Validation States */
        .form-control.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .form-control.is-warning {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }

        /* Validation Messages */
        .valid-feedback {
            color: #28a745;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .warning-feedback {
            color: #856404;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* ===== CHANGE TRACKING STYLES ===== */

        .field-changed {
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }

        .change-indicator {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 8px;
            height: 8px;
            background-color: #ffc107;
            border-radius: 50%;
            z-index: 10;
        }

        .change-summary {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .change-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .change-item:last-child {
            border-bottom: none;
        }

        .change-field {
            font-weight: 600;
            color: #1976d2;
        }

        .change-value {
            color: #666;
            font-family: 'Courier New', monospace;
        }

        /* ===== ENHANCED LOCATION MANAGEMENT ===== */

        .location-selector {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .location-info {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .location-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #007bff;
            color: white;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* ===== ADVANCED METADATA DISPLAY ===== */

        .metadata-section {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .metadata-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .metadata-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            padding: 0.75rem;
        }

        .metadata-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .metadata-value {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .metadata-timestamp {
            color: #6c757d;
            font-size: 0.8rem;
            font-style: italic;
        }

        /* Modal enhancements */
        .supplier-row:hover {
            background-color: #f8f9fa;
        }

        .supplier-row td {
            vertical-align: middle;
        }

        .sap-code-badge {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Additional Document</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('additional-documents.index') }}">Additional
                                Documents</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit"></i>
                                Edit Additional Document
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('additional-documents.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('additional-documents.update', $additionalDocument) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <!-- Basic Information Section -->
                                <div class="form-section-header">
                                    <i class="fas fa-info-circle"></i>
                                    Basic Information
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type_id" class="tooltip-enhanced"
                                                data-tooltip="Select the type of document you are editing. This helps categorize and organize your documents">
                                                Document Type <span class="text-danger">*</span> <i
                                                    class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            <select class="form-control select2bs4 @error('type_id') is-invalid @enderror"
                                                id="type_id" name="type_id" required>
                                                <option value="">Select Document Type</option>
                                                @foreach ($documentTypes as $type)
                                                    <option value="{{ $type->id }}"
                                                        {{ old('type_id', $additionalDocument->type_id) == $type->id ? 'selected' : '' }}>
                                                        {{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lightbulb"></i>
                                                Choose the appropriate document category for better organization and
                                                tracking
                                            </small>
                                            @error('type_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="document_number" class="tooltip-enhanced"
                                                data-tooltip="Unique identifier for this document. Use a clear, consistent format">
                                                Document Number <span class="text-danger">*</span> <i
                                                    class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            <input type="text"
                                                class="form-control @error('document_number') is-invalid @enderror"
                                                id="document_number" name="document_number"
                                                value="{{ old('document_number', $additionalDocument->document_number) }}"
                                                required>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lightbulb"></i>
                                                Use a clear, unique identifier for easy reference and tracking
                                            </small>
                                            @error('document_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Details Section -->
                                <div class="form-section-header">
                                    <i class="fas fa-calendar-alt"></i>
                                    Document Details
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="document_date" class="tooltip-enhanced"
                                                data-tooltip="The original date when the document was created or issued">
                                                Document Date <span class="text-danger">*</span> <i
                                                    class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            <input type="date"
                                                class="form-control @error('document_date') is-invalid @enderror"
                                                id="document_date" name="document_date"
                                                value="{{ old('document_date', $additionalDocument->document_date ? $additionalDocument->document_date->format('Y-m-d') : '') }}"
                                                required>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lightbulb"></i>
                                                The original date when the document was created or issued
                                            </small>
                                            @error('document_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="receive_date" class="tooltip-enhanced"
                                                data-tooltip="The date when this document was received or processed">
                                                Receive Date <span class="text-danger">*</span> <i
                                                    class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            <input type="date"
                                                class="form-control @error('receive_date') is-invalid @enderror"
                                                id="receive_date" name="receive_date"
                                                value="{{ old('receive_date', $additionalDocument->receive_date ? $additionalDocument->receive_date->format('Y-m-d') : '') }}"
                                                required>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lightbulb"></i>
                                                The date when this document was received or processed
                                            </small>
                                            @error('receive_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="po_no" class="tooltip-enhanced"
                                                data-tooltip="Purchase Order number associated with this document">
                                                PO Number <i class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            <input type="text"
                                                class="form-control @error('po_no') is-invalid @enderror" id="po_no"
                                                name="po_no" value="{{ old('po_no', $additionalDocument->po_no) }}"
                                                maxlength="50">
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lightbulb"></i>
                                                Enter the Purchase Order number if applicable
                                            </small>
                                            @error('po_no')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="vendor_code" class="tooltip-enhanced"
                                                data-tooltip="Should match supplier's SAP code for PO suggestions">
                                                Vendor Code <i class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control @error('vendor_code') is-invalid @enderror"
                                                    id="vendor_code" name="vendor_code"
                                                    value="{{ old('vendor_code', $additionalDocument->vendor_code) }}"
                                                    placeholder="Enter vendor code">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-info"
                                                        id="sap-suggestions-btn" title="Get SAP Code Suggestions">
                                                        <i class="fas fa-lightbulb"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lightbulb"></i>
                                                Should match supplier's SAP code for PO suggestions
                                            </small>
                                            @error('vendor_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="project" class="tooltip-enhanced"
                                                data-tooltip="Select the project this document belongs to">
                                                Project <i class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            <select class="form-control select2bs4 @error('project') is-invalid @enderror"
                                                id="project" name="project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('project', $additionalDocument->project) == $project->code ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->owner }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lightbulb"></i>
                                                Choose the appropriate project for better organization
                                            </small>
                                            @error('project')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Location & File Section -->
                                <div class="form-section-header">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Location & File Management
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cur_loc" class="tooltip-enhanced"
                                                data-tooltip="Current location of the document. Can be changed by authorized users">
                                                Current Location <i class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            @if (auth()->user()->hasAnyRole(['superadmin', 'admin', 'accounting']))
                                                <select
                                                    class="form-control select2bs4 @error('cur_loc') is-invalid @enderror"
                                                    id="cur_loc" name="cur_loc">
                                                    <option value="">Select Location</option>
                                                    @foreach ($departments as $department)
                                                        <option value="{{ $department->location_code }}"
                                                            {{ old('cur_loc', $additionalDocument->cur_loc) == $department->location_code ? 'selected' : '' }}>
                                                            {{ $department->location_code }} - {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-lightbulb"></i>
                                                    You can change the location as you have administrative privileges
                                                </small>
                                            @else
                                                <input type="text" class="form-control" id="cur_loc"
                                                    value="{{ $additionalDocument->cur_loc ?? 'Not assigned' }}"
                                                    placeholder="{{ $additionalDocument->cur_loc ?? 'No location assigned' }}"
                                                    disabled>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-lock"></i>
                                                    Document location: {{ $additionalDocument->cur_loc ?? 'Not assigned' }}
                                                    (cannot be changed)
                                                </small>
                                            @endif
                                            @error('cur_loc')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="remarks" class="tooltip-enhanced"
                                                data-tooltip="Optional field for additional context or notes about the document">
                                                Remarks <i class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3"
                                                placeholder="Enter any additional notes or comments about this document...">{{ old('remarks', $additionalDocument->remarks) }}</textarea>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lightbulb"></i>
                                                Optional field for additional context or notes about the document
                                            </small>
                                            @error('remarks')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="attachment" class="tooltip-enhanced"
                                                data-tooltip="Upload a new file to replace the current attachment">
                                                Attachment <i class="fas fa-question-circle text-info ml-1"></i>
                                            </label>
                                            @if ($additionalDocument->attachment)
                                                <div class="mb-2">
                                                    <strong>Current Attachment:</strong>
                                                    <a href="{{ route('additional-documents.download', $additionalDocument) }}"
                                                        class="btn btn-sm btn-info ml-2" target="_blank">
                                                        <i class="fas fa-download"></i> Download Current
                                                    </a>
                                                </div>
                                            @endif
                                            <div class="custom-file">
                                                <input type="file"
                                                    class="custom-file-input @error('attachment') is-invalid @enderror"
                                                    id="attachment" name="attachment"
                                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                                <label class="custom-file-label" for="attachment">Choose new file
                                                    (optional)</label>
                                            </div>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-lightbulb"></i>
                                                Allowed file types: PDF, DOC, DOCX, JPG, JPEG, PNG (Max: 2MB)
                                            </small>
                                            @error('attachment')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Change Tracking Summary -->
                                <div class="change-summary" id="change-summary" style="display: none;">
                                    <h6><i class="fas fa-edit"></i> Changes Summary</h6>
                                    <div id="changes-list"></div>
                                </div>

                                <!-- Advanced Metadata Display -->
                                <div class="metadata-section">
                                    <h6><i class="fas fa-info-circle"></i> Document Metadata</h6>
                                    <div class="metadata-grid">
                                        <div class="metadata-item">
                                            <div class="metadata-label">Created By</div>
                                            <div class="metadata-value">
                                                {{ $additionalDocument->creator->name ?? 'Unknown' }}</div>
                                            @if ($additionalDocument->creator && $additionalDocument->creator->department)
                                                <div class="metadata-timestamp">
                                                    {{ $additionalDocument->creator->department->name }}</div>
                                            @endif
                                        </div>
                                        <div class="metadata-item">
                                            <div class="metadata-label">Created Date</div>
                                            <div class="metadata-value">
                                                {{ $additionalDocument->created_at->format('d/m/Y H:i') }}</div>
                                            <div class="metadata-timestamp">
                                                {{ $additionalDocument->created_at->diffForHumans() }}</div>
                                        </div>
                                        <div class="metadata-item">
                                            <div class="metadata-label">Last Updated</div>
                                            <div class="metadata-value">
                                                {{ $additionalDocument->updated_at->format('d/m/Y H:i') }}</div>
                                            @if ($additionalDocument->updated_at != $additionalDocument->created_at)
                                                <div class="metadata-timestamp">Modified
                                                    {{ $additionalDocument->updated_at->diffForHumans() }}</div>
                                            @endif
                                        </div>
                                        <div class="metadata-item">
                                            <div class="metadata-label">Status</div>
                                            <div class="metadata-value">
                                                <span
                                                    class="badge badge-{{ $additionalDocument->status === 'open' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($additionalDocument->status) }}
                                                </span>
                                            </div>
                                            @if ($additionalDocument->status === 'closed')
                                                <div class="metadata-timestamp">Document is archived</div>
                                            @endif
                                        </div>
                                        @if ($additionalDocument->vendor_code)
                                            <div class="metadata-item">
                                                <div class="metadata-label">Vendor Code</div>
                                                <div class="metadata-value">
                                                    <span
                                                        class="badge badge-secondary sap-code-badge">{{ $additionalDocument->vendor_code }}</span>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($additionalDocument->po_no)
                                            <div class="metadata-item">
                                                <div class="metadata-label">PO Number</div>
                                                <div class="metadata-value">{{ $additionalDocument->po_no }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Document Location Info -->
                                @if ($additionalDocument->cur_loc)
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-warning">
                                                <h6><i class="fas fa-map-marker-alt"></i> Location Information</h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Current Location:</strong>
                                                        {{ $additionalDocument->cur_loc }}
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Location Type:</strong>
                                                        @if (str_contains($additionalDocument->cur_loc, 'HLOG'))
                                                            <span class="badge badge-info">Logistics</span>
                                                        @elseif(str_contains($additionalDocument->cur_loc, 'HACC'))
                                                            <span class="badge badge-warning">Accounting</span>
                                                        @elseif(str_contains($additionalDocument->cur_loc, 'HFIN'))
                                                            <span class="badge badge-success">Finance</span>
                                                        @else
                                                            <span class="badge badge-secondary">Other</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Document
                                </button>
                                <a href="{{ route('additional-documents.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <!-- Custom File Input -->
    <script src="{{ asset('adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize custom file input
            bsCustomFileInput.init();

            // Initialize Select2 Bootstrap 4
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select Document Type',
                allowClear: true,
                width: '100%'
            });

            // Initialize Toastr
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "5000"
            };

            // ===== CHANGE TRACKING =====
            const originalValues = {
                'type_id': '{{ $additionalDocument->type_id }}',
                'document_number': '{{ $additionalDocument->document_number }}',
                'document_date': '{{ $additionalDocument->document_date ? $additionalDocument->document_date->format('Y-m-d') : '' }}',
                'receive_date': '{{ $additionalDocument->receive_date ? $additionalDocument->receive_date->format('Y-m-d') : '' }}',
                'po_no': '{{ $additionalDocument->po_no }}',
                'vendor_code': '{{ $additionalDocument->vendor_code }}',
                'project': '{{ $additionalDocument->project }}',
                'cur_loc': '{{ $additionalDocument->cur_loc }}',
                'remarks': '{{ $additionalDocument->remarks }}'
            };

            const fieldLabels = {
                'type_id': 'Document Type',
                'document_number': 'Document Number',
                'document_date': 'Document Date',
                'receive_date': 'Receive Date',
                'po_no': 'PO Number',
                'vendor_code': 'Vendor Code',
                'project': 'Project',
                'cur_loc': 'Current Location',
                'remarks': 'Remarks'
            };

            function trackChanges() {
                const changes = [];

                $('input, select, textarea').each(function() {
                    const fieldName = $(this).attr('name');
                    if (fieldName && originalValues[fieldName] !== undefined) {
                        const currentValue = $(this).val();
                        const originalValue = originalValues[fieldName];

                        if (currentValue !== originalValue) {
                            changes.push({
                                field: fieldName,
                                label: fieldLabels[fieldName] || fieldName,
                                oldValue: originalValue || '(empty)',
                                newValue: currentValue || '(empty)'
                            });

                            // Add visual indicator
                            $(this).addClass('field-changed');
                            if (!$(this).parent().find('.change-indicator').length) {
                                $(this).parent().append('<div class="change-indicator"></div>');
                            }
                        } else {
                            $(this).removeClass('field-changed');
                            $(this).parent().find('.change-indicator').remove();
                        }
                    }
                });

                // Check if a new file is selected
                if ($('#attachment')[0].files.length > 0) {
                    changes.push({
                        field: 'attachment',
                        label: 'Attachment',
                        oldValue: 'Current file',
                        newValue: 'New file selected'
                    });
                }

                updateChangeSummary(changes);
            }

            function updateChangeSummary(changes) {
                const summary = $('#change-summary');
                const changesList = $('#changes-list');

                if (changes.length > 0) {
                    summary.show();
                    changesList.empty();

                    changes.forEach(function(change) {
                        changesList.append(`
                            <div class="change-item">
                                <span class="change-field">${change.label}</span>
                                <span class="change-value">${change.oldValue} → ${change.newValue}</span>
                            </div>
                        `);
                    });
                } else {
                    summary.hide();
                }
            }

            // Track changes on input
            $('input, select, textarea').on('input change', function() {
                trackChanges();
            });

            // ===== REAL-TIME VALIDATION =====

            // Document Number Validation
            $('#document_number').on('input', function() {
                const value = $(this).val();
                const field = $(this);

                if (value.length < 3) {
                    field.removeClass('is-valid is-warning').addClass('is-invalid');
                    showFieldFeedback(field, 'Document number must be at least 3 characters long.',
                    'error');
                } else if (value.length > 50) {
                    field.removeClass('is-valid is-invalid').addClass('is-warning');
                    showFieldFeedback(field,
                        'Document number is quite long. Consider using a shorter identifier.', 'warning'
                        );
                } else {
                    field.removeClass('is-invalid is-warning').addClass('is-valid');
                    showFieldFeedback(field, 'Document number looks good!', 'success');
                }
            });

            // Vendor Code Validation
            $('#vendor_code').on('input', function() {
                const value = $(this).val();
                const field = $(this);

                if (value.length > 0) {
                    if (value.length < 3) {
                        field.removeClass('is-valid is-warning').addClass('is-invalid');
                        showFieldFeedback(field, 'Vendor code should be at least 3 characters long.',
                            'error');
                    } else if (!/^[A-Z0-9]+$/.test(value)) {
                        field.removeClass('is-valid is-invalid').addClass('is-warning');
                        showFieldFeedback(field,
                            'Vendor code should contain only uppercase letters and numbers.', 'warning');
                    } else {
                        field.removeClass('is-invalid is-warning').addClass('is-valid');
                        showFieldFeedback(field, 'Vendor code format looks good!', 'success');
                    }
                } else {
                    field.removeClass('is-valid is-invalid is-warning');
                    clearFieldFeedback(field);
                }
            });

            // PO Number Validation
            $('#po_no').on('input', function() {
                const value = $(this).val();
                const field = $(this);

                if (value.length > 0) {
                    if (value.length < 3) {
                        field.removeClass('is-valid is-warning').addClass('is-invalid');
                        showFieldFeedback(field, 'PO number should be at least 3 characters long.',
                        'error');
                    } else {
                        field.removeClass('is-invalid is-warning').addClass('is-valid');
                        showFieldFeedback(field, 'PO number looks good!', 'success');
                    }
                } else {
                    field.removeClass('is-valid is-invalid is-warning');
                    clearFieldFeedback(field);
                }
            });

            // Date Validation
            $('#document_date, #receive_date').on('change', function() {
                const documentDate = $('#document_date').val();
                const receiveDate = $('#receive_date').val();
                const receiveField = $('#receive_date');

                if (documentDate && receiveDate) {
                    if (new Date(receiveDate) < new Date(documentDate)) {
                        receiveField.removeClass('is-valid is-warning').addClass('is-invalid');
                        showFieldFeedback(receiveField,
                            'Receive date cannot be earlier than document date.', 'error');
                    } else {
                        receiveField.removeClass('is-invalid is-warning').addClass('is-valid');
                        showFieldFeedback(receiveField, 'Date validation passed!', 'success');
                    }
                } else {
                    receiveField.removeClass('is-valid is-invalid is-warning');
                    clearFieldFeedback(receiveField);
                }
            });

            function showFieldFeedback(field, message, type) {
                clearFieldFeedback(field);

                const feedbackClass = type === 'error' ? 'invalid-feedback' :
                    type === 'warning' ? 'warning-feedback' : 'valid-feedback';

                field.after(`<div class="${feedbackClass}">${message}</div>`);
            }

            function clearFieldFeedback(field) {
                field.next('.valid-feedback, .invalid-feedback, .warning-feedback').remove();
            }

            // ===== SAP CODE SUGGESTIONS =====
            $('#sap-suggestions-btn').on('click', function() {
                loadSapSuggestions();
            });

            function loadSapSuggestions() {
                $.ajax({
                    url: '/suppliers/sap-codes',
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            showSapSuggestionsModal(response.data);
                        } else {
                            toastr.info('No SAP codes available');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to load SAP codes');
                    }
                });
            }

            function showSapSuggestionsModal(sapCodes) {
                // Store original data for filtering
                window.sapCodesData = sapCodes;

                let modalHtml = `
                    <div class="modal fade" id="sap-codes-modal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">
                                        <i class="fas fa-clipboard-list mr-2"></i>
                                        Available SAP Codes
                                    </h5>
                                    <button type="button" class="close text-white" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="sap-search" placeholder="Search SAP codes...">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" id="clear-search">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-outline-primary btn-sm" id="sort-sap-asc">
                                                    <i class="fas fa-sort-alpha-down"></i> A-Z
                                                </button>
                                                <button type="button" class="btn btn-outline-primary btn-sm" id="sort-sap-desc">
                                                    <i class="fas fa-sort-alpha-up"></i> Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="sap-codes-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>SAP Code</th>
                                                    <th>Supplier Name</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="sap-codes-tbody">
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-3">
                                        <small class="text-muted" id="sap-results-count">0 results</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remove existing modal if any
                $('#sap-codes-modal').remove();

                // Add modal to body
                $('body').append(modalHtml);

                // Show modal
                $('#sap-codes-modal').modal('show');

                // Populate table
                populateSapCodesTable(sapCodes);

                // Setup search functionality
                setupSapSearch();
            }

            function populateSapCodesTable(sapCodes) {
                const tbody = $('#sap-codes-tbody');
                tbody.empty();

                sapCodes.forEach(function(supplier) {
                    const row = `
                        <tr class="supplier-row" data-sap-code="${supplier.sap_code}">
                            <td>
                                <span class="badge badge-primary sap-code-badge">${supplier.sap_code}</span>
                            </td>
                            <td>${supplier.name}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary select-sap-code" 
                                        data-sap-code="${supplier.sap_code}">
                                    <i class="fas fa-check"></i> Select
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });

                updateSapResultsCount(sapCodes.length);
            }

            function setupSapSearch() {
                $('#sap-search').on('input', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    const rows = $('#sap-codes-tbody tr');
                    let visibleCount = 0;

                    rows.each(function() {
                        const sapCode = $(this).find('.sap-code-badge').text().toLowerCase();
                        const supplierName = $(this).find('td:nth-child(2)').text().toLowerCase();

                        if (sapCode.includes(searchTerm) || supplierName.includes(searchTerm)) {
                            $(this).show();
                            visibleCount++;
                        } else {
                            $(this).hide();
                        }
                    });

                    updateSapResultsCount(visibleCount);
                });

                $('#clear-search').on('click', function() {
                    $('#sap-search').val('');
                    $('#sap-codes-tbody tr').show();
                    updateSapResultsCount(window.sapCodesData.length);
                });

                // Sorting
                $('#sort-sap-asc').on('click', function() {
                    sortSapCodes('asc');
                });

                $('#sort-sap-desc').on('click', function() {
                    sortSapCodes('desc');
                });

                // Selection
                $(document).on('click', '.select-sap-code', function() {
                    const sapCode = $(this).data('sap-code');
                    $('#vendor_code').val(sapCode).trigger('input');
                    $('#sap-codes-modal').modal('hide');
                    toastr.success(`SAP code ${sapCode} selected`);
                });
            }

            function sortSapCodes(direction) {
                const tbody = $('#sap-codes-tbody');
                const rows = tbody.find('tr').toArray();

                rows.sort(function(a, b) {
                    const sapCodeA = $(a).find('.sap-code-badge').text();
                    const sapCodeB = $(b).find('.sap-code-badge').text();

                    if (direction === 'asc') {
                        return sapCodeA.localeCompare(sapCodeB);
                    } else {
                        return sapCodeB.localeCompare(sapCodeA);
                    }
                });

                tbody.empty().append(rows);
            }

            function updateSapResultsCount(count) {
                $('#sap-results-count').text(`${count} result${count !== 1 ? 's' : ''}`);
            }

            // ===== FORM SUBMISSION =====
            $('form').on('submit', function(e) {
                const changes = [];

                $('input, select, textarea').each(function() {
                    const fieldName = $(this).attr('name');
                    if (fieldName && originalValues[fieldName] !== undefined) {
                        const currentValue = $(this).val();
                        const originalValue = originalValues[fieldName];

                        if (currentValue !== originalValue) {
                            changes.push(fieldLabels[fieldName] || fieldName);
                        }
                    }
                });

                if ($('#attachment')[0].files.length > 0) {
                    changes.push('Attachment');
                }

                if (changes.length > 0) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirm Changes',
                        html: `You are about to update the following fields:<br><br><strong>${changes.join(', ')}</strong><br><br>Are you sure you want to proceed?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, update it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('form').off('submit').submit();
                        }
                    });
                }
            });

            // Show success message if exists
            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @endif

            // Show error message if exists
            @if (session('error'))
                toastr.error('{{ session('error') }}');
            @endif

            // Initial change tracking
            trackChanges();
        });
    </script>
@endsection
