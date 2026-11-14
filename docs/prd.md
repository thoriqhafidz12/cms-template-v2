# PRD --- Salon Management System & POS

**Versi:** 1.0\
**Tanggal:** 14 November 2026\
**Status:** Draft spesifikasi implementasi\
**Ruang lingkup:** Salon kecantikan satu cabang

## 1. Ringkasan Produk

Salon Management System adalah aplikasi berbasis web untuk mengelola
operasional salon dari proses booking, antrian, layanan, transaksi
kasir, pelanggan, stylist, stok produk, laporan, pengguna, hingga
pengaturan salon.

Aplikasi dapat diakses melalui browser desktop/tablet/mobile dan dapat
dipasang sebagai Progressive Web App (PWA). POS terintegrasi dalam
aplikasi yang sama, bukan aplikasi terpisah.

### 1.1 Tujuan

-   Mengurangi pencatatan booking dan transaksi secara manual.
-   Mempercepat pelayanan pelanggan dan proses checkout.
-   Mengetahui jadwal, beban kerja, dan performa stylist.
-   Mengendalikan persediaan produk.
-   Menyediakan laporan operasional dan penjualan yang konsisten.
-   Menyediakan jejak audit untuk tindakan penting.

### 1.2 Sasaran Pengguna

-   Owner / Admin Utama
-   Admin / Resepsionis
-   Kasir
-   Stylist
-   Petugas stok (opsional, dapat digabung dengan Admin)

## 2. Ruang Lingkup

### 2.1 Termasuk MVP

1.  Login, role, permission, dan manajemen user.
2.  Master pelanggan.
3.  Master stylist dan jadwal kerja.
4.  Master layanan, kategori layanan, harga, durasi, dan komisi.
5.  Booking dan antrian walk-in.
6.  POS untuk layanan dan produk.
7.  Pembayaran tunai, transfer, QRIS, serta multi-payment.
8.  Cetak struk thermal 58mm/80mm melalui Browser Print.
9.  Stok produk, penerimaan/pembelian sederhana, penyesuaian, dan kartu
    stok.
10. Laporan booking, antrian, penjualan, layanan, stylist, stok, dan
    kasir.
11. Pengaturan profil salon, jam operasional, nomor dokumen,
    pajak/diskon, dan printer.
12. PWA-ready dengan kebutuhan koneksi internet untuk transaksi.

### 2.2 Di luar MVP

-   Multi-cabang.
-   POS offline penuh dan sinkronisasi konflik.
-   Aplikasi native Android/iOS.
-   Integrasi langsung dengan API payment gateway.
-   Payroll dan akuntansi penuh.
-   Program loyalty kompleks, poin, dan membership berlangganan.
-   Print bridge silent printing (dapat menjadi fase lanjutan).
-   Integrasi WhatsApp API otomatis (dapat menjadi fase lanjutan).

## 3. Struktur Menu Utama

  -----------------------------------------------------------------------
  No.                     Menu                    Submenu / Fitur
  ----------------------- ----------------------- -----------------------
  1                       Booking dan Antrian     Kalender booking,
                                                  booking baru, daftar
                                                  booking, antrian hari
                                                  ini, check-in,
                                                  panggilan antrian,
                                                  status layanan,
                                                  pembatalan, no-show

  2                       Kasir                   POS, transaksi baru,
                                                  pembayaran, cetak
                                                  struk, riwayat
                                                  transaksi, refund/void
                                                  sesuai izin, shift
                                                  kasir

  3                       Pelanggan               Daftar pelanggan,
                                                  tambah/edit pelanggan,
                                                  detail pelanggan,
                                                  riwayat booking,
                                                  riwayat transaksi

  4                       Layanan dan Harga       Kategori layanan,
                                                  daftar layanan, harga,
                                                  durasi, komisi, status
                                                  aktif

  5                       Stylish                 Daftar stylist, profil,
                                                  keahlian, jadwal kerja,
                                                  hari libur, status
                                                  aktif, performa

  6                       Stok Produk             Kategori produk,
                                                  produk, satuan,
                                                  supplier, penerimaan
                                                  stok, penyesuaian stok,
                                                  kartu stok, stok
                                                  minimum

  7                       Laporan                 Penjualan, pembayaran,
                                                  booking, antrian,
                                                  layanan terlaris,
                                                  performa stylist, stok,
                                                  shift kasir

  8                       Manajemen User          Pengguna, role,
                                                  permission, status
                                                  akun, reset password,
                                                  audit aktivitas

  9                       Pengaturan              Profil salon, jam
                                                  operasional, metode
                                                  pembayaran,
                                                  pajak/diskon, format
                                                  nomor, template struk,
                                                  preferensi printer
  -----------------------------------------------------------------------

