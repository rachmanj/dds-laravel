# DDS Laravel Architecture Documentation

## 🏗️ **System Overview**

The DDS (Document Distribution System) is a comprehensive Laravel 11+ application designed for managing document workflows across multiple departments. The system handles invoices, additional documents, and their distribution through a secure, role-based workflow.

## 🔄 **Core Workflows**

### **External API System**

The system provides secure external API access for invoice data with comprehensive security and user accountability:

**API Architecture**:

-   **Authentication**: API key-based authentication using `DDS_API_KEY` environment variable
-   **Rate Limiting**: Multi-tier rate limiting (hourly: 100, minute: 20, daily: 1000 requests)
-   **Security Middleware**: Custom `ApiKeyMiddleware` and `ApiRateLimitMiddleware` for enterprise-grade security
-   **Data Access**: Read-only access to invoice data with complete user accountability

**API Endpoints**:

-   **General Invoices**: `GET /api/v1/departments/{location_code}/invoices` - All invoices with comprehensive data
-   **Wait-Payment Invoices**: `GET /api/v1/departments/{location_code}/wait-payment-invoices` - Invoices pending payment
-   **Paid Invoices**: `GET /api/v1/departments/{location_code}/paid-invoices` - Invoices with completed payments
-   **Payment Updates**: `PUT /api/v1/invoices/{invoice_id}/payment` - Update payment information
-   **Department Reference**: `GET /api/v1/departments` - Available departments for API consumers

**Data Structure**:

-   **Invoice Fields**: Complete invoice data including `paid_by` field for user accountability
-   **Additional Documents**: Nested document information with proper relationships
-   **Distribution Data**: Latest distribution information to requested department
-   **User Accountability**: `paid_by` field shows user who processed payment
-   **Enhanced Filtering**: Project, supplier, date range, and status filtering capabilities

**Security Features**:

-   **API Key Validation**: Secure header-based authentication
-   **Rate Limiting**: Prevents API abuse with configurable limits
-   **Audit Logging**: Complete request logging with IP tracking and timestamps
-   **Error Handling**: Comprehensive error responses with proper HTTP status codes

### **Distribution Workflow**

```
Draft → Verified by Sender → Sent → Received → Verified by Receiver → Completed
  ↓           ↓              ↓        ↓           ↓                    ↓
Create    Sender Verifies  Send to   Receive    Receiver Verifies   Complete
Distribution  Documents   Destination  Documents   Documents        Distribution
```

**Critical Status Management**:

-   **Document Protection**: Documents become `'in_transit'` when distribution is sent, preventing selection in new distributions
-   **Status Isolation**: Documents in transit are completely isolated from new distribution selection
-   **Workflow Integrity**: Complete audit trail of document movement through distribution lifecycle

**Status Transitions**:

1. **Available** (`distribution_status = 'available'`) → Can be selected for distribution
2. **In Transit** (`distribution_status = 'in_transit'`) → Cannot be selected for new distributions ✅
3. **Distributed** (`distribution_status = 'distributed'`) → Cannot be selected for new distributions ✅
4. **Unaccounted For** (`distribution_status = 'unaccounted_for'`) → Cannot be selected for new distributions ✅

### **On-the-Fly Document Creation Workflow**

**Overview**: Real-time additional document creation within invoice workflows

```
Invoice Create/Edit → Click "Create New Document" → Modal Form → Submit → Auto-Select → Continue Invoice
        ↓                        ↓                    ↓           ↓            ↓              ↓
    Permission Check      Open Bootstrap Modal    Fill Form    AJAX Submit   Add to Selection   Attach to Invoice
```

**Architecture Components**:

-   **Frontend**: Bootstrap modal with comprehensive form validation
-   **Backend**: Dedicated route and controller method with permission validation
-   **Integration**: Seamless embedding in invoice create/edit workflows
-   **UX**: Real-time updates without page refreshes

**Key Technical Decisions**:

