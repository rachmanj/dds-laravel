/**
 * Analytics Integration for Distribution Show Page
 * Handles performance metrics, user behavior analytics, document flow analytics,
 * real-time dashboards, and predictive analytics
 */

class DistributionAnalytics {
    constructor() {
        this.metrics = {
            verificationTime: [],
            errorCount: 0,
            completionRate: 0,
            userActions: [],
            documentFlow: [],
            performanceMetrics: {
                startTime: Date.now(),
                pageLoadTime: 0,
                ajaxCalls: 0,
                ajaxErrors: 0,
            },
        };

        this.realTimeData = {
            activeUsers: 0,
            currentStatus: "loading",
            estimatedCompletion: null,
            bottlenecks: [],
        };

        this.predictiveModels = {
            completionTime: null,
            errorProbability: null,
            efficiencyScore: null,
        };

        // Throttling controls
        this.analyticsThrottle = null;
        this.lastAnalyticsSend = 0;
        this.analyticsInterval = null;

        this.init();
    }

    init() {
        this.trackPageLoad();
        this.setupRealTimeUpdates();
        this.setupPerformanceMonitoring();
        this.setupUserBehaviorTracking();
        this.setupDocumentFlowTracking();
        this.setupPredictiveAnalytics();
        this.createAnalyticsDashboard();
    }

    trackPageLoad() {
        const loadTime = Date.now() - this.metrics.performanceMetrics.startTime;
        this.metrics.performanceMetrics.pageLoadTime = loadTime;

        this.trackUserAction("page_load", {
            loadTime: loadTime,
            url: window.location.href,
            timestamp: Date.now(),
        });

        // Send initial analytics after a short delay
        setTimeout(() => {
            this.sendAnalytics();
        }, 2000);
    }

    setupRealTimeUpdates() {
        // Create real-time dashboard
        this.createRealTimeDashboard();

        // Update every 30 seconds
        setInterval(() => {
            this.updateRealTimeData();
        }, 30000);

        // Send analytics every 300 seconds instead of on every action
        this.analyticsInterval = setInterval(() => {
            this.sendAnalytics();
        }, 300000);

        // Listen for distribution status changes
        $(document).on("distributionStatusChanged", (e, data) => {
            this.trackDistributionStatusChange(data);
        });
    }

    createRealTimeDashboard() {
        // Add responsive CSS for analytics dashboard
        const responsiveStyles = `
            <style>
                @media (min-width: 768px) {
                    .analytics-dashboard {
                        left: 280px !important; /* Desktop: sidebar is 250px wide */
                    }
                }
                @media (max-width: 767px) {
                    .analytics-dashboard {
                        left: 20px !important; /* Mobile: sidebar collapses */
                        width: calc(100% - 40px) !important;
                        max-width: 300px !important;
                    }
                }
            </style>
        `;

        const dashboardHtml = `
            ${responsiveStyles}
            <div class="analytics-dashboard" style="position: fixed; bottom: 20px; left: 280px; width: 320px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; display: none;">
                <div class="dashboard-header" style="background: #007bff; color: white; padding: 10px; border-radius: 8px 8px 0 0;">
                    <h6 style="margin: 0; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-chart-line"></i> Live Analytics</span>
                        <button class="btn btn-sm btn-outline-light" id="toggleAnalytics" style="padding: 2px 8px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </h6>
                </div>
                <div class="dashboard-body" style="padding: 15px;">
                    <div class="metric-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Status:</span>
                        <span class="status-indicator" id="currentStatus">Loading...</span>
                    </div>
                    <div class="metric-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Completion:</span>
                        <span id="completionRate">0%</span>
                    </div>
                    <div class="metric-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Est. Time:</span>
                        <span id="estimatedCompletion">Calculating...</span>
                    </div>
                    <div class="metric-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Efficiency:</span>
                        <span id="efficiencyScore">N/A</span>
                    </div>
                    <div class="metric-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Errors:</span>
                        <span id="errorCount">0</span>
                    </div>
                    <div class="bottlenecks" style="margin-top: 10px;">
                        <small class="text-muted">Bottlenecks:</small>
                        <div id="bottlenecksList" style="font-size: 12px; color: #dc3545;"></div>
                    </div>
                </div>
            </div>
        `;

        $("body").append(dashboardHtml);

        // Toggle dashboard visibility
        $("#toggleAnalytics").click(() => {
            $(".analytics-dashboard").toggle();
        });

        // Dashboard is hidden by default (display: none in CSS)
        // Users can show it by clicking the Analytics button
    }

