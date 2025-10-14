# Document Distribution Status Implementation

## 📋 **Overview**

This document outlines the implementation of document distribution status tracking to prevent documents from being sent multiple times while they are "in transit" or already distributed.

## 🎯 **Problem Statement**

### **Before Implementation:**

-   ❌ **No document status tracking** when distributions are sent
-   ❌ **Documents could be selected for multiple distributions** simultaneously
-   ❌ **No way to know if documents are "on the way"** to another department
-   ❌ **Potential for duplicate distributions** of the same documents
-   ❌ **Missing/damaged documents created false audit trails** by appearing to be at destination

### **After Implementation:**

-   ✅ **Complete document status tracking** throughout distribution lifecycle
-   ✅ **Documents are automatically filtered** to prevent multiple distributions
-   ✅ **Clear visibility** of document distribution state
-   ✅ **Prevention of duplicate distributions**
-   ✅ **Accurate tracking of missing/damaged documents** with proper status and location preservation

## 🏗️ **Technical Implementation**

### **1. Database Schema Changes**

#### **New Migration: `2025_08_14_000000_add_distribution_status_to_documents.php`**

```php
// Add to invoices table
$table->enum('distribution_status', ['available', 'in_transit', 'distributed'])
    ->default('available')
    ->after('status');

// Add to additional_documents table
$table->enum('distribution_status', ['available', 'in_transit', 'distributed'])
    ->default('available')
    ->after('status');
```

#### **Enhanced Migration: `2025_08_21_082720_add_unaccounted_for_status_to_documents.php`**

```php
// Update invoices table to include 'unaccounted_for' status
$table->enum('distribution_status', ['available', 'in_transit', 'distributed', 'unaccounted_for'])
    ->default('available')
    ->change();

// Update additional_documents table to include 'unaccounted_for' status
$table->enum('distribution_status', ['available', 'in_transit', 'distributed', 'unaccounted_for'])
    ->default('available')
    ->change();
```

#### **Distribution Status Values:**

-   **`available`**: Document is ready to be included in a new distribution
-   **`in_transit`**: Document is currently being sent to another department
-   **`distributed`**: Document has been received and is at its final destination
-   **`unaccounted_for`**: Document was missing or damaged during distribution (CRITICAL: maintains original location)

### **2. Model Updates**

#### **Invoice Model (`app/Models/Invoice.php`)**

```php
protected $fillable = [
    // ... existing fields
    'distribution_status',
    // ... other fields
];

// New scopes
public function scopeAvailableForDistribution($query)
{
    return $query->where('distribution_status', 'available');
}

public function scopeInTransit($query)
{
    return $query->where('distribution_status', 'in_transit');
}

public function scopeDistributed($query)
{
    return $query->where('distribution_status', 'distributed');
}

public function scopeUnaccountedFor($query)
{
    return $query->where('distribution_status', 'unaccounted_for');
}
```

#### **AdditionalDocument Model (`app/Models/AdditionalDocument.php`)**

```php
protected $fillable = [
    // ... existing fields
    'distribution_status',
    // ... other fields
];

// Same scopes as Invoice model including unaccountedFor scope
```

### **3. Controller Updates**

#### **DistributionController (`app/Http/Controllers/DistributionController.php`)**

**Enhanced Method:**

