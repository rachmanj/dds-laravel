@extends('layouts.main')

@section('title_page')
    Processing Analytics Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
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
                                <div class="col-lg-2 col-md-3 col-sm-6">
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
                                <div class="col-lg-2 col-md-3 col-sm-6">
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
                                <div class="col-lg-2 col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Document Type</label>
                                        <select class="form-control" id="documentTypeSelect">
                                            <option value="both">Both Documents</option>
                                            <option value="invoice">Invoices Only</option>
                                            <option value="additional_document">Additional Documents Only</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Analysis Type</label>
                                        <select class="form-control" id="analysisTypeSelect">
                                            <option value="basic">Basic Analysis</option>
                                            <option value="accurate">Accurate Analysis</option>
                                            <option value="department_specific">Department-Specific Analysis</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-primary btn-block" id="loadDataBtn">
                                            <i class="fas fa-refresh mr-1"></i> Load Data
                                        </button>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-success btn-block" id="helpBtn">
                                            <i class="fas fa-question-circle mr-1"></i> Help
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Department-Specific Aging Alerts -->
                            <div class="row mb-4" id="departmentAgingAlerts" style="display: none;">
                                <div class="col-12">
                                    <div class="alert alert-danger alert-dismissible" id="criticalAgingAlert" style="display: none;">
                                        <h4><i class="icon fas fa-exclamation-triangle"></i> Critical Aging Alert!</h4>
                                        <span id="criticalAgingCount">0</span> documents have been in departments for over 30 days.
                                        <button type="button" class="btn btn-danger btn-sm ml-2" id="viewCriticalDocuments">
                                            <i class="fas fa-eye"></i> View Critical Documents
                                        </button>
                                    </div>
                                    <div class="alert alert-warning alert-dismissible" id="warningAgingAlert" style="display: none;">
                                        <h4><i class="icon fas fa-exclamation-circle"></i> Warning!</h4>
                                        <span id="warningAgingCount">0</span> documents have been in departments for 15-30 days.
                                        <button type="button" class="btn btn-warning btn-sm ml-2" id="viewWarningDocuments">
                                            <i class="fas fa-eye"></i> View Warning Documents
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

                            <!-- Department-Specific Analysis Section -->
                            <div class="row" id="departmentSpecificAnalysis" style="display: none;">
                                <!-- Department-Specific Processing Chart -->
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Department-Specific Processing Analysis</h3>
                                            <div class="card-tools">
                                                <select class="form-control" id="agingAnalysisType">
                                                    <option value="current_location">Days in Current Department</option>
                                                    <option value="total_processing">Total Processing Days</option>
                                                    <option value="comparison">Comparison View</option>
                                                </select>
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div id="departmentSpecificChart" style="height: 400px;"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Aging Categories Breakdown -->
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Aging Categories Breakdown</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div id="agingCategoriesChart" style="height: 400px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced Analytics Section -->
                            <div class="row" id="enhancedAnalytics" style="display: none;">
                                <!-- Processing Bottlenecks -->
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                                                Processing Bottlenecks
                                            </h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div id="bottlenecksChart" style="height: 300px;"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Slow Processing Documents -->
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-clock text-danger mr-2"></i>
                                                Slow Processing Documents
                                            </h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped" id="slowDocumentsTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Document</th>
                                                            <th>Department</th>
                                                            <th>Days</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="slowDocumentsTableBody">
                                                        <!-- Slow documents will be loaded here -->
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

                    <!-- Department Monthly Performance Chart -->
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-line mr-2"></i>
                                    Department Monthly Performance
                                </h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label>Department:</label>
                                        <select id="departmentSelect" class="form-control">
                                            <option value="">Select Department</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Year:</label>
                                        <select id="departmentYearSelect" class="form-control">
                                            <option value="2025">2025</option>
                                            <option value="2024">2024</option>
                                            <option value="2023">2023</option>
                                            <option value="2022">2022</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Document Type:</label>
                                        <select id="departmentDocumentTypeSelect" class="form-control">
                                            <option value="both">Both Documents</option>
                                            <option value="invoice">Invoices Only</option>
                                            <option value="additional_document">Additional Documents Only</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>&nbsp;</label>
                                        <button id="loadDepartmentData" class="btn btn-primary btn-block">
                                            <i class="fas fa-sync-alt"></i> Load Data
                                        </button>
                                    </div>
                                </div>

                                <!-- Summary Cards -->
                                <div class="row mb-3" id="departmentSummaryCards" style="display: none;">
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info"><i class="fas fa-file-alt"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Documents</span>
                                                <span class="info-box-number" id="totalDocuments">0</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i class="fas fa-clock"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Avg Processing Days</span>
                                                <span class="info-box-number" id="avgProcessingDays">0</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning"><i class="fas fa-trophy"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Best Month</span>
                                                <span class="info-box-number" id="bestMonth">-</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger"><i
                                                    class="fas fa-exclamation-triangle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Worst Month</span>
                                                <span class="info-box-number" id="worstMonth">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="departmentMonthlyChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                @endsection

                @push('js')
                    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
                    <script>
                        $(document).ready(function() {
                            let departmentChart, documentTypeChart, trendChart;

                            // Initialize charts
                            initializeCharts();

                            // Load departments for department selection
                            loadDepartments();

                            // Load initial data
                            loadData();

                            // Event handlers
                            $('#loadDataBtn').on('click', loadData);
                            $('#exportBtn').on('click', exportData);
                            $('#helpBtn').on('click', showHelp);
                            $('#loadDepartmentData').on('click', loadDepartmentMonthlyData);
                            
                            // NEW: Aging alert event handlers
                            $('#viewCriticalDocuments').on('click', function() {
                                // Navigate to invoices/additional documents with critical aging filter
                                window.location.href = '{{ url("invoices") }}?age_filter=30_plus_days&status_filter=available,in_transit';
                            });
                            
                            $('#viewWarningDocuments').on('click', function() {
                                // Navigate to invoices/additional documents with warning aging filter
                                window.location.href = '{{ url("invoices") }}?age_filter=15-30_days&status_filter=available,in_transit';
                            });

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
                                const analysisType = $('#analysisTypeSelect').val();

                                showLoading();

                                try {
                                    // Load overview data
                                    const overviewResponse = await fetch(
                                        `{{ url('api/v1/processing-analytics/overview') }}?year=${year}&month=${month}&document_type=${documentType}`
                                    );
                                    const overviewData = await overviewResponse.json();

                                    // Load trend data
                                    const trendResponse = await fetch(`{{ url('api/v1/processing-analytics/trends/6') }}`);
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

                                    // Load enhanced analytics if accurate analysis is selected
                                    if (analysisType === 'accurate') {
                                        await loadEnhancedAnalytics(year, month);
                                        $('#enhancedAnalytics').show();
                                        $('#departmentSpecificAnalysis').hide();
                                    } else if (analysisType === 'department_specific') {
                                        await loadDepartmentSpecificAnalytics(year, month);
                                        $('#departmentSpecificAnalysis').show();
                                        $('#enhancedAnalytics').hide();
                                    } else {
                                        $('#enhancedAnalytics').hide();
                                        $('#departmentSpecificAnalysis').hide();
                                    }

                                    // Load aging alerts for all analysis types
                                    await loadAgingAlerts();

                                } catch (error) {
                                    console.error('Error loading data:', error);
                                    showError('Failed to load analytics data');
                                } finally {
                                    hideLoading();
                                }
                            }

                            async function loadEnhancedAnalytics(year, month) {
                                try {
                                    // Load processing bottlenecks
                                    const bottlenecksResponse = await fetch(
                                        `{{ url('api/v1/processing-analytics/processing-bottlenecks') }}?year=${year}&month=${month}&limit=5`
                                    );
                                    const bottlenecksData = await bottlenecksResponse.json();

                                    // Load slow processing documents
                                    const slowDocsResponse = await fetch(
                                        `{{ url('api/v1/processing-analytics/slow-processing-documents') }}?year=${year}&month=${month}&threshold_days=7`
                                    );
                                    const slowDocsData = await slowDocsResponse.json();

                                    if (bottlenecksData.success) {
                                        updateBottlenecksChart(bottlenecksData.data);
                                    }

                                    if (slowDocsData.success) {
                                        updateSlowDocumentsTable(slowDocsData.data);
                                    }

                                    $('#enhancedAnalytics').show();

                                } catch (error) {
                                    console.error('Error loading enhanced analytics:', error);
                                }
                            }

                            // NEW: Load department-specific analytics
                            async function loadDepartmentSpecificAnalytics(year, month) {
                                try {
                                    // Load department-specific metrics
                                    const response = await fetch(
                                        `{{ url('api/v1/processing-analytics/department-specific') }}/${year}/${month}`
                                    );
                                    const data = await response.json();

                                    if (data.success) {
                                        updateDepartmentSpecificChart(data.data);
                                        updateAgingCategoriesChart(data.data);
                                        updateDepartmentSpecificTable(data.data);
                                    }

                                } catch (error) {
                                    console.error('Error loading department-specific analytics:', error);
                                }
                            }

                            // NEW: Load aging alerts
                            async function loadAgingAlerts() {
                                try {
                                    const response = await fetch(
                                        `{{ url('api/v1/processing-analytics/aging-alerts') }}`
                                    );
                                    const data = await response.json();

                                    if (data.success) {
                                        updateAgingAlerts(data.data);
                                    }

                                } catch (error) {
                                    console.error('Error loading aging alerts:', error);
                                }
                            }

                            // NEW: Update aging alerts display
                            function updateAgingAlerts(alerts) {
                                const alertsContainer = $('#departmentAgingAlerts');
                                const criticalAlert = $('#criticalAgingAlert');
                                const warningAlert = $('#warningAgingAlert');

                                if (alerts.overdue_critical > 0 || alerts.overdue_warning > 0) {
                                    alertsContainer.show();

                                    if (alerts.overdue_critical > 0) {
                                        $('#criticalAgingCount').text(alerts.overdue_critical);
                                        criticalAlert.show();
                                    } else {
                                        criticalAlert.hide();
                                    }

                                    if (alerts.overdue_warning > 0) {
                                        $('#warningAgingCount').text(alerts.overdue_warning);
                                        warningAlert.show();
                                    } else {
                                        warningAlert.hide();
                                    }
                                } else {
                                    alertsContainer.hide();
                                }
                            }

                            // NEW: Update department-specific chart
                            function updateDepartmentSpecificChart(data) {
                                const chart = echarts.init(document.getElementById('departmentSpecificChart'));
                                
                                const departments = [];
                                const currentLocationDays = [];
                                const totalProcessingDays = [];

                                // Process department breakdown data
                                Object.values(data.invoices.department_breakdown).forEach(dept => {
                                    departments.push(dept.department_name);
                                    currentLocationDays.push(dept.avg_days_in_current_location || 0);
                                    totalProcessingDays.push(dept.avg_days_in_current_location || 0); // For now, same as current location
                                });

                                const option = {
                                    title: {
                                        text: 'Department-Specific Processing Analysis',
                                        left: 'center'
                                    },
                                    tooltip: {
                                        trigger: 'axis',
                                        axisPointer: {
                                            type: 'shadow'
                                        }
                                    },
                                    legend: {
                                        data: ['Days in Current Department', 'Total Processing Days'],
                                        top: 30
                                    },
                                    grid: {
                                        left: '3%',
                                        right: '4%',
                                        bottom: '3%',
                                        containLabel: true
                                    },
                                    xAxis: {
                                        type: 'category',
                                        data: departments,
                                        axisLabel: {
                                            rotate: 45
                                        }
                                    },
                                    yAxis: {
                                        type: 'value',
                                        name: 'Days'
                                    },
                                    series: [
                                        {
                                            name: 'Days in Current Department',
                                            type: 'bar',
                                            data: currentLocationDays,
                                            itemStyle: {
                                                color: '#3498db'
                                            }
                                        },
                                        {
                                            name: 'Total Processing Days',
                                            type: 'bar',
                                            data: totalProcessingDays,
                                            itemStyle: {
                                                color: '#e74c3c'
                                            }
                                        }
                                    ]
                                };

                                chart.setOption(option);
                            }

                            // NEW: Update aging categories chart
                            function updateAgingCategoriesChart(data) {
                                const chart = echarts.init(document.getElementById('agingCategoriesChart'));
                                
                                const categories = data.summary.aging_categories;
                                const chartData = [
                                    { value: categories['0-7_days'], name: '0-7 Days', itemStyle: { color: '#27ae60' } },
                                    { value: categories['8-14_days'], name: '8-14 Days', itemStyle: { color: '#f39c12' } },
                                    { value: categories['15-30_days'], name: '15-30 Days', itemStyle: { color: '#e67e22' } },
                                    { value: categories['30_plus_days'], name: '30+ Days', itemStyle: { color: '#e74c3c' } }
                                ];

                                const option = {
                                    title: {
                                        text: 'Document Aging Distribution',
                                        left: 'center'
                                    },
                                    tooltip: {
                                        trigger: 'item',
                                        formatter: '{a} <br/>{b}: {c} documents ({d}%)'
                                    },
                                    series: [{
                                        name: 'Documents',
                                        type: 'pie',
                                        radius: '70%',
                                        data: chartData,
                                        emphasis: {
                                            itemStyle: {
                                                shadowBlur: 10,
                                                shadowOffsetX: 0,
                                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                                            }
                                        }
                                    }]
                                };

                                chart.setOption(option);
                            }

                            // NEW: Update department-specific table
                            function updateDepartmentSpecificTable(data) {
                                // This would update the existing department table with department-specific data
                                // For now, we'll use the existing table structure
                                console.log('Department-specific table data:', data);
                            }

                            function updateBottlenecksChart(data) {
                                const bottlenecksChart = echarts.init(document.getElementById('bottlenecksChart'));

                                const chartData = data.map(item => ({
                                    name: item.department_name,
                                    value: parseFloat(item.avg_invoice_processing_days || 0)
                                }));

                                const option = {
                                    title: {
                                        text: 'Top Processing Bottlenecks',
                                        left: 'center'
                                    },
                                    tooltip: {
                                        trigger: 'item',
                                        formatter: '{a} <br/>{b}: {c} days ({d}%)'
                                    },
                                    series: [{
                                        name: 'Processing Days',
                                        type: 'pie',
                                        radius: '50%',
                                        data: chartData,
                                        emphasis: {
                                            itemStyle: {
                                                shadowBlur: 10,
                                                shadowOffsetX: 0,
                                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                                            }
                                        }
                                    }]
                                };

                                bottlenecksChart.setOption(option);
                            }

                            function updateSlowDocumentsTable(data) {
                                const tbody = $('#slowDocumentsTableBody');
                                tbody.empty();

                                // Combine slow invoices and additional documents
                                const allSlowDocs = [
                                    ...data.slow_invoices.map(doc => ({
                                        ...doc,
                                        type: 'Invoice',
                                        number: doc.invoice_number
                                    })),
                                    ...data.slow_additional_documents.map(doc => ({
                                        ...doc,
                                        type: 'Additional Document',
                                        number: doc.document_number
                                    }))
                                ].sort((a, b) => b.processing_days - a.processing_days);

                                allSlowDocs.slice(0, 10).forEach(doc => {
                                    const row = `
                        <tr>
                            <td>
                                <strong>${doc.number}</strong><br>
                                <small class="text-muted">${doc.type}</small>
                            </td>
                            <td>${doc.department_name}</td>
                            <td>
                                <span class="badge badge-danger">${doc.processing_days} days</span>
                            </td>
                            <td>
                                <a href="${getDocumentShowUrl(doc)}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View Document
                                </a>
                            </td>
                        </tr>
                    `;
                                    tbody.append(row);
                                });

                                if (allSlowDocs.length === 0) {
                                    tbody.append(
                                        '<tr><td colspan="4" class="text-center text-muted">No slow processing documents found</td></tr>'
                                    );
                                }
                            }

                            function getDocumentShowUrl(doc) {
                                if (doc.type === 'Invoice') {
                                    return `{{ url('invoices') }}/${doc.id}`;
                                } else if (doc.type === 'Additional Document') {
                                    return `{{ url('additional-documents') }}/${doc.id}`;
                                }
                                return '#';
                            }

                            function showHelp() {
                                $('#helpModal').modal('show');
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

                                        // Get department data from invoices and additional_documents arrays
                                        const invoiceDept = monthData.data?.invoices?.find(inv => inv
                                            .department_id == dept.id);
                                        const docDept = monthData.data?.additional_documents?.find(doc => doc
                                            .department_id == dept.id);

                                        const avgInvoiceDays = invoiceDept?.avg_processing_days || 0;
                                        const avgDocDays = docDept?.avg_processing_days || 0;

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

                                window.open(
                                    `{{ url('api/v1/processing-analytics/export') }}?year=${year}&month=${month}&format=excel`);
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

                            // Load departments for department selection
                            function loadDepartments() {
                                // Add departments with correct IDs from database
                                const departmentSelect = document.getElementById('departmentSelect');

                                // Clear existing options except the first one
                                departmentSelect.innerHTML = '<option value="">Select Department</option>';

                                // Add Accounting department (ID: 15)
                                const accountingOption = document.createElement('option');
                                accountingOption.value = '15';
                                accountingOption.textContent = 'Accounting';
                                departmentSelect.appendChild(accountingOption);

                                // Add Logistic department (ID: 9)
                                const logisticOption = document.createElement('option');
                                logisticOption.value = '9';
                                logisticOption.textContent = 'Logistic';
                                departmentSelect.appendChild(logisticOption);
                            }

                            // Load department monthly performance data
                            function loadDepartmentMonthlyData() {
                                const departmentId = document.getElementById('departmentSelect').value;
                                const year = document.getElementById('departmentYearSelect').value;
                                const documentType = document.getElementById('departmentDocumentTypeSelect').value;

                                if (!departmentId) {
                                    toastr.error('Please select a department');
                                    return;
                                }

                                const button = document.getElementById('loadDepartmentData');
                                button.disabled = true;
                                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

                                fetch(
                                        `{{ url('api/v1/processing-analytics/department-monthly-performance') }}?year=${year}&department_id=${departmentId}&document_type=${documentType}`
                                        )
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            updateDepartmentMonthlyChart(data.data);
                                            updateDepartmentSummaryCards(data.data);
                                            $('#departmentSummaryCards').show();
                                        } else {
                                            toastr.error(data.error || 'Failed to load department data');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        toastr.error('Failed to load department data');
                                    })
                                    .finally(() => {
                                        button.disabled = false;
                                        button.innerHTML = '<i class="fas fa-sync-alt"></i> Load Data';
                                    });
                            }

                            // Update department monthly performance chart
                            function updateDepartmentMonthlyChart(data) {
                                const chart = echarts.init(document.getElementById('departmentMonthlyChart'));

                                const months = data.monthly_data.map(item => item.month_name);
                                const invoiceData = data.monthly_data.map(item => item.invoices?.avg_processing_days || 0);
                                const docData = data.monthly_data.map(item => item.additional_documents?.avg_processing_days || 0);
                                const totalData = data.monthly_data.map(item => item.avg_processing_days || 0);

                                const option = {
                                    title: {
                                        text: `${data.department.name} - Monthly Performance (${data.year})`,
                                        left: 'center'
                                    },
                                    tooltip: {
                                        trigger: 'axis',
                                        axisPointer: {
                                            type: 'cross'
                                        },
                                        formatter: function(params) {
                                            const month = params[0].axisValue;
                                            const monthData = data.monthly_data.find(m => m.month_name === month);
                                            let tooltip = `<strong>${month}</strong><br/>`;

                                            if (monthData.invoices.count > 0) {
                                                tooltip +=
                                                    `Invoices: ${monthData.invoices.count} docs, ${monthData.invoices.avg_processing_days} days avg<br/>`;
                                            }
                                            if (monthData.additional_documents.count > 0) {
                                                tooltip +=
                                                    `Documents: ${monthData.additional_documents.count} docs, ${monthData.additional_documents.avg_processing_days} days avg<br/>`;
                                            }
                                            tooltip +=
                                                `Total: ${monthData.total_documents} docs, ${monthData.avg_processing_days} days avg`;

                                            return tooltip;
                                        }
                                    },
                                    legend: {
                                        data: ['Invoices', 'Additional Documents', 'Overall Average'],
                                        top: 'bottom'
                                    },
                                    grid: {
                                        left: '3%',
                                        right: '4%',
                                        bottom: '15%',
                                        containLabel: true
                                    },
                                    xAxis: {
                                        type: 'category',
                                        data: months,
                                        axisLabel: {
                                            rotate: 45
                                        }
                                    },
                                    yAxis: {
                                        type: 'value',
                                        name: 'Processing Days',
                                        minInterval: 0.5
                                    },
                                    series: [{
                                            name: 'Invoices',
                                            type: 'line',
                                            data: invoiceData,
                                            itemStyle: {
                                                color: '#3498db'
                                            },
                                            lineStyle: {
                                                width: 3
                                            },
                                            symbol: 'circle',
                                            symbolSize: 6
                                        },
                                        {
                                            name: 'Additional Documents',
                                            type: 'line',
                                            data: docData,
                                            itemStyle: {
                                                color: '#e74c3c'
                                            },
                                            lineStyle: {
                                                width: 3
                                            },
                                            symbol: 'circle',
                                            symbolSize: 6
                                        },
                                        {
                                            name: 'Overall Average',
                                            type: 'line',
                                            data: totalData,
                                            itemStyle: {
                                                color: '#2ecc71'
                                            },
                                            lineStyle: {
                                                width: 4,
                                                type: 'dashed'
                                            },
                                            symbol: 'diamond',
                                            symbolSize: 8
                                        }
                                    ]
                                };

                                chart.setOption(option);
                            }

                            // Update department summary cards
                            function updateDepartmentSummaryCards(data) {
                                document.getElementById('totalDocuments').textContent = data.summary.total_documents;
                                document.getElementById('avgProcessingDays').textContent = data.summary.avg_processing_days;
                                document.getElementById('bestMonth').textContent = data.summary.best_month ? data.summary.best_month
                                    .month_name : '-';
                                document.getElementById('worstMonth').textContent = data.summary.worst_month ? data.summary
                                    .worst_month.month_name : '-';
                            }
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

                <!-- Help Modal -->
                <div class="modal fade" id="helpModal" tabindex="-1" role="dialog"
                    aria-labelledby="helpModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="helpModalLabel">
                                    <i class="fas fa-question-circle"></i> Processing Analytics Help
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-chart-line"></i> Dashboard Features</h6>
                                        <ul>
                                            <li><strong>Basic Analysis:</strong> Shows overall processing statistics
                                            </li>
                                            <li><strong>Accurate Analysis:</strong> Shows processing times based on
                                                actual distribution
                                                workflow</li>
                                            <li><strong>Processing Bottlenecks:</strong> Identifies departments with
                                                longest processing
                                                times</li>
                                            <li><strong>Slow Processing Documents:</strong> Lists documents exceeding
                                                processing
                                                thresholds</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-route"></i> Document Journey Tracking</h6>
                                        <ul>
                                            <li>Click "View Document" in Slow Processing Documents table</li>
                                            <li>On the document detail page, click "Load Document Journey"</li>
                                            <li>View step-by-step timeline of document processing</li>
                                            <li>See processing time at each department</li>
                                        </ul>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-12">
                                        <h6><i class="fas fa-info-circle"></i> How Processing Days Are Calculated</h6>
                                        <div class="alert alert-info">
                                            <strong>Basic Analysis:</strong> Days from receive_date to current date<br>
                                            <strong>Accurate Analysis:</strong> Days from receive_date to when document
                                            was sent to next
                                            department
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
