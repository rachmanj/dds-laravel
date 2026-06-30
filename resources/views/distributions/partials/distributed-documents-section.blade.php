@php
    $invoiceDocuments = $distribution->documents->filter(function ($doc) {
        return $doc->document_type === 'App\Models\Invoice';
    });

    $additionalDocumentDocuments = $distribution->documents->filter(function ($doc) {
        return $doc->document_type === 'App\Models\AdditionalDocument';
    });

    $attachedAdditionalDocs = collect();
    foreach ($invoiceDocuments as $invoiceDoc) {
        $invoice = $invoiceDoc->document;
        if ($invoice && $invoice->additionalDocuments && $invoice->additionalDocuments->count() > 0) {
            foreach ($invoice->additionalDocuments as $addDoc) {
                $distDoc = $additionalDocumentDocuments->first(function ($doc) use ($addDoc) {
                    return $doc->document_id === $addDoc->id;
                });
                if ($distDoc) {
                    $attachedAdditionalDocs->push([
                        'invoice_id' => $invoice->id,
                        'distribution_doc' => $distDoc,
                        'additional_doc' => $addDoc,
                    ]);
                }
            }
        }
    }

    $standaloneAdditionalDocs = $additionalDocumentDocuments->filter(function ($doc) use ($attachedAdditionalDocs) {
        return ! $attachedAdditionalDocs->contains('distribution_doc.id', $doc->id);
    });

    $resolveDocStatusKey = function ($distDoc) {
        if ($distDoc->skip_verification) {
            return 'not_included';
        }
        if ($distDoc->has_discrepancies) {
            return 'discrepancy';
        }
        if ($distDoc->sender_verified && $distDoc->receiver_verified) {
            return 'verified';
        }

        return 'pending';
    };

    $documentGroups = collect();
    $groupIndex = 0;

    foreach ($invoiceDocuments as $doc) {
        $invoice = $doc->document;
        $children = $attachedAdditionalDocs->where('invoice_id', $invoice->id)->values();
        $documentGroups->push([
            'index' => $groupIndex++,
            'type' => 'invoice',
            'doc' => $doc,
            'invoice' => $invoice,
            'children' => $children,
        ]);
    }

    foreach ($standaloneAdditionalDocs as $doc) {
        $documentGroups->push([
            'index' => $groupIndex++,
            'type' => 'standalone',
            'doc' => $doc,
            'children' => collect(),
        ]);
    }
@endphp

@if ($distribution->documents->count() === 0)
    <div class="text-center py-4 text-muted">
        <i class="fas fa-file-alt fa-2x mb-2"></i>
        <p class="mb-0">No documents found in this distribution</p>
    </div>
