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
                                        <tr>
                                            <td><strong>Vendor Code:</strong></td>
                                            <td>{{ $additionalDocument->vendor_code ?? 'N/A' }}</td>
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
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label><strong><i class="fas fa-comment"></i> Remarks:</strong></label>
                                            <div class="alert alert-info border">
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
                                                <a href="{{ route('additional-documents.preview', $additionalDocument) }}"
                                                    class="btn btn-info" target="_blank">
                                                    <i class="fas fa-eye"></i> Preview Attachment
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

                    @if ($additionalDocument->requiresSignature())
                        <div class="card mt-3" id="signature-verification-card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-signature"></i> Signature Verification</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <strong class="mr-2">Status:</strong>
                                    <span id="signature-status-badge" class="badge badge-secondary">
                                        {{ ucfirst($additionalDocument->signature_status ?? 'not started') }}
                                    </span>
                                    @if ($additionalDocument->signature_override_reason)
                                        <span class="badge badge-info ml-2">Override recorded</span>
                                    @endif
                                </div>

                                @if ($additionalDocument->signature_project_id)
                                    <p class="mb-2">
                                        <strong>Verification project:</strong>
                                        {{ $additionalDocument->signatureProject?->code ?? $additionalDocument->signature_project_id }}
                                    </p>
                                @endif

                                @if ($additionalDocument->signature_checked_at)
                                    <p class="mb-2 text-muted">
                                        Confirmed {{ $additionalDocument->signature_checked_at->format('d/m/Y H:i') }}
                                        @if ($additionalDocument->signatureCheckedBy)
                                            by {{ $additionalDocument->signatureCheckedBy->name }}
                                        @endif
                                    </p>
                                @endif

                                @if ($additionalDocument->signature_override_reason)
                                    <div class="alert alert-warning">
                                        <strong>Override reason:</strong> {{ $additionalDocument->signature_override_reason }}
                                    </div>
                                @endif

                                @if (! $additionalDocument->attachment)
                                    <div class="alert alert-info">
                                        Upload a scan attachment to run signature verification.
                                    </div>
                                @else
                                    @hasanyrole('superadmin|admin|accounting|finance')
                                        <button type="button" class="btn btn-primary btn-sm" id="btn-signature-verify">
                                            <i class="fas fa-search"></i> Verify / Re-verify
                                        </button>
                                    @endhasanyrole
                                @endif

                                <div id="signature-results" class="mt-3"></div>

                                <div id="signature-no-match-form" class="mt-3" style="display:none;">
                                    <div class="form-group">
                                        <label for="signature_override_reason">Override reason <span class="text-danger">*</span></label>
                                        <textarea id="signature_override_reason" class="form-control" rows="3"
                                            placeholder="Explain why this document should proceed without a matching signature"></textarea>
                                    </div>
                                    <button type="button" class="btn btn-warning btn-sm" id="btn-signature-override">
                                        <i class="fas fa-exclamation-circle"></i> Submit override
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="signatureProjectModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Select project for signature matching</h5>
                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="signature_project_id">Project</label>
                                            <select id="signature_project_id" class="form-control">
                                                <option value="">— Select project —</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}" @selected($additionalDocument->signature_project_id == $project->id)>
                                                        {{ $project->code }} — {{ $project->owner }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" id="btn-start-signature-verify">Start verification</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

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
            var signaturePollTimer = null;
            var documentId = {{ $additionalDocument->id }};
            var statusUrl = @json(route('additional-documents.signature-status', $additionalDocument));
            var verifyUrl = @json(route('additional-documents.signature-verify', $additionalDocument));
            var confirmUrl = @json(route('additional-documents.signature-confirm', $additionalDocument));
            var overrideUrl = @json(route('additional-documents.signature-override', $additionalDocument));

            function signatureBadgeClass(status) {
                switch (status) {
                    case 'matched':
                        return 'badge-success';
                    case 'uncertain':
                    case 'pending':
                        return 'badge-warning';
                    case 'no_match':
                        return 'badge-danger';
                    case 'skipped':
                        return 'badge-secondary';
                    default:
                        return 'badge-secondary';
                }
            }

            function updateSignatureBadge(status) {
                var badge = $('#signature-status-badge');
                badge.removeClass('badge-success badge-warning badge-danger badge-secondary badge-info');
                badge.addClass(signatureBadgeClass(status));
                badge.text(status ? status.replace('_', ' ') : 'not started');
            }

            function renderSignatureResults(results) {
                var container = $('#signature-results');
                if (!results || results.length === 0) {
                    container.html('<p class="text-muted">No match results yet.</p>');
                    return;
                }

                var html = '<div class="row">';
                results.forEach(function(result) {
                    html += '<div class="col-md-4 mb-3"><div class="card h-100">';
                    html += '<div class="card-header py-2"><strong>' + (result.specimen_name || 'Candidate') + '</strong>';
                    html += ' <span class="badge ' + signatureBadgeClass(result.verdict) + '">' + result.verdict + '</span></div>';
                    html += '<div class="card-body">';
                    if (result.score !== null) {
                        html += '<p><strong>Score:</strong> ' + Number(result.score).toFixed(3) + '</p>';
                    }
                    if (result.reasoning) {
                        html += '<p class="small text-muted">' + result.reasoning + '</p>';
                    }
                    if (result.document_crop) {
                        html += '<p class="small"><strong>Document crop</strong></p><img src="' + result.document_crop + '" class="img-fluid border mb-2" alt="Document signature">';
                    }
                    if (result.specimen_crop) {
                        html += '<p class="small"><strong>Specimen crop</strong></p><img src="' + result.specimen_crop + '" class="img-fluid border mb-2" alt="Specimen signature">';
                    }
                    html += '<button type="button" class="btn btn-success btn-sm btn-confirm-signature" data-specimen-id="' + result.specimen_id + '" data-name="' + (result.specimen_name || '') + '">';
                    html += '<i class="fas fa-check"></i> Confirm as ' + (result.specimen_name || 'match') + '</button>';
                    html += '</div></div></div>';
                });
                html += '</div>';
                html += '<button type="button" class="btn btn-outline-danger btn-sm mt-2" id="btn-mark-no-match"><i class="fas fa-times"></i> Mark as no match</button>';
                container.html(html);
            }

            function pollSignatureStatus() {
                $.get(statusUrl, function(response) {
                    if (!response.success) {
                        return;
                    }

                    updateSignatureBadge(response.signature_status);
                    renderSignatureResults(response.results);

                    if (response.signature_status === 'no_match' || response.signature_status === 'uncertain') {
                        $('#signature-no-match-form').show();
                    } else if (response.signature_status === 'matched') {
                        $('#signature-no-match-form').hide();
                    }

                    if (response.is_processing) {
                        signaturePollTimer = setTimeout(pollSignatureStatus, 3000);
                    } else if (signaturePollTimer) {
                        clearTimeout(signaturePollTimer);
                        signaturePollTimer = null;
                    }
                });
            }

            $('#btn-signature-verify').on('click', function() {
                $('#signatureProjectModal').modal('show');
            });

            $('#btn-start-signature-verify').on('click', function() {
                var projectId = $('#signature_project_id').val();
                if (!projectId) {
                    alert('Please select a project.');
                    return;
                }

                $.ajax({
                    url: verifyUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        project_id: projectId
                    },
                    success: function(response) {
                        $('#signatureProjectModal').modal('hide');
                        updateSignatureBadge('pending');
                        pollSignatureStatus();
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Verification could not be started.';
                        alert(message);
                    }
                });
            });

            $(document).on('click', '.btn-confirm-signature', function() {
                var specimenId = $(this).data('specimen-id');
                $.ajax({
                    url: confirmUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        specimen_id: specimenId
                    },
                    success: function(response) {
                        updateSignatureBadge(response.signature_status);
                        $('#signature-no-match-form').hide();
                        alert(response.message);
                    }
                });
            });

            $('#btn-mark-no-match').on('click', function() {
                $('#signature-no-match-form').show();
            });

            $('#btn-signature-override').on('click', function() {
                var reason = $('#signature_override_reason').val();
                if (!reason) {
                    alert('Please provide an override reason.');
                    return;
                }

                $.ajax({
                    url: overrideUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        reason: reason
                    },
                    success: function(response) {
                        updateSignatureBadge(response.signature_status);
                        alert(response.message);
                    }
                });
            });

            @if ($additionalDocument->requiresSignature())
                pollSignatureStatus();
            @endif

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
                            <strong>Current Location Arrival:</strong>
                            <p class="text-success mb-0">${new Date(data.document.current_location_arrival_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}).replace(/ /g, '-')}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Days in Current Location:</strong>
                            <p class="text-warning mb-0 text-right">${Math.round(data.document.days_in_current_location * 10) / 10} days</p>
                        </div>
                    </div>
                `;

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
                            <h6><strong>Department-Specific Processing Timeline (Total: ${Math.round(data.total_processing_days * 10) / 10} days)</strong></h6>
                            <div class="timeline">
                    `;

                    data.timeline.forEach((step, index) => {
                        const statusClass = getStatusClass(step.status);
                        const statusText = getStatusText(step.status);
                        const isDelayed = step.processing_days > 14;
                        const itemClass = step.is_current ? 'current' : (isDelayed ? 'delayed' : '');

                        html += `
                            <div class="timeline-item ${itemClass}">
                                <div class="timeline-marker">
                                    <i class="fas fa-${step.is_current ? 'play' : 'check'}"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h6 class="timeline-title">Step ${step.step}: ${step.department}</h6>
                                        <span class="timeline-badge ${statusClass}">${statusText}</span>
                                        ${isDelayed ? '<span class="badge badge-danger ml-1">DELAYED</span>' : ''}
                                    </div>
                                    <div class="timeline-details">
                                        <p><strong>Arrival Date:</strong> ${new Date(step.arrival_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}).replace(/ /g, '-')}</p>
                                        ${step.departure_date ? `<p><strong>Departure Date:</strong> ${new Date(step.departure_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}).replace(/ /g, '-')}</p>` : ''}
                                        <p><strong>Processing Days:</strong> <span class="timeline-duration ${isDelayed ? 'text-danger' : ''} text-right">${Math.round(step.processing_days * 10) / 10} days</span></p>
                                        ${step.next_department ? `<p><strong>Next Department:</strong> ${step.next_department}</p>` : ''}
                                        ${step.distribution_number ? `<p><strong>Distribution:</strong> ${step.distribution_number}</p>` : ''}
                                        ${step.location_code ? `<p><strong>Location Code:</strong> ${step.location_code}</p>` : ''}
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
    </style>
@endsection
