@extends('layouts.main')

@section('title_page')
    Invoice Report Detail
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('document-report.invoices') }}">All Invoice Report</a></li>
    <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice"></i> Invoice Information</h3>
                    <div class="card-tools">
                        <a href="{{ route('document-report.invoices') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Report
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr><td width="40%"><strong>Invoice Number:</strong></td><td>{{ $invoice->invoice_number }}</td></tr>
                                <tr><td><strong>Faktur No:</strong></td><td>{{ $invoice->faktur_no ?: '-' }}</td></tr>
                                <tr><td><strong>Invoice Date:</strong></td><td>{{ $invoice->formatted_invoice_date }}</td></tr>
                                <tr><td><strong>Receive Date:</strong></td><td>{{ $invoice->formatted_receive_date }}</td></tr>
                                <tr><td><strong>Supplier:</strong></td><td>{{ $invoice->supplier?->name ?? '-' }}@if($invoice->supplier?->sap_code) <span class="text-muted">({{ $invoice->supplier->sap_code }})</span>@endif</td></tr>
                                <tr><td><strong>PO Number:</strong></td><td>{{ $invoice->po_no ?: '-' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr><td width="40%"><strong>Invoice Type:</strong></td><td>{{ $invoice->type?->type_name ?? '-' }}</td></tr>
                                @if ($invoice->isConsignment() || filled($invoice->gl_account))
                                    <tr><td><strong>G/L Account:</strong></td><td>{{ $invoice->gl_account ?: '-' }}</td></tr>
                                @endif
                                <tr><td><strong>Currency:</strong></td><td>{{ $invoice->currency }}</td></tr>
                                <tr><td><strong>Amount:</strong></td><td class="text-right">{{ $invoice->formatted_amount }}</td></tr>
                                <tr><td><strong>Status:</strong></td><td>{!! $invoice->status_badge !!}</td></tr>
                                <tr><td><strong>SAP Post Status:</strong></td><td>{!! $invoice->sap_status_badge !!}</td></tr>
                                <tr><td><strong>Current Location:</strong></td><td>{{ $invoice->cur_loc ?? '-' }}</td></tr>
                                <tr><td><strong>Distribution Status:</strong></td><td>{{ ucfirst(str_replace('_', ' ', $invoice->distribution_status ?? 'available')) }}</td></tr>
                                <tr><td><strong>Payment Date:</strong></td><td>{{ $invoice->formatted_payment_date }}</td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <strong>Receive Project:</strong><br>
                            @if ($invoice->receiveProjectInfo)
                                <span class="badge badge-info">{{ $invoice->receive_project }}</span><br>
                                <small>{{ $invoice->receiveProjectInfo->name }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <strong>Invoice Project:</strong><br>
                            @if ($invoice->invoiceProjectInfo)
                                <span class="badge badge-info">{{ $invoice->invoice_project }}</span><br>
                                <small>{{ $invoice->invoiceProjectInfo->name }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <strong>Payment Project:</strong><br>
                            @if ($invoice->paymentProjectInfo)
                                <span class="badge badge-info">{{ $invoice->payment_project }}</span><br>
                                <small>{{ $invoice->paymentProjectInfo->name }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>

                    @if ($invoice->remarks)
                        <div class="row mt-3">
                            <div class="col-12">
                                <strong>Remarks:</strong>
                                <p class="text-muted mb-0">{{ $invoice->remarks }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($invoice->lineDetails->isNotEmpty())
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6><strong>Line Details</strong></h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Description</th>
                                                <th class="text-right">Qty</th>
                                                <th class="text-right">Unit Price</th>
                                                <th class="text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($invoice->lineDetails as $line)
                                                <tr>
                                                    <td>{{ $line->description }}</td>
                                                    <td class="text-right">{{ $line->quantity !== null ? number_format((float) $line->quantity, 4) : '—' }}</td>
                                                    <td class="text-right">{{ $line->unit_price !== null ? number_format((float) $line->unit_price, 2) : '—' }}</td>
                                                    <td class="text-right">{{ $line->amount !== null ? number_format((float) $line->amount, 2) : '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>SAP Document:</strong> {{ $invoice->display_sap_document ?: '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Created By:</strong> {{ $invoice->creator?->name ?? '-' }}
                            <br><strong>Created At:</strong> {{ $invoice->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>

            @if ($invoice->attachments->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-paperclip"></i> Attachments ({{ $invoice->attachments->count() }})</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach ($invoice->attachments as $attachment)
                                <li class="mb-1">
                                    <i class="fas fa-file"></i> {{ $attachment->original_filename ?? basename($attachment->file_path) }}
                                    <small class="text-muted">— uploaded by {{ $attachment->uploader?->name ?? 'N/A' }}</small>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-link"></i> Linked Additional Documents ({{ $invoice->additionalDocuments->count() }})</h3>
                </div>
                <div class="card-body">
                    @if ($invoice->additionalDocuments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Document No</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>PO No</th>
                                        <th>Location</th>
                                        <th>Dist. Status</th>
                                        <th>Signature</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->additionalDocuments as $doc)
                                        <tr>
                                            <td>
                                                <a href="{{ route('document-report.additional-document-detail', $doc) }}">{{ $doc->document_number }}</a>
                                            </td>
                                            <td>{{ $doc->type?->type_name }}</td>
                                            <td>{{ $doc->document_date?->format('d/m/Y') }}</td>
                                            <td>{{ $doc->po_no }}</td>
                                            <td><span class="badge badge-secondary">{{ $doc->cur_loc }}</span></td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $doc->distribution_status ?? 'available')) }}</td>
                                            <td>
                                                @if ($doc->requiresSignature())
                                                    {{ $doc->signature_status ?? 'not started' }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No additional documents linked.</p>
                    @endif
                </div>
            </div>

            @include('reports.document-report.partials.journey', ['journey' => $journey])
        </div>
    </section>
@endsection
