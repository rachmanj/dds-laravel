# Plan: Line Items Review & Edit on Invoice Create (Import flow)

**Status**: Planned  
**Date**: 2026-05-09  
**Scope**: `invoices.create` — import card only; no change to non-import creates or to SAP posting  
**Related**: `[docs/INVOICE-FROM-DOCUMENT-IMPLEMENTATION-PLAN.md](INVOICE-FROM-DOCUMENT-IMPLEMENTATION-PLAN.md)`, `[docs/architecture.md](architecture.md)` (Invoice creation from PDF/image), `[docs/decisions.md](decisions.md)` (2026-03-31)

---

## 1. Problem statement

After extraction the draft carries a `line_items` array inside the cache/`import_extraction` JSON. Currently:

- `applyDraft()` in `create.blade.php` **ignores** `draft.line_items` — it writes them to the `remarks` field as a one-line text summary only.
- The user has **no structured view** of what was extracted for each line.
- The user **cannot correct** quantities, unit prices, or amounts before saving.
- `InvoiceImportLineDetailsPersister` runs **after** `Invoice::create`, so any OCR error in a line is only fixable on the invoice **show** page, after the record already exists.
- There is **no live feedback** on the create form when the sum of line amounts diverges from the header `amount` field.

---

## 2. Goals


| #   | Goal                                                                                                                                           |
| --- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| G1  | Show extracted `line_items` in the import card as an editable table immediately after `applyDraft()` runs.                                     |
| G2  | Let users add, edit, and delete rows before saving.                                                                                            |
| G3  | Show a **live warning banner** whenever `Σ line amounts ≠ header amount` (same tolerance as show view: IDR ±1, else ±0.01).                    |
| G4  | On form submit, pass the user-reviewed line items to the server so `InvoiceImportLineDetailsPersister` uses them instead of the raw OCR lines. |
| G5  | Remove the `[Import lines]` text block from the auto-generated `remarks` field (it is noise once lines are structured).                        |
| G6  | Keep all changes inside the import card section; zero impact on the non-import create path.                                                    |


---

## 3. Out of scope

- SAP line-item posting (still header-only).
- Line items for manually created invoices (no `import_uuid`).
- Changing the `InvoiceExtractionResult` DTO or OpenRouter extraction prompt.
- The invoice **edit** form (not touched).

---

## 4. Current data flow (before this change)

```
ExtractInvoiceFromDocumentJob
  └─ InvoiceImportDraftBuilder::build()
       ├─ draft.line_items   ← array of {description, quantity, unit_price, amount}
       └─ draft.remarks      ← "[Import lines] desc qty @ price = amt; ..."  (text summary)

GET /invoices/import-draft/{uuid}
  └─ returns full draft JSON (including line_items)

applyDraft() in create.blade.php
  ├─ fills header fields (supplier, amount, dates, …)
  ├─ appends remarks text (includes [Import lines] block)
  └─ ✗ does NOT render line_items as a table

POST /invoices  (store)
  ├─ Invoice::create  (import_extraction JSON saved from cache)
  └─ InvoiceImportLineDetailsPersister::persistFromImportExtraction()
       └─ reads import_extraction.draft.line_items from the saved JSON
```

---

## 5. Proposed data flow (after this change)

```
(extraction unchanged)

applyDraft() in create.blade.php
  ├─ fills header fields  (unchanged)
  ├─ renders line_items into an editable table  ← NEW
  ├─ triggers live mismatch check              ← NEW
  └─ does NOT append [Import lines] to remarks ← CHANGE

POST /invoices  (store)
  ├─ line_items rows submitted as hidden inputs  ← NEW
  ├─ Invoice::create  (unchanged)
  └─ InvoiceImportLineDetailsPersister::persistFromUserInput()  ← NEW method
       └─ reads request line_items[] instead of import_extraction JSON
```

---

## 6. Implementation plan

### Phase 1 — Blade: line items table in the import card

**File**: `resources/views/invoices/create.blade.php`

Add a `div#import_line_items_wrap` block inside the import card body, below the status span. Hidden by default (via `d-none`), shown and populated by `applyDraft()`.

