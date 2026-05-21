@extends('layouts.main')

@section('title_page')
    SAP AP Invoice Preview
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.show', $invoice) }}">Details</a></li>
    <li class="breadcrumb-item active">SAP Preview</li>
@endsection

@section('content')
    <div class="content">
        <div class="container-fluid">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-5">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Invoice Summary</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th>Invoice No</th>
                                    <td>{{ $invoice->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <th>Supplier</th>
                                    <td>{{ $invoice->supplier?->name }} ({{ $invoice->supplier?->sap_code }})</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td>{{ $invoice->formatted_invoice_date }}</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>{{ $invoice->formatted_amount }}</td>
                                </tr>
                                <tr>
                                    <th>PO / GRPO No</th>
                                    <td>{{ $invoice->po_no ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Tax Code</th>
                                    <td>{{ $apPreview['tax_code'] ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <form id="sap-submit-form" action="{{ route('invoices.submit-to-sap', $invoice) }}" method="POST">
                        @csrf

                        <div class="card card-warning card-outline">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">GRPO References (SAP Relationship Map)</h3>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-grpo-row">Add GRPO</button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0" id="grpo-table">
                                        <thead>
                                            <tr>
                                                <th>GRPO No</th>
                                                <th>DocEntry</th>
                                                <th>Line</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="grpo-tbody">
                                            @foreach ($grpoRows as $index => $row)
                                                <tr class="grpo-row" data-index="{{ $index }}">
                                                    <td>
                                                        <input type="text" name="grpo_references[{{ $index }}][grpo_no]"
                                                            class="form-control form-control-sm grpo-no-input"
                                                            value="{{ $row['grpo_no'] }}">
                                                    </td>
                                                    <td>
                                                        <input type="number" name="grpo_references[{{ $index }}][doc_entry]"
                                                            class="form-control form-control-sm doc-entry-input"
                                                            value="{{ $row['doc_entry'] }}" min="1"
                                                            {{ $row['found'] ? '' : 'placeholder=Resolve in SAP' }}>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="grpo_references[{{ $index }}][line]"
                                                            class="form-control form-control-sm line-input"
                                                            value="{{ $row['line'] }}" min="0">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01"
                                                            name="grpo_references[{{ $index }}][amount]"
                                                            class="form-control form-control-sm amount-input"
                                                            value="{{ number_format($row['amount'], 2, '.', '') }}">
                                                    </td>
                                                    <td class="status-cell">
                                                        @if ($row['found'])
                                                            <span class="badge bg-success">Found</span>
                                                        @elseif ($row['error'])
                                                            <span class="badge bg-danger" title="{{ $row['error'] }}">Not found</span>
                                                        @else
                                                            <span class="badge bg-secondary">Optional</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-grpo-row"
                                                            title="Remove row">&times;</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">
                                    Lines with valid DocEntry will link AP Invoice to GRPO in SAP B1 (BaseType 20).
                                    Invoice total: <strong id="invoice-total">{{ number_format($invoice->amount, 2) }}</strong>
                                    — GRPO sum: <strong id="grpo-sum">0.00</strong>
                                    <span id="amount-mismatch" class="text-warning d-none"> (amounts differ)</span>
                                </small>
                            </div>
                        </div>

                        <div class="card card-info card-outline mt-3">
                            <div class="card-header">
                                <h3 class="card-title">AP Invoice Lines Preview</h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Qty</th>
                                                <th>Unit Price</th>
                                                <th>Project</th>
                                                <th>Cost Center</th>
                                                <th>GRPO Link</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lines-preview-body">
                                            @foreach ($apPreview['document_lines'] ?? [] as $line)
                                                <tr>
                                                    <td>{{ $line['ItemCode'] ?? '—' }}</td>
                                                    <td>{{ $line['Quantity'] ?? 1 }}</td>
                                                    <td class="text-right">{{ number_format($line['UnitPrice'] ?? 0, 2) }}</td>
                                                    <td>{{ $line['ProjectCode'] ?? '—' }}</td>
                                                    <td>{{ $line['CostingCode'] ?? '—' }}</td>
                                                    <td>
                                                        @if (! empty($line['BaseEntry']))
                                                            <span class="badge bg-primary">GRPO {{ $line['BaseEntry'] }}</span>
                                                        @else
                                                            <span class="text-muted">Standalone</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                Confirm &amp; Submit to SAP B1
                            </button>
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const invoiceTotal = {{ (float) $invoice->amount }};
            let rowIndex = {{ count($grpoRows) }};

            function recalcSum() {
                let sum = 0;
                document.querySelectorAll('.amount-input').forEach(function(el) {
                    sum += parseFloat(el.value) || 0;
                });
                document.getElementById('grpo-sum').textContent = sum.toFixed(2);
                const mismatch = document.getElementById('amount-mismatch');
                if (Math.abs(sum - invoiceTotal) > 0.01) {
                    mismatch.classList.remove('d-none');
                } else {
                    mismatch.classList.add('d-none');
                }
            }

            function reindexRows() {
                document.querySelectorAll('#grpo-tbody .grpo-row').forEach(function(row, idx) {
                    row.dataset.index = idx;
                    row.querySelectorAll('input').forEach(function(input) {
                        const name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/grpo_references\[\d+\]/,
                                'grpo_references[' + idx + ']'));
                        }
                    });
                });
                rowIndex = document.querySelectorAll('#grpo-tbody .grpo-row').length;
            }

            document.getElementById('add-grpo-row').addEventListener('click', function() {
                const tbody = document.getElementById('grpo-tbody');
                const tr = document.createElement('tr');
                tr.className = 'grpo-row';
                tr.innerHTML = `
                    <td><input type="text" name="grpo_references[${rowIndex}][grpo_no]" class="form-control form-control-sm grpo-no-input"></td>
                    <td><input type="number" name="grpo_references[${rowIndex}][doc_entry]" class="form-control form-control-sm doc-entry-input" min="1"></td>
                    <td><input type="number" name="grpo_references[${rowIndex}][line]" class="form-control form-control-sm line-input" value="0" min="0"></td>
                    <td><input type="number" step="0.01" name="grpo_references[${rowIndex}][amount]" class="form-control form-control-sm amount-input" value="0"></td>
                    <td class="status-cell"><span class="badge bg-secondary">Manual</span></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-grpo-row">&times;</button></td>
                `;
                tbody.appendChild(tr);
                rowIndex++;
                bindRow(tr);
                recalcSum();
            });

            function bindRow(row) {
                row.querySelector('.remove-grpo-row').addEventListener('click', function() {
                    row.remove();
                    reindexRows();
                    recalcSum();
                });
                row.querySelectorAll('.amount-input').forEach(function(el) {
                    el.addEventListener('input', recalcSum);
                });
            }

            document.querySelectorAll('#grpo-tbody .grpo-row').forEach(bindRow);
            recalcSum();

            document.getElementById('sap-submit-form').addEventListener('submit', function(e) {
                const hasPo = @json((bool) $invoice->po_no);
                if (!hasPo) {
                    return;
                }
                const validRows = Array.from(document.querySelectorAll('.doc-entry-input'))
                    .filter(function(el) {
                        return parseInt(el.value, 10) > 0;
                    });
                if (validRows.length === 0) {
                    e.preventDefault();
                    alert('At least one GRPO with a valid DocEntry is required when PO/GRPO number is set.');
                }
            });
        })();
    </script>
@endsection
