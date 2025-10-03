/**
 * Distribution Show Page JavaScript
 * Handles workflow management, document verification, and AJAX operations
 */

class DistributionShow {
    constructor() {
        this.selectedDocuments = new Set();
        this.selectedDocumentsReceiver = new Set();
        this.isLoading = false;
        this.init();
    }

    init() {
        this.initSenderVerification();
        this.initReceiverVerification();
        this.initWorkflowActions();
        this.initCancelActions();
        this.initSyncActions();
        this.initLoadingStates();
        this.initMobileFeatures();
        this.initBulkOperations();
        this.initAnalytics();
        this.initAccessibility();
    }

    // Loading States Management
    initLoadingStates() {
        // Add loading overlay styles
        $("head").append(`
            <style>
                .loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                }
                .loading-spinner {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    text-align: center;
                }
                .spinner-border {
                    width: 2rem;
                    height: 2rem;
                    border: 0.25em solid currentColor;
                    border-right-color: transparent;
                    border-radius: 50%;
                    animation: spinner-border 0.75s linear infinite;
                }
                @keyframes spinner-border {
                    to { transform: rotate(360deg); }
                }
                .btn-loading {
                    position: relative;
                    pointer-events: none;
                }
                .btn-loading::after {
                    content: "";
                    position: absolute;
                    width: 16px;
                    height: 16px;
                    top: 50%;
                    left: 50%;
                    margin-left: -8px;
                    margin-top: -8px;
                    border: 2px solid transparent;
                    border-top-color: #ffffff;
                    border-radius: 50%;
                    animation: spinner-border 0.75s linear infinite;
                }
                .progress-container {
                    background: #f8f9fa;
                    border-radius: 4px;
                    padding: 10px;
                    margin: 10px 0;
                }
                .progress-bar {
                    background: #007bff;
                    height: 20px;
                    border-radius: 4px;
                    transition: width 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 12px;
                    font-weight: bold;
                }
                
                /* Mobile Responsive Styles */
                @media (max-width: 768px) {
                    .modal-dialog {
                        margin: 10px;
                        max-width: calc(100% - 20px);
                    }
                    
                    .modal-body {
                        padding: 15px;
                        max-height: 70vh;
                        overflow-y: auto;
                    }
                    
                    .table-responsive {
                        font-size: 14px;
                    }
                    
                    .table-responsive th,
                    .table-responsive td {
                        padding: 8px 4px;
                        white-space: nowrap;
                    }
                    
                    .btn {
                        padding: 8px 12px;
                        font-size: 14px;
                        margin: 2px;
                    }
                    
                    .btn-sm {
                        padding: 6px 10px;
                        font-size: 12px;
                    }
                    
                    .card-body {
                        padding: 15px;
                    }
                    
                    .verification-summary-card {
                        margin-bottom: 15px;
                    }
                    
                    .workflow-actions .btn {
                        width: 100%;
                        margin-bottom: 10px;
                    }
                    
                    .loading-spinner {
                        padding: 15px;
                        margin: 20px;
                    }
                    
                    .progress-container {
                        margin: 10px 0;
                        padding: 8px;
                    }
                    
                    .badge {
                        font-size: 11px;
                        padding: 4px 6px;
                    }
                    
                    /* Touch-friendly elements */
                    .document-checkbox,
                    .document-checkbox-receiver {
                        transform: scale(1.2);
                        margin: 5px;
                    }
                    
                    select.form-control,
                    input.form-control {
                        min-height: 44px;
                        font-size: 16px;
                    }
                    
                    /* Modal header adjustments */
                    .modal-header {
                        padding: 15px;
                        border-bottom: 1px solid #dee2e6;
                    }
                    
                    .modal-title {
                        font-size: 18px;
                    }
                    
                    /* Footer adjustments */
                    .modal-footer {
                        padding: 15px;
                        flex-direction: column;
                    }
                    
                    .modal-footer .btn {
                        width: 100%;
                        margin: 5px 0;
                    }
                }
                
                @media (max-width: 576px) {
                    .modal-dialog {
                        margin: 5px;
                        max-width: calc(100% - 10px);
                    }
                    
                    .table-responsive {
                        font-size: 12px;
                    }
                    
                    .btn {
                        padding: 10px 15px;
                        font-size: 16px;
                    }
                    
                    .card-header h4,
                    .card-header h5 {
                        font-size: 16px;
                    }
                    
                    .workflow-progress {
                        flex-direction: column;
                        align-items: center;
                    }
                    
                    .workflow-progress .step {
                        margin: 5px 0;
                        width: 100%;
                        text-align: center;
                    }
                    
                    .btn-touch {
                        transform: scale(0.95);
                        transition: transform 0.1s ease;
                    }
                    
                    .modal-mobile .modal-dialog {
                        margin: 0;
                        height: 100vh;
                        max-height: 100vh;
                    }
                    
                    .modal-mobile .modal-content {
                        height: 100vh;
                        border-radius: 0;
                    }
                    
                    .mobile-device .table-responsive {
                        border: none;
                    }
                    
                    .mobile-device .card {
                        border: none;
                        box-shadow: none;
                    }
                    
                    .alert-sm {
                        padding: 8px 12px;
                        font-size: 13px;
                        margin-bottom: 10px;
                    }
                    
                    .alert-sm .fas {
                        margin-right: 5px;
                    }
                }
            </style>
        `);
    }

