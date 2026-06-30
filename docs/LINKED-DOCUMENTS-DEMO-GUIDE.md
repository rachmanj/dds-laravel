# Linked Documents Management Feature - Demo Guide

> **Updated 2026-06-30**: Linked documents at distribution create are detected via **`additional_document_invoice`** (invoice-attached), not PO number equality. See [`docs/decisions.md`](decisions.md) (2026-06-30).

## Overview

This guide demonstrates the **Linked Documents Management** feature in the DDS (Document Distribution System). When creating an invoice distribution, the system finds additional documents **explicitly linked to selected invoices** and lets users choose which to include before submission.

## Feature Components

### 1. **Department Location Indicators**

-   Green badges showing location code in the Location column
-   Visual indicators for documents in the current department
-   Available for both Invoice and Additional Document tables

### 2. **Confirmation Dialog**

-   Appears when submitting distribution form
-   Shows distribution summary and selected documents
-   Displays linked documents section when pivot-linked additional documents exist
-   **Confirm** is enabled only after linked-documents AJAX completes

### 3. **Linked Documents Management Modal**

-   Allows selection/deselection of linked documents before create
-   Checkbox interface for each linked document
-   Shows document number, type, PO, and linked invoice number(s)
-   Save/Cancel functionality

### 4. **Edit Page — Other Additional Documents (2026-06-30)**

-   Draft edit lists all documents on the distribution
-   **Other Additional Documents** section shows rows not pivot-linked to any selected invoice (e.g. legacy PO-only attaches)
-   Icon-only remove buttons on every row

## Step-by-Step Demo

### Step 1: Setup Distribution Form

1. **Navigate to**: `http://localhost:8000/distributions/create`
2. **Select Document Type**: Choose "Invoice"
3. **Select Distribution Type**: Choose appropriate type
4. **Select Destination Department**: Choose target department

### Step 2: Select Invoice with Linked Documents

5. **Find Invoice**: Choose an invoice that has additional documents linked on the **invoice edit** page (`additional_document_invoice`)
6. **Check the checkbox** next to that invoice
7. **Verify**: Linked additional documents appear only if attached to that invoice in the pivot table (not merely same PO)

### Step 3: Submit and View Confirmation Dialog

8. **Click**: "Create Distribution" button
9. **Confirmation Dialog appears** with:
    - Distribution Information (Type, Destination, Document Type, Notes)
    - Selected Documents (chosen invoices)
    - **Linked Documents Section** — invoice-attached additional documents (review before confirm)

### Step 4: Access Linked Documents Management

10. **Click**: "Manage Linked Documents" in the confirmation dialog
11. **Linked Documents Management Modal opens** with:
    -   List of linked documents with checkboxes
    -   Document details (number, type, PO, invoice number)
    -   Save Selection and Cancel buttons

### Step 5: Manage Document Selection

12. **Review**: Linked pivot documents are checked by default
13. **Uncheck** any document you do not want in the distribution
14. **Click**: "Save Selection"
15. **Click**: "Confirm & Create Distribution"

### Step 6: Sync on Draft Show (optional)

16. After create, open draft distribution show page
17. Use **Sync linked documents** if invoices gained new pivot links after creation

## Technical Implementation

### Backend API

-   **Endpoint**: `POST /distributions/check-linked-documents`
-   **Purpose**: Find additional documents linked to selected invoices
-   **Logic (current)**: `whereHas('invoices', selected invoice IDs)`, department `cur_loc`, `availableForDistribution()`
-   **Not used for distribution create**: PO number-only matching (removed 2026-06-30 — caused false positives)
-   **Response**: JSON with linked document details including `invoice_numbers`

### Frontend Components

-   **Confirmation Modal**: Bootstrap modal with distribution summary
-   **Linked Documents Section**: Dynamic content; user must confirm inclusion
-   **Management Modal**: Checkbox selection before create
-   **AJAX Integration**: Linked-doc check gates confirm button

### Database Relationships

-   **Primary link**: `additional_document_invoice` (invoice_id ↔ additional_document_id)
-   **Distribution rows**: `distribution_documents`
-   **PO field**: informational; PO search on **invoice create/edit** is separate from distribution linking

## Key Features Demonstrated

✅ **Pivot-based detection**: Only invoice-attached additional documents suggested at create  
✅ **User control**: Select/deselect before submission  
✅ **Confirmation flow**: Review before submission  
✅ **Edit visibility**: All attached additional documents visible and removable on draft edit  
✅ **Manual sync**: Draft show sync for newly linked documents  

## Benefits

1. **Data integrity**: Avoids bundling unrelated documents that share a PO
2. **User control**: Explicit confirmation of supporting documents
3. **Live-safe cleanup**: Legacy PO-only rows removable on edit without migration scripts
4. **Flexibility**: Sync linked documents when invoice links change after draft creation

## Testing

-   `tests/Feature/DistributionCheckLinkedDocumentsTest.php`
-   `tests/Feature/DistributionEditDocumentsTest.php`

**Last Updated**: 2026-06-30
