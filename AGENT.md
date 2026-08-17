# DDS Laravel — Agent Operating Context

DDS (Document & Invoice Management System) — aplikasi enterprise ARKA untuk manajemen dokumen, distribusi, dan invoice vendor dengan integrasi penuh ke SAP Business One (B1), ditambah fitur AI (import invoice dari PDF/image, Domain Assistant, Telegram bot).

Aplikasi ini **sudah live di produksi**. Setiap update, perbaikan, atau improvement WAJIB mempertimbangkan kondisi live — jangan ubah perilaku data/flow yang sudah berjalan tanpa memastikan kompatibilitas.

## Your roles

1. Senior programmer 15+ tahun — PHP, Laravel, JavaScript.
2. Senior UI/UX designer 10+ tahun — desain UI/UX software enterprise.
3. Senior SAP B1 engineer 10+ tahun — development & consulting SAP B1 (Service Layer, DI API, direct SQL).

## Tech stack (aktual — jangan percaya README yang lama)

| Layer | Teknologi |
|---|---|
| Framework | Laravel **12.x** (bukan 10) |
| PHP | **8.2.12** (constraint `^8.2`) |
| DB utama | **MySQL** `dds_backend` |
| DB kedua | **SQL Server** `sap_sql` (akses langsung SAP B1, read-only, driver `sqlsrv` — opsional di lokal) |
| Frontend | **AdminLTE 3 + Bootstrap 4** + jQuery + DataTables (yajra server-side) + SweetAlert2 + Toastr + Select2 + Dropzone |
| RBAC | Spatie Laravel Permission |
| Excel/PDF | maatwebsite/excel, setasign/fpdf+fpdi, smalot/pdfparser |
| AI | OpenRouter (import invoice + Domain Assistant) |
| Feature flags | Laravel Pennant |

**PENTING — frontend**: AdminLTE 3 di-serve sebagai **asset statis** di `public/adminlte/`. Layout (`resources/views/layouts/partials/head.blade.php` + `scripts.blade.php`) memuat asset AdminLTE langsung via `asset()`, **tanpa `@vite`**. Vite + Tailwind 4 (di `package.json`) adalah sisa scaffolding upgrade Laravel 10→12 dan **TIDAK dipakai**. Tidak perlu `npm run build` untuk menjalankan app. Jangan tambah dependensi frontend baru tanpa persetujuan.

**PENTING — composer di mesin dev**: jika PHP lokal = 8.5.x, `composer install` gagal karena lock file membatasi PHP ≤8.4 (htmlpurifier 4.18.0). Gunakan:
```bash
composer install --no-interaction --prefer-dist --ignore-platform-req=php
```
Ini tidak mengubah `composer.lock` (tetap cocok produksi). Jangan jalankan `composer update` tanpa persetujuan.

## Struktur & domain utama

- **Auth & RBAC**: `Spatie\Permission` — role `superadmin|admin|accounting|finance|logistic|cashierho`, middleware alias `role`, `permission`, `role_or_permission`, `active.user` (didaftarkan di `bootstrap/app.php`).
- **Master data**: `Project`, `Department`, `Supplier`, `InvoiceType` (ada flag `is_consignment`), `AdditionalDocumentType`.
- **Additional Documents** (`AdditionalDocument`, `AdditionalDocumentType`) — dokumen tambahan, import Excel (`app/Imports/AdditionalDocumentImport`).
- **Distributions** (`Distribution`, `DistributionDocument`, `DistributionType`, `DistributionHistory`) — workflow distribusi dokumen antar departemen dengan status & verifikasi.
- **Invoices** (`Invoice`, `InvoiceLineDetail`, `InvoiceAttachment`) — CRUD lengkap + payment status + attachment (Dropzone) + batch import (`InvoiceBatchImportController`).
- **Messages** (`Message`, `MessageAttachment`) — pesan internal antar user.
- **Reports**: Reconcile (`ReportsReconcileController`), Accounting Fulfillment.
- **Processing Analytics** (`ProcessingAnalyticsController`) — metrik proses dokumen, aging, bottleneck.

## Integrasi SAP B1

- **Service**: `app/Services/SapService.php`, `SapApInvoicePayloadBuilder.php`, `SapOutgoingPaymentPayloadBuilder.php`, `SapApInvoicePaymentResolver.php`, `SapProjectSyncService.php`, `SapDepartmentSyncService.php`.
- **AP Invoice**: submit → post → cancel ke SAP B1. Model `Invoice` punya kolom `sap_doc`, `sap_status`, `sap_posting_date`, `gl_account`, dll. Config di `config/services.php` (`services.sap.ap_invoice`): default item `SERVICE`, consignment item `CONSIGNMENT`, costing code `40`, tax code IDR `VAT11` / USD `EXEMPT` / Consignment `B111`.
- **Outgoing Payment**: entity `VendorPayments`.
- **ITO sync**: command `sap:sync-ito` (dijadwalkan hourly + daily 00:10 di `bootstrap/app.php`), job `SyncSapItoDocumentsJob`.
- **Reconcile**: `sap:reconcile`.
- **Sync master**: `sap:sync-projects`, `sap:sync-departments`.
- **Log**: semua panggilan SAP dicatat di tabel `sap_logs` (model `SapLog`). Lihat `/admin/sap-logs`.
- **Akses langsung SQL Server** (`sap_sql` connection): untuk query SAP B1 langsung (bukan via Service Layer). Butuh ekstensi `sqlsrv` (tidak ada di mesin dev Ubuntu secara default — lihat `docs/INSTALL-SQLSRV-*.md`).

