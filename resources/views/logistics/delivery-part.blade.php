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
                                            <option value="{{ $site['code'] }}" @selected($selectedProject === $site['code'])>
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
                                @can('edit-delivery-part')
                                    <th>Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>

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

            if (canEdit) {
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
        });
    </script>
@endsection
