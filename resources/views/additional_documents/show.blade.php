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
            </div>
        </div>
    </section>
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
                        `{{ url('api/v1/processing-analytics/document-timeline') }}?document_id={{ $additionalDocument->id }}&document_type=additional_document`
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
                            <strong>Receive Date:</strong>
                            <p class="text-success mb-0">${new Date(data.document.receive_date).toLocaleDateString()}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Current Status:</strong>
                            <p class="text-warning mb-0">${data.current_status.charAt(0).toUpperCase() + data.current_status.slice(1)}</p>
                        </div>
                    </div>
                `;

                if (data.timeline.length === 0) {
                    html +=
                        '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No processing timeline available for this document.</div>';
                } else {
                    html += `
                        <div class="timeline-container">
                            <h6><strong>Processing Timeline (Total: ${data.total_processing_days} days)</strong></h6>
                            <div class="timeline">
                    `;

                    data.timeline.forEach((step, index) => {
                        const statusClass = getStatusClass(step.status);
                        const statusText = getStatusText(step.status);

                        html += `
                            <div class="timeline-item ${step.status}">
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h6 class="timeline-title">Step ${step.step}: ${step.department}</h6>
                                        <span class="timeline-badge ${statusClass}">${statusText}</span>
                                    </div>
                                    <div class="timeline-details">
                                        <p><strong>Start Date:</strong> ${new Date(step.start_date).toLocaleDateString()}</p>
                                        ${step.end_date ? `<p><strong>End Date:</strong> ${new Date(step.end_date).toLocaleDateString()}</p>` : ''}
                                        <p><strong>Processing Days:</strong> <span class="timeline-duration">${step.processing_days} days</span></p>
                                        ${step.next_department ? `<p><strong>Next Department:</strong> ${step.next_department}</p>` : ''}
                                        ${step.distribution_number ? `<p><strong>Distribution:</strong> ${step.distribution_number}</p>` : ''}
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

                // Add processing statistics
                const timeline = data.timeline;
                const totalDays = data.total_processing_days;
                const departmentsVisited = timeline.length;
                const avgPerDepartment = departmentsVisited > 0 ? Math.round(totalDays / departmentsVisited) : 0;
                const longestStay = timeline.length > 0 ? Math.max(...timeline.map(step => step.processing_days)) :
                    0;

                html += `
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Processing Days</span>
                                    <span class="info-box-number">${totalDays}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-building"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Departments Visited</span>
                                    <span class="info-box-number">${departmentsVisited}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Average per Department</span>
                                    <span class="info-box-number">${avgPerDepartment}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Longest Stay</span>
                                    <span class="info-box-number">${longestStay}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

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
    </style>
@endsection
