@extends('layouts.main')

@section('title_page')
    Review Batch #{{ $batch->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('ito-batch-import.index') }}">ITO Batch Import</a></li>
    <li class="breadcrumb-item"><a href="{{ route('ito-batch-import.show', $batch) }}">Batch #{{ $batch->id }}</a></li>
    <li class="breadcrumb-item active">Review</li>
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
                    <h3 class="card-title">Review Queue — {{ $items->count() }} item(s)</h3>
                    <div class="card-tools">
                        <a href="{{ route('ito-batch-import.show', $batch) }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Batch
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse ($items as $item)
                        <div class="card card-outline card-warning mb-4">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Page {{ $item->page_number }}
                                    <span class="badge badge-{{ $item->status === 'ambiguous' ? 'warning' : 'danger' }} ml-2">
                                        {{ str_replace('_', ' ', ucfirst($item->status)) }}
                                    </span>
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        @if ($item->attached_path)
                                            <p><strong>Preview:</strong></p>
                                            <embed src="{{ asset('storage/'.$item->attached_path) }}" type="application/pdf" width="100%" height="300px">
                                        @endif
                                    </div>
                                    <div class="col-md-8">
                                        <p><strong>Extracted ITO No.:</strong> {{ $item->extracted_ito_no ?? '(none)' }}</p>
                                        <p><strong>Confidence:</strong> {{ $item->confidence !== null ? number_format((float) $item->confidence, 2) : '-' }}</p>

                                        <hr>

                                        <h5>Assign to Existing ITO Record</h5>
                                        <form action="{{ route('ito-batch-import.items.assign', $item) }}" method="POST" class="mb-4">
                                            @csrf
                                            <div class="form-row">
                                                <div class="col-md-8">
                                                    <select name="document_id" class="form-control" required>
                                                        <option value="">— Select ITO document —</option>
                                                        @foreach ($itoDocuments as $doc)
                                                            <option value="{{ $doc->id }}" @selected($item->extracted_ito_no && $doc->document_number === $item->extracted_ito_no)>
                                                                {{ $doc->document_number }}
                                                                @if ($doc->po_no) (PO: {{ $doc->po_no }}) @endif
                                                                @if ($doc->attachment) [has attachment] @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="submit" class="btn btn-primary btn-block">
                                                        <i class="fas fa-link"></i> Assign to Record
                                                    </button>
                                                </div>
                                            </div>
                                        </form>

                                        <h5>Create New ITO Record &amp; Attach</h5>
                                        <form action="{{ route('ito-batch-import.items.create', $item) }}" method="POST" class="mb-3">
                                            @csrf
                                            <div class="form-row">
                                                <div class="col-md-4 form-group">
                                                    <label>ITO Number</label>
                                                    <input type="text" name="document_number" class="form-control" value="{{ $item->extracted_ito_no }}" required>
                                                </div>
                                                <div class="col-md-4 form-group">
                                                    <label>Document Date</label>
                                                    <input type="date" name="document_date" class="form-control" value="{{ now()->toDateString() }}">
                                                </div>
                                                <div class="col-md-4 form-group">
                                                    <label>PO No.</label>
                                                    <input type="text" name="po_no" class="form-control">
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-plus"></i> Create &amp; Attach
                                            </button>
                                        </form>

                                        <form action="{{ route('ito-batch-import.items.skip', $item) }}" method="POST" onsubmit="return confirm('Skip this page?');">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary">
                                                <i class="fas fa-forward"></i> Skip
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check"></i> No items need review.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