@else
    <div class="doc-toolbar mb-3">
        <div class="row align-items-center">
            <div class="col-lg-4 mb-2 mb-lg-0">
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" class="form-control" id="docSearch"
                        placeholder="Search document, supplier, invoice no...">
                </div>
            </div>
            <div class="col-lg-3 mb-2 mb-lg-0">
                <select class="form-control form-control-sm" id="docStatusFilter">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="verified">Verified</option>
                    <option value="discrepancy">Has Discrepancies</option>
                    <option value="not_included">Not included</option>
                </select>
            </div>
            <div class="col-lg-5 text-lg-right">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="expandAllDocs">
                    <i class="fas fa-expand-alt"></i> Expand all
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="collapseAllDocs">
                    <i class="fas fa-compress-alt"></i> Collapse all
                </button>
                <span class="text-muted ml-2" id="docResultsCount"></span>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="distributedDocumentsTable">
            <thead class="thead-light">
                <tr>
                    <th width="20%">Document</th>
                    <th width="15%">Supplier</th>
                    <th width="12%">Invoice No</th>
                    <th width="13%">Type</th>
                    <th width="15%">Sender Status</th>
                    <th width="15%">Receiver Status</th>
                    <th width="10%">Overall Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($documentGroups as $group)
                    @if ($group['type'] === 'invoice')
                        @php
                            $doc = $group['doc'];
                            $invoice = $group['invoice'];
                            $childCount = $group['children']->count();
                            $parentSearch = strtolower(
                                implode(
                                    ' ',
                                    array_filter([
                                        $invoice->invoice_number ?? '',
                                        $invoice->type->type_name ?? '',
                                        $invoice->supplier->name ?? '',
                                        $invoice->po_no ?? '',
                                    ]),
                                ),
                            );
                        @endphp
                        <tr class="doc-group-row" data-group="{{ $group['index'] }}"
                            data-search="{{ $parentSearch }}" data-status="{{ $resolveDocStatusKey($doc) }}"
                            data-expanded="false">
                            <td>
                                <div class="d-flex align-items-center">
                                    @if ($childCount > 0)
                                        <button type="button"
                                            class="btn btn-link btn-sm p-0 mr-2 doc-toggle text-secondary"
                                            aria-label="Toggle additional documents">
                                            <i class="fas fa-chevron-right doc-toggle-icon"></i>
                                        </button>
                                    @else
                                        <span class="doc-toggle-spacer mr-2"></span>
                                    @endif
                                    <div class="document-icon mr-2">
                                        <i class="fas fa-file-invoice text-primary"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $invoice->invoice_number ?? 'N/A' }}</strong>
                                        @if ($childCount > 0)
                                            <span class="badge badge-light border ml-1">{{ $childCount + 1 }}
                                                docs</span>
                                        @endif
                                        <br>
                                        <small class="text-muted">{{ $invoice->type->type_name ?? 'N/A' }}</small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i>
                                            {{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : 'N/A' }}
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <small class="text-muted">{{ $invoice->supplier->name ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $invoice->invoice_number ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-primary">Invoice</span>
                            </td>
                            @include('distributions.partials.document-status-cells', ['doc' => $doc])
                        </tr>

                        @foreach ($group['children'] as $attachedDoc)
                            @php
                                $addDoc = $attachedDoc['additional_doc'];
                                $distDoc = $attachedDoc['distribution_doc'];
                                $childSearch = strtolower(
                                    implode(
                                        ' ',
                                        array_filter([
                                            $addDoc->document_number ?? '',
                                            $addDoc->type->type_name ?? '',
                                            $invoice->invoice_number ?? '',
                                        ]),
                                    ),
                                );
                            @endphp
                            <tr class="attached-document-row doc-child-row d-none"
                                data-parent="{{ $group['index'] }}" data-search="{{ $childSearch }}"
                                data-status="{{ $resolveDocStatusKey($distDoc) }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="document-icon mr-2">
                                            <i class="fas fa-file-alt text-info"></i>
                                        </div>
                                        <div>
                                            <strong>{{ $addDoc->document_number ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $addDoc->type->type_name ?? 'N/A' }}</small>
                                            @if ($distDoc->skip_verification)
                                                <br>
                                                <span class="badge badge-secondary"
                                                    title="Document was not in origin department at creation">Out of
                                                    origin dept</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><small class="text-muted">-</small></td>
                                <td>
                                    <small class="text-muted">{{ $invoice->invoice_number ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-info">Additional Document</span>
                                </td>
                                @include('distributions.partials.document-status-cells', ['doc' => $distDoc])
                            </tr>
                        @endforeach
                    @else
                        @php
                            $doc = $group['doc'];
                            $additionalDoc = $doc->document;
                            $invoiceNumbers =
                                $additionalDoc &&
                                $additionalDoc->invoices &&
                                $additionalDoc->invoices->count() > 0
                                    ? $additionalDoc->invoices->pluck('invoice_number')->implode(', ')
                                    : '-';
                            $parentSearch = strtolower(
                                implode(
                                    ' ',
                                    array_filter([
                                        $additionalDoc->document_number ?? '',
                                        $additionalDoc->type->type_name ?? '',
                                        $invoiceNumbers !== '-' ? $invoiceNumbers : '',
                                    ]),
                                ),
                            );
                            $standaloneOutOfLocation = $invoiceDocuments->count() === 0;
                        @endphp
                        <tr class="doc-group-row" data-group="{{ $group['index'] }}"
                            data-search="{{ $parentSearch }}" data-status="{{ $resolveDocStatusKey($doc) }}"
                            data-expanded="false">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="doc-toggle-spacer mr-2"></span>
                                    <div class="document-icon mr-2">
                                        <i class="fas fa-file-alt text-info"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $additionalDoc->document_number ?? 'N/A' }}</strong>
                                        <br>
                                        <small
                                            class="text-muted">{{ $additionalDoc && $additionalDoc->type ? $additionalDoc->type->type_name : 'N/A' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><small class="text-muted">-</small></td>
                            <td><small class="text-muted">{{ $invoiceNumbers }}</small></td>
                            <td>
                                <span class="badge badge-info">Additional Document</span>
                            </td>
                            @include('distributions.partials.document-status-cells', [
                                'doc' => $doc,
                                'standaloneOutOfLocation' => $standaloneOutOfLocation && $doc->skip_verification,
                            ])
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <nav id="docPagination" class="mt-3" aria-label="Documents pagination"></nav>
@endif