    setupPerformanceMonitoring() {
        // Monitor AJAX calls
        const originalAjax = $.ajax;
        const self = this;

        $.ajax = function (options) {
            self.metrics.performanceMetrics.ajaxCalls++;
            const startTime = Date.now();

            return originalAjax.call(this, options).always(function () {
                const duration = Date.now() - startTime;
                self.trackAjaxCall(options.url, duration, this.status);

                if (this.status >= 400) {
                    self.metrics.performanceMetrics.ajaxErrors++;
                    self.trackError("ajax_error", {
                        url: options.url,
                        status: this.status,
                        duration: duration,
                    });
                }
            });
        };

        // Monitor page performance
        if (window.performance && window.performance.timing) {
            const timing = window.performance.timing;
            const loadTime = timing.loadEventEnd - timing.navigationStart;
            this.metrics.performanceMetrics.pageLoadTime = loadTime;
        }
    }

    setupUserBehaviorTracking() {
        // Track user interactions
        $(document).on("click", ".btn, .modal, .form-control", (e) => {
            this.trackUserAction("click", {
                element: e.target.tagName,
                className: e.target.className,
                id: e.target.id,
                text: $(e.target).text().trim(),
            });
        });

        // Track form submissions
        $(document).on("submit", "form", (e) => {
            this.trackUserAction("form_submit", {
                formId: e.target.id,
                formAction: e.target.action,
            });
        });

        // Track modal interactions
        $(document).on("show.bs.modal", ".modal", (e) => {
            this.trackUserAction("modal_open", {
                modalId: e.target.id,
            });
        });

        // Track verification actions
        $(document).on(
            "click",
            "#selectAllVerified, #selectAllVerifiedReceiver",
            (e) => {
                this.trackUserAction("bulk_verify", {
                    action: "select_all_verified",
                });
            }
        );
    }

    setupDocumentFlowTracking() {
        // Track document status changes
        $(document).on("change", ".document-status", (e) => {
            const documentId = $(e.target).data("document-id");
            const newStatus = $(e.target).val();

            this.trackDocumentFlow(documentId, "status_change", {
                newStatus: newStatus,
                timestamp: Date.now(),
            });
        });

        // Track verification completion
        $(document).on("verificationCompleted", (e, data) => {
            this.trackDocumentFlow(data.documentId, "verification_completed", {
                status: data.status,
                duration: data.duration,
                timestamp: Date.now(),
            });
        });

        // Track distribution workflow steps
        $(document).on("workflowStepCompleted", (e, data) => {
            this.trackDocumentFlow("distribution", "workflow_step", {
                step: data.step,
                duration: data.duration,
                timestamp: Date.now(),
            });
        });
    }

    setupPredictiveAnalytics() {
        // Calculate completion time prediction
        this.calculateCompletionPrediction();

        // Calculate error probability
        this.calculateErrorProbability();

        // Calculate efficiency score
        this.calculateEfficiencyScore();

        // Update predictions every 2 minutes to reduce load
        setInterval(() => {
            this.updatePredictions();
        }, 120000);
    }

    trackUserAction(action, context) {
        const actionData = {
            action: action,
            context: context,
            timestamp: Date.now(),
            userId: window.currentUserId || "anonymous",
            sessionId: this.getSessionId(),
        };

        this.metrics.userActions.push(actionData);

        // Keep only last 100 actions to prevent memory issues
        if (this.metrics.userActions.length > 100) {
            this.metrics.userActions.shift();
        }

        // Don't send analytics on every action - let the interval handle it
    }

    trackDocumentFlow(documentId, event, data) {
        const flowData = {
            documentId: documentId,
            event: event,
            data: data,
            timestamp: Date.now(),
        };

        this.metrics.documentFlow.push(flowData);

        // Keep only last 50 flow events
        if (this.metrics.documentFlow.length > 50) {
            this.metrics.documentFlow.shift();
        }

        // Update real-time data
        this.updateRealTimeData();
    }

