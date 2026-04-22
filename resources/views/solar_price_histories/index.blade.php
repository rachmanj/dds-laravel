@extends('layouts.main')

@section('title_page')
    Solar Price Histories
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Solar Price Histories</li>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @php
                $solarFiltersExpanded = request()->filled('filter_invoice_number')
                    || request()->filled('filter_period_overlap_from')
                    || request()->filled('filter_period_overlap_to');
            @endphp

            <div
                class="card card-outline card-primary @unless ($solarFiltersExpanded) collapsed-card @endunless"
                id="solar-filters-card">
                <div class="card-header">
                    <h3 class="card-title">Filters</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Show / hide filters">
                            <i
                                class="fas @if ($solarFiltersExpanded) fa-minus @else fa-plus @endif"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" @unless ($solarFiltersExpanded) style="display: none;" @endunless>
                    <form method="get" action="{{ route('solar-price-histories.index') }}">
                        <p class="text-muted small mb-2">
                            Filters rows whose <strong>saved period</strong> overlaps the dates below (inclusive). Leave either
                            field empty to leave that side open. Use a half-month (e.g. 1st–14th) as both values to match that
                            window.
                        </p>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filter_invoice_number">Invoice number</label>
                                    <input type="text" class="form-control" id="filter_invoice_number"
                                        name="filter_invoice_number" value="{{ request('filter_invoice_number') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filter_period_overlap_from">Period overlaps — from</label>
                                    <input type="date" class="form-control" id="filter_period_overlap_from"
                                        name="filter_period_overlap_from"
                                        value="{{ request('filter_period_overlap_from') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filter_period_overlap_to">Period overlaps — to</label>
                                    <input type="date" class="form-control" id="filter_period_overlap_to"
                                        name="filter_period_overlap_to" value="{{ request('filter_period_overlap_to') }}">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                        <a href="{{ route('solar-price-histories.index') }}" class="btn btn-default btn-sm">Reset</a>
                    </form>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header py-2">
                    <h3 class="card-title text-sm">Harga Solar Pinjaman — periode tahun
                        {{ $solarUnitPriceYearChart['year'] }}</h3>
                </div>
                <div class="card-body py-2">
                    @if (count($solarUnitPriceYearChart['labels']) === 0)
                        <p class="text-muted small mb-0">Belum ada data harga untuk tahun ini.</p>
                    @else
                        <p class="text-muted small mb-1">Urutan titik mengikuti period start (tahun kalender berjalan;
                            periode yang overlap tahun ini ikut ditampilkan).</p>
                        <div style="height: 12rem; position: relative;">
                            <canvas id="solarUnitPriceYearChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                    <h3 class="card-title mb-0">History list</h3>
                    <div>
                        @can('create-solar-price-histories')
                            <button type="button" class="btn btn-info btn-sm mr-1" id="getLastPriceBtn" data-toggle="modal"
                                data-target="#getLastPriceModal" title="This action will get last SOLAR invoice from PERTAMINA"
                                @if (empty($pertaminaId)) disabled @endif>
                                <i class="fas fa-magic"></i> Get last price
                            </button>
                            <a href="{{ route('solar-price-histories.create') }}"
                                class="btn btn-primary btn-sm @if (empty($pertaminaId)) disabled @endif">
                                <i class="fas fa-plus"></i> New record
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-0">
                    @if (empty($pertaminaId))
                        <div class="p-3">
                            <p class="text-warning mb-0">Supplier “PERTAMINA” was not found. Create/lookup rules cannot run.</p>
                        </div>
                    @endif
                    <div class="table-responsive p-0">
                        @if ($histories->isEmpty())
                            <p class="text-center text-muted py-4 mb-0">No records found.</p>
                        @else
                            <table id="solar-price-histories-table" class="table table-hover table-striped mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Period</th>
                                        <th>Unit price</th>
                                        <th>Invoice</th>
                                        <th>Line</th>
                                        <th>Created</th>
                                        <th style="width: 140px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($histories as $h)
                                        <tr>
                                            <td>{{ $h->id }}</td>
                                            <td
                                                data-order="{{ $h->period_start?->toDateString() ?? '' }}">
                                                {{ $h->period_start?->format('Y-m-d') }} →
                                                {{ $h->period_end?->format('Y-m-d') }}
                                            </td>
                                            <td data-order="{{ $h->unit_price }}">{{ number_format((float) $h->unit_price, 4) }}
                                            </td>
                                            <td>
                                                <a href="#"
                                                    class="js-solar-invoice-preview text-primary"
                                                    data-preview-url="{{ route('solar-price-histories.invoice-preview', $h) }}">{{ $h->invoice?->invoice_number ?? $h->invoice_id }}</a>
                                            </td>
                                            <td>
                                                <small
                                                    class="d-block text-muted">{{ $h->invoiceLineDetail?->description ?? '—' }}</small>
                                            </td>
                                            <td data-order="{{ $h->created_at?->timestamp ?? 0 }}">
                                                {{ $h->created_at?->format('Y-m-d H:i') }}
                                            </td>
                                            <td>
                                                <a class="btn btn-xs btn-info"
                                                    href="{{ route('solar-price-histories.show', $h) }}">View</a>
                                                @can('edit-solar-price-histories')
                                                    <a class="btn btn-xs btn-warning"
                                                        href="{{ route('solar-price-histories.edit', $h) }}">Edit</a>
                                                @endcan
                                                @can('delete-solar-price-histories')
                                                    <form method="post"
                                                        action="{{ route('solar-price-histories.destroy', $h) }}"
                                                        class="d-inline" onsubmit="return confirm('Delete this record?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="invoicePreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invoice</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="inv_preview_error" class="alert alert-warning d-none"></div>
                    <div id="inv_preview_loading" class="text-center py-3 d-none">
                        <i class="fas fa-spinner fa-spin"></i> Loading…
                    </div>
                    <div id="inv_preview_body" class="d-none">
                        <h6 class="text-muted">Header</h6>
                        <table class="table table-sm table-bordered mb-3">
                            <tr>
                                <th style="width: 35%;">Number / Faktur</th>
                                <td id="inv_pr_invoice_no"></td>
                            </tr>
                            <tr>
                                <th>Supplier</th>
                                <td id="inv_pr_supplier"></td>
                            </tr>
                            <tr>
                                <th>Type / Status / Location</th>
                                <td id="inv_pr_type_status"></td>
                            </tr>
                            <tr>
                                <th>Invoice / Receive date</th>
                                <td id="inv_pr_dates"></td>
                            </tr>
                            <tr>
                                <th>PO / SAP doc</th>
                                <td id="inv_pr_po_sap"></td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td id="inv_pr_amount"></td>
                            </tr>
                        </table>
                        <h6 class="text-muted">SOLAR line (this history)</h6>
                        <table class="table table-sm table-bordered mb-3">
                            <tr>
                                <th style="width: 35%;">Line</th>
                                <td id="inv_pr_line"></td>
                            </tr>
                        </table>
                        <h6 class="text-muted">Remarks</h6>
                        <div class="border rounded p-2 small" style="max-height: 160px; overflow-y: auto; white-space: pre-wrap;" id="inv_pr_remarks"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <a id="inv_pr_open_full" href="#" class="btn btn-primary d-none" target="_blank" rel="noopener">Open full
                        invoice</a>
                </div>
            </div>
        </div>
    </div>

    @can('create-solar-price-histories')
        <div class="modal fade" id="getLastPriceModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form method="post" action="{{ route('solar-price-histories.store') }}">
                        @csrf
                        <input type="hidden" name="invoice_id" id="gpl_invoice_id">
                        <input type="hidden" name="invoice_line_detail_id" id="gpl_line_id">
                        <input type="hidden" name="unit_price" id="gpl_unit_price">
                        <input type="hidden" name="quantity" id="gpl_quantity">
                        <input type="hidden" name="amount" id="gpl_amount">
                        <div class="modal-header">
                            <h5 class="modal-title">Get last price from PERTAMINA</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-2">
                                Periods are often <strong>1st–14th</strong> and <strong>15th–end of month</strong> (not
                                1–15 / 16–end). Use the quick actions below, or enter dates manually.
                            </p>
                            <div id="gpl_error" class="alert alert-warning d-none" role="alert"></div>
                            <div id="gpl_loading" class="text-center py-3 d-none">
                                <i class="fas fa-spinner fa-spin"></i> Loading last invoice…
                            </div>
                            <div id="gpl_source" class="d-none">
                                <h6>Source invoice &amp; line</h6>
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th style="width: 35%;">Invoice #</th>
                                        <td id="gpl_d_invoice_number"></td>
                                    </tr>
                                    <tr>
                                        <th>Faktur / PO</th>
                                        <td id="gpl_d_faktur"></td>
                                    </tr>
                                    <tr>
                                        <th>Dates</th>
                                        <td id="gpl_d_dates"></td>
                                    </tr>
                                    <tr>
                                        <th>Line</th>
                                        <td id="gpl_d_line"></td>
                                    </tr>
                                    <tr>
                                        <th>Quantity / amount</th>
                                        <td id="gpl_d_qty"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="form-row align-items-end">
                                <div class="form-group col-md-4">
                                    <label for="gpl_month_quick">Quick fill (month)</label>
                                    <input type="month" class="form-control" id="gpl_month_quick"
                                        value="{{ now()->format('Y-m') }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="gpl_btn_1_14">Set
                                        1st–14th</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="gpl_btn_15_eom">Set
                                        15th–end</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="gpl_period_start">Period start <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="period_start" id="gpl_period_start" required>
                            </div>
                            <div class="form-group">
                                <label for="gpl_period_end">Period end <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="period_end" id="gpl_period_end" required>
                            </div>
                            <div class="form-group">
                                <label for="gpl_notes">Notes (optional)</label>
                                <textarea class="form-control" name="notes" id="gpl_notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="gpl_save_btn" disabled>Save
                                history</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script>
        (function() {
            const solarY = @json($solarUnitPriceYearChart);
            const cEl = document.getElementById('solarUnitPriceYearChart');
            if (cEl && typeof Chart !== 'undefined' && solarY.labels && solarY.labels.length) {
                new Chart(cEl, {
                    type: 'line',
                    data: {
                        labels: solarY.labels,
                        datasets: [{
                            data: solarY.values,
                            borderColor: 'rgba(240, 173, 78, 1)',
                            backgroundColor: 'rgba(240, 173, 78, 0.06)',
                            borderWidth: 1.5,
                            fill: true,
                            lineTension: 0.35,
                            pointRadius: 2,
                            pointBackgroundColor: 'rgba(240, 173, 78, 1)',
                        }]
                    },
                    options: {
                        legend: {
                            display: false
                        },
                        responsive: true,
                        maintainAspectRatio: false,
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(tooltipItem) {
                                    const n = typeof tooltipItem.yLabel === 'number' ? tooltipItem.yLabel : parseFloat(
                                        tooltipItem.yLabel, 10);
                                    if (isNaN(n)) {
                                        return '';
                                    }
                                    return 'Unit price: ' + n.toLocaleString('en-US', {
                                        maximumFractionDigits: 4
                                    });
                                }
                            }
                        },
                        hover: {
                            mode: 'nearest',
                            intersect: false
                        },
                        scales: {
                            xAxes: [{
                                gridLines: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 0,
                                    fontSize: 10
                                }
                            }],
                            yAxes: [{
                                gridLines: {
                                    color: 'rgba(0,0,0,0.05)'
                                },
                                ticks: {
                                    fontSize: 10
                                }
                            }]
                        }
                    }
                });
            }
        })();
    </script>
    <script>
        $(function() {
            var $t = $('#solar-price-histories-table');
            if ($t.length && $.fn.dataTable) {
                $t.DataTable({
                    responsive: true,
                    autoWidth: false,
                    pageLength: 25,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, 'All']
                    ],
                    order: [
                        [0, 'desc']
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: 6
                    }],
                    language: {
                        search: 'Search table:',
                        lengthMenu: 'Show _MENU_ rows',
                        info: 'Showing _START_ to _END_ of _TOTAL_',
                        infoEmpty: 'No rows',
                        infoFiltered: '(filtered from _MAX_ total)',
                        zeroRecords: 'No matching records',
                    },
                });
            }
        });
    </script>
