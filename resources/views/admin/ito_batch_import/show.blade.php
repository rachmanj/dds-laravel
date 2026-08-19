@extends('layouts.main')

@section('title_page')
    ITO Batch #{{ $batch->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('ito-batch-import.index') }}">ITO Batch Import</a></li>
    <li class="breadcrumb-item active">Batch #{{ $batch->id }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('success') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Batch Details</h3>
                    <div class="card-tools">
                        @if ($batch->reviewNeededCount() > 0)
                            <a href="{{ route('ito-batch-import.review', $batch) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-tasks"></i> Review Queue ({{ $batch->reviewNeededCount() }})
                            </a>
                        @endif
                        <a href="{{ route('ito-batch-import.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Filename:</strong> {{ $batch->filename }}</div>
                        <div class="col-md-2"><strong>Pages:</strong> {{ $batch->total_pages }}</div>
                        <div class="col-md-2">
                            <strong>Status:</strong>
                            @php
                                $statusClass = match ($batch->status) {
                                    'processed' => 'success',
                                    'partial' => 'warning',
                                    'processing' => 'info',
                                    'failed' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge badge-{{ $statusClass }}">{{ ucfirst($batch->status) }}</span>
                        </div>
                        <div class="col-md-2"><strong>Matched:</strong> {{ $batch->matchedCount() }}</div>
                        <div class="col-md-3"><strong>Review needed:</strong> {{ $batch->reviewNeededCount() }}</div>
                    </div>

                    @if (in_array($batch->status, ['pending', 'processing']))
                        <div class="alert alert-info" id="processing-alert">
                            <i class="fas fa-spinner fa-spin"></i> Processing batch... this page will refresh automatically.
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Extracted ITO No.</th>
                                    <th>Confidence</th>
                                    <th>Status</th>
                                    <th>Matched Document</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($batch->items->sortBy('page_number') as $item)
                                    <tr>
                                        <td>{{ $item->page_number }}</td>
                                        <td>{{ $item->extracted_ito_no ?? '-' }}</td>
                                        <td>{{ $item->confidence !== null ? number_format((float) $item->confidence, 2) : '-' }}</td>
                                        <td>
                                            @php
                                                $itemClass = match ($item->status) {
                                                    'matched' => 'success',
                                                    'skipped' => 'secondary',
                                                    'ambiguous' => 'warning',
                                                    'not_found', 'low_confidence' => 'danger',
                                                    default => 'info',
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $itemClass }}">{{ str_replace('_', ' ', ucfirst($item->status)) }}</span>
                                        </td>
                                        <td>
                                            @if ($item->matchedDocument)
                                                <a href="{{ route('additional-documents.show', $item->matchedDocument) }}">
                                                    {{ $item->matchedDocument->document_number }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No items yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    @if (in_array($batch->status, ['pending', 'processing']))
        <script>
            setTimeout(function() {
                window.location.reload();
            }, 5000);
        </script>
    @endif
@endsection
