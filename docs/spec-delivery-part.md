# Spec — Logistics: Delivery Part + Cancel ITO

Status: **menunggu persetujuan Iwan** (hasil grill 30 Sep 2026)
Sumber permintaan: email tim logistik via Iwan ("fitur baru di DDS") + lampiran `Copy of Delivery Part All Site 2026.xlsx` + file query `query-list-ito-logistic.txt`.

## 1. Goal

1. Menggantikan kerja manual tim logistik yang copy-paste hasil query SAP ke Excel "Delivery Part All Site" dengan satu halaman di menu Logistic.
2. Kolom yang datanya ada di SAP terisi otomatis; hanya kolom yang memang milik site yang diisi manusia — dan isian itu tersimpan di DDS, bukan di Excel yang di-share lewat chat.
3. Menyediakan fitur Cancel ITO dari DDS.

## 2. Scope

### In

- Halaman `/logistics/delivery-part` (filter site + rentang tanggal ITO, default bulan berjalan).
- Kolom otomatis dari SAP (lihat §3.1).
- Kolom manual di DDS: No SPB, Remarks Barang, Tgl Delivery, Transporter, Unit & No Kendaraan, Ekspedisi Pengirim (pilihan tetap: TRUCK ARKA, EKSPEDISI NAMARA, EKSPEDISI JNE).
- Koreksi No ITO (karena SUMMARY menyebut kolom ini manual) — nilai asli SAP tetap dicatat, koreksi disimpan terpisah + riwayat.
- Keterangan = otomatis: ada nomor ITI → `COMPLETE`, belum ada → kosong.
- Tab **Input SPB Pengiriman** di halaman yang sama: No SPB (manual, bebas), Part Number, Description, QTY, UOM, Remarks Barang. Berdiri sendiri, tanpa relasi wajib ke baris delivery.
- Export Excel per site dengan urutan & nama kolom persis seperti file Excel sekarang.
- Tabel mapping warehouse → project yang bisa diatur (satu project boleh punya beberapa warehouse).
- Import data manual dari Excel 2026 (perintah artisan, wajib dry-run dulu).
- Cancel ITO dari DDS: hanya untuk ITO yang **belum punya ITI**, alasan wajib, terekam lengkap; eksekusi lewat helper DI API di Windows.
- Site: 017C, 022C, 026C, PRATASABA. Sheet PRATASABA diperlakukan sebagai baris manual penuh (data SAP-nya memang kosong: keramik, mie atom, kipas angin).

### Out

- Tidak menulis balik kolom manual ke SAP (Tgl Delivery / Delivery Status) — keputusan Iwan; SAP tidak diubah selain pada fitur cancel.
- Tidak membatalkan ITO yang sudah punya ITI.
- Tidak mengubah dokumen ITO yang sudah tersync di `additional_documents` (9.801 dokumen).
- Tidak menyentuh data site lain di luar 4 site itu pada fase ini (mapping tabel sudah siap untuk ditambah).

## 3. Tech decisions

### 3.1 Sumber data otomatis

Pakai query milik tim logistik (sudah diverifikasi cocok baris-per-baris dengan Excel) dijalankan lewat koneksi `sap_sql` DDS yang sudah ada (`SapService::executeItoSqlQuery()` sebagai pola):

- `ITO No` / `ITO Date` / `ITO Created Date` ← OWTR (`U_MIS_TransferType='OUT'`)
- `ITI No` / `ITI Date` ← OWTR pasangan via `T3.U_MIS_DocRefNo = T0.DocNum`
- `GRPO No` ← OPDN via `U_MIS_GRPONo`; `PO No` ← OPOR via PDN1.BaseRef; `PR No` ← OPRQ via OPOR.U_MIS_PRNo; `MR No` ← ORDR via POR1.U_MISMRNo
- `ItemCode`, `Dscription`, `UoM` (`unitMsr`), `Qty` ← WTR1
- `Unit No` ← OPRQ.U_MIS_UnitNo; `Vendor` ← OPDN.CardName
- `From Warehouse` ← `T0.Filler`; `To Warehouse` ← `T0.U_MIS_ToWarehouse`
- Cost: `OITW.AvgPrice` pada warehouse asal (dipakai kalau nanti perlu nilai, bukan untuk tampilan Excel)
- Baris dengan GRPO/PO/PR/Unit No kosong tetap ditampilkan apa adanya (memang ada di data).

