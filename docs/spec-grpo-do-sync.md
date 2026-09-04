# Spec: GRPO → DO Auto-Sync (create DO records from SAP GRPO)

**Feature**: DDS secara periodik menarik data GRPO (OPDN) dari SAP B1 yang **dibuat hari ini/kemarin** → membuat record **Delivery Order (DO)** sebagai additional document, berdasarkan data DO yang ditulis user di field GRPO.
**Status**: Spec hasil grill (disetujui Iwan).
**Dependency**: SAP direct SQL (`sap_sql` connection, user `dds_intern`) — sudah terbukti akses dari dev & produksi.

---

## 1. Goal

Praktek lapangan: saat menerima barang, user membuat GRPO di SAP dan menulis **nomor DO** di `OPDN.NumAtCard` + **tanggal DO** di `OPDN.TaxDate`. GRPO sering dibuat **backdate**. Saat ini record DO di DDS dibuat **manual** oleh user (document_number 10-digit). Tujuan:

- DDS otomatis membuat record DO (type **"Delivery Order (DO)"**) dari GRPO yang dibuat hari ini → user tidak perlu bikin manual.
- Tarikan periodik: **tiap 1 jam (24 jam)** untuk GRPO `CreateDate = hari ini` + **07:00 WITA harian** untuk GRPO `CreateDate = kemarin` (antisipasi GRPO dibuat di luar jam kantor; site beroperasi 24 jam).
- **Idempoten**: GRPO yang sama tidak pernah membuat DO duplikat.

---

## 2. Scope

### In scope

1. Command artisan `sap:sync-do-from-grpo` + job `SyncSapDoFromGrpoJob` (ShouldQueue) — pola `sap:sync-ito` / `SyncSapItoDocumentsJob`.
2. Query GRPO (OPDN) via `sap_sql`: non-canceled (`Canceled='N'`), `NumAtCard` non-kosong, filter `CreateDate` (hari ini / kemarin / range `--start`/`--end`).
3. Mapping & pembuatan record `additional_documents` type "Delivery Order (DO)".
4. Logging ke `sap_logs` (pola sync ITO) + skip duplikat.
5. Penjadwalan di `bootstrap/app.php` (timezone WITA): hourly + daily 07:00.

### Out of scope

- Tidak pull GRPO lama (kecuali via `--start`/`--end` manual).
- Tidak upload scan / signature verification otomatis (record DO baru tanpa attachment → `signature_status` menunggu scan diupload, lalu flow existing yang jalan).
- Tidak update record DO yang sudah ada (create-only).
- Tidak handling "GRPO tanpa NumAtCard" (dilewati).

---

## 3. Tech Decisions

- **Koneksi**: `DB::connection('sap_sql')` — creds dari `.env` (`SAP_SQL_*`). Di lokal dev belum ada → tambah ke `.env` lokal dari .env produksi untuk test (user `dds_intern`, read-only).
- **Command**: `app/Console/Commands/SapSyncDoFromGrpoCommand.php` — opsi `--today` (default), `--yesterday`, `--start=Y-m-d` + `--end=Y-m-d`; `--user` (default 1 = superadmin); audit context di `sap_logs` (pola `SyncSapItoDocumentsJob`).
- **Job**: `SyncSapDoFromGrpoJob` — terima target date + audit context; ambil data, buat record, log hasil (fetched/created/skipped).
- **SQL utama** (query GRPO + DO info):

```sql
SELECT h.DocEntry, h.DocNum, h.CardCode, oc.CardName, h.NumAtCard,
       h.TaxDate, h.DocDate, CONVERT(date, h.CreateDate) AS CreateDate
FROM OPDN h
LEFT JOIN OCRD oc ON oc.CardCode = h.CardCode
WHERE h.Canceled = 'N'
  AND h.NumAtCard IS NOT NULL AND LTRIM(RTRIM(h.NumAtCard)) <> ''
  AND CONVERT(date, h.CreateDate) = @targetDate
```

- **PO number & project** (dari baris GRPO → base PO, per GRPO ambil baris pertama):