Navigasi dan submenu ditampilkan sesuai permission pengguna.

## 4. Role dan Hak Akses

  -----------------------------------------------------------------------------------------
  Fitur               Owner   Admin/Resepsionis             Kasir      Stylist Petugas Stok
  ------------ ------------ ------------------- ----------------- ------------ ------------
  Dashboard           Semua         Operasional             Kasir       Jadwal         Stok
                                                                       sendiri 

  Booking dan        Kelola              Kelola    Lihat/check-in Lihat jadwal           \-
  Antrian                                                              sendiri 

  Kasir               Semua         Sesuai izin          Checkout           \-           \-

  Pelanggan          Kelola              Kelola      Lihat/tambah        Lihat           \-
                                                                      terbatas 

  Layanan dan        Kelola               Lihat             Lihat        Lihat           \-
  Harga                                                                        

  Stylish            Kelola              Kelola             Lihat       Profil           \-
                                                                       sendiri 

  Stok Produk        Kelola               Lihat   Lihat/penjualan           \-       Kelola

  Laporan             Semua         Operasional     Shift sendiri     Performa         Stok
                                                                       sendiri 

  Manajemen          Kelola                  \-                \-           \-           \-
  User                                                                         

  Pengaturan         Kelola            Terbatas     Printer/shift       Profil           \-
                                                      sesuai izin      sendiri 
  -----------------------------------------------------------------------------------------

Permission harus ditegakkan di backend menggunakan middleware, Policy,
atau Gate. Menyembunyikan menu di UI saja tidak cukup.

## 5. Definisi Modul dan Kebutuhan Fungsional

### 5.1 Booking dan Antrian

**Tujuan:** mencatat janji temu dan mengatur pelanggan yang datang.

Fitur: - Buat booking untuk pelanggan terdaftar atau pelanggan baru. -
Pilih tanggal, waktu, layanan, stylist pilihan (opsional), dan
catatan. - Hitung estimasi durasi dari layanan yang dipilih. - Periksa
jam operasional, jadwal stylist, cuti, dan konflik booking. - Check-in
pelanggan menjadi antrian. - Tambahkan pelanggan walk-in ke antrian. -
Tetapkan stylist dan ruang/station jika fitur station diaktifkan. - Ubah
status booking dan layanan. - Catat alasan pembatalan.

Status booking: - `pending` - `confirmed` - `checked_in` -
`in_service` - `completed` - `cancelled` - `no_show`

Status antrian/layanan: - `waiting` - `called` - `in_service` -
`completed` - `cancelled`

Aturan: - Booking tidak otomatis berarti transaksi sudah dibayar. -
Booking yang check-in menghasilkan entri antrian atau service order. -
Cegah bentrok jadwal stylist berdasarkan interval waktu dan durasi. -
Walk-in dapat dibuat tanpa appointment. - Perubahan status dan waktu
penting dicatat dalam riwayat.

### 5.2 Kasir / POS

**Tujuan:** membuat transaksi layanan dan penjualan produk.

Fitur: - Cari pelanggan berdasarkan nama atau nomor telepon. - Pilih
booking/service order yang akan ditagihkan. - Tambahkan layanan dan
produk. - Pilih stylist per item layanan; satu transaksi dapat
melibatkan beberapa stylist. - Tampilkan harga, kuantitas, diskon,
pajak, subtotal, dan total. - Mendukung pembayaran tunai, transfer,
QRIS, dan kombinasi metode. - Hitung kembalian untuk pembayaran tunai. -
Simpan referensi pembayaran non-tunai. - Cetak dan cetak ulang struk. -
Lihat riwayat transaksi. - Void/refund dengan alasan dan permission
khusus. - Buka dan tutup shift kasir.

Status transaksi: - `draft` - `unpaid` - `partially_paid` - `paid` -
`cancelled` - `refunded` - `partially_refunded` (bila refund parsial
didukung)

