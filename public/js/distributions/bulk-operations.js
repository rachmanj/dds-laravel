/**
 * Bulk Operations for Distribution Show Page
 * Handles bulk status updates, verification, notes, export, and print operations
 */

class BulkOperations {
    constructor() {
        this.selectedDocuments = new Set();
        this.operationQueue = [];
        this.isProcessing = false;
        this.init();
    }

    init() {
        this.setupBulkSelection();
        this.setupBulkActions();
        this.setupBulkExport();
        this.setupBulkPrint();
    }

    setupBulkSelection() {
        // Add bulk selection controls
        const bulkControlsHtml = `
            <div class="bulk-operations-panel" style="display: none; background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <span class="bulk-selection-info">
                            <i class="fas fa-check-square text-primary"></i>
                            <span class="selected-count">0</span> documents selected
                        </span>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" id="bulkStatusUpdate">
                                <i class="fas fa-tags"></i> Update Status
                            </button>
                            <button type="button" class="btn btn-outline-success" id="bulkVerify">
                                <i class="fas fa-check-double"></i> Bulk Verify
                            </button>
                            <button type="button" class="btn btn-outline-info" id="bulkNotes">
                                <i class="fas fa-sticky-note"></i> Add Notes
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="bulkExport">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <button type="button" class="btn btn-outline-warning" id="bulkPrint">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="clearBulkSelection">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert bulk controls after the document table
        $(".table-responsive").after(bulkControlsHtml);

        // Add bulk selection checkbox to table header
        const tableHeader = $(".table thead tr th").first();
        tableHeader.prepend(`
            <input type="checkbox" id="selectAllBulk" class="bulk-select-all" 
                   title="Select all documents for bulk operations">
        `);

        // Add individual bulk selection checkboxes
        $(".document-checkbox").each((index, element) => {
            const checkbox = $(element);
            const documentId = checkbox.data("document-id");

            // Add bulk selection checkbox
            const bulkCheckbox = $(`
                <input type="checkbox" class="bulk-select-document" 
                       data-document-id="${documentId}" 
                       title="Select for bulk operations">
            `);

            checkbox.after(bulkCheckbox);
        });

        // Handle bulk selection events
        $("#selectAllBulk").change((e) => {
            const isChecked = $(e.target).is(":checked");
            $(".bulk-select-document").prop("checked", isChecked);

            if (isChecked) {
                $(".bulk-select-document").each((index, element) => {
                    const documentId = $(element).data("document-id");
                    this.selectedDocuments.add(documentId);
                });
            } else {
                this.selectedDocuments.clear();
            }

            this.updateBulkSelectionDisplay();
        });

        $(document).on("change", ".bulk-select-document", (e) => {
            const documentId = $(e.target).data("document-id");
            const isChecked = $(e.target).is(":checked");

            if (isChecked) {
                this.selectedDocuments.add(documentId);
            } else {
                this.selectedDocuments.delete(documentId);
            }

            // Update select all checkbox
            const totalDocuments = $(".bulk-select-document").length;
            const checkedDocuments = this.selectedDocuments.size;
            $("#selectAllBulk").prop(
                "checked",
                checkedDocuments === totalDocuments
            );
            $("#selectAllBulk").prop(
                "indeterminate",
                checkedDocuments > 0 && checkedDocuments < totalDocuments
            );

            this.updateBulkSelectionDisplay();
        });
    }

    updateBulkSelectionDisplay() {
        const selectedCount = this.selectedDocuments.size;
        const bulkPanel = $(".bulk-operations-panel");
        const selectedCountSpan = $(".selected-count");

        if (selectedCount > 0) {
            bulkPanel.show();
            selectedCountSpan.text(selectedCount);
        } else {
            bulkPanel.hide();
        }
    }

    setupBulkActions() {
        // Bulk Status Update
        $("#bulkStatusUpdate").click(() => {
            if (this.selectedDocuments.size === 0) {
                toastr.warning("Please select documents first");
                return;
            }

            this.showBulkStatusModal();
        });

        // Bulk Verify
        $("#bulkVerify").click(() => {
            if (this.selectedDocuments.size === 0) {
                toastr.warning("Please select documents first");
                return;
            }

            this.showBulkVerifyModal();
        });

        // Bulk Notes
        $("#bulkNotes").click(() => {
            if (this.selectedDocuments.size === 0) {
                toastr.warning("Please select documents first");
                return;
            }

            this.showBulkNotesModal();
        });

        // Clear Bulk Selection
        $("#clearBulkSelection").click(() => {
            this.selectedDocuments.clear();
            $(".bulk-select-document").prop("checked", false);
            $("#selectAllBulk").prop("checked", false);
            this.updateBulkSelectionDisplay();
        });
    }

    showBulkStatusModal() {
        const selectedCount = this.selectedDocuments.size;
        const modalHtml = `
            <div class="modal fade" id="bulkStatusModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-tags"></i> Bulk Status Update
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Updating status for <strong>${selectedCount}</strong> selected documents
                            </div>
                            <form id="bulkStatusForm">
                                <div class="form-group">
                                    <label for="bulkStatus">New Status:</label>
                                    <select class="form-control" id="bulkStatus" required>
                                        <option value="">Select Status</option>
                                        <option value="verified">Verified</option>
                                        <option value="missing">Missing</option>
                                        <option value="damaged">Damaged</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="bulkStatusNotes">Notes (Optional):</label>
                                    <textarea class="form-control" id="bulkStatusNotes" rows="3" 
                                              placeholder="Add notes for this bulk status update"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmBulkStatus">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("body").append(modalHtml);
        $("#bulkStatusModal").modal("show");

        $("#confirmBulkStatus").click(() => {
            const status = $("#bulkStatus").val();
            const notes = $("#bulkStatusNotes").val();

            if (!status) {
                toastr.error("Please select a status");
                return;
            }

            this.processBulkStatusUpdate(status, notes);
        });

        $("#bulkStatusModal").on("hidden.bs.modal", () => {
            $("#bulkStatusModal").remove();
        });
    }

