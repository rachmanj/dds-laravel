# Invoice Additional Documents Auto-Inclusion in Distribution

> **Status update (2026-06-30)**: Silent auto-inclusion on **distribution create** and **edit attach** was removed. Additional documents are included only when the user confirms them at create (`linked_document_ids`) or via **Sync linked documents** on a draft show page. **`attachInvoiceAdditionalDocuments`** remains for that manual sync path. See [`docs/decisions.md`](decisions.md) (2026-06-30) and [`MEMORY.md`](../MEMORY.md).

## 📋 **Overview**

This document describes how additional documents linked to invoices are handled in distributions: explicit user confirmation at create, manual sync on draft show, and status/location synchronization for documents actually attached to the distribution.

## 🎯 **Problem Statement**

### **Before Implementation:**

-   ❌ **Invoices could be distributed without their attached additional documents**
-   ❌ **Additional documents remained at origin location** when invoices were moved
-   ❌ **No automatic status synchronization** between invoices and their supporting documents
-   ❌ **Manual tracking required** to ensure complete document sets were distributed together

### **After Implementation (historical → current):**

-   ✅ **Invoice-attached additional documents** can be bundled with invoice distributions
-   ✅ **Complete status synchronization** for documents in `distribution_documents`
-   ✅ **Automatic location updates** for attached invoices and additional documents
-   ✅ **User-controlled inclusion (2026-06-30)** — confirmation dialog + linked-doc management at create; no PO-only false positives

## 🏗️ **Technical Implementation**

### **1. Document Attachment**

#### **Method: `attachInvoiceAdditionalDocuments()`**

Used by **`syncLinkedDocuments`** (draft show page only). Attaches additional documents from **`additional_document_invoice`** for invoices already on the distribution.

```php
private function attachInvoiceAdditionalDocuments(Distribution $distribution, array $invoiceIds): void
{
    foreach ($invoiceIds as $invoiceId) {
        $invoice = Invoice::find($invoiceId);
        if ($invoice && $invoice->additionalDocuments()->count() > 0) {
            foreach ($invoice->additionalDocuments as $additionalDocument) {
                $existingAttachment = DistributionDocument::where('distribution_id', $distribution->id)
                    ->where('document_type', AdditionalDocument::class)
                    ->where('document_id', $additionalDocument->id)
                    ->first();

                if (! $existingAttachment) {
                    DistributionDocument::create([
                        'distribution_id' => $distribution->id,
                        'document_type' => AdditionalDocument::class,
                        'document_id' => $additionalDocument->id,
                        'origin_cur_loc' => $additionalDocument->cur_loc,
                        'skip_verification' => ($additionalDocument->cur_loc !== $originLocationCode),
                    ]);
                }
            }
        }
    }
}
```

#### **Create flow (current — 2026-06-30):**

```php
$this->attachDocuments($distribution, $request->document_type, $request->document_ids);

if ($request->linked_document_ids) {
    $linkedDocumentIds = array_filter(explode(',', $request->linked_document_ids));
    if (! empty($linkedDocumentIds)) {
        $this->attachDocuments($distribution, 'additional_document', $linkedDocumentIds);
    }
}
// attachInvoiceAdditionalDocuments is NOT called here
```

#### **`checkLinkedDocuments` (create confirmation):**

Returns pivot-linked additional documents for selected invoice IDs (same relationship as above), filtered by department and `availableForDistribution()`.

### **2. Enhanced Status Synchronization**

Status and location updates apply to rows in **`distribution_documents`** (and pivot-linked additional docs on send/receive per existing rules). See controller methods `updateDocumentDistributionStatuses()` and related helpers.

## 🔄 **Complete Workflow Flow**

### **Distribution Creation (current):**

```
User selects invoices for distribution
       ↓
Confirmation dialog loads invoice-attached additional documents (checkLinkedDocuments)
       ↓
User reviews / Manage Linked Documents → linked_document_ids
       ↓
Distribution created with selected invoices + confirmed additional documents only
```

### **Draft maintenance:**

```
User opens draft show page
       ↓
Optional: Sync linked documents → attachInvoiceAdditionalDocuments
       ↓
Edit page lists all distribution_documents (including Other Additional Documents)
       ↓
User can remove unwanted rows individually
```

## 🧪 **Testing Scenarios**

### **Scenario 1: Invoice with attached documents (create)**

1. Link additional documents to invoice via invoice create/edit (pivot table)
2. Create distribution selecting that invoice
3. Confirmation shows linked additional documents
4. User confirms → distribution includes invoice + selected additional documents only

### **Scenario 2: PO-only documents must not appear**

1. Additional document shares PO with invoice but is **not** in `additional_document_invoice`
2. Create distribution with that invoice
3. **Result**: PO-only document **not** listed in linked documents or attached

### **Scenario 3: Legacy PO-only rows on edit**

1. Draft distribution contains PO-only additional documents from before 2026-06-30
2. Edit page shows them under **Other Additional Documents**
3. User removes via icon remove button

## 📊 **Automated tests**

-   `tests/Feature/DistributionCheckLinkedDocumentsTest.php`
-   `tests/Feature/DistributionEditDocumentsTest.php`

---

**Last Updated**: 2026-06-30  
**Version**: 1.1  
**Status**: ✅ Current behavior documented
