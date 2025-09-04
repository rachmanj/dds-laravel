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
            // Get only the invoice documents from the distribution
            $invoiceDocuments = $distribution->documents->filter(function ($doc) {
                return $doc->document_type === 'App\Models\Invoice';
            });
        @endphp

        @foreach ($invoiceDocuments as $index => $doc)
            @php $invoice = $doc->document; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>Invoice</strong></td>
                <td>{{ $invoice->supplier->name ?? 'N/A' }}</td>
                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                <td>{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->setTimezone('Asia/Singapore')->format('d-M-Y') : 'N/A' }}
                </td>
                <td class="text-right">
                    @if ($invoice->currency && $invoice->amount)
                        {{ $invoice->currency }}
                        {{ number_format($invoice->amount, 0) }}
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $invoice->po_no ?? 'N/A' }}</td>
                <td>{{ $invoice->invoice_project ?? 'N/A' }}</td>
            </tr>

            {{-- Show additional documents attached to this invoice --}}
            @if ($invoice->additionalDocuments && $invoice->additionalDocuments->count() > 0)
                @foreach ($invoice->additionalDocuments as $addDoc)
                    <tr class="additional-doc-row">
                        <td></td>
                        <td style="padding-left: 20px;">{{ $addDoc->type->type_name ?? 'Additional Document' }}</td>
                        <td></td>
                        <td>{{ $addDoc->document_number ?? 'N/A' }}</td>
                        <td>{{ $addDoc->document_date ? \Carbon\Carbon::parse($addDoc->document_date)->setTimezone('Asia/Singapore')->format('d-M-Y') : 'N/A' }}
                        </td>
                        <td class="text-right"></td>
                        <td>{{ $addDoc->po_no ?? 'N/A' }}</td>
                        <td>{{ $addDoc->project ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            @endif
        @endforeach
    </tbody>
</table>