    showBulkVerifyModal() {
        const selectedCount = this.selectedDocuments.size;
        const modalHtml = `
            <div class="modal fade" id="bulkVerifyModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-check-double"></i> Bulk Verification
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Verifying <strong>${selectedCount}</strong> selected documents
                            </div>
                            <form id="bulkVerifyForm">
                                <div class="form-group">
                                    <label for="bulkVerifyStatus">Verification Status:</label>
                                    <select class="form-control" id="bulkVerifyStatus" required>
                                        <option value="">Select Verification Status</option>
                                        <option value="verified">Verified</option>
                                        <option value="missing">Missing</option>
                                        <option value="damaged">Damaged</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="bulkVerifyNotes">Verification Notes:</label>
                                    <textarea class="form-control" id="bulkVerifyNotes" rows="3" 
                                              placeholder="Add verification notes"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success" id="confirmBulkVerify">
                                <i class="fas fa-check-double"></i> Verify Documents
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("body").append(modalHtml);
        $("#bulkVerifyModal").modal("show");

        $("#confirmBulkVerify").click(() => {
            const status = $("#bulkVerifyStatus").val();
            const notes = $("#bulkVerifyNotes").val();

            if (!status) {
                toastr.error("Please select a verification status");
                return;
            }

            this.processBulkVerification(status, notes);
        });

        $("#bulkVerifyModal").on("hidden.bs.modal", () => {
            $("#bulkVerifyModal").remove();
        });
    }

    showBulkNotesModal() {
        const selectedCount = this.selectedDocuments.size;
        const modalHtml = `
            <div class="modal fade" id="bulkNotesModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-sticky-note"></i> Bulk Notes
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Adding notes to <strong>${selectedCount}</strong> selected documents
                            </div>
                            <form id="bulkNotesForm">
                                <div class="form-group">
                                    <label for="bulkNotesText">Notes:</label>
                                    <textarea class="form-control" id="bulkNotesText" rows="4" 
                                              placeholder="Enter notes to add to all selected documents" required></textarea>
                                </div>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="bulkNotesAppend">
                                        <label class="form-check-label" for="bulkNotesAppend">
                                            Append to existing notes (instead of replacing)
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-info" id="confirmBulkNotes">
                                <i class="fas fa-sticky-note"></i> Add Notes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("body").append(modalHtml);
        $("#bulkNotesModal").modal("show");

        $("#confirmBulkNotes").click(() => {
            const notes = $("#bulkNotesText").val();
            const append = $("#bulkNotesAppend").is(":checked");

            if (!notes.trim()) {
                toastr.error("Please enter notes");
                return;
            }

            this.processBulkNotes(notes, append);
        });

        $("#bulkNotesModal").on("hidden.bs.modal", () => {
            $("#bulkNotesModal").remove();
        });
    }

