@php
    $invoices = $documentsForEdit['invoices'] ?? ($documentType === 'invoice' ? $documentsForEdit : []);
    $standaloneAdditionalDocuments = $documentsForEdit['standalone_additional_documents'] ?? ($documentType === 'additional_document' ? $documentsForEdit : []);
    $hasDocuments = count($invoices) > 0 || count($standaloneAdditionalDocuments) > 0;
@endphp

@if (! $hasDocuments)
    <div class="alert alert-warning mb-0" id="noDocumentsAlert">
        <i class="fas fa-exclamation-triangle"></i>
        No documents attached to this distribution yet.
    </div>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="currentDocumentsTable">
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Type</th>
                    <th>Details</th>
                    <th width="56">Actions</th>
                </tr>
            </thead>
            <tbody>
                @if ($documentType === 'invoice')
                    @foreach ($invoices as $document)
                        <tr data-distribution-document-id="{{ $document['id'] }}">
                            <td><strong>{{ $document['number'] }}</strong></td>
                            <td>Invoice</td>
                            <td>{{ $document['details'] }}</td>
                            <td>
                                <button type="button"
                                    class="btn btn-outline-danger btn-sm remove-document-btn px-2"
                                    data-distribution-document-id="{{ $document['id'] }}"
                                    data-document-number="{{ $document['number'] }}"
                                    title="Remove {{ $document['number'] }}"
                                    aria-label="Remove {{ $document['number'] }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @foreach ($document['additional_documents'] ?? [] as $additionalDocument)
                            <tr class="table-secondary" data-distribution-document-id="{{ $additionalDocument['id'] }}">
                                <td class="pl-4">
                                    <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>
                                    <strong>{{ $additionalDocument['number'] }}</strong>
                                </td>
                                <td>Additional Document</td>
                                <td>{{ $additionalDocument['details'] }}</td>
                                <td>
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm remove-document-btn px-2"
                                        data-distribution-document-id="{{ $additionalDocument['id'] }}"
                                        data-document-number="{{ $additionalDocument['number'] }}"
                                        title="Remove {{ $additionalDocument['number'] }}"
                                        aria-label="Remove {{ $additionalDocument['number'] }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    @if (count($standaloneAdditionalDocuments) > 0)
                        <tr class="table-active">
                            <td colspan="4">
                                <strong><i class="fas fa-file-alt"></i> Other Additional Documents</strong>
                                <small class="text-muted ml-2">Not linked to a selected invoice</small>
                            </td>
                        </tr>
                        @foreach ($standaloneAdditionalDocuments as $document)
                            <tr data-distribution-document-id="{{ $document['id'] }}">
                                <td><strong>{{ $document['number'] }}</strong></td>
                                <td>Additional Document</td>
                                <td>{{ $document['details'] }}</td>
                                <td>
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm remove-document-btn px-2"
                                        data-distribution-document-id="{{ $document['id'] }}"
                                        data-document-number="{{ $document['number'] }}"
                                        title="Remove {{ $document['number'] }}"
                                        aria-label="Remove {{ $document['number'] }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @else
                    @foreach ($standaloneAdditionalDocuments as $document)
                        <tr data-distribution-document-id="{{ $document['id'] }}">
                            <td><strong>{{ $document['number'] }}</strong></td>
                            <td>Additional Document</td>
                            <td>{{ $document['details'] }}</td>
                            <td>
                                <button type="button"
                                    class="btn btn-outline-danger btn-sm remove-document-btn px-2"
                                    data-distribution-document-id="{{ $document['id'] }}"
                                    data-document-number="{{ $document['number'] }}"
                                    title="Remove {{ $document['number'] }}"
                                    aria-label="Remove {{ $document['number'] }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
@endif