```php
/**
 * Update document distribution statuses
 * Called when:
 * 1. Distribution is sent (status: in_transit)
 * 2. Distribution is received (status: distributed) - ONLY for verified documents
 * 3. Distribution is completed (status: distributed) - ONLY for verified documents
 *
 * CRITICAL: Missing/damaged documents are handled separately by handleMissingOrDamagedDocuments()
 */
private function updateDocumentDistributionStatuses(Distribution $distribution, string $status): void
{
    foreach ($distribution->documents as $distributionDocument) {
        if ($distributionDocument->document_type === Invoice::class) {
            if ($status === 'in_transit') {
                // ✅ When SENT: Update ALL documents to 'in_transit' (prevent selection in new distributions)
                Invoice::where('id', $distributionDocument->document_id)
                    ->update(['distribution_status' => $status]);

                // Also update status of any additional documents attached to this invoice
                $invoice = Invoice::find($distributionDocument->document_id);
                if ($invoice && $invoice->additionalDocuments()->count() > 0) {
                    $invoice->additionalDocuments()->update(['distribution_status' => $status]);
                }
            } elseif ($status === 'distributed') {
                // ✅ When RECEIVED: Only update documents that were actually verified
                if ($distributionDocument->receiver_verification_status === 'verified') {
                    Invoice::where('id', $distributionDocument->document_id)
                        ->update(['distribution_status' => $status]);

                    // Also update status of any additional documents attached to this invoice
                    $invoice = Invoice::find($distributionDocument->document_id);
                    if ($invoice && $invoice->additionalDocuments()->count() > 0) {
                        $invoice->additionalDocuments()->update(['distribution_status' => $status]);
                    }
                }
            }
        } elseif ($distributionDocument->document_type === AdditionalDocument::class) {
            if ($status === 'in_transit') {
                // ✅ When SENT: Update ALL documents to 'in_transit'
                AdditionalDocument::where('id', $distributionDocument->document_id)
                    ->update(['distribution_status' => $status]);
            } elseif ($status === 'distributed') {
                // ✅ When RECEIVED: Only update verified documents
                if ($distributionDocument->receiver_verification_status === 'verified') {
                    AdditionalDocument::where('id', $distributionDocument->document_id)
                        ->update(['distribution_status' => $status]);
                }
            }
        }
    }
}

/**
 * Handle missing or damaged documents by updating their status to reflect reality
 * This ensures that missing/damaged documents don't get false location or status updates
 */
private function handleMissingOrDamagedDocuments(Distribution $distribution, User $user): void
{
    foreach ($distribution->documents as $distributionDocument) {
        // Check if document was marked as missing or damaged by receiver
        if (in_array($distributionDocument->receiver_verification_status, ['missing', 'damaged'])) {

            // Update document distribution status to reflect reality
            if ($distributionDocument->document_type === Invoice::class) {
                Invoice::where('id', $distributionDocument->document_id)
                    ->update([
                        'distribution_status' => 'unaccounted_for',
                        // Keep original cur_loc - don't move missing documents!
                    ]);

                // Log the discrepancy for audit purposes
                DistributionHistory::logDiscrepancyReport(
                    $distribution,
                    $user,
                    $distributionDocument->document_type,
                    $distributionDocument->document_id,
                    $distributionDocument->receiver_verification_status,
                    $distributionDocument->receiver_verification_notes
                );
            } elseif ($distributionDocument->document_type === AdditionalDocument::class) {
                AdditionalDocument::where('id', $distributionDocument->document_id)
                    ->update([
                        'distribution_status' => 'unaccounted_for',
                        // Keep original cur_loc - don't move missing documents!
                    ]);

                // Log the discrepancy for audit purposes
                DistributionHistory::logDiscrepancyReport(
                    $distribution,
                    $user,
                    $distributionDocument->document_type,
                    $distributionDocument->document_id,
                    $distributionDocument->receiver_verification_status,
                    $distributionDocument->receiver_verification_notes
                );
            }
        }
    }
}

/**
 * Update document locations to destination department
 * Called when:
 * 1. Distribution is received (initial location update)
 * 2. Distribution is completed (final location confirmation)
 *
 * Note: When moving invoices, this also moves any additional documents
 * that are attached to those invoices.
 *
 * CRITICAL: Only documents verified as 'verified' by receiver get location updates.
 * Missing or damaged documents keep their original location to maintain data integrity.
 */
private function updateDocumentLocations(Distribution $distribution): void
{
    $destinationLocationCode = $distribution->destinationDepartment->location_code;

    foreach ($distribution->documents as $distributionDocument) {
        // ✅ CRITICAL FIX: Only update documents that were actually received
        if ($distributionDocument->receiver_verification_status === 'verified') {
            if ($distributionDocument->document_type === Invoice::class) {
                // Update invoice location
                Invoice::where('id', $distributionDocument->document_id)
                    ->update(['cur_loc' => $destinationLocationCode]);

                // Also update location of any additional documents attached to this invoice
                $invoice = Invoice::find($distributionDocument->document_id);
                if ($invoice && $invoice->additionalDocuments()->count() > 0) {
                    $invoice->additionalDocuments()->update(['cur_loc' => $destinationLocationCode]);
                }
            } elseif ($distributionDocument->document_type === AdditionalDocument::class) {
                AdditionalDocument::where('id', $distributionDocument->document_id)
                    ->update(['cur_loc' => $destinationLocationCode]);
            }
        }
        // ❌ Missing/damaged documents keep their original location
        // This prevents false audit trails and maintains data integrity
    }
}
```