Aturan: - Backend mengambil ulang harga dan status item dari database. -
Total pembayaran tidak boleh kurang dari total tagihan untuk status
`paid`. - Pembayaran berlebih hanya menghasilkan kembalian untuk metode
tunai. - Transaksi final tidak boleh dihapus. - Request checkout harus
idempotent agar klik ganda tidak membuat transaksi ganda. - Data struk
harus berasal dari transaksi tersimpan. - Perubahan stok dan komisi
dilakukan secara konsisten dengan status transaksi dan kebijakan
bisnis. - Refund membuat catatan refund dan pembalik stok/komisi bila
relevan; jangan mengubah histori transaksi secara diam-diam.

### 5.3 Pelanggan

Field minimal: - Nomor pelanggan (unik, dapat dibuat otomatis). -
Nama. - Nomor telepon. - Email (opsional). - Tanggal lahir (opsional). -
Jenis kelamin (opsional dan tidak wajib). - Alamat (opsional). - Catatan
preferensi/alergi produk (opsional; batasi akses karena dapat berisi
informasi sensitif). - Status aktif.

Fitur: - Tambah, ubah, lihat, nonaktifkan. - Pencarian dan filter. -
Riwayat booking, layanan, dan transaksi. - Hindari duplikasi nomor
telepon bila kebijakan salon menetapkan nomor telepon unik.

### 5.4 Layanan dan Harga

Field: - Kode layanan. - Kategori. - Nama dan deskripsi. - Harga jual. -
Durasi estimasi dalam menit. - Komisi default (persentase atau
nominal). - Status aktif. - Tanggal mulai berlaku harga (opsional).

Aturan: - Harga dan komisi pada transaksi disimpan sebagai snapshot. -
Layanan nonaktif tidak dapat dipilih untuk transaksi baru, tetapi tetap
tampil pada histori. - Perubahan harga tidak mengubah transaksi lama. -
Durasi gabungan digunakan untuk estimasi booking; konflik jadwal tetap
diperiksa.

### 5.5 Stylish (Stylist)

Field: - Kode karyawan. - Nama. - Nomor telepon. - Foto (opsional). -
Keahlian. - Tanggal bergabung. - Status aktif. - User login
(opsional). - Aturan komisi.

Fitur: - Jadwal kerja per hari. - Hari libur/cuti. - Layanan yang dapat
dikerjakan (opsional). - Kalender booking. - Riwayat layanan dan
performa. - Ringkasan komisi.

Aturan: - Stylist tidak dapat dijadwalkan di luar jam kerja atau saat
cuti. - Konflik booking harus dicegah. - Komisi dicatat per detail
layanan yang selesai, bukan berdasarkan seluruh nilai transaksi tanpa
pemisahan.

### 5.6 Stok Produk

Fitur: - Master kategori, produk, satuan, supplier. - Kode/SKU dan
barcode (opsional). - Harga jual dan harga beli. - Stok minimum. -
Penerimaan stok. - Penyesuaian stok dengan alasan. - Kartu stok. -
Laporan stok minimum.

Aturan: - Gunakan stock ledger/mutasi stok sebagai sumber histori. -
Jangan mengubah saldo stok tanpa dokumen mutasi. - Penjualan produk
mengurangi stok ketika transaksi diposting sesuai kebijakan. - Produk
yang dipakai untuk treatment dapat dicatat melalui `product_usages` atau
dokumen pemakaian terpisah. - Pembatalan/refund harus membuat mutasi
pembalik bila barang dikembalikan ke stok. - Saldo stok harus dapat
direkonsiliasi dari saldo awal ditambah seluruh mutasi.

### 5.7 Laporan

Filter umum: - Rentang tanggal. - Status. - Stylist. - Kasir. - Metode
pembayaran. - Produk/layanan. - Ekspor PDF/Excel/CSV sesuai kebutuhan.

Laporan MVP: - Ringkasan penjualan harian/bulanan. - Detail transaksi. -
Penjualan per layanan dan produk. - Pembayaran per metode. - Booking dan
no-show. - Antrian dan durasi layanan. - Performa stylist dan komisi. -
Posisi stok dan kartu stok. - Stok minimum. - Ringkasan shift kasir dan
selisih kas. - Refund dan pembatalan.

