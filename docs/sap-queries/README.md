# Query SAP untuk DDS (dipakai fitur Logistik Summary)

File di folder ini adalah **sumber kebenaran** query ke SAP B1 (`SBO_AAP_NEW`, koneksi `sap_sql`).
Aturan: **jangan pernah menyunting SQL ini dengan regex/str_replace di PHP.** Repository hanya membaca file apa adanya lalu menjalankan dengan binding `?`.

| File | Dipakai oleh | Parameter posisi `?` |
|------|--------------|----------------------|
| `grpo-param.sql` | `SapGrpoRepository::fetch($from, $to)` — halaman Logistik → GRPO | 2: from, to (DocDate OPDN) |
| `pemakaian-param.sql` | `SapUsageRepository::fetch($from, $to)` — halaman Logistik → Pemakaian | 6: from,to (Goods Issue), from,to (Delivery), from,to (AP Service) |
| `inventory-all-warehouse.sql` | `SapInventoryRepository::fetchAll()` — snapshot harian inventory | 0 (tanpa parameter) |

## File referensi (JANGAN dijalankan aplikasi)

Tiga file berikut adalah pecahan per sumber dari `pemakaian-param.sql`. Disimpan hanya sebagai rujukan/untuk menelusuri tiap cabang query. Kalau isinya berubah, **tidak ada efeknya** ke aplikasi — yang dibaca aplikasi adalah `pemakaian-param.sql`.

- `usage-goods-issue-param.sql` (OIGE + IGE1)
- `usage-delivery-param.sql` (ODLN + DLN1)
- `usage-ap-service-param.sql` (OPCH + PCH1)

## catatan semantik penting

- `pemakaian-param.sql` adalah **satu query UNION** (bukan tiga query terpisah). `UNION` men-dedupe baris: 15.598 + 8.843 + 24 baris mentah menjadi 8.452 baris hasil (laporan manual tim Logistik: 8.427 untuk periode 16 Agu–15 Sep 2026). Memecahnya menjadi tiga query akan melipatgandakan baris dan tidak sama dengan laporan mereka.
- Query asli dari email tim Logistik (versi mentah dengan `DECLARE`/`SET`/`[%0]`) disimpan sebagai `grpo.sql`, `pemakaian.sql`, `inventory-all-warehouse.sql`.
- `grpo-param.sql` menambah kolom `Vendor Code` dan `Vendor Name` (join `OCRD`) untuk kebutuhan filter vendor. `pemakaian-param.sql` menambah kolom `Source` di kolom pertama.
