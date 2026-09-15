# Spec — Logistik Summary (SAP) di DDS

Status: disetujui Iwan via grill-me (15 Sep 2026) · Sumber: email tim Logistik (Mario) + query SAP terlampir

## 1. Goal

Tim Logistik bisa melihat summary transaksi SAP — **Inventory in warehouse**, **GRPO**, dan **Goods Issue / Material Issue** — langsung di DDS, ditarik otomatis & periodik dari SAP, tanpa query manual di SAP. Bisa di-extract ke Excel.

## 2. Scope

**In:**
- 3 laporan dalam satu menu baru **Logistik** (submenu: Ringkasan Inventory, GRPO, Pemakaian).
- Inventory dari snapshot harian yang disimpan di DDS; GRPO & Pemakaian live query ke SAP.
- Export Excel (kolom persis seperti query asli tim logistik).
- Mapping kategori dari prefix kode item, bisa dikoreksi admin.
- Link GRPO No → dokumen DO/ITO di DDS (match `grpo_no`).

**Out:**
- Tidak menulis apa pun ke SAP (read-only).
- Tidak mengubah modul dokumen/distribusi/invoice yang ada.
- Tidak ada PDF di v1 (Excel dulu).
- Tidak menggantikan SAP sebagai sumber kebenaran.

## 3. Tech decisions

| Hal | Keputusan |
|-----|-----------|
| Sumber data | `DB::connection('sap_sql')` → sqlsrv `arkasrv2` / `SBO_AAP_NEW` (sudah ada di container php82: `pdo_sqlsrv`, `sqlsrv`; terverifikasi 15 Sep 2026) |
| Inventory | Snapshot terjadwal harian **06:00 WITA**, dibaca dari tabel DDS |
| GRPO & Pemakaian | Live query ke SAP saat halaman dibuka / filter diubah, di-cache 5 menit per kombinasi filter |
| Excel | `maatwebsite/excel ^3.1` (sudah terpasang) |
| Chart | Chart.js (sudah ada di `public/adminlte/plugins/chart.js`) — 2 mini bar chart: Instock per Project, Nilai per Category |
| Bahasa | Label menu/filter/KPI Bahasa Indonesia; header kolom English sesuai query asli |
| Kategori | Tabel mapping `prefix kode item → kategori` (18 kategori dari file Excel) |
| Refresh | Harian 06:00 WITA; status "terakhir diperbarui" di halaman; gagal → peringatan di halaman + catat `sap_logs` |

## 4. DB changes

- `logistics_inventory_snapshots` — `id`, `snapshot_date`, `status` (success/failed), `row_count`, `total_value`, `error_message`, `duration_ms`, `created_at`.
- `logistics_inventory_pivots` — `snapshot_id`, `project`, `category`, `sum_instock`, `sum_value` (histori 12 bulan, sumber chart + tabel pivot).
- `logistics_inventory_items` — `snapshot_id`, `model_no`, `unit_no`, `item_code`, `item_name`, `category`, `uom`, `instock`, `committed`, `ordered`, `currency`, `last_price`, `total_value`, `whs_code`, `whs_name`, `project`, `status`, `last_mr_no`, `last_mi_no` (detail; hanya snapshot terbaru + arsip 1x/bulan yang disimpan).
- `logistics_item_categories` — `prefix`, `category`, `is_active`, `updated_by` (editable admin; 18 baris seed awal dari file Excel).
- Retensi: snapshot > 12 bulan dipurge; job `logistics:prune-inventory` menyisakan detail untuk snapshot terbaru + snapshot terakhir tiap bulan.

## 5. UI/UX

Menu sidebar baru **Logistik** (role: logistic, accounting, admin, superadmin):

1. **Ringkasan Inventory** — KPI (total item, total nilai, jumlah warehouse, tanggal snapshot) · 2 mini bar chart · tabel pivot Project × Category · tabel detail (server-side DataTables, filter warehouse/project/kategori/status) · tombol Export Excel · banner peringatan bila snapshot terakhir gagal.
2. **GRPO** — filter FromDate/ToDate (default bulan berjalan), project, vendor · KPI (jumlah GRPO, total nilai, per project/vendor) · tabel 26 kolom sesuai query asli (horizontal scroll) · kolom **GRPO No** diklik → dokumen DO/ITO DDS bila ada · Export Excel.
3. **Pemakaian** — filter rentang tanggal (default bulan berjalan), project, sumber (Goods Issue / Delivery / AP Service) · satu tabel gabungan (UNION OIGE + ODLN + OPCH) dengan kolom penanda sumber · Export Excel.

Aturan UI: ikut tema AdminLTE DDS yang ada (tidak menambah warna/palet baru), format angka ringkas (ribu/juta), tanpa teks asing.

## 6. Routes & permission

- `GET /logistics/inventory` → `logistics.inventory.index` (+ `GET /logistics/inventory/data`)
- `GET /logistics/inventory/export` → `logistics.inventory.export`
- `GET /logistics/grpo`, `GET /logistics/grpo/data`, `GET /logistics/grpo/export`
- `GET /logistics/usage`, `GET /logistics/usage/data`, `GET /logistics/usage/export`
- `GET/POST /logistics/categories` → kelola mapping prefix (admin)
- Permission baru: `view-logistics-summary`, `export-logistics-summary`, `manage-logistics-category-map` → role logistic, accounting, admin, superadmin.
- Command: `logistics:snapshot-inventory` (dijadwalkan harian 06:00 WITA), `logistics:prune-inventory`.

## 7. Risks

- **Query inventory berat** (subquery "last purchase price" per item) → jalankan di queue job, timeout 1200s, catat durasi; kalau >5 menit pertimbangkan tabel staging harga.
- **SAP tidak tersedia** saat snapshot → snapshot `failed`, halaman tetap menampilkan snapshot terakhir + peringatan; tidak menghapus data lama.
- **Rentang tanggal besar** di GRPO/Pemakaian (mis. 1 tahun) bisa lambat → batasi rentang maksimal 92 hari per request dengan pesan yang jelas.
- **Prefix kategori tidak lengkap** → item tanpa match masuk kategori `(tanpa kategori)` dan bisa dikoreksi lewat halaman mapping.
- **Nilai consignment = 0** (seperti di Excel) → tampilkan apa adanya, beri catatan di halaman.
- **Timezone**: SAP menyimpan datetime tanpa zona; konversi ke WITA saat menampilkan agar konsisten dengan label jam 06:00 WITA.

## 8. Urutan implementasi

1. Migrasi + model + seeder mapping kategori.
2. Command snapshot inventory + prune + jadwal.
3. Halaman Ringkasan Inventory (KPI, chart, pivot, detail, export).
4. Halaman GRPO (filter, KPI, detail, link ke DDS, export).
5. Halaman Pemakaian (filter, detail gabungan, export).
6. Menu + permission + role, deploy.
