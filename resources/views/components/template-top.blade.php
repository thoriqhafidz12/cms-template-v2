<!DOCTYPE html>
<html lang="id">

<head>
    @php
        $brandSetting = $salonProfile ?? \App\Models\SalonSetting::current();
    @endphp
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>{{ $brandSetting->salon_name }} — {{ $brandSetting->tagline ?: 'Management & POS' }}</title>
    <meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport" />
    <meta name="theme-color" content="#841CC9" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="{{ $brandSetting->salon_name }}" />
    <link rel="manifest" href="{{ url('/') }}/manifest.webmanifest" />
    <link rel="icon" href="{{ url('/') }}/assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="{{ url('/') }}/assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {
                families: ["Public Sans:300,400,500,600,700"]
            },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["{{ url('/') }}/assets/css/fonts.min.css"],
            },
            active: function() {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="{{ url('/') }}/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="{{ url('/') }}/assets/css/plugins.min.css" />
    <link rel="stylesheet" href="{{ url('/') }}/assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="{{ url('/') }}/assets/css/custom.css" />
    <link rel="stylesheet" href="{{ url('/') }}/assets/css/demo.css" />
    <style>
        :root {
            --salon-main: #D4C7B6;
            --salon-main-border: #BFB09C;
            --salon-second: #FFFFFF;
            --salon-accent: #841CC9;
            --salon-accent-dark: #6B15A3;
            --salon-primary: #841CC9;
            --salon-primary-dark: #6B15A3;
            --salon-secondary: #D4C7B6;
            --salon-soft: #F5EAFC;
            --salon-border: #DFBFF5;
            --salon-bg: #D4C7B6;
            --salon-ink: #231F20;
            --bottom-nav-height: 66px;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-top: env(safe-area-inset-top, 0px);
        }

        body {
            background-color: var(--salon-main) !important;
            color: var(--salon-ink);
            overscroll-behavior-y: contain;
            -webkit-tap-highlight-color: transparent;
        }

        /* Latar belakang utama menggunakan Main Color (#D4C7B6) & Kartu menggunakan Second Color (#FFFFFF) */
        .main-panel {
            min-height: 100vh !important;
            height: auto !important;
            padding-bottom: 24px !important;
            background-color: var(--salon-main) !important;
        }
        .main-panel > .container {
            margin-top: 72px !important;
            overflow: visible !important;
            min-height: calc(100vh - 135px);
            background-color: var(--salon-main) !important;
        }
        .main-header {
            background-color: var(--salon-second) !important;
            border-bottom: 1px solid var(--salon-main-border) !important;
        }
        .footer {
            background-color: var(--salon-second) !important;
            border-top: 1px solid var(--salon-main-border) !important;
        }
        .card {
            background-color: var(--salon-second) !important;
            border: 1px solid rgba(191, 176, 156, 0.55);
        }

        /* Perbaikan Header pada tampilan Tablet & HP (<= 991.98px) agar tidak terpotong */
        @media screen and (max-width: 991.98px) {
            .main-panel {
                padding-bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 84px) !important;
            }
            .main-header {
                z-index: 1025 !important;
            }
            .main-header .main-header-logo .logo-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                height: 70px !important;
                padding: 0 16px !important;
                position: relative !important;
            }
            .main-header .main-header-logo .logo-header .nav-toggle {
                position: static !important;
                transform: none !important;
                left: auto !important;
                right: auto !important;
                order: 1 !important;
                height: auto !important;
            }
            .main-header .main-header-logo .logo-header .logo {
                position: static !important;
                transform: none !important;
                left: auto !important;
                order: 2 !important;
                margin: 0 auto !important;
                opacity: 1 !important;
            }
            .main-header .main-header-logo .logo-header .more {
                order: 3 !important;
                margin-left: 0 !important;
                color: var(--salon-ink) !important;
                line-height: 1 !important;
                padding: 6px !important;
            }
            .topbar_open .main-panel > .container {
                margin-top: 138px !important;
            }
        }

        /* Sidebar & Logo Header menggunakan Main Color (#D4C7B6), Permukaan (#FFFFFF) & Aksen (#841CC9) */
        .sidebar[data-background-color="dark"] {
            background: var(--salon-second) !important;
            border-right: 1px solid var(--salon-main-border) !important;
        }
        .logo-header[data-background-color="dark"] {
            background: var(--salon-main) !important;
            border-bottom: 1px solid var(--salon-main-border) !important;
        }
        .logo-header[data-background-color="dark"] .btn-toggle,
        .logo-header[data-background-color="dark"] .btn-toggle i,
        .logo-header[data-background-color="dark"] .more,
        .logo-header[data-background-color="dark"] .more i {
            color: var(--salon-ink) !important;
        }
        .sidebar[data-background-color="dark"] .nav > .nav-item > a {
            color: #3f3832 !important;
        }
        .sidebar[data-background-color="dark"] .nav > .nav-item > a i,
        .sidebar[data-background-color="dark"] .nav > .nav-item > a p,
        .sidebar[data-background-color="dark"] .nav > .nav-item > a .caret {
            color: #3f3832 !important;
        }
        .sidebar[data-background-color="dark"] .nav > .nav-item > a:hover {
            background: var(--salon-soft) !important;
            color: var(--salon-accent) !important;
        }
        .sidebar[data-background-color="dark"] .nav > .nav-item > a:hover i,
        .sidebar[data-background-color="dark"] .nav > .nav-item > a:hover p {
            color: var(--salon-accent) !important;
        }
        .sidebar[data-background-color="dark"] .nav > .nav-item.active > a {
            background: var(--salon-accent) !important;
            color: var(--salon-second) !important;
            box-shadow: 0 4px 12px rgba(132, 28, 201, 0.25);
        }
        .sidebar[data-background-color="dark"] .nav > .nav-item.active > a i,
        .sidebar[data-background-color="dark"] .nav > .nav-item.active > a p,
        .sidebar[data-background-color="dark"] .nav > .nav-item.active > a .caret {
            color: var(--salon-second) !important;
        }
        .sidebar[data-background-color="dark"] .nav-collapse li > a {
            color: #4a423b !important;
        }
        .sidebar[data-background-color="dark"] .nav-collapse li.active > a,
        .sidebar[data-background-color="dark"] .nav-collapse li > a:hover {
            color: var(--salon-accent) !important;
            font-weight: 700 !important;
            background: var(--salon-soft) !important;
        }
        .sidebar[data-background-color="dark"] .nav-collapse li.active > a .sub-item::before {
            background: var(--salon-accent) !important;
        }

        /* Override Warna Aksen Utama (#841CC9) */
        .btn-salon,
        .btn-primary {
            background: #841CC9 !important;
            background-color: #841CC9 !important;
            color: #FFFFFF !important;
            border-color: #841CC9 !important;
            font-weight: 600;
        }
        .btn-salon:hover, .btn-salon:focus,
        .btn-primary:hover, .btn-primary:focus {
            background: #6B15A3 !important;
            background-color: #6B15A3 !important;
            color: #FFFFFF !important;
            border-color: #6B15A3 !important;
        }
        .btn-outline-primary {
            color: #841CC9 !important;
            border-color: #841CC9 !important;
        }
        .btn-outline-primary:hover, .btn-outline-primary:focus {
            background-color: #841CC9 !important;
            color: #FFFFFF !important;
            border-color: #841CC9 !important;
        }
        .bg-primary,
        .card-primary {
            background: #841CC9 !important;
            background-color: #841CC9 !important;
            color: #FFFFFF !important;
        }
        .text-primary {
            color: #841CC9 !important;
        }
        .badge-salon {
            background-color: #F5EAFC;
            color: #841CC9;
            border: 1px solid #DFBFF5;
            font-weight: 600;
        }
        .salon-brand-title {
            color: var(--salon-ink) !important;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 0.4px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .salon-brand-title i {
            color: #841CC9 !important;
        }
        .salon-brand-title:hover {
            color: #841CC9 !important;
            text-decoration: none;
        }

        /* ── Top Segmented Tabs (Permukaan Putih di Atas Background #D4C7B6) ── */
        .mobile-segmented-tabs {
            position: relative;
            z-index: 1;
            background: #FFFFFF;
            border: 1px solid var(--salon-main-border);
            padding: 6px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(35, 31, 32, 0.05);
        }
        .mobile-segmented-tabs .seg-tab {
            flex: 1 1 auto;
            min-height: 40px;
            padding: 8px 14px;
            border-radius: 9px;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--salon-ink);
            background: transparent;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .mobile-segmented-tabs .seg-tab:hover {
            background: var(--salon-soft);
            color: #841CC9;
        }
        .mobile-segmented-tabs .seg-tab.active {
            background: #841CC9 !important;
            color: #FFFFFF !important;
            box-shadow: 0 3px 10px rgba(132, 28, 201, 0.28);
        }
        .mobile-segmented-tabs .seg-tab .badge {
            font-size: 0.7rem;
            border-radius: 999px;
        }

        /* ── Bottom Navigation Bar: HANYA Tampil di Tablet & HP (< 992px) ── */
        .mobile-bottom-nav {
            display: none;
        }
        @media screen and (max-width: 991.98px) {
            .mobile-bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: calc(var(--bottom-nav-height) + var(--safe-bottom));
                padding-bottom: var(--safe-bottom);
                background: #FFFFFF;
                border-top: 1px solid var(--salon-main-border);
                display: flex;
                justify-content: space-around;
                align-items: center;
                z-index: 1035;
                box-shadow: 0 -4px 18px rgba(35, 31, 32, 0.08);
            }
        }
        .mobile-bottom-nav .nav-tab {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            color: #5c5349;
            text-decoration: none;
            font-size: 0.72rem;
            font-weight: 600;
            gap: 3px;
            border: none;
            background: transparent;
            transition: color 0.15s ease;
        }
        .mobile-bottom-nav .nav-tab i {
            font-size: 1.15rem;
        }
        .mobile-bottom-nav .nav-tab.active {
            color: #841CC9;
        }
        .mobile-bottom-nav .nav-tab.active i {
            color: #841CC9;
        }
        .mobile-bottom-nav .nav-tab-pos-wrap {
            position: relative;
            top: -12px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .mobile-bottom-nav .nav-tab-pos-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #841CC9;
            color: #FFFFFF !important;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(132, 28, 201, 0.38);
            border: 3px solid #FFFFFF;
        }
        .mobile-bottom-nav .nav-tab-pos-circle i {
            font-size: 1.2rem;
            color: #FFFFFF !important;
        }

        /* ── Sticky Floating POS Checkout Bar (Hanya di Tablet & HP < 992px) ─ */
        .pos-floating-bar {
            display: none;
        }
        @media screen and (max-width: 991.98px) {
            .pos-floating-bar {
                position: fixed;
                left: 12px;
                right: 12px;
                bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 10px);
                z-index: 1030;
                background: #841CC9;
                border: 1px solid #FFFFFF;
                color: #FFFFFF;
                border-radius: 16px;
                padding: 10px 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: 0 10px 25px rgba(132, 28, 201, 0.35);
            }
        }

        /* ── Menu Hub Bottom Sheet Grid ──────────────────────────────────── */
        .mobile-hub-sheet {
            border-top-left-radius: 20px !important;
            border-top-right-radius: 20px !important;
            max-height: 82vh !important;
            background-color: #FFFFFF !important;
        }
        .mobile-hub-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 12px 8px;
            border-radius: 14px;
            background: #FFFFFF;
            border: 1px solid var(--salon-main-border);
            color: var(--salon-ink);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 600;
            height: 100%;
            transition: all 0.15s ease;
        }
        .mobile-hub-card:hover, .mobile-hub-card.active {
            background: #F5EAFC;
            border-color: #841CC9;
            color: #841CC9;
        }
        .mobile-hub-card .hub-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #F5EAFC;
            color: #841CC9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 6px;
        }

        /* ── Responsive Mobile Card View untuk Tabel (.table-mobile-card) ── */
        @media (max-width: 767.98px) {
            .table-mobile-card thead {
                display: none;
            }
            .table-mobile-card,
            .table-mobile-card tbody,
            .table-mobile-card tr,
            .table-mobile-card td {
                display: block;
                width: 100%;
            }
            .table-mobile-card tr {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                margin-bottom: 10px;
                padding: 8px 12px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            }
            .table-mobile-card td {
                border: none !important;
                border-bottom: 1px dashed #f1f5f9 !important;
                padding: 6px 0 !important;
                text-align: right !important;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
            }
            .table-mobile-card td:last-child {
                border-bottom: none !important;
                padding-top: 8px !important;
            }
            .table-mobile-card td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 0.76rem;
                color: #64748b;
                text-align: left;
                flex-shrink: 0;
            }
        }
    </style>
    @stack('styles')
</head>