Query dijalankan **live** saat halaman dibuka, filter tanggal ITO wajib, hasil di-cache 5 menit per (site, rentang tanggal) + tombol Refresh untuk membuang cache.

### 3.2 Site dari warehouse

Query SAP hanya mengembalikan kode warehouse (`02-SPT`, `08-SPT`, `17-SPT`, `02-APS`, `16-SPT`, …). Tabel mapping baru `logistics_warehouse_projects` (whs_code → project) diisi tim; halaman menampilkan daftar warehouse yang belum dipetakan supaya tidak ada baris "hilang" diam-diam.

### 3.3 Cancel ITO

Service Layer instalasi ini **tidak** menyediakan aksi cancel untuk dokumen transfer (hanya `..._Cancel2` untuk Invoices/CreditNotes/DeliveryNotes/PurchaseInvoices/PurchaseCreditNotes/PurchaseDeliveryNotes/PurchaseReturns/Returns/Assets; entity `StockTransfer` tidak punya field `Canceled`). Karena itu:

- DDS mencatat permintaan cancel (`delivery_part_ito_cancels`, status `requested`) + alasan + user.
- Helper **DI API (SAPbobsCOM) di Windows** mengambil permintaan lewat endpoint DDS, memanggil `StockTransfer.GetByKey(docEntry)` + `Cancel()`, lalu melaporkan hasil (sukses/gagal + pesan SAP).
- **Catatan penting:** DI API SAP hanya berjalan di Windows, sedangkan DDS berjalan di saphire-two (Linux). Jadi helper **tidak bisa** ditaruh di saphire-two — perlu mesin Windows yang punya SAP B1 client/DI (kandidat: ns15 `192.168.32.15` atau `.17`). DDS hanya menyediakan endpoint permintaan/hasil; keputusan host final menyusul di fase 7.
- DDS memverifikasi dengan membaca ulang `OWTR.CANCELED`; status baru `cancelled` kalau SAP benar-benar `Y`.
- **Dilarang** mengubah `CANCELED` lewat SQL langsung (melewati logika SAP, berisiko merusak stok).
- Kalau SAP menolak (mis. stok sudah terpakai), pesan SAP ditampilkan apa adanya; DDS tidak boleh mengklaim berhasil.

### 3.4 Arsitektur penyimpanan

- Baris otomatis: tidak disalin permanen — dibaca live dari SAP dan digabung dengan isian manual dari DDS berdasarkan kunci `(ITO No, ItemCode, Unit No)`.
- Baris manual (PRATASABA, atau Excel yang tidak ketemu di SAP): tersimpan penuh di tabel DDS.
- Export Excel = gabungan keduanya, urutan kolom mengikuti file tim.

## 4. DB changes

| Tabel | Isi pokok |
|---|---|
| `logistics_warehouse_projects` | `whs_code` (unik), `project_id` → projects, `is_active`, timestamps |
| `delivery_part_entries` | `project_id`, `ito_no`, `item_code`, `unit_no`, `source` (`sap`/`manual`), `no_spb`, `remarks_barang`, `tgl_delivery`, `transporter`, `unit_kendaraan`, `ekspedisi`, `ito_no_override`, `created_by`, `updated_by`, timestamps; unik `(ito_no, item_code, unit_no)` untuk baris SAP |
| `delivery_part_entry_histories` | `entry_id`, `field`, `old_value`, `new_value`, `user_id`, `created_at` (riwayat perubahan kolom manual) |
| `delivery_part_spb` + `delivery_part_spb_items` | header SPB (project, no_spb, tanggal, remarks) + barisnya (part number, description, qty, uom, remarks) |
| `delivery_part_ito_cancels` | `ito_no`, `doc_entry`, `project_id`, `reason`, `status` (`requested`/`processing`/`cancelled`/`failed`), `requested_by`, `requested_at`, `executed_at`, `sap_message`, `attempts` |
| permissions | `view-delivery-part`, `edit-delivery-part`, `cancel-ito` (seeder, ditautkan ke role yang sudah ada sesuai pola Logistics) |