@endsection

@can('create-solar-price-histories')
    @push('js')
        <script>
            (function() {
                const url = @json(route('solar-price-histories.fetch-last'));
                const modal = $('#getLastPriceModal');
                const errBox = document.getElementById('gpl_error');
                const loadBox = document.getElementById('gpl_loading');
                const srcBox = document.getElementById('gpl_source');
                const saveBtn = document.getElementById('gpl_save_btn');

                function eomDay(y, m) {
                    return new Date(Number(y), Number(m), 0).getDate();
                }

                function setQuick(half) {
                    const m = document.getElementById('gpl_month_quick').value;
                    if (!m) return;
                    const [y, mo] = m.split('-');
                    const end = eomDay(y, mo);
                    const ps = document.getElementById('gpl_period_start');
                    const pe = document.getElementById('gpl_period_end');
                    if (half === '1') {
                        ps.value = y + '-' + mo + '-01';
                        pe.value = y + '-' + mo + '-14';
                    } else {
                        ps.value = y + '-' + mo + '-15';
                        pe.value = y + '-' + mo + '-' + String(end).padStart(2, '0');
                    }
                }
                document.getElementById('gpl_btn_1_14')?.addEventListener('click', function() {
                    setQuick('1');
                });
                document.getElementById('gpl_btn_15_eom')?.addEventListener('click', function() {
                    setQuick('2');
                });

                modal.on('show.bs.modal', function() {
                    errBox.classList.add('d-none');
                    errBox.textContent = '';
                    loadBox.classList.remove('d-none');
                    srcBox.classList.add('d-none');
                    saveBtn.disabled = true;
                    document.getElementById('gpl_notes').value = '';
                    document.getElementById('gpl_period_start').value = '';
                    document.getElementById('gpl_period_end').value = '';
                    document.getElementById('gpl_invoice_id').value = '';
                    document.getElementById('gpl_line_id').value = '';
                    document.getElementById('gpl_unit_price').value = '';
                    document.getElementById('gpl_quantity').value = '';
                    document.getElementById('gpl_amount').value = '';
                    fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => {
                            if (!r.ok) {
                                return r.json().then(j => {
                                    throw new Error(j.message || 'Request failed');
                                });
                            }
                            return r.json();
                        })
                        .then(data => {
                            loadBox.classList.add('d-none');
                            srcBox.classList.remove('d-none');
                            document.getElementById('gpl_invoice_id').value = data.invoice.id;
                            document.getElementById('gpl_line_id').value = data.line.id;
                            document.getElementById('gpl_unit_price').value = data.resolved_unit_price;
                            document.getElementById('gpl_quantity').value = data.line.quantity ?? '';
                            document.getElementById('gpl_amount').value = data.line.amount ?? '';
                            document.getElementById('gpl_d_invoice_number').textContent = data.invoice
                                .invoice_number;
                            document.getElementById('gpl_d_faktur').textContent = (data.invoice
                                    .faktur_no || '—') +
                                ' / ' + (data.invoice.po_no || '—');
                            document.getElementById('gpl_d_dates').textContent = 'Inv: ' + (data.invoice
                                .invoice_date || '—') + ' · Recv: ' + (data.invoice.receive_date || '—') +
                                ' · ' + (data.invoice.currency || '') + ' ' + (data.invoice.amount || '');
                            document.getElementById('gpl_d_line').textContent = '#' + data.line.line_no + ' ' +
                                data.line.description;
                            document.getElementById('gpl_d_qty').textContent = (data.line.quantity ?? '—') +
                                ' / ' + (data.line.unit_price ? data.line.unit_price : '—') + ' / ' + (data.line
                                    .amount ?? '—');
                            saveBtn.disabled = false;
                        })
                        .catch(e => {
                            loadBox.classList.add('d-none');
                            errBox.classList.remove('d-none');
                            errBox.textContent = e.message;
                        });
                });
            })();
        </script>
    @endpush
