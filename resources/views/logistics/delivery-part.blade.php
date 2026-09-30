@extends('layouts.main')

@section('title_page')
    Delivery Part
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Delivery Part</li>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="mb-0">Delivery Part</h4>
                    <p class="text-muted small mb-0">Data ITO dari SAP (live) digabung dengan isian manual site.</p>
                </div>
            </div>

            @if ($sapError)
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $sapError }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <ul class="nav nav-tabs mb-3" id="delivery-part-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-delivery-part-link" data-toggle="tab" href="#tab-delivery-part"
                        role="tab">Delivery Part</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-spb-link" data-toggle="tab" href="#tab-spb" role="tab">Input SPB
                        Pengiriman</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-cancel-history-link" data-toggle="tab" href="#tab-cancel-history"
                        role="tab">Riwayat pembatalan</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-delivery-part" role="tabpanel">

            <div class="card card-outline card-info mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filter</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('logistics.delivery-part.index') }}" id="delivery-part-filter-form">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="project">Site</label>
                                    <select class="form-control" id="project" name="project" required>
                                        <option value="">— Pilih site —</option>
                                        @foreach ($sites as $site)
                                            <option value="{{ $site['code'] }}" data-project-id="{{ $site['id'] }}"
                                                @selected($selectedProject === $site['code'])>
                                                {{ $site['code'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="from_date">Dari Tanggal ITO</label>
                                    <input type="date" class="form-control" id="from_date" name="from_date"
                                        value="{{ $fromDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="to_date">Sampai Tanggal ITO</label>
                                    <input type="date" class="form-control" id="to_date" name="to_date"
                                        value="{{ $toDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="table_search">Pencarian</label>
                                    <input type="text" class="form-control" id="table_search" placeholder="Cari di tabel...">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                        <button type="button" class="btn btn-default btn-sm" id="btn_refresh">
                            <i class="fas fa-sync"></i> Refresh SAP
                        </button>
                        @can('export-delivery-part')
                            <button type="button" class="btn btn-success btn-sm float-right" id="export_excel">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                        @endcan
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-responsive">
                    <table id="delivery-part-table" class="table table-bordered table-striped table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th>TANGGAL RECEIVED</th>
                                <th>Supplier</th>
                                <th>PO Number</th>
                                <th>No. SPB</th>
                                <th>NO ITO</th>
                                <th>No Unit</th>
                                <th>Parts Number</th>
                                <th>Descriptions</th>
                                <th>QTY</th>
                                <th>UOM</th>
                                <th>Remarks Barang</th>
                                <th>Tgl Delivery</th>
                                <th>Transporter</th>
                                <th>Unit &amp; No Kendaraan</th>
                                <th>Ekspedisi</th>
                                <th>Tgl ITI</th>
                                <th>NO. ITI</th>
                                <th>Keterangan</th>
                                @if (auth()->user()?->can('edit-delivery-part') || auth()->user()?->can('cancel-ito'))
                                    <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

                </div>

                <div class="tab-pane fade" id="tab-spb" role="tabpanel">
                    <div class="card card-outline card-info mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-filter"></i> Filter Daftar SPB</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="spb_filter_project">Site</label>
                                        <select class="form-control" id="spb_filter_project">
                                            <option value="">— Semua site —</option>
                                            @foreach ($sites as $site)
                                                <option value="{{ $site['id'] }}">{{ $site['code'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="spb_from_date">Dari Tanggal</label>
                                        <input type="date" class="form-control" id="spb_from_date">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="spb_to_date">Sampai Tanggal</label>
                                        <input type="date" class="form-control" id="spb_to_date">
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary btn-sm mb-3" id="spb_filter_apply">
                                        <i class="fas fa-search"></i> Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @can('edit-delivery-part')
                        <div class="card card-primary mb-3">
                            <div class="card-header">
                                <h3 class="card-title" id="spb-form-title">Input SPB Pengiriman</h3>
                            </div>
                            <div class="card-body">
                                <form id="spb-form">
                                    <input type="hidden" id="spb_id" value="">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="spb_project_id">Site</label>
                                                <select class="form-control" id="spb_project_id" name="project_id">
                                                    <option value="">— Pilih site —</option>
                                                    @foreach ($sites as $site)
                                                        <option value="{{ $site['id'] }}">{{ $site['code'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="spb_no_spb">No. SPB <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="spb_no_spb" name="no_spb"
                                                    required maxlength="100">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="spb_tanggal">Tanggal</label>
                                                <input type="date" class="form-control" id="spb_tanggal" name="tanggal">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="spb_remarks">Keterangan</label>
                                                <input type="text" class="form-control" id="spb_remarks" name="remarks">
                                            </div>
                                        </div>
                                    </div>

                                    <h6 class="mt-2">Barang</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="spb-items-table">
                                            <thead>
                                                <tr>
                                                    <th>Part Number</th>
                                                    <th>Description</th>
                                                    <th>QTY</th>
                                                    <th>UOM</th>
                                                    <th>Remarks</th>
                                                    <th width="50"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="spb-items-body"></tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-default btn-sm" id="spb-add-row">
                                        <i class="fas fa-plus"></i> Tambah Baris
                                    </button>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-save"></i> Simpan SPB
                                        </button>
                                        <button type="button" class="btn btn-default btn-sm d-none" id="spb-cancel-edit">
                                            Batal Edit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endcan

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Daftar SPB</h3>
                        </div>
                        <div class="card-body table-responsive">
                            <table id="spb-list-table" class="table table-bordered table-striped table-sm" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Site</th>
                                        <th>No. SPB</th>
                                        <th>Tanggal</th>
                                        <th>Jumlah Baris</th>
                                        <th>Keterangan</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <div class="modal fade" id="spb-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Detail SPB</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body" id="spb-detail-body"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-cancel-history" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Riwayat pembatalan ITO</h3>
                        </div>
                        <div class="card-body table-responsive">
                            <table id="cancel-history-table" class="table table-bordered table-striped table-sm"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Site</th>
                                        <th>ITO</th>
                                        <th>Alasan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @can('cancel-ito')
        <div class="modal fade" id="cancel-ito-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Batalkan ITO</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="cancel-ito-form">
                        <div class="modal-body">
                            <input type="hidden" id="cancel_doc_entry" name="doc_entry">
                            <input type="hidden" id="cancel_ito_no" name="ito_no">
                            <input type="hidden" id="cancel_project_id" name="project_id">
                            <input type="hidden" id="cancel_item_code" name="item_code">
                            <input type="hidden" id="cancel_unit_no" name="unit_no">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Pembatalan akan membalik pergerakan stok di SAP. Hanya ITO yang belum memiliki ITI yang
                                dapat dibatalkan.
                            </div>
                            <dl class="row small mb-3">
                                <dt class="col-sm-4">No ITO</dt>
                                <dd class="col-sm-8" id="cancel_summary_ito">-</dd>
                                <dt class="col-sm-4">Parts Number</dt>
                                <dd class="col-sm-8" id="cancel_summary_part">-</dd>
                                <dt class="col-sm-4">Description</dt>
                                <dd class="col-sm-8" id="cancel_summary_desc">-</dd>
                                <dt class="col-sm-4">QTY / UOM</dt>
                                <dd class="col-sm-8" id="cancel_summary_qty">-</dd>
                                <dt class="col-sm-4">No Unit</dt>
                                <dd class="col-sm-8" id="cancel_summary_unit">-</dd>
                                <dt class="col-sm-4">Tujuan (warehouse)</dt>
                                <dd class="col-sm-8" id="cancel_summary_dest">-</dd>
                            </dl>
                            <div class="form-group">
                                <label for="cancel_reason">Alasan pembatalan <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="cancel_reason" name="reason" rows="3" required
                                    minlength="10" placeholder="Minimal 10 karakter"></textarea>
                            </div>
                            <div id="cancel-result-alert" class="alert d-none" role="alert"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-danger" id="cancel-ito-submit">
                                <i class="fas fa-ban"></i> Konfirmasi pembatalan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('edit-delivery-part')
        <div class="modal fade" id="edit-entry-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit kolom manual</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="edit-entry-form">
                        <div class="modal-body">
                            <input type="hidden" id="entry_id" name="entry_id">
                            <input type="hidden" id="row_ito_no" name="ito_no">
                            <input type="hidden" id="row_item_code" name="item_code">
                            <input type="hidden" id="row_unit_no" name="unit_no">
                            <p class="small text-muted mb-3" id="ito_sap_hint"></p>
                            <div class="form-group">
                                <label for="ito_no_override">NO ITO (koreksi)</label>
                                <input type="text" class="form-control" id="ito_no_override" name="ito_no_override">
                            </div>
                            <div class="form-group">
                                <label for="no_spb">No. SPB</label>
                                <input type="text" class="form-control" id="no_spb" name="no_spb">
                            </div>
                            <div class="form-group">
                                <label for="remarks_barang">Remarks Barang</label>
                                <textarea class="form-control" id="remarks_barang" name="remarks_barang" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="tgl_delivery">Tgl Delivery</label>
                                <input type="date" class="form-control" id="tgl_delivery" name="tgl_delivery">
                            </div>
                            <div class="form-group">
                                <label for="transporter">Transporter</label>
                                <input type="text" class="form-control" id="transporter" name="transporter">
                            </div>
                            <div class="form-group">
                                <label for="unit_kendaraan">Unit &amp; No Kendaraan</label>
                                <input type="text" class="form-control" id="unit_kendaraan" name="unit_kendaraan">
                            </div>
                            <div class="form-group">
                                <label for="ekspedisi">Ekspedisi Pengirim</label>
                                <select class="form-control" id="ekspedisi" name="ekspedisi">
                                    <option value="">—</option>
                                    @foreach ($ekspedisiOptions as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script>
        $(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            const canEdit = @json(auth()->user()?->can('edit-delivery-part') ?? false);
            const canCancel = @json(auth()->user()?->can('cancel-ito') ?? false);

            function filterParams() {
                return {
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    project: $('#project').val(),
                };
            }

            const columns = [
                { data: 'tanggal_received', name: 'tanggal_received', defaultContent: '-' },
                { data: 'supplier', name: 'supplier', defaultContent: '-' },
                { data: 'po_number', name: 'po_number', defaultContent: '-' },
                { data: 'no_spb', name: 'no_spb', defaultContent: '-' },
                { data: 'no_ito_display', name: 'no_ito', orderable: false, searchable: true, defaultContent: '-' },
                { data: 'no_unit', name: 'no_unit', defaultContent: '-' },
                { data: 'parts_number', name: 'parts_number', defaultContent: '-' },
                { data: 'descriptions', name: 'descriptions', defaultContent: '-' },
                { data: 'qty_display', name: 'qty', searchable: false },
                { data: 'uom', name: 'uom', defaultContent: '-' },
                { data: 'remarks_barang', name: 'remarks_barang', defaultContent: '-' },
                { data: 'tgl_delivery', name: 'tgl_delivery', defaultContent: '-' },
                { data: 'transporter', name: 'transporter', defaultContent: '-' },
                { data: 'unit_kendaraan', name: 'unit_kendaraan', defaultContent: '-' },
                { data: 'ekspedisi', name: 'ekspedisi', defaultContent: '-' },
                { data: 'tgl_iti', name: 'tgl_iti', defaultContent: '-' },
                { data: 'no_iti', name: 'no_iti', defaultContent: '-' },
                { data: 'keterangan_display', name: 'keterangan', orderable: false, searchable: false, defaultContent: '' },
            ];

            if (canEdit || canCancel) {
                columns.push({ data: 'actions', name: 'actions', orderable: false, searchable: false });
            }

            let table = null;

            function initTable() {
                if (!$('#project').val()) {
                    return;
                }

                if (table) {
                    table.destroy();
                    $('#delivery-part-table').empty().append($('#delivery-part-table').data('original-head'));
                }

                table = $('#delivery-part-table').DataTable({
                    processing: true,
                    serverSide: true,
                    scrollX: true,
                    ajax: {
                        url: "{{ route('logistics.delivery-part.data') }}",
                        data: function(d) {
                            Object.assign(d, filterParams());
                        },
                        error: function(xhr) {
                            const msg = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'Gagal memuat data.';
                            alert(msg);
                        }
                    },
                    columns: columns,
                    order: [[0, 'desc']],
                    pageLength: 25,
                });
            }

            $('#delivery-part-table').data('original-head', $('#delivery-part-table thead').clone());

            $('#table_search').on('keyup', function() {
                if (table) {
                    table.search(this.value).draw();
                }
            });

            @if ($selectedProject)
                initTable();
            @endif

            $('#btn_refresh').on('click', function() {
                $.post("{{ route('logistics.delivery-part.refresh') }}", Object.assign({ _token: csrfToken }, filterParams()))
                    .done(function() {
                        if (table) {
                            table.ajax.reload();
                        }
                        alert('Data SAP di-refresh.');
                    })
                    .fail(function(xhr) {
                        alert(xhr.responseJSON?.message || 'Gagal refresh cache.');
                    });
            });

            $('#export_excel').on('click', function() {
                window.location.href = "{{ route('logistics.delivery-part.export') }}?" + new URLSearchParams(filterParams()).toString();
            });

            @can('edit-delivery-part')
                $(document).on('click', '.btn-edit-entry', function() {
                    const row = $(this).data('row');
                    $('#entry_id').val(row.entry_id || '');
                    $('#row_ito_no').val(row.ito_no || '');
                    $('#row_item_code').val(row.item_code || '');
                    $('#row_unit_no').val(row.unit_no || '');
                    $('#ito_no_override').val(row.ito_no_override || '');
                    $('#no_spb').val(row.no_spb || '');
                    $('#remarks_barang').val(row.remarks_barang || '');
                    $('#tgl_delivery').val(row.tgl_delivery || '');
                    $('#transporter').val(row.transporter || '');
                    $('#unit_kendaraan').val(row.unit_kendaraan || '');
                    $('#ekspedisi').val(row.ekspedisi || '');
                    if (row.ito_no_sap) {
                        $('#ito_sap_hint').text('ITO No (SAP): ' + row.ito_no_sap);
                    } else {
                        $('#ito_sap_hint').text('');
                    }
                    $('#edit-entry-modal').modal('show');
                });

                $('#edit-entry-form').on('submit', function(e) {
                    e.preventDefault();
                    const entryId = $('#entry_id').val();
                    const payload = {
                        _token: csrfToken,
                        ito_no_override: $('#ito_no_override').val(),
                        no_spb: $('#no_spb').val(),
                        remarks_barang: $('#remarks_barang').val(),
                        tgl_delivery: $('#tgl_delivery').val(),
                        transporter: $('#transporter').val(),
                        unit_kendaraan: $('#unit_kendaraan').val(),
                        ekspedisi: $('#ekspedisi').val(),
                    };

                    let url;
                    if (entryId) {
                        url = "{{ url('logistics/delivery-part/entry') }}/" + entryId;
                        $.ajax({ url: url, method: 'PATCH', data: payload })
                            .done(function() {
                                $('#edit-entry-modal').modal('hide');
                                if (table) table.ajax.reload(null, false);
                            })
                            .fail(function(xhr) {
                                alert(xhr.responseJSON?.message || 'Gagal menyimpan.');
                            });
                    } else {
                        url = "{{ route('logistics.delivery-part.entry.store') }}";
                        payload.project_code = $('#project').val();
                        payload.ito_no = $('#row_ito_no').val();
                        payload.item_code = $('#row_item_code').val();
                        payload.unit_no = $('#row_unit_no').val();
                        $.post(url, payload)
                            .done(function() {
                                $('#edit-entry-modal').modal('hide');
                                if (table) table.ajax.reload(null, false);
                            })
                            .fail(function(xhr) {
                                const errors = xhr.responseJSON?.errors;
                                if (errors) {
                                    alert(Object.values(errors).flat().join('\n'));
                                } else {
                                    alert(xhr.responseJSON?.message || 'Gagal menyimpan.');
                                }
                            });
                    }
                });
            @endcan

            let spbTable = null;

            function spbFilterParams() {
                return {
                    project_id: $('#spb_filter_project').val(),
                    from_date: $('#spb_from_date').val(),
                    to_date: $('#spb_to_date').val(),
                };
            }

            function initSpbTable() {
                if (spbTable) {
                    spbTable.ajax.reload();
                    return;
                }
                spbTable = $('#spb-list-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('logistics.delivery-part.spb.data') }}",
                        data: function(d) {
                            Object.assign(d, spbFilterParams());
                        },
                    },
                    columns: [
                        { data: 'project_code', name: 'project_code', defaultContent: '-' },
                        { data: 'no_spb', name: 'no_spb' },
                        { data: 'tanggal_display', name: 'tanggal' },
                        { data: 'items_count', name: 'items_count', searchable: false },
                        { data: 'remarks', name: 'remarks', defaultContent: '-' },
                        { data: 'created_by_name', name: 'created_by_name', orderable: false },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false },
                    ],
                    order: [[2, 'desc']],
                    pageLength: 25,
                });
            }

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#tab-spb') {
                    initSpbTable();
                }
            });

            $('#spb_filter_apply').on('click', function() {
                if (spbTable) {
                    spbTable.ajax.reload();
                } else {
                    initSpbTable();
                }
            });

            function spbItemRowHtml(data) {
                data = data || {};
                return '<tr class="spb-item-row">' +
                    '<td><input type="text" class="form-control form-control-sm spb-part" value="' + (data.part_number || '') + '"></td>' +
                    '<td><input type="text" class="form-control form-control-sm spb-desc" value="' + (data.description || '') + '"></td>' +
                    '<td><input type="number" step="any" min="0" class="form-control form-control-sm spb-qty" value="' + (data.qty ?? '') + '"></td>' +
                    '<td><input type="text" class="form-control form-control-sm spb-uom" value="' + (data.uom || '') + '"></td>' +
                    '<td><input type="text" class="form-control form-control-sm spb-item-remarks" value="' + (data.remarks || '') + '"></td>' +
                    '<td><button type="button" class="btn btn-xs btn-danger spb-remove-row"><i class="fas fa-times"></i></button></td>' +
                    '</tr>';
            }

            function resetSpbForm() {
                $('#spb_id').val('');
                $('#spb-form-title').text('Input SPB Pengiriman');
                $('#spb-cancel-edit').addClass('d-none');
                $('#spb-form')[0].reset();
                $('#spb-items-body').empty();
                $('#spb-items-body').append(spbItemRowHtml({}));
            }

            @can('edit-delivery-part')
                $('#spb-add-row').on('click', function() {
                    $('#spb-items-body').append(spbItemRowHtml({}));
                });

                $(document).on('click', '.spb-remove-row', function() {
                    if ($('#spb-items-body tr').length > 1) {
                        $(this).closest('tr').remove();
                    }
                });

                resetSpbForm();

                $('#spb-cancel-edit').on('click', function() {
                    resetSpbForm();
                });

                $('#spb-form').on('submit', function(e) {
                    e.preventDefault();
                    const items = [];
                    $('#spb-items-body tr').each(function() {
                        items.push({
                            part_number: $(this).find('.spb-part').val(),
                            description: $(this).find('.spb-desc').val(),
                            qty: $(this).find('.spb-qty').val(),
                            uom: $(this).find('.spb-uom').val(),
                            remarks: $(this).find('.spb-item-remarks').val(),
                        });
                    });

                    const payload = {
                        _token: csrfToken,
                        project_id: $('#spb_project_id').val() || null,
                        no_spb: $('#spb_no_spb').val(),
                        tanggal: $('#spb_tanggal').val(),
                        remarks: $('#spb_remarks').val(),
                        items: items,
                    };

                    const spbId = $('#spb_id').val();
                    let url = "{{ route('logistics.delivery-part.spb.store') }}";
                    let method = 'POST';
                    if (spbId) {
                        url = "{{ url('logistics/delivery-part/spb') }}/" + spbId;
                        method = 'PUT';
                    }

                    $.ajax({ url: url, method: method, data: payload })
                        .done(function() {
                            resetSpbForm();
                            if (spbTable) spbTable.ajax.reload(null, false);
                            alert('SPB berhasil disimpan.');
                        })
                        .fail(function(xhr) {
                            const errors = xhr.responseJSON?.errors;
                            if (errors) {
                                alert(Object.values(errors).flat().join('\n'));
                            } else {
                                alert(xhr.responseJSON?.message || 'Gagal menyimpan SPB.');
                            }
                        });
                });

                $(document).on('click', '.btn-spb-edit', function() {
                    const id = $(this).data('id');
                    $.get("{{ url('logistics/delivery-part/spb') }}/" + id)
                        .done(function(data) {
                            $('#spb_id').val(data.id);
                            $('#spb-form-title').text('Ubah SPB');
                            $('#spb-cancel-edit').removeClass('d-none');
                            $('#spb_project_id').val(data.project_id || '');
                            $('#spb_no_spb').val(data.no_spb);
                            $('#spb_tanggal').val(data.tanggal || '');
                            $('#spb_remarks').val(data.remarks || '');
                            $('#spb-items-body').empty();
                            (data.items || []).forEach(function(item) {
                                $('#spb-items-body').append(spbItemRowHtml(item));
                            });
                            if ((data.items || []).length === 0) {
                                $('#spb-items-body').append(spbItemRowHtml({}));
                            }
                            $('a[href="#tab-spb"]').tab('show');
                        });
                });

                $(document).on('click', '.btn-spb-delete', function() {
                    if (!confirm('Hapus SPB ini?')) return;
                    const id = $(this).data('id');
                    $.ajax({
                        url: "{{ url('logistics/delivery-part/spb') }}/" + id,
                        method: 'DELETE',
                        data: { _token: csrfToken },
                    })
                        .done(function() {
                            if (spbTable) spbTable.ajax.reload(null, false);
                        })
                        .fail(function(xhr) {
                            alert(xhr.responseJSON?.message || 'Gagal menghapus.');
                        });
                });
            @endcan

            let cancelHistoryTable = null;

            function initCancelHistoryTable() {
                if (cancelHistoryTable) {
                    return;
                }
                cancelHistoryTable = $('#cancel-history-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('logistics.delivery-part.cancel.data') }}",
                        data: function(d) {
                            const projectSelect = $('#project option:selected');
                            const projectId = projectSelect.data('project-id');
                            if (projectId) {
                                d.project_id = projectId;
                            }
                        },
                    },
                    columns: [
                        { data: 'requested_at_display', name: 'requested_at' },
                        { data: 'user_name', name: 'user_name', orderable: false },
                        { data: 'project_code', name: 'project_code' },
                        { data: 'ito_no', name: 'ito_no' },
                        { data: 'reason', name: 'reason' },
                        { data: 'status_label', name: 'status' },
                    ],
                    order: [[0, 'desc']],
                    pageLength: 25,
                });
            }

            $('a[href="#tab-cancel-history"]').on('shown.bs.tab', function() {
                initCancelHistoryTable();
                if (cancelHistoryTable) {
                    cancelHistoryTable.ajax.reload();
                }
            });

            @can('cancel-ito')
                $(document).on('click', '.btn-cancel-ito', function() {
                    const row = $(this).data('row');
                    $('#cancel_doc_entry').val(row.doc_entry || '');
                    $('#cancel_ito_no').val(row.ito_no || '');
                    $('#cancel_project_id').val(row.project_id || '');
                    $('#cancel_item_code').val(row.item_code || '');
                    $('#cancel_unit_no').val(row.unit_no || '');
                    $('#cancel_summary_ito').text(row.no_ito || row.ito_no || '-');
                    $('#cancel_summary_part').text(row.parts_number || '-');
                    $('#cancel_summary_desc').text(row.descriptions || '-');
                    $('#cancel_summary_qty').text((row.qty != null ? row.qty : '-') + ' / ' + (row.uom || '-'));
                    $('#cancel_summary_unit').text(row.no_unit || '-');
                    $('#cancel_summary_dest').text(row.to_warehouse || '-');
                    $('#cancel_reason').val('');
                    $('#cancel-result-alert').addClass('d-none').removeClass('alert-success alert-danger');
                    $('#cancel-ito-submit').prop('disabled', false);
                    $('#cancel-ito-modal').modal('show');
                });

                $('#cancel-ito-form').on('submit', function(e) {
                    e.preventDefault();
                    const reason = $('#cancel_reason').val().trim();
                    if (reason.length < 10) {
                        alert('Alasan pembatalan wajib minimal 10 karakter.');
                        return;
                    }
                    $('#cancel-ito-submit').prop('disabled', true);
                    $.post("{{ route('logistics.delivery-part.cancel.store') }}", {
                        _token: csrfToken,
                        doc_entry: $('#cancel_doc_entry').val(),
                        ito_no: $('#cancel_ito_no').val(),
                        project_id: $('#cancel_project_id').val() || null,
                        item_code: $('#cancel_item_code').val(),
                        unit_no: $('#cancel_unit_no').val(),
                        reason: reason,
                    })
                        .done(function(res) {
                            const $alert = $('#cancel-result-alert');
                            $alert.removeClass('d-none alert-danger').addClass('alert-success');
                            $alert.text(res.message || 'Permintaan diproses.');
                            if (table) {
                                table.ajax.reload(null, false);
                            }
                            if (cancelHistoryTable) {
                                cancelHistoryTable.ajax.reload(null, false);
                            }
                        })
                        .fail(function(xhr) {
                            const $alert = $('#cancel-result-alert');
                            $alert.removeClass('d-none alert-success').addClass('alert-danger');
                            const msg = xhr.responseJSON?.message || 'Pembatalan gagal.';
                            const sap = xhr.responseJSON?.cancel?.sap_message;
                            $alert.text(sap ? msg + ' ' + sap : msg);
                            if (table) {
                                table.ajax.reload(null, false);
                            }
                            if (cancelHistoryTable) {
                                cancelHistoryTable.ajax.reload(null, false);
                            }
                        })
                        .always(function() {
                            $('#cancel-ito-submit').prop('disabled', false);
                        });
                });
            @endcan

            $(document).on('click', '.btn-spb-detail', function() {
                const id = $(this).data('id');
                $.get("{{ url('logistics/delivery-part/spb') }}/" + id)
                    .done(function(data) {
                        let html = '<p><strong>Site:</strong> ' + (data.project_code || '-') + '</p>';
                        html += '<p><strong>No. SPB:</strong> ' + data.no_spb + '</p>';
                        html += '<p><strong>Tanggal:</strong> ' + (data.tanggal || '-') + '</p>';
                        html += '<p><strong>Keterangan:</strong> ' + (data.remarks || '-') + '</p>';
                        html += '<table class="table table-sm table-bordered"><thead><tr><th>Part</th><th>Description</th><th>QTY</th><th>UOM</th><th>Remarks</th></tr></thead><tbody>';
                        (data.items || []).forEach(function(item) {
                            html += '<tr><td>' + (item.part_number || '-') + '</td><td>' + (item.description || '-') +
                                '</td><td>' + (item.qty ?? '-') + '</td><td>' + (item.uom || '-') + '</td><td>' +
                                (item.remarks || '-') + '</td></tr>';
                        });
                        html += '</tbody></table>';
                        $('#spb-detail-body').html(html);
                        $('#spb-detail-modal').modal('show');
                    });
            });
        });
    </script>
@endsection
