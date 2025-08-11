@extends('layouts.main')

@section('title_page')
    Additional Document Types Management
@endsection

@section('breadcrumb_title')
    admin / additional-document-types
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Additional Document Types Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Additional Document Types</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Additional Document Types List</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                            data-target="#additionalDocumentTypeModal" id="addAdditionalDocumentTypeBtn">
                            <i class="fas fa-plus"></i> Add New Document Type
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="additional-document-types-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Additional Document Type Modal -->
    <div class="modal fade" id="additionalDocumentTypeModal" tabindex="-1" role="dialog"
        aria-labelledby="additionalDocumentTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="additionalDocumentTypeForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="additionalDocumentTypeModalLabel">Add New Document Type</h5>
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
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save Document Type</button>
                    </div>
                </form>
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
            var table = $('#additional-document-types-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.additional-document-types.data') }}",
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'type_name',
                        name: 'type_name'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                responsive: true,
                autoWidth: false,
            });

            // Add new additional document type
            $('#addAdditionalDocumentTypeBtn').click(function() {
                resetForm();
                $('#additionalDocumentTypeModalLabel').text('Add New Document Type');
                $('#additionalDocumentTypeForm').attr('action',
                    "{{ route('admin.additional-document-types.store') }}");
                $('#additionalDocumentTypeForm').attr('method', 'POST');
                $('#submitBtn').text('Save Document Type');
            });

            // Edit additional document type
            $(document).on('click', '.edit-additional-document-type', function() {
                var documentTypeId = $(this).data('id');
                var documentTypeName = $(this).data('type-name');

                resetForm();
                $('#additionalDocumentTypeModalLabel').text('Edit Document Type');
                $('#additionalDocumentTypeForm').attr('action', '/admin/additional-document-types/' +
                    documentTypeId);
                $('#additionalDocumentTypeForm').append('<input type="hidden" name="_method" value="PUT">');
                $('#submitBtn').text('Update Document Type');

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
                        table.ajax.reload();
                        toastr.success(response.message || 'Document Type saved successfully.');
                        resetForm();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                $('#' + field).addClass('is-invalid');
                                $('#' + field + '-error').text(messages[0]);
                            });
                        } else {
                            toastr.error('An error occurred while saving the document type.');
                        }
                    }
                });
            });

            // Reset form
            function resetForm() {
                $('#additionalDocumentTypeForm')[0].reset();
                $('#additionalDocumentTypeForm').find('input[name="_method"]').remove();
                $('#additionalDocumentTypeForm').find('.is-invalid').removeClass('is-invalid');
                $('#additionalDocumentTypeForm').find('.invalid-feedback').text('');
            }

            // Delete additional document type
            $(document).on('click', '.delete-additional-document-type', function() {
                var documentTypeId = $(this).data('id');
                var documentTypeName = $(this).data('name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/additional-document-types/' + documentTypeId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                table.ajax.reload();
                                toastr.success(response.message ||
                                    'Document Type deleted successfully.');
                            },
                            error: function() {
                                toastr.error(
                                    'An error occurred while deleting the document type.'
                                    );
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
