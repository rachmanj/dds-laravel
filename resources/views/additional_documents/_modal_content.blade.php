@if (isset($additionalDocument))
    <div class="row">
        <div class="col-md-12">
            <h6 class="text-muted">Document Information</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Document Number:</strong></td>
                    <td>{{ $additionalDocument->document_number }}</td>
                </tr>
                <tr>
                    <td><strong>Type:</strong></td>
                    <td>
                        @if ($additionalDocument->type)
                            <span class="badge badge-info">{{ $additionalDocument->type->type_name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong>PO Number:</strong></td>
                    <td>{{ $additionalDocument->po_no ?: '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        @switch($additionalDocument->status)
                            @case('open')
                                <span class="badge badge-success">Open</span>
                            @break

                            @case('closed')
                                <span class="badge badge-secondary">Closed</span>
                            @break

                            @case('cancelled')
                                <span class="badge badge-danger">Cancelled</span>
                            @break

                            @default
                                <span class="badge badge-info">{{ ucfirst($additionalDocument->status) }}</span>
                        @endswitch
                    </td>
                </tr>
                <tr>
                    <td><strong>Current Location:</strong></td>
                    <td><span class="badge badge-secondary">{{ $additionalDocument->cur_loc ?: '-' }}</span></td>
                </tr>
                <tr>
                    <td><strong>Document Date:</strong></td>
                    <td>{{ $additionalDocument->document_date ? $additionalDocument->document_date->format('d-M-Y') : '-' }}
                    </td>
                </tr>
                <tr>
                    <td><strong>Receive Date:</strong></td>
                    <td>{{ $additionalDocument->receive_date ? $additionalDocument->receive_date->format('d-M-Y') : '-' }}
                    </td>
                </tr>
                <tr>
                    <td><strong>Created Date:</strong></td>
                    <td>{{ $additionalDocument->created_at->format('d-M-Y H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Created By:</strong></td>
                    <td>
                        @if ($additionalDocument->creator)
                            {{ $additionalDocument->creator->name }}
                            @if ($additionalDocument->creator->department)
                                <br><small
                                    class="text-muted">({{ $additionalDocument->creator->department->name }})</small>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @if ($additionalDocument->remarks)
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="text-muted">Remarks</h6>
                <p class="text-muted">{{ $additionalDocument->remarks }}</p>
            </div>
        </div>
    @endif

    <div class="row mt-3">
        <div class="col-12 text-right">
            <a href="{{ route('additional-documents.edit', $additionalDocument->id) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('additional-documents.show', $additionalDocument->id) }}" class="btn btn-info btn-sm"
                target="_blank">
                <i class="fas fa-external-link-alt"></i> Full View
            </a>
        </div>
    </div>
@else
    <div class="alert alert-danger">
        Document not found or you don't have permission to view it.
    </div>
@endif