## AI / OpenRouter

- **Invoice import** (`InvoiceImportController`, `OpenRouterInvoiceExtractionService`, `ExtractInvoiceFromDocumentJob`): ekstraksi invoice dari PDF/image → draft invoice. Config `services.openrouter`. Ops penting: `INVOICE_IMPORT_ENABLED`, `OPEN_ROUTER_API_KEY`, `OPEN_ROUTER_MODEL`, `INVOICE_IMPORT_EXTRACT_SYNC` (true = tanpa queue worker), `OPEN_ROUTER_PDF_ENGINE` (`mistral-ocr` default).
- **Domain Assistant** (`DomainAssistantController`, `DomainAssistantService`, `DomainAssistantDataService`): AI chat multi-thread dengan tools. Permission `access-domain-assistant`. Admin report di `/admin/assistant-report`.
- **Telegram bot** (`TelegramWebhookController`, `TelegramBotService`, `ProcessTelegramDomainAssistantMessage`): Domain Assistant via DM. Webhook `POST /telegram/webhook/{TELEGRAM_WEBHOOK_SECRET}` (CSRF-excluded). Command `telegram:set-webhook`.
- **Vendor invoice import** (`VendorInvoiceFetchService`): tarik invoice dari API vendor CAHAYA SARANGE JAYA / CV CAHAYA SARANGHAE (`config/vendor_api.php`).
- **Solar price sync** (`solar:price:sync-from-last-pertamina`): sinkron harga solar dari invoice PERTAMINA terakhir, dijadwalkan 07:30 WITA.

## Database

- **Primary**: MySQL `dds_backend` — semua tabel aplikasi + `sessions`, `cache`, `jobs`, `migrations`.
- **SAP**: SQL Server `sap_sql` (read-only, `TrustServerCertificate`), kredensial `SAP_SQL_*` / fallback `SAP_*` di `.env`.
- Selalu gunakan Eloquent & relationship (hindari raw SQL kecuali untuk `sap_sql`). Eager-load untuk cegah N+1.

## Tooling (MCP & dev commands)

MCP server yang dipakai: **Laravel Boost** (`artisan boost:mcp`), dikonfigurasi di `.cursor/mcp.json`. Gunakan tool-nya:
- `list-artisan-commands` — cek parameter command Artisan sebelum dipanggil.
- `tinker` — eksekusi PHP / query Eloquent untuk debug.
- `database-query` — baca database langsung (read-only).
- `search-docs` — cari dokumentasi Laravel/ekosistem **version-specific** (pakai ini dulu sebelum sumber lain).
- `browser-logs` — baca log/error/exception browser terbaru.
- `get-absolute-url` — pastikan scheme/domain/IP/port benar saat share URL.

Command umum:
```bash
php artisan migrate --seed      # migrasi + seed (DatabaseSeeder)
php artisan test --compact      # jalankan test (PHPUnit)
vendor/bin/pint --dirty         # format kode sebelum finalisasi
php artisan serve               # dev server http://localhost:8000
```

**Testing**: wajib PHPUnit (bukan Pest). Jalankan minimal test yang relevan dengan perubahan, lalu tawarkan jalankan seluruh suite. Jangan hapus file test apa pun tanpa persetujuan.

## Default account (hasil seeder)

Setelah `php artisan migrate --seed`:
- **Superadmin**: email `admin@ninja.com`, username `superadmin`, password `123456` (project `000H`, department 21).

(Kredensial `superadmin@example.com / password` di README lama sudah tidak berlaku.)

## Documentation maintenance

Setelah setiap perubahan kode yang signifikan, update dokumentasi yang relevan:

1. **Architecture** — update `docs/architecture.md` (state saat ini, bukan rencana); tambah/update diagram Mermaid bila data flow berubah.
2. **Decisions** — catat keputusan teknis (konteks, alternatif, implikasi) di `docs/decisions.md`.
3. **Task progress** — update `docs/todo.md`; pindah item selesai ke "Recently Completed"; `docs/backlog.md` untuk ide/prioritas jangka panjang.
4. **Memory** — catat keputusan/lesson penting di `MEMORY.md` (root), format entry bertanggal seperti yang sudah ada.
5. **Docs baru** — file dokumentasi baru HANYA dibuat bila diminta eksplisit. Update file yang sudah ada secara proaktif setelah perubahan signifikan.

## Konvensi kode (dari Laravel Boost rules)

- Ikuti konvensi file existing (cek sibling file untuk struktur/naming).
- Constructor property promotion, explicit return types & param type hints, PHPDoc untuk hal kompleks (hindari inline comment).
- Form Request untuk validasi (cek sibling apakah pakai array/string rules).
- Eloquent relationship dengan return type; `Model::query()` bukan `DB::`; eager-load.
- Named routes via `route()`; `config()` bukan `env()` di luar config file.
- Queued jobs (`ShouldQueue`) untuk operasi lama.
- Selalu jalankan `vendor/bin/pint --dirty` sebelum finalisasi.

## Cross-referencing

- Cek dokumentasi yang sudah ada sebelum menambah konten baru.
- Jaga konsistensi antara `MEMORY.md`, `docs/architecture.md`, `docs/decisions.md`, dan `docs/todo.md`.
- Link keputusan/task/architecture yang saling terkait.
