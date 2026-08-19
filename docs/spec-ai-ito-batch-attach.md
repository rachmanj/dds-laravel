# Spec: AI-Assisted ITO Batch Attach (Split + OCR + Auto-Match)

**Feature**: Accounting scan banyak ITO sekaligus jadi 1 file PDF, upload ke DDS, lalu AI memecah per-halaman, membaca nomor ITO, mencocokkan ke record ITO yang sudah ada, dan auto-attach.
**Status**: Spec hasil grill (disetujui Iwan) — siap implementasi.
**Dependency**: Berinteraksi dengan [spec-ai-signature-matching.md](spec-ai-signature-matching.md) (chain ke signature verification setelah attach).

---

## 1. Goal

Menghilangkan pekerjaan manual: saat ini accounting harus scan tiap ITO jadi 1 file, lalu attach satu per satu ke record ITO di DDS. Dengan fitur ini:

- Accounting scan **satu batch ITO → 1 file PDF multi-halaman** (scanner ADF).
- Upload ke DDS → **AI split per halaman + OCR nomor ITO** → **auto-match ke record ITO** yang sudah ada (dari sync SAP) → **auto-attach** file ke masing-masing record.
- Yang tidak match / ambigu masuk **review queue** untuk diselesaikan manual oleh accounting.
- Setelah attach sukses, **chain** ke signature verification (fitur sebelumnya).

---

## 2. Scope

### Asumsi dasar (hasil grill)

- **Tiap ITO = 1 halaman** → split deterministik (1 halaman = 1 dokumen). AI **tidak** perlu deteksi batas antar-dokumen; AI hanya untuk **OCR nomor ITO** + matching.
- **Record ITO sudah ada di DDS** (hasil sync SAP), diidentifikasi oleh `document_number` (DocNum).
- **Kunci matching = nomor ITO** (`document_number`), dengan normalisasi (uppercase, hilangkan spasi/dash/prefix).

### In scope (fase 1)

1. **Upload batch** — 1 file PDF multi-halaman ke halaman baru "ITO Batch Import".
2. **Split + OCR** — pecah PDF per halaman → OCR nomor ITO tiap halaman (OpenRouter vision).
3. **Auto-match + attach** — cocokkan nomor ITO (ternormalisasi) ke `additional_documents` (type ITO). Cocok unik + confidence tinggi → simpan halaman sebagai file PDF single-page → attach ke record (`attachment`) + set `batch_no`.
4. **Review queue** — nomor tidak ketemu / match ganda / confidence rendah → antrean manual (accounting assign ke record, atau buat record baru, atau skip).
5. **Chain signature verification** — setelah attach sukses, dispatch `VerifyDocumentSignatureJob` (dari spec signature matching).

### Out of scope (fase 1 / nanti)

- Deteksi batas dokumen untuk ITO multi-halaman (asumsi 1 halaman).
- Auto-buat record ITO tanpa konfirmasi (selalu via review queue).
- Batch untuk dokumen selain ITO (struktur dibuat extensible, tapi fase 1 hanya ITO).
- Split dokumen yang datang sebagai banyak file terpisah (bukan 1 PDF).

---

## 3. Tech Decisions

- **Split PDF**: pakai `setasign/fpdf` + `setasign/fpdi` (sudah terpasang) untuk ekstrak halaman → PDF single-page. Referensi pola: `app/Services/PdfInvoiceFirstPageService.php`.
- **OCR nomor ITO**: kirim PDF single-page ke OpenRouter via plugin `file-parser` (pola `OpenRouterInvoiceExtractionService::callOpenRouterPdfFile`). Return JSON `{ ito_no, confidence }`. Suhu 0.1, `response_format: json_object`.
  - Fallback: kalau halaman punya text layer (PDF digital), pakai `smalot/pdfparser` dulu (cepat, gratis) sebelum panggil vision.
- **Matching**: normalisasi kedua sisi (`strtoupper` + hapus non-alphanumeric) → `AdditionalDocument::where('type_id', ITO)->where('document_number', $normalized)`. Match unik → auto-attach; >1 → ambiguous; 0 → not_found.
- **Service**: `app/Services/ItoBatchImportService.php` — koordinasi split → OCR → match → attach.
- **Job**: `app/Jobs/ProcessItoBatchImportJob.php` (ShouldQueue) — proses seluruh batch di background (N halaman bisa banyak), update progress.
- **Config**: `services.openrouter.batch_ocr_model` (`OPEN_ROUTER_BATCH_OCR_MODEL`, fallback `OPEN_ROUTER_MODEL`) + `batch_ocr_timeout`.
- **Storage**: split page disimpan sebagai PDF di disk `public` (`attachments/`), konsisten dengan attachment existing.
- **Chain**: setelah attach → `VerifyDocumentSignatureJob::dispatch($document)` (dari spec signature matching).