1. **Modal Placement**: Positioned outside main form to prevent nested form HTML issues
2. **Permission System**: Both backend (`Auth::user()->can()`) and frontend conditional rendering
3. **Auto-Population**: PO number and location defaults for improved UX
4. **Real-time Integration**: AJAX submission with immediate UI updates

**Data Flow**:

```php
// Frontend: Modal form submission
$.ajax({
    url: '/additional-documents/on-the-fly',
    data: formData,
    success: function(response) {
        // Auto-select created document
        selectedDocs[newDoc.id] = newDoc;
        // Refresh table and update UI
        searchAdditionalDocuments();
        renderSelectedTable();
    }
});

// Backend: Document creation with validation
public function createOnTheFly(Request $request) {
    // Permission check
    if (!Auth::user()->can('on-the-fly-addoc-feature')) {
        return response()->json(['success' => false, 'message' => 'Unauthorized']);
    }

    // Validation and creation
    $validated = $request->validate([...]);
    $document = AdditionalDocument::create([...]);

    return response()->json(['success' => true, 'document' => $document]);
}
```

**Permission Integration**:

-   **Role-Based Access**: `on-the-fly-addoc-feature` permission required
-   **Assigned Roles**: admin, superadmin, logistic, accounting, finance
-   **Frontend Rendering**: Conditional button display based on permissions
-   **Backend Validation**: Server-side permission verification
-   **Permission System**: Uses `$user->can('permission-name')` instead of hardcoded role checks
-   **Cache Management**: Permission cache cleared after changes to ensure immediate effect

### **Invoice Payment Management System**

**System Architecture**:

The Invoice Payment Management System provides comprehensive tracking and management of invoice payment statuses across departments, with days calculation and overdue alerts for workflow optimization.

**Core Components**:

1. **Database Schema**:

    ```sql
    ALTER TABLE invoices ADD COLUMN payment_status ENUM('pending', 'paid') DEFAULT 'pending';
    ALTER TABLE invoices ADD COLUMN paid_by BIGINT UNSIGNED NULL REFERENCES users(id);
    ALTER TABLE invoices ADD COLUMN paid_at TIMESTAMP NULL;
    ```

2. **Permission System**:

    - `view-invoice-payment`: Access to payment dashboard and lists
    - `update-invoice-payment`: Ability to update payment statuses
    - Role assignments: admin, superadmin, accounting, finance

3. **Controller Architecture**:
    ```php
    class InvoicePaymentController extends Controller
    {
        public function dashboard()           // Payment metrics and overview
        public function waitingPayment()      // Invoices for payment management
        public function paidInvoices()        // Historical payment records
        public function updatePayment()       // Individual status updates
        public function updatePaidInvoice()   // Update paid invoice details
        public function bulkUpdatePayment()   // Batch status updates
    }
    ```

**Days Calculation System**:

```php
public function getDaysSinceReceivedAttribute()
{
    // Use receive_date as primary, fallback to created_at
    $dateToUse = $this->receive_date ?: $this->created_at;

    if (!$dateToUse) {
        return null;
    }

    // Calculate days and ensure whole numbers
    $days = $dateToUse->diffInDays(now());
    return (int) round($days);
}
```

**User Interface Architecture**:

-   **Three-Tab System**: Dashboard, Waiting Payment, Paid Invoices
-   **Responsive Design**: AdminLTE integration with mobile-friendly layout
-   **Real-time Updates**: AJAX-based operations with immediate feedback
-   **Bulk Operations**: Checkbox selection with select-all functionality

**Table Structure & Data Display**:

-   **Invoice Project Column**: Added after Amount column for better categorization
-   **Enhanced Supplier Display**: Shows supplier name + SAP code instead of department location
-   **Clean Amount Display**: Removed duplicate currency since it's already shown as prefix
-   **Information Hierarchy**: Logical column placement improves user experience and readability
-   **Visual Indicators**: Project codes displayed as blue badges, status badges for workflow states

**Data Flow**:

