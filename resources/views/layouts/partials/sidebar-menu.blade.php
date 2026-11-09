{{--
    Item menu sidebar KaiAdmin Lite — dirender rekursif, mendukung collapse di dalam collapse (level 2+).

    Level 1: <li class="nav-item"> + data-bs-toggle="collapse" -> <ul class="nav nav-collapse">
             Icon + <p>label</p> + <span class="caret"></span> untuk parent
    Level 2+: <li><a ...><span class="sub-item">label</span>...</a></li>

    Variabel dari app.blade.php: $childMenus, $resolveRoute, $isMenuActive.
    Parameter include: $menu (wajib), $depth (default 1), $ancestors (default []) untuk
    penjaga cycle bila data mParentId membentuk loop.
--}}
@php
    $menuId = $menu->mId;
    $children = $childMenus->get($menuId, collect());
    $routeName = $resolveRoute($menu->mRoute);
    $hasChildren = $children->isNotEmpty();
    $isCollapse = $hasChildren || !$routeName;
    $activeTree = $isMenuActive($menu); // menu ini ATAU turunannya aktif
    $activeSelf = $routeName && request()->routeIs($routeName . '*'); // hanya link menu ini sendiri
@endphp

@if ($depth <= 1)
    @if ($isCollapse)
        {{-- Level 1: Menu yang punya anak (collapse parent) --}}
        <li class="nav-item {{ $activeTree ? 'active' : '' }}">
            <a data-bs-toggle="collapse" href="#collapseMenu{{ $menuId }}"
                class="{{ $activeTree ? '' : 'collapsed' }}" aria-expanded="{{ $activeTree ? 'true' : 'false' }}"
                aria-controls="collapseMenu{{ $menuId }}">
                <i class="fas {{ $menu->mIcon ?: 'fa-folder' }}"></i>
                <p>{{ $menu->mNama }}</p>
                <span class="caret"></span>
            </a>
            <div class="collapse {{ $activeTree ? 'show' : '' }}" id="collapseMenu{{ $menuId }}">
                <ul class="nav nav-collapse">
                    @if ($routeName)
                        {{-- Parent juga punya link sendiri --}}
                        <li>
                            <a href="{{ route($routeName) }}">
                                <span class="sub-item">{{ $menu->mNama }}</span>
                            </a>
                        </li>
                    @endif
                    @foreach ($children as $child)
                        @if (!in_array($child->mId, $ancestors, true))
                            @include('layouts.partials.sidebar-menu', [
                                'menu' => $child,
                                'depth' => $depth + 1,
                                'ancestors' => array_merge($ancestors, [$menuId]),
                            ])
                        @endif
                    @endforeach
                </ul>
            </div>
        </li>
    @else
        {{-- Level 1: Link langsung (tidak punya anak) --}}
        <li class="nav-item {{ $activeSelf ? 'active' : '' }}">
            <a href="{{ route($routeName) }}">
                <i class="fas {{ $menu->mIcon }}"></i>
                <p>{{ $menu->mNama }}</p>
            </a>
        </li>
    @endif
@else
    {{-- Level 2+: Sub-item di dalam collapse --}}
    @if ($isCollapse)
        <li>
            <a data-bs-toggle="collapse" href="#submenu{{ $menuId }}"
                class="{{ $activeTree ? '' : 'collapsed' }}" aria-expanded="{{ $activeTree ? 'true' : 'false' }}"
                aria-controls="submenu{{ $menuId }}">
                <span class="sub-item">{{ $menu->mNama }}</span>
                <span class="caret"></span>
            </a>
            <div class="collapse {{ $activeTree ? 'show' : '' }}" id="submenu{{ $menuId }}">
                <ul class="nav nav-collapse subnav">
                    @if ($routeName)
                        {{-- Submenu juga punya link sendiri --}}
                        <li>
                            <a href="{{ route($routeName) }}">
                                <span class="sub-item">{{ $menu->mNama }}</span>
                            </a>
                        </li>
                    @endif
                    @foreach ($children as $child)
                        @if (!in_array($child->mId, $ancestors, true))
                            @include('layouts.partials.sidebar-menu', [
                                'menu' => $child,
                                'depth' => $depth + 1,
                                'ancestors' => array_merge($ancestors, [$menuId]),
                            ])
                        @endif
                    @endforeach
                </ul>
            </div>
        </li>
    @else
        <li>
            <a href="{{ $routeName ? route($routeName) : '#' }}">
                <span class="sub-item">{{ $menu->mNama }}</span>
            </a>
        </li>
    @endif
@endif
