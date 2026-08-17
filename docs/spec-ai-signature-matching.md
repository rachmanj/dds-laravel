# Spec: AI-Assisted DO Signature Matching

**Feature**: AI membantu accounting mencocokkan tanda tangan penerima barang pada Delivery Order (DO) dengan specimen tanda tangan yang terdaftar di DDS.
**Status**: Spec hasil grill (disetujui Iwan) — siap implementasi.
**Mode**: Assist + human-in-the-loop (AI memberi skor + crop + verdict, manusia yang konfirmasi).

---

## 1. Goal

Menggantikan proses manual saat ini — penerima barang mendaftarkan specimen tanda tangan via email ke accounting, lalu accounting mencocokkan secara manual — dengan alur berbantuan AI di dalam DDS:

- Tanda tangan specimen terdaftar terstruktur di DDS (bukan via email).
- Saat DO diupload, AI otomatis membandingkan tanda tangan pada DO terhadap specimen kandidat (dari project yang dipilih) dan menghasilkan **top-K kandidat + skor confidence + crop + verdict**.
- Accounting mengonfirmasi hasil (memilih petugas yang cocok, atau menandai "tidak cocok").
- DO yang belum terverifikasi / tidak cocok **memblokir submit invoice** sampai di-override oleh accounting dengan alasan.

AI bukan gerbang final — keputusan akhir tetap di tangan accounting.

---

## 2. Scope

### In scope (fase 1)

1. **Registry specimen tanda tangan** (CRUD) untuk entitas "Petugas/Penerima barang" (karyawan ARKA, bukan harus `users` DDS).
   - Satu petugas bisa terdaftar di **banyak project** (many-to-many).
   - Satu petugas bisa punya **beberapa gambar specimen** (variasi tanda tangan).
   - Hak akses: accounting + admin/superadmin (permission `manage-signature-specimens`).
2. **Matching 1:N** (N ≤ 30 total specimen). User memilih **project** untuk menentukan kandidat specimen.
   - Open-set: AI boleh menjawab **"tidak cocok / unknown"**.
3. **Trigger otomatis saat upload DO** (background job) + tombol **Re-verify** manual (ganti project).
4. **Hasil**: top-K (default 3) kandidat + skor + crop + verdict `matched / uncertain / no_match`.
5. **Konfirmasi & override**: accounting memilih petugas yang cocok, atau override "no match" dengan alasan.
6. **Guard submit invoice**: blokir submit bila ada DO terkait yang belum verified / no_match tanpa override.

### Out of scope (fase 1 / nanti)

- Auto-suggest project dari lokasi/PO (user pilih project manual dulu).
- Auto-verification penuh tanpa manusia.
- Deteksi/crop signature region terpisah dengan model khusus (pakai crop langsung dari hasil vision model, atau full-page).
- PWA / capture kamera langsung.
- Matching multi-signature dalam satu DO (fokus 1 tanda tangan penerima utama).

---

## 3. Tech Decisions

- **AI provider**: OpenRouter (key & config sudah ada di `config/services.php` → `services.openrouter`).
- **Service baru**: `app/Services/SignatureMatchingService.php` — reuse pola `OpenRouterInvoiceExtractionService` (HTTP → `/chat/completions`, vision `image_url` / PDF via `file` + `file-parser`).
- **Model**: config baru `OPEN_ROUTER_SIGNATURE_MODEL` (default fallback ke `OPEN_ROUTER_MODEL`). Vision-capable. Suhu rendah (0.1), `response_format: json_object`.
- **Strategi matching**: pairwise per kandidat (N kecil, ≤30, makin kecil setelah filter project). Tiap kandidat → satu panggilan yang mengembalikan `{ score, verdict, reasoning }`. Alternatif optimasi (batch semua specimen dalam 1 prompt) dipertimbangkan saat implementasi bila N per project besar.
- **Prompt**: minta model bedakan **tanda tangan tulisan tangan** dari **stempel/cap** dan **nama tercetak**; beri opsi eksplisit `no_match`; larang menebak nama bila ragu.
- **Job**: `app/Jobs/VerifyDoSignatureJob.php` (ShouldQueue) — ambil DO (image/PDF) + specimen kandidat, panggil service, tulis hasil.
- **Threshold** (config `services.openrouter.signature_*`):
  - `>= signature_match_threshold` (default 0.75) → `matched`
  - `>= signature_uncertain_threshold` (default 0.45) → `uncertain`
  - `< uncertain` → `no_match`
- **Audit**: hasil per-run disimpan (mirip pola `sap_logs` / `assistant_request_logs`).

---

## 4. DB Changes

### Tabel baru

```text
signature_specimens
  - id
  - name (string)
  - nik (string, nullable)
  - department_id (FK departments, nullable)
  - is_active (boolean, default true)
  - timestamps

signature_specimen_project (pivot, many-to-many)
  - id
  - specimen_id (FK signature_specimens, cascade)
  - project_id (FK projects, cascade)
  - unique (specimen_id, project_id)

signature_specimen_images
  - id
  - specimen_id (FK signature_specimens, cascade)
  - path (string)            # relatif ke disk lokal (filesystems)
  - timestamps

signature_match_results (audit)
  - id
  - additional_document_id (FK additional_documents)
  - specimen_id (FK signature_specimens, nullable)
  - score (decimal 4,3, nullable)   # 0.000–1.000
  - verdict (string)                # matched / uncertain / no_match
  - model (string)
  - raw_response (text, nullable)
  - created_at
```