```
User Action → Frontend Validation → AJAX Request → Backend Validation → Database Update → Response → UI Refresh
     ↓              ↓                    ↓              ↓                ↓              ↓          ↓
Select Invoices → Check Required → Send Form Data → Validate Fields → Update Records → Success/Error → Show Result
```

**Paid Invoice Update Capabilities**:

-   **Update Payment Details**: Modify payment dates and remarks for paid invoices
-   **Revert to Pending**: Change paid invoices back to pending payment status
-   **Comprehensive Management**: Single interface for all payment operations
-   **Audit Trail**: Complete tracking of payment status changes and reversals

**Security & Access Control**:

-   **Department Isolation**: Users can only update invoices in their department
-   **Permission Validation**: Middleware-based access control
-   **Input Validation**: Comprehensive frontend and backend validation
-   **Audit Trail**: Complete tracking of payment status changes

**Configuration Management**:

```php
// config/invoice.php
return [
    'payment_overdue_days' => env('INVOICE_PAYMENT_OVERDUE_DAYS', 30),
    'default_payment_date' => now()->format('Y-m-d'),
    'payment_statuses' => ['pending', 'paid'],
    'statuses' => ['open', 'verify', 'return', 'sap', 'close', 'cancel'],
];
```

### **Document Status Management**

**System Architecture**:

The Document Status Management system provides comprehensive control over document distribution statuses, allowing administrators to reset and manage document states for workflow continuity.

### **File Upload System**

**System Architecture**:

The File Upload System provides comprehensive file handling capabilities across all document types, supporting large file uploads up to 50MB with consistent validation and user experience.

**File Size Limits & Validation**:

```
Current System Limits:
├── Invoice Attachments: 50MB (max:51200)
├── Additional Document Attachments: 50MB (max:51200)
├── Excel Import Files: 50MB (max:51200)
└── All File Types: PDF, Images, Excel, Word documents
```

**Validation Architecture**:

**Backend Validation (Laravel)**:

```php
// Invoice Attachments
'files.*' => ['required', 'file', 'max:51200', 'mimes:pdf,jpg,jpeg,png,gif,webp']

// Excel Imports
'file' => 'required|file|mimes:xlsx,xls|max:51200'

// Document Attachments
'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:51200'
```

**Frontend Validation (JavaScript)**:

```javascript
// Client-side file size validation
var maxPerFile = 50 * 1024 * 1024; // 50MB
var maxSize = 50 * 1024 * 1024; // 50MB

// Real-time validation on file selection
$("#files").on("change", function () {
    var files = this.files;
    for (var i = 0; i < files.length; i++) {
        if (files[i].size > maxPerFile) {
            alert("Each file must be 50MB or less.");
            $(this).val("");
            break;
        }
    }
});
```

**File Storage Architecture**:

**Storage Strategy**:

-   **Local Storage**: Files stored in `storage/app/private/` for security
-   **Organized Structure**: Year/month-based folder organization
-   **Unique Naming**: Random 40-character filenames with original extensions
-   **Metadata Storage**: File information stored in database with relationships

**File Processing Flow**:

```
File Upload → Validation → Storage → Database Record → User Feedback
     ↓            ↓         ↓          ↓              ↓
  Form Submit  Size/Type  Local Disk  Attachment    Success/Error
               Check      Storage     Model         Message
```

**Controllers & Routes**:

**Primary Controllers**:

1. **InvoiceAttachmentController**: Handles invoice file attachments
2. **AdditionalDocumentController**: Manages document attachments and Excel imports
3. **InvoiceController**: Processes bulk invoice Excel imports

**Key Routes**:

-   `POST /invoices/{invoice}/attachments` - Upload invoice attachments
-   `POST /additional-documents/import` - Import Excel files
-   `POST /invoices/import` - Bulk invoice import

**User Experience Features**:

**Consistent Interface**:

-   **Help Text**: Clear file size limits displayed on all upload forms
-   **Real-time Validation**: Immediate feedback on file selection
-   **Error Handling**: User-friendly error messages for validation failures
-   **Progress Feedback**: Loading states and success notifications

