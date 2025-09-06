Distribution Workflow
6-Stage Workflow:

Draft: Initial creation
Verified by Sender: Sender verification
Sent: Document transmission
Received: Receipt confirmation
Verified by Receiver: Receiver verification
Completed: Final completion
Document Verification: Sender and receiver verification with status tracking

Discrepancy Management: Handle missing or damaged documents

Transmittal Advice: Generate distribution reports

Distribution Types: Priority classification (Normal, Urgent, etc.)

Distribution System - Comprehensive Analysis
Database Tables Involved
Core Distribution Tables
distributions - Main distribution entity
distribution_documents - Polymorphic pivot table for document associations
distribution_histories - Audit trail for all workflow changes
distribution_types - Distribution priority and type classification
Supporting Tables
departments - Origin and destination departments
users - Creators, verifiers, and actors in the workflow
invoices - Primary document type for distribution
additional_documents - Secondary document type for distribution
Distribution Table Structure
Main Fields (distributions table)

-   id (Primary Key)
-   distribution_number (Unique, auto-generated: YY/LOCATION/DDS/0001)
-   type_id (Foreign Key → distribution_types)
-   origin_department_id (Foreign Key → departments)
-   destination_department_id (Foreign Key → departments)
-   document_type (enum: 'invoice', 'additional_document')
-   created_by (Foreign Key → users)
-   status (enum: draft → verified_by_sender → sent → received → verified_by_receiver → completed)
-   notes (Text, general distribution notes)

// Workflow Timestamps

-   created_at (Distribution creation)
-   sender_verified_at (When sender completed verification)
-   sent_at (When distribution was sent)
-   received_at (When distribution was received)
-   receiver_verified_at (When receiver completed verification)
-   updated_at (Last modification)

// Verification Tracking

