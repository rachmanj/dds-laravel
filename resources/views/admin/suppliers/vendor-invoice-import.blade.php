@extends('layouts.main')

@section('title_page')
    Vendor invoice import
@endsection

@section('breadcrumb_title')
    admin / suppliers / vendor invoices
@endsection

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Import invoices from vendor API</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.suppliers.index') }}">Suppliers</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.suppliers.show', $supplier) }}">Supplier</a></li>
                        <li class="breadcrumb-item active">Vendor API import</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

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
            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">{{ $supplier->name }} ({{ $supplier->sap_code }})</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to supplier
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.suppliers.vendor-invoices.lookup', $supplier) }}">
                                @csrf
                                <div class="form-group">
                                    <label for="invoice_numbers">Invoice numbers</label>
                                    <textarea class="form-control @error('invoice_numbers') is-invalid @enderror"
                                        id="invoice_numbers" name="invoice_numbers" rows="6"
                                        placeholder="One per line, or separated by commas">{{ $invoice_numbers }}</textarea>
                                    @error('invoice_numbers')
                                        <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">Enter the invoice numbers you received
                                        (hardcopy / email), then click Look up to load data from the vendor API.</small>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Look up
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if (is_array($results))
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Preview</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <form method="POST" action="{{ route('admin.suppliers.vendor-invoices.import', $supplier) }}">
                                    @csrf
                                    <table class="table table-striped table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px;">
                                                    <input type="checkbox" id="select-all-new" title="Select all new">
                                                </th>
                                                <th>Invoice no</th>
                                                <th>Date</th>
                                                <th class="text-right">Amount</th>
                                                <th>Currency</th>
                                                <th>Reference no</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($results as $row)
                                                @php
                                                    $data = $row['data'] ?? null;
                                                    $date = is_array($data) && isset($data['date']) ? $data['date'] : '—';
                                                    $amount = is_array($data) && isset($data['total_amount']) ? $data['total_amount'] : '—';
                                                    $currencyCode = '—';
                                                    if (is_array($data) && isset($data['currency']) && is_array($data['currency'])) {
                                                        $currencyCode = $data['currency']['code'] ?? '—';
                                                    }
                                                    $ref = is_array($data) && isset($data['reference_no']) ? $data['reference_no'] : '—';
                                                    $desc = is_array($data) && isset($data['description']) ? $data['description'] : '—';
                                                @endphp
                                                <tr>
                                                    <td>
                                                        @if ($row['status'] === 'new')
                                                            <input type="checkbox" name="invoice_nos[]"
                                                                value="{{ $row['invoice_no'] }}" class="cb-new"
                                                                checked>
                                                        @else
                                                            <input type="checkbox" disabled class="text-muted">
                                                        @endif
                                                    </td>
                                                    <td>{{ $row['invoice_no'] }}</td>
                                                    <td>{{ $date }}</td>
                                                    <td class="text-right">{{ $amount }}</td>
                                                    <td>{{ $currencyCode }}</td>
                                                    <td>{{ $ref }}</td>
                                                    <td>{{ \Illuminate\Support\Str::limit($desc, 80) }}</td>
                                                    <td>
                                                        @if ($row['status'] === 'new')
                                                            <span class="badge badge-success">New</span>
                                                        @elseif ($row['status'] === 'duplicate')
                                                            <span class="badge badge-warning">Already in DB</span>
                                                        @else
                                                            <span class="badge badge-danger">Not found</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <div class="card-footer">
                                        @error('invoice_nos')
                                            <div class="alert alert-danger">{{ $message }}</div>
                                        @enderror
                                        <button type="submit" class="btn btn-success" id="btn-import-selected">
                                            <i class="fas fa-download"></i> Import selected
                                        </button>
                                        <small class="text-muted ml-2">Only rows marked <strong>New</strong> can be
                                            imported.</small>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectAll = document.getElementById('select-all-new');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.cb-new').forEach(function(cb) {
                        cb.checked = selectAll.checked;
                    });
                });
            }
            var importBtn = document.getElementById('btn-import-selected');
            var form = importBtn ? importBtn.closest('form') : null;
            if (form && importBtn) {
                form.addEventListener('submit', function(e) {
                    var any = form.querySelectorAll('.cb-new:checked').length > 0;
                    if (!any) {
                        e.preventDefault();
                        alert('Select at least one new invoice to import.');
                    }
                });
            }
        });
    </script>
@endsection
