@extends('layouts.main')

@section('title_page')
    Processing Analytics Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/">Home</a></li>
    <li class="breadcrumb-item active">Processing Analytics</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Processing Analytics Dashboard -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-2"></i>
                                Processing Analytics Dashboard
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filter Controls -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Year</label>
                                        <select class="form-control" id="yearSelect">
                                            @for ($year = date('Y'); $year >= 2020; $year--)
                                                <option value="{{ $year }}"
                                                    {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Month</label>
                                        <select class="form-control" id="monthSelect">
                                            @for ($month = 1; $month <= 12; $month++)
                                                <option value="{{ $month }}"
                                                    {{ $month == date('n') ? 'selected' : '' }}>
                                                    {{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Document Type</label>
                                        <select class="form-control" id="documentTypeSelect">
                                            <option value="both">Both Documents</option>
                                            <option value="invoice">Invoices Only</option>
                                            <option value="additional_document">Additional Documents Only</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-primary btn-block" id="loadDataBtn">
                                            <i class="fas fa-refresh mr-1"></i> Load Data
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            <div class="row" id="summaryCards">
                                <!-- Summary cards will be loaded here -->
                            </div>

                            <!-- Charts Section -->
                            <div class="row">
                                <!-- Department Performance Chart -->
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Average Processing Days by Department</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div id="departmentChart" style="height: 400px;"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Type Distribution -->
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Document Type Distribution</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div id="documentTypeChart" style="height: 400px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Department Details Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Department Performance Details</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" id="exportBtn">
                                                    <i class="fas fa-download mr-1"></i> Export
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="departmentTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Department</th>
                                                            <th>Invoice Count</th>
                                                            <th>Avg Processing Days (Invoices)</th>
                                                            <th>Document Count</th>
                                                            <th>Avg Processing Days (Documents)</th>
                                                            <th>Overall Avg Days</th>
                                                            <th>Efficiency Score</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="departmentTableBody">
                                                        <!-- Table body will be loaded here -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Trend Analysis -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Processing Trends (Last 6 Months)</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div id="trendChart" style="height: 400px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    </section>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
    <script>
        $(document).ready(function() {
            let departmentChart, documentTypeChart, trendChart;

            // Initialize charts
            initializeCharts();

            // Load initial data
            loadData();

            // Event handlers
            $('#loadDataBtn').on('click', loadData);
            $('#exportBtn').on('click', exportData);

            function initializeCharts() {
                // Department Performance Chart
                departmentChart = echarts.init(document.getElementById('departmentChart'));

                // Document Type Distribution Chart
                documentTypeChart = echarts.init(document.getElementById('documentTypeChart'));

                // Trend Chart
                trendChart = echarts.init(document.getElementById('trendChart'));
            }

            async function loadData() {
                const year = $('#yearSelect').val();
                const month = $('#monthSelect').val();
                const documentType = $('#documentTypeSelect').val();

                showLoading();

                try {
                    // Load overview data
                    const overviewResponse = await fetch(
                        `/api/v1/processing-analytics/overview?year=${year}&month=${month}&document_type=${documentType}`
                    );
                    const overviewData = await overviewResponse.json();

                    // Load trend data
                    const trendResponse = await fetch(`/api/v1/processing-analytics/trends/6`);
                    const trendData = await trendResponse.json();

                    if (overviewData.success) {
                        updateSummaryCards(overviewData.data);
                        updateDepartmentChart(overviewData.data);
                        updateDocumentTypeChart(overviewData.data);
                        updateDepartmentTable(overviewData.data);
                    }

                    if (trendData.success) {
                        updateTrendChart(trendData.data);
                    }

                } catch (error) {
                    console.error('Error loading data:', error);
                    showError('Failed to load analytics data');
                } finally {
                    hideLoading();
                }
            }

            function updateSummaryCards(data) {
                const invoiceStats = data.data.invoices || [];
                const docStats = data.data.additional_documents || [];

                const totalInvoices = invoiceStats.reduce((sum, dept) => sum + dept.count, 0);
                const totalDocs = docStats.reduce((sum, dept) => sum + dept.count, 0);
                const avgInvoiceDays = invoiceStats.length > 0 ? (invoiceStats.reduce((sum, dept) => sum + dept
                    .avg_processing_days, 0) / invoiceStats.length).toFixed(2) : 0;
                const avgDocDays = docStats.length > 0 ? (docStats.reduce((sum, dept) => sum + dept
                    .avg_processing_days, 0) / docStats.length).toFixed(2) : 0;

                $('#summaryCards').html(`
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>${totalInvoices}</h3>
                        <p>Total Invoices Processed</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>${totalDocs}</h3>
                        <p>Total Documents Processed</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>${avgInvoiceDays}</h3>
                        <p>Avg Invoice Processing Days</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>${avgDocDays}</h3>
                        <p>Avg Document Processing Days</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        `);
            }

            function updateDepartmentChart(data) {
                const departments = data.departments || [];
                const invoiceStats = data.data.invoices || [];
                const docStats = data.data.additional_documents || [];

                // Convert departments object to array if needed
                const departmentsArray = Array.isArray(departments) ? departments : Object.values(departments);

                // Combine and process data
                const chartData = departmentsArray.map(dept => {
                    const invoiceDept = invoiceStats.find(inv => inv.department_id == dept.id);
                    const docDept = docStats.find(doc => doc.department_id == dept.id);

                    return {
                        name: dept.name,
                        value: [
                            parseFloat(invoiceDept?.avg_processing_days || 0),
                            parseFloat(docDept?.avg_processing_days || 0)
                        ],
                        itemStyle: getEfficiencyColor(
                            Math.max(
                                parseFloat(invoiceDept?.avg_processing_days || 0),
                                parseFloat(docDept?.avg_processing_days || 0)
                            )
                        )
                    };
                }).filter(item => item.value[0] > 0 || item.value[1] > 0);

                const option = {
                    title: {
                        text: 'Processing Performance by Department'
                    },
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {
                            type: 'shadow'
                        },
                        formatter: function(params) {
                            const dept = params[0];
                            const invoiceValue = dept.data[0] || 0;
                            const docValue = dept.data[1] || 0;
                            return `${dept.name}<br/>
                            Invoices: ${invoiceValue.toFixed(2)} days<br/>
                            Documents: ${docValue.toFixed(2)} days`;
                        }
                    },
                    legend: {
                        data: ['Invoices', 'Additional Documents']
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    xAxis: {
                        type: 'category',
                        data: chartData.map(item => item.name),
                        axisLabel: {
                            rotate: 45
                        }
                    },
                    yAxis: {
                        type: 'value',
                        name: 'Processing Days'
                    },
                    series: [{
                            name: 'Invoices',
                            type: 'bar',
                            data: chartData.map(item => item.value[0])
                        },
                        {
                            name: 'Additional Documents',
                            type: 'bar',
                            data: chartData.map(item => item.value[1])
                        }
                    ]
                };

                departmentChart.setOption(option);
            }

            function updateDocumentTypeChart(data) {
                const invoiceStats = data.data.invoices || [];
                const docStats = data.data.additional_documents || [];

                const totalInvoices = invoiceStats.reduce((sum, dept) => sum + dept.count, 0);
                const totalDocs = docStats.reduce((sum, dept) => sum + dept.count, 0);

                const option = {
                    title: {
                        text: 'Document Type Distribution',
                        left: 'center'
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b}: {c} ({d}%)'
                    },
                    series: [{
                        name: 'Documents',
                        type: 'pie',
                        radius: ['40%', '70%'],
                        center: ['50%', '50%'],
                        data: [{
                                value: totalInvoices,
                                name: 'Invoices',
                                itemStyle: {
                                    color: '#3498db'
                                }
                            },
                            {
                                value: totalDocs,
                                name: 'Additional Documents',
                                itemStyle: {
                                    color: '#e74c3c'
                                }
                            }
                        ],
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                            }
                        }
                    }]
                };

                documentTypeChart.setOption(option);
            }

            function updateDepartmentTable(data) {
                const departments = data.departments || [];
                const invoiceStats = data.data.invoices || [];
                const docStats = data.data.additional_documents || [];

                // Convert departments object to array if needed
                const departmentsArray = Array.isArray(departments) ? departments : Object.values(departments);

                const tbody = $('#departmentTableBody');
                tbody.empty();

                const combinedData = departmentsArray.map(dept => {
                        const invoiceDept = invoiceStats.find(inv => inv.department_id == dept.id);
                        const docDept = docStats.find(doc => doc.department_id == dept.id);

                        const invoiceDays = parseFloat(invoiceDept?.avg_processing_days || 0);
                        const docDays = parseFloat(docDept?.avg_processing_days || 0);
                        const overallAvg = ((invoiceDays || 0) + (docDays || 0)) / 2;

                        const invoiceCount = invoiceDept?.count || 0;
                        const docCount = docDept?.count || 0;

                        return {
                            dept: dept,
                            invoiceDays,
                            docDays,
                            overallAvg,
                            invoiceCount,
                            docCount,
                            efficiency: invoiceCount + docCount > 0 ? calculateEfficiencyScore(overallAvg) : 0
                        };
                    }).filter(item => item.invoiceCount > 0 || item.docCount > 0)
                    .sort((a, b) => b.efficiency - a.efficiency);

                combinedData.forEach(item => {
                    const efficiencyBadge = getEfficiencyBadge(item.efficiency);
                    const row = `
                <tr>
                    <td><strong>${item.dept.name}</strong></td>
                    <td>${item.invoiceCount}</td>
                    <td>${item.invoiceDays.toFixed(2)}</td>
                    <td>${item.docCount}</td>
                    <td>${item.docDays.toFixed(2)}</td>
                    <td>${item.overallAvg.toFixed(2)}</td>
                    <td>${efficiencyBadge}</td>
                </tr>
            `;
                    tbody.append(row);
                });
            }

            function updateTrendChart(data) {
                const months = data.map(item => item.month_name);
                const deptTrends = {};

                // Prepare trend data for top departments
                data.forEach(monthData => {
                    const departments = monthData.data?.departments || [];
                    departments.forEach(dept => {
                        if (!deptTrends[dept.name]) {
                            deptTrends[dept.name] = {
                                invoice_days: [],
                                doc_days: []
                            };
                        }

                        const avgInvoiceDays = dept.invoice_stats?.reduce((sum, stat) => sum + stat
                            .avg_processing_days, 0) / (dept.invoice_stats?.length || 1) || 0;
                        const avgDocDays = dept.additional_document_stats?.reduce((sum, stat) =>
                            sum + stat.avg_processing_days, 0) / (dept.additional_document_stats
                            ?.length || 1) || 0;

                        deptTrends[dept.name].invoice_days.push(avgInvoiceDays);
                        deptTrends[dept.name].doc_days.push(avgDocDays);
                    });
                });

                const series = [];
                const colors = ['#3498db', '#e74c3c', '#f39c12', '#2ecc71', '#9b59b6', '#1abc9c'];
                let colorIndex = 0;

                Object.keys(deptTrends).slice(0, 5).forEach(deptName => {
                    series.push({
                        name: `${deptName} (Invoices)`,
                        type: 'line',
                        data: deptTrends[deptName].invoice_days,
                        itemStyle: {
                            color: colors[colorIndex % colors.length]
                        }
                    });
                    series.push({
                        name: `${deptName} (Docs)`,
                        type: 'line',
                        data: deptTrends[deptName].doc_days,
                        itemStyle: {
                            color: colors[(colorIndex + 1) % colors.length]
                        }
                    });
                    colorIndex += 2;
                });

                const option = {
                    title: {
                        text: 'Processing Trends Over Time'
                    },
                    tooltip: {
                        trigger: 'axis'
                    },
                    legend: {
                        top: 'bottom',
                        type: 'scroll'
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '15%',
                        containLabel: true
                    },
                    xAxis: {
                        type: 'category',
                        data: months
                    },
                    yAxis: {
                        type: 'value',
                        name: 'Processing Days'
                    },
                    series: series
                };

                trendChart.setOption(option);
            }

            function getEfficiencyColor(avgDays) {
                if (avgDays <= 2) return '#2ecc71'; // Green - Excellent
                if (avgDays <= 3) return '#f39c12'; // Orange - Good
                if (avgDays <= 5) return '#e67e22'; // Orange-Red - Average
                return '#e74c3c'; // Red - Poor
            }

            function calculateEfficiencyScore(avgDays) {
                // Score out of 100, higher is better
                if (avgDays <= 1) return 100;
                if (avgDays <= 2) return 90;
                if (avgDays <= 3) return 75;
                if (avgDays <= 5) return 50;
                if (avgDays <= 7) return 25;
                return 10;
            }

            function getEfficiencyBadge(score) {
                if (score >= 90) return '<span class="badge badge-success">Excellent</span>';
                if (score >= 75) return '<span class="badge badge-info">Good</span>';
                if (score >= 50) return '<span class="badge badge-warning">Average</span>';
                return '<span class="badge badge-danger">Needs Improvement</span>';
            }

            function exportData() {
                const year = $('#yearSelect').val();
                const month = $('#monthSelect').val();
                const format = $('#documentTypeSelect').val();

                window.open(`/api/v1/processing-analytics/export?year=${year}&month=${month}&format=excel`);
            }

            function showLoading() {
                $('#loadDataBtn').html('<i class="fas fa-spinner fa-spin mr-1"></i>Loading...').prop('disabled',
                    true);
            }

            function hideLoading() {
                $('#loadDataBtn').html('<i class="fas fa-refresh mr-1"></i>Load Data').prop('disabled', false);
            }

            function showError(message) {
                toastr.error(message);
            }

            // Handle window resize
            $(window).on('resize', function() {
                departmentChart.resize();
                documentTypeChart.resize();
                trendChart.resize();
            });
        });
    </script>
@endpush

@push('css')
    <style>
        .small-box .icon {
            top: 15px;
            font-size: 70px;
            transform: rotate(15deg);
        }

        #departmentChart,
        #documentTypeChart,
        #trendChart {
            width: 100%;
        }
    </style>
@endpush
