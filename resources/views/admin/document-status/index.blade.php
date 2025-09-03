@extends('layouts.main')

@section('title_page', 'Document Status Management')

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Document Status</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Status Overview Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $statusCounts['available'] ?? 0 }}</h3>
                            <p>Available for Distribution</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $statusCounts['in_transit'] ?? 0 }}</h3>
                            <p>In Transit</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ $statusCounts['distributed'] ?? 0 }}</h3>
                            <p>Distributed</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $statusCounts['unaccounted_for'] ?? 0 }}</h3>
                            <p>Unaccounted For</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Document Status Management</h3>
                        </div>
                        <div class="card-body">
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('admin.document-status.invoices') ? 'active' : '' }}"
                                            href="{{ route('admin.document-status.invoices') }}">
                                            <i class="fas fa-file-invoice"></i> Invoice Status Management
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('admin.document-status.additional-documents') ? 'active' : '' }}"
                                            href="{{ route('admin.document-status.additional-documents') }}">
                                            <i class="fas fa-file-alt"></i> Additional Document Status Management
                                        </a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-box">
                                                    <span class="info-box-icon bg-primary">
                                                        <i class="fas fa-file-invoice"></i>
                                                    </span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Invoice Documents</span>
                                                        <span
                                                            class="info-box-number">{{ $statusCounts['available'] + $statusCounts['in_transit'] + $statusCounts['distributed'] + $statusCounts['unaccounted_for'] }}</span>
                                                        <div class="progress">
                                                            <div class="progress-bar" style="width: 100%"></div>
                                                        </div>
                                                        <span class="progress-description">
                                                            <a href="{{ route('admin.document-status.invoices') }}"
                                                                class="btn btn-sm btn-primary">
                                                                Manage Invoice Status
                                                            </a>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-box">
                                                    <span class="info-box-icon bg-success">
                                                        <i class="fas fa-file-alt"></i>
                                                    </span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Additional Documents</span>
                                                        <span
                                                            class="info-box-number">{{ $statusCounts['available'] + $statusCounts['in_transit'] + $statusCounts['distributed'] + $statusCounts['unaccounted_for'] }}</span>
                                                        <div class="progress">
                                                            <div class="progress-bar" style="width: 100%"></div>
                                                        </div>
                                                        <span class="progress-description">
                                                            <a href="{{ route('admin.document-status.additional-documents') }}"
                                                                class="btn btn-sm btn-success">
                                                                Manage Additional Document Status
                                                            </a>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
        .nav-tabs-custom {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        .nav-tabs-custom .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .nav-tabs-custom .nav-tabs .nav-link {
            border: none;
            border-radius: 0;
            color: #495057;
            padding: 0.75rem 1rem;
        }

        .nav-tabs-custom .nav-tabs .nav-link:hover {
            background-color: #e9ecef;
            border-color: transparent;
        }

        .nav-tabs-custom .nav-tabs .nav-link.active {
            background-color: #fff;
            border-bottom: 2px solid #007bff;
            color: #007bff;
        }

        .info-box {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
        }

        .info-box-icon {
            border-radius: 0.25rem 0 0 0.25rem;
            display: block;
            float: left;
            height: 90px;
            width: 90px;
            text-align: center;
            font-size: 45px;
            line-height: 90px;
            background: #007bff;
            color: #fff;
        }

        .info-box-content {
            padding: 5px 10px;
            margin-left: 90px;
        }

        .info-box-text {
            display: block;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .info-box-number {
            display: block;
            font-weight: bold;
            font-size: 18px;
        }

        .progress {
            background-color: #e9ecef;
            border-radius: 0.25rem;
            height: 0.5rem;
            margin: 5px 0;
        }

        .progress-bar {
            background-color: #007bff;
            border-radius: 0.25rem;
        }

        .progress-description {
            display: block;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
@endsection
