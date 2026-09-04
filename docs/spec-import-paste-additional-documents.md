# Spec — Paste-Import Additional Documents (copas dari Excel)

## Goal

User (accounting/logistic) sering punya daftar dokumen di Excel sendiri (BAPP, SPB, BA, DO, dll — campur jenis per file). Template import-general sekarang cuma punya kolom DO/GR/MR, jadi user memaksakan dokumen jenis lain ke kolom DO → hasil salah/gagal (kasus file "BA & SPB": 0/56 sukses).

Solusi: **tanpa file & template** — user cukup **copy-paste baris dari Excel-nya langsung ke UI DDS**, pilih **jenis dokumen** dari dropdown, lalu DDS parse & buat record. Satu paste = satu jenis dokumen.

## Keputusan (hasil grill, terkunci)

| # | Keputusan |
|---|-----------|
| 1 | Alur: pilih jenis → paste → preview → centang/konfirmasi → simpan → ringkasan |
| 2 | Format copas: **2 kolom** — kolom 1 = nomor dokumen (teks utuh), kolom 2 = tanggal (opsional) |
| 3 | Tanggal per baris opsional; kalau kosong/tidak ada → pakai **tanggal fallback** yang dipilih di UI (default hari ini) |
| 4 | Fitur upload file lama **tetap dipertahankan**; paste-import ditambah di halaman yang sama |
| 5 | `description` kosong; `vendor_code` kosong; **project = dropdown opsional** |
| 6 | Preview dengan **checkbox per baris** (default semua tercentang) |
| 7 | **Permission sama** dengan import-general (`import-general-documents`) — tanpa permission baru |
| 8 | Hasil = **ringkasan sekali tampil** (dialog seperti sekarang), tanpa tabel riwayat |

## Alur UI

Halaman: `/additional-documents/import-general` (route `additional-documents.import-general`, permission `import-general-documents`) — ditambah **card/tab kedua "Paste Manual (Copas dari Excel)"** di samping upload file yang ada.

Komponen form:
1. **Jenis dokumen** — dropdown semua `additional_document_types` (ordered by `type_name`; label pakai `type_name` asli mis. "Berita Acara Penyelesaian Pekerjaan (BAPP)"). Wajib.
2. **Project** — dropdown opsional (tabel `projects`), boleh kosong.
3. **Tanggal fallback** — input date, default hari ini. Helper: "Dipakai untuk baris yang tidak mencantumkan tanggal."
4. **Textarea paste** — placeholder berisi contoh 2 baris: `109/BA-Rental Truck/ARKA/VIII/2026<TAB>4-Sep-2026`. Catatan: kolom 1 = nomor dokumen, kolom 2 = tanggal (opsional), 1 baris = 1 dokumen.
5. Tombol **Preview** (client-side JS) → tabel preview di bawahnya:
   - Kolom: checkbox | No | Nomor dokumen | Tanggal (teks mentah sesuai copasan)
   - Baris tanpa nomor (kolom 1 kosong) → ditandai merah "tanpa nomor" + **tidak tercentang** (tidak ikut di-import)
   - Counter "N baris akan di-import"
6. Tombol **Import** → POST → server parse ulang (otoritatif) → buat record → redirect balik + dialog **ringkasan** (pola `general_import_summary` yang sudah ada, lihat bawah).

Parsing preview client: split `\n` → tiap baris split `\t` (tab dari copas Excel); kalau tidak ada tab → seluruh baris = nomor. Ini hanya untuk tampilan; server parse ulang saat submit.

## Endpoint & Controller

- `POST additional-documents/process-paste-general-import` → `AdditionalDocumentController@processPasteGeneralImport` (baru), middleware `permission:import-general-documents`.
- Validasi request:
  - `document_type_id` → `required|exists:additional_document_types,id`
  - `project_id` → `nullable|exists:projects,id`
  - `fallback_date` → `nullable|date`
  - `lines` → `required|array|max:500` (tiap elemen = 1 baris mentah dari textarea, hanya baris tercentang)
- Buat method/service kecil `PasteDocumentImportService` (opsional, kalau logika > ~40 baris) atau langsung di controller — konsisten dengan gaya kode existing.

## Parsing baris (server, otoritatif)