    showLoading(message = "Processing...") {
        if (this.isLoading) return;

        this.isLoading = true;
        $("body").append(`
            <div class="loading-overlay" id="global-loading">
                <div class="loading-spinner">
                    <div class="spinner-border text-primary mb-2"></div>
                    <div>${message}</div>
                </div>
            </div>
        `);
    }

    hideLoading() {
        this.isLoading = false;
        $("#global-loading").remove();
    }

    setButtonLoading(button, loading = true) {
        if (loading) {
            $(button).addClass("btn-loading").prop("disabled", true);
            $(button).data("original-text", $(button).text());
            $(button).text("Processing...");
        } else {
            $(button).removeClass("btn-loading").prop("disabled", false);
            const originalText = $(button).data("original-text");
            if (originalText) {
                $(button).text(originalText);
            }
        }
    }

    showProgress(container, message, progress = 0) {
        const progressHtml = `
            <div class="progress-container" id="progress-container">
                <div class="progress-bar" style="width: ${progress}%">
                    ${progress}%
                </div>
                <div class="mt-2 text-center">
                    <small class="text-muted">${message}</small>
                </div>
            </div>
        `;

        $(container).html(progressHtml);
    }

    updateProgress(progress, message = null) {
        const progressBar = $("#progress-container .progress-bar");
        if (progressBar.length) {
            progressBar.css("width", `${progress}%`).text(`${progress}%`);
            if (message) {
                $("#progress-container .text-muted").text(message);
            }
        }
    }

    hideProgress(container) {
        $(container).find("#progress-container").remove();
    }

    // Mobile-specific functionality
    initMobileFeatures() {
        // Add touch event handlers for better mobile experience
        if (this.isMobile()) {
            this.addTouchHandlers();
            this.optimizeForMobile();
        }
    }

    // Bulk Operations
    initBulkOperations() {
        // Initialize bulk operations if the module is loaded
        if (typeof window.bulkOperations !== "undefined") {
            // Bulk operations are handled by the separate module
            console.log("Bulk operations module loaded");
        }
    }

    // Analytics Integration
    initAnalytics() {
        // Initialize analytics if the module is loaded
        if (typeof window.distributionAnalytics !== "undefined") {
            // Analytics are handled by the separate module
            console.log("Analytics module loaded");
        }
    }