    async processBulkStatusUpdate(status, notes) {
        if (this.isProcessing) return;

        this.isProcessing = true;
        const selectedDocuments = Array.from(this.selectedDocuments);

        this.showProgress(0, "Updating document status...");
        $("#bulkStatusModal").modal("hide");

        try {
            for (let i = 0; i < selectedDocuments.length; i++) {
                const documentId = selectedDocuments[i];
                await this.updateDocumentStatus(documentId, status, notes);

                const progress = ((i + 1) / selectedDocuments.length) * 100;
                this.updateProgress(
                    progress,
                    `Updated ${i + 1}/${selectedDocuments.length} documents`
                );
            }

            this.hideProgress();
            toastr.success(
                `Successfully updated status for ${selectedDocuments.length} documents`
            );

            // Clear selection and refresh
            this.clearBulkSelection();
            setTimeout(() => location.reload(), 1000);
        } catch (error) {
            this.hideProgress();
            toastr.error("Failed to update document status: " + error.message);
        } finally {
            this.isProcessing = false;
        }
    }

    async processBulkVerification(status, notes) {
        if (this.isProcessing) return;

        this.isProcessing = true;
        const selectedDocuments = Array.from(this.selectedDocuments);

        this.showProgress(0, "Verifying documents...");
        $("#bulkVerifyModal").modal("hide");

        try {
            for (let i = 0; i < selectedDocuments.length; i++) {
                const documentId = selectedDocuments[i];
                await this.verifyDocument(documentId, status, notes);

                const progress = ((i + 1) / selectedDocuments.length) * 100;
                this.updateProgress(
                    progress,
                    `Verified ${i + 1}/${selectedDocuments.length} documents`
                );
            }

            this.hideProgress();
            toastr.success(
                `Successfully verified ${selectedDocuments.length} documents`
            );

            // Clear selection and refresh
            this.clearBulkSelection();
            setTimeout(() => location.reload(), 1000);
        } catch (error) {
            this.hideProgress();
            toastr.error("Failed to verify documents: " + error.message);
        } finally {
            this.isProcessing = false;
        }
    }

    async processBulkNotes(notes, append) {
        if (this.isProcessing) return;

        this.isProcessing = true;
        const selectedDocuments = Array.from(this.selectedDocuments);

        this.showProgress(0, "Adding notes to documents...");
        $("#bulkNotesModal").modal("hide");

        try {
            for (let i = 0; i < selectedDocuments.length; i++) {
                const documentId = selectedDocuments[i];
                await this.addDocumentNotes(documentId, notes, append);

                const progress = ((i + 1) / selectedDocuments.length) * 100;
                this.updateProgress(
                    progress,
                    `Added notes to ${i + 1}/${
                        selectedDocuments.length
                    } documents`
                );
            }

            this.hideProgress();
            toastr.success(
                `Successfully added notes to ${selectedDocuments.length} documents`
            );

            // Clear selection and refresh
            this.clearBulkSelection();
            setTimeout(() => location.reload(), 1000);
        } catch (error) {
            this.hideProgress();
            toastr.error("Failed to add notes: " + error.message);
        } finally {
            this.isProcessing = false;
        }
    }

