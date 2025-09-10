<div class="row">
    <!-- Summary Cards -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="total-invoices">0</h3>
                <p>Total Invoices</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-invoice"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="invoices-without-sap">0</h3>
                <p>Without SAP Doc</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="invoices-with-sap">0</h3>
                <p>With SAP Doc</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3 id="completion-percentage">0%</h3>
                <p>Completion Rate</p>
            </div>
            <div class="icon">
                <i class="fas fa-percentage"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Updates Over Time Chart -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">SAP Doc Updates Over Time (Last 30 Days)</h3>
            </div>
            <div class="card-body">
                <canvas id="updatesChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Updates -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Updates</h3>
            </div>
            <div class="card-body">
                <div id="recent-updates">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var updatesChart;
    
    $(function() {
        // Load dashboard data
        refreshDashboard();
    });
    
    function refreshDashboard() {
        $.ajax({
            url: '{{ route("invoices.sap-update.dashboard") }}',
            type: 'GET',
            success: function(response) {
                updateDashboardMetrics(response.metrics);
                updateRecentUpdates(response.recent_updates);
                createUpdatesChart(response.updates_over_time);
            },
            error: function(xhr) {
                console.error('Failed to load dashboard data:', xhr);
            }
        });
    }

    function updateDashboardMetrics(metrics) {
        $('#total-invoices').text(metrics.total_invoices);
        $('#invoices-without-sap').text(metrics.invoices_without_sap);
        $('#invoices-with-sap').text(metrics.invoices_with_sap);
        $('#completion-percentage').text(metrics.completion_percentage + '%');
        
        // Update tab badges
        $('#without-sap-count').text(metrics.invoices_without_sap);
        $('#with-sap-count').text(metrics.invoices_with_sap);
    }

    function updateRecentUpdates(updates) {
        let html = '';
        if (updates.length === 0) {
            html = '<div class="text-muted">No recent updates</div>';
        } else {
            updates.forEach(function(update) {
                html += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <strong>${update.invoice_number}</strong><br>
                        <small class="text-muted">SAP: ${update.sap_doc}</small>
                    </div>
                    <small class="text-muted">${formatDate(update.updated_at)}</small>
                </div>
            `;
            });
        }
        $('#recent-updates').html(html);
    }

    function createUpdatesChart(data) {
        const ctx = document.getElementById('updatesChart').getContext('2d');
        
        if (updatesChart) {
            updatesChart.destroy();
        }

        updatesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => formatDate(item.date)),
                datasets: [{
                    label: 'SAP Doc Updates',
                    data: data.map(item => item.count),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
    
    // Make refreshDashboard available globally
    window.refreshDashboard = refreshDashboard;
</script>