**File Type Support**:

-   **Documents**: PDF, DOC, DOCX
-   **Images**: JPG, JPEG, PNG, GIF, WebP
-   **Spreadsheets**: XLS, XLSX
-   **Validation**: MIME type checking for security

**Performance & Security**:

**Performance Optimizations**:

-   **Efficient Validation**: Single-pass validation with clear error messages
-   **Storage Optimization**: Organized folder structure for easy management
-   **Memory Management**: Laravel's built-in file handling for large files
-   **Database Efficiency**: Optimized queries for file metadata

**Security Features**:

-   **File Type Validation**: Strict MIME type checking
-   **Size Limits**: Server-side validation prevents abuse
-   **Permission Control**: Role-based access to upload functionality
-   **Secure Storage**: Files stored outside web-accessible directories
-   **Unique Naming**: Prevents filename conflicts and security issues

**Business Impact**:

**Immediate Benefits**:

-   **Larger Documents**: Support for comprehensive business documents up to 50MB
-   **Bulk Operations**: Enhanced Excel import capabilities for large datasets
-   **User Productivity**: Reduced need to split or compress large files
-   **Process Efficiency**: Streamlined document upload workflows

**Long-term Benefits**:

-   **System Scalability**: Support for growing document size requirements
-   **User Adoption**: Better experience leads to increased system usage
-   **Business Process**: Improved support for real-world document sizes
-   **Data Integrity**: Complete documents uploaded without compression

**Controller Architecture**:

```php
class DocumentStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:reset-document-status');
    }

    // Main listing with filtering and search
    public function index(Request $request)

    // Individual status reset with full flexibility
    public function resetStatus(Request $request): JsonResponse

    // Bulk reset (limited to unaccounted_for → available)
    public function bulkResetStatus(Request $request): JsonResponse
}
```

**Permission System**:

-   **Permission**: `reset-document-status` required for all operations
-   **Role Assignment**: Limited to admin and superadmin roles
-   **Access Control**: Middleware-based protection at route level
-   **Frontend Control**: Conditional rendering using `@can('reset-document-status')`

**Data Relationships**:

```php
// Invoice eager loading
->with(['supplier', 'invoiceProjectInfo', 'creator.department'])

// Additional Document eager loading
->with(['type', 'creator.department'])

// Project relationship access
$invoice->invoiceProjectInfo->code ?? $invoice->invoice_project ?? 'N/A'
```

**Status Management Logic**:

1. **Individual Operations**: Full status flexibility (any → any)
2. **Bulk Operations**: Safety-restricted (`unaccounted_for` → `available` only)
3. **Department Filtering**: Non-admin users see only their department documents
4. **Audit Logging**: Complete tracking via DistributionHistory model

**Audit Trail Integration**:

```php
DistributionHistory::create([
    'distribution_id' => null, // Not tied to specific distribution
    'user_id' => $user->id,
    'action' => 'status_reset',
    'metadata' => [
        'document_type' => get_class($document),
        'document_id' => $document->id,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'reason' => $reason,
        'operation_type' => $operationType,
        'timestamp' => now()->toISOString()
    ],
    'action_performed_at' => now()
]);
```

**Critical Implementation**:

```php
private function updateDocumentDistributionStatuses(Distribution $distribution, string $status): void
{
    foreach ($distribution->documents as $distributionDocument) {
        if ($distributionDocument->document_type === Invoice::class) {
            if ($status === 'in_transit') {
                // ✅ When SENT: Update ALL documents to 'in_transit' (prevent selection in new distributions)
                Invoice::where('id', $distributionDocument->document_id)
                    ->update(['distribution_status' => $status]);

                // Also update attached additional documents
                $invoice->additionalDocuments()->update(['distribution_status' => $status]);
            } elseif ($status === 'distributed') {
                // ✅ When RECEIVED: Only update verified documents
                if ($distributionDocument->receiver_verification_status === 'verified') {
                    // Update status...
                }
            }
        }
        // Similar logic for AdditionalDocument...
    }
}
```

