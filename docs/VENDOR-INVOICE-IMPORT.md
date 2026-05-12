# Vendor invoice import (DDS)

This document describes how **DDS (this Laravel app)** pulls invoice data from **vendor-operated** customer invoice APIs—such as those documented in [customer-invoice-api-reference.md](customer-invoice-api-reference.md)—and stores them as **purchase invoices** locally.

## Purpose

Staff receive vendor invoices physically (email or hardcopy) and already know the vendor invoice numbers. They use the admin UI to:

1. Enter one or more invoice numbers.
2. **Look up** each number against the vendor’s `GET /api/v1/invoices/{invoice_no}` endpoint.
3. Review a preview (**New**, **Already in DB**, **Not found**).
4. **Import selected** “New” rows into DDS (`invoices` plus `invoice_line_details`).

There is **no** scheduled sync; imports are **user-driven**.

## Supported vendors

Suppliers must exist in DDS with these **SAP codes** (see `suppliers.sap_code`), and matching entries must exist under `config('vendor_api.vendors')`:

| SAP code     | Typical supplier |
|--------------|------------------|
| `VCASJIDR01` | CAHAYA SARANGE JAYA, PT |
| `VCASAIDR01` | CV CAHAYA SARANGHAE |

The **Import Invoices from API** action on the supplier admin show page appears only when that supplier’s `sap_code` is configured in `config/vendor_api.php`.

## Access control

Routes are under `/admin/suppliers/{supplier}/vendor-invoices`. Middleware (see `VendorInvoiceImportController`):

| Action   | Capability |
|----------|------------|
| View import page (`index`) | `superadmin`, `admin`, or permission `view-suppliers` |
| Look up / Import (`lookup`, `import`) | `superadmin`, `admin`, `accounting`, or permission `create-invoices` |

## Configuration (environment)

Set in `.env` (see `.env.example`). **Base URL** must be the **site root only** (no `/api` path)—the app appends `/api/v1/invoices/...`.

Per vendor:

| Variable | Meaning |
|----------|---------|
| `VENDOR_VCASJIDR01_API_URL` | Base URL (e.g. `https://sarang-erp.xyz`) |
| `VENDOR_VCASJIDR01_API_TOKEN` | Bearer token for that vendor API |
| `VENDOR_VCASJIDR01_TYPE_ID` | DDS `invoice_types.id` for imported rows (default `1`) |
| `VENDOR_VCASJIDR01_CUR_LOC` | Fallback **current location** when the importing user has no department (default `000HPROC`) |
| `VENDOR_VCASAIDR01_*` | Same shape for the second vendor |

Optional HTTP tuning (`config/vendor_api.php` → `http`):

| Variable | Meaning |
|----------|---------|
| `VENDOR_API_HTTP_USER_AGENT` | `User-Agent` header (some hosts/WAFs are strict) |
| `VENDOR_API_VERIFY_SSL` | Set `false` only for local debugging if TLS verification fails |

After changing config, run `php artisan config:clear` if you use config caching.

## Current location (`cur_loc`)

Imported invoices use **`cur_loc`** as follows:

1. **Primary:** the logged-in user’s **`department_location_code`** (via `User` → `department` → `location_code`), so the document is attributed to the importer’s department.
2. **Fallback:** the vendor’s `cur_loc` value in `vendor_api` config (env `VENDOR_*_CUR_LOC`) when the user has no department or no location code.
3. If both are empty, `cur_loc` may be stored as `null` (column is nullable).

This matches the pattern used elsewhere for user-scoped document location (e.g. Additional Documents, invoice create flows).

## Field mapping (vendor API → DDS)

Summary; detail payloads follow samples in `docs/invoice-detail.txt` and `docs/get-invoices.txt`.

| Vendor (detail) | DDS `invoices` |
|-----------------|----------------|
| `invoice_no` | `invoice_number` |
| `date` | `invoice_date` |
| `total_amount` | `amount` |
| `currency.code` | `currency` |
| `reference_no` (truncated to 30 chars) | `po_no` |
| `description` | `remarks` |
| (import run date) | `receive_date` |
| Resolved supplier | `supplier_id` |
| `open` | `status` |
|Importer | `created_by` |
| Config `type_id` | `type_id` |
| Importer dept. / config fallback | `cur_loc` |
| Supplier | `payment_project` |

Line items → `invoice_line_details` with `source = vendor_api`; `description` prefers `item`, then `description`; amounts map `qty`, `unit_price`, `total`.

## HTTP client behaviour

`App\Services\VendorInvoiceFetchService` uses Laravel `Http` with:

- `Accept: application/json`
- `Authorization: Bearer {token}`
- Configurable `User-Agent`
- Optional `withoutVerifying()` when `VENDOR_API_VERIFY_SSL` is false

Responses are accepted if JSON contains either a wrapped `data` object or a root object with `invoice_no`. Non-success responses and unparseable bodies are logged (`Vendor invoice API` warnings in `storage/logs/laravel.log`) with status, URL, and a body preview—useful when Postman works but the app previously showed **Not found**.

## Routes and UI

| Method | Path | Name |
|--------|------|------|
| GET | `/admin/suppliers/{supplier}/vendor-invoices` | `admin.suppliers.vendor-invoices.index` |
| POST | `/admin/suppliers/{supplier}/vendor-invoices/lookup` | `admin.suppliers.vendor-invoices.lookup` |
| POST | `/admin/suppliers/{supplier}/vendor-invoices/import` | `admin.suppliers.vendor-invoices.import` |

Entry point: **Admin → Suppliers → supplier show → Import Invoices from API** (only for configured SAP codes).

## Code map

| Piece | Location |
|-------|----------|
| Config | `config/vendor_api.php` |
| HTTP + mapping | `app/Services/VendorInvoiceFetchService.php` |
| Admin controller | `app/Http/Controllers/Admin/VendorInvoiceImportController.php` |
| Form requests | `app/Http/Requests/Admin/LookupVendorInvoicesRequest.php`, `ImportVendorInvoicesRequest.php` |
| View | `resources/views/admin/suppliers/vendor-invoice-import.blade.php` |
| Supplier link | `resources/views/admin/suppliers/show.blade.php` |
| Routes | `routes/admin.php` |
| Tests | `tests/Feature/VendorInvoiceImportTest.php` |

## External API reference

Vendor-side endpoints and payload shapes (for integrators testing with Postman) remain documented in [customer-invoice-api-reference.md](customer-invoice-api-reference.md). Sample JSON: `docs/get-invoices.txt`, `docs/invoice-detail.txt`.

## Troubleshooting

| Symptom | Checks |
|---------|--------|
| **Not found** for valid numbers | Confirm `.env` base URL is root only; token matches Postman; run `config:clear`; inspect log lines for HTTP status and HTML vs JSON body. |
| TLS / redirect issues | Prefer `https://` in URL if the site redirects; or temporarily `VENDOR_API_VERIFY_SSL=false` locally. |
| 404 on import page | Supplier `sap_code` missing or not listed in `vendor_api.vendors`. |
| Warning about missing credentials | Set both `*_API_URL` and `*_API_TOKEN` for that SAP code. |
