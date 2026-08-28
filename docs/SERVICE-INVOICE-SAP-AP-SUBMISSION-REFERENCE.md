# Service Invoice → SAP AP Invoice (submission reference)

**Purpose**: Target shape of a **Service** A/P Invoice in SAP B1, taken from a real posted document. Use this when implementing or changing DDS **Submit to SAP AP Invoice** so Service invoices are not posted as a single `SERVICE` lump or as GRPO draw-from-base (`BaseType 20`).  
**DDS type**: `invoice_types.type_name = Service` (`is_consignment = false`)  
**This example is already in SAP** (`Status: SAP`, `SAP Doc: 267005849 (Legacy)`). It is the **desired SAP result**, not a DDS preview screen.  
**Related**: [`CONSIGNMENT-INVOICE-REFERENCE.md`](CONSIGNMENT-INVOICE-REFERENCE.md) (CONSIGNMENT bucket item), [`SAP-AP-INVOICE-PREVIEW-GRPO-REFERENCE.md`](SAP-AP-INVOICE-PREVIEW-GRPO-REFERENCE.md) (GRPO-based Item invoices).  
**Screenshot**: [`images/service-invoices/01-united-tractors-loreh.png`](images/service-invoices/01-united-tractors-loreh.png)

---

## 1. What this example is

| Layer | Screen | Role |
| --- | --- | --- |
| **SAP B1** (top) | A/P Invoice | Accounting document. Two **named service items** (`SV-LABOUR`, `SV-CONSUMABLEPART`), PPN 11%, WTax liable. |
| **DDS** (bottom) | Invoice Details | Document-control record. Header amount is **tax-inclusive**. Type = Service. PO + linked BAPP/GRPO exist as supporting docs. |

Business story:

1. United Tractors Loreh invoices labour + consumable parts (quotation in remarks).
2. DDS stores one Service invoice with PO `260203470` and linked BAPP / GRPO.
3. Finance posts SAP A/P with **two service item lines** (not stock from GRPO, not one generic `SERVICE` line).
4. DDS keeps `sap_doc_num` `267005849`.

---

## 2. Header mapping (DDS → SAP B1)

| DDS Invoice Details | Value in this example | SAP A/P Invoice field | Service Layer payload |
| --- | --- | --- | --- |
| Invoice Number | `980347730-LR` | Vendor Ref. No. | `NumAtCard` |
| Supplier | `UNITED TRACTORS LOREH (VUNTLIDR01)` | Vendor + Name | `CardCode` = `VUNTLIDR01` |
| Faktur No | `04002600241420446` | Faktur Pajak No. | `U_MIS_FPNum` |
| Invoice Date | `26-Jun-2026` | Document Date / Faktur Pajak Date | `TaxDate`, `U_MIS_FPDate` |
| Receive Date | `08-Jul-2026` | Posting Date | `DocDate` |
| *(DDS Payment Date empty)* | SAP Due Date `10.08.2026` | Due Date | `DocDueDate` |
| PO Number | `260203470` | *(not on A/P header in the screenshot)* | `Reference1` |
| Invoice Type | `Service` | Contents still **Item** type (service **item codes**) | do **not** switch SAP document to Service-type header |
| Currency | `IDR` | Local currency | `DocCurrency` |
| Amount | **IDR 21,645,000.00** | **Total Payment Due** | header is gross; lines are **net** |
| SAP Post Status | `SAP Doc: 267005849 (Legacy)` | DocNum in status / series | do **not** send `DocNum`; SAP assigns it |
| Invoice Project | `017C` | Line **Project** | `DocumentLines[].ProjectCode` |
| Current Location | `000HACC` | Line **Department** in this example is `40`, not the DDS location | `DocumentLines[].CostingCode` = `40` |
| Receive / Payment Project | `000H` / `001H` | not on SAP A/P header | DDS workflow only |

SAP series/internal No. `2826` on the window is **not** the DocNum DDS stores. DDS stores **`267005849`**.

---

## 3. Amounts (do not post gross on lines)

```
Labour net              18,500,000.00
Consumable net           1,000,000.00
Total before discount   19,500,000.00     ← Σ SAP line UnitPrice (qty 1)
PPN 11%                  2,145,000.00     ← 19,500,000 × 0.11
Total payment due       21,645,000.00     ← DDS Amount
WTax (withholding)         ~2% of DPP     ← shown on SAP footer; NOT added to DDS Amount
```