```html
<!-- shown only after applyDraft() fires -->
<div id="import_line_items_wrap" class="d-none mt-3">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <strong class="small">Extracted line items</strong>
        <button type="button" class="btn btn-outline-secondary btn-xs" id="import_line_add">
            <i class="fas fa-plus"></i> Add row
        </button>
    </div>

    <!-- live mismatch warning -->
    <div id="import_line_mismatch" class="alert alert-warning small py-2 d-none mb-2">
        <i class="fas fa-exclamation-triangle"></i>
        Sum of line amounts (<span id="import_line_sum_display">0</span>)
        differs from invoice amount (<span id="import_line_header_display">0</span>).
        Verify before saving.
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0" id="import_line_table">
            <thead class="thead-light">
                <tr>
                    <th>Description</th>
                    <th class="text-right" style="width:100px">Qty</th>
                    <th class="text-right" style="width:120px">Unit price</th>
                    <th class="text-right" style="width:130px">Amount</th>
                    <th style="width:36px"></th>
                </tr>
            </thead>
            <tbody id="import_line_tbody"></tbody>
        </table>
    </div>
    <small class="text-muted">SAP posting stays header-only. These lines are saved as informational records.</small>
</div>
```

Each row template (rendered by JS):

```html
<tr>
  <td><input type="text"   name="import_line_items[{i}][description]" class="form-control form-control-sm il-desc" required></td>
  <td><input type="text"   name="import_line_items[{i}][quantity]"    class="form-control form-control-sm il-qty  text-right" inputmode="decimal"></td>
  <td><input type="text"   name="import_line_items[{i}][unit_price]"  class="form-control form-control-sm il-price text-right" inputmode="decimal"></td>
  <td><input type="text"   name="import_line_items[{i}][amount]"      class="form-control form-control-sm il-amt  text-right" inputmode="decimal"></td>
  <td class="text-center align-middle">
      <button type="button" class="btn btn-link btn-sm text-danger p-0 il-delete" title="Remove row">
          <i class="fas fa-times"></i>
      </button>
  </td>
</tr>
```

---

### Phase 2 — JS: `applyDraft()` extension + live validation

**File**: `resources/views/invoices/create.blade.php` (script block)

#### 2a. Render lines after draft is applied

Extend the existing `applyDraft(rawDraft)` function:

```js
// After the existing header-field assignments:
if (Array.isArray(draft.line_items) && draft.line_items.length) {
    renderImportLines(draft.line_items);
    $('#import_line_items_wrap').removeClass('d-none');
    checkImportLineMismatch();
}
```

#### 2b. `renderImportLines(lines)`

```js
function renderImportLines(lines) {
    $('#import_line_tbody').empty();
    lines.forEach(function (row, i) {
        appendImportLineRow(i, row.description || '', row.quantity, row.unit_price, row.amount);
    });
    reindexImportLines();
}

function appendImportLineRow(i, desc, qty, price, amt) {
    const html = `<tr>
      <td><input type="text"  name="import_line_items[${i}][description]" class="form-control form-control-sm il-desc" value="${escHtml(desc)}" required></td>
      <td><input type="text"  name="import_line_items[${i}][quantity]"    class="form-control form-control-sm il-qty  text-right" inputmode="decimal" value="${escNum(qty)}"></td>
      <td><input type="text"  name="import_line_items[${i}][unit_price]"  class="form-control form-control-sm il-price text-right" inputmode="decimal" value="${escNum(price)}"></td>
      <td><input type="text"  name="import_line_items[${i}][amount]"      class="form-control form-control-sm il-amt  text-right" inputmode="decimal" value="${escNum(amt)}"></td>
      <td class="text-center align-middle">
          <button type="button" class="btn btn-link btn-sm text-danger p-0 il-delete" title="Remove"><i class="fas fa-times"></i></button>
      </td></tr>`;
    $('#import_line_tbody').append(html);
}
```

- `escHtml(v)` — escape for HTML attribute.
- `escNum(v)` — `v != null ? v : ''`.

#### 2c. `reindexImportLines()`

Renumbers `name` attributes after add/delete so the server receives a clean 0-based array.

```js
function reindexImportLines() {
    $('#import_line_tbody tr').each(function (i) {
        $(this).find('input').each(function () {
            const name = $(this).attr('name') || '';
            $(this).attr('name', name.replace(/import_line_items\[\d+\]/, `import_line_items[${i}]`));
        });
    });
}
```

#### 2d. Add / delete row events

```js
$('#import_line_add').on('click', function () {
    const i = $('#import_line_tbody tr').length;
    appendImportLineRow(i, '', null, null, null);
    reindexImportLines();
    checkImportLineMismatch();
});

$(document).on('click', '#import_line_tbody .il-delete', function () {
    $(this).closest('tr').remove();
    reindexImportLines();
    checkImportLineMismatch();
});
```

#### 2e. `checkImportLineMismatch()` — live warning

Fires on: `applyDraft`, row add, row delete, change on any `.il-amt` input, and change on `#amount` / `#amount_display`.

