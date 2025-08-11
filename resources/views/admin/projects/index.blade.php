@extends('layouts.main')

@section('title_page')
    Projects Management
@endsection

@section('breadcrumb_title')
    admin / projects
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
                    <h1 class="m-0">Projects Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Projects</li>
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
                    <h3 class="card-title">Projects List</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                            data-target="#projectModal" id="addProjectBtn">
                            <i class="fas fa-plus"></i> Add New Project
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="projects-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Code</th>
                                    <th>Owner</th>
                                    <th>Location</th>
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

    <!-- Project Modal -->
    <div class="modal fade" id="projectModal" tabindex="-1" role="dialog" aria-labelledby="projectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="projectForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="projectModalLabel">Add New Project</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">Project Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="code" name="code" required>
                                    <div class="invalid-feedback" id="code-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="owner">Owner</label>
                                    <input type="text" class="form-control" id="owner" name="owner">
                                    <div class="invalid-feedback" id="owner-error"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <input type="text" class="form-control" id="location" name="location">
                                    <div class="invalid-feedback" id="location-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active">
                                        <label class="custom-control-label" for="is_active">Active Status</label>
                                    </div>
                                    <div class="invalid-feedback" id="is_active-error"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save Project</button>
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
            var table = $('#projects-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.projects.data') }}",
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'owner',
                        name: 'owner'
                    },
                    {
                        data: 'location',
                        name: 'location'
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

            // Add new project
            $('#addProjectBtn').click(function() {
                resetForm();
                $('#projectModalLabel').text('Add New Project');
                $('#projectForm').attr('action', "{{ route('admin.projects.store') }}");
                $('#projectForm').attr('method', 'POST');
                $('#submitBtn').text('Save Project');
            });

            // Edit project
            $(document).on('click', '.edit-project', function() {
                var projectId = $(this).data('id');
                var projectCode = $(this).data('code');
                var projectOwner = $(this).data('owner');
                var projectLocation = $(this).data('location');
                var projectActive = $(this).data('active');

                resetForm();
                $('#projectModalLabel').text('Edit Project');
                $('#projectForm').attr('action', '/admin/projects/' + projectId);
                $('#projectForm').append('<input type="hidden" name="_method" value="PUT">');
                $('#submitBtn').text('Update Project');

                $('#code').val(projectCode);
                $('#owner').val(projectOwner);
                $('#location').val(projectLocation);
                if (projectActive) {
                    $('#is_active').prop('checked', true);
                }
            });

            // Form submission
            $('#projectForm').submit(function(e) {
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
                        $('#projectModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'Project saved successfully.');
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
                            toastr.error('An error occurred while saving the project.');
                        }
                    }
                });
            });

            // Reset form
            function resetForm() {
                $('#projectForm')[0].reset();
                $('#projectForm').find('input[name="_method"]').remove();
                $('#projectForm').find('.is-invalid').removeClass('is-invalid');
                $('#projectForm').find('.invalid-feedback').text('');
            }

            // Delete project
            $(document).on('click', '.delete-project', function() {
                var projectId = $(this).data('id');
                var projectName = $(this).data('name');

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
                            url: '/admin/projects/' + projectId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    table.ajax.reload();
                                    toastr.success(response.message);
                                } else {
                                    toastr.error('Error deleting project');
                                }
                            },
                            error: function() {
                                toastr.error('Error deleting project');
                            }
                        });
                    }
                });
            });

            // Show success message if exists
            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @endif
        });
    </script>
@endsection