    trackAjaxCall(url, duration, status) {
        this.trackUserAction("ajax_call", {
            url: url,
            duration: duration,
            status: status,
        });
    }

    trackError(errorType, context) {
        this.metrics.errorCount++;

        this.trackUserAction("error", {
            errorType: errorType,
            context: context,
            timestamp: Date.now(),
        });

        // Update real-time dashboard
        this.updateRealTimeData();
    }

    calculateCompletionPrediction() {
        const totalDocuments = $(".document-checkbox").length;
        const verifiedDocuments = $(".document-checkbox:checked").length;
        const completionRate =
            totalDocuments > 0 ? (verifiedDocuments / totalDocuments) * 100 : 0;

        this.metrics.completionRate = completionRate;

        // Simple prediction based on current rate
        if (completionRate > 0) {
            const remainingDocuments = totalDocuments - verifiedDocuments;
            const averageTimePerDocument =
                this.calculateAverageVerificationTime();
            const estimatedTime = remainingDocuments * averageTimePerDocument;

            this.predictiveModels.completionTime = estimatedTime;
            this.realTimeData.estimatedCompletion =
                this.formatTime(estimatedTime);
        }
    }

    calculateErrorProbability() {
        const totalActions = this.metrics.userActions.length;
        const errorActions = this.metrics.userActions.filter(
            (action) =>
                action.action === "error" || action.context?.status >= 400
        ).length;

        this.predictiveModels.errorProbability =
            totalActions > 0 ? (errorActions / totalActions) * 100 : 0;
    }

    calculateEfficiencyScore() {
        const verificationTimes = this.metrics.verificationTime;
        if (verificationTimes.length === 0) {
            this.predictiveModels.efficiencyScore = null;
            return;
        }

        const averageTime =
            verificationTimes.reduce((a, b) => a + b, 0) /
            verificationTimes.length;
        const baselineTime = 30000; // 30 seconds baseline
        const efficiency = Math.max(
            0,
            Math.min(100, (baselineTime / averageTime) * 100)
        );

        this.predictiveModels.efficiencyScore = Math.round(efficiency);
    }

    calculateAverageVerificationTime() {
        if (this.metrics.verificationTime.length === 0) {
            return 30000; // Default 30 seconds
        }

        return (
            this.metrics.verificationTime.reduce((a, b) => a + b, 0) /
            this.metrics.verificationTime.length
        );
    }

    updateRealTimeData() {
        // Update completion rate
        $("#completionRate").text(
            `${Math.round(this.metrics.completionRate)}%`
        );

        // Update estimated completion
        if (this.realTimeData.estimatedCompletion) {
            $("#estimatedCompletion").text(
                this.realTimeData.estimatedCompletion
            );
        }

        // Update efficiency score
        if (this.predictiveModels.efficiencyScore) {
            $("#efficiencyScore").text(
                `${this.predictiveModels.efficiencyScore}%`
            );
        }

        // Update error count
        $("#errorCount").text(this.metrics.errorCount);

        // Update bottlenecks
        this.identifyBottlenecks();
    }

    identifyBottlenecks() {
        const bottlenecks = [];

        // Check for slow verification
        const avgVerificationTime = this.calculateAverageVerificationTime();
        if (avgVerificationTime > 60000) {
            // More than 1 minute
            bottlenecks.push("Slow verification process");
        }

        // Check for high error rate
        if (this.predictiveModels.errorProbability > 10) {
            bottlenecks.push("High error rate detected");
        }

        // Check for incomplete documents
        if (
            this.metrics.completionRate < 50 &&
            this.metrics.userActions.length > 10
        ) {
            bottlenecks.push("Low completion rate");
        }

        this.realTimeData.bottlenecks = bottlenecks;

        // Update UI
        const bottlenecksList = $("#bottlenecksList");
        if (bottlenecks.length > 0) {
            bottlenecksList.html(bottlenecks.map((b) => `• ${b}`).join("<br>"));
        } else {
            bottlenecksList.html(
                '<span class="text-success">No bottlenecks detected</span>'
            );
        }
    }

