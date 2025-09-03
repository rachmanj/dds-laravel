<table class="documents-table">
    <thead>
        <tr>
            <th>NO</th>
            <th>DOCUMENT TYPE</th>
            <th>VENDOR/SUPPLIER</th>
            <th>DOCUMENT NO.</th>
            <th>DATE</th>
            <th class="text-right">AMOUNT</th>
            <th>PO NO</th>
            <th>PROJECT</th>
        </tr>
    </thead>
    <tbody>
        @php
            // For additional document distributions, filter to get only additional documents
            $additionalDocumentDocuments = $distribution->documents->filter(function ($doc) {
                return $doc->document_type === 'App\Models\AdditionalDocument';
            });
        @endphp

        @foreach ($additionalDocumentDocuments as $index => $doc)
            @php $additionalDoc = $doc->document; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>Additional Document</strong></td>
                <td>{{ $additionalDoc->type->type_name ?? 'Additional Document' }}</td>
                <td><strong>{{ $additionalDoc->document_number ?? 'N/A' }}</strong></td>
                <td>{{ $additionalDoc->document_date ? \Carbon\Carbon::parse($additionalDoc->document_date)->setTimezone('Asia/Singapore')->format('d-M-Y') : 'N/A' }}
                </td>
                <td class="text-right">N/A</td>
                <td>{{ $additionalDoc->po_no ?? 'N/A' }}</td>
                <td>{{ $additionalDoc->project ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
