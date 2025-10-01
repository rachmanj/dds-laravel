# Invoice Create Page - UX Patterns Reference

**Date**: October 1, 2025  
**Implementation**: `resources/views/invoices/create.blade.php`

This document serves as a reference for implementing similar UX patterns across other forms in the DDS system.

---

## 📝 **Form UX Patterns Implemented**

### **1. Keyboard Shortcuts**

**Purpose**: Accelerate data entry for power users and reduce reliance on mouse navigation.

**Implementation**:

-   **Ctrl+S**: Save with validation check
-   **Esc**: Cancel and return (scoped to avoid modal conflicts)
-   **Ctrl+Enter**: Quick action in specific fields (e.g., PO search)

**Code Pattern**:

```javascript
$(document).on("keydown", function (e) {
    // Ctrl+S to save
    if (e.ctrlKey && e.key === "s") {
        e.preventDefault();
        if (allRequiredFieldsFilled()) {
            $("form").submit();
        } else {
            toastr.warning("Complete required fields first");
        }
    }

    // Esc to cancel (avoid modal interference)
    if (
        e.key === "Escape" &&
        !$(".modal").hasClass("show") &&
        !$(".swal2-container").length
    ) {
        e.preventDefault();
        window.location.href = cancelUrl;
    }
});
```

**Visual Pattern**:

```html
<div class="alert alert-info alert-dismissible">
    <strong><i class="fas fa-keyboard"></i> Keyboard Shortcuts:</strong>
    <kbd>Ctrl+S</kbd> Save | <kbd>Esc</kbd> Cancel |
    <kbd>Ctrl+Enter</kbd> Search
</div>
```

---

### **2. Form Progress Indicator**

**Purpose**: Provide real-time feedback on form completion to reduce abandonment.

**Features**:

-   Real-time progress bar
-   Color-coded: Red (<40%), Yellow (40-79%), Green (80-100%)
-   Text counter: "X/8 required fields completed"
-   Animated when 100% complete

**Code Pattern**:

```javascript
function updateFormProgress() {
    var requiredFields = $("[required]:visible");
    var filledFields = requiredFields.filter(function () {
        var val = $(this).val();
        return val !== "" && val !== null && val.toString().trim() !== "";
    });

    var total = requiredFields.length;
    var filled = filledFields.length;
    var percentage = Math.round((filled / total) * 100);

    $("#form-progress-bar")
        .css("width", percentage + "%")
        .text(percentage + "%")
        .removeClass("bg-danger bg-warning bg-success")
        .addClass(
            percentage < 40
                ? "bg-danger"
                : percentage < 80
                ? "bg-warning"
                : "bg-success"
        );

    $("#progress-text").text(
        filled + "/" + total + " required fields completed"
    );
}

$("form :input").on("change input blur", updateFormProgress);
```

**Visual Pattern**:

```html
<div class="card bg-light">
    <div class="card-body p-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong><i class="fas fa-tasks"></i> Form Progress:</strong>
                <span id="progress-text">0/8 required fields completed</span>
            </div>
            <div class="progress" style="width: 300px; height: 25px;">
                <div
                    class="progress-bar progress-bar-striped"
                    id="form-progress-bar"
                    role="progressbar"
                    style="width: 0%"
                >
                    0%
                </div>
            </div>
        </div>
    </div>
</div>
```

---

### **3. Enhanced Submit Button**

**Purpose**: Prevent double-submission and provide clear feedback during save.

**Features**:

-   Large buttons (btn-lg) for visibility
-   Cancel button next to Submit
-   Loading state with spinner
-   Buttons disabled during submission
-   Status indicator

**Code Pattern**:

