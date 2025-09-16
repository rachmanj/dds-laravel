# Document Distribution System (DDS) Training Materials

## Introduction

Welcome to the Document Distribution System (DDS) training program. This comprehensive guide is designed to help you understand and effectively use the DDS Laravel application in your daily operations. The system manages document workflows across multiple departments, handling invoices, additional documents, and their distribution through a secure, role-based workflow.

## Table of Contents

1. [System Overview](#system-overview)
2. [Core Features](#core-features)
3. [User Roles and Permissions](#user-roles-and-permissions)
4. [Getting Started](#getting-started)
5. [Working with Invoices](#working-with-invoices)
6. [Working with Additional Documents](#working-with-additional-documents)
7. [Distribution Workflow](#distribution-workflow)
8. [Document Status Management](#document-status-management)
9. [SAP Document Updates](#sap-document-updates)
10. [Invoice Payment Management](#invoice-payment-management)
11. [Reconciliation System](#reconciliation-system)
12. [Story-Based Learning Scenarios](#story-based-learning-scenarios)

## System Overview

The Document Distribution System (DDS) is a comprehensive application designed for managing document workflows across multiple departments. The system handles:

-   **Invoices**: Financial documents with supplier and project information
-   **Additional Documents**: Supporting documents that can be linked to invoices
-   **Distributions**: The process of moving documents between departments
-   **Document Status Tracking**: Complete visibility of document location and status
-   **Payment Management**: Tracking invoice payment status and history

The system is built with a secure, role-based workflow that ensures documents move through your organization efficiently while maintaining proper tracking and accountability.

## Core Features

### Document Management

-   Create, view, edit, and track invoices and additional documents
-   Link supporting documents to invoices
-   Upload attachments (up to 50MB per file)
-   Track document status and location

### Distribution System

-   Create distributions to move documents between departments
-   Verification process for both sender and receiver
-   Complete audit trail of document movement
-   Status tracking to prevent duplicate distributions

### Financial Features

-   Invoice payment status tracking
-   Days calculation for pending payments
-   Reconciliation with external data
-   SAP document number management

### Reporting & Analytics

-   Dashboard with critical workflow metrics
-   Document status and location reporting
-   Distribution workflow performance analytics
-   Financial metrics and processing analysis

## User Roles and Permissions

The DDS system uses role-based access control to ensure users can only access features relevant to their job function:

| Role         | Primary Responsibilities       | Key Permissions                        |
| ------------ | ------------------------------ | -------------------------------------- |
| Admin        | System administration          | All permissions                        |
| Superadmin   | System oversight               | All permissions                        |
| Accounting   | Financial document management  | Invoice creation, payment updates      |
| Finance      | Payment processing             | Payment status updates, reconciliation |
| Logistics    | Document distribution          | Distribution creation and management   |
| Regular User | Department-specific operations | Limited to own department documents    |

## Getting Started

### Logging In

1. Navigate to the DDS system URL
2. Enter your username or email
3. Enter your password
4. Click "Sign In"

### Dashboard Overview

After logging in, you'll see the main dashboard with:

-   Critical workflow metrics
-   Documents requiring attention
-   Recent activity
-   Quick access to common functions

### Navigation

-   The left sidebar contains all main menu items
-   Menu items are displayed based on your permissions
-   The top navigation bar shows your user information and logout option

## Working with Invoices

### Creating a New Invoice

1. Navigate to Invoices > Create New
2. Fill in required information:
    - Invoice number
    - Invoice date
    - Receive date
    - Supplier (select from dropdown)
    - PO number (if applicable)
    - Project information
    - Currency and amount
    - Invoice type
3. Add any remarks or notes
4. Click "Save" to create the invoice

### Linking Additional Documents

When creating or editing an invoice, you can link existing additional documents:

1. In the invoice form, scroll to "Additional Documents"
2. Use the search function to find documents by PO number, document number, etc.
3. Select documents to link to the invoice
4. Click "Save" to update the invoice with linked documents

### On-the-Fly Document Creation

Users with appropriate permissions can create additional documents directly from the invoice form:

1. Click "Create New Document" button
2. Fill in document details in the modal form
3. Submit the form to create the document
4. The newly created document is automatically selected for linking

### Viewing Invoice Details

1. Navigate to Invoices > All Invoices
2. Use filters to find specific invoices
3. Click on an invoice to view details
4. The detail page shows all invoice information, linked documents, and history

## Working with Additional Documents

### Creating Additional Documents

1. Navigate to Additional Documents > Create New
2. Fill in required information:
    - Document type
    - Document number
    - Document date
    - PO number (if applicable)
    - Project information
3. Upload an attachment if needed
4. Add any remarks or notes
5. Click "Save" to create the document

### Importing Additional Documents

For bulk document creation:

1. Navigate to Additional Documents > Import
2. Download the template Excel file
3. Fill in document information in the template
4. Upload the completed Excel file
5. Review the import results

### Viewing and Managing Documents

1. Navigate to Additional Documents > All Documents
2. Use filters to find specific documents
3. Click on a document to view details
4. Use the "Show All Records" toggle to view documents outside your department (if permitted)

## Distribution Workflow

The distribution workflow follows these stages:

```
Draft → Verified by Sender → Sent → Received → Verified by Receiver → Completed
```

### Creating a Distribution

1. Navigate to Distributions > Create New
2. Select the distribution type (Normal, Urgent, Express, etc.)
3. Select origin department (your department)
4. Select destination department
5. Choose documents to include in the distribution
6. Click "Save" to create the draft distribution

### Sender Verification

1. Navigate to Distributions > All Distributions
2. Find the draft distribution and click to view details
3. Review all documents included in the distribution
4. Select documents to verify and mark as "Verified"
5. Add any verification notes
6. Click "Verify Documents" to complete sender verification

### Sending a Distribution

1. After sender verification is complete
2. Click "Send Distribution" button
3. Confirm the action
4. The distribution status changes to "Sent"
5. Documents become "in_transit" and cannot be selected for new distributions

### Receiving a Distribution

1. Navigate to Distributions > All Distributions
2. Find incoming distributions marked "Sent"
3. Click to view details
4. Click "Receive Distribution" button
5. Confirm the action
6. The distribution status changes to "Received"

### Receiver Verification

1. After receiving a distribution
2. Review all documents included
3. Mark each document as "Verified" or report discrepancies
4. Add verification notes if needed
5. Click "Verify Documents" to complete receiver verification

### Completing a Distribution

1. After receiver verification is complete
2. Click "Complete Distribution" button
3. Confirm the action
4. The distribution status changes to "Completed"
5. Verified documents become "distributed" and their location updates to the destination department

### Printing a Transmittal Advice

1. View a distribution
2. Click "Print" button
3. A printer-friendly version opens
4. Click the floating print button or use browser print function
5. The printed document shows all distribution details and included documents

## Document Status Management

Documents in the system have a distribution status that controls their availability:

| Status          | Description                           | Can be distributed? |
| --------------- | ------------------------------------- | ------------------- |
| Available       | Ready for distribution                | Yes                 |
| In Transit      | Currently in a sent distribution      | No                  |
| Distributed     | Successfully delivered to destination | No                  |
| Unaccounted For | Missing or damaged in transit         | No                  |

### Resetting Document Status

Users with the `reset-document-status` permission can:

1. Navigate to Master Data > Document Status
2. Find documents by status, type, or search terms
3. Reset individual document status with a reason
4. Perform bulk reset for unaccounted_for documents

## SAP Document Updates

The SAP Document Update feature allows users to manage SAP document numbers for invoices:

### Updating SAP Document Numbers

1. Navigate to Invoices > SAP Update
2. Use the dashboard to see completion statistics
3. Go to "Without SAP Doc" to see invoices needing SAP numbers
4. Enter SAP document numbers for invoices
5. The system validates uniqueness in real-time
6. Click "Update" to save the SAP document number

### Viewing Invoices with SAP Documents

1. Navigate to Invoices > SAP Update
2. Click "With SAP Doc" tab
3. View all invoices with assigned SAP document numbers
4. Use filters to find specific invoices

## Invoice Payment Management

The Invoice Payment Management system allows tracking and updating payment status:

### Dashboard Overview

1. Navigate to Invoice Payments > Dashboard
2. View payment metrics and statistics
3. See overdue invoices requiring attention
4. Access quick links to pending payments

### Managing Waiting Payments

1. Navigate to Invoice Payments > Waiting Payment
2. View invoices pending payment
3. See days since received with color coding
4. Select invoices to mark as paid
5. Enter payment date and click "Update" to process

### Viewing Paid Invoices

1. Navigate to Invoice Payments > Paid Invoices
2. View historical payment records
3. See who processed each payment and when
4. Update payment details or revert to pending if needed

## Reconciliation System

The Reconciliation feature allows matching external invoice data with internal records:

### Importing Reconciliation Data

1. Navigate to Reports > Reconciliation
2. Click "Upload" button
3. Select supplier from dropdown
4. Upload Excel file with external invoice data
5. The system processes the import and shows results

### Viewing Reconciliation Results

1. After import, see statistics dashboard
2. View matched and unmatched records
3. Use filters to find specific invoices
4. Click on records to see detailed matching information

### Exporting Results

1. After reviewing reconciliation data
2. Click "Export" button
3. Download Excel file with complete reconciliation results
4. Use for reporting or further analysis

## Story-Based Learning Scenarios

### Scenario 1: Processing a New Invoice

**Background:**
You work in the Accounting department and have received a new invoice from supplier ABC Corporation for project 000H. You need to enter it into the system and link it to the relevant additional documents.

**Tasks:**

1. Create a new invoice with the following details:

    - Invoice Number: INV-2025-001
    - Invoice Date: Today's date
    - Receive Date: Today's date
    - Supplier: ABC Corporation
    - PO Number: PO-2025-001
    - Project: 000H
    - Amount: $5,000
    - Type: Service

2. Search for additional documents related to PO-2025-001
3. Link the found documents to the invoice
4. Create a new additional document "on-the-fly" for a missing Time Sheet
5. Save the invoice with all linked documents

**Learning Objectives:**

-   Creating new invoices
-   Searching for related documents
-   Linking documents to invoices
-   Creating additional documents on-the-fly

### Scenario 2: Distributing Documents Between Departments

**Background:**
You work in the Logistics department and need to send several invoices and additional documents to the Finance department for payment processing.

**Tasks:**

1. Create a new distribution with the following details:

    - Type: Normal
    - Origin: Logistics
    - Destination: Finance
    - Documents: Select 3 invoices and their linked additional documents

2. Complete the sender verification process
3. Send the distribution to Finance
4. Switch roles (for training) to Finance department
5. Receive the distribution
6. Complete receiver verification
7. Complete the distribution

**Learning Objectives:**

-   Creating distributions
-   Understanding the verification process
-   Following the complete distribution workflow
-   Handling document status changes

### Scenario 3: Managing Payment Status

**Background:**
You work in the Finance department and need to update the payment status of several invoices that have been paid today.

**Tasks:**

1. Navigate to Invoice Payments > Waiting Payment
2. Filter for invoices from a specific supplier
3. Review the days since received for each invoice
4. Select multiple invoices for bulk payment update
5. Enter payment date and update status
6. Verify the invoices now appear in Paid Invoices

**Learning Objectives:**

-   Using the payment management system
-   Performing bulk operations
-   Understanding payment tracking
-   Using filters and search functions

### Scenario 4: Reconciling External Invoice Data

**Background:**
You've received an Excel file from the Finance team with external invoice data that needs to be reconciled with the system records.

**Tasks:**

1. Navigate to Reports > Reconciliation
2. Download the template Excel file
3. Upload the provided sample data file
4. Select the appropriate supplier
5. Review the reconciliation results
6. Identify matched and unmatched records
7. Export the results for reporting

**Learning Objectives:**

-   Using the reconciliation system
-   Understanding matching algorithms
-   Analyzing reconciliation results
-   Exporting data for reporting

### Scenario 5: Handling Missing Documents

**Background:**
A document that was sent in a distribution has been reported missing by the receiving department. You need to reset its status so it can be redistributed.

**Tasks:**

1. Navigate to Master Data > Document Status
2. Filter for documents with "Unaccounted For" status
3. Locate the specific document by searching for its number
4. Reset the document status to "Available"
5. Provide a reason for the status change
6. Verify the document can now be selected for a new distribution

**Learning Objectives:**

-   Managing document status
-   Handling exceptions in the workflow
-   Understanding the audit trail
-   Resolving document discrepancies

### Scenario 6: Updating SAP Document Numbers

**Background:**
Several invoices have been processed in SAP and now need their SAP document numbers updated in the DDS system.

**Tasks:**

1. Navigate to Invoices > SAP Update
2. Review the dashboard statistics
3. Go to the "Without SAP Doc" section
4. Enter SAP document numbers for several invoices
5. Experience the real-time validation (try entering a duplicate number)
6. Complete the updates and verify they appear in "With SAP Doc" section

**Learning Objectives:**

-   Using the SAP update feature
-   Understanding real-time validation
-   Tracking SAP document completion
-   Using the dashboard for progress monitoring

## Conclusion

This training guide provides a comprehensive overview of the Document Distribution System. By following the instructions and working through the scenarios, you'll develop the skills needed to effectively use the system in your daily operations.

Remember that the system is designed to improve document workflow efficiency, enhance tracking, and ensure proper accountability throughout your organization. If you have questions or need assistance, please contact your system administrator.
