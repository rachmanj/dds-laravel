# SAP AP Invoice Preview — GRPO mapping bug reference

**Purpose**: Machine-readable description of two captured UI states on the SAP AP Invoice Preview page. Use this file as the source of truth when diagnosing or fixing the GRPO link / amount-mismatch behaviour.  
**Screen**: `GET /invoices/{invoice}/sap-preview` → view `resources/views/invoices/sap-preview.blade.php`  
**Controller**: `InvoiceController::previewSapSubmission`  
**Payload**: `App\Services\SapApInvoicePayloadBuilder`  
**Date captured**: 2026-08-14  
**Screenshots**: [`docs/images/sap-ap-invoice-preview/`](images/sap-ap-invoice-preview/)

This is **not** a consignment invoice. Mode badge is `GRPO-based (BaseType 20)` because `po_no` is set and `Invoice::isConsignment()` is false.

---

## 1. Invoice under test (same on both screenshots)

| UI label | Value | Source field |
| --- | --- | --- |
| Invoice No | `71260800624` | `invoices.invoice_number` |
| Supplier | `CAHAYA SARANGE JAYA, PT (VCASJIDR01)` | `suppliers.name` + `suppliers.sap_code` |
| Date | `07-Aug-2026` | `invoices.invoice_date` |
| Posting Date | `2026-08-12` | `invoices.receive_date` → SAP `DocDate` |
| Document Date | `2026-08-07` | `invoices.invoice_date` → SAP `TaxDate` |
| Faktur No | `04002600310831070` | `invoices.faktur_no` → SAP `U_MIS_FPNum` |
| Faktur Date | `2026-08-07` | same as invoice date → SAP `U_MIS_FPDate` |
| Amount | **IDR 10,389,600.00** | `invoices.amount` — **tax-inclusive** |
| PO No | `260204302` | `invoices.po_no` → SAP `Reference1` |
| Tax Code | `VAT11` | `SapApInvoicePayloadBuilder::determineTaxCode()` (PPN 11%) |
| Mode | `GRPO-based (BaseType 20)` | shown when `po_no` is non-empty and invoice is not consignment |
| Project (on lines) | `APS` | SAP `ProjectCode` |
| Cost Center (on lines) | `40` | SAP `CostingCode` |

`Submitted By` differs between screenshots (`Omanof Sullivan` vs `Prana Dian`). That is the logged-in / `sapSubmitter` name, not part of the GRPO bug.

---

## 2. Page layout (what each panel means)

```
[ Invoice Summary (left, read-only) ]
[ GRPO Lines / SAP Relationship Map (right top, editable form) ]
[ AP Invoice Lines Preview (right bottom, read-only payload preview) ]
[ Confirm & Submit to SAP B1 | Cancel ]
```

| Panel | Role | Important |
| --- | --- | --- |
| Invoice Summary | Header that will be posted | Amount here is DDS header = tax-inclusive |
| GRPO Lines | User-editable `grpo_references[]` posted on submit | Qty × Unit Price are **SAP GRPO prices (pre-tax)** |
| AP Invoice Lines Preview | Output of `SapApInvoicePayloadBuilder::mapLineItems()` | Built only from **Found** GRPO rows. If none found, it falls back to a single `SERVICE` line |
| Amount footer | JS `recalcSum()` | Compares GRPO qty×price sum to `invoice.amount` with tolerance `0.01`. Warning text: `(amounts differ)` |

SAP B1 meaning of a valid GRPO row:

- `BaseType` = `20` (Purchase Delivery Notes / GRPO)
- `BaseEntry` = GRPO `DocEntry`
- `BaseLine` = GRPO `LineNum`

`GRPO No` on the table is SAP `DocNum` (user-facing). `DocEntry` is the internal key that actually links the AP Invoice.

---

## 3. State A — GRPO not resolved (broken preview)

Screenshot: [`01-grpo-not-found-standalone-preview.png`](images/sap-ap-invoice-preview/01-grpo-not-found-standalone-preview.png)

Mode still says **GRPO-based**, but the relationship map did not resolve a GRPO for PO `260204302`.

### GRPO Lines table (1 row)

| GRPO No | DocEntry | Line | Item | Qty | Unit Price | Line Total | Status |
| --- | --- | ---: | --- | ---: | ---: | ---: | --- |
| *(empty)* | *(empty, placeholder Resolve)* | 0 | *(empty)* | 0.0000 | 0.00 | 0.00 | **Not found** (red) |

Footer:

```
Invoice total: 10,389,600.00 — GRPO sum: 0.00 (amounts differ)
```

How this row is produced: `InvoiceController::resolveGrpoLinesForPreview()`:

- `sapService->getGrposByPoNumber('260204302')` returned empty, **or** threw, **or** returned a GRPO with no open lines.
- Empty/error path pushes one row with `found = false`, `error` set (tooltip on the red badge), qty/price 0.

