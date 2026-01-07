@extends('layouts.main')

@section('title_page')
    Accounting Monthly Invoice Fulfillment Report
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="#">Reports</a></li>
    <li class="breadcrumb-item active">Accounting Monthly Invoice Fulfillment</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Accounting Monthly Invoice Fulfillment Report</h3>
                        <div class="card-tools">
                            <form method="GET" action="{{ route('accounting-fulfillment.index') }}" class="d-inline-flex">
                                <select name="year" id="yearSelect" class="form-control form-control-sm mr-2" style="width: 120px;">
                                    @foreach($years as $y)
                                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-filter"></i> Apply
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="fulfillmentTable" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 10%">Month</th>
                                        <th style="width: 15%">Total Received</th>
                                        <th style="width: 15%">Distributed to Finance</th>
                                        <th style="width: 15%">% Distributed</th>
                                        <th style="width: 15%">Avg Days in Accounting</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Loading data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .percentage-cell {
            font-weight: 600;
        }
        .percentage-high {
            color: #28a745;
        }
        .percentage-medium {
            color: #ffc107;
        }
        .percentage-low {
            color: #dc3545;
        }
        .days-cell {
            font-weight: 500;
        }
    </style>
@endpush

@push('js')
    <script>
        $(document).ready(function() {
            loadFulfillmentData();

            // Reload data when year changes
            $('#yearSelect').on('change', function() {
                loadFulfillmentData();
            });
        });

        function loadFulfillmentData() {
            const year = $('#yearSelect').val();
            
            $.ajax({
                url: '{{ route('accounting-fulfillment.data') }}',
                method: 'GET',
                data: { year: year },
                success: function(response) {
                    if (response.success) {
                        renderTable(response.data);
                    } else {
                        showError('Failed to load data: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    showError('Error loading data. Please try again.');
                    console.error('Error:', xhr);
                }
            });
        }

        function renderTable(data) {
            const tbody = $('#fulfillmentTable tbody');
            tbody.empty();

            if (!data || data.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            <i class="fas fa-info-circle"></i> No data available for the selected year.
                        </td>
                    </tr>
                `);
                return;
            }

            data.forEach(function(row) {
                const percentageClass = getPercentageClass(row.percentage_distributed);
                const percentageDisplay = row.total_received > 0 
                    ? row.percentage_distributed.toFixed(2) + '%' 
                    : '-';
                const avgDaysDisplay = row.distributed_to_finance > 0 
                    ? row.average_days.toFixed(1) + ' days' 
                    : '-';

                tbody.append(`
                    <tr>
                        <td><strong>${row.month_name}</strong></td>
                        <td class="text-center">
                            ${row.total_received}
                        </td>
                        <td class="text-center">
                            ${row.distributed_to_finance}
                        </td>
                        <td class="text-center percentage-cell ${percentageClass}">
                            ${percentageDisplay}
                        </td>
                        <td class="text-center days-cell">
                            ${avgDaysDisplay}
                        </td>
                    </tr>
                `);
            });
        }

        function getPercentageClass(percentage) {
            if (percentage >= 90) {
                return 'percentage-high';
            } else if (percentage >= 70) {
                return 'percentage-medium';
            } else {
                return 'percentage-low';
            }
        }

        function showError(message) {
            const tbody = $('#fulfillmentTable tbody');
            tbody.empty();
            tbody.append(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${message}
                    </td>
                </tr>
            `);
        }
    </script>
@endpush