```sql
SELECT TOP 1 CONVERT(varchar, p.DocNum) AS PoNo, l.Project
FROM PDN1 d1
JOIN OPOR p ON p.DocEntry = d1.BaseEntry AND d1.BaseType = 22
JOIN POR1 l ON l.DocEntry = d1.BaseEntry AND l.LineNum = d1.BaseLine
WHERE d1.DocEntry = @opdnDocEntry
```

- **Mapping → `additional_documents`:**

| DDS field | Sumber |
|---|---|
| `type_id` | "Delivery Order (DO)" (id 9) |
| `document_number` | `OPDN.NumAtCard` (apa adanya) |
| `document_date` | `OPDN.TaxDate` |
| `receive_date` | `OPDN.DocDate` (tanggal GRPO) |
| `po_no` | base PO `OPOR.DocNum` (via PDN1) |
| `vendor_code` | `OPDN.CardCode` |
| `project` | `POR1.Project` (line base PO, contoh `022C`) |
| `grpo_no` | `OPDN.DocNum` ← **dedup key** |
| `cur_loc` | `'000HACC'` (default, sama dgn DO manual) |
| `created_by` | 1 (superadmin) |
| `status` | `'open'` |
| `remarks` | `'Auto-sync dari GRPO {DocNum}'` |

- **Dedup**: sebelum create, cek `AdditionalDocument::where('type_id', $doTypeId)->where('grpo_no', $docNum)->exists()` → skip.
- **Timezone**: target date dihitung di `Asia/Makassar` (WITA), konsisten dengan scheduler existing.

---

## 4. DB Changes

Tidak ada tabel/kolom baru (semua field sudah ada: `grpo_no`, `vendor_code`, `project`, dst).

Opsional (disarankan): migration index `additional_documents.grpo_no` (nullable) — mempercepat lookup dedup.

---

## 5. UI/UX

Tidak ada halaman baru. Record DO hasil sync muncul di list "Additional Documents" seperti biasa (type "Delivery Order (DO)").

Admin bisa cek riwayat sync di halaman SAP ITO sync-style (`/admin/sap-sync-ito` punya pola serupa) — untuk fase 1 cukup lewat `sap_logs` / command manual. (Opsional: tambahkan filter type DO di halaman sync existing.)

---

## 6. API Endpoints

Tidak ada endpoint web baru. Hanya command artisan:

```text
php artisan sap:sync-do-from-grpo            # GRPO dibuat hari ini
php artisan sap:sync-do-from-grpo --yesterday
php artisan sap:sync-do-from-grpo --start=2026-09-01 --end=2026-09-03   # backfill manual
```

Schedule (`bootstrap/app.php`, WITA, `withoutOverlapping`):
```text
* * * * * (hourly)      sap:sync-do-from-grpo --today
dailyAt('07:00')        sap:sync-do-from-grpo --yesterday
```

---

## 7. Risks

- **NumAtCard bukan selalu nomor DO** (format vendor beragam: `DO-TV-...`, `26/019753`, angka polos, ada yang berisi "PS" dll). → Per Iwan: ambil semua non-kosong apa adanya.
- **Asumsi TaxDate = tanggal DO**: di sampel TaxDate selalu = DocDate. Dipakai sesuai instruksi Iwan (kalau ternyata salah, mapping tinggal diganti).
- **Duplikat `document_number`** (satu DO dipecah jadi 2 GRPO partial delivery) → masih bisa terjadi; dedup per `grpo_no` mencegah duplikat GRPO yang sama. Document_number TIDAK unique di additional_documents (aman).
- **GRPO multi-PO / multi-project** → ambil baris pertama (asumsi 1 GRPO ≈ 1 PO; sampel konsisten).
- **SAP down / query gagal** → job catch exception, status `failed` di `sap_logs`, retry via queue; tanpa data hilang (dedup memungkinkan re-run aman).
- **GRPO dibuat tengah malam** → ter-catch oleh job 07:00 WITA (CreateDate = kemarin).
