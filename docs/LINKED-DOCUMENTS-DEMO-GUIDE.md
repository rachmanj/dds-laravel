# Linked Documents Management Feature - Demo Guide

## Overview

This guide demonstrates the **Linked Documents Management** feature in the DDS (Document Distribution System). This feature automatically detects additional documents linked to selected invoices and allows users to manage which linked documents to include in their distribution.

## Feature Components

### 1. **Department Location Indicators**

-   Green badges showing "000HACC" in the Location column
-   Visual indicators for documents in the current department
-   Available for both Invoice and Additional Document tables

### 2. **Confirmation Dialog**

-   Appears when submitting distribution form
-   Shows distribution summary and selected documents
-   Displays linked documents section when found

### 3. **Linked Documents Management Modal**

-   Allows selection/deselection of linked documents
-   Checkbox interface for each linked document
-   Save/Cancel functionality

## Step-by-Step Demo

### Step 1: Setup Distribution Form

1. **Navigate to**: `http://localhost:8000/distributions/create`
2. **Select Document Type**: Choose "Invoice"
3. **Select Distribution Type**: Choose "Normal (NORM)"
4. **Select Destination Department**: Choose "Finance (001HFIN)"

### Step 2: Select Invoice with Linked Documents

5. **Find Invoice**: Look for invoice "345656" (ABADI TOWER)
6. **Check the checkbox** next to this invoice
7. **Verify**: The invoice has a linked additional document "251006079"

### Step 3: Submit and View Confirmation Dialog

8. **Click**: "Create Distribution" button
9. **Confirmation Dialog appears** with:
    - Distribution Information (Type, Destination, Document Type, Notes)
    - Selected Documents (Invoice 345656)
    - **Linked Documents Section** showing additional document "251006079"

### Step 4: Access Linked Documents Management

10. **Click**: "Manage Linked Documents" button in the confirmation dialog
11. **Linked Documents Management Modal opens** with:
    -   Title: "Manage Linked Additional Documents"
    -   List of linked documents with checkboxes
    -   Document details (number, type, PO number)
    -   Save Selection and Cancel buttons

### Step 5: Manage Document Selection

12. **Review**: The linked document is checked by default
13. **Option to uncheck** if you don't want to include it
14. **Click**: "Save Selection" to apply changes
15. **Click**: "Cancel" to discard changes

## Technical Implementation

### Backend API

-   **Endpoint**: `POST /distributions/check-linked-documents`
-   **Purpose**: Find additional documents linked to selected invoices
-   **Logic**: Links via PO number matching between invoices and additional documents
-   **Response**: JSON with linked document details

### Frontend Components

-   **Confirmation Modal**: Bootstrap modal with distribution summary
-   **Linked Documents Section**: Dynamic content showing found linked documents
-   **Management Modal**: Separate modal for document selection management
-   **AJAX Integration**: Real-time checking for linked documents

### Database Relationships

-   **Invoices**: `po_no` field
-   **Additional Documents**: `po_no` field (matching link)
-   **Location Filtering**: `cur_loc` field for department-based filtering

## Screenshots Available

1. **`confirmation-modal-working.png`**: Shows the confirmation dialog with linked documents section
2. **`linked-documents-management-modal-working.png`**: Shows the management modal with checkboxes

## Key Features Demonstrated

✅ **Automatic Detection**: System automatically finds linked additional documents
✅ **Visual Indicators**: Clear location badges and document status
✅ **User Control**: Ability to select/deselect linked documents
✅ **Confirmation Flow**: Review before submission
✅ **Modal Interface**: Clean, intuitive management interface
✅ **Real-time Updates**: AJAX-based linked document detection

## Benefits

1. **Data Integrity**: Ensures related documents are not missed
2. **User Control**: Users can choose which linked documents to include
3. **Visual Clarity**: Clear indicators and organized interface
4. **Efficiency**: Automated detection reduces manual work
5. **Flexibility**: Can include or exclude linked documents as needed

## Testing Data Used

-   **Invoice**: 345656 (ABADI TOWER)
-   **Linked Document**: 251006079 (Type: ITO)
-   **PO Number**: 250206117 (matching link)
-   **Location**: 000HACC (current department)

This feature successfully demonstrates the complete linked documents management workflow from detection to user control.