**Updated Methods:**

```php
// In create() method - Show available and distributed documents (2025-10-14 Enhancement)
$invoices = Invoice::where('cur_loc', $user->department->location_code)
    ->availableForDistribution() // Now includes both 'available' and 'distributed' statuses
    ->get();

$additionalDocuments = AdditionalDocument::where('cur_loc', $user->department->location_code)
    ->availableForDistribution() // Now includes both 'available' and 'distributed' statuses
    ->get();

// In send() method - Mark documents as in_transit
$this->updateDocumentDistributionStatuses($distribution, 'in_transit');

// In receive() method - Mark documents as distributed AND handle missing/damaged
$this->updateDocumentDistributionStatuses($distribution, 'distributed');
$this->handleMissingOrDamagedDocuments($distribution, $user);
$this->updateDocumentLocations($distribution);

// In complete() method - Ensure documents are marked as distributed
$this->updateDocumentDistributionStatuses($distribution, 'distributed');
```

## 🔄 **Document Lifecycle Flow**

### **Status Transitions:**

```
Document Creation
       ↓
   [available] ← Document is ready for distribution
       ↓
Distribution Created & Sent
       ↓
   [in_transit] ← Document is on the way
       ↓
Distribution Received
       ↓
   [distributed] ← Document has reached destination (verified documents only)
       ↓
Distribution Completed
       ↓
   [distributed] ← Final confirmation (verified documents only)

   OR (for missing/damaged documents)
       ↓
   [unaccounted_for] ← Document missing/damaged, original location preserved
```

### **Distribution Workflow:**

| Distribution Status      | Document Status | What Happens                                    |
| ------------------------ | --------------- | ----------------------------------------------- |
| **Draft**                | `available`     | Documents can be selected for distribution      |
| **Verified by Sender**   | `available`     | Documents still available                       |
| **Sent**                 | `in_transit`    | ✅ **Documents marked as in transit**           |
| **Received**             | `distributed`   | ✅ **Verified documents marked as distributed** |
| **Verified by Receiver** | `distributed`   | Verified documents remain distributed           |
| **Completed**            | `distributed`   | ✅ **Final confirmation of distribution**       |

### **Missing/Damaged Document Workflow:**

| Receiver Verification Status | Document Status   | Location        | What Happens                              |
| ---------------------------- | ----------------- | --------------- | ----------------------------------------- |
| **missing**                  | `unaccounted_for` | **Original**    | ✅ **Status updated, location preserved** |
| **damaged**                  | `unaccounted_for` | **Original**    | ✅ **Status updated, location preserved** |
| **verified**                 | `distributed`     | **Destination** | ✅ **Status and location updated**        |

## 🛡️ **Security & Prevention Features**

### **1. Automatic Filtering**

-   **Create Distribution**: Shows documents with `distribution_status = 'available'` and `'distributed'` (2025-10-14 Enhancement)
-   **Prevents Selection**: Documents with `in_transit` or `unaccounted_for` status are automatically excluded
-   **Re-distribution**: Documents with `'distributed'` status can be selected for re-distribution (2025-10-14 Enhancement)

### **2. Status Enforcement**

-   **Sent**: Documents automatically become `in_transit`
-   **Received**: Only verified documents become `distributed`
-   **Missing/Damaged**: Documents become `unaccounted_for` with original location preserved
-   **Completed**: Only verified documents confirmed as `distributed`

### **3. Data Integrity Protection**

