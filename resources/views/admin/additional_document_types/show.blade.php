@extends('layouts.main')

@section('title_page')
    Additional Document Type Details
@endsection

@section('breadcrumb_title')
    admin / additional-document-types / show
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Additional Document Type Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.additional-document-types.index') }}">Additional
                                Document Types</a></li>
                        <li class="breadcrumb-item active">Details</li>
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
                            <h3 class="card-title">Additional Document Type Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.additional-document-types.index') }}"
                                    class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                                <button type="button" class="btn btn-warning btn-sm edit-additional-document-type"
                                    data-toggle="modal" data-target="#additionalDocumentTypeModal"
                                    data-id="{{ $additionalDocumentType->id }}"
                                    data-type-name="{{ $additionalDocumentType->type_name }}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="30%"><strong>ID:</strong></td>
                                            <td>{{ $additionalDocumentType->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type Name:</strong></td>
                                            <td>{{ $additionalDocumentType->type_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td>{{ $additionalDocumentType->created_at ? $additionalDocumentType->created_at->format('d M Y H:i') : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Updated:</strong></td>
                                            <td>{{ $additionalDocumentType->updated_at ? $additionalDocumentType->updated_at->format('d M Y H:i') : 'N/A' }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Additional Document Type Modal for Edit -->
    <div class="modal fade" id="additionalDocumentTypeModal" tabindex="-1" role="dialog"
        aria-labelledby="additionalDocumentTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="additionalDocumentTypeForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="additionalDocumentTypeModalLabel">Edit Document Type</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="type_name">Type Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="type_name" name="type_name" required>
                            <div class="invalid-feedback" id="type_name-error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Update Document Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Edit additional document type from show page
            $(document).on('click', '.edit-additional-document-type', function() {
                var documentTypeId = $(this).data('id');
                var documentTypeName = $(this).data('type-name');

                $('#additionalDocumentTypeForm').attr('action', '/admin/additional-document-types/' +
                    documentTypeId);
                $('#additionalDocumentTypeForm').append('<input type="hidden" name="_method" value="PUT">');

                $('#type_name').val(documentTypeName);
            });

            // Form submission
            $('#additionalDocumentTypeForm').submit(function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var url = $(this).attr('action');
                var method = $(this).attr('method');

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#additionalDocumentTypeModal').modal('hide');
                        toastr.success(response.message ||
                            'Document Type updated successfully.');
                        // Reload the page to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                $('#' + field).addClass('is-invalid');
                                $('#' + field + '-error').text(messages[0]);
                            });
                        } else {
                            toastr.error('An error occurred while updating the document type.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
