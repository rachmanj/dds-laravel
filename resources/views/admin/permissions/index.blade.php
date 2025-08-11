@extends('layouts.main')

@section('title_page')
    Permissions Management
@endsection

@section('breadcrumb_title')
    admin / permissions
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
                    <h1 class="m-0">Permissions Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Permissions</li>
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
                    <h3 class="card-title">Permissions List</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                            data-target="#permissionModal" id="addPermissionBtn">
                            <i class="fas fa-plus"></i> Add New Permission
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="permissions-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Guard</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Permission Modal -->
    <div class="modal fade" id="permissionModal" tabindex="-1" role="dialog" aria-labelledby="permissionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="permissionForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionModalLabel">Add New Permission</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Permission Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="guard_name">Guard Name</label>
                            <select class="form-control" id="guard_name" name="guard_name">
                                <option value="web">Web</option>
                                <option value="api">API</option>
                            </select>
                            <div class="invalid-feedback" id="guard_name-error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save Permission</button>
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
            // Initialize DataTable
            var table = $('#permissions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.permissions.data') }}",
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'formatted_name',
                        name: 'name'
                    },
                    {
                        data: 'guard_name',
                        name: 'guard_name'
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

            // Add new permission
            $('#addPermissionBtn').click(function() {
                resetForm();
                $('#permissionModalLabel').text('Add New Permission');
                $('#permissionForm').attr('action', "{{ route('admin.permissions.store') }}");
                $('#permissionForm').attr('method', 'POST');
                $('#submitBtn').text('Save Permission');
            });

            // Edit permission
            $(document).on('click', '.edit-permission', function() {
                var permissionId = $(this).data('id');
                var permissionName = $(this).data('name');
                var permissionGuard = $(this).data('guard');

                resetForm();
                $('#permissionModalLabel').text('Edit Permission');
                $('#permissionForm').attr('action', '/admin/permissions/' + permissionId);
                $('#permissionForm').attr('method', 'POST');
                $('#permissionForm').append('<input type="hidden" name="_method" value="PUT">');
                $('#submitBtn').text('Update Permission');

                $('#name').val(permissionName);
                $('#guard_name').val(permissionGuard);
            });

            // Form submission
            $('#permissionForm').submit(function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var url = $(this).attr('action');

                $.ajax({
                    url: url,
                    type: 'POST', // Always use POST, method override handled by _method field
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#permissionModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'Permission saved successfully.');
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
                            toastr.error('An error occurred while saving the permission.');
                        }
                    }
                });
            });

            // Reset form
            function resetForm() {
                $('#permissionForm')[0].reset();
                $('#permissionForm').find('input[name="_method"]').remove();
                $('#permissionForm').find('.is-invalid').removeClass('is-invalid');
                $('#permissionForm').find('.invalid-feedback').text('');
            }

            // Handle delete permission
            $(document).on('click', '.delete-permission', function() {
                var permissionId = $(this).data('id');
                var permissionName = $(this).data('name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete permission '" + permissionName + "'?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/permissions/' + permissionId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    toastr.success(response.message);
                                    table.ajax.reload();
                                } else {
                                    toastr.error(response.message);
                                }
                            },
                            error: function() {
                                toastr.error(
                                    'An error occurred while deleting the permission.'
                                );
                            }
                        });
                    }
                });
            });

            // Show flash messages with toastr
            @if (session('success'))
                toastr.success("{{ session('success') }}");
            @endif

            @if (session('error'))
                toastr.error("{{ session('error') }}");
            @endif
        });
    </script>
@endsection