| Number | Includes PPN? | Includes WTax? |
| --- | --- | --- |
| SAP line UnitPrice / Total Before Discount | No | No |
| SAP Tax | Yes (the tax itself) | No |
| SAP Total Payment Due / DDS Amount | **Yes** | **No** (WTax is withheld at payment) |
| SAP WTax Amount | No | Yes (PPh, ~2%) |

`19,500,000 × 1.11 = 21,645,000` exactly.

WTax on the screenshot is **2% PPh**:

- `18,500,000 × 0.02 = 370,000` (labour only)
- `19,500,000 × 0.02 = 390,000` (both lines)

Both SAP lines are **WTax Liable = Yes**. Submission must set withholding on the AP Invoice according to vendor WTax setup in SAP; **do not** bake WTax into `UnitPrice` or into DDS `amount`.

Current payload builder posts `UnitPrice = invoice.amount` (gross **21,645,000**) on one `SERVICE` line. That double-counts PPN once SAP applies `TaxCode`. **Service lines must use net prices** (18,500,000 + 1,000,000).

---

## 4. Target SAP DocumentLines (the feature)

SAP Contents tab in this example:

| Item No | Description | Qty | Unit Price (net) | Tax Code | WTax Liable | G/L Account | Project | Department |
| --- | --- | ---: | ---: | --- | --- | --- | --- | --- |
| `SV-LABOUR` | LABOUR | 1 | 18,500,000.00 | PPN 11% (`S111` / `B111`) | Yes | `51105010` | `017C` | `40` |
| `SV-CONSUMABLEPART` | CONSUMABLE PART | 1 | 1,000,000.00 | PPN 11% (`S111` / `B111`) | Yes | `51105010` | `017C` | `40` |

Tax code on the capture is PPN 11%. Service invoices in this company typically use **`S111`**. Current DDS IDR default is **`VAT11`** (`config/services.php` `tax_codes.by_currency.IDR`). The new feature must use the **SAP tax code that exists on the item/vendor**, not invent a third rate. Rate in this example is 11% either way.

### Payload shape to reproduce this SAP document

Header (already mostly implemented in `SapApInvoicePayloadBuilder::build()`):

```json
{
  "CardCode": "VUNTLIDR01",
  "NumAtCard": "980347730-LR",
  "DocDate": "2026-07-08",
  "TaxDate": "2026-06-26",
  "DocDueDate": "2026-08-10",
  "DocCurrency": "IDR",
  "Reference1": "260203470",
  "U_MIS_FPNum": "04002600241420446",
  "U_MIS_FPDate": "2026-06-26",
  "Comments": "<DDS remarks>",
  "DocumentLines": []
}
```

Lines (**this is the gap**):

```json
[
  {
    "ItemCode": "SV-LABOUR",
    "Quantity": 1,
    "UnitPrice": 18500000.00,
    "TaxCode": "S111",
    "AccountCode": "51105010",
    "ProjectCode": "017C",
    "CostingCode": "40",
    "WTLiable": "tYES"
  },
  {
    "ItemCode": "SV-CONSUMABLEPART",
    "Quantity": 1,
    "UnitPrice": 1000000.00,
    "TaxCode": "S111",
    "AccountCode": "51105010",
    "ProjectCode": "017C",
    "CostingCode": "40",
    "WTLiable": "tYES"
  }
]
```

**Do not send** `BaseType` / `BaseEntry` / `BaseLine` on these lines. They are not drawn from GRPO even though DDS has a linked GRPO additional document.

**Do not send** `ItemCode: SERVICE` with `UnitPrice: 21645000`.

---

## 5. PO / GRPO on a Service invoice (easy to get wrong)

This invoice **has** a PO and a linked GRPO in DDS, but SAP A/P lines are **service items**, not GRPO stock.

| DDS linked additional document | Type | Date |
| --- | --- | --- |
| `BAPP108.2026` | BAPP | 2026-06-12 |
| `265131535` | BAPP | 2026-07-13 |
| `260505535` | GRPO | 2026-07-13 |

Current submit preview (`previewSapSubmission`):

- `isStandalone = empty(po_no)` → **false** here (`po_no = 260203470`)
- Mode badge becomes **GRPO-based (BaseType 20)**
- Lookup tries `getGrposByPoNumber('260203470')` and would post stock lines with `BaseType 20` if found

