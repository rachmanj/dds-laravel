<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document Transmittal Advice - {{ $distribution->distribution_number }}</title>

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
                padding: 10px;
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

            .row {
                margin-bottom: 10px;
            }

            .col-12,
            .col-6 {
                padding: 0 5px;
            }

            .documents-table th,
            .documents-table td {
                padding: 4px;
                font-size: 11px;
            }

            .info-section {
                margin-bottom: 10px;
            }

            .info-row {
                margin-bottom: 5px;
            }
        }

        .transmittal-header {
            border-bottom: 2px solid #333;
            margin-bottom: 10px;
            padding-bottom: 10px;
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
            margin: 10px 0;
            color: #333;
        }

        .distribution-number {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            color: #666;
        }

        .info-section {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
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
            margin: 10px 0;
        }

        .documents-table th,
        .documents-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-size: 11px;
        }

        .documents-table .text-center {
            text-align: center !important;
        }

        .documents-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .table-responsive {
            margin: 0;
            padding: 0;
        }

        .row {
            margin-bottom: 10px;
        }

        .col-12,
        .col-6 {
            padding: 0 5px;
        }

        .signature-section {
            margin-top: 20px;
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
                                <div class="document-title">Document Transmittal Advice</div>
                                <div class="distribution-number">No: {{ $distribution->distribution_number }}</div>
                            </td>
                            <td class="text-right">ARKA/ACC/IV/01.01</td>
                        </tr>
                        <tr>
                            <td>{{ $distribution->local_created_at->format('d-M-Y') }}</td>
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
                                {{ $distribution->local_created_at->format('d-M-Y H:i') }}</div>
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

            <!-- Additional Documents Table -->
            <div class="row">
                <div class="col-12 table-responsive">
                    <table class="documents-table">
                        <thead>
                            <tr>
                                <th class="text-right">NO.</th>
                                <th>DOC NO.</th>
                                <th>DOC DATE</th>
                                <th>DOC TYPE</th>
                                <th>PO NO</th>
                                <th>INV NO</th>
                                <th>PROJECT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $additionalDocumentDocuments = $distribution->documents->filter(function ($doc) {
                                    return $doc->document_type === 'App\Models\AdditionalDocument';
                                });
                            @endphp

                            @foreach ($additionalDocumentDocuments as $index => $doc)
                                @php
                                    $additionalDoc = $doc->document;
                                    $invoiceNumbers =
                                        $additionalDoc->invoices && $additionalDoc->invoices->count() > 0
                                            ? $additionalDoc->invoices->pluck('invoice_number')->implode(', ')
                                            : '-';
                                @endphp
                                <tr>
                                    <td class="text-right">{{ $index + 1 }}</td>
                                    <td><strong>{{ $additionalDoc->document_number ?? 'N/A' }}</strong></td>
                                    <td class="text-center">
                                        {{ $additionalDoc->document_date ? \Carbon\Carbon::parse($additionalDoc->document_date)->setTimezone('Asia/Singapore')->format('d-M-Y') : '-' }}
                                    </td>
                                    <td>{{ $additionalDoc->type->type_name ?? 'N/A' }}</td>
                                    <td>{{ $additionalDoc->po_no ?? '-' }}</td>
                                    <td><small>{{ $invoiceNumbers }}</small></td>
                                    <td>{{ $additionalDoc->project ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

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

    <!-- Floating Print Button -->
    <div class="floating-print-btn">
        <button type="button" class="btn btn-primary btn-lg" onclick="window.print()">
            <i class="fas fa-print"></i>
            <span class="btn-text">Print Now</span>
        </button>
    </div>

    <!-- Page specific script -->
    <script>
        window.addEventListener("load", function() {
            // Auto-trigger print dialog
            // window.print();
        });
    </script>

    <style>
        .floating-print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .floating-print-btn .btn {
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
            padding: 15px 20px;
        }

        .floating-print-btn .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        .floating-print-btn .btn-text {
            margin-left: 8px;
        }

        @media (max-width: 768px) {
            .floating-print-btn {
                bottom: 20px;
                right: 20px;
            }

            .floating-print-btn .btn {
                padding: 12px 16px;
            }

            .floating-print-btn .btn-text {
                display: none;
            }
        }

        @media print {
            .floating-print-btn {
                display: none !important;
            }
        }
    </style>
</body>

</html>