**Business Logic Flow**:

-   **When SENT**: ALL documents become `'in_transit'` (preventing selection in new distributions)
-   **When RECEIVED**: Only `'verified'` documents become `'distributed'`
-   **Missing/Damaged**: Keep original status for audit trail integrity

**Layout Architecture**:

-   **View Extension**: Uses `layouts.main` for consistent application structure
-   **Section Organization**: Proper `@section('title_page')`, `@section('breadcrumb_title')`, `@section('content')`
-   **Content Structure**: `<section class="content">` with `<div class="container-fluid">` wrapper
-   **Script Organization**: JavaScript organized in `@section('scripts')` with proper DataTables integration

**Database Architecture**:

-   **Migration Strategy**: Created migration to make `distribution_id` nullable in `distribution_histories` table
-   **Constraint Management**: Proper foreign key constraints with nullable support for standalone operations
-   **Audit Trail**: Complete status change tracking with required fields (`action`, `action_type`, `metadata`)

**Audit Logging Architecture**:

```php
// Complete audit trail creation
DistributionHistory::create([
    'distribution_id' => null, // Nullable for standalone operations
    'user_id' => $user->id,
    'action' => 'status_reset',
    'action_type' => 'status_management', // Required field for categorization
    'metadata' => [
        'document_type' => get_class($document),
        'document_id' => $document->id,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'reason' => $reason,
        'operation_type' => $operationType,
        'timestamp' => now()->toISOString()
    ],
    'action_performed_at' => now()
]);
```

## 🔐 **Security & Permissions**

### **Role-Based Access Control**

**Permission Checking Pattern**:

```php
// Consistent pattern used throughout the codebase
if (!array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
    // Regular user restrictions
    $query->where('destination_department_id', $user->department->id)
          ->where('status', 'sent');
}
```

**Benefits**:

-   **Performance**: Uses PHP array operations instead of method calls
-   **Consistency**: Same pattern across all permission checks
-   **Maintainability**: Easy to understand and modify permission logic

### **Department Isolation**

**Access Control**:

-   **Regular Users**: Can only see distributions related to their department
-   **Admin/Superadmin**: Can access all distributions across departments
-   **Data Filtering**: All queries respect user's department location

## 🗄️ **Database Architecture**

### **Core Tables**

**Distributions**:

-   `distributions`: Main distribution records with workflow status
-   `distribution_documents`: Polymorphic pivot table linking documents to distributions
-   `distribution_histories`: Complete audit trail of all workflow actions

**Documents**:

-   `invoices`: Financial documents with supplier and project information
-   `additional_documents`: Supporting documents linked to invoices
-   `distribution_status` enum: `available`, `in_transit`, `distributed`, `unaccounted_for`

**Document Status Management**:

-   `DocumentStatusController`: Admin interface for status management and reset operations
-   `reset-document-status` permission: Controls access to status management features
-   `DistributionHistory`: Audit trail for all status changes with reason tracking
-   **View Architecture**: Proper layout structure with `layouts.main` extension and section organization

**Users & Departments**:

-   `users`: System users with role assignments
-   `departments`: Department locations with location codes
-   `roles`: User roles (superadmin, admin, user)

### **Key Relationships**

**Polymorphic Document Linking**:

```php
// Distribution can contain both invoices and additional documents
$distribution->documents() // Returns DistributionDocument collection
$distribution->invoices() // Returns Invoice collection
$distribution->additionalDocuments() // Returns AdditionalDocument collection
```

**Document Status Synchronization**:

-   **Primary Documents**: Invoices and additional documents maintain `distribution_status`
-   **Related Documents**: Additional documents attached to invoices are automatically synchronized
-   **Status Isolation**: Documents in transit cannot be selected for new distributions

## 🚀 **Performance Optimizations**

### **Query Optimization**

**Eager Loading**:

```php
// Prevent N+1 queries in distribution views
$distribution->load(['documents.document', 'originDepartment', 'destinationDepartment']);
```

