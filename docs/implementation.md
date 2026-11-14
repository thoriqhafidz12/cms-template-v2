# IMPLEMENTATION --- Salon Management System & POS (Mobile-First PWA & Tab UI)

**Versi:** 2.0 (Mobile Apps PWA Edition)\
**Tanggal:** 14 November 2026\
**Acuan:** `prd.md` dan `desain.md`

---

## 1. Tujuan Dokumen

Dokumen ini menjadi panduan teknis implementasi **Salon Management System & POS** berbasis **Mobile-First Progressive Web App (PWA)** dengan pola navigasi **Mobile App Tabs (Bottom Navigation Bar + Top Segmented Tabs)** di atas Laravel, Blade, Bootstrap 5 / Custom Mobile Shell CSS, dan JavaScript interaktif.

---

## 2. Stack Teknologi

| Area | Teknologi |
| :--- | :--- |
| **Backend** | Laravel (PHP 8.2+) |
| **Database** | MariaDB / MySQL / SQLite (Testing) |
| **UI & App Shell** | Blade + Mobile-First App Shell (`layouts/app.blade.php`) |
| **Navigasi Mobile** | Fixed Bottom Tab Bar (5 Tab Utama) + Sticky Top Segmented Tabs |
| **Interaktivitas** | Vanilla JS / Alpine-style Tab Switching + Bottom Sheet Modals |
| **Otorisasi Menu** | `role_menu` + `CheckRole` Middleware (`app/Http/Middleware/CheckRole.php`) |
| **CRUD Pattern** | `BaseController` + `master.blade.php` (Responsif dengan Mobile Card View) |
| **PWA** | `public/manifest.webmanifest` + `public/sw.js` + Install Prompt + Offline Guard |
| **Printer & Struk** | Thermal CSS (`58mm` / `80mm`) + `window.print()` + Web Share API (`navigator.share`) |
| **Testing** | PHPUnit Feature Tests (`tests/Feature/SalonPosTest.php`) |

---

## 3. Prinsip Implementasi Mobile PWA

1. **Single Codebase, Native Mobile Experience:** Seluruh route Laravel merender di dalam *Mobile App Shell* yang mendukung mode layar penuh (`display: standalone`), *safe-area-inset*, dan transisi antar-tab yang instan.
2. **2-Tier Tab Navigation:**
   - **Tier 1 — Bottom Navigation Bar:** Selalu berada di bawah layar untuk berpindah ke 5 area utama (`Home`, `Antrian`, `Kasir POS`, `Katalog & Stok`, `Lainnya`).
   - **Tier 2 — Top Segmented Tabs:** Berada di bagian atas konten untuk berpindah antar sub-halaman atau tahapan proses tanpa memuat ulang seluruh halaman jika berada dalam satu modul.
3. **Controller Tipis & Service Transaksional:** Seluruh logika bisnis tetap berada di `BookingService`, `CheckoutService`, `StockService`, dan `DocumentNumberService` menggunakan `DB::transaction()` dan `lockForUpdate()`.
4. **Online-Validated Checkout dengan Offline Guard:** Service Worker meng-cache aset statis agar antarmuka terbuka instan, namun transaksi finansial (`checkout`, `refund`, `stock mutation`) wajib divalidasi server dan dilindungi oleh deteksi jaringan *real-time*.

---

## 4. Struktur Folder Mobile PWA

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── DashboardController.php
│   │   ├── BookingController.php
│   │   ├── PosController.php
│   │   ├── CashShiftController.php
│   │   ├── TransactionController.php
│   │   ├── StockController.php
│   │   ├── ReportController.php
│   │   └── ... (Master Controllers)
│   └── Middleware/
│       └── CheckRole.php
├── Models/ (26 Eloquent Models)
└── Services/
    ├── BookingService.php
    ├── CheckoutService.php
    ├── StockService.php
    └── DocumentNumberService.php

public/
├── manifest.webmanifest          # Konfigurasi PWA Standalone, Portrait & App Shortcuts
├── sw.js                         # Service Worker (App Shell Cache & Network Guard)
└── offline.html                  # Halaman fallback saat perangkat offline