```javascript
var isSubmitting = false;

$("#submit-btn").on("click", function (e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
});

function onFormSubmit() {
    if (isSubmitting) return false;

    isSubmitting = true;

    $("#submit-btn")
        .prop("disabled", true)
        .html('<i class="fas fa-spinner fa-spin"></i> Creating...')
        .removeClass("btn-primary")
        .addClass("btn-secondary");

    $("#cancel-btn").addClass("disabled").css("pointer-events", "none");
    $("#save-status").show();
}
```

**Visual Pattern**:

```html
<div class="card-footer">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <button
                type="submit"
                class="btn btn-primary btn-lg"
                id="submit-btn"
            >
                <i class="fas fa-save"></i> Create Invoice
            </button>
            <a
                href="{{ route('invoices.index') }}"
                class="btn btn-outline-secondary btn-lg ml-2"
                id="cancel-btn"
            >
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
        <div id="save-status" style="display:none;">
            <i class="fas fa-spinner fa-spin"></i>
            <strong>Creating invoice...</strong>
        </div>
    </div>
    <small class="text-muted mt-2 d-block">
        <i class="fas fa-info-circle"></i>
        Tip: Press <kbd>Ctrl+S</kbd> to save or <kbd>Esc</kbd> to cancel
    </small>
</div>
```

---

### **4. Progressive Disclosure with Collapsed Cards**

**Purpose**: Reduce initial visual complexity by hiding optional sections.

**Features**:

-   Starts collapsed by default
-   Auto-expands when relevant
-   Manual toggle button
-   "Optional" badge indicator

**Code Pattern**:

```javascript
// Auto-expand when data is available
function expandCardIfNeeded() {
    if ($("#optional-card").hasClass("collapsed-card")) {
        $("#optional-card").find('[data-card-widget="collapse"]').click();
    }
}
```

**Visual Pattern**:

```html
<div
    class="card card-outline card-secondary mt-3 collapsed-card"
    id="optional-card"
>
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-link"></i> Optional Section
            <span class="badge badge-secondary">Optional</span>
        </h3>
        <div class="card-tools">
            <button
                type="button"
                class="btn btn-tool"
                data-card-widget="collapse"
            >
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="card-body" style="display: none;">
        <!-- Content -->
    </div>
</div>
```

---

### **5. SweetAlert2 Warning Dialogs**

**Purpose**: Provide rich, informative confirmations for potentially problematic actions.

**Features**:

-   Beautiful modal design
-   Context-rich messaging
-   Shows relevant data
-   Confirm/Cancel options
-   Success feedback

**Code Pattern**:

```javascript
Swal.fire({
    title: "Document Already Linked",
    html:
        '<div class="text-left">' +
        "<p>This document (<strong>" +
        docNumber +
        "</strong>) is already linked to " +
        "<strong>" +
        count +
        "</strong> other invoice(s):</p>" +
        '<div class="alert alert-warning mt-2 mb-2">' +
        '<i class="fas fa-link"></i> <strong>Currently linked to:</strong><br>' +
        '<span class="small">' +
        linkedList +
        "</span>" +
        "</div>" +
        '<p class="mt-3">Linking to multiple invoices is allowed. Continue?</p>' +
        "</div>",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-link"></i> Yes, Link Anyway',
    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
    confirmButtonColor: "#ffc107",
    cancelButtonColor: "#6c757d",
    reverseButtons: true,
    width: "600px",
}).then((result) => {
    if (result.isConfirmed) {
        // Proceed
        toastr.success("Document linked successfully");
    } else {
        // Cancel
        $(checkbox).prop("checked", false);
    }
});
```

**Design Guidelines**:

