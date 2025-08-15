<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transmittal Advice - {{ $distribution->distribution_number }}</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">

    <style>
        @media print {
            body {
                margin: 0;
                padding: 20px;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-before: always;
            }

            table {
                page-break-inside: avoid;
            }
        }

        .transmittal-header {
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }

        .company-info {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .document-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            color: #333;
        }

        .distribution-number {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            color: #666;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            min-width: 150px;
        }

        .info-value {
            flex: 1;
        }

        .documents-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .documents-table th,
        .documents-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .documents-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .additional-docs-header {
            background-color: #e9ecef;
            font-weight: bold;
            font-style: italic;
        }

        .signature-section {
            margin-top: 40px;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .signature-box {
            text-align: center;
            width: 30%;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-bottom: 10px;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .signature-title {
            font-size: 12px;
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft {
            background-color: #6c757d;
            color: white;
        }

        .status-verified_by_sender {
            background-color: #17a2b8;
            color: white;
        }

        .status-sent {
            background-color: #007bff;
            color: white;
        }

        .status-received {
            background-color: #ffc107;
            color: #212529;
        }

        .status-verified_by_receiver {
            background-color: #28a745;
            color: white;
        }

        .status-completed {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Main content -->
        <section class="invoice">
            <!-- title row -->
            <div class="row">
                <div class="col-12">
                    <table class="table">
                        <tr>
                            <td rowspan="2">
                                <div class="company-info">PT Arkananta Apta Pratista</div>
                            </td>
                            <td rowspan="2">
                                <div class="document-title">Transmittal Advice</div>
                                <div class="distribution-number">No: {{ $distribution->distribution_number }}</div>
                            </td>
                            <td class="text-right">ARKA/ACC/IV/01.01</td>
                        </tr>
                        <tr>
                            <td>{{ $distribution->created_at->format('d-M-Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Distribution Information -->
            <div class="row">
                <div class="col-6">
                    <div class="info-section">
                        <div class="info-label">From:</div>
                        <div class="info-value">
                            <strong>{{ $distribution->originDepartment->name }}</strong><br>
                            <strong>{{ $distribution->originDepartment->location_code }}</strong><br>
                            {{ $distribution->originDepartment->address ?? 'Address not specified' }}
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="info-section">
                        <div class="info-label">To:</div>
                        <div class="info-value">
                            <strong>{{ $distribution->destinationDepartment->name }}</strong><br>
                            <strong>{{ $distribution->destinationDepartment->location_code }}</strong><br>
                            {{ $distribution->destinationDepartment->address ?? 'Address not specified' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Distribution Details -->
            <div class="row">
                <div class="col-12">
                    <div class="info-section">
                        <div class="info-row">
                            <div class="info-label">Distribution Type:</div>
                            <div class="info-value">{{ $distribution->type->name }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Status:</div>
                            <div class="info-value">
                                <span class="status-badge status-{{ $distribution->status }}">
                                    {{ ucwords(str_replace('_', ' ', $distribution->status)) }}
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Created By:</div>
                            <div class="info-value">{{ $distribution->creator->name }} on
                                {{ $distribution->created_at->format('d-M-Y H:i') }}</div>
                        </div>
                        @if ($distribution->notes)
                            <div class="info-row">
                                <div class="info-label">Notes:</div>
                                <div class="info-value">{{ $distribution->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="row">
                <div class="col-12 table-responsive">
                    <table class="documents-table">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>DOCUMENT TYPE</th>
                                <th>DOCUMENT NO.</th>
                                <th>DATE</th>
                                <th>AMOUNT</th>
                                <th>VENDOR/SUPPLIER</th>
                                <th>PO NO</th>
                                <th>PROJECT</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($distribution->documents as $index => $doc)
                                @if ($doc->document_type === 'App\Models\Invoice')
                                    @php $invoice = $doc->document; @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><strong>Invoice</strong></td>
                                        <td><strong>{{ $invoice->inv_no }}</strong></td>
                                        <td>{{ $invoice->inv_date ? date('d-M-Y', strtotime($invoice->inv_date)) : 'N/A' }}
                                        </td>
                                        <td class="text-right">
                                            @if ($invoice->inv_currency && $invoice->inv_nominal)
                                                {{ $invoice->inv_currency }}
                                                {{ number_format($invoice->inv_nominal, 0) }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $invoice->vendor->vendor_name ?? 'N/A' }}</td>
                                        <td>{{ $invoice->po_no ?? 'N/A' }}</td>
                                        <td>{{ $invoice->project->project_code ?? 'N/A' }}</td>
                                        <td>
                                            <span
                                                class="status-badge status-{{ $doc->verification_status ?? 'pending' }}">
                                                {{ ucwords(str_replace('_', ' ', $doc->verification_status ?? 'pending')) }}
                                            </span>
                                        </td>
                                    </tr>

                                    @if ($invoice->additionalDocuments && $invoice->additionalDocuments->count() > 0)
                                        <tr class="additional-docs-header">
                                            <td colspan="9">
                                                <strong>Additional Documents for Invoice
                                                    {{ $invoice->inv_no }}:</strong>
                                            </td>
                                        </tr>
                                        @foreach ($invoice->additionalDocuments as $addDoc)
                                            <tr>
                                                <td></td>
                                                <td colspan="2">
                                                    {{ $addDoc->doctype->docdesc ?? 'Additional Document' }}</td>
                                                <td colspan="2">{{ $addDoc->document_no ?? 'N/A' }}</td>
                                                <td colspan="2"></td>
                                                <td></td>
                                                <td>
                                                    <span
                                                        class="status-badge status-{{ $addDoc->distribution_status ?? 'available' }}">
                                                        {{ ucwords(str_replace('_', ' ', $addDoc->distribution_status ?? 'available')) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @else
                                    @php $additionalDoc = $doc->document; @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><strong>Additional Document</strong></td>
                                        <td><strong>{{ $additionalDoc->document_no ?? 'N/A' }}</strong></td>
                                        <td>{{ $additionalDoc->created_at ? date('d-M-Y', strtotime($additionalDoc->created_at)) : 'N/A' }}
                                        </td>
                                        <td colspan="3">N/A</td>
                                        <td>{{ $additionalDoc->project->project_code ?? 'N/A' }}</td>
                                        <td>
                                            <span
                                                class="status-badge status-{{ $doc->verification_status ?? 'pending' }}">
                                                {{ ucwords(str_replace('_', ' ', $doc->verification_status ?? 'pending')) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Workflow Status Information -->
            @if ($distribution->status !== 'draft')
                <div class="row">
                    <div class="col-12">
                        <div class="info-section">
                            <h5><strong>Workflow Status:</strong></h5>
                            @if ($distribution->sender_verified_at)
                                <div class="info-row">
                                    <div class="info-label">Sender Verified:</div>
                                    <div class="info-value">
                                        {{ $distribution->senderVerifier->name ?? 'N/A' }} on
                                        {{ $distribution->sender_verified_at->format('d-M-Y H:i') }}
                                        @if ($distribution->sender_verification_notes)
                                            <br><small><em>Notes:
                                                    {{ $distribution->sender_verification_notes }}</em></small>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @if ($distribution->sent_at)
                                <div class="info-row">
                                    <div class="info-label">Sent:</div>
                                    <div class="info-value">{{ $distribution->sent_at->format('d-M-Y H:i') }}</div>
                                </div>
                            @endif
                            @if ($distribution->received_at)
                                <div class="info-row">
                                    <div class="info-label">Received:</div>
                                    <div class="info-value">{{ $distribution->received_at->format('d-M-Y H:i') }}</div>
                                </div>
                            @endif
                            @if ($distribution->receiver_verified_at)
                                <div class="info-row">
                                    <div class="info-label">Receiver Verified:</div>
                                    <div class="info-value">
                                        {{ $distribution->receiverVerifier->name ?? 'N/A' }} on
                                        {{ $distribution->receiver_verified_at->format('d-M-Y H:i') }}
                                        @if ($distribution->receiver_verification_notes)
                                            <br><small><em>Notes:
                                                    {{ $distribution->receiver_verification_notes }}</em></small>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Signature Section -->
            <div class="row">
                <div class="col-12">
                    <div class="signature-section">
                        <div class="signature-row">
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">Prepared by</div>
                                <div class="signature-title">{{ $distribution->creator->name }}</div>
                            </div>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">Verified by</div>
                                <div class="signature-title">
                                    @if ($distribution->senderVerifier)
                                        {{ $distribution->senderVerifier->name }}
                                    @else
                                        (Signature)
                                    @endif
                                </div>
                            </div>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">Received by</div>
                                <div class="signature-title">
                                    @if ($distribution->receiverVerifier)
                                        {{ $distribution->receiverVerifier->name }}
                                    @else
                                        (Signature)
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Page specific script -->
    <script>
        window.addEventListener("load", function() {
            // Auto-trigger print dialog
            window.print();
        });
    </script>
</body>

</html>
