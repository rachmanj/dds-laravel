# DDS Laravel Architecture Documentation

## 🏗️ **System Overview**

The DDS (Document Distribution System) is a comprehensive Laravel 11+ application designed for managing document workflows across multiple departments. The system handles invoices, additional documents, and their distribution through a secure, role-based workflow.

## 🔄 **Core Workflows**

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

### **Document Status Management**

**System Architecture**:

The Document Status Management system provides comprehensive control over document distribution statuses, allowing administrators to reset and manage document states for workflow continuity.

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
