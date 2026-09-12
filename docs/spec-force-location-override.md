# Spec — Force Location Override (`cur_loc`) untuk Additional Document hasil auto-sync GRPO

Status: disetujui Iwan (12 Sep 2026) · Scope: v1

## Masalah

Dokumen DO yang di-auto-sync dari GRPO SAP dibuat dengan `cur_loc` default yang salah
(`000HACC`, kemudian diperbaiki ke `000HLOG` di commit 43a36bc). Dokumen lama yang sudah
terlanjur masuk alur distribusi tidak bisa dikoreksi: `canChangeLocationManually()` mengunci
`cur_loc` selama dokumen punya riwayat distribusi, dan kunci ini berlaku untuk SEMUA role
termasuk superadmin.

Data produksi (12 Sep 2026): 6.826 dokumen DO auto-sync GRPO, 438 masih di `000HACC`
(431 di antaranya terkunci karena sudah punya riwayat distribusi), total 27.974 additional
document terkunci.

## Keputusan

1. **Role**: `superadmin`, `admin`, `accounting` — lewat permission baru `override-document-location`.
2. **Cakupan**: hanya additional document dengan `grpo_no` terisi (penanda hasil auto-sync SAP).
   Invoice dan non-GRPO tidak termasuk.
3. **Dokumen in-transit**: koreksi DITOLAK kalau dokumen masih terikat distribusi berstatus
   `sent` dan `received_at` NULL. Admin harus membatalkan distribusi itu dulu (`cancelSent`).
   Alasan: kalau tidak, saat distribusi diterima `cur_loc` akan ditimpa kembali.
4. **Efek samping**: `cur_loc` → lokasi baru, `distribution_status` → `available`, supaya
   dokumen ikut daftar distribusi lokasi baru.
5. **Audit**: setiap koreksi wajib beralasan (min 10 karakter) dan dicatat di tabel
   `document_location_overrides` + ditampilkan di halaman edit dokumen.

## Perubahan teknis

- Migration `document_location_overrides`: `document_type`, `document_id` (index),
  `from_loc`, `to_loc`, `reason` (text), `overridden_by`, timestamps.
- Model `App\Models\DocumentLocationOverride`.
- Permission `override-document-location` di `RolePermissionSeeder` → superadmin, admin, accounting.
- Route `POST additional-documents/{additionalDocument}/force-location`
  (`additional-documents.force-location`) di `routes/additional-docs.php`.
- `AdditionalDocumentController::forceLocationOverride()` — guard berurutan:
  permission → `grpo_no` terisi → `to_loc` valid & aktif → berbeda dari `cur_loc` →
  alasan min 10 karakter → tidak ada distribusi `sent` yang belum diterima → update + log.
- UI: tombol **Koreksi Lokasi** (danger) di modal halaman `additional_documents/edit.blade.php`
  saat dokumen terkunci + `grpo_no` terisi + user punya permission; plus tabel riwayat koreksi.
- Test: `tests/Feature/AdditionalDocumentLocationOverrideTest.php`.

## Non-goals (v1)

- Tidak untuk Invoice.
- Tidak ada bulk/CSV override (koreksi massal 438 dokumen dilakukan terpisah dengan approval + backup).
- Tidak mengubah `canChangeLocationManually()` — jalur edit normal tetap terkunci.