### Kolom baru di `additional_documents`

```text
  - signature_status (string, nullable)     # pending / matched / uncertain / no_match / skipped
  - signature_project_id (FK projects, nullable)   # project yang dipakai saat matching
  - signature_checked_by (FK users, nullable)
  - signature_checked_at (timestamp, nullable)
  - signature_override_reason (text, nullable)
  - signature_override_by (FK users, nullable)
  - signature_override_at (timestamp, nullable)
```

- Matching hanya berlaku untuk `additional_documents.type_id` = **"Delivery Order (DO)"** (id 9 dari seeder).
- `signature_status = skipped` bila DO tidak punya `project` (tidak bisa auto-determine kandidat) — user jalankan Re-verify manual.

### Migration & Seeder

- Migration baru sesuai tabel/kolom di atas.
- Seeder permission: `manage-signature-specimens` → role `accounting`, `admin`, `superadmin`.
- Seeder menambah permission ke seeder `RolePermissionSeeder` (atau migration add-permission sesuai konvensi existing, lihat `2025_10_10_*` add-permission migrations).

---

## 5. UI/UX

### Registry specimen (Admin/Accounting)

- Menu baru di `layouts/partials/menu/master.blade.php` (atau section Admin) → **"Signature Specimens"** (gate: `manage-signature-specimens`).
- List (DataTables server-side, konsisten dengan halaman master lain): name, nik, department, projects, jumlah specimen image, is_active, actions.
- Create/Edit: name, nik, department, project (multi-select), upload 1..n gambar specimen (Dropzone, reuse pola attachment invoice).
- Delete: SweetAlert2 confirm.

### Halaman DO (AdditionalDocument show)

- Card **"Signature Verification"** menampilkan:
  - Badge status (`pending` / `matched` / `uncertain` / `no_match` / `skipped`).
  - Tombol **"Verify"** → modal pilih **project** → dispatch job → polling status.
  - Hasil: top-K kandidat dengan **crop tanda tangan DO** + **crop specimen** + skor + verdict.
  - Tombol **"Confirm as <name>"** (pilih kandidat → `signature_status = matched`).
  - Tombol **"Mark as no match"** (→ `no_match`, muncul form override reason).
- Auto-run saat upload DO (bila `project` terisi): badge `pending` → `matched/uncertain/no_match`.

### Invoice

- Di halaman invoice (detail/submit), tampilkan daftar DO terkait + status signature-nya (warning badge bila `pending/uncertain/no_match`).
- **Guard**: `submitToSap` / finalize invoice diblokir bila ada DO terkait dengan `signature_status` ∈ {`pending`, `uncertain`, `no_match`} **tanpa override**. Pesan error jelas + tunjuk DO mana yang belum beres.

---

## 6. API Endpoints (web routes, konsisten existing)

```text
# Specimen registry (Blade, gate manage-signature-specimens)
GET    /admin/signature-specimens                 -> index
GET    /admin/signature-specimens/create          -> create
POST   /admin/signature-specimens                 -> store
GET    /admin/signature-specimens/{id}/edit       -> edit
PUT    /admin/signature-specimens/{id}            -> update
DELETE /admin/signature-specimens/{id}            -> destroy

# Matching (additional documents)
POST /additional-documents/{id}/signature-verify     -> dispatch VerifyDoSignatureJob (body: project_id)
GET  /additional-documents/{id}/signature-status     -> polling status + top-K result
POST /additional-documents/{id}/signature-confirm    -> body: specimen_id (confirm matched)
POST /additional-documents/{id}/signature-override   -> body: reason (override no_match)
```

---

## 7. Risks

- **Akurasi tanda tangan rendah secara inheren** (ahli forensik pun bisa beda pendapat). → Mitigasi: human-in-the-loop, band `uncertain`, opsi `no_match`, threshold konservatif.
- **Salah tunjuk (false attribution) di 1:N**. → Mitigasi: opsi `no_match`, top-K + skor, manusia konfirmasi, jangan paksa pilih 1.
- **Stempel/cap & nama tercetak vs tanda tangan asli**. → Mitigasi: prompt eksplisit bedakan tulisan tangan vs cap/print.
- **Kualitas scan buruk (blur/resolusi rendah)**. → Mitigasi: crop, izinkan re-upload/re-scan, fallback manual.
- **Privasi data pribadi (tanda tangan = data pribadi)**. → Mitigasi: registry dibatasi permission, akses file via disk lokal terproteksi.
- **Biaya & latensi panggilan AI**. → Mitigasi: N kecil + filter project, async queue, cache hasil per DO.
- **Konsistensi model / hallucination**. → Mitigasi: suhu 0.1, JSON strict, parse gagal → retry sekali → fallback `uncertain`.
