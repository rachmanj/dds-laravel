# Panduan Pengguna Akhir — Domain Assistant & Invoice dari PDF/Gambar

Dokumen ini adalah **versi pengguna akhir** (bukan panduan IT/administrator). Isinya menjelaskan cara memakai **Domain Assistant** dan **import invoice dari PDF atau gambar** di **ARKA DDS**. Untuk pengaturan server, variabel lingkungan, dan antrean, tim IT memakai panduan lengkap terpisah.

**Versi administrator / teknis:** [`MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID.md`](MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID.md)

---

## Daftar isi

1. [Domain Assistant](#1-domain-assistant)
2. [Pembuatan invoice dari PDF atau gambar](#2-pembuatan-invoice-dari-pdf-atau-gambar)
3. [Jika ada kendala](#3-jika-ada-kendala)

---

## 1. Domain Assistant

### 1.1 Apa itu Domain Assistant?

**Domain Assistant** adalah fitur obrolan (chat) berbasis AI yang menjawab pertanyaan seputar alur kerja DDS—misalnya dokumen tambahan, invoice, distribusi, rekonsiliasi, proses SAP, dan hal terkait lainnya.

- Jawaban didasarkan pada **data yang Anda diizinkan lihat** di aplikasi (sama seperti di layar daftar), bukan tebakan bebas.
- Anda boleh bertanya dalam **Bahasa Indonesia** atau bahasa lain. Sebutkan **nama vendor**, **nomor invoice**, atau **rentang waktu** dengan jelas agar jawaban lebih tepat.

### 1.2 Apakah saya bisa mengaksesnya?

Menu **Domain Assistant** hanya muncul jika organisasi Anda sudah **mengaktifkan fitur ini** dan akun Anda **diberi akses** oleh pihak yang mengurus DDS (biasanya melalui peran / izin pengguna).

**Jika menu tidak ada atau halaman menolak akses:** hubungi **administrator DDS** atau **tim IT** internal Anda—mereka yang menambahkan izin atau memastikan fitur disalakan untuk lingkungan produksi.

### 1.3 Cara membuka

1. Login ke DDS.
2. Di bilah sisi kiri, bagian **MAIN**, klik **Domain Assistant** (ikon robot).
3. Bila tidak ada menu tersebut, lihat bagian [Jika ada kendala](#3-jika-ada-kendala).

### 1.4 Tampilan utama

| Bagian | Fungsi |
|--------|--------|
| **Percakapan** (kolom kiri) | Daftar topik obrolan. Anda bisa punya beberapa percakapan terpisah. |
| **Obrolan + kirim pesan** (kolom kanan) | Riwayat dengan asisten, kotak teks, tombol **Send**. |
| **New chat** | Memulai percakapan baru (kosong). |
| **Hapus percakapan** (ikon ×) | Menghapus percakapan itu setelah konfirmasi. |
| **Clear** | Mengosongkan / menghapus percakapan yang sedang dibuka (sesuai perilaku di sistem). |

Judul percakapan diambil dari **pesan pertama** Anda (dipotong singkat).

### 1.5 Contoh pertanyaan yang membantu

- **Invoice menurut vendor**  
  *“Tampilkan 10 invoice terakhir dari [nama PT / vendor].”*  
  Pakai nama yang jelas. Jika hasilnya kurang pas, coba **nama lebih pendek** yang tetap khas (misalnya inti nama PT).

- **Ringkasan**  
  *“Ringkas data yang saya boleh lihat di DDS (angka saja).”*

- **Daftar singkat**  
  *“Tampilkan hingga 10 invoice terbaru yang saya boleh lihat.”*

- **Topik lain**  
  Sebutkan dokumen tambahan, distribusi, rekonsiliasi, atau supplier secara spesifik (status, tanggal, kata kunci).

### 1.6 Centang “Show all records” (jika tersedia)

Jika Anda melihat opsi **Show all records**:

- **Dicentang**: cakupan data mengikuti mode “lihat semua rekaman” seperti di layar daftar (untuk peran yang memang diizinkan).
- **Tidak dicentang**: cakupan mengikuti **default** (biasanya lebih sempit).

Sesuaikan seperti saat Anda memakai filter di layar daftar invoice/dokumen.

### 1.7 Jawaban mengalir per kata (opsional)

Terkadang muncul opsi **stream** (jawaban terurai bertahap). Jika tidak ada, jawaban biasanya muncul **sekaligus** setelah pemrosesan selesai—itu normal.

### 1.8 Batas penggunaan

Jika muncul pesan bahwa **batas pesan harian** tercapai, coba lagi **keesokan harinya** atau minta bantuan administrator.

### 1.9 Catatan privasi

Permintaan Anda ke asisten dapat **dicatat** untuk keperluan audit (waktu, berhasil/gagal, dan sejenisnya) sesuai kebijakan organisasi. Jika ada pertanyaan, tanyakan ke atasan atau tim IT.

---

## 2. Pembuatan invoice dari PDF atau gambar

### 2.1 Apa itu fitur ini?

Di layar **Buat Invoice Baru**, Anda dapat mengunggah **PDF** atau **gambar** faktur dari supplier. Sistem membantu **mengisi kolom formulir** dari isi dokumen. **Anda wajib memeriksa dan mengoreksi semua field** sebelum menyimpan—data hasil baca otomatis bisa salah.

### 2.2 Apakah saya bisa memakainya?

- Anda harus bisa mengakses **Invoices** dan **membuat invoice baru** seperti biasa.
- Kartu **Import from PDF or image** hanya tampil jika organisasi Anda **menyalakan fitur impor** di sistem. Jika tidak ada kartu itu, fitur belum tersedia untuk Anda—hubungi administrator.

- Format berkas: biasanya **PDF**, **JPG**, **PNG** (ikut apa yang ditampilkan di layar unggah).

### 2.3 Langkah singkat

1. Buka **Invoices** → **Create** (Buat invoice baru).
2. Pada kartu **Import from PDF or image**, pilih berkas (**Choose file**).
3. Opsional: **Preview** untuk melihat dokumen.
4. Klik **Extract data** (ekstraksi data).
5. **Tunggu** hingga status menunjukkan selesai. Proses bisa memakan waktu beberapa detik atau lebih, tergantung ukuran berkas dan beban server.
6. **Periksa semua field** yang terisi—nomor invoice, tanggal, supplier, mata uang, jumlah, proyek, lokasi, dll.
7. Perbaiki yang salah atau kosong (ada petunjuk visual jika kepercayaan rendah).
8. Simpan invoice seperti biasa.

Jika ekstraksi sangat lama atau tidak selesai, lihat [Jika ada kendala](#3-jika-ada-kendala).

### 2.4 Tips hasil lebih baik

- Utamakan PDF dengan **teks jelas** (bukan foto buram), bila memungkinkan.
- Untuk PDF hasil pindai **banyak halaman**, data penting sebaiknya ada di **halaman pertama** (banyak konfigurasi hanya menganalisis halaman awal).
- **Verifikasi manual** angka pajak, nomor PO, dan pilihan supplier—kesalahan baca masih mungkin terjadi.

### 2.5 Jika gagal atau tidak memuaskan

- Unggah ulang dengan **gambar lebih tajam** atau **crop** ke area faktur.
- Isi formulir **secara manual** jika impor tidak bisa dipakai.

---

## 3. Jika ada kendala

Ringkasan untuk **pengguna akhir**—langkah praktis tanpa mengubah pengaturan server.

| Yang Anda alami | Yang bisa Anda lakukan |
|-----------------|-------------------------|
| Menu **Domain Assistant** tidak muncul | Pastikan login dengan akun yang benar. Minta administrator **memastikan akses** Anda dan **fitur diaktifkan** di DDS. |
| Chat error, kosong, atau tidak pernah selesai | Coba **refresh** halaman, tunggu sebentar, coba lagi. Jika berlanjut, laporkan ke **IT / admin DDS** (waktu kejadian + cuplikan pesan error jika ada). |
| Jawaban invoice **tidak sesuai vendor** | Ulangi pertanyaan dengan **nama vendor lebih jelas** atau **lebih pendek** (kata kunci unik). Cek ejaan nama di master supplier di DDS. |
| Kartu **Import PDF/gambar** tidak ada | Fitur mungkin belum diaktifkan untuk lingkungan Anda—hubungi **administrator**. |
| **Extract data** lama sekali atau menggantung | Tunggu beberapa menit; tutup tab lain yang berat. Jika tetap tidak selesai, coba berkas lebih kecil atau **laporkan ke IT** (unggah bisa jadi tertahan di server). |
| Ekstraksi gagal atau form kosong | Coba **file lain** (kualitas lebih baik). Lanjutkan dengan **input manual**. |
| Pesan **batas harian** asisten | Coba **besok** atau minta kelonggaran ke **administrator**. |

**Untuk masalah akun, izin, atau penyaluran fitur di server**, selalu hubungi **administrator DDS** atau **tim IT** sesuai prosedur organisasi Anda.

---

*Versi pengguna akhir — 2026-04-02. Dokumen teknis lengkap: [`MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID.md`](MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID.md).*