```js
function checkImportLineMismatch() {
    const lineRows = $('#import_line_tbody tr');
    if (!lineRows.length) {
        $('#import_line_mismatch').addClass('d-none');
        return;
    }

    let lineSum = 0;
    lineRows.each(function () {
        const v = parseFloat($(this).find('.il-amt').val().replace(/,/g, '')) || 0;
        lineSum += v;
    });

    const currency = ($('#currency').val() || '').toUpperCase();
    const tolerance = currency === 'IDR' ? 1.0 : 0.01;
    const headerAmt = parseFloat($('#amount').val()) || 0;
    const mismatch = Math.abs(headerAmt - lineSum) > tolerance;

    $('#import_line_sum_display').text(lineSum.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#import_line_header_display').text(headerAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#import_line_mismatch').toggleClass('d-none', !mismatch);
}

// Hook onto amount field change
$(document).on('input change', '#amount, #amount_display, #import_line_tbody .il-amt', function () {
    checkImportLineMismatch();
});
$(document).on('change', '#currency', function () {
    checkImportLineMismatch();
});
```

#### 2f. Remove `[Import lines]` from auto-remarks

Inside `applyDraft()`, remove the line-item text block from `draft.remarks` before appending it:

```js
if (draft.remarks) {
    // strip the [Import lines] block — it is now shown as a structured table
    const cleaned = draft.remarks.replace(/\[Import lines\][^\n]*/g, '').trim();
    const cur = $('#remarks').val() || '';
    if (cleaned) {
        $('#remarks').val(cur ? cur + '\n' + cleaned : cleaned);
    }
}
```

---

### Phase 3 — Controller: accept user-reviewed lines

**File**: `app/Http/Controllers/InvoiceController.php`

Add validation for the submitted line items array in `store()`:

```php
// Append to the existing validate() call:
'import_line_items'                     => ['nullable', 'array', 'max:200'],
'import_line_items.*.description'       => ['required', 'string', 'max:65535'],
'import_line_items.*.quantity'          => ['nullable', 'numeric'],
'import_line_items.*.unit_price'        => ['nullable', 'numeric'],
'import_line_items.*.amount'            => ['nullable', 'numeric', 'min:0'],
```

Replace the existing persister call:

```php
// Before:
app(InvoiceImportLineDetailsPersister::class)->persistFromImportExtraction($invoice);

// After:
$userLines = $request->input('import_line_items');
if (is_array($userLines) && count($userLines) > 0) {
    app(InvoiceImportLineDetailsPersister::class)->persistFromUserInput($invoice, $userLines);
} else {
    app(InvoiceImportLineDetailsPersister::class)->persistFromImportExtraction($invoice);
}
```

The `else` branch keeps backward compatibility for cases where the draft had no lines but `import_extraction` does (e.g. a future API path or a re-run).

---

### Phase 4 — Persister: new `persistFromUserInput()` method

**File**: `app/Services/InvoiceImportLineDetailsPersister.php`

```php
/**
 * Persist line items submitted directly by the user (create form review step).
 *
 * @param  array<int, array<string, mixed>>  $userLines
 */
public function persistFromUserInput(Invoice $invoice, array $userLines): int
{
    $invoice->lineDetails()->delete();

    $inserted = 0;
    $lineNo = 0;

    foreach ($userLines as $row) {
        if (! is_array($row)) {
            continue;
        }
        $description = trim((string) ($row['description'] ?? ''));
        $quantity    = $this->nullableNumeric($row['quantity']   ?? null);
        $unitPrice   = $this->nullableNumeric($row['unit_price'] ?? null);
        $amount      = $this->nullableNumeric($row['amount']     ?? null);

        if ($amount === null && $quantity !== null && $unitPrice !== null) {
            $amount = round($quantity * $unitPrice, 2);
        }

        if ($description === '' && $quantity === null && $unitPrice === null && $amount === null) {
            continue;
        }
        if ($description === '') {
            $description = '(no description)';
        }

        $lineNo++;
        InvoiceLineDetail::create([
            'invoice_id'  => $invoice->id,
            'line_no'     => $lineNo,
            'description' => $description,
            'quantity'    => $quantity,
            'unit_price'  => $unitPrice,
            'amount'      => $amount,
            'source'      => 'user',   // distinguishes user-reviewed from raw import
        ]);
        $inserted++;
    }

    return $inserted;
}
```

Note the new `source` value `**user**` — row was reviewed and submitted by the user on the create form.

---

### Phase 5 — `InvoiceImportDraftBuilder`: stop adding `[Import lines]` to remarks