### AP Invoice Lines Preview (fallback)

| Item | Qty | Unit Price | Project | Cost Center | GRPO Link |
| --- | ---: | ---: | --- | --- | --- |
| `SERVICE` | 1 | 10,389,600.00 | APS | 40 | **Standalone** |

This is **not** a standalone invoice. `po_no` is set. The preview still looks standalone because:

1. Preview builder is constructed only with **found** rows (`array_filter($grpoRows, fn ($row) => $row['found'])`).
2. Found list is empty.
3. `mapLineItems()` therefore takes the non-GRPO, non-consignment fallback:

```php
ItemCode = SERVICE (config default_item_code)
Quantity = 1
UnitPrice = $invoice->amount   // 10,389,600.00 tax-inclusive
// no BaseType / BaseEntry / BaseLine
```

### Submit behaviour in this state

Client JS **blocks** submit:

> At least one GRPO line with a valid DocEntry is required when PO number is set.

Server `submitToSap` has the same rule. State A cannot be posted as-is. The bug to investigate here is **why GRPO lookup by PO `260204302` failed**, not the SERVICE line itself (that is a preview fallback).

---

## 4. State B — GRPO found, amounts still differ

Screenshot: [`02-grpo-found-amount-mismatch.png`](images/sap-ap-invoice-preview/02-grpo-found-amount-mismatch.png)

Lookup succeeded. All three open GRPO lines are from **one** GRPO.

| Field | Value |
| --- | --- |
| GRPO No (`DocNum`) | `2605` |
| DocEntry (`BaseEntry`) | `22904` |
| Status | **Found** (green) on every row |
| PO used for lookup | `260204302` (invoice PO, **not** equal to GRPO DocNum) |

### GRPO Lines table (editable map)

Reconstructed so qty × unit price = line total. SAP `LineNum` is 0-based.

| GRPO No | DocEntry | Line (`BaseLine`) | Item | Qty | Unit Price | Line Total | Status |
| --- | ---: | ---: | --- | ---: | ---: | ---: | --- |
| 2605 | 22904 | 0 | `TO-SCAFFSET` | 4 | 1,105,000.00 | 4,420,000.00 | Found |
| 2605 | 22904 | 1 | `TO-SCFCTW` | 4 | 910,000.00 | 3,640,000.00 | Found |
| 2605 | 22904 | 2 | `TO-SCFEB060` | 8 | 162,500.00 | 1,300,000.00 | Found |
| | | | | | **GRPO sum (pre-tax)** | **9,360,000.00** | |

Item code on line 2 may read as `TO-SCFEB060` or similar `TO-SCF*` code; identity is `DocEntry 22904 / Line 2`.

### AP Invoice Lines Preview (what would be posted)

| Item | Qty | Unit Price | Project | Cost Center | GRPO Link |
| --- | ---: | ---: | --- | --- | --- |
| `TO-SCAFFSET` | 4 | 1,105,000.00 | APS | 40 | `GRPO 22904 / L0` |
| `TO-SCFCTW` | 4 | 910,000.00 | APS | 40 | `GRPO 22904 / L1` |
| `TO-SCFEB060` | 8 | 162,500.00 | APS | 40 | `GRPO 22904 / L2` |

Each preview line has `BaseType = 20`, `BaseEntry = 22904`, `BaseLine = 0|1|2`, `TaxCode = VAT11`.

### Footer still warns

```
Invoice total: 10,389,600.00 — GRPO sum: 9,360,000.00 (amounts differ)
```

Submit is **allowed**. Server only attaches a warning and continues:

> GRPO line amounts (9360000.00) do not match invoice total (10389600.00). Submission will continue.

---

## 5. The amount mismatch is VAT, not missing GRPO lines

Do not treat State B as “GRPO lines incomplete”. The arithmetic is exact PPN 11% (`VAT11`):

```
GRPO sum (pre-tax)     = 4,420,000 + 3,640,000 + 1,300,000 = 9,360,000.00
Tax 11%                = 9,360,000 × 0.11                 = 1,029,600.00
Invoice amount (gross) = 9,360,000 + 1,029,600            = 10,389,600.00
```

```
9,360,000 × 1.11 = 10,389,600
```

| Number | What it is | Includes VAT? |
| --- | --- | --- |
| `invoices.amount` / Invoice Summary Amount / `#invoice-total` | DDS header | **Yes** |
| GRPO `Price` / `UnitPrice` × qty / `#grpo-sum` | SAP GRPO line totals | **No** |
| AP preview `UnitPrice` when GRPO found | Copied from GRPO | **No** |
| AP preview `UnitPrice` when GRPO missing | `$invoice->amount` on a `SERVICE` line | **Yes** |

Comparison code (client, `sap-preview.blade.php`):