Definisi KPI: - Omzet kotor: jumlah nilai transaksi sebelum diskon dan
refund. - Diskon: total diskon transaksi/detail. - Penjualan bersih:
omzet kotor dikurangi diskon dan refund yang relevan. - Pembayaran
diterima: total pembayaran yang berhasil tercatat, tidak termasuk
pembayaran yang dibatalkan. - Rata-rata transaksi: penjualan bersih
dibagi jumlah transaksi paid yang memenuhi kriteria. - Booking
conversion: booking completed yang menghasilkan transaksi dibagi booking
yang check-in/completed sesuai definisi laporan. - Komisi: akumulasi
komisi dari detail layanan yang memenuhi syarat.

### 5.8 Manajemen User

Fitur: - Buat, ubah, nonaktifkan pengguna. - Hubungkan user dengan role
dan/atau data stylist. - Reset password. - Atur permission granular. -
Catat login dan aktivitas penting. - Akun nonaktif tidak dapat login.

### 5.9 Pengaturan

Fitur: - Profil salon dan logo. - Alamat dan kontak. - Jam
operasional. - Zona waktu (default `Asia/Jakarta`). - Mata uang (IDR). -
Metode pembayaran. - Pajak dan diskon. - Format nomor
booking/transaksi. - Template struk. - Lebar kertas thermal 58mm/80mm. -
Preferensi printer di perangkat kasir. - Pengaturan komisi. - Pengaturan
stok minimum. - Backup dan informasi versi aplikasi (administratif).

## 6. Alur Bisnis Utama

### 6.1 Booking hingga checkout

1.  Resepsionis membuat booking.
2.  Sistem memeriksa jadwal salon dan stylist.
3.  Booking dikonfirmasi.
4.  Pelanggan datang dan check-in.
5.  Sistem membuat antrian/service order.
6.  Stylist memulai dan menyelesaikan layanan.
7.  Kasir mengambil item layanan dari service order.
8.  Kasir menambahkan produk jika ada.
9.  Backend menghitung ulang tagihan.
10. Kasir memproses pembayaran.
11. Sistem memposting transaksi, pembayaran, stok, dan komisi.
12. Kasir mencetak struk.

### 6.2 Walk-in

1.  Kasir/resepsionis memilih pelanggan atau pelanggan umum.
2.  Membuat antrian walk-in.
3.  Menentukan layanan dan stylist.
4.  Layanan diproses.
5.  Item layanan diteruskan ke POS.
6.  Pembayaran dan cetak struk.

### 6.3 Pembelian dan penerimaan stok

1.  Petugas membuat dokumen penerimaan/pembelian.
2.  Memasukkan produk, jumlah, dan harga beli.
3.  Dokumen diposting.
4.  Sistem membuat mutasi masuk.
5.  Kartu stok dan saldo stok diperbarui melalui ledger.

### 6.4 Refund

1.  User dengan permission membuka transaksi.
2.  Memilih item/nominal yang akan direfund.
3.  Memasukkan alasan.
4.  Backend memvalidasi jumlah refund agar tidak melebihi nilai yang
    dapat dikembalikan.
5.  Sistem mencatat refund dan pembayaran keluar.
6.  Stok/komisi dikoreksi bila sesuai kebijakan.
7.  Audit log dicatat.

## 7. Aturan Bisnis Lintas Modul

-   Appointment, service order, transaction, dan payment adalah entitas
    terpisah.
-   Satu transaksi dapat mencakup banyak item dan stylist.
-   Harga, nama item, diskon, dan komisi historis disimpan sebagai
    snapshot.
-   Semua nominal menggunakan tipe DECIMAL, bukan floating point.
-   Proses checkout berjalan dalam database transaction.
-   Stok hanya berubah melalui mutasi stok yang tercatat.
-   Transaksi final tidak dapat dihapus.
-   Refund dan void wajib menyimpan alasan serta pengguna pelaksana.
-   Nomor dokumen harus unik.
-   Gunakan waktu server dan zona waktu `Asia/Jakarta` untuk tampilan
    operasional.
-   Setiap aksi sensitif memiliki audit log.
-   Gunakan soft delete untuk master data yang sudah pernah digunakan;
    jangan soft-delete histori finansial.

## 8. Kebutuhan Non-Fungsional

### Keamanan