Per elemen `lines[]`:
1. `trim()`; kalau hasil kosong → skip diam-diam (bukan error).
2. Split kolom: `explode("\t", ...)`; kalau cuma 1 segmen (tanpa tab) → `[baris, null]`; kalau ≥2 segmen → `[segmen0, segmen1]` (segmen sisanya **diabaikan**).
3. **Nomor dokumen** = segmen 0, `trim()`. Wajib non-kosong, max 255. Kalau kosong → error baris "Nomor dokumen kosong".
4. **Tanggal** = segmen 1 (kalau ada & non-kosong) → parse dengan urutan format:
   - `d-M-Y` / `d M Y` gaya `4-Sep-2026` (Carbon otomatis)
   - `d/m/Y`, `d-m-Y`, `d.m.Y` (prioritas **hari-bulan-tahun** karena konteks Indonesia)
   - `Y-m-d`
   - `m/d/Y` (fallback terakhir, hanya kalau segmen pertama ≤12)
   - Numerik murni ≥ 20000 → dianggap **Excel serial date** → konversi (1900-01-01 + n-2)
   - Gagal semua → error baris: `Format tanggal tidak dikenali: {nilai}` (record TIDAK dibuat — jangan diam-diam pakai fallback kalau user memberi tanggal tapi salah format).
5. Tidak ada kolom tanggal / kolom tanggal kosong → pakai `fallback_date` (dari UI; kalau kosong → hari ini).

## Pembuatan record

Per baris valid:
- `type_id` = `document_type_id` terpilih
- `document_number` = nomor hasil parse
- `document_date` = tanggal hasil parse/fallback
- `project` = `project_id` (nullable)
- `vendor_code` = null
- `description` = null
- `cur_loc` = `auth()->user()->department_location_code ?: 'DEFAULT'` (sama dengan import existing)
- `status` = `'open'` (sama dengan import existing)
- `created_by` = id user

**Duplikat → skip** (bukan error, bukan update): record dianggap duplikat kalau sudah ada `additional_documents` dengan `type_id` DAN `document_number` yang sama (tanpa syarat vendor — konsisten dengan dedup sync GRPO→DO & file lama yang masuk type salah). Dihitung sebagai "skipped".

**Batasan**: max 500 baris per submit (validasi `lines|max:500`); error kalau lebih.

## Ringkasan hasil

Redirect balik ke `additional-documents.import-general` dengan session (pola sama seperti `processGeneralImport`):
- `general_import_summary` = array: `success_count`, `skipped_count`, `error_count`, `errors` (array per baris: nomor baris + alasan, mis. `Baris 5: Format tanggal tidak dikenali: abc`), `total_processed`, `document_type` = nama type terpilih, `imported_at`, `duplicate_action=skip`, `check_duplicates=true`
- `general_import_success` = toastr message

→ Dialog summary yang sudah ada di `import-general.blade.php` otomatis tampil (kalau blade membaca key yang sama; kalau modal menampilkan `file_name`, isi `'(paste)'` atau sesuaikan label).

## Testing (wajib sebelum commit)

- Unit/feature test baru (`tests/Feature/PasteGeneralImportTest.php`):
  1. Posting baris valid → record dibuat (type/number/date/project/created_by benar, description & vendor_code null)
  2. Baris dengan tanggal `4-Sep-2026` → `document_date` 2026-09-04
  3. Baris tanpa tanggal → pakai fallback_date dari request
  4. Baris tanggal numeric serial (mis. `46269`) → jadi 2026-09-04
  5. Duplikat (type+nomor sama sudah ada) → skipped, tidak dibuat 2x
  6. Baris dengan tanggal tidak dikenal → error, tidak dibuat
  7. `lines` kosong / > 500 → validation error
  8. Permission: user tanpa `import-general-documents` → 403
- `php artisan test`, pint, `php -l`.

## Risiko / Catatan

- Ambigu tanggal `04/09/2026`: parser prioritaskan d/m/Y (Indonesia); user yang terbiasa mm/dd bisa salah — dapat dilihat di preview & diperbaiki.
- Copas dari Excel bisa membawa format tanggal sesuai **display cell** (`4-Sep-2026`, `04/09/2026`, dst) — parser multi-format menangani; kalau ada format baru yang gagal, error per baris jelas (bukan silent salah tanggal).
- Nilai kolom tambahan (kolom 3+) diabaikan — aman kalau user copas range lebih lebar dari 2 kolom.
- Tidak menyentuh fitur upload file / `GeneralDocumentImport` yang ada.
- Record yang dibuat via paste-import: kalau typenya `requires_signature` (DO/ITO) → mengikuti alur verifikasi tanda tangan normal (tidak ada perubahan).
