@extends('layouts.main')

@section('title_page', 'Distribution Details')
@section('breadcrumb_title', 'Distribution Details')

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Distribution Header -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-share-alt"></i>
                        Distribution: {{ $distribution->distribution_number }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('distributions.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>

                        <!-- Print Transmittal Advice Button -->
                        <a href="{{ route('distributions.print', $distribution) }}" class="btn btn-info btn-sm"
                            target="_blank">
                            <i class="fas fa-print"></i> Print Transmittal Advice
                        </a>

                        @if ($distribution->status === 'draft')
                            @can('edit-distributions')
                                <a href="{{ route('distributions.edit', $distribution) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            @endcan
                            @can('delete-distributions')
                                <button type="button" class="btn btn-danger btn-sm delete-distribution"
                                    data-id="{{ $distribution->id }}" data-number="{{ $distribution->distribution_number }}">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            @endcan
                        @else
                            @role('superadmin|admin')
                                <button type="button" class="btn btn-danger btn-sm cancel-distribution"
                                    data-id="{{ $distribution->id }}" data-number="{{ $distribution->distribution_number }}"
                                    title="Only admins can cancel non-draft distributions">
                                    <i class="fas fa-ban"></i> Cancel
                                </button>
                            @endrole
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Distribution Number:</strong></td>
                                    <td>{{ $distribution->distribution_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>
                                        <span class="badge badge-info">{{ $distribution->type->name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Origin Department:</strong></td>
                                    <td>{{ $distribution->originDepartment->name }}
                                        ({{ $distribution->originDepartment->location_code }})</td>
                                </tr>
                                <tr>
                                    <td><strong>Destination Department:</strong></td>
                                    <td>{{ $distribution->destinationDepartment->name }}
                                        ({{ $distribution->destinationDepartment->location_code }})</td>
                                </tr>
                                <tr>
                                    <td><strong>Document Type:</strong></td>
                                    <td>{{ ucwords(str_replace('_', ' ', $distribution->document_type)) }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge {{ $distribution->status_badge_class }} badge-lg">
                                            {{ $distribution->status_display }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $distribution->local_created_at->format('d-M-Y H:i') }} by
                                        {{ $distribution->creator->name }}</td>
                                </tr>
                                @if ($distribution->sender_verified_at)
                                    <tr>
                                        <td><strong>Sender Verified:</strong></td>
                                        <td>{{ $distribution->local_sender_verified_at->format('d-M-Y H:i') }} by
                                            {{ $distribution->senderVerifier->name }}</td>
                                    </tr>
                                @endif
                                @if ($distribution->sent_at)
                                    <tr>
                                        <td><strong>Sent:</strong></td>
                                        <td>{{ $distribution->local_sent_at->format('d-M-Y H:i') }}</td>
                                    </tr>
                                @endif
                                @if ($distribution->received_at)
                                    <tr>
                                        <td><strong>Received:</strong></td>
                                        <td>{{ $distribution->local_received_at->format('d-M-Y H:i') }}</td>
                                    </tr>
                                @endif
                                @if ($distribution->receiver_verified_at)
                                    <tr>
                                        <td><strong>Receiver Verified:</strong></td>
                                        <td>{{ $distribution->local_receiver_verified_at->format('d-M-Y H:i') }} by
                                            {{ $distribution->receiverVerifier->name }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if ($distribution->notes)
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <strong>Notes:</strong> {{ $distribution->notes }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Workflow Progress -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-project-diagram"></i> Workflow Progress
                    </h4>
                </div>
                <div class="card-body">
                    <div class="workflow-progress">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                <div
                                    class="workflow-step {{ $distribution->status === 'draft' ? 'active' : ($distribution->status !== 'draft' ? 'completed' : '') }}">
                                    <div class="step-icon">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div class="step-label">Draft</div>
                                    @if ($distribution->status !== 'draft')
                                        <small
                                            class="text-muted">{{ $distribution->local_created_at->format('d-M') }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div
                                    class="workflow-step {{ $distribution->status === 'verified_by_sender' ? 'active' : (in_array($distribution->status, ['verified_by_sender', 'sent', 'received', 'verified_by_receiver', 'completed']) ? 'completed' : '') }}">
                                    <div class="step-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="step-label">Sender Verified</div>
                                    @if ($distribution->sender_verified_at)
                                        <small
                                            class="text-muted">{{ $distribution->local_sender_verified_at->format('d-M') }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div
                                    class="workflow-step {{ $distribution->status === 'sent' ? 'active' : (in_array($distribution->status, ['sent', 'received', 'verified_by_receiver', 'completed']) ? 'completed' : '') }}">
                                    <div class="step-icon">
                                        <i class="fas fa-paper-plane"></i>
                                    </div>
                                    <div class="step-label">Sent</div>
                                    @if ($distribution->sent_at)
                                        <small class="text-muted">{{ $distribution->local_sent_at->format('d-M') }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div
                                    class="workflow-step {{ $distribution->status === 'received' ? 'active' : (in_array($distribution->status, ['received', 'verified_by_receiver', 'completed']) ? 'completed' : '') }}">
                                    <div class="step-icon">
                                        <i class="fas fa-download"></i>
                                    </div>
                                    <div class="step-label">Received</div>
                                    @if ($distribution->received_at)
                                        <small
                                            class="text-muted">{{ $distribution->local_received_at->format('d-M') }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div
                                    class="workflow-step {{ $distribution->status === 'verified_by_receiver' ? 'active' : ($distribution->status === 'completed' ? 'completed' : '') }}">
                                    <div class="step-icon">
                                        <i class="fas fa-clipboard-check"></i>
                                    </div>
                                    <div class="step-label">Receiver Verified</div>
                                    @if ($distribution->receiver_verified_at)
                                        <small
                                            class="text-muted">{{ $distribution->local_receiver_verified_at->format('d-M') }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="workflow-step {{ $distribution->status === 'completed' ? 'active' : '' }}">
                                    <div class="step-icon">
                                        <i class="fas fa-flag-checkered"></i>
                                    </div>
                                    <div class="step-label">Completed</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workflow Actions -->
            @if ($distribution->status !== 'completed')
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-cogs"></i> Workflow Actions
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if ($distribution->canVerifyBySender())
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5>Sender Verification</h5>
                                            <p class="text-muted">Verify documents before sending</p>
                                            <button type="button" class="btn btn-primary" data-toggle="modal"
                                                data-target="#senderVerificationModal">
                                                <i class="fas fa-check-circle"></i> Verify as Sender
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($distribution->canSend())
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5>Send Distribution</h5>
                                            <p class="text-muted">Mark distribution as sent</p>
                                            <button type="button" class="btn btn-warning" data-toggle="modal"
                                                data-target="#sendModal">
                                                <i class="fas fa-paper-plane"></i> Send
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($distribution->canReceive())
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5>Receive Distribution</h5>
                                            <p class="text-muted">Confirm receipt of documents</p>
                                            <button type="button" class="btn btn-info" data-toggle="modal"
                                                data-target="#receiveModal">
                                                <i class="fas fa-download"></i> Receive
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($distribution->canVerifyByReceiver())
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5>Receiver Verification</h5>
                                            <p class="text-muted">Verify received documents</p>
                                            <button type="button" class="btn btn-success" data-toggle="modal"
                                                data-target="#receiverVerificationModal">
                                                <i class="fas fa-clipboard-check"></i> Verify as Receiver
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($distribution->canComplete())
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5>Complete Distribution</h5>
                                            <p class="text-muted">Mark distribution as completed</p>
                                            <button type="button" class="btn btn-success" data-toggle="modal"
                                                data-target="#completeModal">
                                                <i class="fas fa-flag-checkered"></i> Complete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Document Verification Summary -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-chart-pie"></i> Document Verification Summary
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Sender Verification Summary -->
                        <div class="col-md-6">
                            <div class="verification-summary-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-check"></i> Sender Verification
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @php
                                        $senderVerified = $distribution->documents
                                            ->where('sender_verified', true)
                                            ->count();
                                        $senderMissing = $distribution->documents
                                            ->where('sender_verification_status', 'missing')
                                            ->count();
                                        $senderDamaged = $distribution->documents
                                            ->where('sender_verification_status', 'damaged')
                                            ->count();
                                        $senderPending = $distribution->documents->count() - $senderVerified;
                                    @endphp

                                    <div class="row text-center">
                                        <div class="col-3">
                                            <div class="verification-stat">
                                                <div class="stat-number text-success">{{ $senderVerified }}</div>
                                                <div class="stat-label">Verified</div>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="verification-stat">
                                                <div class="stat-number text-warning">{{ $senderMissing }}</div>
                                                <div class="stat-label">Missing</div>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="verification-stat">
                                                <div class="stat-number text-danger">{{ $senderDamaged }}</div>
                                                <div class="stat-label">Damaged</div>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="verification-stat">
                                                <div class="stat-number text-secondary">{{ $senderPending }}</div>
                                                <div class="stat-label">Pending</div>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($senderVerified > 0)
                                        <div class="progress mt-3" style="height: 8px;">
                                            <div class="progress-bar bg-success"
                                                style="width: {{ ($senderVerified / $distribution->documents->count()) * 100 }}%">
                                            </div>
                                        </div>
                                        <small
                                            class="text-muted">{{ round(($senderVerified / $distribution->documents->count()) * 100) }}%
                                            verified</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Receiver Verification Summary -->
                        <div class="col-md-6">
                            <div class="verification-summary-card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-clipboard-check"></i> Receiver Verification
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @php
                                        $receiverVerified = $distribution->documents
                                            ->where('receiver_verified', true)
                                            ->count();
                                        $receiverMissing = $distribution->documents
                                            ->where('receiver_verification_status', 'missing')
                                            ->count();
                                        $receiverDamaged = $distribution->documents
                                            ->where('receiver_verification_status', 'damaged')
                                            ->count();
                                        $receiverPending = $distribution->documents->count() - $receiverVerified;
                                    @endphp

                                    <div class="row text-center">
                                        <div class="col-3">
                                            <div class="verification-stat">
                                                <div class="stat-number text-success">{{ $receiverVerified }}</div>
                                                <div class="stat-label">Verified</div>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="verification-stat">
                                                <div class="stat-number text-warning">{{ $receiverMissing }}</div>
                                                <div class="stat-label">Missing</div>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="verification-stat">
                                                <div class="stat-number text-danger">{{ $receiverDamaged }}</div>
                                                <div class="stat-label">Damaged</div>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="verification-stat">
                                                <div class="stat-number text-secondary">{{ $receiverPending }}</div>
                                                <div class="stat-label">Pending</div>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($receiverVerified > 0)
                                        <div class="progress mt-3" style="height: 8px;">
                                            <div class="progress-bar bg-success"
                                                style="width: {{ ($receiverVerified / $distribution->documents->count()) * 100 }}%">
                                            </div>
                                        </div>
                                        <small
                                            class="text-muted">{{ round(($receiverVerified / $distribution->documents->count()) * 100) }}%
                                            verified</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-file-alt"></i> Distributed Documents
                    </h4>
                    <div class="card-tools">
                        <span class="badge badge-info badge-lg">
                            Total: {{ $distribution->documents->count() }} documents
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="25%">Document</th>
                                    <th width="15%">Type</th>
                                    <th width="20%">Sender Status</th>
                                    <th width="20%">Receiver Status</th>
                                    <th width="20%">Overall Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($distribution->documents as $doc)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="document-icon mr-2">
                                                    @if ($doc->document_type === 'App\Models\Invoice')
                                                        <i class="fas fa-file-invoice text-primary"></i>
                                                    @else
                                                        <i class="fas fa-file-alt text-info"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <strong>{{ $doc->document->document_number ?? ($doc->document->invoice_number ?? 'N/A') }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        @if ($doc->document_type === 'App\Models\Invoice')
                                                            {{ $doc->document->type->type_name ?? 'N/A' }}
                                                        @elseif($doc->document_type === 'App\Models\AdditionalDocument')
                                                            {{ $doc->document->type->type_name ?? 'N/A' }}
                                                        @else
                                                            {{ class_basename($doc->document_type) }}
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($doc->document_type === 'App\Models\Invoice')
                                                <span class="badge badge-primary">Invoice</span>
                                            @else
                                                <span class="badge badge-info">Additional Document</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($doc->sender_verified)
                                                <span
                                                    class="badge badge-{{ $doc->sender_verification_status === 'verified' ? 'success' : ($doc->sender_verification_status === 'missing' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($doc->sender_verification_status) }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($doc->receiver_verified)
                                                <span
                                                    class="badge badge-{{ $doc->receiver_verification_status === 'verified' ? 'success' : ($doc->receiver_verification_status === 'missing' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($doc->receiver_verification_status) }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $doc->verification_status_badge_class }}">
                                                {{ $doc->verification_status_display }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                                <p>No documents found in this distribution</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- History -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-history"></i> Distribution History
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="15%">Date & Time</th>
                                    <th width="20%">Action</th>
                                    <th width="20%">User</th>
                                    <th width="25%">Details</th>
                                    <th width="20%">Status Change</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($distribution->histories as $history)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong>{{ \Carbon\Carbon::parse($history->action_performed_at)->setTimezone('Asia/Singapore')->format('d-M-Y') }}</strong>
                                                <small
                                                    class="text-muted">{{ \Carbon\Carbon::parse($history->action_performed_at)->setTimezone('Asia/Singapore')->format('H:i') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary badge-lg">
                                                {{ $history->action_display }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-2">
                                                    {{ strtoupper(substr($history->user->name, 0, 1)) }}
                                                </div>
                                                <span>{{ $history->user->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($history->notes)
                                                <p class="mb-0">{{ $history->notes }}</p>
                                            @else
                                                <span class="text-muted">No additional notes</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($history->old_status && $history->new_status)
                                                <div class="d-flex align-items-center">
                                                    <span
                                                        class="badge badge-secondary mr-1">{{ $history->old_status }}</span>
                                                    <i class="fas fa-arrow-right text-muted mx-2"></i>
                                                    <span class="badge badge-primary">{{ $history->new_status }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">No status change</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-history fa-2x mb-2"></i>
                                                <p>No history records found</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sender Verification Modal -->
    <div class="modal fade" id="senderVerificationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sender Verification</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="senderVerificationForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="sender_verification_notes">Verification Notes</label>
                            <textarea class="form-control" id="sender_verification_notes" name="verification_notes" rows="3"
                                placeholder="Optional notes about the verification"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Document Verification</h6>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllVerified">
                                    <i class="fas fa-check-double"></i> Select All as Verified
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearAll">
                                    <i class="fas fa-times"></i> Clear All
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th>Document</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($distribution->documents as $doc)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input document-checkbox"
                                                    data-document-id="{{ $doc->document_id }}">
                                            </td>
                                            <td>
                                                <strong>{{ $doc->document->document_number ?? ($doc->document->invoice_number ?? 'N/A') }}</strong>
                                                <br>
                                                <small
                                                    class="text-muted">{{ class_basename($doc->document_type) }}</small>
                                            </td>
                                            <td>
                                                <select class="form-control document-status"
                                                    name="document_verifications[{{ $doc->document_id }}][status]"
                                                    data-document-id="{{ $doc->document_id }}" required>
                                                    <option value="">Select Status</option>
                                                    <option value="verified">Verified</option>
                                                    <option value="missing">Missing</option>
                                                    <option value="damaged">Damaged</option>
                                                </select>
                                                <input type="hidden"
                                                    name="document_verifications[{{ $doc->document_id }}][document_id]"
                                                    value="{{ $doc->document_id }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control document-notes"
                                                    name="document_verifications[{{ $doc->document_id }}][notes]"
                                                    placeholder="Notes required for Missing/Damaged status"
                                                    data-document-id="{{ $doc->document_id }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Verify as Sender</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Send Modal -->
    <div class="modal fade" id="sendModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Distribution</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark this distribution as sent?</p>
                    <p class="text-muted">This action will move the distribution to the next workflow stage.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="sendDistribution">Send Distribution</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receive Modal -->
    <div class="modal fade" id="receiveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receive Distribution</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark this distribution as received?</p>
                    <p class="text-muted">This action will update document locations and move to the next workflow stage.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info" id="receiveDistribution">Receive Distribution</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receiver Verification Modal -->
    <div class="modal fade" id="receiverVerificationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receiver Verification</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="receiverVerificationForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="receiver_verification_notes">Verification Notes</label>
                            <textarea class="form-control" id="receiver_verification_notes" name="verification_notes" rows="3"
                                placeholder="Optional notes about the verification"></textarea>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="has_discrepancies"
                                    name="has_discrepancies">
                                <label class="custom-control-label" for="has_discrepancies">
                                    Distribution has discrepancies
                                </label>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Document Verification</h6>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-outline-success btn-sm"
                                    id="selectAllVerifiedReceiver">
                                    <i class="fas fa-check-double"></i> Select All as Verified
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearAllReceiver">
                                    <i class="fas fa-times"></i> Clear All
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAllReceiver" class="form-check-input">
                                        </th>
                                        <th>Document</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($distribution->documents as $doc)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input document-checkbox-receiver"
                                                    data-document-id="{{ $doc->document_id }}">
                                            </td>
                                            <td>
                                                <strong>{{ $doc->document->document_number ?? ($doc->document->invoice_number ?? 'N/A') }}</strong>
                                                <br>
                                                <small
                                                    class="text-muted">{{ class_basename($doc->document_type) }}</small>
                                            </td>
                                            <td>
                                                <select class="form-control document-status-receiver"
                                                    name="document_verifications[{{ $doc->document_id }}][status]"
                                                    data-document-id="{{ $doc->document_id }}" required>
                                                    <option value="">Select Status</option>
                                                    <option value="verified">Verified</option>
                                                    <option value="missing">Missing</option>
                                                    <option value="damaged">Damaged</option>
                                                </select>
                                                <input type="hidden"
                                                    name="document_verifications[{{ $doc->document_id }}][document_id]"
                                                    value="{{ $doc->document_id }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control document-notes-receiver"
                                                    name="document_verifications[{{ $doc->document_id }}][notes]"
                                                    placeholder="Notes required for Missing/Damaged status"
                                                    data-document-id="{{ $doc->document_id }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Verify as Receiver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Complete Modal -->
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Distribution</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark this distribution as completed?</p>
                    <p class="text-muted">This action will finalize the distribution workflow.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="completeDistribution">Complete
                        Distribution</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .workflow-progress {
            padding: 20px 0;
        }

        /* Modal enhancements */
        .modal-xl {
            max-width: 90%;
        }

        .document-checkbox {
            margin: 0;
        }

        .form-check-input {
            margin-top: 0.3rem;
        }

        /* Button spacing */
        .btn+.btn {
            margin-left: 0.25rem;
        }

        /* Table enhancements */
        .table th {
            vertical-align: middle;
        }

        .table td {
            vertical-align: middle;
        }

        .workflow-step {
            position: relative;
            padding: 15px 10px;
        }

        .workflow-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50%;
            width: 100%;
            height: 2px;
            background-color: #dee2e6;
            transform: translateY(-50%);
            z-index: 1;
        }

        .workflow-step.completed:not(:last-child)::after {
            background-color: #28a745;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 20px;
            position: relative;
            z-index: 2;
        }

        .workflow-step.active .step-icon {
            background-color: #007bff;
            color: white;
        }

        .workflow-step.completed .step-icon {
            background-color: #28a745;
            color: white;
        }

        .step-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Verification Summary Cards */
        .verification-summary-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .verification-summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .verification-summary-card .card-header {
            border: none;
            padding: 15px 20px;
        }

        .verification-summary-card .card-header h5 {
            margin: 0;
            font-size: 1.1rem;
        }

        .verification-summary-card .card-body {
            padding: 20px;
        }

        .verification-stat {
            padding: 10px 5px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Document Table Enhancements */
        .document-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background-color: #f8f9fa;
        }

        .document-icon i {
            font-size: 16px;
        }

        /* Avatar Styles */
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 14px;
            font-weight: bold;
        }

        /* Badge Enhancements */
        .badge-lg {
            font-size: 0.9rem;
            padding: 8px 12px;
        }

        /* Progress Bar Enhancement */
        .progress {
            border-radius: 10px;
            background-color: #e9ecef;
        }

        .progress-bar {
            border-radius: 10px;
        }

        /* Table Enhancements */
        .table thead th {
            border-top: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Card Tools Enhancement */
        .card-tools .badge {
            font-size: 0.9rem;
            padding: 8px 12px;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Sender Verification Modal Functionality
            let selectedDocuments = new Set();

            // Select All checkbox functionality
            $('#selectAll').change(function() {
                const isChecked = $(this).is(':checked');
                $('.document-checkbox').prop('checked', isChecked);

                if (isChecked) {
                    $('.document-checkbox').each(function() {
                        selectedDocuments.add($(this).data('document-id'));
                    });
                } else {
                    selectedDocuments.clear();
                }
                updateSelectAllButton();
            });

            // Individual document checkbox functionality
            $(document).on('change', '.document-checkbox', function() {
                const documentId = $(this).data('document-id');
                if ($(this).is(':checked')) {
                    selectedDocuments.add(documentId);
                } else {
                    selectedDocuments.delete(documentId);
                }

                // Update select all checkbox state
                const totalDocuments = $('.document-checkbox').length;
                const checkedDocuments = selectedDocuments.size;
                $('#selectAll').prop('checked', checkedDocuments === totalDocuments);
                $('#selectAll').prop('indeterminate', checkedDocuments > 0 && checkedDocuments <
                    totalDocuments);

                updateSelectAllButton();
            });

            // Select All as Verified button
            $('#selectAllVerified').click(function() {
                $('.document-checkbox').prop('checked', true);
                $('.document-status').val('verified');
                $('.document-notes').val('').prop('required', false);

                selectedDocuments.clear();
                $('.document-checkbox').each(function() {
                    selectedDocuments.add($(this).data('document-id'));
                });

                $('#selectAll').prop('checked', true);
                updateSelectAllButton();
            });

            // Clear All button
            $('#clearAll').click(function() {
                $('.document-checkbox').prop('checked', false);
                $('.document-status').val('');
                $('.document-notes').val('').prop('required', false);

                selectedDocuments.clear();
                $('#selectAll').prop('checked', false);
                updateSelectAllButton();
            });

            // Update Select All button text based on selection
            function updateSelectAllButton() {
                const selectedCount = selectedDocuments.size;
                const totalCount = $('.document-checkbox').length;

                if (selectedCount === 0) {
                    $('#selectAllVerified').html('<i class="fas fa-check-double"></i> Select All as Verified');
                } else if (selectedCount === totalCount) {
                    $('#selectAllVerified').html('<i class="fas fa-check-double"></i> All Selected');
                } else {
                    $('#selectAllVerified').html(
                        `<i class="fas fa-check-double"></i> ${selectedCount}/${totalCount} Selected`);
                }
            }

            // Status change handler for notes requirement
            $(document).on('change', '.document-status', function() {
                const documentId = $(this).data('document-id');
                const status = $(this).val();
                const notesField = $(`.document-notes[data-document-id="${documentId}"]`);

                if (status === 'missing' || status === 'damaged') {
                    notesField.prop('required', true);
                    notesField.attr('placeholder', 'Notes required for ' + status.charAt(0).toUpperCase() +
                        status.slice(1) + ' status');
                } else {
                    notesField.prop('required', false);
                    notesField.attr('placeholder', 'Optional notes');
                }
            });

            // Sender Verification Form
            $('#senderVerificationForm').submit(function(e) {
                e.preventDefault();

                // Validate required fields
                let isValid = true;
                let errorMessage = '';

                // Check if at least one document is selected
                if ($('.document-checkbox:checked').length === 0) {
                    isValid = false;
                    errorMessage = 'Please select at least one document to verify.';
                }

                // Check required notes for missing/damaged status
                $('.document-status').each(function() {
                    const status = $(this).val();
                    const documentId = $(this).data('document-id');
                    const notesField = $(`.document-notes[data-document-id="${documentId}"]`);

                    if (status === 'missing' || status === 'damaged') {
                        if (!notesField.val().trim()) {
                            isValid = false;
                            errorMessage =
                                'Notes are required for Missing or Damaged document status.';
                            return false; // break the loop
                        }
                    }
                });

                if (!isValid) {
                    toastr.error(errorMessage);
                    return;
                }

                // Prepare form data with only selected documents
                const formData = new FormData();
                formData.append('verification_notes', $('#sender_verification_notes').val());

                $('.document-checkbox:checked').each(function() {
                    const documentId = $(this).data('document-id');
                    const status = $(`.document-status[data-document-id="${documentId}"]`).val();
                    const notes = $(`.document-notes[data-document-id="${documentId}"]`).val();

                    formData.append(`document_verifications[${documentId}][document_id]`,
                        documentId);
                    formData.append(`document_verifications[${documentId}][status]`, status);
                    formData.append(`document_verifications[${documentId}][notes]`, notes);
                });

                $.ajax({
                    url: '{{ route('distributions.verify-sender', $distribution) }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Failed to verify as sender');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            if (errors) {
                                let errorMsg = '';
                                Object.keys(errors).forEach(key => {
                                    errorMsg += errors[key][0] + '\n';
                                });
                                toastr.error(errorMsg);
                            } else {
                                toastr.error('Please fill in all required fields');
                            }
                        } else {
                            toastr.error('Failed to verify as sender');
                        }
                    }
                });
            });

            // Send Distribution
            $('#sendDistribution').click(function() {
                $.ajax({
                    url: '{{ route('distributions.send', $distribution) }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Failed to send distribution');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to send distribution');
                    }
                });
                $('#sendModal').modal('hide');
            });

            // Receive Distribution
            $('#receiveDistribution').click(function() {
                $.ajax({
                    url: '{{ route('distributions.receive', $distribution) }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Failed to receive distribution');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to receive distribution');
                    }
                });
                $('#receiveModal').modal('hide');
            });

            // Receiver Verification Modal Functionality
            let selectedDocumentsReceiver = new Set();

            // Select All checkbox functionality for receiver
            $('#selectAllReceiver').change(function() {
                const isChecked = $(this).is(':checked');
                $('.document-checkbox-receiver').prop('checked', isChecked);

                if (isChecked) {
                    $('.document-checkbox-receiver').each(function() {
                        selectedDocumentsReceiver.add($(this).data('document-id'));
                    });
                } else {
                    selectedDocumentsReceiver.clear();
                }
                updateSelectAllReceiverButton();
            });

            // Individual document checkbox functionality for receiver
            $(document).on('change', '.document-checkbox-receiver', function() {
                const documentId = $(this).data('document-id');
                if ($(this).is(':checked')) {
                    selectedDocumentsReceiver.add(documentId);
                } else {
                    selectedDocumentsReceiver.delete(documentId);
                }

                // Update select all checkbox state
                const totalDocuments = $('.document-checkbox-receiver').length;
                const checkedDocuments = selectedDocumentsReceiver.size;
                $('#selectAllReceiver').prop('checked', checkedDocuments === totalDocuments);
                $('#selectAllReceiver').prop('indeterminate', checkedDocuments > 0 && checkedDocuments <
                    totalDocuments);

                updateSelectAllReceiverButton();
            });

            // Select All as Verified button for receiver
            $('#selectAllVerifiedReceiver').click(function() {
                $('.document-checkbox-receiver').prop('checked', true);
                $('.document-status-receiver').val('verified');
                $('.document-notes-receiver').val('').prop('required', false);

                selectedDocumentsReceiver.clear();
                $('.document-checkbox-receiver').each(function() {
                    selectedDocumentsReceiver.add($(this).data('document-id'));
                });

                $('#selectAllReceiver').prop('checked', true);
                updateSelectAllReceiverButton();
            });

            // Clear All button for receiver
            $('#clearAllReceiver').click(function() {
                $('.document-checkbox-receiver').prop('checked', false);
                $('.document-status-receiver').val('');
                $('.document-notes-receiver').val('').prop('required', false);

                selectedDocumentsReceiver.clear();
                $('#selectAllReceiver').prop('checked', false);
                updateSelectAllReceiverButton();
            });

            // Update Select All button text based on selection for receiver
            function updateSelectAllReceiverButton() {
                const selectedCount = selectedDocumentsReceiver.size;
                const totalCount = $('.document-checkbox-receiver').length;

                if (selectedCount === 0) {
                    $('#selectAllVerifiedReceiver').html(
                        '<i class="fas fa-check-double"></i> Select All as Verified');
                } else if (selectedCount === totalCount) {
                    $('#selectAllVerifiedReceiver').html('<i class="fas fa-check-double"></i> All Selected');
                } else {
                    $('#selectAllVerifiedReceiver').html(
                        `<i class="fas fa-check-double"></i> ${selectedCount}/${totalCount} Selected`);
                }
            }

            // Status change handler for notes requirement in receiver modal
            $(document).on('change', '.document-status-receiver', function() {
                const documentId = $(this).data('document-id');
                const status = $(this).val();
                const notesField = $(`.document-notes-receiver[data-document-id="${documentId}"]`);

                if (status === 'missing' || status === 'damaged') {
                    notesField.prop('required', true);
                    notesField.attr('placeholder', 'Notes required for ' + status.charAt(0).toUpperCase() +
                        status.slice(1) + ' status');
                } else {
                    notesField.prop('required', false);
                    notesField.attr('placeholder', 'Optional notes');
                }
            });

            // Receiver Verification Form
            $('#receiverVerificationForm').submit(function(e) {
                e.preventDefault();

                // Validate required fields
                let isValid = true;
                let errorMessage = '';

                // Check if at least one document is selected
                if ($('.document-checkbox-receiver:checked').length === 0) {
                    isValid = false;
                    errorMessage = 'Please select at least one document to verify.';
                }

                // Check required notes for missing/damaged status
                $('.document-status-receiver').each(function() {
                    const status = $(this).val();
                    const documentId = $(this).data('document-id');
                    const notesField = $(
                        `.document-notes-receiver[data-document-id="${documentId}"]`);

                    if (status === 'missing' || status === 'damaged') {
                        if (!notesField.val().trim()) {
                            isValid = false;
                            errorMessage =
                                'Notes are required for Missing or Damaged document status.';
                            return false; // break the loop
                        }
                    }
                });

                if (!isValid) {
                    toastr.error(errorMessage);
                    return;
                }

                // Prepare form data with only selected documents
                const formData = new FormData();
                formData.append('verification_notes', $('#receiver_verification_notes').val());
                formData.append('has_discrepancies', $('#has_discrepancies').is(':checked') ? '1' : '0');

                $('.document-checkbox-receiver:checked').each(function() {
                    const documentId = $(this).data('document-id');
                    const status = $(`.document-status-receiver[data-document-id="${documentId}"]`)
                        .val();
                    const notes = $(`.document-notes-receiver[data-document-id="${documentId}"]`)
                        .val();

                    formData.append(`document_verifications[${documentId}][document_id]`,
                        documentId);
                    formData.append(`document_verifications[${documentId}][status]`, status);
                    formData.append(`document_verifications[${documentId}][notes]`, notes);
                });

                $.ajax({
                    url: '{{ route('distributions.verify-receiver', $distribution) }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Failed to verify as receiver');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            if (errors) {
                                let errorMsg = '';
                                Object.keys(errors).forEach(key => {
                                    errorMsg += errors[key][0] + '\n';
                                });
                                toastr.error(errorMsg);
                            } else {
                                toastr.error('Please fill in all required fields');
                            }
                        } else {
                            toastr.error('Failed to verify as receiver');
                        }
                    }
                });
            });

            // Complete Distribution
            $('#completeDistribution').click(function() {
                $.ajax({
                    url: '{{ route('distributions.complete', $distribution) }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Failed to complete distribution');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to complete distribution');
                    }
                });
                $('#completeModal').modal('hide');
            });

            // Cancel Distribution (Admin only)
            $('.cancel-distribution').click(function() {
                const distributionId = $(this).data('id');
                const distributionNumber = $(this).data('number');

                if (confirm(
                        `Are you sure you want to cancel distribution ${distributionNumber}? This action cannot be undone.`
                    )) {
                    $.ajax({
                        url: '{{ url('distributions') }}/' + distributionId,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                toastr.error(response.message ||
                                    'Failed to cancel distribution');
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 403) {
                                toastr.error(
                                    'You do not have permission to cancel this distribution'
                                );
                            } else if (xhr.status === 422) {
                                toastr.error('This distribution cannot be cancelled');
                            } else {
                                toastr.error('Failed to cancel distribution');
                            }
                        }
                    });
                }
            });
        });
    </script>


@endsection
