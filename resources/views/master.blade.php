@extends('layouts.app')

@section('content')
    @php
        $showFormFirst = isset($editData) || $errors->any() || request('tab') === 'form';
    @endphp

    <div id="masterCrudTabGroup" data-tab-group>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="fw-bold mb-0">{{ $titlePage }}</h4>
        </div>

        {{-- Top Segmented Tabs: Daftar Data vs Form Input/Edit --}}
        <div class="mobile-segmented-tabs" role="tablist">
            <button type="button" class="seg-tab {{ $showFormFirst ? '' : 'active' }}" data-tab-target="list"
                onclick="switchMobileTab('masterCrudTabGroup', 'list')">
                <i class="fas fa-list-ul"></i> Daftar Data
                <span class="badge bg-primary ms-1">{{ $items->total() }}</span>
            </button>
            <button type="button" class="seg-tab {{ $showFormFirst ? 'active' : '' }}" data-tab-target="form"
                onclick="switchMobileTab('masterCrudTabGroup', 'form')">
                <i class="fas {{ isset($editData) ? 'fa-edit' : 'fa-plus-circle' }}"></i>
                {{ isset($editData) ? 'Edit Data' : '+ Tambah Data' }}
            </button>
        </div>

        {{-- TAB 1: Table / Mobile Card Listing --}}
        <div data-tab-pane="list" class="{{ $showFormFirst ? 'd-none' : '' }}">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="m-0 font-weight-bold text-primary">{{ $titlePage }}</h6>
                    <form method="GET" action="{{ route($route . '.index') }}" class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="max-width: 260px;">
                            <input type="text" name="search" class="form-control bg-light border-0 small"
                                placeholder="Cari data..." value="{{ $search ?? '' }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-salon" onclick="switchMobileTab('masterCrudTabGroup', 'form')">
                            <i class="fas fa-plus"></i>
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-mobile-card" width="100%" cellspacing="0">
                            <thead>
                                <tr class="text-center">
                                    @foreach ($grid as $column)
                                        <th>{{ $column['label'] }}</th>
                                    @endforeach
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr
                                        class="{{ isset($editData) && $editData->{$primaryKey} == $item->{$primaryKey} ? 'table-warning' : '' }}">
                                        @foreach ($grid as $column)
                                            @if ($column['type'] === 'text')
                                                <td class="{{ $column['class'] ?? 'text-center' }}"
                                                    data-label="{{ $column['label'] }}">
                                                    {{ $item->{$column['field']} ?? '-' }}</td>
                                            @elseif ($column['type'] === 'icon')
                                                <td class="{{ $column['class'] ?? 'text-center' }}"
                                                    data-label="{{ $column['label'] }}"><i
                                                        class="fas {{ $item->{$column['field']} }}"></i></td>
                                            @elseif ($column['type'] === 'badge')
                                                <td class="{{ $column['class'] ?? 'text-center' }}"
                                                    data-label="{{ $column['label'] }}"><span
                                                        class="badge badge-primary">{{ $item->{$column['field']} }}</span>
                                                </td>
                                            @elseif ($column['type'] === 'date')
                                                <td class="{{ $column['class'] ?? 'text-center' }}"
                                                    data-label="{{ $column['label'] }}">
                                                    {{ $item->{$column['field']} ? \Carbon\Carbon::parse($item->{$column['field']})->format('d/m/Y') : '-' }}
                                                </td>
                                            @elseif ($column['type'] === 'datetime')
                                                <td class="{{ $column['class'] ?? 'text-center' }}"
                                                    data-label="{{ $column['label'] }}">
                                                    {{ $item->{$column['field']} ? \Carbon\Carbon::parse($item->{$column['field']})->format('d/m/Y H:i:s') : '-' }}
                                                </td>
                                            @elseif ($column['type'] === 'angka')
                                                <td class="{{ $column['class'] ?? 'text-center' }}"
                                                    data-label="{{ $column['label'] }}">
                                                    @php
                                                        $value = $item->{$column['field']} ?? 0;
                                                        echo number_format($value, 2, ',', '.');
                                                    @endphp
                                                </td>
                                            @elseif ($column['type'] === 'rupiah')
                                                <td class="{{ $column['class'] ?? 'text-center' }}"
                                                    data-label="{{ $column['label'] }}">
                                                    @php
                                                        $value = $item->{$column['field']} ?? 0;
                                                        echo 'Rp ' . number_format($value, 2, ',', '.');
                                                    @endphp
                                                </td>
                                            @else
                                                <td class="{{ $column['class'] ?? 'text-center' }}"
                                                    data-label="{{ $column['label'] }}">
                                                    {{ $item->{$column['field']} ?? '-' }}</td>
                                            @endif
                                        @endforeach
                                        <td class="text-center" data-label="Aksi">
                                            <div class="d-flex justify-content-center gap-1">
                                                @if ($route === 'roles')
                                                    <a href="{{ route('roles.menu', $item->{$primaryKey}) }}"
                                                        class="btn btn-info btn-sm text-white" title="Hak Akses Menu">
                                                        <i class="fas fa-sitemap"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route($route . '.index', ['edit' => $item->{$primaryKey}, 'tab' => 'form']) }}"
                                                    class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route($route . '.destroy', $item->{$primaryKey}) }}"
                                                    method="POST" class="d-inline form-delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($grid) + 2 }}" class="text-center text-muted py-3">
                                            Tidak ada data.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Menampilkan {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }}
                            dari {{ $items->total() }} data
                        </small>
                        <div>
                            {{ $items->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: Form Create / Edit --}}
        <div data-tab-pane="form" class="{{ $showFormFirst ? '' : 'd-none' }}">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                {{ isset($editData) ? 'Edit Data' : 'Tambah Data Baru' }}
                            </h6>
                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="switchMobileTab('masterCrudTabGroup', 'list')">
                                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                            </button>
                        </div>
                        <div class="card-body">
                            @include('components.crud-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