-   **Location Preservation**: Missing/damaged documents keep their original `cur_loc`
-   **Audit Trail Integrity**: No false location updates for unaccounted documents
-   **Compliance**: Accurate tracking reflects physical reality

### **4. Database Constraints**

-   **Enum Validation**: Only valid status values allowed
-   **Indexed Field**: Fast queries for status-based filtering
-   **Default Value**: New documents start as `available`

## 🧪 **Testing Scenarios**

### **Scenario 1: Prevent Multiple Distributions**

1. **Create Distribution A** with Document X → Document X status: `in_transit`
2. **Try to Create Distribution B** with Document X → Document X will NOT appear in selection
3. **Result**: ✅ Document X cannot be sent twice

### **Scenario 2: Document Status Tracking**

1. **Send Distribution** → Documents become `in_transit`
2. **Receive Distribution** → Verified documents become `distributed`
3. **Complete Distribution** → Verified documents remain `distributed`
4. **Result**: ✅ Complete audit trail of document movement

### **Scenario 3: Missing/Damaged Document Handling**

1. **Send Distribution** with Document Y → Document Y status: `in_transit`
2. **Receiver marks Document Y as missing** → Document Y status: `unaccounted_for`, location: original
3. **Distribution completed** → Document Y remains `unaccounted_for` at original location
4. **Result**: ✅ Accurate tracking of missing document without false location updates

### **Scenario 4: Available Documents Only**

1. **View Create Distribution Page** → Only shows documents with `available` status
2. **Documents in transit, distributed, or unaccounted** are automatically filtered out
3. **Result**: ✅ Users can only select documents ready for distribution

## 📊 **Database Queries**

### **Available Documents for Distribution:**

```sql
-- Invoices available for distribution
SELECT * FROM invoices
WHERE cur_loc = 'DEPARTMENT_CODE'
AND distribution_status = 'available';

-- Additional documents available for distribution
SELECT * FROM additional_documents
WHERE cur_loc = 'DEPARTMENT_CODE'
AND distribution_status = 'available';
```

### **Documents Currently In Transit:**

```sql
-- All documents currently being distributed
SELECT * FROM invoices WHERE distribution_status = 'in_transit'
UNION ALL
SELECT * FROM additional_documents WHERE distribution_status = 'in_transit';
```

### **Distributed Documents:**

```sql
-- All documents that have been distributed
SELECT * FROM invoices WHERE distribution_status = 'distributed'
UNION ALL
SELECT * FROM additional_documents WHERE distribution_status = 'distributed';
```

### **Unaccounted Documents:**

```sql
-- All missing or damaged documents
SELECT * FROM invoices WHERE distribution_status = 'unaccounted_for'
UNION ALL
SELECT * FROM additional_documents WHERE distribution_status = 'unaccounted_for';
```

## 🚀 **Benefits**

1. **Prevents Duplicate Distributions**: Documents cannot be sent multiple times
2. **Clear Status Tracking**: Always know where documents are in the distribution process
3. **Improved User Experience**: Users only see documents they can actually distribute
4. **Data Integrity**: Prevents data inconsistencies and duplicate records
5. **Audit Trail**: Complete tracking of document movement throughout the system
6. **Performance**: Indexed status field for fast filtering queries
7. **Compliance**: Accurate tracking of missing/damaged documents without false location updates
8. **Business Reality**: System status reflects physical document reality

## 🔄 **Migration Steps**

1. **Run Migration**: `php artisan migrate`
2. **Update Existing Data**: Set all existing documents to `available` status
3. **Test Functionality**: Verify document filtering works correctly
4. **Monitor Performance**: Ensure queries remain fast with new indexes

## 📝 **Future Enhancements**

1. **Status History**: Track all status changes with timestamps
2. **Notifications**: Alert users when documents become available again
3. **Bulk Status Updates**: Allow admins to reset document statuses if needed
4. **Status Reports**: Generate reports on document distribution statuses
5. **API Endpoints**: Expose status information via API for external systems
6. **Investigation Workflow**: Automated processes for unaccounted document resolution

---

**Last Updated**: 2025-01-27  
**Version**: 2.0  
**Status**: ✅ Complete Implementation with Missing/Damaged Document Handling
