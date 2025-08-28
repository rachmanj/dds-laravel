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

        .documents-table .text-right {
            text-align: right !important;
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

        .additional-doc-row {
            background-color: #f8f9fa;
            font-size: 0.9em;
        }

        .additional-doc-row td {
            border-top: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
        }

        .additional-doc-row td:first-child {
            border-left: 1px solid #dee2e6;
        }

        .additional-doc-row td:last-child {
            border-right: 1px solid #dee2e6;
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

            <!-- Documents Table -->
            <div class="row">
                <div class="col-12 table-responsive">
                    @if ($distribution->document_type === 'invoice')
                        @include('distributions.partials.invoice-table')
                    @else
                        @include('distributions.partials.additional-document-table')
                    @endif
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
                                        {{ $distribution->local_sender_verified_at->format('d-M-Y H:i') }}
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
                                    <div class="info-value">{{ $distribution->local_sent_at->format('d-M-Y H:i') }}
                                    </div>
                                </div>
                            @endif
                            @if ($distribution->received_at)
                                <div class="info-row">
                                    <div class="info-label">Received:</div>
                                    <div class="info-value">{{ $distribution->local_received_at->format('d-M-Y H:i') }}
                                    </div>
                                </div>
                            @endif
                            @if ($distribution->receiver_verified_at)
                                <div class="info-row">
                                    <div class="info-label">Receiver Verified:</div>
                                    <div class="info-value">
                                        {{ $distribution->local_receiver_verified_at->format('d-M-Y H:i') }}
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