resources/views/
├── layouts/
│   └── app.blade.php             # Mobile App Shell (Top Bar, Viewport, Bottom Tab Bar)
├── components/
│   ├── template-top.blade.php    # Header Mobile + CSS Variables + Safe Area + Tab Styles
│   ├── template-bottom.blade.php # Bottom Navigation Bar + Menu Hub Sheet + PWA Scripts
│   └── mobile-tabs.blade.php     # Reusable Top Segmented Tabs Component
├── dashboard.blade.php           # Home dengan 3 Segmented Tabs
├── bookings/
│   └── index.blade.php           # Booking & Antrian dengan 3 Segmented Tabs
├── pos/
│   ├── index.blade.php           # POS Mobile 3-Tab (Katalog, Keranjang, Bayar) + Floating Bar
│   ├── shifts.blade.php          # Shift Kasir dengan Tab Buka/Tutup & Riwayat
│   ├── transactions.blade.php    # Riwayat & Bottom Sheet Refund
│   └── receipt.blade.php         # Struk 58mm/80mm + Tombol Print & Web Share
├── inventory/
│   └── stocks.blade.php          # Tab Mutasi Baru & Tab Kartu Stok
└── reports/
    └── index.blade.php           # 4 Segmented Tabs Laporan + Export CSV
```

---

## 5. Implementasi Mobile App Shell & Navigasi Tab

### 5.1 CSS Mobile App Shell & Safe Area (`template-top.blade.php`)

Tambahkan aturan CSS khusus mobile app shell agar navigasi bawah, tab atas, dan *floating bar* nyaman digunakan dengan satu tangan:

```css
:root {
    --salon-primary: #e11d48;
    --salon-primary-dark: #be123c;
    --salon-accent: #7c3aed;
    --salon-surface: #ffffff;
    --salon-bg: #f8f7fa;
    --bottom-nav-height: 64px;
    --safe-bottom: env(safe-area-inset-bottom, 0px);
    --safe-top: env(safe-area-inset-top, 0px);
}

body {
    background-color: var(--salon-bg);
    padding-top: var(--safe-top);
    padding-bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 16px);
    overscroll-behavior-y: contain;
    -webkit-tap-highlight-color: transparent;
}

