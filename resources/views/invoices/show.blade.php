@extends('layouts.main')

@section('title_page')
    Invoice Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('content')
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <!-- Invoice Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-invoice"></i> Invoice Information
                            </h3>
                            <div class="card-tools">
                                @can('view-document-distribution-history')
                                    @if ($invoice->distributions->count() > 0)
                                        <a href="{{ route('distributions.document.distribution-history', ['document_type' => 'invoice', 'document_id' => $invoice->id]) }}"
                                            class="btn btn-info btn-sm">
                                            <i class="fas fa-route"></i> Distribution History
                                        </a>
                                    @endif
                                @endcan
                                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Invoice Number:</strong></td>
                                            <td>{{ $invoice->invoice_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Faktur No:</strong></td>
                                            <td>{{ $invoice->faktur_no ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Invoice Date:</strong></td>
                                            <td>{{ $invoice->formatted_invoice_date }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Receive Date:</strong></td>
                                            <td>{{ $invoice->formatted_receive_date }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Supplier:</strong></td>
                                            <td>{{ $invoice->supplier ? $invoice->supplier->name : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>PO Number:</strong></td>
                                            <td>{{ $invoice->po_no ?: '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Invoice Type:</strong></td>
                                            <td>{{ $invoice->type ? $invoice->type->type_name : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Currency:</strong></td>
                                            <td>{{ $invoice->currency }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Amount:</strong></td>
                                            <td class="text-right">{{ $invoice->formatted_amount }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>{!! $invoice->status_badge !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>SAP Status:</strong></td>
                                            <td>{!! $invoice->sap_status_badge !!}</td>
                                        </tr>
                                        @if ($invoice->sap_status === 'failed' && (auth()->user()->can('edit-invoices') || auth()->user()->can('update-invoice') || auth()->user()->hasRole('superadmin')))
                                            <tr>
                                                <td colspan="2">
                                                    <form action="{{ route('invoices.sap-sync', $invoice) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-warning btn-sm">Retry SAP Sync</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td><strong>Current Location:</strong></td>
                                            <td>{{ $invoice->cur_loc }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Payment Date:</strong></td>
                                            <td>{{ $invoice->formatted_payment_date }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6><strong>Project Information:</strong></h6>
                                    <div class="row">
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
                                </div>
                            </div>

                            @if ($invoice->remarks)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6><strong>Remarks:</strong></h6>
                                        <p class="text-muted">{{ $invoice->remarks }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6><strong>Additional Information:</strong></h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>SAP Document:</strong> {{ $invoice->sap_doc ?: '-' }}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Created By:</strong>
                                            {{ $invoice->creator ? $invoice->creator->name : '-' }}
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <strong>Created At:</strong>
                                            {{ $invoice->created_at->format('d/m/Y H:i') }}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Updated At:</strong>
                                            {{ $invoice->updated_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Document Journey Tracking -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-route"></i> Document Journey Tracking
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="documentJourneyContent">
                                <div class="text-center">
                                    <button type="button" class="btn btn-primary" id="loadJourneyBtn">
                                        <i class="fas fa-route"></i> Load Document Journey
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Attachments Link -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-paperclip"></i> Attachments
                                <span class="badge badge-info">{{ $invoice->attachments->count() }}</span>
                            </h3>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-file-upload fa-3x text-muted mb-3"></i>
                                <h5>Manage Attachments</h5>
                                <p class="text-muted">Upload, view, and manage files for this invoice</p>
                            </div>
                            <a href="{{ route('invoices.attachments.show', $invoice) }}" class="btn btn-primary btn-block">
                                <i class="fas fa-paperclip"></i> Go to Attachments Page
                            </a>
                            @if ($invoice->attachments->count() > 0)
                                <small class="text-muted d-block mt-2">
                                    {{ $invoice->attachments->count() }} file(s) attached
                                </small>
                            @endif
                        </div>
                    </div>

                    <!-- Linked Additional Documents -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-link"></i> Linked Additional Documents
                                <span class="badge badge-info">{{ $invoice->additionalDocuments->count() }}</span>
                            </h3>
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
                                                <th>Cur Loc</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($invoice->additionalDocuments as $doc)
                                                <tr>
                                                    <td>{{ $doc->document_number }}</td>
                                                    <td>{{ optional($doc->type)->type_name }}</td>
                                                    <td>{{ optional($doc->document_date)->format('Y-m-d') }}</td>
                                                    <td>{{ $doc->po_no }}</td>
                                                    <td><span class="badge badge-secondary">{{ $doc->cur_loc }}</span>
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
                </div>
            </div>
        </div>
    </div>
    </div>

    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Document Journey Tracking
            $('#loadJourneyBtn').on('click', function() {
                loadDocumentJourney();
            });

            function loadDocumentJourney() {
                const button = $('#loadJourneyBtn');
                const originalText = button.html();

                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

                fetch(
                        `{{ url('api/v1/processing-analytics/document-timeline') }}?document_id={{ $invoice->id }}&document_type=invoice`
                    )
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayDocumentJourney(data.data);
                        } else {
                            showJourneyError(data.error || 'Failed to load document journey');
                        }
                    })
                    .catch(error => {
                        showJourneyError('Network error: ' + error.message);
                    })
                    .finally(() => {
                        button.prop('disabled', false).html(originalText);
                    });
            }

            function displayDocumentJourney(data) {
                const content = $('#documentJourneyContent');

                let html = `
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Document Number:</strong>
                            <p class="text-primary mb-0">${data.document.number}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Document Type:</strong>
                            <p class="text-info mb-0">${data.document.type.charAt(0).toUpperCase() + data.document.type.slice(1)}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Current Location Arrival:</strong>
                            <p class="text-success mb-0">${new Date(data.document.current_location_arrival_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}).replace(/ /g, '-')}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Days in Current Location:</strong>
                            <p class="text-warning mb-0 text-right">${Math.round(data.document.days_in_current_location * 10) / 10} days</p>
                        </div>
                    </div>
                `;

                // Add distribution summary if available
                if (data.distributions && data.distributions.length > 0) {
                    html += `
                        <div class="alert alert-info mb-3">
                            <h6><i class="fas fa-route"></i> Distribution Summary</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Total Distributions:</strong> ${data.distributions.length}<br>
                                    <strong>Current Status:</strong> <span class="badge badge-${data.document.distribution_status === 'distributed' ? 'success' : 'warning'}">${data.document.distribution_status}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Current Location:</strong> ${data.document.current_location}<br>
                                    <strong>Total Processing Days:</strong> ${Math.round(data.total_processing_days * 10) / 10} days
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Add journey summary if available
                if (data.journey_summary) {
                    const summary = data.journey_summary;
                    html += `
                        <div class="alert ${summary.is_delayed ? 'alert-danger' : 'alert-info'}">
                            <h6><i class="fas fa-${summary.is_delayed ? 'exclamation-triangle' : 'info-circle'}"></i> Journey Summary</h6>
                            <p><strong>Status:</strong> ${summary.status} | <strong>Current Location:</strong> ${summary.current_location} | <strong>Total Days:</strong> ${Math.round(summary.total_days * 10) / 10} | <strong>Departments Visited:</strong> ${summary.departments_visited}</p>
                            ${summary.recommendations.length > 0 ? `<p><strong>Recommendations:</strong> ${summary.recommendations.join(' ')}</p>` : ''}
                        </div>
                    `;
                }

                if (data.timeline.length === 0) {
                    html +=
                        '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No processing timeline available for this document.</div>';
                } else {
                    html += `
                        <div class="timeline-container">
                            <h6><strong>Complete Document Journey Timeline (Total: ${Math.round(data.total_processing_days * 10) / 10} days)</strong></h6>
                            <div class="timeline">
                    `;

                    data.timeline.forEach((step, index) => {
                        const statusClass = getStatusClass(step.status);
                        const statusText = getStatusText(step.status);
                        const isDelayed = step.processing_days > 14;
                        const itemClass = step.is_current ? 'current' : (isDelayed ? 'delayed' : (step
                            .is_origin ? 'origin' : 'destination'));
                        const isOrigin = step.is_origin;

                        html += `
                            <div class="timeline-item ${itemClass}">
                                <div class="timeline-marker">
                                    <i class="fas fa-${step.is_current ? 'play' : (isOrigin ? 'paper-plane' : 'check')}"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h6 class="timeline-title">
                                            Step ${step.step}: ${step.department}
                                            ${isOrigin ? '<span class="badge badge-secondary ml-2">Origin</span>' : ''}
                                        </h6>
                                        <span class="timeline-badge ${statusClass}">${statusText}</span>
                                        ${isDelayed ? '<span class="badge badge-danger ml-1">DELAYED</span>' : ''}
                                    </div>
                                    <div class="timeline-details">
                                        <p><strong>Arrival Date:</strong> ${new Date(step.arrival_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}).replace(/ /g, '-')}</p>
                                        ${step.departure_date ? `<p><strong>Departure Date:</strong> ${new Date(step.departure_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}).replace(/ /g, '-')}</p>` : ''}
                                        <p><strong>Processing Days:</strong> <span class="timeline-duration ${isDelayed ? 'text-danger' : ''} text-right">${Math.round(step.processing_days * 10) / 10} days</span></p>
                                        ${step.next_department ? `<p><strong>Next Department:</strong> ${step.next_department}</p>` : ''}
                                        ${step.distribution_number ? `<p><strong>Distribution:</strong> <span class="badge badge-info">${step.distribution_number}</span></p>` : ''}
                                        ${step.distribution_type ? `<p><strong>Distribution Type:</strong> ${step.distribution_type} (${step.distribution_type_code})</p>` : ''}
                                        ${step.location_code ? `<p><strong>Location Code:</strong> ${step.location_code}</p>` : ''}
                                        
                                        ${step.verification_details ? `
                                                <div class="verification-details mt-2">
                                                    <h6><i class="fas fa-shield-alt"></i> Verification Details</h6>
                                                    ${isOrigin ? `
                                                    <p><strong>Sender Verified:</strong> ${step.verification_details.sender_verified_at ? new Date(step.verification_details.sender_verified_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}).replace(/ /g, '-') : 'Not verified'}</p>
                                                    <p><strong>Verified By:</strong> ${step.verification_details.sender_verified_by || 'N/A'}</p>
                                                    ${step.verification_details.sender_verification_notes ? `<p><strong>Notes:</strong> ${step.verification_details.sender_verification_notes}</p>` : ''}
                                                ` : `
                                                    <p><strong>Receiver Verified:</strong> ${step.verification_details.receiver_verified_at ? new Date(step.verification_details.receiver_verified_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}).replace(/ /g, '-') : 'Not verified'}</p>
                                                    <p><strong>Verified By:</strong> ${step.verification_details.receiver_verified_by || 'N/A'}</p>
                                                    ${step.verification_details.receiver_verification_notes ? `<p><strong>Notes:</strong> ${step.verification_details.receiver_verification_notes}</p>` : ''}
                                                `}
                                                </div>
                                            ` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += `
                            </div>
                        </div>
                    `;
                }

                // Add enhanced processing statistics
                const metrics = data.metrics || {};
                const timeline = data.timeline;
                const totalDays = data.total_processing_days;

                html += `
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Processing Days</span>
                                    <span class="info-box-number text-right">${Math.round(totalDays * 10) / 10}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-building"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Departments Visited</span>
                                    <span class="info-box-number">${metrics.total_departments || timeline.length}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Average per Department</span>
                                    <span class="info-box-number text-right">${Math.round((metrics.average_stay || 0) * 10) / 10}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Longest Stay</span>
                                    <span class="info-box-number text-right">${Math.round((metrics.longest_stay || 0) * 10) / 10}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Add delayed departments alert if any
                if (metrics.delayed_departments && metrics.delayed_departments.length > 0) {
                    html += `
                        <div class="alert alert-warning mt-3">
                            <h6><i class="fas fa-exclamation-triangle"></i> Delayed Departments</h6>
                            <ul class="mb-0">
                           ${metrics.delayed_departments.map(dept => 
                               `<li><strong>${dept.department}</strong>: ${Math.round(dept.days * 10) / 10} days ${dept.status === 'current' ? '(Current)' : '(Completed)'}</li>`
                           ).join('')}
                            </ul>
                        </div>
                    `;
                }

                content.html(html);
            }

            function showJourneyError(message) {
                $('#documentJourneyContent').html(`
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> Error!</h6>
                        <p>${message}</p>
                    </div>
                `);
            }

            function getStatusClass(status) {
                switch (status) {
                    case 'completed':
                        return 'badge-success';
                    case 'current':
                        return 'badge-primary';
                    case 'in_progress':
                        return 'badge-warning';
                    case 'pending':
                        return 'badge-secondary';
                    default:
                        return 'badge-secondary';
                }
            }

            function getStatusText(status) {
                switch (status) {
                    case 'completed':
                        return 'Completed';
                    case 'current':
                        return 'Current';
                    case 'in_progress':
                        return 'In Progress';
                    case 'pending':
                        return 'Pending';
                    default:
                        return 'Unknown';
                }
            }
        });
    </script>

    <style>
        .timeline-container {
            position: relative;
            padding: 20px 0;
        }

        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 30px;
            height: 100%;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-left: 60px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 22px;
            top: 10px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #007bff;
            border: 3px solid #fff;
            box-shadow: 0 0 0 3px #007bff;
        }

        .timeline-item.completed::before {
            background: #28a745;
            box-shadow: 0 0 0 3px #28a745;
        }

        .timeline-item.in-progress::before {
            background: #ffc107;
            box-shadow: 0 0 0 3px #ffc107;
        }

        .timeline-item.pending::before {
            background: #6c757d;
            box-shadow: 0 0 0 3px #6c757d;
        }

        .timeline-content {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .timeline-title {
            font-weight: bold;
            color: #495057;
            margin: 0;
        }

        .timeline-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .timeline-details {
            color: #6c757d;
            font-size: 14px;
        }

        .timeline-duration {
            font-weight: bold;
            color: #007bff;
        }

        .verification-details {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            margin-top: 10px;
        }

        .verification-details h6 {
            color: #495057;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .verification-details p {
            margin-bottom: 5px;
            font-size: 13px;
        }

        .timeline-item.origin {
            border-left: 3px solid #17a2b8;
        }

        .timeline-item.destination {
            border-left: 3px solid #28a745;
        }

        .timeline-item.current {
            border-left: 3px solid #007bff;
        }

        .timeline-item.delayed {
            border-left: 3px solid #dc3545;
        }

        .timeline-item.completed {
            border-left: 3px solid #6c757d;
        }
    </style>
@endsection
