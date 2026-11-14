# Implementation Plan — Salon Management System & POS (Laravel 13)

## 1. Objective & Architecture
Membangun aplikasi **Salon Management System & POS** satu cabang berdasarkan `docs/prd.md`, `docs/implementation.md`, dan `docs/desain.md` dengan mengikuti pattern yang sudah ada di repositori `kai-template`:
- **UI & Layout**: KaiAdmin Bootstrap 5 (`layouts/app.blade.php`) dengan tema warna Salon (rose/violet accent yang elegan), responsif untuk desktop/tablet/mobile, serta PWA-ready.
- **CRUD Master Pattern**: Menggunakan `BaseController` + `resources/views/master.blade.php` + `resources/views/components/crud-form.blade.php` (layout 2 kolom: tabel di kiri, form tambah/edit di kanan).
- **Custom Interactive Modules**: Halaman khusus (tetap extends `layouts.app`) untuk:
  - **Dashboard Salon** (KPI hari ini, grafik penjualan, antrian aktif, stok minimum, performa stylist).
  - **Booking & Antrian** (Form booking, validasi konflik jadwal stylist, daftar booking, antrian walk-in, check-in ke service order, perubahan status layanan).
  - **POS / Kasir 3-Panel** (Katalog Layanan & Produk di kiri, Keranjang + pilih Stylist per item di tengah, Ringkasan + Multi-Payment di kanan, dilengkapi idempotency key, DB transaction, row locking, perhitungan komisi & pengurangan stok otomatis).
  - **Struk Thermal 58mm / 80mm** (`resources/views/pos/receipt.blade.php` dengan Browser Print CSS 58mm & 80mm).
  - **Shift Kasir & Refund/Void** (Buka/tutup shift kasir, riwayat transaksi, void/refund penuh dengan alasan, pembalik stok & komisi, dan audit log).
  - **Mutasi & Kartu Stok** (Penerimaan stok, penyesuaian stok, pemakaian produk treatment, dan kartu stok berbasis ledger).
  - **Laporan Operasional & Ekspor** (Filter rentang tanggal, KPI omzet kotor, diskon, refund, penjualan bersih, performa & komisi stylist, pembayaran per metode, stok minimum, ekspor CSV & cetak).
  - **Pengaturan Salon & Audit Log** (Profil salon, jam operasional, pajak, ukuran struk 58mm/80mm, metode pembayaran, dan jejak audit).

## 2. Urutan Slice Implementasi
1. **Slice 1: Fondasi & Perbaikan Template (`layouts/app.blade.php`, `BaseController`, `master.blade.php`, Migration Salon Core)**
   - Perbaiki asset URL & tombol logout di `layouts/app.blade.php`, tambahkan `@stack('styles')` dan `@stack('scripts')` di `template-top` / `template-bottom`, tambahkan tema warna Salon & PWA manifest + service worker.
   - Perkaya `BaseController` dengan default `index()` dan dukungan validasi field `select` bertipe string maupun integer, serta `date` dan `textarea`.
   - Buat migration dan model untuk seluruh tabel Salon (`salon_settings`, `document_sequences`, `audit_logs`, `customers`, `employees`, `employee_schedules`, `employee_leaves`, `service_categories`, `services`, `product_categories`, `units`, `suppliers`, `products`, `payment_methods`, `appointments`, `appointment_details`, `service_orders`, `service_order_details`, `cash_shifts`, `transactions`, `transaction_details`, `payments`, `transaction_refunds`, `stock_headers`, `stock_details`, `employee_commissions`).
2. **Slice 2: Modul Master Data (`BaseController` Pattern)**
   - `CustomerController` (`customers`)
   - `ServiceCategoryController` (`service-categories`) & `ServiceController` (`services`)
   - `StylistController` (`stylists`) & `StylistScheduleController` (`stylist-schedules`)
   - `ProductCategoryController` (`product-categories`), `UnitController` (`units`), `SupplierController` (`suppliers`), `ProductController` (`products`)
   - `PaymentMethodController` (`payment-methods`), `SalonSettingController` (`salon-settings`), `AuditLogController` (`audit-logs`)
3. **Slice 3: Modul Booking, Antrian Walk-in, dan Service Order**
   - `BookingService` & `BookingController` (`bookings`): buat appointment, cek jadwal/cuti/konflik stylist, konfirmasi, check-in menjadi `ServiceOrder`, tambah antrian walk-in langsung, ubah status antrian (`waiting`, `called`, `in_service`, `completed`, `cancelled`, `no_show`).
4. **Slice 4: Modul Kasir / POS, Shift Kasir, Receipt Thermal 58mm/80mm, & Refund/Void**
   - `CashShiftController` (`cash-shifts`): buka dan tutup shift kasir dengan perhitungan kas sistem vs kas aktual.
   - `CheckoutService` & `PosController` (`pos`): UI 3-panel, load dari Service Order selesai atau Walk-in baru, validasi backend, DB transaction, `lockForUpdate` stok & shift, idempotency key, multi-payment, snapshot harga & komisi, pengurangan stok via ledger, audit log.
   - `TransactionController` (`transactions`): riwayat transaksi, detail transaksi, halaman cetak struk thermal 58mm/80mm (`pos.receipt`), dan fitur Refund/Void dengan alasan + reversal stok & komisi.
5. **Slice 5: Modul Mutasi Stok, Laporan, Dashboard Salon, & Seeder Lengkap**
   - `StockService` & `StockController` (`stocks`): penerimaan stok, penyesuaian stok, pemakaian produk, kartu stok.
   - `ReportController` (`reports`): laporan penjualan, metode pembayaran, komisi stylist, booking, stok, shift kasir, dan ekspor CSV.
   - `DashboardController` (`dashboard`): KPI Salon real-time, grafik penjualan 7 hari terakhir, antrian aktif, dan produk stok rendah.
   - `DatabaseSeeder`: seed 5 role sesuai PRD, seluruh hirarki menu Salon, `role_menu`, pengaturan salon, metode pembayaran, kategori & layanan salon, stylist & jadwal, produk & stok awal, pelanggan contoh, serta contoh booking & transaksi.
6. **Slice 6: Automated Feature & Unit Tests**
   - Test autentikasi & RBAC `CheckRole`.
   - Test CRUD Master Data.
   - Test Booking & deteksi konflik jadwal stylist + check-in ke antrian.
   - Test POS Checkout (perhitungan harga backend, multi-payment, pengurangan stok ledger, pencatatan komisi, idempotency anti double-click).
   - Test Refund/Void (reversal stok & komisi + audit log).
   - Test Struk Thermal & Laporan.