## 5. UI/UX

- Menu Logistic: tambah **Delivery Part** (sejajar dengan Inventory, GRPO, Pemakaian, Categories).
- Halaman index: filter site, rentang tanggal ITO, pencarian; tabel server-side (pola DataTables seperti halaman Logistics lain); kolom manual bisa diedit lewat modal per baris; badge `COMPLETE` bila ITI sudah ada; tombol **Export Excel** dan **Refresh**.
- Tab **Input SPB**: form input + daftar SPB yang sudah diinput, filter site/tanggal.
- Dialog **Cancel ITO**: tampilkan No ITO, item, qty, tujuan; peringatan "hanya bisa dibatalkan selama belum ada ITI"; alasan wajib diisi; setelah dikirim tampil status `menunggu SAP` / `dibatalkan` / `gagal + pesan SAP`.
- Halaman admin kecil untuk mapping warehouse → project (CRUD + daftar yang belum dipetakan).
- Bahasa Indonesia, gaya halaman Logistic yang sudah ada, angka ringkas.

## 6. API endpoints

```
GET    /logistics/delivery-part                        index (permission view-delivery-part)
GET    /logistics/delivery-part/data                   data tabel server-side
PATCH  /logistics/delivery-part/entry/{entry}          simpan kolom manual (edit-delivery-part)
POST   /logistics/delivery-part/refresh                buang cache
GET    /logistics/delivery-part/export                 export Excel per site
POST   /logistics/delivery-part/spb                    simpan SPB + barisnya
GET    /logistics/delivery-part/spb/data               daftar SPB
POST   /logistics/delivery-part/cancel                 ajukan cancel ITO (cancel-ito)
GET    /logistics/delivery-part/cancel/{cancel}        status cancel
GET    /logistics/warehouse-projects                   mapping (admin)
POST   /logistics/warehouse-projects                   simpan mapping
# endpoint untuk helper Windows (auth token khusus, hanya jaringan internal)
GET    /api/sap-cancel/next                            ambil 1 permintaan cancel
POST   /api/sap-cancel/{cancel}/result                 lapor hasil eksekusi
```

Artisan:

```
delivery-part:import-excel --file=... --dry-run        import Excel 2026 (dry-run wajib dulu)
delivery-part:verify-cancels                           verifikasi ulang status CANCELED di SAP
```

## 7. Risks

1. **Helper Windows = komponen baru** di luar Laravel. Kalau helper mati, cancel tertunda; halaman harus jujur menampilkan status, tidak pernah "sukses" tanpa verifikasi `CANCELED='Y'`.
2. Helper memakai kredensial SAP (user `manager`) → kredensial disimpan di konfigurasi helper di mesin Windows, tidak di repo DDS.
3. Performa query: 6.520 dokumen ITO (OUT) di 2026; wajib filter tanggal + cache 5 menit; perlu uji rentang 1 tahun (target < 5 detik).
4. Import Excel: kunci `(ITO, Part, Unit)` bisa tidak unik; dry-run + laporan cocok/tidak cocok sebelum menulis; baris tak cocok dibuat sebagai baris manual bertanda.
5. SAP bisa menolak cancel (stok sudah terpakai/berpindah) — pesan SAP ditampilkan apa adanya.
6. Kolom manual tidak terlihat di SAP (keputusan sadar) → bila nanti tim SAP butuh, fase 2 menulis `U_MIS_DeliveryTime`/`U_ARK_DelivStat`.
7. Mapping warehouse→project harus diisi manusia; kalau kosong, baris tidak muncul di site mana pun — karena itu daftar "belum dipetakan" ditampilkan.

## 8. Urutan pengerjaan

1. Migrasi + seeder permission + tabel mapping.
2. Service query SAP (versi DDS dari query tim) + cache + uji hitung baris vs Excel (017C, 022C, 026C).
3. Halaman index + kolom manual + riwayat.
4. Export Excel per site.
5. Tab Input SPB.
6. Import Excel 2026 (dry-run → eksekusi).
7. Cancel ITO: tabel + UI + endpoint + helper DI API Windows (spike dulu: pastikan cancel dokumen uji berhasil sebelum dipakai di data nyata).