-   Password di-hash menggunakan mekanisme Laravel.
-   HTTPS wajib di lingkungan produksi.
-   CSRF protection dan validasi server-side.
-   Role/permission ditegakkan di backend.
-   Pembatasan akses laporan keuangan dan data pelanggan.
-   Audit log untuk checkout, refund, void, perubahan harga, stok, dan
    user.

### Kinerja

-   Pagination untuk daftar data.
-   Eager loading untuk menghindari N+1.
-   Index pada nomor dokumen, tanggal, status, foreign key, dan kolom
    pencarian.
-   Checkout menggunakan transaksi database.
-   Hindari memuat seluruh histori ke halaman POS.

### Ketersediaan dan backup

-   Backup database terjadwal.
-   Uji restore berkala.
-   PWA menyimpan aset statis, tetapi transaksi membutuhkan koneksi
    server pada MVP.
-   Kegagalan cetak tidak boleh membatalkan transaksi yang telah
    tersimpan.

### Kompatibilitas

-   Browser modern Chrome, Edge, Safari.
-   Layout desktop/tablet untuk kasir dan mobile untuk booking/jadwal.
-   Printer thermal ESC/POS dengan driver yang sesuai.

## 9. Acceptance Criteria MVP

### Booking

-   User dapat membuat booking dengan satu atau beberapa layanan.
-   Sistem menolak konflik jadwal stylist.
-   Check-in menghasilkan antrian/service order.
-   Status booking tercatat dan dapat ditelusuri.

### POS

-   Kasir dapat membuat transaksi dari service order atau walk-in.
-   Harga dihitung ulang backend.
-   Pembayaran satu metode dan multi-metode tersimpan benar.
-   Klik checkout berulang tidak menghasilkan transaksi ganda.
-   Transaksi gagal tidak meninggalkan data parsial.
-   Struk dapat dicetak ulang dari data tersimpan.

### Stok

-   Penerimaan menambah stok melalui ledger.
-   Penjualan produk membuat mutasi keluar.
-   Kartu stok dapat direkonsiliasi dengan saldo.
-   Penyesuaian stok wajib memiliki alasan.

### Komisi

-   Komisi dihitung per item layanan sesuai aturan.
-   Detail komisi dapat ditelusuri ke transaksi dan stylist.
-   Refund/void mengikuti kebijakan koreksi komisi.

### Laporan

-   Total laporan cocok dengan transaksi sumber untuk filter yang sama.
-   Refund dan diskon ditampilkan terpisah.
-   Laporan dapat difilter berdasarkan tanggal.

### Keamanan

-   User tanpa permission tidak dapat mengakses endpoint secara
    langsung.
-   Aksi refund/void tercatat dalam audit log.
-   User nonaktif tidak dapat login.

## 10. Prioritas Pengembangan

### Fase 1 --- Fondasi

-   Setup Laravel, autentikasi, role/permission.
-   Layout, menu dinamis, pengaturan dasar.
-   Master pelanggan, layanan, stylist, produk.

### Fase 2 --- Booking dan antrian

-   Kalender booking.
-   Jadwal stylist.
-   Check-in, walk-in, service order, status layanan.

### Fase 3 --- POS

-   UI keranjang.
-   Checkout, pembayaran, shift kasir.
-   Receipt HTML dan Browser Print.

### Fase 4 --- Stok dan komisi

-   Penerimaan stok, mutasi, kartu stok.
-   Pemakaian produk.
-   Perhitungan komisi.

### Fase 5 --- Laporan dan PWA

-   Laporan operasional.
-   Ekspor.
-   PWA installability dan caching aset statis.
-   Hardening keamanan, backup, dan UAT.

## 11. Risiko dan Keputusan Terbuka

-   Tentukan apakah stylist menggunakan akun login sendiri.
-   Tentukan apakah layanan memerlukan deposit.
-   Tentukan aturan pembatalan dan no-show.
-   Tentukan komisi berdasarkan omzet kotor, setelah diskon, atau
    nominal tetap.
-   Tentukan apakah produk treatment mengurangi stok otomatis atau
    dicatat manual.
-   Tentukan apakah refund parsial didukung pada MVP.
-   Tentukan perangkat dan sistem operasi komputer kasir serta model
    printer.
-   Tentukan apakah QRIS dicatat manual atau kelak diintegrasikan dengan
    payment gateway.

Keputusan tersebut harus ditetapkan sebelum implementasi modul terkait
agar tidak menimbulkan perubahan struktur data besar.
