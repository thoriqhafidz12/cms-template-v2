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
        <img src="assets/img/spinner.svg">
    </div>
    <div class="bottom-slide">
        <div class="wrapper">
            <!-- Sidebar -->
            <div class="sidebar" data-background-color="dark">
                <div class="sidebar-logo">
                    <!-- Logo Header -->
                    <div class="logo-header" data-background-color="dark">
                        <a href="index.html" class="logo">
                            <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand"
                                height="20" />
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
                    <!-- End Logo Header -->
                </div>
                <div class="sidebar-wrapper scrollbar scrollbar-inner">
                    <div class="sidebar-content">
                        <ul class="nav nav-secondary">
                            @php
                                // Ambil ID menu yang diizinkan untuk role user saat ini
                                $user = Auth::user();
                                $allowedMenuIds = $user->roleRelation?->menus()->pluck('menu.mId')->toArray() ?? [];

                                $allMenus = App\Models\Menu::where('mIsActive', 1)
                                    ->when(!empty($allowedMenuIds), function ($query) use ($allowedMenuIds) {
                                        // Jika role punya assignment → filter
                                        return $query->whereIn('mId', $allowedMenuIds);
                                    })
                                    ->orderBy('mOrder')
                                    ->get();

                                $parentMenus = $allMenus->whereNull('mParentId');
                                $childMenus = $allMenus->whereNotNull('mParentId')->groupBy('mParentId');

                                /**
                                 * Resolve route name dari mRoute prefix.
                                 * Cek: 'dashboard' → Route::has('dashboard') = true
                                 *      'users'    → Route::has('users') = false, tapi Route::has('users.index') = true
                                 */
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

                                /**
                                 * Cek apakah menu ini (atau turunannya di level berapa pun) sedang aktif.
                                 * Dipakai juga oleh partial sidebar-menu untuk membuka collapse induk
                                 * ketika child di level 2+ sedang aktif.
                                 */
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
                            @endphp

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
                        <!-- Logo Header -->
                        <div class="logo-header" data-background-color="dark">
                            <a href="index.html" class="logo">
                                <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand"
                                    height="20" />
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
                        <!-- End Logo Header -->
                    </div>
                    <!-- Navbar Header -->
                    <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                        <div class="container-fluid">
                            {{-- <nav
                            class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <button type="submit" class="btn btn-search pe-1">
                                        <i class="fa fa-search search-icon"></i>
                                    </button>
                                </div>
                                <input type="text" placeholder="Search ..." class="form-control" />
                            </div>
                        </nav> --}}

                            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                                <li class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none">
                                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"
                                        role="button" aria-expanded="false" aria-haspopup="true">
                                        <i class="fa fa-search"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-search animated fadeIn">
                                        <form class="navbar-left navbar-form nav-search">
                                            <div class="input-group">
                                                <input type="text" placeholder="Search ..." class="form-control" />
                                            </div>
                                        </form>
                                    </ul>
                                </li>

                                <li class="nav-item topbar-user dropdown hidden-caret">
                                    <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
                                        aria-expanded="false">
                                        <div class="avatar-sm">
                                            <img src="assets/img/profile.jpg" alt="..."
                                                class="avatar-img rounded-circle" />
                                        </div>
                                        <span class="profile-username">
                                            <span class="op-7">Hi,</span>
                                            <span class="fw-bold">@php echo Auth::user()->name; @endphp</span>
                                        </span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-user animated fadeIn">
                                        <div class="dropdown-user-scroll scrollbar-outer">
                                            <li>
                                                <div class="user-box">
                                                    <div class="avatar-lg">
                                                        <img src="assets/img/profile.jpg" alt="image profile"
                                                            class="avatar-img rounded" />
                                                    </div>
                                                    <div class="u-text">
                                                        <h4>@php echo Auth::user()->name; @endphp</h4>
                                                        <p class="text-muted">@php echo Auth::user()->email; @endphp</p>
                                                        <a href="profile.html"
                                                            class="btn btn-xs btn-secondary btn-sm"><i
                                                                class="fas fa-sign-out-alt"></i>&nbsp;Logout</a>
                                                    </div>
                                                </div>
                                            </li>
                                            {{-- <li>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">My Profile</a>
                                            <a class="dropdown-item" href="#">My Balance</a>
                                            <a class="dropdown-item" href="#">Inbox</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Account Setting</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Logout</a>
                                        </li> --}}
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
                    <div class="container-fluid py-4">
                        {{-- Flash Messages --}}
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </div>

                <footer class="footer">
                    <div class="container-fluid d-flex justify-content-between">
                        <div class="copyright">
                            {{ date('Y') }}, CMS-TH
                        </div>
                    </div>
                </footer>

            </div>
        </div>
    </div>
</body>

<x-template-bottom />
