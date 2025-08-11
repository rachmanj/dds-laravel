@extends('layouts.main')

@section('title_page')
    Department Details
@endsection

@section('breadcrumb_title')
    admin / departments / show
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Department Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.departments.index') }}">Departments</a></li>
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
                            <h3 class="card-title">Department Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.departments.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                                <button type="button" class="btn btn-warning btn-sm edit-department" data-toggle="modal"
                                    data-target="#departmentModal" data-id="{{ $department->id }}"
                                    data-name="{{ $department->name }}" data-project="{{ $department->project }}"
                                    data-location-code="{{ $department->location_code }}"
                                    data-transit-code="{{ $department->transit_code }}"
                                    data-akronim="{{ $department->akronim }}" data-sap-code="{{ $department->sap_code }}">
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
                                            <td>{{ $department->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td>{{ $department->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Akronim:</strong></td>
                                            <td>{{ $department->akronim ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Project:</strong></td>
                                            <td>{{ $department->project ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="30%"><strong>Location Code:</strong></td>
                                            <td>{{ $department->location_code ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Transit Code:</strong></td>
                                            <td>{{ $department->transit_code ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>SAP Code:</strong></td>
                                            <td>{{ $department->sap_code ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td>{{ $department->created_at ? $department->created_at->format('d M Y H:i') : 'N/A' }}
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

    <!-- Department Modal for Edit -->
    <div class="modal fade" id="departmentModal" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="departmentForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="departmentModalLabel">Edit Department</h5>
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
                        <button type="submit" class="btn btn-primary" id="submitBtn">Update Department</button>
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
            // Edit department from show page
            $(document).on('click', '.edit-department', function() {
                var departmentId = $(this).data('id');
                var departmentName = $(this).data('name');
                var departmentProject = $(this).data('project');
                var departmentLocationCode = $(this).data('location-code');
                var departmentTransitCode = $(this).data('transit-code');
                var departmentAkronim = $(this).data('akronim');
                var departmentSapCode = $(this).data('sap-code');

                $('#departmentForm').attr('action', '/admin/departments/' + departmentId);
                $('#departmentForm').append('<input type="hidden" name="_method" value="PUT">');

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
                        toastr.success(response.message || 'Department updated successfully.');
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
                            toastr.error('An error occurred while updating the department.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
