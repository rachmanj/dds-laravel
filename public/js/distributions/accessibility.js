/**
 * Accessibility Enhancements for Distribution Show Page
 * Handles screen reader support, focus management, ARIA labels, and keyboard navigation
 */

class AccessibilityManager {
    constructor() {
        this.isHighContrast = false;
        this.fontSize = "medium";
        this.keyboardMode = false;
        this.announcements = [];
        this.init();
    }

    init() {
        this.loadAccessibilitySettings();
        this.addARIALabels();
        this.setupKeyboardNavigation();
        this.addFocusIndicators();
        this.setupScreenReaderSupport();
        this.createAccessibilityControls();
        this.setupHighContrastMode();
        this.setupFontSizeControls();
        this.setupAnnouncements();
    }

    loadAccessibilitySettings() {
        // Load saved settings from localStorage
        this.isHighContrast = localStorage.getItem("highContrast") === "true";
        this.fontSize = localStorage.getItem("fontSize") || "medium";

        // Apply saved settings
        if (this.isHighContrast) {
            $("body").addClass("high-contrast");
        }

        $("body").addClass(`font-${this.fontSize}`);
    }

    addARIALabels() {
        // Add descriptive labels to all interactive elements
        $(".btn").each((index, element) => {
            const $btn = $(element);
            const text = $btn.text().trim();
            const icon = $btn.find("i").attr("class");

            if (!text && icon) {
                // Button with only icon - add descriptive label
                const iconDescription = this.getIconDescription(icon);
                $btn.attr("aria-label", iconDescription);
            } else if (text) {
                // Button with text - ensure proper labeling
                $btn.attr("aria-label", `${text} button`);
            }
        });

        // Add role and labels to modals
        $(".modal").each((index, element) => {
            const $modal = $(element);
            $modal.attr("role", "dialog");
            $modal.attr("aria-modal", "true");

            const title = $modal.find(".modal-title").text().trim();
            if (title) {
                $modal.attr("aria-labelledby", `modal-title-${index}`);
                $modal.find(".modal-title").attr("id", `modal-title-${index}`);
            }
        });

        // Add labels to form controls
        $(".form-control").each((index, element) => {
            const $control = $(element);
            const $label = $control.closest(".form-group").find("label");

            if ($label.length && !$control.attr("aria-label")) {
                $control.attr(
                    "aria-labelledby",
                    $label.attr("id") || `label-${index}`
                );
                if (!$label.attr("id")) {
                    $label.attr("id", `label-${index}`);
                }
            }
        });

        // Add labels to checkboxes and radio buttons
        $('input[type="checkbox"], input[type="radio"]').each(
            (index, element) => {
                const $input = $(element);
                const $label =
                    $input.closest("label") || $input.siblings("label");

                if ($label.length && !$input.attr("aria-label")) {
                    $input.attr(
                        "aria-labelledby",
                        $label.attr("id") || `label-${index}`
                    );
                    if (!$label.attr("id")) {
                        $label.attr("id", `label-${index}`);
                    }
                }
            }
        );

        // Add table headers and descriptions
        $(".table").each((index, table) => {
            const $table = $(table);
            $table.attr("role", "table");

            // Add caption if missing
            if (!$table.find("caption").length) {
                $table.prepend(
                    `<caption class="sr-only">Distribution documents table with ${
                        $table.find("tr").length - 1
                    } documents</caption>`
                );
            }

            // Add row headers
            $table.find("tbody tr").each((rowIndex, row) => {
                $(row).attr("role", "row");
                $(row)
                    .find("td")
                    .each((cellIndex, cell) => {
                        $(cell).attr("role", "gridcell");
                    });
            });
        });

        // Add status indicators
        $(".badge").each((index, element) => {
            const $badge = $(element);
            const text = $badge.text().trim();
            const status = this.getStatusDescription(text);

            $badge.attr("role", "status");
            $badge.attr("aria-label", status);
        });
    }