@endcan

@push('js')
    <script>
        (function() {
            $(document).on('click', '.js-solar-invoice-preview', function(e) {
                e.preventDefault();
                const url = $(this).data('preview-url');
                const $modal = $('#invoicePreviewModal');
                const $err = $('#inv_preview_error');
                const $load = $('#inv_preview_loading');
                const $body = $('#inv_preview_body');
                const $open = $('#inv_pr_open_full');
                $err.addClass('d-none').text('');
                $body.addClass('d-none');
                $load.removeClass('d-none');
                $open.addClass('d-none').attr('href', '#');
                $modal.modal('show');
                fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(r) {
                        if (!r.ok) {
                            return r.json().then(function(j) {
                                throw new Error(j.message || 'Request failed');
                            });
                        }
                        return r.json();
                    })
                    .then(function(data) {
                        $load.addClass('d-none');
                        var inv = data.invoice;
                        $('#inv_pr_invoice_no').text((inv.invoice_number || '—') + (inv.faktur_no ? ' / ' + inv
                            .faktur_no : ''));
                        $('#inv_pr_supplier').text(inv.supplier ? (inv.supplier.name + (inv.supplier.sap_code ?
                                ' (' + inv.supplier.sap_code + ')' : '')) : '—');
                        $('#inv_pr_type_status').text((inv.type || '—') + ' · ' + (inv.status || '—') + ' · ' + (inv
                            .cur_loc || '—') + ' · Pay: ' + (inv.payment_status || '—'));
                        $('#inv_pr_dates').text('Inv: ' + (inv.invoice_date || '—') + ' · Recv: ' + (inv
                            .receive_date || '—'));
                        $('#inv_pr_po_sap').text((inv.po_no || '—') + ' / ' + (inv.sap_doc || '—'));
                        $('#inv_pr_amount').text((inv.currency || '') + ' ' + (inv.amount || '—'));
                        var line = data.line;
                        if (line) {
                            $('#inv_pr_line').text('#' + line.line_no + ' ' + line.description + ' · Qty ' + (line
                                    .quantity ?? '—') + ' · Unit ' + (line.unit_price ?? '—') + ' · Amt ' + (line
                                    .amount ?? '—') + (line.source ? ' · ' + line.source : ''));
                        } else {
                            $('#inv_pr_line').text('—');
                        }
                        $('#inv_pr_remarks').text(inv.remarks && inv.remarks.length ? inv.remarks : '—');
                        if (data.open_url) {
                            $open.attr('href', data.open_url).removeClass('d-none');
                        }
                        $body.removeClass('d-none');
                    })
                    .catch(function(err) {
                        $load.addClass('d-none');
                        $err.removeClass('d-none').text(err.message || 'Could not load invoice.');
                    });
            });
        })();
    </script>
@endpush
