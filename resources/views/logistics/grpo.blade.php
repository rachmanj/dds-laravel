@extends('layouts.main')

@section('title_page')
    GRPO
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">GRPO</li>
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
                    <h4 class="mb-0">GRPO</h4>
                    <p class="text-muted small mb-0">Penerimaan barang dari SAP — data live per rentang tanggal.</p>
                </div>
            </div>

            @if ($dateRangeError)
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $dateRangeError }}
                </div>
            @endif

            @if ($errors->has('date_range'))
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $errors->first('date_range') }}
                </div>
            @endif

            <div class="card card-outline card-info mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filter</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('logistics.grpo.index') }}" id="grpo-filter-form">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="from_date">Dari Tanggal</label>
                                    <input type="date" class="form-control" id="from_date" name="from_date"
                                        value="{{ $fromDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="to_date">Sampai Tanggal</label>
                                    <input type="date" class="form-control" id="to_date" name="to_date"
                                        value="{{ $toDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_project">Project</label>
                                    <select class="form-control" id="filter_project" name="project">
                                        <option value="">Semua Project</option>
                                        @foreach ($filterOptions['projects'] as $project)
                                            <option value="{{ $project }}" @selected($selectedProject === $project)>
                                                {{ $project }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_vendor">Vendor</label>
                                    <select class="form-control" id="filter_vendor" name="vendor">
                                        <option value="">Semua Vendor</option>
                                        @foreach ($filterOptions['vendors'] as $vendor)
                                            <option value="{{ $vendor['code'] }}" @selected($selectedVendor === $vendor['code'])>
                                                {{ $vendor['code'] }} — {{ $vendor['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                        <a href="{{ route('logistics.grpo.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-eraser"></i> Reset
                        </a>
                        @can('export-logistics-summary')
                            <button type="button" class="btn btn-success btn-sm float-right" id="export_excel">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                        @endcan
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $kpis['grpo_count_formatted'] }}</h3>
                            <p>Jumlah GRPO</p>
                        </div>
                        <div class="icon"><i class="fas fa-file-invoice"></i></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ $kpis['row_count_formatted'] }}</h3>
                            <p>Jumlah Baris</p>
                        </div>
                        <div class="icon"><i class="fas fa-list"></i></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $kpis['total_value_formatted'] }}</h3>
                            <p>Total Nilai</p>
                        </div>
                        <div class="icon"><i class="fas fa-coins"></i></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card card-outline card-secondary mb-3">
                        <div class="card-header py-2">
                            <h3 class="card-title text-sm">Ringkasan per Project</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            @if (count($summaryByProject) === 0)
                                <p class="text-muted small p-3 mb-0">Belum ada data.</p>
                            @else
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th class="text-right">Jumlah GRPO</th>
                                            <th class="text-right">Total Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summaryByProject as $summary)
                                            <tr>
                                                <td>{{ $summary['label'] }}</td>
                                                <td class="text-right">{{ number_format($summary['grpo_count'], 0, ',', '.') }}</td>
                                                <td class="text-right">{{ \App\Support\CompactNumberFormatter::format($summary['total_value']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-outline card-secondary mb-3">
                        <div class="card-header py-2">
                            <h3 class="card-title text-sm">Ringkasan per Vendor</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            @if (count($summaryByVendor) === 0)
                                <p class="text-muted small p-3 mb-0">Belum ada data.</p>
                            @else
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Vendor</th>
                                            <th class="text-right">Jumlah GRPO</th>
                                            <th class="text-right">Total Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summaryByVendor as $summary)
                                            <tr>
                                                <td>{{ $summary['label'] }}</td>
                                                <td class="text-right">{{ number_format($summary['grpo_count'], 0, ',', '.') }}</td>
                                                <td class="text-right">{{ \App\Support\CompactNumberFormatter::format($summary['total_value']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detail GRPO</h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="grpo-detail-table" class="table table-bordered table-striped table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th>GRPO Date</th>
                                <th>GRPO Created Date</th>
                                <th>GRPO No</th>
                                <th>PO No.</th>
                                <th>PO Date</th>
                                <th>PO Created Date</th>
                                <th>PO Delivery Status</th>
                                <th>PO Delivery Time</th>
                                <th>PR No.</th>
                                <th>Item Code</th>
                                <th>OEM No.</th>
                                <th>Item Name</th>
                                <th>U_MIS_ConsRe1</th>
                                <th>U_MIS_ConsRe2</th>
                                <th>Quantity</th>
                                <th>U_MIS_UnitNo</th>
                                <th>Currency</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Total Price</th>
                                <th>UoM</th>
                                <th>Warehouse Code</th>
                                <th>Warehouse Name</th>
                                <th>Received By</th>
                                <th>Time</th>
                                <th>Project</th>
                                <th>Department</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script>
        $(function() {
            function grpoFilterParams() {
                return {
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    project: $('#filter_project').val(),
                    vendor: $('#filter_vendor').val(),
                };
            }

            const grpoTable = $('#grpo-detail-table').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: {
                    url: "{{ route('logistics.grpo.data') }}",
                    data: function(d) {
                        Object.assign(d, grpoFilterParams());
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                            alert(xhr.responseJSON.message);
                        }
                    }
                },
                columns: [
                    { data: 'grpo_date', name: 'grpo_date', defaultContent: '-' },
                    { data: 'grpo_created_date', name: 'grpo_created_date', defaultContent: '-' },
                    { data: 'grpo_no_display', name: 'grpo_no', orderable: false, searchable: false, defaultContent: '-' },
                    { data: 'po_no', name: 'po_no', defaultContent: '-' },
                    { data: 'po_date', name: 'po_date', defaultContent: '-' },
                    { data: 'po_created_date', name: 'po_created_date', defaultContent: '-' },
                    { data: 'po_delivery_status', name: 'po_delivery_status', defaultContent: '-' },
                    { data: 'po_delivery_time', name: 'po_delivery_time', defaultContent: '-' },
                    { data: 'pr_no', name: 'pr_no', defaultContent: '-' },
                    { data: 'item_code', name: 'item_code', defaultContent: '-' },
                    { data: 'oem_no', name: 'oem_no', defaultContent: '-' },
                    { data: 'item_name', name: 'item_name', defaultContent: '-' },
                    { data: 'u_mis_consre1', name: 'u_mis_consre1', defaultContent: '-' },
                    { data: 'u_mis_consre2', name: 'u_mis_consre2', defaultContent: '-' },
                    { data: 'formatted_quantity', name: 'quantity' },
                    { data: 'u_mis_unitno', name: 'u_mis_unitno', defaultContent: '-' },
                    { data: 'currency', name: 'currency', defaultContent: '-' },
                    { data: 'formatted_price', name: 'price', className: 'text-right' },
                    { data: 'formatted_total_price', name: 'total_price', className: 'text-right' },
                    { data: 'uom', name: 'uom', defaultContent: '-' },
                    { data: 'warehouse_code', name: 'warehouse_code', defaultContent: '-' },
                    { data: 'warehouse_name', name: 'warehouse_name', defaultContent: '-' },
                    { data: 'received_by', name: 'received_by', defaultContent: '-' },
                    { data: 'time', name: 'time', defaultContent: '-' },
                    { data: 'project', name: 'project', defaultContent: '-' },
                    { data: 'department', name: 'department', defaultContent: '-' },
                    { data: 'comments', name: 'comments', defaultContent: '-' },
                ],
                order: [[0, 'desc']],
                pageLength: 25,
            });

            $('#export_excel').on('click', function() {
                window.location.href = "{{ route('logistics.grpo.export') }}?" + new URLSearchParams(grpoFilterParams()).toString();
            });
        });
    </script>
@endsection