```javascript
const invoiceTotal = {{ (float) $invoice->amount }}; // tax-inclusive
// sum = Σ (qty * unit_price) from GRPO rows   // pre-tax
if (Math.abs(sum - invoiceTotal) > 0.01) {
    // show "(amounts differ)"
}
```

Same comparison on submit (`InvoiceController::submitToSap`). Neither side multiplies GRPO sum by 1.11, and neither side strips VAT from `invoice.amount`.

---

## 6. Code paths the two screenshots correspond to

```
previewSapSubmission
  isStandalone = empty(po_no)          → false (PO 260204302)
  isConsignment = invoice->isConsignment() → false
  grpoRows = resolveGrpoLinesForPreview()
      getGrposByPoNumber(po_no)
      keep lines with openQty > 0
  grpoReferences = only rows where found === true
  payloadBuilder = new SapApInvoicePayloadBuilder(invoice, grpoReferences)
  apPreview.document_lines = mapLineItems()
```

| Condition | `mapLineItems()` result | Seen in |
| --- | --- | --- |
| `grpoReferences` empty, not consignment | 1× `SERVICE`, qty 1, `UnitPrice = invoice.amount`, no BaseType | State A |
| `grpoReferences` non-empty | One AP line per GRPO row, `BaseType 20`, GRPO item/qty/price | State B |
| consignment, no GRPO refs | `CONSIGNMENT` from `invoice_line_details` + G/L | not these screenshots |

Mode badge is computed from `po_no` / consignment flags, **not** from whether GRPO rows were found. That is why State A can show `GRPO-based (BaseType 20)` while the preview lines say `Standalone`.

---

## 7. Numbers an AI should reuse (do not re-guess)

```
invoice_number     = 71260800624
supplier_sap_code  = VCASJIDR01
po_no              = 260204302
grpo_docnum        = 2605
grpo_docentry      = 22904
tax_code           = VAT11
tax_rate           = 0.11
invoice_amount     = 10389600.00   // gross
grpo_sum           = 9360000.00    // net
vat_amount         = 1029600.00
project_code       = APS
costing_code       = 40
```

Line check:

```
4 × 1,105,000 = 4,420,000
4 ×   910,000 = 3,640,000
8 ×   162,500 = 1,300,000
                  9,360,000
```

---

## 8. Likely issues to fix (from these two pictures)

Ordered by what the screenshots actually show. Confirm with the user which one is in scope.

1. **False `(amounts differ)` on a correct GRPO match (State B)**  
   Compare net-to-net or gross-to-gross. Candidate: `GRPO sum × (1 + VAT rate)` vs `invoice.amount`, or `invoice.amount / 1.11` vs GRPO sum, using tax code `VAT11` = 11%. Current tolerance `0.01` is fine; the compared bases are wrong.

2. **Preview lies in State A**  
   When `po_no` is set but GRPO is `Not found`, AP Invoice Lines Preview still renders a standalone `SERVICE` line at the **gross** amount. Mode badge still says GRPO-based. Preview should stay empty / error, not look like a valid standalone post.

3. **GRPO lookup miss in State A**  
   `getGrposByPoNumber('260204302')` failed or returned no open lines. State B proves the GRPO **does** exist (`DocNum 2605`, `DocEntry 22904`) and open lines sum to the invoice net. Investigate lookup key (PO vs GRPO DocNum vs NumAtCard), open-qty filter, and vendor match (`VCASJIDR01`).

4. **Do not “fix” State B by adding a SERVICE line for the VAT difference**  
   `10,389,600 − 9,360,000 = 1,029,600` is tax, not a missing item. SAP should compute VAT from `TaxCode = VAT11` on the three GRPO-linked lines.

---

## 9. Related files

| File | Why |
| --- | --- |
| `resources/views/invoices/sap-preview.blade.php` | UI, amount JS, submit guard |
| `app/Http/Controllers/InvoiceController.php` (`previewSapSubmission`, `submitToSap`, `resolveGrpoLinesForPreview`) | Resolve GRPO, filter found rows, amount warning |
| `app/Services/SapApInvoicePayloadBuilder.php` (`mapLineItems`) | SERVICE fallback vs BaseType 20 lines |
| `tests/Feature/SapApInvoicePreviewTest.php` | Existing preview tests |

---

## 10. Screenshot files

| File | State |
| --- | --- |
| [`images/sap-ap-invoice-preview/01-grpo-not-found-standalone-preview.png`](images/sap-ap-invoice-preview/01-grpo-not-found-standalone-preview.png) | GRPO Not found, preview = SERVICE standalone, sum 0 |
| [`images/sap-ap-invoice-preview/02-grpo-found-amount-mismatch.png`](images/sap-ap-invoice-preview/02-grpo-found-amount-mismatch.png) | GRPO Found 22904 L0–L2, net 9,360,000 vs gross 10,389,600 |