-   Use for important confirmations requiring context
-   Include relevant data in alert boxes
-   Use warning color (#ffc107) for cautions
-   Reverse button order (confirm on right)
-   Always handle both outcomes
-   Follow with success/error feedback

---

### **6. Enhanced Dropdown Information**

**Purpose**: Show critical reference information inline to reduce lookups.

**Patterns**:

**Supplier with SAP Code**:

```blade
<option value="{{ $supplier->id }}" data-sap-code="{{ $supplier->sap_code ?? '' }}">
    {{ $supplier->name }}@if($supplier->sap_code) ({{ $supplier->sap_code }})@endif
</option>
```

Result: "PT ABC Company (V12345)"

**Project with Owner**:

```blade
<option value="{{ $project->code }}">
    {{ $project->code }}@if($project->owner) - {{ $project->owner }}@endif
</option>
```

Result: "001H - Finance Department"

**Guidelines**:

-   Show secondary info in parentheses or after dash
-   Use data attributes for additional metadata
-   Works seamlessly with Select2 search
-   Don't overload with too much information

---

### **7. Field Help Text & Tooltips**

**Purpose**: Provide contextual help without cluttering the interface.

**Code Pattern**:

```html
<label for="field_name">
    Field Name <span class="text-danger">*</span>
    <i
        class="fas fa-question-circle text-info ml-1"
        data-toggle="tooltip"
        data-placement="top"
        title="Helpful description of this field's purpose and format"
    ></i>
</label>
<input
    type="text"
    class="form-control"
    placeholder="e.g., 010.000-25.00000123"
/>
```

**JavaScript Initialization**:

```javascript
$('[data-toggle="tooltip"]').tooltip({
    trigger: "hover",
    html: true,
    boundary: "window",
});
```

**Guidelines**:

-   Use fa-question-circle icon in text-info color
-   Place after label, before field
-   Keep tooltip text concise but informative
-   Include format examples when helpful
-   Add placeholder text for additional guidance
-   Initialize tooltips on page load

---

## 🎯 **When to Use Each Pattern**

| Pattern              | Best For                                        | Avoid When                           |
| -------------------- | ----------------------------------------------- | ------------------------------------ |
| Keyboard Shortcuts   | Forms used frequently by power users            | Simple forms, infrequent use         |
| Progress Indicator   | Multi-section forms with 6+ required fields     | Simple forms (<5 fields)             |
| Enhanced Submit      | All forms to prevent double-submission          | Read-only views                      |
| Collapsed Cards      | Long forms with optional sections               | Short forms, all fields important    |
| SweetAlert2 Warnings | Potentially problematic actions needing context | Simple yes/no questions (use Toastr) |
| Enhanced Dropdowns   | Lists where secondary info aids selection       | Simple name-only lists               |
| Tooltips             | Complex or unfamiliar fields                    | Self-explanatory fields              |

---

## 📊 **Performance Considerations**

**Progress Indicator**:

-   Debounce input events if form is very large (>50 fields)
-   Exclude auto-filled fields from required count

**Tooltips**:

-   Initialize only once on page load
-   Use delegation for dynamic elements

**SweetAlert2**:

-   Load library asynchronously if not used on every page
-   Cache frequently-shown dialogs

---

## ♿ **Accessibility Guidelines**

**Keyboard Shortcuts**:

-   Never override browser/OS shortcuts
-   Provide visual indication of available shortcuts
-   Work without JavaScript (forms should still submit)

**Progress Bars**:

-   Include `role="progressbar"` and ARIA attributes
-   Provide text alternative to visual progress

**Tooltips**:

-   Don't rely solely on hover (touch devices)
-   Ensure keyboard accessible
-   Use appropriate ARIA labels

---

## 🔄 **Future Enhancements to Consider**

1. **Auto-save with conflict detection** - Warn if another user modified record
2. **Field-level progress** - Show which specific required fields are missing
3. **Smart defaults** - Pre-fill based on user history
4. **Bulk validation** - Validate all fields on demand before submit
5. **Contextual help panel** - Expandable sidebar with detailed field help
6. **Form templates** - Save and reuse common form configurations

---

**Reference Implementation**: `resources/views/invoices/create.blade.php`  
**Documentation**: `INVOICE_CREATE_IMPROVEMENTS_SUMMARY.md`  
**Decision Record**: `docs/decisions.md` (2025-10-01)
