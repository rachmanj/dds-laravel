@extends('layouts.main')

@section('title_page')
    Signature Specimens
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Signature Specimens</li>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Signature Specimens</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.signature-specimens.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Specimen
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="specimens-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>NIK</th>
                                    <th>Department</th>
                                    <th>Projects</th>
                                    <th>Images</th>
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
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <script>
        $(function() {
            var table = $('#specimens-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.signature-specimens.data') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'nik', name: 'nik' },
                    { data: 'department_name', name: 'department_name', orderable: false, searchable: false },
                    { data: 'projects_list', name: 'projects_list', orderable: false, searchable: false },
                    { data: 'image_count', name: 'image_count', orderable: false, searchable: false },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                responsive: true,
                autoWidth: false,
            });

            $(document).on('click', '.delete-specimen', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Delete specimen "' + name + '"?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/signature-specimens/' + id,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                if (response.success) {
                                    toastr.success(response.message);
                                    table.ajax.reload();
                                } else {
                                    toastr.error(response.message || 'Delete failed.');
                                }
                            },
                            error: function() {
                                toastr.error('An error occurred while deleting the specimen.');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
