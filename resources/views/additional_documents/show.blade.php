@extends('layouts.main')

@section('title_page')
    Additional Document Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('additional-documents.index') }}">Additional
            Documents</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Document Information</h3>
                            <div class="card-tools">
                                @can('view-document-distribution-history')
                                    @if ($additionalDocument->distributions->count() > 0)
                                        <a href="{{ route('distributions.document.distribution-history', ['document_type' => 'additional-document', 'document_id' => $additionalDocument->id]) }}"
                                            class="btn btn-info btn-sm">
                                            <i class="fas fa-route"></i> Distribution History
                                        </a>
                                    @endif
                                @endcan

                                <a href="{{ route('additional-documents.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                                @if ($additionalDocument->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('additional-documents.edit', $additionalDocument) }}"
                                        class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit Document
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%"><strong>Document Number:</strong></td>
                                            <td>{{ $additionalDocument->document_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Document Type:</strong></td>
                                            <td>{{ $additionalDocument->type->type_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Document Date:</strong></td>
                                            <td>{{ $additionalDocument->document_date ? $additionalDocument->document_date->format('d/m/Y') : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Receive Date:</strong></td>
                                            <td>{{ $additionalDocument->receive_date ? $additionalDocument->receive_date->format('d/m/Y') : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>PO Number:</strong></td>
                                            <td>{{ $additionalDocument->po_no ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Project:</strong></td>
                                            <td>{{ $additionalDocument->project ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%"><strong>Status:</strong></td>
                                            <td>
                                                <span
                                                    class="badge badge-{{ $additionalDocument->status === 'open' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($additionalDocument->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Distribution Status:</strong></td>
                                            <td>
                                                @switch($additionalDocument->distribution_status)
                                                    @case('available')
                                                        <span class="badge badge-success">Available</span>
                                                    @break

                                                    @case('in_transit')
                                                        <span class="badge badge-warning">In Transit</span>
                                                    @break

                                                    @case('distributed')
                                                        <span class="badge badge-info">Distributed</span>
                                                    @break

                                                    @default
                                                        <span
                                                            class="badge badge-secondary">{{ ucfirst($additionalDocument->distribution_status ?? 'N/A') }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Current Location:</strong></td>
                                            <td>{{ $additionalDocument->cur_loc ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created By:</strong></td>
                                            <td>{{ $additionalDocument->creator->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created Date:</strong></td>
                                            <td>{{ $additionalDocument->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Updated:</strong></td>
                                            <td>{{ $additionalDocument->updated_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Department:</strong></td>
                                            <td>{{ $additionalDocument->creator->department->name ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if ($additionalDocument->remarks)
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label><strong>Remarks:</strong></label>
                                            <div class="alert alert-light border">
                                                {{ $additionalDocument->remarks }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($additionalDocument->attachment)
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label><strong>Attachment:</strong></label>
                                            <div class="alert alert-info">
                                                <a href="{{ route('additional-documents.download', $additionalDocument) }}"
                                                    class="btn btn-info" target="_blank">
                                                    <i class="fas fa-download"></i> Download Attachment
                                                </a>
                                                <small class="ml-2">File:
                                                    {{ basename($additionalDocument->attachment) }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Additional Fields -->
                            @if (
                                $additionalDocument->flag ||
                                    $additionalDocument->ito_creator ||
                                    $additionalDocument->grpo_no ||
                                    $additionalDocument->origin_wh ||
                                    $additionalDocument->destination_wh ||
                                    $additionalDocument->batch_no)
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5><i class="fas fa-info-circle"></i> Additional Information</h5>
                                        <div class="row">
                                            @if ($additionalDocument->flag)
                                                <div class="col-md-3">
                                                    <strong>Flag:</strong> {{ $additionalDocument->flag }}
                                                </div>
                                            @endif
                                            @if ($additionalDocument->ito_creator)
                                                <div class="col-md-3">
                                                    <strong>ITO Creator:</strong> {{ $additionalDocument->ito_creator }}
                                                </div>
                                            @endif
                                            @if ($additionalDocument->grpo_no)
                                                <div class="col-md-3">
                                                    <strong>GRPO No:</strong> {{ $additionalDocument->grpo_no }}
                                                </div>
                                            @endif
                                            @if ($additionalDocument->origin_wh)
                                                <div class="col-md-3">
                                                    <strong>Origin WH:</strong> {{ $additionalDocument->origin_wh }}
                                                </div>
                                            @endif
                                            @if ($additionalDocument->destination_wh)
                                                <div class="col-md-3">
                                                    <strong>Destination WH:</strong>
                                                    {{ $additionalDocument->destination_wh }}
                                                </div>
                                            @endif
                                            @if ($additionalDocument->batch_no)
                                                <div class="col-md-3">
                                                    <strong>Batch No:</strong> {{ $additionalDocument->batch_no }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('additional-documents.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            @if ($additionalDocument->canBeEditedBy(auth()->user()))
                                <a href="{{ route('additional-documents.edit', $additionalDocument) }}"
                                    class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Edit Document
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
