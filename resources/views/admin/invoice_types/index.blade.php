@extends('layouts.main')

@section('title_page')
    Invoice Types Management
@endsection

@section('breadcrumb_title')
    admin / invoice-types
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
                    <h1 class="m-0">Invoice Types Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Invoice Types</li>
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
                    <h3 class="card-title">Invoice Types List</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                            data-target="#invoiceTypeModal" id="addInvoiceTypeBtn">
                            <i class="fas fa-plus"></i> Add New Invoice Type
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="invoice-types-table">
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

    <!-- Invoice Type Modal -->
    <div class="modal fade" id="invoiceTypeModal" tabindex="-1" role="dialog" aria-labelledby="invoiceTypeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="invoiceTypeForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="invoiceTypeModalLabel">Add New Invoice Type</h5>
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
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save Invoice Type</button>
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
            var table = $('#invoice-types-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.invoice-types.data') }}",
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

            // Add new invoice type
            $('#addInvoiceTypeBtn').click(function() {
                resetForm();
                $('#invoiceTypeModalLabel').text('Add New Invoice Type');
                $('#invoiceTypeForm').attr('action', "{{ route('admin.invoice-types.store') }}");
                $('#invoiceTypeForm').attr('method', 'POST');
                $('#submitBtn').text('Save Invoice Type');
            });

            // Edit invoice type
            $(document).on('click', '.edit-invoice-type', function() {
                var invoiceTypeId = $(this).data('id');
                var invoiceTypeName = $(this).data('type-name');

                resetForm();
                $('#invoiceTypeModalLabel').text('Edit Invoice Type');
                $('#invoiceTypeForm').attr('action', '/admin/invoice-types/' + invoiceTypeId);
                $('#invoiceTypeForm').append('<input type="hidden" name="_method" value="PUT">');
                $('#submitBtn').text('Update Invoice Type');

                $('#type_name').val(invoiceTypeName);
            });

            // Form submission
            $('#invoiceTypeForm').submit(function(e) {
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
                        $('#invoiceTypeModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'Invoice Type saved successfully.');
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
                            toastr.error('An error occurred while saving the invoice type.');
                        }
                    }
                });
            });

            // Reset form
            function resetForm() {
                $('#invoiceTypeForm')[0].reset();
                $('#invoiceTypeForm').find('input[name="_method"]').remove();
                $('#invoiceTypeForm').find('.is-invalid').removeClass('is-invalid');
                $('#invoiceTypeForm').find('.invalid-feedback').text('');
            }

            // Delete invoice type
            $(document).on('click', '.delete-invoice-type', function() {
                var invoiceTypeId = $(this).data('id');
                var invoiceTypeName = $(this).data('name');

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
                            url: '/admin/invoice-types/' + invoiceTypeId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                table.ajax.reload();
                                toastr.success(response.message ||
                                    'Invoice Type deleted successfully.');
                            },
                            error: function() {
                                toastr.error(
                                    'An error occurred while deleting the invoice type.'
                                    );
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
