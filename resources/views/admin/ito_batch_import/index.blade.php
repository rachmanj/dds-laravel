@extends('layouts.main')

@section('title_page')
    ITO Batch Import
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">ITO Batch Import</li>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('success') }}
                </div>
            @endif

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Upload Batch PDF</h3>
                </div>
                <form action="{{ route('ito-batch-import.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <p class="text-muted">
                            Upload a multi-page PDF containing scanned ITO documents (one ITO per page).
                            The system will split each page, read the ITO number, and auto-attach to matching records.
                        </p>
                        <div class="form-group">
                            <label for="pdf">Batch PDF File</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('pdf') is-invalid @enderror" id="pdf" name="pdf" accept="application/pdf" required>
                                <label class="custom-file-label" for="pdf">Choose PDF file...</label>
                            </div>
                            @error('pdf')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload &amp; Process
                        </button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Import History</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="batches-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Filename</th>
                                    <th>Pages</th>
                                    <th>Status</th>
                                    <th>Summary</th>
                                    <th>Uploaded By</th>
                                    <th>Created</th>
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
    <script src="{{ asset('adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <script>
        $(function() {
            bsCustomFileInput.init();

            $('#batches-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('ito-batch-import.data') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'filename', name: 'filename' },
                    { data: 'total_pages', name: 'total_pages' },
                    { data: 'status_badge', name: 'status', orderable: false, searchable: false },
                    { data: 'summary', name: 'summary', orderable: false, searchable: false },
                    { data: 'creator_name', name: 'creator.name', orderable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                responsive: true,
                autoWidth: false,
                order: [[0, 'desc']]
            });
        });
    </script>
@endsection
