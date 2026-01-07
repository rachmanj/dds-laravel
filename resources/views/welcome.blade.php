@extends('layouts.main')

@section('title_page', 'Welcome')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-primary card-outline">
                <div class="card-body text-center py-5">
                    <h1 class="display-4 mb-3">
                        <i class="fas fa-hand-sparkles text-primary"></i>
                        Welcome back, {{ auth()->user()->name }}!
                    </h1>
                    <p class="lead text-muted mb-4">Your dashboard is loading...</p>
                    
                    <div class="mt-4 mb-4">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    
                    @if(isset($quickStats) && ($quickStats['pending_distributions'] ?? 0) > 0)
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>{{ $quickStats['pending_distributions'] }}</strong> pending distribution(s) require your attention.
                        </div>
        @endif
                    
                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg mr-2">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                        <a href="{{ route('additional-documents.index') }}" class="btn btn-outline-primary btn-lg mr-2">
                            <i class="fas fa-file-alt"></i> View Documents
                        </a>
                        <a href="{{ route('distributions.index') }}" class="btn btn-outline-info btn-lg">
                            <i class="fas fa-truck"></i> View Distributions
                        </a>
                </div>
                    
                    <div class="mt-4 text-muted">
                        <small>You will be redirected automatically in a few seconds...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>

<script>
// Auto-redirect to dashboard after 3 seconds (or let user click button)
setTimeout(function() {
    window.location.href = '{{ route('dashboard') }}';
}, 3000);
</script>
@endsection