/* Top Segmented Tabs (Sticky di bawah Header) */
.mobile-segmented-tabs {
    position: sticky;
    top: 56px;
    z-index: 1010;
    background: var(--salon-bg);
    padding: 8px 12px;
    display: flex;
    gap: 8px;
    overflow-x: auto;
    scrollbar-width: none;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
.mobile-segmented-tabs::-webkit-scrollbar {
    display: none;
}
.mobile-segmented-tabs .seg-tab {
    flex: 1 0 auto;
    min-height: 40px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #4b5563;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
    transition: all 0.15s ease;
}
.mobile-segmented-tabs .seg-tab.active {
    background: var(--salon-primary);
    color: #ffffff;
    border-color: var(--salon-primary);
    box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
}

/* Fixed Bottom Navigation Bar */
.mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: calc(var(--bottom-nav-height) + var(--safe-bottom));
    padding-bottom: var(--safe-bottom);
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-around;
    align-items: center;
    z-index: 1030;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
}
.mobile-bottom-nav .nav-tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.72rem;
    font-weight: 600;
    gap: 3px;
}
.mobile-bottom-nav .nav-tab i {
    font-size: 1.15rem;
}
.mobile-bottom-nav .nav-tab.active {
    color: var(--salon-primary);
}
.mobile-bottom-nav .nav-tab-pos {
    position: relative;
    top: -12px;
    background: linear-gradient(135deg, #e11d48, #7c3aed);
    color: #ffffff !important;
    width: 54px;
    height: 54px;
    border-radius: 50%;
    box-shadow: 0 6px 16px rgba(225, 29, 72, 0.35);
}
```

### 5.2 Komponen Bottom Navigation Bar & Menu Hub (`layouts/app.blade.php`)

Bottom Navigation menampilkan 5 tab utama dan membuka *Slide-Up Bottom Sheet* untuk menu **Lainnya** sesuai permission `role_menu`:

```html
<nav class="mobile-bottom-nav d-lg-none no-print" aria-label="Navigasi Utama Mobile">
    <a href="{{ route('dashboard') }}" class="nav-tab {{ request()->is('dashboard*') ? 'active' : '' }}">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>

    <a href="{{ route('bookings.index') }}" class="nav-tab {{ request()->is('bookings*') ? 'active' : '' }}">
        <i class="fas fa-calendar-check"></i>
        <span>Antrian</span>
    </a>

    <a href="{{ route('pos.index') }}" class="nav-tab {{ request()->is('pos*') ? 'active' : '' }}">
        <div class="nav-tab-pos d-flex align-items-center justify-content-center">
            <i class="fas fa-cash-register"></i>
        </div>
        <span>Kasir</span>
    </a>

    <a href="{{ route('stocks.index') }}" class="nav-tab {{ request()->is('stocks*', 'products*', 'services*') ? 'active' : '' }}">
        <i class="fas fa-boxes"></i>
        <span>Katalog</span>
    </a>

    <button type="button" class="nav-tab border-0 bg-transparent" data-bs-toggle="offcanvas" data-bs-target="#mobileMenuHub">
        <i class="fas fa-th-large"></i>
        <span>Lainnya</span>
    </button>
</nav>
```

### 5.3 Implementasi Top Segmented Tabs di Setiap Modul

Gunakan helper JavaScript ringan yang menyimpan tab aktif di URL (`?tab=...` atau `#tab-...`) agar ketika halaman di-*refresh* atau form disubmit, pengguna tetap berada di tab yang sama:

```javascript
function switchMobileTab(tabGroupId, targetTabId) {
    const container = document.getElementById(tabGroupId);
    if (!container) return;

    container.querySelectorAll('[data-tab-target]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tabTarget === targetTabId);
    });

    container.querySelectorAll('[data-tab-pane]').forEach(pane => {
        pane.classList.toggle('d-none', pane.dataset.tabPane !== targetTabId);
    });

    const url = new URL(window.location);
    url.searchParams.set('tab', targetTabId);
    window.history.replaceState({}, '', url);
}
```

---

## 6. Implementasi Layar Utama Berbasis Tab

### 6.1 Modul Booking & Antrian (`resources/views/bookings/index.blade.php`)
Menggunakan 3 Segmented Tabs:
1. `data-tab-pane="queue"`: Menampilkan daftar kartu `ServiceOrder` hari ini dengan tombol progres satu sentuhan (`Waiting` -> `In Service` -> `Completed` -> `Kirim ke POS`).
2. `data-tab-pane="appointments"`: Menampilkan filter tanggal dan kartu jadwal `Appointment` beserta tombol `Check-in`.
3. `data-tab-pane="create"`: Menampilkan sub-switch form **Walk-in Langsung** dan **Booking Terjadwal**.

### 6.2 Modul POS Kasir Mobile (`resources/views/pos/index.blade.php`)
Menggunakan 3 Segmented Workflow Tabs + Sticky Floating Bar:
1. **Tab `Katalog`**:
   - *Quick Load Chips* untuk antrian yang siap dibayar.
   - Pencarian instan + filter pill kategori.
   - Grid 2 kolom kartu layanan & produk. Saat item disentuh, kuantitas di keranjang bertambah + memberikan *haptic/visual feedback*.
2. **Tab `Keranjang`**:
   - Pemilihan pelanggan, penentuan **Stylist** pada setiap baris layanan, pengubahan qty, dan diskon per item.
3. **Tab `Bayar`**:
   - Input diskon nota, PPN otomatis, *Multi-Payment* (Tunai, QRIS, Transfer, Debit), kalkulasi kembalian *real-time*, dan tombol **Proses Checkout**.
4. **Sticky Floating Bar**:
   - Muncul di atas Bottom Navigation saat kasir berada di Tab `Katalog`, menampilkan total item & nominal keranjang, serta tombol `Lanjut ke Keranjang ->`.

---

## 7. Implementasi PWA untuk Mobile Apps

### 7.1 Web App Manifest (`public/manifest.webmanifest`)

```json
{
    "name": "GlowSalon Management & POS",
    "short_name": "GlowSalon",
    "description": "Aplikasi Mobile POS, Booking, Antrian & Inventori Salon",
    "start_url": "/dashboard?source=pwa",
    "scope": "/",
    "display": "standalone",
    "orientation": "portrait",
    "background_color": "#f8f7fa",
    "theme_color": "#e11d48",
    "icons": [
        {
            "src": "/assets/img/kaiadmin/icon-192.png",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/assets/img/kaiadmin/icon-512.png",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "any maskable"
        }
    ],
    "shortcuts": [
        {
            "name": "Kasir POS",
            "short_name": "POS",
            "url": "/pos",
            "icons": [{ "src": "/assets/img/kaiadmin/icon-192.png", "sizes": "192x192" }]
        },
        {
            "name": "Antrian & Booking",
            "short_name": "Antrian",
            "url": "/bookings",
            "icons": [{ "src": "/assets/img/kaiadmin/icon-192.png", "sizes": "192x192" }]
        }
    ]
}
```

### 7.2 Service Worker dengan Proteksi Endpoint Transaksi (`public/sw.js`)

```javascript
const CACHE_NAME = 'glowsalon-mobile-v2';
const STATIC_ASSETS = [
    '/manifest.webmanifest',
    '/assets/css/bootstrap.min.css',
    '/assets/css/plugins.min.css',
    '/assets/css/kaiadmin.min.css',
    '/assets/js/core/jquery-3.7.1.min.js',
    '/assets/js/core/popper.min.js',
    '/assets/js/core/bootstrap.min.js'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const req = event.request;
    const url = new URL(req.url);

    // 1. Jangan pernah meng-cache request non-GET atau endpoint transaksi sensitif
    if (
        req.method !== 'GET' ||
        url.pathname.startsWith('/pos/checkout') ||
        url.pathname.startsWith('/transactions') ||
        url.pathname.startsWith('/login') ||
        url.pathname.startsWith('/logout')
    ) {
        return;
    }

    // 2. Cache-First untuk aset statis (/assets/)
    if (url.pathname.startsWith('/assets/')) {
        event.respondWith(
            caches.match(req).then(cached => cached || fetch(req))
        );
        return;
    }

    // 3. Network-First untuk halaman navigasi aplikasi
    event.respondWith(
        fetch(req).catch(() => caches.match(req))
    );
});
```

### 7.3 Deteksi Online/Offline & Install Prompt (`template-bottom.blade.php`)

```javascript
// Proteksi tombol submit saat perangkat kehilangan sinyal
function updateConnectionStatus() {
    const isOnline = navigator.onLine;
    const banner = document.getElementById('pwa-offline-banner');
    if (banner) banner.classList.toggle('d-none', isOnline);

    document.querySelectorAll('[data-requires-online="true"]').forEach(btn => {
        btn.disabled = !isOnline;
    });
}
window.addEventListener('online', updateConnectionStatus);
window.addEventListener('offline', updateConnectionStatus);

// Handler Install PWA ke Home Screen
let deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredInstallPrompt = e;
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) installBtn.classList.remove('d-none');
});
```

---

## 8. Implementasi Struk Thermal & Share Struk di Mobile (`receipt.blade.php`)

Selain tombol `window.print()` untuk printer thermal `58mm` dan `80mm`, halaman struk mobile dilengkapi fitur **Bagikan Struk Digital** menggunakan Web Share API:

```javascript
async function shareReceipt(txNumber, customerName, grandTotal, receiptUrl) {
    const shareData = {
        title: `Struk ${txNumber} - GlowSalon`,
        text: `Terima kasih ${customerName}, total transaksi Anda sebesar ${grandTotal}.`,
        url: receiptUrl
    };

    if (navigator.share) {
        await navigator.share(shareData);
    } else {
        await navigator.clipboard.writeText(receiptUrl);
        alert('Tautan struk berhasil disalin!');
    }
}
```

---

## 9. Tahapan Implementasi / Migrasi UI ke Mobile Tab PWA

1. **Tahap 1 — Mobile App Shell & PWA Manifest:**
   - Memperbarui `public/manifest.webmanifest` (`standalone`, `portrait`, `shortcuts`) dan `public/sw.js`.
   - Menambahkan **Fixed Bottom Navigation Bar (5 Tab)**, **Slide-Up Menu Hub (`Lainnya`)**, serta *Offline Banner* dan tombol *Install App* pada `layouts/app.blade.php`.
2. **Tahap 2 — Komponen Top Segmented Tabs & Mobile Card List:**
   - Menerapkan *Sticky Top Segmented Tabs* pada modul `Dashboard`, `Booking & Antrian`, `Stok Produk`, `Stylish`, `Laporan`, dan `Pengaturan`.
   - Mengadaptasi tabel CRUD pada `master.blade.php` agar menampilkan kartu responsif (*mobile card view*) pada layar ponsel.
3. **Tahap 3 — POS Mobile 3-Tab & Floating Checkout Bar:**
   - Mengatur halaman `/pos` menjadi 3 tab tahapan (`[1. Katalog]`, `[2. Keranjang]`, `[3. Bayar]`) dengan *Sticky Floating Checkout Bar* di atas Bottom Navigation.
4. **Tahap 4 — Mobile Receipt (Print + Web Share) & Verifikasi:**
   - Menambahkan fitur *Share Struk* (`navigator.share`) di `pos/receipt.blade.php` dan menguji seluruh alur dengan `php artisan test`.

---

## 10. Checklist Definition of Done (Mobile PWA Edition)

- [x] `manifest.webmanifest` mendukung `display: standalone`, `orientation: portrait`, dan `shortcuts`.
- [x] Service Worker (`sw.js`) meng-cache aset statis tanpa meng-cache endpoint transaksi/checkout.
- [x] Navigasi utama menggunakan **Fixed Bottom Navigation Bar (5 Tab)** yang mematuhi permission `role_menu`.
- [x] Setiap modul multi-subfitur memiliki **Top Segmented Tabs** yang dapat diakses satu tangan tanpa harus membuka sidebar desktop.
- [x] Halaman Kasir POS (`/pos`) mendukung alur 3-Tab (`Katalog` -> `Keranjang` -> `Bayar`) dengan *Floating Checkout Bar*.
- [x] Deteksi status koneksi (`online`/`offline`) melindungi kasir dari gagal kirim saat sinyal terputus.
- [x] Struk mendukung ukuran `58mm`/`80mm` sekaligus *Web Share API* untuk pengiriman struk digital di perangkat mobile.
- [x] Seluruh pengujian otomatis (`php artisan test`) berjalan lulus.