**Scope Usage**:

```php
// Efficient filtering for available documents
Invoice::availableForDistribution()->get();
AdditionalDocument::availableForDistribution()->get();
```

### **Permission Checking**

**Array Operations**:

```php
// Fast permission validation using PHP array operations
array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])
```

## 🔧 **Technical Implementation**

### **Controller Architecture**

**DistributionController**:

-   **Workflow Methods**: `send()`, `receive()`, `complete()` with proper status management
-   **Document Management**: Automatic status updates and location tracking
-   **Error Handling**: Comprehensive error handling with database transactions
-   **Audit Logging**: Complete workflow history tracking

**Key Methods**:

-   `updateDocumentDistributionStatuses()`: Conditional status updates based on workflow stage
-   `updateDocumentLocations()`: Location updates for verified documents only
-   `handleMissingOrDamagedDocuments()`: Proper handling of discrepancies

**DocumentStatusController**:

-   **Status Management**: Individual and bulk status reset operations
-   **Permission Control**: Middleware-based access control for admin operations
-   **Audit Logging**: Comprehensive logging of all status changes with reasons
-   **Bulk Operations**: Safe bulk processing with status transition restrictions

**Key Methods**:

-   `resetStatus()`: Individual document status reset with full flexibility
-   `bulkResetStatus()`: Bulk reset limited to `unaccounted_for` → `available`
-   `logStatusChange()`: Detailed audit logging for compliance purposes

**View Architecture**:

-   **Layout Extension**: All views extend `layouts.main` for consistency
-   **Section Organization**: Proper use of `title_page`, `breadcrumb_title`, `content`, `styles`, and `scripts`
-   **Content Structure**: `<section class="content">` with `<div class="container-fluid">` pattern
-   **DataTables Integration**: Proper table IDs and script organization for enhanced functionality

### **Model Relationships**

**Distribution Model**:

```php
// Workflow state management
public function canSend(): bool
public function canReceive(): bool
public function canVerifyByReceiver(): bool

// Status transition methods
public function markAsSent(): bool
public function markAsReceived(): bool
public function markAsVerifiedByReceiver(): bool
```

**Document Models**:

```php
// Status scopes for filtering
public function scopeAvailableForDistribution($query)
public function scopeInTransit($query)
public function scopeDistributed($query)
public function scopeUnaccountedFor($query)
```

## 📊 **Data Integrity & Validation**

### **Business Rule Enforcement**

**Document Protection**:

-   **Single Distribution**: Documents cannot be in multiple distributions simultaneously
-   **Status Validation**: All status transitions follow business workflow rules
-   **Location Accuracy**: Physical document location always matches system records

**Audit Trail**:

-   **Complete History**: Every workflow action is logged with user and timestamp
-   **Status Changes**: All document status changes are tracked
-   **Discrepancy Reporting**: Missing/damaged documents are properly logged

### **Error Prevention**

**Frontend Protection**:

-   **Form Validation**: Required fields and business rule validation
-   **Submission Guards**: Prevention of multiple AJAX requests
-   **User Feedback**: Clear error messages and validation feedback

**Backend Protection**:

-   **Database Transactions**: Atomic operations for data consistency
-   **Status Validation**: Workflow state validation before actions
-   **Permission Checking**: Role and department-based access control

## 🔮 **Future Enhancements**

### **Scalability Considerations**

**Performance**:

-   **Caching Strategy**: Redis caching for frequently accessed data
-   **Database Optimization**: Query optimization and indexing strategies
-   **Load Balancing**: Horizontal scaling for high-volume usage

**Features**:

-   **Real-time Updates**: WebSocket integration for live workflow updates
-   **Advanced Analytics**: Business intelligence and predictive insights
-   **Mobile Integration**: Native mobile experience for field operations

---

**Last Updated**: 2025-01-27  
**Version**: 4.3  
**Status**: ✅ **Production Ready** - Critical fixes implemented, comprehensive workflow protection & layout issues resolved
