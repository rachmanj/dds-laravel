@extends('layouts.main')

@section('title_page')
    Supplier Details
@endsection

@section('breadcrumb_title')
    admin / suppliers / details
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Supplier Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.suppliers.index') }}">Suppliers</a></li>
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
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Supplier Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>ID:</strong></td>
                                            <td>{{ $supplier->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>SAP Code:</strong></td>
                                            <td>{{ $supplier->sap_code ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td>{{ $supplier->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td>
                                                @if ($supplier->type === 'vendor')
                                                    <span class="badge badge-primary">Vendor</span>
                                                @else
                                                    <span class="badge badge-info">Customer</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>City:</strong></td>
                                            <td>{{ $supplier->city ?: '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Payment Project:</strong></td>
                                            <td>
                                                @php
                                                    $project = \App\Models\Project::where(
                                                        'code',
                                                        $supplier->payment_project,
                                                    )->first();
                                                @endphp
                                                {{ $supplier->payment_project }}@if ($project)
                                                    - {{ $project->owner }}
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>NPWP:</strong></td>
                                            <td>{{ $supplier->npwp ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                @if ($supplier->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created By:</strong></td>
                                            <td>{{ $supplier->creator->name ?? 'System' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created At:</strong></td>
                                            <td>{{ $supplier->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            @if ($supplier->address)
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Address</h5>
                                        <p>{{ $supplier->address }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="btn-group-vertical w-100">
                                <button type="button" class="btn btn-warning mb-2 edit-supplier" data-toggle="modal"
                                    data-target="#supplierModal" data-id="{{ $supplier->id }}"
                                    data-sap-code="{{ $supplier->sap_code ?? '' }}" data-name="{{ $supplier->name }}"
                                    data-type="{{ $supplier->type }}" data-city="{{ $supplier->city ?? '' }}"
                                    data-payment-project="{{ $supplier->payment_project }}"
                                    data-address="{{ $supplier->address ?? '' }}" data-npwp="{{ $supplier->npwp ?? '' }}"
                                    data-active="{{ $supplier->is_active ? '1' : '0' }}">
                                    <i class="fas fa-edit"></i> Edit Supplier
                                </button>
                                <button type="button" class="btn btn-danger delete-supplier" data-id="{{ $supplier->id }}"
                                    data-name="{{ $supplier->name }}">
                                    <i class="fas fa-trash"></i> Delete Supplier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Supplier Modal for Edit -->
    <div class="modal fade" id="supplierModal" tabindex="-1" role="dialog" aria-labelledby="supplierModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="supplierForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="supplierModalLabel">Edit Supplier</h5>
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
                                            name="is_active" value="1">
                                        <label class="custom-control-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
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
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
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
                                    toastr.success(response.message);
                                    setTimeout(function() {
                                        window.location.href =
                                            '{{ route('admin.suppliers.index') }}';
                                    }, 1000);
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
                $('#supplierForm').find('input[name="_method"]').remove();
                $('.invalid-feedback').hide();
                $('.form-control').removeClass('is-invalid');
            });
        });
    </script>
@endsection
