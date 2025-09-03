@extends('layouts.main')

@section('title_page', 'Document Status Test')

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Document Status Test</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Simple Test Page</h3>
                        </div>
                        <div class="card-body">
                            <p>This is a simple test page to check if the basic layout works.</p>
                            <p>Current time: {{ now() }}</p>
                            <p>User: {{ Auth::user()->name ?? 'Not logged in' }}</p>

                            <div class="alert alert-info">
                                <h4><i class="fas fa-info-circle"></i> Test Alert</h4>
                                <p>This alert tests FontAwesome icons.</p>
                            </div>

                            <div class="btn-group">
                                <button type="button" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Primary Button
                                </button>
                                <button type="button" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Secondary Button
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('styles')
    <style>
        .test-icon {
            font-size: 2rem;
            color: #007bff;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            console.log('Test page loaded successfully');
        });
    </script>
@endsection
