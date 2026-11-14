<x-template-top />

<body>
    <div class="loading-page">
        <div class="lds-ring">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
    <div id="Moduleloader-page" class="jssorl-009-spin">
        <img src="{{ url('/') }}/assets/img/spinner.svg">
    </div>
    @php
        $user = Auth::user();
        $allowedMenuIds = $user->roleRelation?->menus()->pluck('menu.mId')->toArray() ?? [];

        $allMenus = App\Models\Menu::where('mIsActive', 1)
            ->when(!empty($allowedMenuIds), function ($query) use ($allowedMenuIds) {
                return $query->whereIn('mId', $allowedMenuIds);
            })
            ->orderBy('mOrder')
            ->get();

        $parentMenus = $allMenus->whereNull('mParentId');
        $childMenus = $allMenus->whereNotNull('mParentId')->groupBy('mParentId');

        $resolveRoute = function (?string $mRoute): ?string {
            if (empty($mRoute)) {
                return null;
            }
            if (\Illuminate\Support\Facades\Route::has($mRoute)) {
                return $mRoute;
            }
            if (\Illuminate\Support\Facades\Route::has($mRoute . '.index')) {
                return $mRoute . '.index';
            }
            return null;
        };

        $isMenuActive = function ($menu) use (
            $childMenus,
            $resolveRoute,
            &$isMenuActive,
        ): bool {
            $routeName = $resolveRoute($menu->mRoute);
            if ($routeName && request()->routeIs($routeName . '*')) {
                return true;
            }
            foreach ($childMenus->get($menu->mId, collect()) as $child) {
                if ($isMenuActive($child)) {
                    return true;
                }
            }
            return false;
        };

        $canAccessRoute = function (string $routePrefix) use ($allMenus): bool {
            if ($allMenus->isEmpty()) {
                return true;
            }
            return $allMenus->contains('mRoute', $routePrefix);
        };

        $brandSetting = $salonProfile ?? \App\Models\SalonSetting::current();
    @endphp

    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <div class="logo-header" data-background-color="dark">
                    <a href="{{ route('dashboard') }}" class="logo salon-brand-title">
                        <i class="fas fa-spa text-warning"></i>
                        <span>{{ $brandSetting->salon_name }}</span>
                    </a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar">
                            <i class="gg-menu-right"></i>
                        </button>
                        <button class="btn btn-toggle sidenav-toggler">
                            <i class="gg-menu-left"></i>
                        </button>
                    </div>
                    <button class="topbar-toggler more">
                        <i class="gg-more-vertical-alt"></i>
                    </button>
                </div>
            </div>
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <ul class="nav nav-secondary">
                        @foreach ($parentMenus as $menu)
                            @include('layouts.partials.sidebar-menu', [
                                'menu' => $menu,
                                'depth' => 1,
                                'ancestors' => [],
                            ])
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <!-- End Sidebar -->

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <div class="logo-header" data-background-color="dark">
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar">
                                <i class="gg-menu-right"></i>
                            </button>
                            <button class="btn btn-toggle sidenav-toggler">
                                <i class="gg-menu-left"></i>
                            </button>
                        </div>
                        <a href="{{ route('dashboard') }}" class="logo salon-brand-title">
                            <i class="fas fa-spa text-warning"></i>
                            <span>{{ $brandSetting->salon_name }}</span>
                        </a>
                        <button class="topbar-toggler more">
                            <i class="gg-more-vertical-alt"></i>
                        </button>
                    </div>
                </div>
                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge badge-salon px-3 py-2 d-none d-sm-inline-block">
                                <i class="fas fa-store me-1"></i> {{ $brandSetting->salon_name }} • {{ now()->timezone($brandSetting->timezone ?: 'Asia/Jakarta')->format('d M Y') }}
                            </span>
                            <span id="pwa-network-badge" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                <i class="fas fa-wifi me-1"></i>Online
                            </span>
                            <button type="button" onclick="triggerPwaInstall()" class="btn btn-xs btn-outline-primary pwa-install-trigger d-none">
                                <i class="fas fa-mobile-alt me-1"></i> Install App
                            </button>
                        </div>

                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="{{ url('/') }}/assets/img/user.png" alt="..."
                                            class="avatar-img rounded-circle" />
                                    </div>
                                    <span class="profile-username">
                                        <span class="op-7">Hi,</span>
                                        <span class="fw-bold">{{ Auth::user()->name }}</span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg">
                                                    <img src="{{ url('/') }}/assets/img/user.png" alt="image profile"
                                                        class="avatar-img rounded" />
                                                </div>
                                                <div class="u-text">
                                                    <h4>{{ Auth::user()->name }}</h4>
                                                    <p class="text-muted mb-1">{{ Auth::user()->email }}</p>
                                                    <span class="badge bg-primary mb-2">{{ Auth::user()->roleRelation?->rNama ?? 'Staff' }}</span>
                                                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-xs btn-danger btn-sm">
                                                            <i class="fas fa-sign-out-alt"></i>&nbsp;Logout
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </li>
                                    </div>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>

            <div class="container">
                {{-- Main Content --}}
                <div class="container-fluid py-3">
                    {{-- Banner Peringatan Offline PWA --}}
                    <div id="pwa-offline-banner" class="alert alert-danger py-2 text-center small fw-bold d-none no-print mb-3">
                        <i class="fas fa-wifi-slash me-1"></i> Perangkat sedang <strong>Offline</strong>. Transaksi kasir, booking, dan mutasi stok dikunci sementara hingga koneksi kembali.
                    </div>

                    {{-- Flash Messages --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>

            <footer class="footer no-print">
                <div class="container-fluid d-flex justify-content-between">
                    <div class="copyright">
                        {{ date('Y') }}, {{ $brandSetting->salon_name }} — {{ $brandSetting->tagline ?: 'Management & POS' }}
                    </div>
                </div>
            </footer>
        </div>
    </div>

    {{-- ── PRIMARY MOBILE/TABLET BOTTOM NAVIGATION BAR (Hanya Tampil di Tablet & HP < 992px) ── --}}
    <nav class="mobile-bottom-nav d-lg-none no-print" aria-label="Navigasi Utama Aplikasi Mobile">
        <a href="{{ route('dashboard') }}"
            class="nav-tab {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>

        @if ($canAccessRoute('bookings'))
            <a href="{{ route('bookings.index') }}"
                class="nav-tab {{ request()->routeIs('bookings*') ? 'active' : '' }}">
                <i class="fas fa-calendar-check"></i>
                <span>Antrian</span>
            </a>
        @endif

        @if ($canAccessRoute('pos'))
            <a href="{{ route('pos.index') }}"
                class="nav-tab {{ request()->routeIs('pos*', 'transactions*', 'cash-shifts*') ? 'active' : '' }}">
                <div class="nav-tab-pos-wrap">
                    <div class="nav-tab-pos-circle">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <span class="mt-1">Kasir POS</span>
                </div>
            </a>
        @endif

        @if ($canAccessRoute('products') || $canAccessRoute('stocks') || $canAccessRoute('services'))
            @php
                $katalogRoute = $canAccessRoute('stocks')
                    ? route('stocks.index')
                    : ($canAccessRoute('products') ? route('products.index') : route('services.index'));
            @endphp
            <a href="{{ $katalogRoute }}"
                class="nav-tab {{ request()->routeIs('stocks*', 'products*', 'product-categories*', 'units*', 'suppliers*', 'services*', 'service-categories*') ? 'active' : '' }}">
                <i class="fas fa-boxes"></i>
                <span>Katalog</span>
            </a>
        @endif

        <button type="button"
            class="nav-tab {{ request()->routeIs('customers*', 'stylists*', 'stylist-schedules*', 'reports*', 'users*', 'roles*', 'menus*', 'audit-logs*', 'salon-settings*', 'payment-methods*', 'ganti-password*') ? 'active' : '' }}"
            data-bs-toggle="offcanvas" data-bs-target="#mobileMenuHub" aria-controls="mobileMenuHub">
            <i class="fas fa-th-large"></i>
            <span>Lainnya</span>
        </button>
    </nav>

    {{-- ── SLIDE-UP BOTTOM SHEET: MENU HUB ("LAINNYA") ────────────────────────── --}}
    <div class="offcanvas offcanvas-bottom mobile-hub-sheet d-lg-none no-print" tabindex="-1" id="mobileMenuHub" aria-labelledby="mobileMenuHubLabel">
        <div class="offcanvas-header border-bottom py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-salon p-2"><i class="fas fa-th-large"></i></span>
                <div>
                    <h6 class="offcanvas-title fw-bold mb-0" id="mobileMenuHubLabel">Menu {{ $brandSetting->salon_name }}</h6>
                    <small class="text-muted">{{ Auth::user()->name }} • {{ Auth::user()->roleRelation?->rNama ?? 'Staff' }}</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="row g-2">
                @foreach ($allMenus as $mItem)
                    @php
                        $resolved = $resolveRoute($mItem->mRoute);
                    @endphp
                    @if ($resolved)
                        <div class="col-4 col-sm-3">
                            <a href="{{ route($resolved) }}"
                                class="mobile-hub-card {{ request()->routeIs($resolved . '*') ? 'active' : '' }}">
                                <div class="hub-icon">
                                    <i class="fas {{ $mItem->mIcon ?: 'fa-layer-group' }}"></i>
                                </div>
                                <span>{{ $mItem->mNama }}</span>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3">
                <button type="button" onclick="triggerPwaInstall()" class="btn btn-sm btn-outline-primary pwa-install-trigger d-none">
                    <i class="fas fa-download me-1"></i> Pasang Aplikasi (PWA)
                </button>
                <form action="{{ route('logout') }}" method="POST" class="ms-auto">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-sign-out-alt me-1"></i> Keluar / Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

<x-template-bottom />
