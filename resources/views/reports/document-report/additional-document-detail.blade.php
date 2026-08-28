@extends('layouts.main')

@section('title_page')
    Additional Document Report Detail
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('document-report.additional-documents') }}">All Additional Documents Report</a></li>
    <li class="breadcrumb-item active">{{ $additionalDocument->document_number }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt"></i> Document Information</h3>
                    <div class="card-tools">
                        <a href="{{ route('document-report.additional-documents') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Report
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr><td width="40%"><strong>Document Number:</strong></td><td>{{ $additionalDocument->document_number }}</td></tr>
                                <tr><td><strong>Document Type:</strong></td><td>{{ $additionalDocument->type?->type_name ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Document Date:</strong></td><td>{{ $additionalDocument->document_date?->format('d/m/Y') ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Receive Date:</strong></td><td>{{ $additionalDocument->receive_date?->format('d/m/Y') ?? 'N/A' }}</td></tr>
                                <tr><td><strong>PO Number:</strong></td><td>{{ $additionalDocument->po_no ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Project:</strong></td><td>{{ $additionalDocument->project ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Vendor Code:</strong></td><td>{{ $additionalDocument->vendor_code ?? 'N/A' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr><td width="40%"><strong>Status:</strong></td><td>{{ ucfirst($additionalDocument->status) }}</td></tr>
                                <tr><td><strong>Distribution Status:</strong></td><td>{{ ucfirst(str_replace('_', ' ', $additionalDocument->distribution_status ?? 'available')) }}</td></tr>
                                <tr><td><strong>Current Location:</strong></td><td>{{ $additionalDocument->cur_loc ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Created By:</strong></td><td>{{ $additionalDocument->creator?->name ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Department:</strong></td><td>{{ $additionalDocument->creator?->department?->name ?? 'N/A' }}</td></tr>
                                <tr><td><strong>Created Date:</strong></td><td>{{ $additionalDocument->created_at->format('d/m/Y H:i') }}</td></tr>
                                <tr><td><strong>Last Updated:</strong></td><td>{{ $additionalDocument->updated_at->format('d/m/Y H:i') }}</td></tr>
                            </table>
                        </div>
                    </div>

                    @if ($additionalDocument->remarks)
                        <div class="row mt-3">
                            <div class="col-12">
                                <strong>Remarks:</strong>
                                <p class="text-muted mb-0">{{ $additionalDocument->remarks }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($additionalDocument->attachment)
                        <div class="row mt-3">
                            <div class="col-12">
                                <strong>Attachment:</strong>
                                <span class="text-muted">{{ basename($additionalDocument->attachment) }}</span>
                            </div>
                        </div>
                    @endif

                    @if ($additionalDocument->flag || $additionalDocument->ito_creator || $additionalDocument->grpo_no || $additionalDocument->origin_wh || $additionalDocument->destination_wh || $additionalDocument->batch_no)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6><strong>SAP / ITO Information</strong></h6>
                                <div class="row">
                                    @if ($additionalDocument->flag)
                                        <div class="col-md-3"><strong>Flag:</strong> {{ $additionalDocument->flag }}</div>
                                    @endif
                                    @if ($additionalDocument->ito_creator)
                                        <div class="col-md-3"><strong>ITO Creator:</strong> {{ $additionalDocument->ito_creator }}</div>
                                    @endif
                                    @if ($additionalDocument->grpo_no)
                                        <div class="col-md-3"><strong>GRPO No:</strong> {{ $additionalDocument->grpo_no }}</div>
                                    @endif
                                    @if ($additionalDocument->origin_wh)
                                        <div class="col-md-3"><strong>Origin WH:</strong> {{ $additionalDocument->origin_wh }}</div>
                                    @endif
                                    @if ($additionalDocument->destination_wh)
                                        <div class="col-md-3"><strong>Destination WH:</strong> {{ $additionalDocument->destination_wh }}</div>
                                    @endif
                                    @if ($additionalDocument->batch_no)
                                        <div class="col-md-3"><strong>Batch No:</strong> {{ $additionalDocument->batch_no }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($additionalDocument->requiresSignature())
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6><strong>Signature Verification</strong></h6>
                                <p class="mb-0">
                                    Status: <span class="badge badge-secondary">{{ $additionalDocument->signature_status ?? 'not started' }}</span>
                                    @if ($additionalDocument->hasSignatureOverride())
                                        <span class="badge badge-info">override</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-link"></i> Linked Invoices ({{ $additionalDocument->invoices->count() }})</h3>
                </div>
                <div class="card-body">
                    @if ($additionalDocument->invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Supplier</th>
                                        <th>Type</th>
                                        <th>Invoice Date</th>
                                        <th>PO No</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($additionalDocument->invoices as $invoice)
                                        <tr>
                                            <td>
                                                <a href="{{ route('document-report.invoice-detail', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                            </td>
                                            <td>{{ $invoice->supplier?->name ?? '-' }}</td>
                                            <td>{{ $invoice->type?->type_name ?? '-' }}</td>
                                            <td>{{ $invoice->formatted_invoice_date }}</td>
                                            <td>{{ $invoice->po_no }}</td>
                                            <td>{{ $invoice->formatted_amount }}</td>
                                            <td>{!! $invoice->status_badge !!}</td>
                                            <td><span class="badge badge-secondary">{{ $invoice->cur_loc }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No invoices linked.</p>
                    @endif
                </div>
            </div>

            @include('reports.document-report.partials.journey', ['journey' => $journey])
        </div>
    </section>
@endsection
