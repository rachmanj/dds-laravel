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
                                    <th>Submitted By</th>
                                    <td>{{ $apPreview['submitted_by_name'] ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td>{{ $invoice->formatted_invoice_date }}</td>
                                </tr>
                                <tr>
                                    <th>Posting Date</th>
                                    <td>{{ $apPreview['posting_date'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Document Date</th>
                                    <td>{{ $apPreview['document_date'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Faktur No</th>
                                    <td>{{ $apPreview['faktur_no'] ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Faktur Date</th>
                                    <td>{{ $apPreview['faktur_date'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>{{ $invoice->formatted_amount }}</td>
                                </tr>
                                <tr>
                                    <th>PO No</th>
                                    <td>{{ $invoice->po_no ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Tax Code</th>
                                    <td>{{ $apPreview['tax_code'] ?? '—' }}</td>
                                </tr>
                                @if (!empty($apPreview['is_consignment']) || filled($apPreview['gl_account'] ?? null))
                                    <tr>
                                        <th>G/L Account</th>
                                        <td>{{ $apPreview['gl_account'] ?: '—' }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Mode</th>
                                    <td>
                                        @if ($isStandalone)
                                            <span class="badge bg-secondary">Standalone (no GRPO)</span>
                                        @else
                                            <span class="badge bg-primary">GRPO-based (BaseType 20)</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <form id="sap-submit-form" action="{{ route('invoices.submit-to-sap', $invoice) }}" method="POST">
                        @csrf

                        @if ($isStandalone)
                            <div class="alert alert-info">
                                No PO number on this invoice. It will post as a <strong>standalone AP Invoice</strong>
                                (single service line, no GRPO base document / relationship map link).
                            </div>
                        @else
                            <div class="card card-warning card-outline">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h3 class="card-title mb-0">GRPO Lines (SAP Relationship Map)</h3>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-grpo-row">Add
                                        line</button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0" id="grpo-table">
                                            <thead>
                                                <tr>
                                                    <th>GRPO No</th>
                                                    <th>DocEntry</th>
                                                    <th>Line</th>
                                                    <th>Item</th>
                                                    <th>Qty</th>
                                                    <th>Unit Price</th>
                                                    <th>Line Total</th>
                                                    <th>Status</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="grpo-tbody">
                                                @forelse ($grpoRows as $index => $row)
                                                    <tr class="grpo-row" data-index="{{ $index }}">
                                                        <td>
                                                            <input type="text"
                                                                name="grpo_references[{{ $index }}][grpo_no]"
                                                                class="form-control form-control-sm grpo-no-input"
                                                                value="{{ $row['grpo_no'] }}">
                                                        </td>
                                                        <td>
                                                            <input type="number"
                                                                name="grpo_references[{{ $index }}][doc_entry]"
                                                                class="form-control form-control-sm doc-entry-input"
                                                                value="{{ $row['doc_entry'] }}" min="1"
                                                                {{ $row['found'] ? '' : 'placeholder=Resolve in SAP' }}>
                                                        </td>
                                                        <td>
                                                            <input type="number"
                                                                name="grpo_references[{{ $index }}][base_line]"
                                                                class="form-control form-control-sm line-input"
                                                                value="{{ $row['base_line'] }}" min="0">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                name="grpo_references[{{ $index }}][item_code]"
                                                                class="form-control form-control-sm item-code-input"
                                                                value="{{ $row['item_code'] }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.0001"
                                                                name="grpo_references[{{ $index }}][quantity]"
                                                                class="form-control form-control-sm quantity-input"
                                                                value="{{ number_format($row['quantity'], 4, '.', '') }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01"
                                                                name="grpo_references[{{ $index }}][unit_price]"
                                                                class="form-control form-control-sm unit-price-input"
                                                                value="{{ number_format($row['unit_price'], 2, '.', '') }}">
                                                        </td>
                                                        <td class="line-total-cell text-right">
                                                            {{ number_format($row['quantity'] * $row['unit_price'], 2) }}
                                                        </td>
                                                        <td class="status-cell">
                                                            @if ($row['found'])
                                                                <span class="badge bg-success">Found</span>
                                                            @elseif ($row['error'])
                                                                <span class="badge bg-danger"
                                                                    title="{{ $row['error'] }}">Not found</span>
                                                            @else
                                                                <span class="badge bg-secondary">Optional</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger remove-grpo-row"
                                                                title="Remove row">&times;</button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr class="grpo-row" data-index="0">
                                                        <td>
                                                            <input type="text" name="grpo_references[0][grpo_no]"
                                                                class="form-control form-control-sm grpo-no-input">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="grpo_references[0][doc_entry]"
                                                                class="form-control form-control-sm doc-entry-input"
                                                                min="1">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="grpo_references[0][base_line]"
                                                                class="form-control form-control-sm line-input"
                                                                value="0" min="0">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="grpo_references[0][item_code]"
                                                                class="form-control form-control-sm item-code-input">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.0001"
                                                                name="grpo_references[0][quantity]"
                                                                class="form-control form-control-sm quantity-input"
                                                                value="0">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01"
                                                                name="grpo_references[0][unit_price]"
                                                                class="form-control form-control-sm unit-price-input"
                                                                value="0">
                                                        </td>
                                                        <td class="line-total-cell text-right">0.00</td>
                                                        <td class="status-cell"><span
                                                                class="badge bg-secondary">Manual</span></td>
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger remove-grpo-row">&times;</button>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">
                                        Lines with valid DocEntry will link AP Invoice to GRPO in SAP B1 (BaseType 20).
                                        Invoice total: <strong
                                            id="invoice-total">{{ number_format($invoice->amount, 2) }}</strong>
                                        — GRPO sum: <strong id="grpo-sum">0.00</strong>
                                        <span id="amount-mismatch" class="text-warning d-none"> (amounts differ)</span>
                                    </small>
                                </div>
                            </div>
                        @endif

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
                                                    <td class="text-right">
                                                        {{ isset($line['UnitPrice']) ? number_format($line['UnitPrice'], 2) : '—' }}
                                                    </td>
                                                    <td>{{ $line['ProjectCode'] ?? '—' }}</td>
                                                    <td>{{ $line['CostingCode'] ?? '—' }}</td>
                                                    <td>
                                                        @if (!empty($line['BaseEntry']))
                                                            <span class="badge bg-primary">GRPO
                                                                {{ $line['BaseEntry'] }} /
                                                                L{{ $line['BaseLine'] ?? 0 }}</span>
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

                        <div class="mt-3 d-flex" style="gap: 8px;">
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
    @include('invoices.partials.sap-status-monitor')

    <script>
        (function() {
            const invoiceTotal = {{ (float) $invoice->amount }};
            const isStandalone = @json((bool) $isStandalone);
            const submitUrl = @json(route('invoices.submit-to-sap', $invoice));
            const statusUrl = @json(route('invoices.sap-status', $invoice));
            const invoiceUrl = @json(route('invoices.show', $invoice));
            const invoiceId = {{ $invoice->id }};
            const invoiceNumber = @json($invoice->invoice_number);
            const csrfToken = @json(csrf_token());
            let rowIndex = {{ count($grpoRows) ?: 1 }};

            function recalcSum() {
                if (isStandalone) {
                    return;
                }
                let sum = 0;
                document.querySelectorAll('#grpo-tbody .grpo-row').forEach(function(row) {
                    const qty = parseFloat(row.querySelector('.quantity-input')?.value) || 0;
                    const price = parseFloat(row.querySelector('.unit-price-input')?.value) || 0;
                    const lineTotal = qty * price;
                    sum += lineTotal;
                    const cell = row.querySelector('.line-total-cell');
                    if (cell) {
                        cell.textContent = lineTotal.toFixed(2);
                    }
                });
                const sumEl = document.getElementById('grpo-sum');
                if (sumEl) {
                    sumEl.textContent = sum.toFixed(2);
                }
                const mismatch = document.getElementById('amount-mismatch');
                if (mismatch) {
                    if (Math.abs(sum - invoiceTotal) > 0.01) {
                        mismatch.classList.remove('d-none');
                    } else {
                        mismatch.classList.add('d-none');
                    }
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

            function bindRow(row) {
                const removeBtn = row.querySelector('.remove-grpo-row');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function() {
                        row.remove();
                        reindexRows();
                        recalcSum();
                    });
                }
                row.querySelectorAll('.quantity-input, .unit-price-input').forEach(function(el) {
                    el.addEventListener('input', recalcSum);
                });
            }

            if (!isStandalone) {
                const addBtn = document.getElementById('add-grpo-row');
                if (addBtn) {
                    addBtn.addEventListener('click', function() {
                        const tbody = document.getElementById('grpo-tbody');
                        const tr = document.createElement('tr');
                        tr.className = 'grpo-row';
                        tr.innerHTML = `
                            <td><input type="text" name="grpo_references[${rowIndex}][grpo_no]" class="form-control form-control-sm grpo-no-input"></td>
                            <td><input type="number" name="grpo_references[${rowIndex}][doc_entry]" class="form-control form-control-sm doc-entry-input" min="1"></td>
                            <td><input type="number" name="grpo_references[${rowIndex}][base_line]" class="form-control form-control-sm line-input" value="0" min="0"></td>
                            <td><input type="text" name="grpo_references[${rowIndex}][item_code]" class="form-control form-control-sm item-code-input"></td>
                            <td><input type="number" step="0.0001" name="grpo_references[${rowIndex}][quantity]" class="form-control form-control-sm quantity-input" value="0"></td>
                            <td><input type="number" step="0.01" name="grpo_references[${rowIndex}][unit_price]" class="form-control form-control-sm unit-price-input" value="0"></td>
                            <td class="line-total-cell text-right">0.00</td>
                            <td class="status-cell"><span class="badge bg-secondary">Manual</span></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-grpo-row">&times;</button></td>
                        `;
                        tbody.appendChild(tr);
                        rowIndex++;
                        bindRow(tr);
                        recalcSum();
                    });
                }

                document.querySelectorAll('#grpo-tbody .grpo-row').forEach(bindRow);
                recalcSum();
            }

            function submitToSap(form) {
                const submitBtn = document.getElementById('submit-btn');
                const formData = new FormData(form);

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';

                DdsSapStatusMonitor.showOverlay(
                    'Submitting to SAP',
                    'Queueing AP Invoice for SAP B1…',
                    true
                );

                fetch(submitUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: formData,
                    })
                    .then(function(response) {
                        return response.json().then(function(data) {
                            return {
                                ok: response.ok,
                                status: response.status,
                                data: data,
                            };
                        });
                    })
                    .then(function(result) {
                        if (!result.ok) {
                            const message = result.data.message ||
                                (result.data.errors ?
                                    Object.values(result.data.errors).flat().join(' ') :
                                    'Submission failed');
                            throw new Error(message);
                        }

                        if (result.data.warning && typeof toastr !== 'undefined') {
                            toastr.warning(result.data.warning);
                        }

                        DdsSapStatusMonitor.showOverlay(
                            'Processing in SAP',
                            'Waiting for SAP B1 to create the AP Invoice…',
                            true
                        );

                        return DdsSapStatusMonitor.poll({
                            invoiceId: invoiceId,
                            statusUrl: statusUrl,
                        });
                    })
                    .then(function(data) {
                        DdsSapStatusMonitor.showOverlayResult(data, invoiceUrl);
                        submitBtn.innerHTML = data.sap_status === 'posted' ?
                            '<i class="fas fa-check"></i> Posted to SAP' :
                            '<i class="fas fa-times"></i> SAP Failed';
                    })
                    .catch(function(error) {
                        DdsSapStatusMonitor.showOverlay(
                            'Submission failed',
                            error.message || 'Unable to submit to SAP.',
                            false
                        );
                        document.getElementById('sap-status-spinner').classList.add('d-none');
                        document.getElementById('sap-status-result').classList.remove('d-none');
                        document.getElementById('sap-status-badge').innerHTML =
                            '<span class="badge bg-danger">Submission failed</span>';
                        document.getElementById('sap-status-detail').textContent = error.message ||
                            'Unable to submit to SAP.';
                        document.getElementById('sap-status-invoice-link').href = invoiceUrl;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Confirm &amp; Submit to SAP B1';
                    });
            }

            document.getElementById('sap-submit-form').addEventListener('submit', function(e) {
                e.preventDefault();

                if (!isStandalone) {
                    const validRows = Array.from(document.querySelectorAll('.doc-entry-input'))
                        .filter(function(el) {
                            return parseInt(el.value, 10) > 0;
                        });
                    if (validRows.length === 0) {
                        alert('At least one GRPO line with a valid DocEntry is required when PO number is set.');
                        return;
                    }
                }

                const modeLabel = isStandalone ?
                    'standalone AP Invoice (no GRPO link)' :
                    'GRPO-linked AP Invoice';

                Swal.fire({
                    title: 'Submit to SAP B1?',
                    html: 'Post invoice <strong>' + invoiceNumber + '</strong> as a ' + modeLabel +
                        ' in SAP Business One?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, submit to SAP',
                    cancelButtonText: 'Cancel',
                }).then(function(result) {
                    if (result.isConfirmed) {
                        submitToSap(e.target);
                    }
                });
            });
        })();
    </script>
@endsection