-   sender_verified_by (Foreign Key → users, who verified as sender)
-   sender_verification_notes (Text, sender's verification notes)
-   receiver_verified_by (Foreign Key → users, who verified as receiver)
-   receiver_verification_notes (Text, receiver's verification notes)
-   has_discrepancies (Boolean, if any documents have issues)

// Soft Delete

-   deleted_at (For soft deletion support)
    Document Association (distribution_documents table)
-   id (Primary Key)
-   distribution_id (Foreign Key → distributions)
-   document_type (Polymorphic: 'App\Models\Invoice' | 'App\Models\AdditionalDocument')
-   document_id (Polymorphic ID for the actual document)
-   origin_cur_loc (string, nullable) — snapshot of document location when distribution created
-   skip_verification (boolean, default false) — if document was not in origin department at creation

// Document-Level Verification

-   sender_verified (Boolean, if sender verified this specific document)
-   sender_verification_status (enum: 'verified', 'missing', 'damaged')
-   sender_verification_notes (Text, notes for this specific document)
-   receiver_verified (Boolean, if receiver verified this specific document)
-   receiver_verification_status (enum: 'verified', 'missing', 'damaged')
-   receiver_verification_notes (Text, notes for this specific document)

-   created_at, updated_at (Timestamps)
    Distribution Workflow Analysis

1. Draft Stage (Initial Creation)
   Triggered by: POST /api/distributions Fields Updated:

'status' => 'draft'
'created_by' => Auth::id()
'distribution_number' => 'YY/LOCATION/DDS/0001'
'created_at' => now()
Document Operations:

Validates document location matches user's department location
Auto-includes related documents (additional docs attached to invoices)
Creates entries in distribution_documents table History Entry: 'created' action logged 2. Sender Verification Stage
Triggered by: POST /api/distributions/{id}/verify-sender Fields Updated:

'status' => 'verified_by_sender'
'sender_verified_at' => now()
'sender_verified_by' => Auth::id()
'sender_verification_notes' => $verificationNotes
Document-Level Updates:

// For each document in document_verifications array:
'sender_verified' => true
'sender_verification_status' => 'verified' | 'missing' | 'damaged'
'sender_verification_notes' => $notes_per_document
Business Rules:

Only allowed if status is 'draft'
Each document can be marked as verified, missing, or damaged
Individual verification notes per document History Entry: 'verified_by_sender' action logged 3. Send Stage
Triggered by: POST /api/distributions/{id}/send Fields Updated:

'status' => 'sent'
'sent_at' => now()
Business Rules:

Only allowed if status is 'verified_by_sender'
No document modifications allowed
Triggers notification to destination department History Entry: 'sent' action logged 4. Receive Stage
Triggered by: POST /api/distributions/{id}/receive Fields Updated:

'status' => 'received'
'received_at' => now()
Critical Document Location Updates:

// Updates cur_loc field for distributed documents
Invoice::where('id', $invoiceId)->update(['cur_loc' => $destinationLocationCode]);
AdditionalDocument::where('id', $docId)->update(['cur_loc' => $destinationLocationCode]);
Business Rules:

Only allowed if status is 'sent'
KEY FEATURE: Automatically updates document locations to destination department
Exceptions for Out-of-Origin Additional Documents:

-   Additional documents that were not in the origin department at creation (skip_verification = true) are informational only in this distribution:
    -   No distribution_status change on Send/Receive
    -   No cur_loc change on Receive/Complete
    -   Verification inputs disabled in sender/receiver modals
    -   Sender/Receiver/Overall columns show a neutral badge: "Not included in this distribution"
    -   Summary counters and progress bars exclude skipped documents
    -   "Select All as Verified" ignores skipped documents in both modals
        This is the core business logic - documents physically "move" between departments

UI Enhancements (2025-09-05):

-   Sender/Receiver Verification modals include a Type column (e.g., ITO, BAST, BAPP)
-   Out-of-origin attached additional documents display an "Out of origin dept" badge near the document name

5. Receiver Verification Stage
   Triggered by: POST /api/distributions/{id}/verify-receiver Fields Updated:

'status' => 'verified_by_receiver'
'receiver_verified_at' => now()
'receiver_verified_by' => Auth::id()
'receiver_verification_notes' => $verificationNotes
'has_discrepancies' => $hasDiscrepancies
Document-Level Updates:

// For each document in document_verifications array:
'receiver_verified' => true
'receiver_verification_status' => 'verified' | 'missing' | 'damaged'
'receiver_verification_notes' => $notes_per_document
Discrepancy Handling:

If any document marked as 'missing' or 'damaged', requires confirmation
force_complete_with_discrepancies parameter can override
Creates detailed discrepancy notifications
Separate history entries for each discrepancy Business Rules:
Only allowed if status is 'received'
Comprehensive verification with discrepancy management History Entry: 'verified_by_receiver' action + individual discrepancy entries 6. Completion Stage
Triggered by: POST /api/distributions/{id}/complete Fields Updated:

'status' => 'completed'
Business Rules:

Only allowed if status is 'verified_by_receiver'
Final stage - no further modifications allowed
Triggers completion notifications History Entry: 'completed' action logged
CRUD Operations Analysis
Create (POST /api/distributions)
Validation: Document location, department permissions
Auto-Processing: Related document inclusion, location filtering
Number Generation: Automatic distribution number (YY/DEPT/TYPE/SEQ)
Document Attachment: Polymorphic document associations
History: Initial creation logged
Read (GET /api/distributions, GET /api/distributions/{id})
Filtering: By status, department, user, date range, type
Department Scope: Users only see distributions involving their department
Relationships: Eager loading of all related entities
Pagination: Configurable page size
Update (PUT /api/distributions/{id})
Restriction: Only allowed in 'draft' status
Fields: Basic distribution metadata (type, destination, notes)
History: Update action logged
Delete (DELETE /api/distributions/{id})
Restriction: Only allowed in 'draft' status
Type: Soft deletion (deleted_at timestamp)
Cascade: Related document associations removed
Special Operations
Document Management
Attach Documents: POST /api/distributions/{id}/attach-documents
Detach Document: DELETE /api/distributions/{id}/detach-document/{type}/{id}
Location Validation: Documents must be in user's department location
Reporting and Queries
History: GET /api/distributions/{id}/history - Complete audit trail
Transmittal: GET /api/distributions/{id}/transmittal - Distribution report
Discrepancy Summary: GET /api/distributions/{id}/discrepancy-summary
Department Filter: GET /api/distributions/by-department/{id}
Status Filter: GET /api/distributions/by-status/{status}
User Filter: GET /api/distributions/by-user/{id}
Key Business Logic Features

1. Location-Based Document Management
   Documents have cur_loc field tracking physical location
   Distribution automatically updates document locations upon receipt
   Prevents distribution of documents not in user's location
2. Auto-Document Inclusion
   When distributing invoices, attached additional documents are automatically included
   Location validation prevents mismatched document inclusion
   Warnings generated for location conflicts
3. Granular Verification System
   Distribution-Level: Overall verification with timestamps and notes
   Document-Level: Individual document verification with status (verified/missing/damaged)
   Out-of-Origin Additional Documents: Verification inputs are disabled and labeled; they do not participate in status/location changes
   Discrepancy Management: Detailed tracking and notification system
4. Comprehensive Audit Trail
   Every action logged in distribution_histories table
   Includes user, action type, notes, and metadata
   Discrepancies get individual history entries
