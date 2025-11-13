@extends('layouts.main')

@section('title_page')
    SAP ITO Sync
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
    <li class="breadcrumb-item active">SAP ITO Sync</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">SAP ITO Sync</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.sap-sync-ito') }}" id="sync-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" 
                                               class="form-control @error('start_date') is-invalid @enderror" 
                                               id="start_date" 
                                               name="start_date" 
                                               value="{{ old('start_date') }}"
                                               required>
                                        @error('start_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="end_date">End Date <span class="text-danger">*</span></label>
                                        <input type="date" 
                                               class="form-control @error('end_date') is-invalid @enderror" 
                                               id="end_date" 
                                               name="end_date" 
                                               value="{{ old('end_date') }}"
                                               required>
                                        @error('end_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="sync-btn">
                                    <i class="fas fa-sync"></i> Sync from SAP
                                </button>
                                <span class="ml-2 text-muted" id="sync-status"></span>
                            </div>
                        </form>

                        @if(session('sync_results'))
                            <div class="alert alert-info mt-3">
                                <h5><i class="icon fas fa-info"></i> Sync Results</h5>
                                <ul class="mb-0">
                                    <li><strong>Created:</strong> {{ session('sync_results')['success'] }} record(s)</li>
                                    <li><strong>Skipped:</strong> {{ session('sync_results')['skipped'] }} record(s)</li>
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
@endsection

@section('scripts')
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Toastr
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "8000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            // Show session messages
            @if (session('success'))
                toastr.success('{{ session('success') }}', 'Success');
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}', 'Error');
            @endif

            @if (session('warning'))
                toastr.warning('{{ session('warning') }}', 'Warning');
            @endif

            @if (session('info'))
                toastr.info('{{ session('info') }}', 'Info');
            @endif

            // Handle form submission
            $('#sync-form').on('submit', function(e) {
                const $btn = $('#sync-btn');
                const $status = $('#sync-status');
                
                // Disable button and show loading
                $btn.prop('disabled', true);
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
                $status.html('<i class="fas fa-spinner fa-spin"></i> Please wait, this may take a moment...');
            });
        });
    </script>
@endsection
