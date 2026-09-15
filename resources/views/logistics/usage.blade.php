@extends('layouts.main')

@section('title_page')
    Pemakaian
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pemakaian</li>
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
                    <h4 class="mb-0">Pemakaian</h4>
                    <p class="text-muted small mb-0">Goods Issue, Delivery, dan AP Service dari SAP — data live per rentang tanggal.</p>
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
                    <form method="GET" action="{{ route('logistics.usage.index') }}" id="usage-filter-form">
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
                                    <label for="filter_sumber">Sumber</label>
                                    <select class="form-control" id="filter_sumber" name="sumber">
                                        <option value="">Semua Sumber</option>
                                        <option value="goods_issue" @selected($selectedSource === 'goods_issue')>Goods Issue</option>
                                        <option value="delivery" @selected($selectedSource === 'delivery')>Delivery</option>
                                        <option value="ap_service" @selected($selectedSource === 'ap_service')>AP Service</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                        <a href="{{ route('logistics.usage.index') }}" class="btn btn-default btn-sm">
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
                            <h3>{{ $kpis['row_count_formatted'] }}</h3>
                            <p>Jumlah Baris</p>
                        </div>
                        <div class="icon"><i class="fas fa-list"></i></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ $kpis['document_count_formatted'] }}</h3>
                            <p>Jumlah Dokumen</p>
                        </div>
                        <div class="icon"><i class="fas fa-file-alt"></i></div>
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
                                            <th>Jumlah Dokumen</th>
                                            <th class="text-right">Total Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summaryByProject as $summary)
                                            <tr>
                                                <td>{{ $summary['label'] }}</td>
                                                <td>{{ number_format($summary['document_count'], 0, ',', '.') }}</td>
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
                            <h3 class="card-title text-sm">Ringkasan per Kategori</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            @if (count($summaryByCategory) === 0)
                                <p class="text-muted small p-3 mb-0">Belum ada data.</p>
                            @else
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Kategori</th>
                                            <th>Jumlah Dokumen</th>
                                            <th class="text-right">Total Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summaryByCategory as $summary)
                                            <tr>
                                                <td>{{ $summary['label'] }}</td>
                                                <td>{{ number_format($summary['document_count'], 0, ',', '.') }}</td>
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
                    <h3 class="card-title">Detail Pemakaian</h3>
                </div>
                <div class="card-body table-responsive">
                    <table id="usage-detail-table" class="table table-bordered table-striped table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>DocNum</th>
                                <th>createDate</th>
                                <th>DocDate</th>
                                <th>WO No</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Line</th>
                                <th>Issue Purpose</th>
                                <th>Job Category</th>
                                <th>Job Name</th>
                                <th>Unit No</th>
                                <th>Model No</th>
                                <th>Serial No</th>
                                <th>Hours Meter</th>
                                <th>ItemCode</th>
                                <th>Dscription</th>
                                <th>Quantity</th>
                                <th class="text-right">Stockprice</th>
                                <th class="text-right">Total</th>
                                <th>Project</th>
                                <th>WhsName</th>
                                <th>U_MIS_NoBA</th>
                                <th>Order Type</th>
                                <th>Status</th>
                                <th>GR No</th>
                                <th>M Ret No</th>
                                <th>Ret ItemCode</th>
                                <th>Ret Dscription</th>
                                <th>Ret Quantity</th>
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
            function usageFilterParams() {
                return {
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    project: $('#filter_project').val(),
                    sumber: $('#filter_sumber').val(),
                };
            }

            $('#usage-detail-table').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: {
                    url: "{{ route('logistics.usage.data') }}",
                    data: function(d) {
                        Object.assign(d, usageFilterParams());
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                            alert(xhr.responseJSON.message);
                        }
                    }
                },
                columns: [
                    { data: 'source_label', name: 'source', defaultContent: '-' },
                    { data: 'doc_num', name: 'doc_num', defaultContent: '-' },
                    { data: 'create_date', name: 'create_date', defaultContent: '-' },
                    { data: 'doc_date', name: 'doc_date', defaultContent: '-' },
                    { data: 'wo_no', name: 'wo_no', defaultContent: '-' },
                    { data: 'subject', name: 'subject', defaultContent: '-' },
                    { data: 'category', name: 'category', defaultContent: '-' },
                    { data: 'line', name: 'line', defaultContent: '-' },
                    { data: 'issue_purpose', name: 'issue_purpose', defaultContent: '-' },
                    { data: 'job_category', name: 'job_category', defaultContent: '-' },
                    { data: 'job_name', name: 'job_name', defaultContent: '-' },
                    { data: 'unit_no', name: 'unit_no', defaultContent: '-' },
                    { data: 'model_no', name: 'model_no', defaultContent: '-' },
                    { data: 'serial_no', name: 'serial_no', defaultContent: '-' },
                    { data: 'hours_meter', name: 'hours_meter', defaultContent: '-' },
                    { data: 'item_code', name: 'item_code', defaultContent: '-' },
                    { data: 'dscription', name: 'dscription', defaultContent: '-' },
                    { data: 'formatted_quantity', name: 'quantity' },
                    { data: 'formatted_stockprice', name: 'stockprice', className: 'text-right' },
                    { data: 'formatted_total', name: 'total', className: 'text-right' },
                    { data: 'project', name: 'project', defaultContent: '-' },
                    { data: 'whs_name', name: 'whs_name', defaultContent: '-' },
                    { data: 'u_mis_no_ba', name: 'u_mis_no_ba', defaultContent: '-' },
                    { data: 'order_type', name: 'order_type', defaultContent: '-' },
                    { data: 'status_doc', name: 'status_doc', defaultContent: '-' },
                    { data: 'gr_no', name: 'gr_no', defaultContent: '-' },
                    { data: 'm_ret_no', name: 'm_ret_no', defaultContent: '-' },
                    { data: 'return_item_code', name: 'return_item_code', defaultContent: '-' },
                    { data: 'return_dscription', name: 'return_dscription', defaultContent: '-' },
                    { data: 'formatted_return_quantity', name: 'return_quantity' },
                    { data: 'comments', name: 'comments', defaultContent: '-' },
                ],
                order: [[3, 'desc']],
                pageLength: 25,
            });

            $('#export_excel').on('click', function() {
                window.location.href = "{{ route('logistics.usage.export') }}?" + new URLSearchParams(usageFilterParams()).toString();
            });
        });
    </script>
@endsection
