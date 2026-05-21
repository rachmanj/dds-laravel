@extends('layouts.main')

@section('title_page')
    Batch import invoices
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">Batch import</li>
@endsection

@section('content')
    <section class="content">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-layer-group"></i> Batch import from PDF / images</h3>
                <div>
                    <a href="{{ route('invoices.create') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-alt"></i> Single create
                    </a>
                    <a href="{{ route('invoices.index') }}" class="btn btn-info btn-sm">
                        <i class="fas fa-list"></i> Invoices list
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if (in_array($invoiceImportQueueDriver ?? 'sync', ['database', 'redis'], true))
                    <div class="alert alert-info small py-2 mb-3">
                        <i class="fas fa-info-circle"></i> Extraction runs in a queue. If uploads never complete, run
                        <code>php artisan queue:work</code> or set <code>QUEUE_CONNECTION=sync</code> for local testing.
                    </div>
                @endif

                <div id="batch_zone_a" class="mb-4">
                    <label class="font-weight-bold d-block">1. Select files (max {{ $batchImportMax }})</label>
                    <p class="text-muted small mb-2">One file per invoice. PDF, JPG, PNG, WebP, or GIF. Each file is saved as an
                        <strong>Invoice Copy</strong> attachment on the created invoice (same as single-file import).</p>
                    <div id="batch_drop_zone" class="border border-secondary rounded p-4 text-center bg-light mb-2"
                        style="cursor: pointer;">
                        <i class="fas fa-cloud-upload-alt fa-2x text-secondary mb-2"></i>
                        <div>Drop files here or click to browse</div>
                        <input type="file" id="batch_file_input" class="d-none" multiple
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,application/pdf,image/jpeg,image/png,image/webp,image/gif">
                    </div>
                    <div id="batch_file_list" class="small mb-2"></div>
                    <div id="batch_file_limit_msg" class="alert alert-warning small py-2 d-none mb-2"></div>
                    <button type="button" class="btn btn-primary" id="batch_upload_btn" disabled>
                        <i class="fas fa-magic"></i> Upload &amp; extract all
                    </button>
                    <button type="button" class="btn btn-outline-secondary ml-1" id="batch_clear_files_btn" disabled>Clear
                        selection</button>
                </div>

                <div id="batch_zone_b" class="mb-4 d-none">
                    <label class="font-weight-bold d-block">2. Extraction progress</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="batch_progress_table">
                            <thead class="thead-light">
                                <tr>
                                    <th>File</th>
                                    <th style="width: 140px">Status</th>
                                </tr>
                            </thead>
                            <tbody id="batch_progress_tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="batch_zone_c" class="d-none">
                    <label class="font-weight-bold d-block">3. Review &amp; create</label>
                    <div class="card card-outline card-secondary mb-3">
                        <div class="card-body py-3">
                            <div class="form-row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label for="batch_default_type_id" class="small font-weight-bold mb-0">Invoice type
                                        (all rows) <span class="text-danger">*</span></label>
                                    <select id="batch_default_type_id" class="form-control form-control-sm" required>
                                        <option value="">Select type</option>
                                        @foreach ($invoiceTypes as $t)
                                            <option value="{{ $t->id }}">{{ $t->type_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="batch_default_receive_date" class="small font-weight-bold mb-0">Receive date
                                        (all rows)</label>
                                    <input type="date" id="batch_default_receive_date" class="form-control form-control-sm"
                                        value="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="batch_default_cur_loc" class="small font-weight-bold mb-0">Current location
                                        (all rows) <span class="text-danger">*</span></label>
                                    <input type="text" id="batch_default_cur_loc" class="form-control form-control-sm"
                                        value="{{ auth()->user()->department_location_code ?? '' }}" required maxlength="30">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="batch_default_supplier_id" class="small font-weight-bold mb-0">Supplier
                                        (all rows)</label>
                                    <select id="batch_default_supplier_id" class="form-control form-control-sm">
                                        <option value="">Select supplier</option>
                                        @foreach ($suppliers as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}@if ($s->sap_code)
                                                    ({{ $s->sap_code }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row align-items-end">
                                <div class="col-12 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="batch_apply_defaults_btn">
                                        <i class="fas fa-arrow-down"></i> Apply type &amp; supplier to all rows
                                    </button>
                                    <small class="text-muted d-block mt-2">Receive date and location apply when you create
                                        invoices. Use the button to copy the selected invoice type and/or supplier into every
                                        row (supplier is optional).</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-3" style="overflow-x: auto;">
                        <table class="table table-sm table-bordered" id="batch_review_table" style="min-width: 1100px;">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 36px"><abbr title="Include in batch create">✓</abbr></th>
                                    <th>File</th>
                                    <th style="width: 90px">Conf.</th>
                                    <th style="width: 140px">Invoice #</th>
                                    <th style="width: 130px">Invoice date</th>
                                    <th style="min-width: 180px">Supplier</th>
                                    <th style="width: 70px">Curr.</th>
                                    <th style="width: 110px">Amount</th>
                                    <th style="min-width: 160px">Type</th>
                                    <th style="width: 90px">Lines</th>
                                </tr>
                            </thead>
                            <tbody id="batch_review_tbody"></tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-success" id="batch_submit_btn" disabled>
                        <i class="fas fa-save"></i> Create <span id="batch_submit_count">0</span> invoice(s)
                    </button>
                </div>

                <div id="batch_zone_d" class="d-none mt-4">
                    <label class="font-weight-bold d-block">4. Results</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="batch_results_table">
                            <thead class="thead-light">
                                <tr>
                                    <th>File / index</th>
                                    <th>Invoice #</th>
                                    <th style="width: 120px">Status</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody id="batch_results_tbody"></tbody>
                        </table>
                    </div>
                    <a href="{{ route('invoices.import-batch') }}" class="btn btn-outline-primary btn-sm mt-2">Start new
                        batch</a>
                    <a href="{{ route('invoices.index') }}" class="btn btn-info btn-sm mt-2">Back to list</a>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(function() {
            const cfg = {
                maxBatch: {{ (int) $batchImportMax }},
                batchStoreUrl: @json(route('invoices.import-batch.store')),
                extractUrl: @json(route('invoices.import-extract')),
                statusBase: @json(rtrim(url('/invoices/import-status'), '/')),
                draftBase: @json(rtrim(url('/invoices/import-draft'), '/')),
                suppliers: @json($suppliers->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values()),
                types: @json($invoiceTypes->map(fn($t) => ['id' => $t->id, 'name' => $t->type_name])->values()),
                invoiceShowBase: @json(rtrim(url('/invoices'), '/')),
                pollMs: 1500,
                pollMaxTries: 120,
            };

            const MAX_LINE_ITEMS = 200;
            let selectedFiles = [];
            const activePolls = {};

            function csrfHeaders() {
                return {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                };
            }

            function escapeHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, function(c) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    } [c] || c;
                });
            }

            function statusUrl(uuid) {
                return cfg.statusBase + '/' + encodeURIComponent(uuid);
            }

            function draftUrl(uuid) {
                return cfg.draftBase + '/' + encodeURIComponent(uuid);
            }

            function normalizeDraft(raw) {
                if (raw == null || typeof raw !== 'object' || Array.isArray(raw)) {
                    return {};
                }
                return raw;
            }

            function supplierOptionsHtml(selectedId) {
                let h = '<option value="">Select supplier</option>';
                cfg.suppliers.forEach(function(s) {
                    const sel = String(s.id) === String(selectedId || '') ? ' selected' : '';
                    h += '<option value="' + s.id + '"' + sel + '>' + escapeHtml(s.name) + '</option>';
                });
                return h;
            }

            function typeOptionsHtml(selectedId) {
                let h = '<option value="">Select type</option>';
                cfg.types.forEach(function(t) {
                    const sel = String(t.id) === String(selectedId || '') ? ' selected' : '';
                    h += '<option value="' + t.id + '"' + sel + '>' + escapeHtml(t.name) + '</option>';
                });
                return h;
            }

            function stripAmount(val) {
                if (val === null || val === undefined) return '';
                return String(val).replace(/,/g, '').trim();
            }

            function parseNum(val) {
                const n = parseFloat(stripAmount(val));
                return isNaN(n) ? null : n;
            }

            function updateSubmitCount() {
                const n = $('#batch_review_tbody tr.batch-review-row').filter(function() {
                    return $(this).find('.batch-include').is(':checked');
                }).length;
                $('#batch_submit_count').text(String(n));
                $('#batch_submit_btn').prop('disabled', n < 1 || !$('#batch_default_type_id').val());
            }

            function recalcLineAmount($lineItemTr) {
                const q = parseNum($lineItemTr.find('.batch-il-qty').val());
                const p = parseNum($lineItemTr.find('.batch-il-price').val());
                if (q !== null && p !== null) {
                    $lineItemTr.find('.batch-il-amt').val((Math.round(q * p * 100) / 100).toFixed(2));
                }
                const $block = $lineItemTr.closest('.batch-line-block');
                const $reviewRow = $block.prev('.batch-review-row');
                checkRowMismatch($reviewRow);
            }

            function checkRowMismatch($reviewRow) {
                if (!$reviewRow || !$reviewRow.length) return;
                const $block = $reviewRow.next('.batch-line-block');
                const $tbody = $block.find('.batch-il-tbody');
                const rows = $tbody.find('tr');
                const $warn = $block.find('.batch-line-mismatch');
                if (!rows.length) {
                    $warn.addClass('d-none');
                    return;
                }
                let sum = 0;
                rows.each(function() {
                    const v = parseNum($(this).find('.batch-il-amt').val());
                    sum += (v !== null ? v : 0);
                });
                const cur = ($reviewRow.find('.batch-currency').val() || '').toUpperCase();
                const tol = cur === 'IDR' ? 1.0 : 0.01;
                const header = parseNum($reviewRow.find('.batch-amount').val()) || 0;
                const bad = Math.abs(header - sum) > tol;
                $warn.toggleClass('d-none', !bad);
                $warn.find('.batch-line-sum').text(sum.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $warn.find('.batch-line-header').text(header.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }

            function appendLineRow($tbody, idx, row, rowKey) {
                row = row || {};
                const d = row.description != null ? String(row.description) : '';
                const q = row.quantity != null && row.quantity !== '' ? String(row.quantity) : '';
                const p = row.unit_price != null && row.unit_price !== '' ? String(row.unit_price) : '';
                const a = row.amount != null && row.amount !== '' ? String(row.amount) : '';
                const $tr = $('<tr>');
                $tr.append($('<td>').append($('<input>', {
                    type: 'text',
                    class: 'form-control form-control-sm batch-il-desc',
                    name: 'batch_li_' + rowKey + '[' + idx + '][description]',
                    autocomplete: 'off'
                }).val(d)));
                $tr.append($('<td>').append($('<input>', {
                    type: 'text',
                    class: 'form-control form-control-sm batch-il-qty text-right',
                    name: 'batch_li_' + rowKey + '[' + idx + '][quantity]',
                    inputmode: 'decimal',
                    autocomplete: 'off'
                }).val(q)));
                $tr.append($('<td>').append($('<input>', {
                    type: 'text',
                    class: 'form-control form-control-sm batch-il-price text-right',
                    name: 'batch_li_' + rowKey + '[' + idx + '][unit_price]',
                    inputmode: 'decimal',
                    autocomplete: 'off'
                }).val(p)));
                $tr.append($('<td>').append($('<input>', {
                    type: 'text',
                    class: 'form-control form-control-sm batch-il-amt text-right',
                    name: 'batch_li_' + rowKey + '[' + idx + '][amount]',
                    inputmode: 'decimal',
                    autocomplete: 'off'
                }).val(a)));
                $tr.append($('<td>', {
                    class: 'text-center'
                }).append($('<button>', {
                    type: 'button',
                    class: 'btn btn-link btn-sm text-danger p-0 batch-il-del',
                    title: 'Remove'
                }).append('<i class="fas fa-times"></i>')));
                $tbody.append($tr);
                recalcLineAmount($tr);
            }

            function buildLineItemsSection(rowKey, lineItems) {
                const $wrap = $('<div>', {
                    class: 'batch-lines-wrap p-2 bg-light border-top'
                });
                const $tbl = $('<table>').addClass('table table-sm table-bordered mb-2').css('min-width', '640px');
                $tbl.append('<thead class="thead-light"><tr><th>Description</th><th class="text-right" style="width:7rem">Qty</th><th class="text-right" style="width:7.5rem">Unit price</th><th class="text-right" style="width:8rem">Amount</th><th style="width:2rem"></th></tr></thead>');
                const $tb = $('<tbody>').addClass('batch-il-tbody');
                $tbl.append($tb);
                if (Array.isArray(lineItems) && lineItems.length) {
                    lineItems.forEach(function(li, i) {
                        appendLineRow($tb, i, li, rowKey);
                    });
                }
                $wrap.append('<div class="d-flex justify-content-between align-items-center mb-1"><strong class="small mb-0">Line items</strong><button type="button" class="btn btn-outline-secondary btn-sm batch-il-add" data-row-key="' + rowKey + '"><i class="fas fa-plus"></i> Add row</button></div>');
                $wrap.append($('<div>', {
                    class: 'table-responsive',
                    style: 'overflow-x:auto'
                }).append($tbl));
                const $mis = $('<div>', {
                    class: 'batch-line-mismatch alert alert-warning small py-2 d-none mb-0'
                }).html('<i class="fas fa-exclamation-triangle"></i> Line sum (<span class="batch-line-sum">0</span>) ≠ header amount (<span class="batch-line-header">0</span>).');
                $wrap.append($mis);
                $wrap.append('<small class="text-muted">SAP posting stays header-only.</small>');

                $wrap.on('input', '.batch-il-qty, .batch-il-price', function() {
                    recalcLineAmount($(this).closest('tr'));
                });
                $wrap.on('input change', '.batch-il-amt', function() {
                    const $block = $(this).closest('.batch-line-block');
                    const $reviewRow = $block.prev('.batch-review-row');
                    checkRowMismatch($reviewRow);
                });
                $wrap.on('click', '.batch-il-del', function() {
                    const $tbod = $(this).closest('.batch-il-tbody');
                    $(this).closest('tr').remove();
                    const $rr = $(this).closest('.batch-line-block').prev('.batch-review-row');
                    checkRowMismatch($rr);
                });
                $wrap.on('click', '.batch-il-add', function() {
                    const key = $(this).data('row-key');
                    const $tbody = $(this).closest('.batch-lines-wrap').find('.batch-il-tbody');
                    const n = $tbody.find('tr').length;
                    if (n >= MAX_LINE_ITEMS) {
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Maximum ' + MAX_LINE_ITEMS + ' lines.');
                        }
                        return;
                    }
                    appendLineRow($tbody, n, {}, key);
                    const $rr = $(this).closest('.batch-line-block').prev('.batch-review-row');
                    checkRowMismatch($rr);
                });

                const $row = $('<tr>').addClass('batch-line-block d-none').attr('data-line-for', rowKey);
                $row.append($('<td>', {
                    colspan: 10
                }).append($wrap));
                return {
                    $lineTr: $row,
                    $tbody: $tb,
                    rowKey: rowKey
                };
            }

            function addReviewRow(meta) {
                meta = meta || {};
                const uuid = meta.uuid || '';
                const fileName = meta.fileName || '';
                const draft = normalizeDraft(meta.draft);
                const failed = !!meta.failed;
                const errMsg = meta.error || '';

                const rowKey = 'rk_' + Math.random().toString(36).slice(2, 11);
                let confText = '—';
                let rowClass = '';
                if (failed) {
                    rowClass = 'table-danger';
                } else if (draft.confidence != null && !isNaN(Number(draft.confidence))) {
                    confText = (Number(draft.confidence) * 100).toFixed(0) + '%';
                    const low = Number(draft.confidence) < 0.7 || (Array.isArray(draft.low_confidence_fields) && draft.low_confidence_fields.length);
                    if (low) {
                        rowClass = 'table-warning';
                    }
                }

                const $tr = $('<tr>').addClass('batch-review-row ' + rowClass).attr('data-import-uuid', uuid).attr(
                    'data-row-key', rowKey);
                $tr.data('draftSnapshot', JSON.parse(JSON.stringify(draft)));

                const $cb = $('<input>', {
                    type: 'checkbox',
                    class: 'batch-include'
                }).prop('checked', !failed);
                $tr.append($('<td>').append($cb));
                $tr.append($('<td>').addClass('small').text(fileName));
                $tr.append($('<td>').addClass('small').text(failed ? (errMsg || 'Failed') : confText));

                const $inv = $('<input>', {
                    type: 'text',
                    class: 'form-control form-control-sm batch-invoice-number',
                    required: true,
                    maxlength: 255
                }).val(draft.invoice_number || '');
                $tr.append($('<td>').append($inv));

                const $idt = $('<input>', {
                    type: 'date',
                    class: 'form-control form-control-sm batch-invoice-date'
                }).val(draft.invoice_date || '');
                $tr.append($('<td>').append($idt));

                const defSupplier = ($('#batch_default_supplier_id').val() || '').trim();
                const supplierIdForRow = draft.supplier_id || defSupplier || '';
                const $sup = $('<select>', {
                    class: 'form-control form-control-sm batch-supplier'
                }).html(supplierOptionsHtml(supplierIdForRow));
                $tr.append($('<td>').append($sup));

                const $cur = $('<input>', {
                    type: 'text',
                    class: 'form-control form-control-sm batch-currency text-uppercase',
                    maxlength: 3
                }).val((draft.currency || 'IDR').toString().substring(0, 3));
                $tr.append($('<td>').append($cur));

                const $amt = $('<input>', {
                    type: 'text',
                    class: 'form-control form-control-sm batch-amount text-right',
                    inputmode: 'decimal'
                }).val(stripAmount(draft.amount || ''));
                $tr.append($('<td>').append($amt));

                const defType = $('#batch_default_type_id').val() || '';
                const $type = $('<select>', {
                    class: 'form-control form-control-sm batch-type-id'
                }).html(typeOptionsHtml(defType));
                $tr.append($('<td>').append($type));

                const $btn = $('<button>', {
                    type: 'button',
                    class: 'btn btn-sm btn-outline-secondary batch-toggle-lines'
                }).html('<i class="fas fa-list"></i>');
                $tr.append($('<td>').append($btn));

                const lineSection = buildLineItemsSection(rowKey, draft.line_items || []);
                $('#batch_review_tbody').append($tr);
                $('#batch_review_tbody').append(lineSection.$lineTr);

                $btn.on('click', function() {
                    lineSection.$lineTr.toggleClass('d-none');
                });

                if (Array.isArray(draft.line_items) && draft.line_items.length) {
                    lineSection.$lineTr.removeClass('d-none');
                }

                $tr.on('change input', '.batch-amount, .batch-currency', function() {
                    checkRowMismatch($tr);
                });

                $cb.on('change', updateSubmitCount);
                $tr.find('input, select').on('change', updateSubmitCount);

                checkRowMismatch($tr);
                $('#batch_zone_c').removeClass('d-none');
                updateSubmitCount();
            }

            function setProgressRow(fileName, text, isError) {
                const $tbody = $('#batch_progress_tbody');
                let $tr = $tbody.find('tr').filter(function() {
                    return $(this).find('.batch-prog-name').text() === fileName;
                }).first();
                if (!$tr.length) {
                    $tr = $('<tr>');
                    $tr.append($('<td>').append($('<span>').addClass('batch-prog-name').text(fileName)));
                    $tr.append($('<td>').addClass('batch-prog-status small'));
                    $tbody.append($tr);
                }
                const $st = $tr.find('.batch-prog-status');
                $st.text(text);
                $st.toggleClass('text-danger', !!isError);
                $st.toggleClass('text-success', !isError && (text === 'Done' || text.indexOf('Done') === 0));
                $('#batch_zone_b').removeClass('d-none');
            }

            function pollUuid(uuid, fileName) {
                if (activePolls[uuid]) {
                    return;
                }
                let tries = 0;
                activePolls[uuid] = true;

                function tick() {
                    tries++;
                    if (tries > cfg.pollMaxTries) {
                        clearInterval(timer);
                        delete activePolls[uuid];
                        setProgressRow(fileName, 'Timed out', true);
                        addReviewRow({
                            fileName: fileName,
                            failed: true,
                            error: 'Timed out waiting for extraction.'
                        });
                        return;
                    }
                    $.getJSON(statusUrl(uuid))
                        .done(function(res) {
                            if (!res.success) {
                                return;
                            }
                            if (res.status === 'completed') {
                                clearInterval(timer);
                                delete activePolls[uuid];
                                setProgressRow(fileName, 'Done', false);
                                $.getJSON(draftUrl(uuid))
                                    .done(function(dres) {
                                        if (!dres.success) {
                                            addReviewRow({
                                                uuid: uuid,
                                                fileName: fileName,
                                                failed: true,
                                                error: dres.message || 'No draft'
                                            });
                                            return;
                                        }
                                        addReviewRow({
                                            uuid: uuid,
                                            fileName: fileName,
                                            draft: dres.draft || {}
                                        });
                                    })
                                    .fail(function() {
                                        addReviewRow({
                                            uuid: uuid,
                                            fileName: fileName,
                                            failed: true,
                                            error: 'Could not load draft'
                                        });
                                    });
                            } else if (res.status === 'failed') {
                                clearInterval(timer);
                                delete activePolls[uuid];
                                setProgressRow(fileName, 'Failed', true);
                                addReviewRow({
                                    uuid: uuid,
                                    fileName: fileName,
                                    failed: true,
                                    error: res.error || 'Extraction failed'
                                });
                            } else {
                                setProgressRow(fileName, 'Extracting…', false);
                            }
                        });
                }
                const timer = setInterval(tick, cfg.pollMs);
                tick();
            }

            function uploadFile(file) {
                const fd = new FormData();
                fd.append('file', file);
                setProgressRow(file.name, 'Uploading…', false);
                $.ajax({
                    url: cfg.extractUrl,
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: csrfHeaders(),
                }).done(function(res) {
                    if (!res || !res.success || !res.uuid) {
                        setProgressRow(file.name, 'Upload failed', true);
                        addReviewRow({
                            fileName: file.name,
                            failed: true,
                            error: (res && res.message) ? res.message : 'Upload failed'
                        });
                        return;
                    }
                    if (res.status === 'failed') {
                        setProgressRow(file.name, 'Failed', true);
                        addReviewRow({
                            uuid: res.uuid,
                            fileName: file.name,
                            failed: true,
                            error: res.error || 'Extraction failed'
                        });
                        return;
                    }
                    if (res.status === 'completed') {
                        setProgressRow(file.name, 'Done', false);
                        $.getJSON(draftUrl(res.uuid))
                            .done(function(dres) {
                                if (!dres.success) {
                                    addReviewRow({
                                        uuid: res.uuid,
                                        fileName: file.name,
                                        failed: true,
                                        error: dres.message || 'No draft'
                                    });
                                    return;
                                }
                                addReviewRow({
                                    uuid: res.uuid,
                                    fileName: file.name,
                                    draft: dres.draft || {}
                                });
                            });
                        return;
                    }
                    pollUuid(res.uuid, file.name);
                }).fail(function(xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message :
                        'Upload failed';
                    setProgressRow(file.name, 'Error', true);
                    addReviewRow({
                        fileName: file.name,
                        failed: true,
                        error: msg
                    });
                });
            }

            function syncSelectedFilesFromInput(input) {
                selectedFiles = Array.prototype.slice.call(input.files || []);
                if (selectedFiles.length > cfg.maxBatch) {
                    $('#batch_file_limit_msg').removeClass('d-none').text('Only the first ' + cfg.maxBatch +
                        ' files can be processed. Extra files were removed from the selection.');
                    selectedFiles = selectedFiles.slice(0, cfg.maxBatch);
                    const dt = new DataTransfer();
                    selectedFiles.forEach(function(f) {
                        dt.items.add(f);
                    });
                    input.files = dt.files;
                } else {
                    $('#batch_file_limit_msg').addClass('d-none').text('');
                }
                renderFileList();
            }

            function renderFileList() {
                const $list = $('#batch_file_list');
                $list.empty();
                if (!selectedFiles.length) {
                    $('#batch_upload_btn, #batch_clear_files_btn').prop('disabled', true);

                    return;
                }
                selectedFiles.forEach(function(f) {
                    $list.append($('<div>').text(f.name + ' (' + Math.round(f.size / 1024) + ' KB)'));
                });
                $('#batch_clear_files_btn').prop('disabled', false);
                $('#batch_upload_btn').prop('disabled', false);
            }

            $('#batch_drop_zone').on('click', function() {
                $('#batch_file_input').trigger('click');
            });
            $('#batch_file_input').on('change', function() {
                syncSelectedFilesFromInput(this);
            });

            $('#batch_drop_zone').on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('border-primary');
            });
            $('#batch_drop_zone').on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('border-primary');
            });
            $('#batch_drop_zone').on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('border-primary');
                const dt = e.originalEvent.dataTransfer;
                if (!dt || !dt.files || !dt.files.length) {
                    return;
                }
                const arr = Array.prototype.slice.call(dt.files);
                const input = document.getElementById('batch_file_input');
                const dataTransfer = new DataTransfer();
                arr.forEach(function(f) {
                    dataTransfer.items.add(f);
                });
                input.files = dataTransfer.files;
                syncSelectedFilesFromInput(input);
            });

            $('#batch_clear_files_btn').on('click', function() {
                const input = document.getElementById('batch_file_input');
                input.value = '';
                selectedFiles = [];
                renderFileList();
            });

            $('#batch_default_type_id').on('change', updateSubmitCount);
            $('#batch_default_supplier_id').on('change', updateSubmitCount);

            $('#batch_apply_defaults_btn').on('click', function() {
                const tid = $('#batch_default_type_id').val();
                const sid = ($('#batch_default_supplier_id').val() || '').trim();
                if (!tid && !sid) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Choose an invoice type and/or supplier to apply.');
                    }
                    return;
                }
                if (tid) {
                    $('#batch_review_tbody tr.batch-review-row').each(function() {
                        $(this).find('.batch-type-id').val(String(tid));
                    });
                }
                if (sid) {
                    $('#batch_review_tbody tr.batch-review-row').each(function() {
                        $(this).find('.batch-supplier').val(String(sid));
                    });
                }
                updateSubmitCount();
            });

            $('#batch_upload_btn').on('click', function() {
                if (!selectedFiles.length) {
                    return;
                }
                let list = selectedFiles.slice();
                if (list.length > cfg.maxBatch) {
                    $('#batch_file_limit_msg').removeClass('d-none').text('Only the first ' + cfg.maxBatch +
                        ' files will be processed.');
                    list = list.slice(0, cfg.maxBatch);
                } else {
                    $('#batch_file_limit_msg').addClass('d-none').text('');
                }
                $('#batch_progress_tbody').empty();
                $('#batch_zone_b').removeClass('d-none');
                list.forEach(function(file) {
                    uploadFile(file);
                });
            });

            function collectLinesFromReviewRow($tr) {
                const $block = $tr.next('.batch-line-block');
                const items = [];
                $block.find('.batch-il-tbody tr').each(function() {
                    const desc = ($(this).find('.batch-il-desc').val() || '').trim();
                    const q = $(this).find('.batch-il-qty').val();
                    const p = $(this).find('.batch-il-price').val();
                    const a = $(this).find('.batch-il-amt').val();
                    if (!desc && !q && !p && !a) {
                        return;
                    }
                    items.push({
                        description: desc || '(no description)',
                        quantity: q === '' ? null : q,
                        unit_price: p === '' ? null : p,
                        amount: a === '' ? null : a,
                    });
                });

                return items;
            }

            $('#batch_submit_btn').on('click', function() {
                const receiveDate = $('#batch_default_receive_date').val();
                const curLoc = ($('#batch_default_cur_loc').val() || '').trim();
                if (!receiveDate) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Receive date is required.');
                    }
                    return;
                }
                if (!curLoc) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Current location is required.');
                    }
                    return;
                }
                if (!$('#batch_default_type_id').val()) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Select a default invoice type (and apply to rows if needed).');
                    }
                    return;
                }
                if (!window.confirm('Create the selected invoices?')) {
                    return;
                }

                const invoices = [];
                const rowLabels = [];
                $('#batch_review_tbody tr.batch-review-row').each(function() {
                    const $tr = $(this);
                    if (!$tr.find('.batch-include').is(':checked')) {
                        return;
                    }
                    const fileLabel = $tr.find('td').eq(1).text() || '';
                    const snap = $tr.data('draftSnapshot') || {};
                    const uuid = ($tr.attr('data-import-uuid') || '').trim();
                    const lines = collectLinesFromReviewRow($tr);
                    let typeId = ($tr.find('.batch-type-id').val() || '').trim();
                    if (!typeId) {
                        typeId = $('#batch_default_type_id').val();
                    }
                    const payload = {
                        import_uuid: uuid || null,
                        invoice_number: ($tr.find('.batch-invoice-number').val() || '').trim(),
                        invoice_date: $tr.find('.batch-invoice-date').val(),
                        receive_date: receiveDate,
                        supplier_id: $tr.find('.batch-supplier').val(),
                        currency: ($tr.find('.batch-currency').val() || '').trim(),
                        amount: stripAmount($tr.find('.batch-amount').val()),
                        type_id: typeId,
                        cur_loc: curLoc,
                        faktur_no: snap.faktur_no || null,
                        po_no: snap.po_no || null,
                        receive_project: snap.receive_project || null,
                        invoice_project: snap.invoice_project || null,
                        payment_project: snap.payment_project || null,
                        payment_date: snap.payment_date || null,
                        remarks: snap.remarks || null,
                        sap_doc: null,
                    };
                    if (lines.length) {
                        payload.import_line_items = lines;
                    }
                    invoices.push(payload);
                    rowLabels.push(fileLabel);
                });

                if (!invoices.length) {
                    return;
                }

                $('#batch_submit_btn').prop('disabled', true);
                $.ajax({
                    url: cfg.batchStoreUrl,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        invoices: invoices
                    }),
                    headers: csrfHeaders(),
                }).done(function(res) {
                    $('#batch_zone_c').addClass('d-none');
                    $('#batch_zone_a').addClass('d-none');
                    $('#batch_zone_b').addClass('d-none');
                    const $rb = emptyResultsTable();
                    if (!res.results || !res.results.length) {
                        $rb.append($('<tr>').append($('<td>', {
                            colspan: 4
                        }).text('No results')));
                    } else {
                        res.results.forEach(function(r) {
                            const idx = typeof r.index === 'number' ? r.index : 0;
                            const fileLabel = rowLabels[idx] || ('Row ' + (idx + 1));
                            const $rtr = $('<tr>');
                            $rtr.append($('<td>').text(fileLabel));
                            const invNo = r.invoice_number || '—';
                            $rtr.append($('<td>').text(invNo));
                            let st = r.status || '';
                            let badge = 'secondary';
                            if (st === 'created') {
                                badge = 'success';
                                st = 'Created';
                            } else if (st === 'validation_failed') {
                                badge = 'warning';
                                st = 'Validation';
                            } else if (st === 'failed') {
                                badge = 'danger';
                                st = 'Failed';
                            }
                            $rtr.append($('<td>').append($('<span>').addClass('badge badge-' + badge).text(st)));
                            let detail = '';
                            if (r.errors && r.errors.length) {
                                detail = r.errors.join('; ');
                            } else if (r.message) {
                                detail = r.message;
                            }
                            if (r.status === 'created') {
                                if (r.import_attachment_saved === true) {
                                    detail = (detail ? detail + ' ' : '') + 'Invoice Copy attachment saved.';
                                } else if (r.import_attachment_saved === false) {
                                    detail = (detail ? detail + ' ' : '') +
                                        'Warning: uploaded file was not attached (import session may have expired).';
                                }
                            }
                            const $dt = $('<td>').addClass('small');
                            if (r.status === 'created' && r.invoice_id) {
                                $dt.append($('<a>', {
                                    href: cfg.invoiceShowBase + '/' + encodeURIComponent(r.invoice_id),
                                    class: 'mr-2'
                                }).text('View'));
                            }
                            $dt.append($('<span>').text(detail));
                            $rtr.append($dt);
                            $rb.append($rtr);
                        });
                    }
                    $('#batch_zone_d').removeClass('d-none');
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Batch finished. Created: ' + (res.created_count || 0));
                    }
                }).fail(function(xhr) {
                    $('#batch_submit_btn').prop('disabled', false);
                    let msg = 'Batch request failed.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    }
                });
            });

            function emptyResultsTable() {
                const $tb = $('#batch_results_tbody');
                $tb.empty();
                return $tb;
            }
        });
    </script>
@endsection