**File**: `app/Services/InvoiceImportDraftBuilder.php`

Remove the `[Import lines]` block from `$remarksParts` (the JS `applyDraft` cleanup in Phase 2f is still kept as a safety fallback for cached drafts built before this change):

```php
// Remove (lines 34–50 in the current file):
if ($extraction->lineItems !== []) {
    $lines = array_map(function ($r) { … }, $extraction->lineItems);
    $remarksParts[] = '[Import lines] '.implode('; ', array_filter($lines));
}
```

The supplier-not-matched remark line stays.

---

### Phase 6 — `invoice_line_details.source`: add `'user'` as a recognized value

`source` is a free-form string column (`varchar(20)`) — no migration required. Document the new value in code comments and the `InvoiceLineDetail` model PHPDoc.

```php
/**
 * source values:
 *   'import'   — written by InvoiceImportLineDetailsPersister from raw OCR draft
 *   'adjusted' — row was edited via the show-page edit modal after import
 *   'user'     — row was reviewed and submitted via the create-form review step
 */
```

---

### Phase 7 — Tests

**File**: `tests/Feature/InvoiceLineDetailUpdateTest.php` (extend) or new file `tests/Feature/InvoiceCreateLineItemsTest.php`


| Test                                                        | Assertion                                                                                           |
| ----------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `test_create_with_import_lines_persists_user_reviewed_rows` | Submit `import_line_items[]` on store → rows in DB with `source = 'user'`.                          |
| `test_create_falls_back_to_extraction_when_no_user_lines`   | No `import_line_items` in request + `import_uuid` → rows from `import_extraction.draft.line_items`. |
| `test_create_without_import_produces_no_line_rows`          | No `import_uuid`, no `import_line_items` → `invoice_line_details` empty.                            |
| `test_user_lines_validation_rejects_non_numeric_amount`     | `import_line_items[0][amount] = 'abc'` → 422.                                                       |


---

## 7. File change summary


| File                                                 | Change                                                                                                                                                                      |
| ---------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `resources/views/invoices/create.blade.php`          | Add `#import_line_items_wrap` block; extend `applyDraft()`, add `renderImportLines()`, `checkImportLineMismatch()`, add/delete/reindex helpers; bind amount-field listener. |
| `app/Http/Controllers/InvoiceController.php`         | Add `import_line_items.`* validation rules; conditional persister branch.                                                                                                   |
| `app/Services/InvoiceImportLineDetailsPersister.php` | Add `persistFromUserInput()`.                                                                                                                                               |
| `app/Services/InvoiceImportDraftBuilder.php`         | Remove `[Import lines]` from `$remarksParts`.                                                                                                                               |
| `tests/Feature/InvoiceCreateLineItemsTest.php`       | New feature test (or extend existing).                                                                                                                                      |


No migration required. No route changes. No model changes.

---

## 8. UX behaviour summary


| Trigger                               | UI response                                                                                   |
| ------------------------------------- | --------------------------------------------------------------------------------------------- |
| `applyDraft()` fires after extraction | Line table appears inside import card; rows prefilled from OCR. Mismatch banner checked.      |
| User edits any `Amount` cell          | Mismatch banner recalculates instantly.                                                       |
| User changes `Amount` header field    | Mismatch banner recalculates instantly.                                                       |
| User changes `Currency`               | Mismatch tolerance switches between IDR (±1) and non-IDR (±0.01).                             |
| User clicks `+ Add row`               | Empty row appended; mismatch rechecks.                                                        |
| User clicks row delete (×)            | Row removed; names reindexed; mismatch rechecks.                                              |
| User submits form                     | `import_line_items[]` posted; server uses them for `invoice_line_details`; `source = 'user'`. |
| No `import_uuid` (manual create)      | `#import_line_items_wrap` never shown; no `import_line_items` posted; persister skips.        |
| Draft has no `line_items`             | Table is not shown; mismatch banner never appears.                                            |


---

## 9. Open questions for review

1. **Column widths on small screens** — the table has 5 columns; confirm the import card is full-width (`col-12`) or add horizontal scroll.
2. **Auto-compute amount from qty × unit price** — should editing Qty or Unit price auto-fill Amount in the row? Keeps parity with the show-page calculator but adds JS surface area; optional for v1.
3. **Max rows guard** — server validates `max:200`; should the JS also prevent adding beyond that limit with a UI message?
4. **Remarks cleanup** — removing `[Import lines]` from auto-remarks may surprise users who relied on that text. Confirm the structured table is sufficient replacement.