That is **wrong for Invoice Type = Service**. PO/GRPO/BAPP are supporting documents for routing and evidence. The SAP A/P **accounting lines** are `SV-*` + G/L `51105010`.

| Invoice type | How to post AP lines |
| --- | --- |
| Item (goods) with PO | GRPO draw-from-base `BaseType 20` (see GRPO preview reference) |
| Consignment | `CONSIGNMENT` item + invoice G/L (see consignment reference) |
| **Service** (this file) | Named service items + G/L; **no** BaseType 20, even if `po_no` is set |

---

## 6. What DDS has today vs what SAP shows

| Concern | This SAP document | Current `SapApInvoicePayloadBuilder` |
| --- | --- | --- |
| Line count | 2 | 1 (`SERVICE`) unless GRPO refs exist |
| Item codes | `SV-LABOUR`, `SV-CONSUMABLEPART` | `config default_item_code` = `SERVICE` |
| Unit prices | net 18.5M and 1M | gross `invoice.amount` 21.645M |
| G/L | `51105010` on both lines | G/L only for consignment |
| Tax on lines | PPN 11% (`S111`/`B111`) | `VAT11` for all IDR |
| WTax | Liable Yes, ~2% footer | not mapped |
| Project | `017C` on lines | `invoice_project` → `ProjectCode` (OK if mapped) |
| Cost center | `40` | `SapDepartment` from `cur_loc`, else config `40` |
| GRPO link | none on A/P lines | if `po_no` set, prefers BaseType 20 |

`invoice_line_details` today stores `description`, `quantity`, `unit_price`, `amount` — **no** `item_code`, `tax_code`, `gl_account`, or `wtax_liable`. Consignment already persists lines; Service submission needs the same (plus SAP item code and G/L) or a type-specific line mapper (`SV-LABOUR` / `SV-CONSUMABLEPART` cannot be inferred from header amount).

DDS remarks on this record include `[Import] Supplier not matched automatically: PT UNITED TRACTORS Tbk` while SAP CardCode is the **site** vendor `VUNTLIDR01` (UNITED TRACTORS LOREH). CardCode must stay the matched supplier, not the legal-entity name from OCR.

---

## 7. Checklist for Service AP submission

1. Branch on **`InvoiceType` = Service**, not only on “has `po_no`”.
2. Post **multiple net lines** with real SAP item codes (`SV-LABOUR`, `SV-CONSUMABLEPART`, …), not one gross `SERVICE` line.
3. Set **`AccountCode`** (here `51105010`) on each service line.
4. Set **`ProjectCode`** from invoice project (`017C`) and **`CostingCode`** `40` (SAP department), independent of Receive/Payment project.
5. Apply PPN via **TaxCode**; line `UnitPrice` is pre-tax. DDS `amount` must equal Σ net × (1 + rate).
6. Enable **WTax liable** per vendor/SAP setup; do not subtract WTax from DDS amount or from line prices.
7. Map `NumAtCard`, faktur UDFs, `DocDate` = receive date, `TaxDate` = invoice date, `Reference1` = PO.
8. Keep linked GRPO/BAPP as **additional documents** in DDS; do not convert them into `BaseType 20` lines for Service type.
9. After post, store SAP **DocNum** (here `267005849`) on the invoice; never send DocNum in the create payload.

---

## 8. Worked example (copy-paste identity)

```
invoice_number     = 980347730-LR
card_code          = VUNTLIDR01
supplier_name      = UNITED TRACTORS LOREH
invoice_type       = Service
po_no              = 260203470
faktur_no          = 04002600241420446
invoice_date       = 2026-06-26
receive_date       = 2026-07-08
due_date_in_sap    = 2026-08-10
invoice_project    = 017C
costing_code       = 40
gl_account         = 51105010
line_1             = SV-LABOUR           qty 1  net 18500000
line_2             = SV-CONSUMABLEPART   qty 1  net  1000000
dpp                = 19500000
ppn_11             =  2145000
dds_amount         = 21645000
sap_doc_num        = 267005849
linked_grpo        = 260505535   (additional document only)
```

---

## 9. Screenshot

[`images/service-invoices/01-united-tractors-loreh.png`](images/service-invoices/01-united-tractors-loreh.png) — SAP A/P (top) vs DDS Invoice Details (bottom), United Tractors Loreh service invoice.
