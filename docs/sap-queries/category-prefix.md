# Mapping prefix kode item → Category

Sumber: file `INVENTORY.xlsx` dari tim Logistik (sheet `data iwr`, 9.161 baris) — dianalisis 15 Sep 2026.
Kolom `Category` **bukan** item group SAP (OITB); nilainya berasal dari **prefix kode item**.

| Prefix | Category (persis seperti di file Excel) | Jumlah item (file Excel) |
|--------|------------------------------------------|--------------------------|
| CE | CATERING | 1 |
| CO | CONSIGNMENT | 7 |
| FE | FREE PART | 46 |
| FR | MESS EQUIPMENT | 1 |
| FU | SOLAR | 2 |
| GT | GET | 278 |
| IT | INFORMATION TECNOLOGI | 34 |
| LO | LUBRICATING OIL | 37 |
| OH | OVERHEAD COST | 16 |
| OS | OFFICE SUPPLY | 172 |
| RC | RECONDITION COMPONEN | 16 |
| RM | RE-US | 9 |
| SA | SAFETY SUPPLY | 28 |
| SOLAR | SOLAR | 4 |
| SP | SPAREPART | 7.412 |
| TO | TOOLS | 13 |
| TY | TYRE | 29 |
| UC | UNDERCARRIAGE | 62 |
| US | RE-US | 156 |
| WO | WORKSHOP CONSUMABLE | 835 |

## Aturan pencocokan (urutan)

1. Kalau `ItemCode` sama persis dengan sebuah prefix (contoh: `SOLAR`) → pakai kategori prefix itu.
2. Ambil bagian sebelum tanda `-` pertama, uppercase (contoh `SP-0280806120` → `SP`).
3. Kalau tidak ketemu, coba prefix 2–3 karakter terdepan tanpa tanda hubung:
   - `SP1761372` → `SP` → SPAREPART
   - `VOE11030271` → `VOE` → SPAREPART (dipetakan ke SPAREPART)
4. Tidak ketemu sama sekali → kategori `(tanpa kategori)`.

Catatan: `RC` di file Excel tertulis `RECONDITION COMPONEN` (nama terpotong 20 karakter) — dipertahankan apa adanya supaya sama dengan laporan tim logistik.
