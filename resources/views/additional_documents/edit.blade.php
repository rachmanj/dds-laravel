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
                            <h3 class="card-title">Document Information</h3>
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
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type_id">Document Type <span class="text-danger">*</span></label>
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
                                            @error('type_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="document_number">Document Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('document_number') is-invalid @enderror"
                                                id="document_number" name="document_number"
                                                value="{{ old('document_number', $additionalDocument->document_number) }}"
                                                required>
                                            @error('document_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="document_date">Document Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('document_date') is-invalid @enderror"
                                                id="document_date" name="document_date"
                                                value="{{ old('document_date', $additionalDocument->document_date ? $additionalDocument->document_date->format('Y-m-d') : '') }}"
                                                required>
                                            @error('document_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
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
                                                value="{{ old('receive_date', $additionalDocument->receive_date ? $additionalDocument->receive_date->format('Y-m-d') : '') }}"
                                                required>
                                            @error('receive_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
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
                                                value="{{ old('po_no', $additionalDocument->po_no) }}" maxlength="50">
                                            @error('po_no')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cur_loc">Current Location</label>
                                            <input type="text" class="form-control" id="cur_loc"
                                                value="{{ $additionalDocument->cur_loc ?? 'Not assigned' }}"
                                                placeholder="{{ $additionalDocument->cur_loc ?? 'No location assigned' }}"
                                                disabled>
                                            <small class="form-text text-muted">
                                                @if ($additionalDocument->cur_loc)
                                                    Document location: {{ $additionalDocument->cur_loc }} (cannot be
                                                    changed)
                                                @else
                                                    No location assigned to this document
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="remarks">Remarks</label>
                                            <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3">{{ old('remarks', $additionalDocument->remarks) }}</textarea>
                                            @error('remarks')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="attachment">Attachment</label>
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
                                                Allowed file types: PDF, DOC, DOCX, JPG, JPEG, PNG (Max: 2MB)
                                            </small>
                                            @error('attachment')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Info -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle"></i> Document Information</h6>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>Created By:</strong>
                                                    {{ $additionalDocument->creator->name ?? 'Unknown' }}
                                                    @if ($additionalDocument->creator && $additionalDocument->creator->department)
                                                        <br><small
                                                            class="text-muted">{{ $additionalDocument->creator->department->name }}</small>
                                                    @endif
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Created Date:</strong>
                                                    {{ $additionalDocument->created_at->format('d/m/Y H:i') }}
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Last Updated:</strong>
                                                    {{ $additionalDocument->updated_at->format('d/m/Y H:i') }}
                                                    @if ($additionalDocument->updated_at != $additionalDocument->created_at)
                                                        <br><small class="text-muted">Modified
                                                            {{ $additionalDocument->updated_at->diffForHumans() }}</small>
                                                    @endif
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Status:</strong>
                                                    <span
                                                        class="badge badge-{{ $additionalDocument->status === 'open' ? 'success' : 'secondary' }}">
                                                        {{ ucfirst($additionalDocument->status) }}
                                                    </span>
                                                    @if ($additionalDocument->status === 'closed')
                                                        <br><small class="text-muted">Document is archived</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
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

            // Form validation and confirmation
            $('form').on('submit', function(e) {
                var hasChanges = false;
                var originalValues = {
                    'type_id': '{{ $additionalDocument->type_id }}',
                    'document_number': '{{ $additionalDocument->document_number }}',
                    'document_date': '{{ $additionalDocument->document_date ? $additionalDocument->document_date->format('Y-m-d') : '' }}',
                    'receive_date': '{{ $additionalDocument->receive_date ? $additionalDocument->receive_date->format('Y-m-d') : '' }}',
                    'po_no': '{{ $additionalDocument->po_no }}',
                    'remarks': '{{ $additionalDocument->remarks }}'
                };

                // Check if any field has changed
                $('input, select, textarea').each(function() {
                    var fieldName = $(this).attr('name');
                    if (fieldName && originalValues[fieldName] !== undefined) {
                        if ($(this).val() !== originalValues[fieldName]) {
                            hasChanges = true;
                            return false; // break the loop
                        }
                    }
                });

                // Check if a new file is selected
                if ($('#attachment')[0].files.length > 0) {
                    hasChanges = true;
                }

                if (hasChanges) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirm Changes',
                        text: 'Are you sure you want to update this document?',
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

            // Real-time validation feedback
            $('#document_number').on('input', function() {
                var value = $(this).val();
                if (value.length < 3) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after(
                            '<div class="invalid-feedback">Document number must be at least 3 characters long.</div>'
                        );
                    }
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            });

            // Date validation
            $('#document_date, #receive_date').on('change', function() {
                var documentDate = $('#document_date').val();
                var receiveDate = $('#receive_date').val();

                if (documentDate && receiveDate) {
                    if (new Date(receiveDate) < new Date(documentDate)) {
                        $('#receive_date').addClass('is-invalid');
                        if (!$('#receive_date').next('.invalid-feedback').length) {
                            $('#receive_date').after(
                                '<div class="invalid-feedback">Receive date cannot be earlier than document date.</div>'
                            );
                        }
                    } else {
                        $('#receive_date').removeClass('is-invalid');
                        $('#receive_date').next('.invalid-feedback').remove();
                    }
                }
            });

            // Show success message if exists
            @if (session('success'))
                Swal.fire({
                    title: 'Success!',
                    text: '{{ session('success') }}',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            @endif

            // Show error message if exists
            @if (session('error'))
                Swal.fire({
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            @endif
        });
    </script>
@endsection
