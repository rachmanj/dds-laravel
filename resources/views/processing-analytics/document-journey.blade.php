@extends('layouts.main')

@section('title', 'Document Journey Tracking')

@section('content')
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Document Journey Tracking</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('processing-analytics.index') }}">Processing
                                    Analytics</a></li>
                            <li class="breadcrumb-item active">Document Journey</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="container-fluid">
                <!-- Document Search Form -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Search Document</h3>
                            </div>
                            <div class="card-body">
                                <form id="documentSearchForm">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="documentType">Document Type</label>
                                                <select class="form-control" id="documentType" name="document_type">
                                                    <option value="invoice">Invoice</option>
                                                    <option value="additional_document">Additional Document</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="documentId">Document ID</label>
                                                <input type="number" class="form-control" id="documentId"
                                                    name="document_id" placeholder="Enter document ID">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fas fa-search"></i> Track Journey
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Information -->
                <div class="row" id="documentInfo" style="display: none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Document Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Document Number:</strong>
                                        <p id="documentNumber" class="text-primary"></p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Document Type:</strong>
                                        <p id="documentTypeInfo" class="text-info"></p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Receive Date:</strong>
                                        <p id="receiveDate" class="text-success"></p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Current Status:</strong>
                                        <p id="currentStatus" class="text-warning"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Processing Timeline -->
                <div class="row" id="timelineSection" style="display: none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Processing Timeline</h3>
                                <div class="card-tools">
                                    <span class="badge badge-info" id="totalProcessingDays">Total: 0 days</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="timeline" id="processingTimeline">
                                    <!-- Timeline items will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Processing Statistics -->
                <div class="row" id="statisticsSection" style="display: none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Processing Statistics</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Processing Days</span>
                                                <span class="info-box-number" id="totalDays">0</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i class="fas fa-building"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Departments Visited</span>
                                                <span class="info-box-number" id="departmentsVisited">0</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning"><i
                                                    class="fas fa-chart-line"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Average per Department</span>
                                                <span class="info-box-number" id="avgPerDepartment">0</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger"><i
                                                    class="fas fa-exclamation-triangle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Longest Stay</span>
                                                <span class="info-box-number" id="longestStay">0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div class="row" id="errorSection" style="display: none;">
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            <p id="errorMessage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50px;
            height: 100%;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 80px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 42px;
            top: 15px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #007bff;
            border: 3px solid #fff;
            box-shadow: 0 0 0 3px #007bff;
        }

        .timeline-item.completed::before {
            background: #28a745;
            box-shadow: 0 0 0 3px #28a745;
        }

        .timeline-item.in-progress::before {
            background: #ffc107;
            box-shadow: 0 0 0 3px #ffc107;
        }

        .timeline-item.pending::before {
            background: #6c757d;
            box-shadow: 0 0 0 3px #6c757d;
        }

        .timeline-content {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timeline-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }

        .timeline-title {
            font-weight: bold;
            color: #495057;
            margin: 0;
        }

        .timeline-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-completed {
            background-color: #28a745;
            color: white;
        }

        .badge-in-progress {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-pending {
            background-color: #6c757d;
            color: white;
        }

        .timeline-details {
            color: #6c757d;
            font-size: 14px;
        }

        .timeline-duration {
            font-weight: bold;
            color: #007bff;
        }

        .processing-chart {
            height: 300px;
            margin-top: 20px;
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('documentSearchForm');
            const documentInfo = document.getElementById('documentInfo');
            const timelineSection = document.getElementById('timelineSection');
            const statisticsSection = document.getElementById('statisticsSection');
            const errorSection = document.getElementById('errorSection');

            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const documentId = document.getElementById('documentId').value;
                const documentType = document.getElementById('documentType').value;

                if (!documentId) {
                    showError('Please enter a document ID');
                    return;
                }

                fetchDocumentTimeline(documentId, documentType);
            });

            function fetchDocumentTimeline(documentId, documentType) {
                // Show loading state
                showLoading();

                fetch(
                        `{{ url('api/v1/processing-analytics/document-timeline') }}?document_id=${documentId}&document_type=${documentType}`)
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();

                        if (data.success) {
                            displayDocumentInfo(data.data);
                            displayTimeline(data.data);
                            displayStatistics(data.data);
                            showSections();
                        } else {
                            showError(data.error || 'Failed to fetch document timeline');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        showError('Network error: ' + error.message);
                    });
            }

            function displayDocumentInfo(data) {
                document.getElementById('documentNumber').textContent = data.document.number;
                document.getElementById('documentTypeInfo').textContent = data.document.type.charAt(0)
                .toUpperCase() + data.document.type.slice(1);
                document.getElementById('receiveDate').textContent = new Date(data.document.receive_date)
                    .toLocaleDateString();
                document.getElementById('currentStatus').textContent = data.current_status.charAt(0).toUpperCase() +
                    data.current_status.slice(1);
            }

            function displayTimeline(data) {
                const timelineContainer = document.getElementById('processingTimeline');
                timelineContainer.innerHTML = '';

                if (data.timeline.length === 0) {
                    timelineContainer.innerHTML =
                        '<div class="text-center text-muted"><p>No processing timeline available for this document.</p></div>';
                    return;
                }

                data.timeline.forEach((step, index) => {
                    const timelineItem = createTimelineItem(step, index);
                    timelineContainer.appendChild(timelineItem);
                });

                document.getElementById('totalProcessingDays').textContent =
                    `Total: ${data.total_processing_days} days`;
            }

            function createTimelineItem(step, index) {
                const item = document.createElement('div');
                item.className = `timeline-item ${step.status}`;

                const statusClass = getStatusClass(step.status);
                const statusText = getStatusText(step.status);

                item.innerHTML = `
            <div class="timeline-content">
                <div class="timeline-header">
                    <h4 class="timeline-title">Step ${step.step}: ${step.department}</h4>
                    <span class="timeline-badge ${statusClass}">${statusText}</span>
                </div>
                <div class="timeline-details">
                    <p><strong>Start Date:</strong> ${new Date(step.start_date).toLocaleDateString()}</p>
                    ${step.end_date ? `<p><strong>End Date:</strong> ${new Date(step.end_date).toLocaleDateString()}</p>` : ''}
                    <p><strong>Processing Days:</strong> <span class="timeline-duration">${step.processing_days} days</span></p>
                    ${step.next_department ? `<p><strong>Next Department:</strong> ${step.next_department}</p>` : ''}
                    ${step.distribution_number ? `<p><strong>Distribution:</strong> ${step.distribution_number}</p>` : ''}
                </div>
            </div>
        `;

                return item;
            }

            function getStatusClass(status) {
                switch (status) {
                    case 'completed':
                        return 'badge-completed';
                    case 'in_progress':
                        return 'badge-in-progress';
                    case 'pending':
                        return 'badge-pending';
                    default:
                        return 'badge-pending';
                }
            }

            function getStatusText(status) {
                switch (status) {
                    case 'completed':
                        return 'Completed';
                    case 'in_progress':
                        return 'In Progress';
                    case 'pending':
                        return 'Pending';
                    default:
                        return 'Unknown';
                }
            }

            function displayStatistics(data) {
                const timeline = data.timeline;
                const totalDays = data.total_processing_days;
                const departmentsVisited = timeline.length;
                const avgPerDepartment = departmentsVisited > 0 ? Math.round(totalDays / departmentsVisited) : 0;
                const longestStay = Math.max(...timeline.map(step => step.processing_days));

                document.getElementById('totalDays').textContent = totalDays;
                document.getElementById('departmentsVisited').textContent = departmentsVisited;
                document.getElementById('avgPerDepartment').textContent = avgPerDepartment;
                document.getElementById('longestStay').textContent = longestStay;
            }

            function showSections() {
                documentInfo.style.display = 'block';
                timelineSection.style.display = 'block';
                statisticsSection.style.display = 'block';
                errorSection.style.display = 'none';
            }

            function showError(message) {
                document.getElementById('errorMessage').textContent = message;
                errorSection.style.display = 'block';
                documentInfo.style.display = 'none';
                timelineSection.style.display = 'none';
                statisticsSection.style.display = 'none';
            }

            function showLoading() {
                // You can add a loading spinner here
                console.log('Loading...');
            }

            function hideLoading() {
                console.log('Loading complete');
            }

            // Auto-populate document ID if provided in URL
            const urlParams = new URLSearchParams(window.location.search);
            const documentId = urlParams.get('document_id');
            const documentType = urlParams.get('document_type') || 'invoice';

            if (documentId) {
                document.getElementById('documentId').value = documentId;
                document.getElementById('documentType').value = documentType;
                fetchDocumentTimeline(documentId, documentType);
            }
        });
    </script>
@endpush