    updatePredictions() {
        this.calculateCompletionPrediction();
        this.calculateErrorProbability();
        this.calculateEfficiencyScore();
        this.updateRealTimeData();
    }

    formatTime(milliseconds) {
        const seconds = Math.floor(milliseconds / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);

        if (hours > 0) {
            return `${hours}h ${minutes % 60}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${seconds % 60}s`;
        } else {
            return `${seconds}s`;
        }
    }

    getSessionId() {
        if (!this.sessionId) {
            this.sessionId =
                "session_" +
                Date.now() +
                "_" +
                Math.random().toString(36).substr(2, 9);
        }
        return this.sessionId;
    }

    sendAnalytics() {
        // Prevent sending analytics too frequently
        const now = Date.now();
        if (now - this.lastAnalyticsSend < 250000) {
            // Minimum 250 seconds between calls
            return;
        }

        // Throttle analytics calls to prevent excessive requests
        if (this.analyticsThrottle) {
            clearTimeout(this.analyticsThrottle);
        }

        this.analyticsThrottle = setTimeout(() => {
            // Send analytics data to backend
            $.ajax({
                url: "/api/v1/analytics/distribution",
                type: "POST",
                data: {
                    metrics: this.metrics,
                    realTimeData: this.realTimeData,
                    predictiveModels: this.predictiveModels,
                    _token: $('meta[name="csrf-token"]').attr("content"),
                },
                success: (response) => {
                    if (response.success) {
                        this.lastAnalyticsSend = Date.now();
                    }
                },
                error: (xhr) => {
                    console.warn("Failed to send analytics:", xhr.statusText);
                },
            });
        }, 1000); // Small delay to batch any rapid calls
    }

    createAnalyticsDashboard() {
        // Add responsive CSS for analytics toggle button
        const toggleButtonStyles = `
            <style>
                @media (min-width: 768px) {
                    #showAnalytics {
                        left: 270px !important; /* Desktop: position after sidebar */
                    }
                }
                @media (max-width: 767px) {
                    #showAnalytics {
                        left: 20px !important; /* Mobile: sidebar collapses */
                    }
                }
            </style>
        `;

        // Add analytics toggle button
        const toggleButton = `
            ${toggleButtonStyles}
            <button class="btn btn-sm btn-outline-info" id="showAnalytics" 
                    style="position: fixed; bottom: 20px; left: 270px; z-index: 1001;">
                <i class="fas fa-chart-line"></i> Analytics
            </button>
        `;

        $("body").append(toggleButton);

        $("#showAnalytics").click(() => {
            $(".analytics-dashboard").toggle();
        });
    }

    // Public methods for external use
    trackVerificationTime(documentId, startTime, endTime) {
        const duration = endTime - startTime;
        this.metrics.verificationTime.push(duration);

        // Keep only last 20 verification times
        if (this.metrics.verificationTime.length > 20) {
            this.metrics.verificationTime.shift();
        }

        this.trackDocumentFlow(documentId, "verification_completed", {
            duration: duration,
            timestamp: endTime,
        });
    }

    trackWorkflowStep(step, duration) {
        this.trackDocumentFlow("distribution", "workflow_step", {
            step: step,
            duration: duration,
            timestamp: Date.now(),
        });
    }

    getInsights() {
        return {
            averageVerificationTime: this.calculateAverageVerificationTime(),
            completionRate: this.metrics.completionRate,
            errorRate: this.predictiveModels.errorProbability,
            efficiencyScore: this.predictiveModels.efficiencyScore,
            bottlenecks: this.realTimeData.bottlenecks,
            estimatedCompletion: this.realTimeData.estimatedCompletion,
        };
    }

    // Cleanup method to stop analytics when page unloads
    cleanup() {
        if (this.analyticsThrottle) {
            clearTimeout(this.analyticsThrottle);
        }
        if (this.analyticsInterval) {
            clearInterval(this.analyticsInterval);
        }
    }
}

// Initialize when document is ready
$(document).ready(function () {
    window.distributionAnalytics = new DistributionAnalytics();

    // Cleanup on page unload
    $(window).on("beforeunload", function () {
        if (window.distributionAnalytics) {
            window.distributionAnalytics.cleanup();
        }
    });
});