    async updateDocumentStatus(documentId, status, notes) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: `/api/distributions/documents/${documentId}/status`,
                type: "PUT",
                data: {
                    status: status,
                    notes: notes,
                    _token: $('meta[name="csrf-token"]').attr("content"),
                },
                success: (response) => {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(
                            new Error(
                                response.message || "Failed to update status"
                            )
                        );
                    }
                },
                error: (xhr) => {
                    reject(new Error("Network error: " + xhr.statusText));
                },
            });
        });
    }

    async verifyDocument(documentId, status, notes) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: `/api/distributions/documents/${documentId}/verify`,
                type: "POST",
                data: {
                    status: status,
                    notes: notes,
                    _token: $('meta[name="csrf-token"]').attr("content"),
                },
                success: (response) => {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(
                            new Error(
                                response.message || "Failed to verify document"
                            )
                        );
                    }
                },
                error: (xhr) => {
                    reject(new Error("Network error: " + xhr.statusText));
                },
            });
        });
    }

    async addDocumentNotes(documentId, notes, append) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: `/api/distributions/documents/${documentId}/notes`,
                type: "PUT",
                data: {
                    notes: notes,
                    append: append,
                    _token: $('meta[name="csrf-token"]').attr("content"),
                },
                success: (response) => {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(
                            new Error(response.message || "Failed to add notes")
                        );
                    }
                },
                error: (xhr) => {
                    reject(new Error("Network error: " + xhr.statusText));
                },
            });
        });
    }

    clearBulkSelection() {
        this.selectedDocuments.clear();
        $(".bulk-select-document").prop("checked", false);
        $("#selectAllBulk").prop("checked", false);
        this.updateBulkSelectionDisplay();
    }

    setupBulkExport() {
        $("#bulkExport").click(() => {
            if (this.selectedDocuments.size === 0) {
                toastr.warning("Please select documents first");
                return;
            }

            this.showBulkExportModal();
        });
    }

    showBulkExportModal() {
        const selectedCount = this.selectedDocuments.size;
        const modalHtml = `
            <div class="modal fade" id="bulkExportModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-download"></i> Bulk Export
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Exporting <strong>${selectedCount}</strong> selected documents
                            </div>
                            <form id="bulkExportForm">
                                <div class="form-group">
                                    <label for="exportFormat">Export Format:</label>
                                    <select class="form-control" id="exportFormat" required>
                                        <option value="">Select Format</option>
                                        <option value="pdf">PDF Document</option>
                                        <option value="excel">Excel Spreadsheet</option>
                                        <option value="csv">CSV File</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="exportType">Export Type:</label>
                                    <select class="form-control" id="exportType" required>
                                        <option value="">Select Type</option>
                                        <option value="summary">Summary Report</option>
                                        <option value="detailed">Detailed Report</option>
                                        <option value="labels">Document Labels</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmBulkExport">
                                <i class="fas fa-download"></i> Export Documents
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("body").append(modalHtml);
        $("#bulkExportModal").modal("show");

        $("#confirmBulkExport").click(() => {
            const format = $("#exportFormat").val();
            const type = $("#exportType").val();

            if (!format || !type) {
                toastr.error("Please select format and type");
                return;
            }

            this.processBulkExport(format, type);
        });

        $("#bulkExportModal").on("hidden.bs.modal", () => {
            $("#bulkExportModal").remove();
        });
    }

    processBulkExport(format, type) {
        const selectedDocuments = Array.from(this.selectedDocuments);

        $("#bulkExportModal").modal("hide");
        this.showLoading("Preparing export...");

        const form = $("<form>", {
            method: "POST",
            action: "/api/distributions/bulk-export",
            target: "_blank",
        });

        form.append(
            $("<input>", {
                type: "hidden",
                name: "_token",
                value: $('meta[name="csrf-token"]').attr("content"),
            })
        );

        form.append(
            $("<input>", {
                type: "hidden",
                name: "format",
                value: format,
            })
        );

        form.append(
            $("<input>", {
                type: "hidden",
                name: "type",
                value: type,
            })
        );

        selectedDocuments.forEach((documentId) => {
            form.append(
                $("<input>", {
                    type: "hidden",
                    name: "document_ids[]",
                    value: documentId,
                })
            );
        });

        $("body").append(form);
        form.submit();
        form.remove();

        this.hideLoading();
        toastr.success(
            `Export started for ${selectedDocuments.length} documents`
        );
    }

    setupBulkPrint() {
        $("#bulkPrint").click(() => {
            if (this.selectedDocuments.size === 0) {
                toastr.warning("Please select documents first");
                return;
            }

            this.showBulkPrintModal();
        });
    }

    showBulkPrintModal() {
        const selectedCount = this.selectedDocuments.size;
        const modalHtml = `
            <div class="modal fade" id="bulkPrintModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-print"></i> Bulk Print
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Printing <strong>${selectedCount}</strong> selected documents
                            </div>
                            <form id="bulkPrintForm">
                                <div class="form-group">
                                    <label for="printType">Print Type:</label>
                                    <select class="form-control" id="printType" required>
                                        <option value="">Select Type</option>
                                        <option value="labels">Document Labels</option>
                                        <option value="summary">Summary Report</option>
                                        <option value="detailed">Detailed Report</option>
                                        <option value="transmittal">Transmittal Advice</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="printCopies">Number of Copies:</label>
                                    <input type="number" class="form-control" id="printCopies" 
                                           value="1" min="1" max="10" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-warning" id="confirmBulkPrint">
                                <i class="fas fa-print"></i> Print Documents
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("body").append(modalHtml);
        $("#bulkPrintModal").modal("show");

        $("#confirmBulkPrint").click(() => {
            const printType = $("#printType").val();
            const copies = $("#printCopies").val();

            if (!printType || !copies) {
                toastr.error("Please select print type and number of copies");
                return;
            }

            this.processBulkPrint(printType, copies);
        });

        $("#bulkPrintModal").on("hidden.bs.modal", () => {
            $("#bulkPrintModal").remove();
        });
    }

    processBulkPrint(printType, copies) {
        const selectedDocuments = Array.from(this.selectedDocuments);

        $("#bulkPrintModal").modal("hide");
        this.showLoading("Preparing print job...");

        const form = $("<form>", {
            method: "POST",
            action: "/api/distributions/bulk-print",
            target: "_blank",
        });

        form.append(
            $("<input>", {
                type: "hidden",
                name: "_token",
                value: $('meta[name="csrf-token"]').attr("content"),
            })
        );

        form.append(
            $("<input>", {
                type: "hidden",
                name: "print_type",
                value: printType,
            })
        );

        form.append(
            $("<input>", {
                type: "hidden",
                name: "copies",
                value: copies,
            })
        );

        selectedDocuments.forEach((documentId) => {
            form.append(
                $("<input>", {
                    type: "hidden",
                    name: "document_ids[]",
                    value: documentId,
                })
            );
        });

        $("body").append(form);
        form.submit();
        form.remove();

        this.hideLoading();
        toastr.success(
            `Print job started for ${selectedDocuments.length} documents`
        );
    }

    // Utility methods for progress and loading
    showProgress(progress, message) {
        if (
            typeof window.distributionShow !== "undefined" &&
            window.distributionShow.showProgress
        ) {
            window.distributionShow.showProgress(progress, message);
        }
    }

    updateProgress(progress, message) {
        if (
            typeof window.distributionShow !== "undefined" &&
            window.distributionShow.updateProgress
        ) {
            window.distributionShow.updateProgress(progress, message);
        }
    }

    hideProgress() {
        if (
            typeof window.distributionShow !== "undefined" &&
            window.distributionShow.hideProgress
        ) {
            window.distributionShow.hideProgress();
        }
    }

    showLoading(message) {
        if (
            typeof window.distributionShow !== "undefined" &&
            window.distributionShow.showLoading
        ) {
            window.distributionShow.showLoading(message);
        }
    }

    hideLoading() {
        if (
            typeof window.distributionShow !== "undefined" &&
            window.distributionShow.hideLoading
        ) {
            window.distributionShow.hideLoading();
        }
    }
}

// Initialize when document is ready
$(document).ready(function () {
    window.bulkOperations = new BulkOperations();
});