---

## 4. DB Changes

### Tabel baru

```text
ito_batch_imports
  - id
  - filename (string)               # nama file asli
  - stored_path (string)            # path PDF batch di disk
  - total_pages (integer)
  - status (string)                 # pending / processing / processed / partial / failed
  - created_by (FK users)
  - timestamps

ito_batch_items
  - id
  - batch_id (FK ito_batch_imports, cascade)
  - page_number (integer)
  - extracted_ito_no (string, nullable)    # hasil OCR
  - matched_document_id (FK additional_documents, nullable)
  - status (string)                 # pending / matched / not_found / ambiguous / low_confidence / skipped
  - confidence (decimal 4,3, nullable)
  - attached_path (string, nullable)        # path PDF single-page hasil split
  - resolved_by (FK users, nullable)
  - resolved_at (timestamp, nullable)
  - timestamps
```

- `additional_documents` tidak perlu kolom baru — memakai `attachment` + `batch_no` yang sudah ada.
- `batch_no` pada `additional_documents` (sudah ada) dipakai untuk mengelompokkan ITO hasil batch ini (konsisten dengan `ItoImport`).

### Migration & Seeder

- Migration tabel di atas.
- Permission baru `manage-ito-batch-import` → role `accounting`, `admin`, `superadmin` (pola add-permission migration existing).

---

## 5. UI/UX

### Halaman "ITO Batch Import" (Accounting/Admin)

- Menu di `layouts/partials/menu/additional-documents.blade.php` → **"ITO Batch Import"** (gate `manage-ito-batch-import`).
- Form upload 1 file PDF (Dropzone/`custom-file`), tampilkan progress saat diproses (polling status batch).
- Setelah selesai, ringkasan: total halaman, **matched**, **review needed**.

### Review queue

- List item bermasalah (`not_found` / `ambiguous` / `low_confidence`):
  - Preview halaman (gambar/PDF) + `extracted_ito_no` + skor confidence.
  - Tombol: **"Assign to record"** (pilih record ITO yang cocok), **"Create new"** (buat record ITO baru lalu attach), **"Skip"**.
- Item yang sudah matched bisa dilihat + bisa **re-match** manual bila salah.

### Halaman Additional Document (ITO) show

- Badge `batch_no` (sudah ada field) + tombol download/preview attachment (existing) — tidak ada UI baru.

---

## 6. API Endpoints (web routes, konsisten existing)

```text
GET  /ito-batch-import                     -> form upload + daftar batch
POST /ito-batch-import                     -> upload PDF + dispatch ProcessItoBatchImportJob
GET  /ito-batch-import/{batch}             -> detail batch + item status (polling)
GET  /ito-batch-import/{batch}/review      -> review queue (item bermasalah)
POST /ito-batch-import/items/{item}/assign    -> body: document_id (assign manual)
POST /ito-batch-import/items/{item}/create    -> body: { document_number, ... } (buat record baru + attach)
POST /ito-batch-import/items/{item}/skip      -> skip item
```

Semua route di dalam grup auth + permission `manage-ito-batch-import`.

---

## 7. Risks

- **OCR nomor ITO salah baca** (angka mirip: 0/O, 1/I, format bervariasi). → Mitigasi: normalisasi + confidence threshold + review queue.
- **Match salah ke record yang mirip** (nomor hampir sama). → Mitigasi: match harus **unik**; >1 → ambiguous ke review.
- **Halaman kosong / halaman cover di tengah batch** (scanner ADF kadang sisip halaman). → Mitigasi: halaman tanpa nomor ITO terdeteksi → status `low_confidence` → review; jangan auto-attach.
- **Batch besar = banyak panggilan AI + lama**. → Mitigasi: async job, progress, `text layer` shortcut bila tersedia, timeout configurable.
- **Duplikat attach** (batch diupload ulang). → Mitigasi: cek dokumen sudah punya `attachment` sebelum timpa; konfirmasi di review.
- **Ketergantungan chain signature verification** (spec lain belum diimplement). → Mitigasi: chain bersifat best-effort (kalau job/signature belum ada, attach tetap sukses + log).
