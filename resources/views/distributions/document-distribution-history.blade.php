@extends('layouts.main')

@section('title_page')
    Document Distribution History
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('distributions.index') }}">Distributions</a></li>
    <li class="breadcrumb-item active">Document History</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Document Information Card -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-file-alt"></i> Document Information
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Document Type:</strong> {{ ucfirst($documentType) }}<br>
                                    <strong>Document ID:</strong> {{ $document->id }}<br>
                                    <strong>Current Location:</strong> {{ $document->cur_loc ?? 'N/A' }}<br>
                                    <strong>Distribution Status:</strong>
                                    <span
                                        class="badge badge-{{ $document->distribution_status === 'available' ? 'success' : ($document->distribution_status === 'in_transit' ? 'warning' : 'info') }}">
                                        {{ ucfirst($document->distribution_status ?? 'available') }}
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Total Distributions:</strong> {{ $stats['total_distributions'] }}<br>
                                    <strong>Departments Visited:</strong> {{ $stats['total_departments_visited'] }}<br>
                                    @if ($documentType === 'invoice')
                                        <strong>PO Number:</strong> {{ $document->po_no ?? 'N/A' }}<br>
                                        <strong>Supplier:</strong> {{ $document->supplier->name ?? 'N/A' }}
                                    @else
                                        <strong>Document Type:</strong> {{ $document->type->name ?? 'N/A' }}<br>
                                        <strong>Reference:</strong> {{ $document->reference ?? 'N/A' }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Journey Statistics Cards -->
            @if ($distributions->count() > 0)
                <div class="row mb-3">
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $stats['journey_duration'] ?? 0 }}</h3>
                                <p>Total Journey Days</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ $stats['total_distance'] ?? 0 }}</h3>
                                <p>Total Transfers</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $stats['avg_time_per_department'] ?? 0 }}</h3>
                                <p>Avg Days per Dept</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>{{ $stats['total_departments_visited'] }}</h3>
                                <p>Departments Visited</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-secondary">
                            <div class="inner">
                                <h3>{{ $stats['total_distributions'] }}</h3>
                                <p>Total Distributions</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-dark">
                            <div class="inner">
                                <h3>{{ $stats['journey_start'] ? $stats['journey_start']->format('d-M-Y') : 'N/A' }}</h3>
                                <p>Journey Started</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Department Time Statistics -->
            @if (!empty($departmentTimeStats))
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">
                                    <i class="fas fa-chart-pie"></i> Time Spent in Each Department
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th>Visits</th>
                                                <th>Total Time (Days)</th>
                                                <th>Average Time (Days)</th>
                                                <th>First Visit</th>
                                                <th>Last Visit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($departmentTimeStats as $deptId => $dept)
                                                <tr>
                                                    <td><strong>{{ $dept['name'] }}</strong></td>
                                                    <td>
                                                        <span class="badge badge-info">{{ $dept['visits'] }}</span>
                                                    </td>
                                                    <td>{{ $dept['total_time'] }}</td>
                                                    <td>{{ $dept['avg_time'] }}</td>
                                                    <td>{{ $dept['first_visit'] ? $dept['first_visit']->format('d-M-Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $dept['last_visit'] ? $dept['last_visit']->format('d-M-Y') : 'N/A' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Distribution Journey Timeline -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-route"></i> Distribution Journey Timeline
                            </h4>
                        </div>
                        <div class="card-body">
                            @if ($distributions->count() > 0)
                                <div class="timeline">
                                    @foreach ($distributions as $distribution)
                                        <div class="timeline-item">
                                            <div class="timeline-marker">
                                                <i class="fas fa-circle"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-header">
                                                    <strong>Distribution #{{ $distribution->distribution_number }}</strong>
                                                    <small
                                                        class="text-muted float-right">{{ $distribution->created_at->format('d-M-Y H:i') }}</small>
                                                </div>
                                                <div class="timeline-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p class="mb-1">
                                                                <strong>From:</strong>
                                                                {{ $distribution->originDepartment->name }}
                                                            </p>
                                                            <p class="mb-1">
                                                                <strong>To:</strong>
                                                                {{ $distribution->destinationDepartment->name }}
                                                            </p>
                                                            <p class="mb-1">
                                                                <strong>Status:</strong>
                                                                <span
                                                                    class="badge badge-{{ $distribution->status === 'completed' ? 'success' : 'primary' }}">
                                                                    {{ ucfirst(str_replace('_', ' ', $distribution->status)) }}
                                                                </span>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p class="mb-1">
                                                                <strong>Created by:</strong>
                                                                {{ $distribution->creator->name }}
                                                            </p>
                                                            @if ($distribution->sent_at)
                                                                <p class="mb-1">
                                                                    <strong>Sent:</strong>
                                                                    {{ $distribution->sent_at->format('d-M-Y H:i') }}
                                                                </p>
                                                            @endif
                                                            @if ($distribution->received_at)
                                                                <p class="mb-1">
                                                                    <strong>Received:</strong>
                                                                    {{ $distribution->received_at->format('d-M-Y H:i') }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    @if ($distribution->histories->count() > 0)
                                                        <div class="mt-2 p-2 bg-light rounded">
                                                            <strong>Recent Actions:</strong>
                                                            @foreach ($distribution->histories->take(3) as $history)
                                                                <div class="small text-muted">
                                                                    • {{ $history->action_display }} by
                                                                    {{ $history->user->name }}
                                                                    <small>({{ $history->time_ago }})</small>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <div class="mt-3">
                                                        <a href="{{ route('distributions.show', $distribution) }}"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> View Distribution
                                                        </a>
                                                        @if ($distribution->status === 'completed')
                                                            <a href="{{ route('distributions.print', $distribution) }}"
                                                                class="btn btn-sm btn-info">
                                                                <i class="fas fa-print"></i> Print
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Distribution History</h5>
                                    <p class="text-muted">This document has not been distributed yet.</p>
                                    <a href="{{ route('distributions.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create First Distribution
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