    // Accessibility Enhancements
    initAccessibility() {
        // Initialize accessibility if the module is loaded
        if (typeof window.accessibilityManager !== "undefined") {
            // Accessibility is handled by the separate module
            console.log("Accessibility module loaded");
        }
    }

    isMobile() {
        return (
            window.innerWidth <= 768 ||
            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
                navigator.userAgent
            )
        );
    }

    addTouchHandlers() {
        // Add touch feedback for buttons
        $(".btn")
            .on("touchstart", function () {
                $(this).addClass("btn-touch");
            })
            .on("touchend", function () {
                setTimeout(() => {
                    $(this).removeClass("btn-touch");
                }, 150);
            });

        // Prevent zoom on input focus (iOS)
        $("input, select, textarea").attr("autocomplete", "off");
    }

    optimizeForMobile() {
        // Adjust modal behavior for mobile
        $(".modal").on("show.bs.modal", function () {
            if (window.innerWidth <= 768) {
                $(this).addClass("modal-mobile");
            }
        });

        // Add mobile-specific classes
        if (this.isMobile()) {
            $("body").addClass("mobile-device");
            $(".modal-dialog").addClass("modal-dialog-mobile");
        }
    }

    // Sender Verification Methods
    initSenderVerification() {
        // Select All checkbox functionality
        $("#selectAll").change((e) => {
            const isChecked = $(e.target).is(":checked");
            $(".document-checkbox").prop("checked", isChecked);

            if (isChecked) {
                $(".document-checkbox").each((index, element) => {
                    this.selectedDocuments.add($(element).data("document-id"));
                });
            } else {
                this.selectedDocuments.clear();
            }
            this.updateSelectAllButton();
        });

        // Individual document checkbox functionality
        $(document).on("change", ".document-checkbox", (e) => {
            const documentId = $(e.target).data("document-id");
            if ($(e.target).is(":checked")) {
                this.selectedDocuments.add(documentId);
            } else {
                this.selectedDocuments.delete(documentId);
            }

            // Update select all checkbox state
            const totalDocuments = $(".document-checkbox").length;
            const checkedDocuments = this.selectedDocuments.size;
            $("#selectAll").prop(
                "checked",
                checkedDocuments === totalDocuments
            );
            $("#selectAll").prop(
                "indeterminate",
                checkedDocuments > 0 && checkedDocuments < totalDocuments
            );

            this.updateSelectAllButton();
        });

        // Select All as Verified button
        $("#selectAllVerified").click(() => {
            console.log("=== SELECT ALL AS VERIFIED CLICKED ===");
            console.log(
                "Total documents found:",
                $(".document-checkbox").length
            );

            // Only affect non-skipped rows
            $(".document-checkbox").each((index, element) => {
                const row = $(element).closest("tr");
                const select = row.find(".document-status");
                const isDisabled = select.is(":disabled");
                if (!isDisabled) {
                    $(element).prop("checked", true);
                    select.val("verified");
                    row.find(".document-notes").val("").prop("required", false);
                }
            });

            this.selectedDocuments.clear();
            $(".document-checkbox").each((index, element) => {
                const docId = $(element).data("document-id");
                const row = $(element).closest("tr");
                const select = row.find(".document-status");
                if (!select.is(":disabled")) {
                    this.selectedDocuments.add(docId);
                }
                console.log("Added document ID to selection:", docId);
            });

            $("#selectAll").prop("checked", true);
            this.updateSelectAllButton();

            console.log(
                "Final selected documents count:",
                this.selectedDocuments.size
            );
            console.log(
                "Selected document IDs:",
                Array.from(this.selectedDocuments)
            );
            console.log("=== END SELECT ALL DEBUG ===");
        });

        // Clear All button
        $("#clearAll").click(() => {
            $(".document-checkbox").prop("checked", false);
            $(".document-status").val("");
            $(".document-notes").val("").prop("required", false);

            this.selectedDocuments.clear();
            $("#selectAll").prop("checked", false);
            this.updateSelectAllButton();
        });

        // Status change handler for notes requirement
        $(document).on("change", ".document-status", (e) => {
            const documentId = $(e.target).data("document-id");
            const status = $(e.target).val();
            const notesField = $(
                `.document-notes[data-document-id="${documentId}"]`
            );

            if (status === "missing" || status === "damaged") {
                notesField.prop("required", true);
                notesField.attr(
                    "placeholder",
                    "Notes required for " +
                        status.charAt(0).toUpperCase() +
                        status.slice(1) +
                        " status"
                );
            } else {
                notesField.prop("required", false);
                notesField.attr("placeholder", "Optional notes");
            }
        });

        // Sender Verification Form
        $("#senderVerificationForm").submit((e) => {
            e.preventDefault();
            this.handleSenderVerification();
        });
    }

    updateSelectAllButton() {
        const selectedCount = this.selectedDocuments.size;
        const totalCount = $(".document-checkbox").length;

        if (selectedCount === 0) {
            $("#selectAllVerified").html(
                '<i class="fas fa-check-double"></i> Select All as Verified'
            );
        } else if (selectedCount === totalCount) {
            $("#selectAllVerified").html(
                '<i class="fas fa-check-double"></i> All Selected'
            );
        } else {
            $("#selectAllVerified").html(
                `<i class="fas fa-check-double"></i> ${selectedCount}/${totalCount} Selected`
            );
        }
    }

    validateSenderVerificationForm() {
        // Get only eligible documents (not skipped for verification)
        const eligibleDocuments = [];
        $(".document-checkbox:checked").each((index, element) => {
            const documentId = $(element).data("document-id");
            const row = $(element).closest("tr");
            const statusSelect = row.find(".document-status");

            // Only include documents that are not disabled (not skipped)
            if (!statusSelect.is(":disabled")) {
                eligibleDocuments.push(documentId);
            }
        });

        // Check if at least one eligible document is selected
        if (eligibleDocuments.length === 0) {
            toastr.error(
                "Please select at least one eligible document to verify."
            );
            return false;
        }

        // Check required notes for missing/damaged status - ONLY for eligible documents
        let isValid = true;
        eligibleDocuments.forEach((documentId) => {
            const status = $(
                `.document-status[data-document-id="${documentId}"]`
            ).val();
            const notesField = $(
                `.document-notes[data-document-id="${documentId}"]`
            );

            if (status === "missing" || status === "damaged") {
                if (!notesField.val().trim()) {
                    isValid = false;
                    toastr.error(
                        "Notes are required for Missing or Damaged document status."
                    );
                    return false; // break the loop
                }
            }
        });

        return isValid;
    }

    handleSenderVerification() {
        if (!this.validateSenderVerificationForm()) {
            return;
        }

        // Show loading state
        const submitBtn = $('#senderVerificationForm button[type="submit"]');
        this.setButtonLoading(submitBtn, true);
        this.showLoading("Verifying documents as sender...");

        // Prepare form data with only selected documents
        const formData = new FormData();
        formData.append(
            "verification_notes",
            $("#sender_verification_notes").val()
        );

        // Debug logging
        console.log("=== SENDER VERIFICATION DEBUG ===");
        console.log(
            "Total documents in distribution:",
            $(".document-checkbox").length
        );
        console.log(
            "Selected documents:",
            $(".document-checkbox:checked").length
        );

        const selectedDocumentsData = [];
        $(".document-checkbox:checked").each((index, element) => {
            const documentId = $(element).data("document-id");
            const status = $(
                `.document-status[data-document-id="${documentId}"]`
            ).val();
            const notes = $(
                `.document-notes[data-document-id="${documentId}"]`
            ).val();

            selectedDocumentsData.push({
                document_id: documentId,
                status: status,
                notes: notes,
            });

            formData.append(
                `document_verifications[${documentId}][document_id]`,
                documentId
            );
            formData.append(
                `document_verifications[${documentId}][status]`,
                status
            );
            formData.append(
                `document_verifications[${documentId}][notes]`,
                notes
            );
        });

        console.log("Documents being sent to backend:", selectedDocumentsData);
        console.log("Form data entries:", Array.from(formData.entries()));
        console.log("=== END DEBUG ===");

        $.ajax({
            url: window.senderVerificationUrl,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                this.hideLoading();
                this.setButtonLoading(submitBtn, false);
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(
                        response.message || "Failed to verify as sender"
                    );
                }
            },
            error: (xhr) => {
                this.hideLoading();
                this.setButtonLoading(submitBtn, false);
                this.handleAjaxError(xhr, "Failed to verify as sender");
            },
        });
    }

    // Receiver Verification Methods
    initReceiverVerification() {
        // Select All checkbox functionality for receiver
        $("#selectAllReceiver").change((e) => {
            const isChecked = $(e.target).is(":checked");
            $(".document-checkbox-receiver").prop("checked", isChecked);

            if (isChecked) {
                $(".document-checkbox-receiver").each((index, element) => {
                    this.selectedDocumentsReceiver.add(
                        $(element).data("document-id")
                    );
                });
            } else {
                this.selectedDocumentsReceiver.clear();
            }
            this.updateSelectAllReceiverButton();
        });

        // Individual document checkbox functionality for receiver
        $(document).on("change", ".document-checkbox-receiver", (e) => {
            const documentId = $(e.target).data("document-id");
            if ($(e.target).is(":checked")) {
                this.selectedDocumentsReceiver.add(documentId);
            } else {
                this.selectedDocumentsReceiver.delete(documentId);
            }

            // Update select all checkbox state
            const totalDocuments = $(".document-checkbox-receiver").length;
            const checkedDocuments = this.selectedDocumentsReceiver.size;
            $("#selectAllReceiver").prop(
                "checked",
                checkedDocuments === totalDocuments
            );
            $("#selectAllReceiver").prop(
                "indeterminate",
                checkedDocuments > 0 && checkedDocuments < totalDocuments
            );

            this.updateSelectAllReceiverButton();
        });

        // Select All as Verified button for receiver
        $("#selectAllVerifiedReceiver").click(() => {
            console.log("=== RECEIVER SELECT ALL AS VERIFIED CLICKED ===");
            console.log(
                "Total documents found:",
                $(".document-checkbox-receiver").length
            );

            // Only affect non-skipped rows
            $(".document-checkbox-receiver").each((index, element) => {
                const row = $(element).closest("tr");
                const select = row.find(".document-status-receiver");
                const isDisabled = select.is(":disabled");
                if (!isDisabled) {
                    $(element).prop("checked", true);
                    select.val("verified");
                    row.find(".document-notes-receiver")
                        .val("")
                        .prop("required", false);
                }
            });

            this.selectedDocumentsReceiver.clear();
            $(".document-checkbox-receiver").each((index, element) => {
                const docId = $(element).data("document-id");
                const row = $(element).closest("tr");
                const select = row.find(".document-status-receiver");
                if (!select.is(":disabled")) {
                    this.selectedDocumentsReceiver.add(docId);
                }
                console.log("Added document ID to selection:", docId);
            });

            $("#selectAllReceiver").prop("checked", true);
            this.updateSelectAllReceiverButton();

            console.log(
                "Final selected documents count:",
                this.selectedDocumentsReceiver.size
            );
            console.log(
                "Selected document IDs:",
                Array.from(this.selectedDocumentsReceiver)
            );
            console.log("=== END RECEIVER SELECT ALL DEBUG ===");
        });

        // Clear All button for receiver
        $("#clearAllReceiver").click(() => {
            $(".document-checkbox-receiver").prop("checked", false);
            $(".document-status-receiver").val("");
            $(".document-notes-receiver").val("").prop("required", false);

            this.selectedDocumentsReceiver.clear();
            $("#selectAllReceiver").prop("checked", false);
            this.updateSelectAllReceiverButton();
        });

        // Status change handler for notes requirement in receiver modal
        $(document).on("change", ".document-status-receiver", (e) => {
            const documentId = $(e.target).data("document-id");
            const status = $(e.target).val();
            const notesField = $(
                `.document-notes-receiver[data-document-id="${documentId}"]`
            );

            if (status === "missing" || status === "damaged") {
                notesField.prop("required", true);
                notesField.attr(
                    "placeholder",
                    "Notes required for " +
                        status.charAt(0).toUpperCase() +
                        status.slice(1) +
                        " status"
                );
            } else {
                notesField.prop("required", false);
                notesField.attr("placeholder", "Optional notes");
            }
        });

        // Receiver Verification Form
        $("#receiverVerificationForm").submit((e) => {
            e.preventDefault();
            this.handleReceiverVerification();
        });
    }

    updateSelectAllReceiverButton() {
        const selectedCount = this.selectedDocumentsReceiver.size;
        const totalCount = $(".document-checkbox-receiver").length;

        if (selectedCount === 0) {
            $("#selectAllVerifiedReceiver").html(
                '<i class="fas fa-check-double"></i> Select All as Verified'
            );
        } else if (selectedCount === totalCount) {
            $("#selectAllVerifiedReceiver").html(
                '<i class="fas fa-check-double"></i> All Selected'
            );
        } else {
            $("#selectAllVerifiedReceiver").html(
                `<i class="fas fa-check-double"></i> ${selectedCount}/${totalCount} Selected`
            );
        }
    }

    validateReceiverVerificationForm() {
        // Get only eligible documents (not skipped for verification)
        const eligibleDocuments = [];
        $(".document-checkbox-receiver:checked").each((index, element) => {
            const documentId = $(element).data("document-id");
            const row = $(element).closest("tr");
            const statusSelect = row.find(".document-status-receiver");

            // Only include documents that are not disabled (not skipped)
            if (!statusSelect.is(":disabled")) {
                eligibleDocuments.push(documentId);
            }
        });

        // Check if at least one eligible document is selected
        if (eligibleDocuments.length === 0) {
            toastr.error(
                "Please select at least one eligible document to verify."
            );
            return false;
        }

        // Check required notes for missing/damaged status - ONLY for eligible documents
        let isValid = true;
        eligibleDocuments.forEach((documentId) => {
            const status = $(
                `.document-status-receiver[data-document-id="${documentId}"]`
            ).val();
            const notesField = $(
                `.document-notes-receiver[data-document-id="${documentId}"]`
            );

            if (status === "missing" || status === "damaged") {
                if (!notesField.val().trim()) {
                    isValid = false;
                    toastr.error(
                        "Notes are required for Missing or Damaged document status."
                    );
                    return false; // break the loop
                }
            }
        });

        return isValid;
    }

    handleReceiverVerification() {
        if (!this.validateReceiverVerificationForm()) {
            return;
        }

        // Show loading state
        const submitBtn = $('#receiverVerificationForm button[type="submit"]');
        this.setButtonLoading(submitBtn, true);
        this.showLoading("Verifying documents as receiver...");

        // Prepare form data with only selected documents
        const formData = new FormData();
        formData.append(
            "verification_notes",
            $("#receiver_verification_notes").val()
        );
        formData.append(
            "has_discrepancies",
            $("#has_discrepancies").is(":checked") ? "1" : "0"
        );

        // Debug logging
        console.log("=== RECEIVER VERIFICATION DEBUG ===");
        console.log(
            "Total documents in distribution:",
            $(".document-checkbox-receiver").length
        );
        console.log(
            "Selected documents:",
            $(".document-checkbox-receiver:checked").length
        );

        const selectedDocumentsData = [];
        $(".document-checkbox-receiver:checked").each((index, element) => {
            const documentId = $(element).data("document-id");
            const status = $(
                `.document-status-receiver[data-document-id="${documentId}"]`
            ).val();
            const notes = $(
                `.document-notes-receiver[data-document-id="${documentId}"]`
            ).val();

            selectedDocumentsData.push({
                document_id: documentId,
                status: status,
                notes: notes,
            });

            formData.append(
                `document_verifications[${documentId}][document_id]`,
                documentId
            );
            formData.append(
                `document_verifications[${documentId}][status]`,
                status
            );
            formData.append(
                `document_verifications[${documentId}][notes]`,
                notes
            );
        });

        console.log("Documents being sent to backend:", selectedDocumentsData);
        console.log("Form data entries:", Array.from(formData.entries()));
        console.log("=== END DEBUG ===");

        $.ajax({
            url: window.receiverVerificationUrl,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                this.hideLoading();
                this.setButtonLoading(submitBtn, false);
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(
                        response.message || "Failed to verify as receiver"
                    );
                }
            },
            error: (xhr) => {
                this.hideLoading();
                this.setButtonLoading(submitBtn, false);
                this.handleAjaxError(xhr, "Failed to verify as receiver");
            },
        });
    }

    // Workflow Actions
    initWorkflowActions() {
        // Send Distribution
        $("#sendDistribution").click(() => {
            this.handleWorkflowAction(
                window.sendDistributionUrl,
                "Send Distribution",
                "sendModal"
            );
        });

        // Receive Distribution
        $("#receiveDistribution").click(() => {
            this.handleWorkflowAction(
                window.receiveDistributionUrl,
                "Receive Distribution",
                "receiveModal"
            );
        });

        // Complete Distribution
        $("#completeDistribution").click(() => {
            this.handleWorkflowAction(
                window.completeDistributionUrl,
                "Complete Distribution",
                "completeModal"
            );
        });
    }

    handleWorkflowAction(url, actionName, modalId) {
        const button = $(`#${actionName.toLowerCase().replace(/\s+/g, "")}`);

        // Show loading state
        this.setButtonLoading(button, true);
        this.showLoading(`${actionName} in progress...`);

        $.ajax({
            url: url,
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                this.hideLoading();
                this.setButtonLoading(button, false);
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(
                        response.message ||
                            `Failed to ${actionName.toLowerCase()}`
                    );
                }
            },
            error: (xhr) => {
                this.hideLoading();
                this.setButtonLoading(button, false);
                this.handleAjaxError(
                    xhr,
                    `Failed to ${actionName.toLowerCase()}`
                );
            },
            complete: () => {
                // Hide modal
                $(`#${modalId}`).modal("hide");
            },
        });
    }

    // Cancel Actions
    initCancelActions() {
        // Cancel (Sent but not Received) with SweetAlert2 confirmation
        $(".cancel-distribution-sent").click((e) => {
            const distributionId = $(e.target).data("id");
            const distributionNumber = $(e.target).data("number");

            Swal.fire({
                title: "Cancel sent distribution?",
                html: `<small>Distribution <strong>${distributionNumber}</strong> will be cancelled and documents reverted to <strong>available</strong>. This action cannot be undone.</small>`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, cancel and revert",
                cancelButtonText: "No, keep it",
                reverseButtons: true,
            }).then((result) => {
                if (!result.isConfirmed) return;

                this.handleCancelAction(
                    window.cancelSentDistributionUrl,
                    "cancel sent distribution"
                );
            });
        });

        // Cancel Distribution (draft-only)
        $(".cancel-distribution").click((e) => {
            const distributionId = $(e.target).data("id");
            const distributionNumber = $(e.target).data("number");

            Swal.fire({
                title: "Cancel this distribution?",
                html: `<small>Distribution <strong>${distributionNumber}</strong> will be cancelled. This action cannot be undone.</small>`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, cancel it",
                cancelButtonText: "No, keep it",
                reverseButtons: true,
            }).then((result) => {
                if (!result.isConfirmed) return;

                this.handleCancelAction(
                    `${window.distributionsUrl}/${distributionId}`,
                    "cancel distribution"
                );
            });
        });
    }

    handleCancelAction(url, actionType) {
        $.ajax({
            url: url,
            type: "DELETE",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: (response) => {
                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Cancelled",
                        text:
                            response.message ||
                            `Distribution ${actionType} successfully`,
                        timer: 1200,
                        showConfirmButton: false,
                    });
                    setTimeout(() => {
                        window.location.href = window.distributionsIndexUrl;
                    }, 1200);
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        text: response.message || `Failed to ${actionType}`,
                    });
                }
            },
            error: (xhr) => {
                let msg = `Failed to ${actionType}`;
                if (xhr.status === 403) {
                    msg =
                        "You do not have permission to cancel this distribution";
                } else if (xhr.status === 422) {
                    msg = "This distribution cannot be cancelled";
                }
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: msg,
                });
            },
        });
    }

    // Sync Actions
    initSyncActions() {
        // Sync linked documents (draft only)
        $("#syncLinkedDocsBtn").click(() => {
            const button = $("#syncLinkedDocsBtn");
            const originalText = button.html();

            // Show loading state
            button
                .prop("disabled", true)
                .html('<i class="fas fa-spinner fa-spin"></i> Syncing...');

            $.ajax({
                url: window.syncLinkedDocumentsUrl,
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content"
                    ),
                },
                success: (response) => {
                    if (response.success) {
                        toastr.success(
                            response.message || "Linked documents synced"
                        );
                        setTimeout(() => {
                            location.reload();
                        }, 800);
                    } else {
                        toastr.error(
                            response.message ||
                                "Failed to sync linked documents"
                        );
                    }
                },
                error: (xhr) => {
                    this.handleAjaxError(
                        xhr,
                        "Failed to sync linked documents"
                    );
                },
                complete: () => {
                    // Restore button state
                    button.prop("disabled", false).html(originalText);
                },
            });
        });
    }

    // Error Handling
    handleAjaxError(xhr, defaultMessage) {
        console.error("AJAX Error:", {
            xhr,
            status: xhr.status,
            error: xhr.responseText,
        });

        if (xhr.status === 422) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                let errorMsg = "";
                Object.keys(errors).forEach((key) => {
                    errorMsg += errors[key][0] + "\n";
                });
                toastr.error(errorMsg);
            } else {
                toastr.error("Please fill in all required fields");
            }
        } else if (xhr.status === 403) {
            toastr.error("You do not have permission to perform this action");
        } else if (xhr.status === 404) {
            toastr.error("The requested resource was not found");
        } else if (xhr.status === 500) {
            toastr.error("Server error occurred. Please try again later");
        } else {
            toastr.error(defaultMessage);
        }
    }
}

// Initialize when document is ready
$(document).ready(function () {
    // Set up global URLs for AJAX calls
    window.senderVerificationUrl = window.senderVerificationUrl || "";
    window.receiverVerificationUrl = window.receiverVerificationUrl || "";
    window.sendDistributionUrl = window.sendDistributionUrl || "";
    window.receiveDistributionUrl = window.receiveDistributionUrl || "";
    window.completeDistributionUrl = window.completeDistributionUrl || "";
    window.cancelSentDistributionUrl = window.cancelSentDistributionUrl || "";
    window.distributionsUrl = window.distributionsUrl || "";
    window.distributionsIndexUrl = window.distributionsIndexUrl || "";
    window.syncLinkedDocumentsUrl = window.syncLinkedDocumentsUrl || "";

    // Initialize the distribution show functionality
    new DistributionShow();
});
