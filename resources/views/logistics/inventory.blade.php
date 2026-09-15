@extends('layouts.main')

@section('title_page')
    Ringkasan Inventory
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Ringkasan Inventory</li>
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
                    <h4 class="mb-0">Ringkasan Inventory</h4>
                    <p class="text-muted small mb-0">Snapshot gudang SAP — diperbarui harian pukul 06:00 WITA.</p>
                </div>
            </div>

            @if ($showWarning && $warningMessage)
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $warningMessage }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $totalItemsFormatted }}</h3>
                            <p>Total Item</p>
                        </div>
                        <div class="icon"><i class="fas fa-boxes"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $totalValueFormatted }}</h3>
                            <p>Total Nilai</p>
                        </div>
                        <div class="icon"><i class="fas fa-coins"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ $warehouseCountFormatted }}</h3>
                            <p>Jumlah Warehouse</p>
                        </div>
                        <div class="icon"><i class="fas fa-warehouse"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>{{ $snapshotDate ? $snapshotDate->format('d M Y') : '-' }}</h3>
                            <p>Tanggal Snapshot</p>
                        </div>
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header py-2">
                            <h3 class="card-title text-sm">Instock per Project</h3>
                        </div>
                        <div class="card-body py-2">
                            @if (count($instockByProjectChart['labels']) === 0)
                                <p class="text-muted small mb-0">Belum ada data chart.</p>
                            @else
                                <div style="height: 12rem; position: relative;">
                                    <canvas id="instockByProjectChart"></canvas>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header py-2">
                            <h3 class="card-title text-sm">Nilai per Category</h3>
                        </div>
                        <div class="card-body py-2">
                            @if (count($valueByCategoryChart['labels']) === 0)
                                <p class="text-muted small mb-0">Belum ada data chart.</p>
                            @else
                                <div style="height: 12rem; position: relative;">
                                    <canvas id="valueByCategoryChart"></canvas>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary mb-3">
                <div class="card-header">
                    <h3 class="card-title">Pivot Project × Category (Instock)</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    @if (count($pivotMatrix['projects']) === 0)
                        <p class="text-muted small p-3 mb-0">Belum ada data pivot.</p>
                    @else
                        <table class="table table-sm table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    @foreach ($pivotMatrix['categories'] as $category)
                                        <th class="text-right">{{ $category }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pivotMatrix['projects'] as $project)
                                    <tr>
                                        <td>{{ $project }}</td>
                                        @foreach ($pivotMatrix['categories'] as $category)
                                            @php
                                                $cellValue = $pivotMatrix['values'][$project][$category] ?? null;
                                            @endphp
                                            <td class="text-right">
                                                {{ $cellValue !== null ? number_format($cellValue, 2, ',', '.') : '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-info mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filter Detail</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_whs_code">Warehouse</label>
                                <select class="form-control inv-filter" id="filter_whs_code">
                                    <option value="">Semua Warehouse</option>
                                    @foreach ($filterOptions['warehouses'] as $warehouse)
                                        <option value="{{ $warehouse['code'] }}">{{ $warehouse['code'] }} — {{ $warehouse['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_project">Project</label>
                                <select class="form-control inv-filter" id="filter_project">
                                    <option value="">Semua Project</option>
                                    @foreach ($filterOptions['projects'] as $project)
                                        <option value="{{ $project }}">{{ $project }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_category">Kategori</label>
                                <select class="form-control inv-filter" id="filter_category">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($filterOptions['categories'] as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_status">Status</label>
                                <select class="form-control inv-filter" id="filter_status">
                                    <option value="">Semua Status</option>
                                    @foreach ($filterOptions['statuses'] as $status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="apply_filters">
                        <i class="fas fa-search"></i> Terapkan Filter
                    </button>
                    <button type="button" class="btn btn-default btn-sm" id="clear_filters">
                        <i class="fas fa-eraser"></i> Reset
                    </button>
                    @can('export-logistics-summary')
                        <button type="button" class="btn btn-success btn-sm float-right" id="export_excel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detail Inventory</h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="inventory-detail-table" class="table table-bordered table-striped table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th>Model no</th>
                                <th>Unit No</th>
                                <th>Item No.</th>
                                <th>Item Description</th>
                                <th>Category</th>
                                <th>Inventory UoM</th>
                                <th>Instock</th>
                                <th>Committed</th>
                                <th>Ordered</th>
                                <th>Currency</th>
                                <th>Last Purchase Price</th>
                                <th>Total</th>
                                <th>WhsCode</th>
                                <th>WhsName</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Last MR No</th>
                                <th>Last MI No</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="card card-outline card-secondary mt-3">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Snapshot Terakhir</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-right">Jumlah Baris</th>
                                <th class="text-right">Total Nilai</th>
                                <th class="text-right">Durasi (ms)</th>
                                <th>Pesan Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSnapshots as $snapshot)
                                <tr>
                                    <td>{{ $snapshot->snapshot_date->format('d M Y') }}</td>
                                    <td>
                                        @if ($snapshot->status === 'success')
                                            <span class="badge badge-success">Sukses</span>
                                        @else
                                            <span class="badge badge-danger">Gagal</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($snapshot->row_count, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ \App\Support\CompactNumberFormatter::format((float) $snapshot->total_value) }}</td>
                                    <td class="text-right">{{ number_format($snapshot->duration_ms ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $snapshot->error_message ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted text-center">Belum ada snapshot.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script>
        $(function() {
            const instockChartData = @json($instockByProjectChart);
            const valueChartData = @json($valueByCategoryChart);

            function buildBarChart(canvasId, chartData, label) {
                const canvas = document.getElementById(canvasId);
                if (!canvas || typeof Chart === 'undefined' || !chartData.labels || !chartData.labels.length) {
                    return;
                }

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: label,
                            data: chartData.values,
                            backgroundColor: 'rgba(0, 123, 255, 0.65)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 1,
                        }]
                    },
                    options: {
                        legend: { display: false },
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return (value / 1000000).toFixed(1) + ' jt';
                                        }
                                        if (value >= 1000) {
                                            return (value / 1000).toFixed(1) + ' rb';
                                        }
                                        return value;
                                    }
                                }
                            }]
                        }
                    }
                });
            }

            buildBarChart('instockByProjectChart', instockChartData, 'Instock');
            buildBarChart('valueByCategoryChart', valueChartData, 'Nilai');

            function inventoryFilterParams() {
                return {
                    whs_code: $('#filter_whs_code').val(),
                    project: $('#filter_project').val(),
                    category: $('#filter_category').val(),
                    status: $('#filter_status').val(),
                };
            }

            const inventoryTable = $('#inventory-detail-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('logistics.inventory.data') }}",
                    data: function(d) {
                        Object.assign(d, inventoryFilterParams());
                    }
                },
                columns: [
                    { data: 'model_no', name: 'model_no', defaultContent: '-' },
                    { data: 'unit_no', name: 'unit_no', defaultContent: '-' },
                    { data: 'item_code', name: 'item_code' },
                    { data: 'item_name', name: 'item_name', defaultContent: '-' },
                    { data: 'category', name: 'category' },
                    { data: 'uom', name: 'uom', defaultContent: '-' },
                    { data: 'formatted_instock', name: 'instock' },
                    { data: 'formatted_committed', name: 'committed' },
                    { data: 'formatted_ordered', name: 'ordered' },
                    { data: 'currency', name: 'currency', defaultContent: '-' },
                    { data: 'formatted_last_price', name: 'last_price' },
                    { data: 'formatted_total_value', name: 'total_value' },
                    { data: 'whs_code', name: 'whs_code', defaultContent: '-' },
                    { data: 'whs_name', name: 'whs_name', defaultContent: '-' },
                    { data: 'project', name: 'project', defaultContent: '-' },
                    { data: 'status', name: 'status', defaultContent: '-' },
                    { data: 'last_mr_no', name: 'last_mr_no', defaultContent: '-' },
                    { data: 'last_mi_no', name: 'last_mi_no', defaultContent: '-' },
                ],
                order: [[2, 'asc']],
                pageLength: 25,
                responsive: true,
                scrollX: true,
            });

            $('#apply_filters').on('click', function() {
                inventoryTable.ajax.reload();
            });

            $('#clear_filters').on('click', function() {
                $('.inv-filter').val('');
                inventoryTable.ajax.reload();
            });

            $('#export_excel').on('click', function() {
                window.location.href = "{{ route('logistics.inventory.export') }}?" + new URLSearchParams(inventoryFilterParams()).toString();
            });
        });
    </script>
@endsection
