# Panduan Pengguna — Domain Assistant & Pembuatan Invoice dari PDF/Gambar

Dokumen ini menjelaskan cara memakai dua fitur di **ARKA DDS** (aplikasi DDS Laravel): **Domain Assistant** (asisten AI) dan **import/pembuatan invoice dari berkas PDF atau gambar**. Ditujukan untuk **pengguna akhir, tim operasional, dan administrator** (termasuk bagian konfigurasi server).

**Versi hanya pengguna akhir (tanpa bagian administrator):** [`MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID-PENGGUNA-AKHIR.md`](MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID-PENGGUNA-AKHIR.md)

---

## Daftar isi

1. [Domain Assistant](#1-domain-assistant)
2. [Pembuatan invoice dari PDF atau gambar](#2-pembuatan-invoice-dari-pdf-atau-gambar)
3. [Hak akses & konfigurasi (untuk administrator)](#3-hak-akses--konfigurasi-untuk-administrator)
4. [Ringkasan masalah umum](#4-ringkasan-masalah-umum)

---

## 1. Domain Assistant

### 1.1 Apa itu Domain Assistant?

**Domain Assistant** adalah fitur obrolan (chat) berbasis AI yang menjawab pertanyaan seputar alur kerja DDS—misalnya dokumen tambahan (*additional documents*), invoice, distribusi, rekonsiliasi, proses SAP, dan hal terkait lainnya.

- Jawaban **bukan tebakan bebas**: sistem memanggil **alat bantu** (*tools*) yang membaca data dari basis data sesuai **hak akses Anda** (sama seperti di layar daftar).
- Anda dapat bertanya dalam **Bahasa Indonesia** atau bahasa lain; usahakan menyebut **nama vendor**, **nomor invoice**, atau **rentang waktu** jelas agar hasilnya tepat.

### 1.2 Siapa yang bisa mengakses?

- Menu **Domain Assistant** hanya tampil jika:
  - fitur diaktifkan di server (**lihat bagian admin di bawah**), dan
  - peran (role) Anda memiliki izin **`access-domain-assistant`** (biasanya diatur oleh administrator melalui *Roles & Permissions*).

Jika Anda tidak melihat menu ini, hubungi administrator DDS.

### 1.3 Cara membuka

1. Login ke DDS.
2. Di bilah sisi kiri, bagian **MAIN**, klik **Domain Assistant** (ikon robot).
3. Jika diminta atau halaman menolak: fitur mungkin dimatikan atau Anda belum punya izin.

### 1.4 Tampilan utama

| Bagian | Fungsi |
|--------|--------|
| **Percakapan** (kolom kiri) | Daftar topik obrolan (*thread*). Anda bisa punya beberapa percakapan terpisah. |
| **Obrolan + kirim pesan** (kolom kanan) | Riwayat pesan dengan asisten, kotak teks, tombol **Send**. |
| **New chat** | Memulai percakapan baru (kosong). |
| **Hapus percakapan** (ikon × di tiap baris) | Menghapus percakapan tersebut setelah konfirmasi. |
| **Clear** | Menghapus percakapan yang sedang aktif (sesuai perilaku di server). |

Judul percakapan otomatis diambil dari **pesan pertama** Anda (dipotong singkat).

### 1.5 Mengajukan pertanyaan yang baik

- **Invoice menurut vendor**  
  Contoh: *“Tampilkan 10 invoice terakhir dari [nama PT / vendor].”*  
  Sebutkan **nama vendor** yang jelas (atau sebagian nama yang unik). Jika nama di sistem sedikit berbeda ejaan, coba **nama lebih pendek** yang tetap khas (misalnya kata inti dari nama PT).

- **Ringkasan / jumlah**  
  Contoh: *“Ringkas data yang saya boleh lihat di DDS (angka saja).”*

- **Daftar singkat**  
  Contoh: *“Tampilkan hingga 10 invoice terbaru yang saya boleh lihat.”*

- **Dokumen tambahan, distribusi, rekonsiliasi, supplier**  
  Tanyakan spesifik apa yang Anda butuhkan (status, tanggal, kata kunci).

### 1.6 Opsi “Show all records” (jika Anda punya izin)

Jika akun Anda memiliki izin **“see all records”** (*see-all-record-switch*), Anda akan melihat centang **Show all records**.

- **Dicentang**: data invoice/dokumen tambahan mengikuti cakupan yang sama seperti ketika di layar daftar Anda menyalakan **Show all records** (cakupan lebih luas untuk peran yang memang diizinkan).
- **Tidak dicentang**: mengikuti cakupan **default** (lebih sempit untuk banyak pengguna non-akuntansi).

Sesuaikan dengan cara Anda biasa bekerja di layar daftar.

### 1.7 Streaming jawaban (opsional)

Opsi **stream** hanya muncul jika administrator mengatur sistem agar **alat bantu AI dimatikan** tetapi **streaming** diaktifkan (konfigurasi khusus). Biasanya Anda memakai mode **tanpa stream**; jawaban dikirim sekaligus setelah selesai diproses.

### 1.8 Batas penggunaan harian

Administrator dapat mengatur **batas jumlah pesan pengguna per hari**. Jika Anda melihat pesan bahwa batas tercapai, coba lagi keesokan harinya atau hubungi admin.

### 1.9 Privasi dan log

- Aktivitas permintaan dapat dicatat untuk **audit** (waktu, durasi, status sukses/gagal, ringkasan error, alat yang dipanggil, dll.).
- Administrator dengan akses laporan dapat melihat **log permintaan asisten** di menu administrasi (jika fitur tersebut diaktifkan).

---

## 2. Pembuatan invoice dari PDF atau gambar

### 2.1 Apa itu fitur ini?

Di layar **Buat Invoice Baru**, Anda dapat mengunggah **PDF** atau **gambar** (misalnya foto/faktur pemindaian) invoice dari supplier. Sistem memakai layanan AI (OpenRouter) untuk **mengekstrak teks/data** dan **mengisi kolom formulir** sebagai draf. **Anda wajib meninjau dan mengoreksi** sebelum menyimpan.

### 2.2 Prasyarat

- Fitur **import invoice** diaktifkan di server (`INVOICE_IMPORT_ENABLED` dan kunci API OpenRouter—diatur administrator).
- Anda memiliki hak akses ke menu **Invoices** dan tindakan **create** seperti biasa.
- Berkas: **PDF** atau **gambar** (mis. JPG, PNG) sesuai yang didukung antarmuka unggah.

### 2.3 Langkah penggunaan

1. Buka **Invoices** → **Create** (Buat invoice baru).
2. Di bagian atas formulir, cari kartu **Import from PDF or image** (jika fitur aktif).
3. Klik **Choose file** / pilih berkas invoice supplier.
4. (Opsional) Klik **Preview** untuk melihat dokumen di jendela pratinjau.
5. Klik **Extract data** (atau tombol serupa untuk menjalankan ekstraksi).
6. Tunggu hingga status menunjukkan selesai:
   - Jika server memakai **antrean** (*queue*), proses bisa berjalan di latar; pastikan **queue worker** berjalan di server produksi (ini tugas administrator).
   - Di lingkungan pengembangan, kadang ekstraksi dijalankan **sinkron** (tanpa worker)—tetap bisa memakan waktu beberapa detik hingga menit tergantung ukuran berkas.
7. Setelah berhasil, kolom formulir akan terisi **sebagian atau seluruhnya**. Periksa:
   - Nomor invoice, tanggal, supplier, mata uang, jumlah, proyek, lokasi, dll.
8. Perbaiki field yang salah atau kosong (warna peringatan mungkin menandai kepercayaan rendah).
9. Simpan invoice seperti biasa (**Submit** / **Save**).

Setelah invoice tersimpan, lampiran dari impor dapat disimpan sebagai salinan invoice (sesuai konfigurasi aplikasi).

### 2.4 Tips agar hasil lebih baik

- Gunakan PDF **teks jelas** (bukan hanya foto buram) bila memungkinkan.
- Untuk PDF hasil pindai banyak halaman, sistem mungkin hanya menganalisis **halaman pertama** (pengaturan server)—pastikan data utama ada di halaman tersebut.
- **Selalu verifikasi** angka pajak, nomor PO, dan pemetaan supplier; AI bisa salah baca.

### 2.5 Jika ekstraksi gagal atau lama

- Coba berkas dengan resolusi lebih baik atau potong/crop gambar fokus ke area faktur.
- Pastikan koneksi internet server ke layanan AI berjalan (administrator mengecek log).
- Isi formulir **manual** jika impor tidak tersedia atau gagal berulang.

---

## 3. Hak akses & konfigurasi (untuk administrator)

### 3.1 Domain Assistant

| Item | Penjelasan singkat |
|------|---------------------|
| `DOMAIN_ASSISTANT_ENABLED=true` | Menyalakan fitur; default sering `false` di `.env.example`. |
| `OPEN_ROUTER_API_KEY` / `OPENROUTER_API_KEY` | Kunci API OpenRouter (bisa sama dengan fitur import invoice). |
| Model chat opsional | Mis. `OPEN_ROUTER_CHAT_MODEL` untuk model percakapan. |
| Izin Spatie | Permission `access-domain-assistant` harus ada dan diberikan ke role yang sesuai. |
| Cache konfigurasi | Setelah mengubah `.env`, jalankan `php artisan config:clear` atau `config:cache` sesuai kebijakan deployment. |

Tanpa **`DOMAIN_ASSISTANT_ENABLED=true`**, menu **Domain Assistant** **tidak akan tampil** meskipun user punya izin.

### 3.2 Import invoice PDF/gambar

| Item | Penjelasan singkat |
|------|---------------------|
| `INVOICE_IMPORT_ENABLED=true` | Menyalakan kartu impor di halaman buat invoice. |
| `OPEN_ROUTER_API_KEY` | Wajib untuk ekstraksi via OpenRouter. |
| `QUEUE_CONNECTION` | Jika `database` atau `redis`, jalankan **queue worker** agar ekstraksi selesai. |
| `INVOICE_IMPORT_EXTRACT_SYNC` | Jika `true`, ekstraksi jalan dalam request HTTP (berguna untuk dev tanpa worker); di produksi biasanya `false`. |

---

## 4. Ringkasan masalah umum

| Gejala | Kemungkinan penyebab | Tindakan |
|--------|----------------------|----------|
| Menu **Domain Assistant** tidak ada | Fitur dimatikan (`DOMAIN_ASSISTANT_ENABLED`) atau tanpa izin `access-domain-assistant` | Set env + izin role; *config clear* |
| Chat error / tidak menjawab | Kunci API hilang/salah, model dinonaktifkan, atau batas rate | Periksa log server, OpenRouter, quota |
| Jawaban invoice “tidak sesuai vendor” | Pertanyaan tanpa nama vendor jelas; nama di DB berbeda ejaan | Ulangi dengan nama pendek/unik; cek master supplier |
| Kartu **Import PDF** tidak muncul | `INVOICE_IMPORT_ENABLED=false` atau tidak dikonfigurasi | Set env dan deploy ulang konfigurasi |
| **Extract** menggantung | Queue tidak jalan | Jalankan `php artisan queue:work` di server atau set *sync* sesuai panduan dev |
| Ekstraksi gagal | PDF terlalu besar/gambar buram, timeout, API error | Perbaiki berkas; admin cek `OPEN_ROUTER_*` dan log |

---

## Dokumentasi teknis terkait (bahasa Inggris)

- Arsitektur Domain Assistant: [`docs/architecture.md`](architecture.md), [`docs/DOMAIN-ASSISTANT-REFERENCE.md`](DOMAIN-ASSISTANT-REFERENCE.md)
- Rencana / catatan import invoice: [`docs/INVOICE-FROM-DOCUMENT-IMPLEMENTATION-PLAN.md`](INVOICE-FROM-DOCUMENT-IMPLEMENTATION-PLAN.md) (jika ada di repositori)

---

*Versi dokumen: 2026-04-02. Sesuaikan dengan kebijakan internal organisasi Anda.*
