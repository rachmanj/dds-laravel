@extends('layouts.main')

@section('title_page')
    Suppliers Management
@endsection

@section('breadcrumb_title')
    admin / suppliers
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
                    <h1 class="m-0">Suppliers Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Suppliers</li>
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
                    <h3 class="card-title">Suppliers List</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                            data-target="#supplierModal" id="addSupplierBtn">
                            <i class="fas fa-plus"></i> Add New Supplier
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="suppliers-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>SAP Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>City</th>
                                    <th>Payment Project</th>
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

    <!-- Supplier Modal -->
    <div class="modal fade" id="supplierModal" tabindex="-1" role="dialog" aria-labelledby="supplierModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="supplierForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="supplierModalLabel">Add New Supplier</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sap_code">SAP Code</label>
                                    <input type="text" class="form-control" id="sap_code" name="sap_code"
                                        maxlength="50">
                                    <div class="invalid-feedback" id="sap_code-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Supplier Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                        maxlength="255">
                                    <div class="invalid-feedback" id="name-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Type <span class="text-danger">*</span></label>
                                    <select class="form-control" id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="vendor">Vendor</option>
                                        <option value="customer">Customer</option>
                                    </select>
                                    <div class="invalid-feedback" id="type-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" class="form-control" id="city" name="city"
                                        maxlength="255">
                                    <div class="invalid-feedback" id="city-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_project">Payment Project <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="payment_project" name="payment_project" required>
                                        <option value="">Select Payment Project</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->code }}">{{ $project->code }} -
                                                {{ $project->owner }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="payment_project-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="npwp">NPWP</label>
                                    <input type="text" class="form-control" id="npwp" name="npwp"
                                        maxlength="50">
                                    <div class="invalid-feedback" id="npwp-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                    <div class="invalid-feedback" id="address-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active"
                                            name="is_active" value="1" checked>
                                        <label class="custom-control-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Supplier</button>
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
            var table = $('#suppliers-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: '{{ route('admin.suppliers.data') }}',
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'sap_code',
                        name: 'sap_code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'type_badge',
                        name: 'type'
                    },
                    {
                        data: 'city',
                        name: 'city'
                    },
                    {
                        data: 'payment_project_info',
                        name: 'payment_project'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                responsive: true,
                order: [
                    [0, 'desc']
                ]
            });

            // Add Supplier Button
            $('#addSupplierBtn').click(function() {
                $('#supplierForm')[0].reset();
                $('#supplierForm').attr('action', '{{ route('admin.suppliers.store') }}');
                $('#supplierForm').attr('method', 'POST');
                $('#supplierModalLabel').text('Add New Supplier');
                $('.invalid-feedback').hide();
                $('.form-control').removeClass('is-invalid');
            });

            // Edit Supplier Button
            $(document).on('click', '.edit-supplier', function() {
                var id = $(this).data('id');
                var sapCode = $(this).data('sap-code');
                var name = $(this).data('name');
                var type = $(this).data('type');
                var city = $(this).data('city');
                var paymentProject = $(this).data('payment-project');
                var address = $(this).data('address');
                var npwp = $(this).data('npwp');
                var active = $(this).data('active');

                $('#supplierForm').attr('action', '/admin/suppliers/' + id);
                $('#supplierForm').attr('method', 'POST');
                $('#supplierForm').append('<input type="hidden" name="_method" value="PUT">');
                $('#supplierModalLabel').text('Edit Supplier');

                $('#sap_code').val(sapCode);
                $('#name').val(name);
                $('#type').val(type);
                $('#city').val(city);
                $('#payment_project').val(paymentProject);
                $('#address').val(address);
                $('#npwp').val(npwp);
                $('#is_active').prop('checked', active == 1);

                $('.invalid-feedback').hide();
                $('.form-control').removeClass('is-invalid');
            });

            // Form Submit
            $('#supplierForm').submit(function(e) {
                e.preventDefault();

                var form = $(this);
                var url = form.attr('action');
                var method = form.attr('method');

                $.ajax({
                    url: url,
                    method: method,
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#supplierModal').modal('hide');
                            table.ajax.reload();
                            toastr.success(response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $('.invalid-feedback').hide();
                            $('.form-control').removeClass('is-invalid');

                            $.each(errors, function(field, messages) {
                                $('#' + field).addClass('is-invalid');
                                $('#' + field + '-error').text(messages[0]).show();
                            });
                        } else {
                            toastr.error('An error occurred. Please try again.');
                        }
                    }
                });
            });

            // Delete Supplier
            $(document).on('click', '.delete-supplier', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');

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
                            url: '/admin/suppliers/' + id,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    table.ajax.reload();
                                    toastr.success(response.message);
                                }
                            },
                            error: function() {
                                toastr.error('An error occurred. Please try again.');
                            }
                        });
                    }
                });
            });

            // Modal hidden event
            $('#supplierModal').on('hidden.bs.modal', function() {
                $('#supplierForm')[0].reset();
                $('#supplierForm').find('input[name="_method"]').remove();
                $('.invalid-feedback').hide();
                $('.form-control').removeClass('is-invalid');
            });
        });
    </script>
@endsection