    getIconDescription(iconClass) {
        const iconMap = {
            "fas fa-check": "Check",
            "fas fa-times": "Close",
            "fas fa-edit": "Edit",
            "fas fa-trash": "Delete",
            "fas fa-plus": "Add",
            "fas fa-search": "Search",
            "fas fa-download": "Download",
            "fas fa-print": "Print",
            "fas fa-save": "Save",
            "fas fa-sync": "Sync",
            "fas fa-eye": "View",
            "fas fa-check-double": "Verify",
            "fas fa-tags": "Status",
            "fas fa-sticky-note": "Notes",
            "fas fa-exclamation-triangle": "Warning",
            "fas fa-info-circle": "Information",
        };

        for (const [icon, description] of Object.entries(iconMap)) {
            if (iconClass.includes(icon)) {
                return description;
            }
        }

        return "Action";
    }

    getStatusDescription(status) {
        const statusMap = {
            verified: "Document verified",
            pending: "Document pending verification",
            missing: "Document missing",
            damaged: "Document damaged",
            sent: "Distribution sent",
            received: "Distribution received",
            completed: "Distribution completed",
            draft: "Distribution draft",
        };

        return statusMap[status.toLowerCase()] || `Status: ${status}`;
    }

    setupKeyboardNavigation() {
        // Detect keyboard usage
        $(document).on("keydown", (e) => {
            if (e.key === "Tab") {
                this.keyboardMode = true;
                $("body").addClass("keyboard-navigation");
            }
        });

        $(document).on("mousedown", () => {
            this.keyboardMode = false;
            $("body").removeClass("keyboard-navigation");
        });

        // Enhanced keyboard navigation for modals
        $(document).on("keydown", ".modal", (e) => {
            if (e.key === "Escape") {
                $(e.target).closest(".modal").modal("hide");
            }
        });

        // Keyboard navigation for document selection
        $(document).on("keydown", ".document-checkbox", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                $(e.target)
                    .prop("checked", !$(e.target).prop("checked"))
                    .trigger("change");
            }
        });

        // Keyboard navigation for bulk operations
        $(document).on("keydown", ".bulk-select-document", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                $(e.target)
                    .prop("checked", !$(e.target).prop("checked"))
                    .trigger("change");
            }
        });

        // Arrow key navigation for table rows
        $(document).on("keydown", ".table tbody tr", (e) => {
            const $currentRow = $(e.target).closest("tr");
            const $rows = $(".table tbody tr");
            const currentIndex = $rows.index($currentRow);

            switch (e.key) {
                case "ArrowDown":
                    e.preventDefault();
                    if (currentIndex < $rows.length - 1) {
                        $rows
                            .eq(currentIndex + 1)
                            .find("td:first")
                            .focus();
                    }
                    break;
                case "ArrowUp":
                    e.preventDefault();
                    if (currentIndex > 0) {
                        $rows
                            .eq(currentIndex - 1)
                            .find("td:first")
                            .focus();
                    }
                    break;
            }
        });
    }

    addFocusIndicators() {
        // Add focus styles
        $("head").append(`
            <style>
                .keyboard-navigation .btn:focus,
                .keyboard-navigation .form-control:focus,
                .keyboard-navigation .modal:focus,
                .keyboard-navigation .table td:focus {
                    outline: 3px solid #007bff !important;
                    outline-offset: 2px !important;
                    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25) !important;
                }
                
                .keyboard-navigation .btn:focus {
                    background-color: #0056b3 !important;
                    border-color: #0056b3 !important;
                }
                
                .keyboard-navigation .form-control:focus {
                    border-color: #007bff !important;
                    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
                }
                
                .keyboard-navigation .table td:focus {
                    background-color: #e3f2fd !important;
                }
                
                .high-contrast {
                    --primary-color: #000000;
                    --secondary-color: #ffffff;
                    --text-color: #000000;
                    --background-color: #ffffff;
                    --border-color: #000000;
                }
                
                .high-contrast .btn-primary {
                    background-color: #000000 !important;
                    border-color: #000000 !important;
                    color: #ffffff !important;
                }
                
                .high-contrast .btn-primary:hover,
                .high-contrast .btn-primary:focus {
                    background-color: #333333 !important;
                    border-color: #333333 !important;
                }
                
                .high-contrast .table {
                    border: 2px solid #000000 !important;
                }
                
                .high-contrast .table th,
                .high-contrast .table td {
                    border: 1px solid #000000 !important;
                }
                
                .font-small { font-size: 14px; }
                .font-medium { font-size: 16px; }
                .font-large { font-size: 18px; }
                .font-extra-large { font-size: 20px; }
                
                .sr-only {
                    position: absolute !important;
                    width: 1px !important;
                    height: 1px !important;
                    padding: 0 !important;
                    margin: -1px !important;
                    overflow: hidden !important;
                    clip: rect(0, 0, 0, 0) !important;
                    white-space: nowrap !important;
                    border: 0 !important;
                }
                
                .skip-link {
                    position: absolute;
                    top: -40px;
                    left: 6px;
                    background: #000;
                    color: #fff;
                    padding: 8px;
                    text-decoration: none;
                    z-index: 1000;
                }
                
                .skip-link:focus {
                    top: 6px;
                }
            </style>
        `);
    }

    setupScreenReaderSupport() {
        // Add skip links
        $("body").prepend(`
            <a href="#main-content" class="skip-link">Skip to main content</a>
            <a href="#navigation" class="skip-link">Skip to navigation</a>
        `);

        // Add main content landmark
        $(".content-wrapper, .main-content")
            .attr("id", "main-content")
            .attr("role", "main");

        // Add navigation landmark
        $(".sidebar, .navigation")
            .attr("id", "navigation")
            .attr("role", "navigation");

        // Add live region for announcements
        $("body").append(`
            <div id="live-region" aria-live="polite" aria-atomic="true" class="sr-only"></div>
        `);

        // Add status region for updates
        $("body").append(`
            <div id="status-region" aria-live="assertive" aria-atomic="true" class="sr-only"></div>
        `);
    }

    createAccessibilityControls() {
        // Add responsive CSS for accessibility controls
        const responsiveStyles = `
            <style>
                @media (min-width: 768px) {
                    .accessibility-controls {
                        right: 20px !important; /* Desktop: position at bottom-right */
                        left: auto !important;
                        bottom: 20px !important; /* Same level as analytics dashboard */
                    }
                }
                @media (max-width: 767px) {
                    .accessibility-controls {
                        right: 20px !important; /* Mobile: also bottom-right */
                        left: auto !important;
                        width: auto !important;
                        bottom: 20px !important;
                    }
                }
            </style>
        `;

        const controlsHtml = `
            ${responsiveStyles}
            <div class="accessibility-controls" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; background: rgba(255,255,255,0.9); border: 1px solid rgba(221,221,221,0.5); border-radius: 8px; padding: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); backdrop-filter: blur(3px);">
                <div class="control-group" style="margin-bottom: 10px;">
                    <label for="fontSizeControl" style="display: block; margin-bottom: 5px; font-size: 12px;">Font Size:</label>
                    <select id="fontSizeControl" class="form-control form-control-sm" style="width: 120px;">
                        <option value="small">Small</option>
                        <option value="medium" selected>Medium</option>
                        <option value="large">Large</option>
                        <option value="extra-large">Extra Large</option>
                    </select>
                </div>
                <div class="control-group" style="margin-bottom: 10px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="highContrastToggle">
                        <label class="form-check-label" for="highContrastToggle" style="font-size: 12px;">
                            High Contrast
                        </label>
                    </div>
                </div>
                <div class="control-group">
                    <button class="btn btn-sm btn-outline-secondary" id="accessibilityHelp" style="width: 100%;">
                        <i class="fas fa-question-circle"></i> Help
                    </button>
                </div>
            </div>
        `;

        $("body").append(controlsHtml);

        // Set initial values
        $("#fontSizeControl").val(this.fontSize);
        $("#highContrastToggle").prop("checked", this.isHighContrast);

        // Font size control
        $("#fontSizeControl").change((e) => {
            this.adjustFontSize($(e.target).val());
        });

        // High contrast toggle
        $("#highContrastToggle").change((e) => {
            this.toggleHighContrast($(e.target).is(":checked"));
        });

        // Accessibility help
        $("#accessibilityHelp").click(() => {
            this.showAccessibilityHelp();
        });
    }

    setupHighContrastMode() {
        if (this.isHighContrast) {
            $("body").addClass("high-contrast");
        }
    }

    setupFontSizeControls() {
        $("body").addClass(`font-${this.fontSize}`);
    }

    setupAnnouncements() {
        // Listen for important events and announce them
        $(document).on("verificationCompleted", (e, data) => {
            this.announce(
                `Document verification completed. Status: ${data.status}`
            );
        });

        $(document).on("workflowStepCompleted", (e, data) => {
            this.announce(`Workflow step completed: ${data.step}`);
        });

        $(document).on("bulkOperationCompleted", (e, data) => {
            this.announce(
                `Bulk operation completed. ${data.count} documents processed`
            );
        });

        $(document).on("error", (e, data) => {
            this.announce(`Error: ${data.message}`, "assertive");
        });
    }

    adjustFontSize(size) {
        this.fontSize = size;
        $("body").removeClass(
            "font-small font-medium font-large font-extra-large"
        );
        $("body").addClass(`font-${size}`);

        localStorage.setItem("fontSize", size);
        this.announce(`Font size changed to ${size}`);
    }

    toggleHighContrast(enabled) {
        this.isHighContrast = enabled;

        if (enabled) {
            $("body").addClass("high-contrast");
            this.announce("High contrast mode enabled");
        } else {
            $("body").removeClass("high-contrast");
            this.announce("High contrast mode disabled");
        }

        localStorage.setItem("highContrast", enabled);
    }

    announce(message, priority = "polite") {
        const region =
            priority === "assertive" ? "#status-region" : "#live-region";
        $(region).text(message);

        // Clear after announcement
        setTimeout(() => {
            $(region).text("");
        }, 1000);
    }

    showAccessibilityHelp() {
        const helpHtml = `
            <div class="modal fade" id="accessibilityHelpModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-universal-access"></i> Accessibility Help
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Keyboard Navigation</h6>
                                    <ul>
                                        <li><kbd>Tab</kbd> - Move to next element</li>
                                        <li><kbd>Shift + Tab</kbd> - Move to previous element</li>
                                        <li><kbd>Enter</kbd> - Activate button or link</li>
                                        <li><kbd>Space</kbd> - Toggle checkbox or button</li>
                                        <li><kbd>Escape</kbd> - Close modal or dialog</li>
                                        <li><kbd>Arrow Keys</kbd> - Navigate table rows</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Screen Reader Support</h6>
                                    <ul>
                                        <li>All interactive elements have descriptive labels</li>
                                        <li>Status changes are announced automatically</li>
                                        <li>Tables include proper headers and descriptions</li>
                                        <li>Modals have proper focus management</li>
                                        <li>Skip links available for quick navigation</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>Visual Accessibility</h6>
                                    <ul>
                                        <li>High contrast mode for better visibility</li>
                                        <li>Adjustable font sizes</li>
                                        <li>Clear focus indicators</li>
                                        <li>Color is not the only way to convey information</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Bulk Operations</h6>
                                    <ul>
                                        <li>Select multiple documents with checkboxes</li>
                                        <li>Use keyboard to navigate and select</li>
                                        <li>Status updates announced to screen readers</li>
                                        <li>Progress indicators for long operations</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("body").append(helpHtml);
        $("#accessibilityHelpModal").modal("show");

        $("#accessibilityHelpModal").on("hidden.bs.modal", () => {
            $("#accessibilityHelpModal").remove();
        });
    }

    // Public methods for external use
    announceStatusChange(status, details = "") {
        this.announce(`Status changed to ${status}. ${details}`);
    }

    announceProgress(progress, message) {
        this.announce(`${message}. Progress: ${progress}%`);
    }

    announceError(error) {
        this.announce(`Error: ${error}`, "assertive");
    }

    announceSuccess(message) {
        this.announce(`Success: ${message}`);
    }

    // Method to enhance existing elements with accessibility features
    enhanceElement($element, options = {}) {
        const defaults = {
            role: null,
            ariaLabel: null,
            ariaDescribedBy: null,
            tabIndex: null,
        };

        const settings = { ...defaults, ...options };

        if (settings.role) {
            $element.attr("role", settings.role);
        }

        if (settings.ariaLabel) {
            $element.attr("aria-label", settings.ariaLabel);
        }

        if (settings.ariaDescribedBy) {
            $element.attr("aria-describedby", settings.ariaDescribedBy);
        }

        if (settings.tabIndex !== null) {
            $element.attr("tabindex", settings.tabIndex);
        }

        return $element;
    }
}

// Initialize when document is ready
$(document).ready(function () {
    window.accessibilityManager = new AccessibilityManager();
});
