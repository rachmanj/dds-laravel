@extends('layouts.main')

@section('title_page')
    Departments Management
@endsection

@section('breadcrumb_title')
    admin / departments
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
                    <h1 class="m-0">Departments Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Departments</li>
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
                    <h3 class="card-title">Departments List</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                            data-target="#departmentModal" id="addDepartmentBtn">
                            <i class="fas fa-plus"></i> Add New Department
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="departments-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Project</th>
                                    <th>Location Code</th>
                                    <th>Transit Code</th>
                                    <th>Akronim</th>
                                    <th>SAP Code</th>
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

    <!-- Department Modal -->
    <div class="modal fade" id="departmentModal" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="departmentForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="departmentModalLabel">Add New Department</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Department Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="invalid-feedback" id="name-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="akronim">Akronim <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="akronim" name="akronim" required>
                                    <div class="invalid-feedback" id="akronim-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <input type="text" class="form-control" id="project" name="project">
                                    <div class="invalid-feedback" id="project-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sap_code">SAP Code</label>
                                    <input type="text" class="form-control" id="sap_code" name="sap_code">
                                    <div class="invalid-feedback" id="sap_code-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location_code">Location Code</label>
                                    <input type="text" class="form-control" id="location_code" name="location_code">
                                    <div class="invalid-feedback" id="location_code-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transit_code">Transit Code</label>
                                    <input type="text" class="form-control" id="transit_code" name="transit_code">
                                    <div class="invalid-feedback" id="transit_code-error"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save Department</button>
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
            var table = $('#departments-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.departments.data') }}",
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'project',
                        name: 'project'
                    },
                    {
                        data: 'location_code',
                        name: 'location_code'
                    },
                    {
                        data: 'transit_code',
                        name: 'transit_code'
                    },
                    {
                        data: 'akronim',
                        name: 'akronim'
                    },
                    {
                        data: 'sap_code',
                        name: 'sap_code'
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

            // Add new department
            $('#addDepartmentBtn').click(function() {
                resetForm();
                $('#departmentModalLabel').text('Add New Department');
                $('#departmentForm').attr('action', "{{ route('admin.departments.store') }}");
                $('#departmentForm').attr('method', 'POST');
                $('#submitBtn').text('Save Department');
            });

            // Edit department
            $(document).on('click', '.edit-department', function() {
                var departmentId = $(this).data('id');
                var departmentName = $(this).data('name');
                var departmentProject = $(this).data('project');
                var departmentLocationCode = $(this).data('location-code');
                var departmentTransitCode = $(this).data('transit-code');
                var departmentAkronim = $(this).data('akronim');
                var departmentSapCode = $(this).data('sap-code');

                resetForm();
                $('#departmentModalLabel').text('Edit Department');
                $('#departmentForm').attr('action', '/admin/departments/' + departmentId);
                $('#departmentForm').append('<input type="hidden" name="_method" value="PUT">');
                $('#submitBtn').text('Update Department');

                $('#name').val(departmentName);
                $('#project').val(departmentProject);
                $('#location_code').val(departmentLocationCode);
                $('#transit_code').val(departmentTransitCode);
                $('#akronim').val(departmentAkronim);
                $('#sap_code').val(departmentSapCode);
            });

            // Form submission
            $('#departmentForm').submit(function(e) {
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
                        $('#departmentModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'Department saved successfully.');
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
                            toastr.error('An error occurred while saving the department.');
                        }
                    }
                });
            });

            // Reset form
            function resetForm() {
                $('#departmentForm')[0].reset();
                $('#departmentForm').find('input[name="_method"]').remove();
                $('#departmentForm').find('.is-invalid').removeClass('is-invalid');
                $('#departmentForm').find('.invalid-feedback').text('');
            }

            // Delete department
            $(document).on('click', '.delete-department', function() {
                var departmentId = $(this).data('id');
                var departmentName = $(this).data('name');

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
                            url: '/admin/departments/' + departmentId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                table.ajax.reload();
                                toastr.success(response.message ||
                                    'Department deleted successfully.');
                            },
                            error: function() {
                                toastr.error(
                                    'An error occurred while deleting the department.'
                                    );
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
