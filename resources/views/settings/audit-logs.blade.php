@extends('layouts.app')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-shield-alt me-1"></i> Audit Log Aktivitas Sistem</h6>
            <form method="GET" action="{{ route('audit-logs.index') }}" class="d-flex flex-wrap gap-2">
                <select name="module" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                    <option value="">Semua Modul</option>
                    @foreach ($modules as $mod)
                        <option value="{{ $mod }}" {{ $module === $mod ? 'selected' : '' }}>{{ strtoupper($mod) }}</option>
                    @endforeach
                </select>
                <div class="input-group input-group-sm" style="width: 220px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari aksi / nomor / user..." value="{{ $search ?? '' }}">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-mobile-card">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th>Waktu</th>
                            <th>User</th>
                            <th>Modul</th>
                            <th>Aksi</th>
                            <th>No. Referensi</th>
                            <th>Keterangan</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-center" data-label="Waktu">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td class="text-center" data-label="User">{{ $log->user_name }}</td>
                                <td class="text-center" data-label="Modul"><span class="badge bg-secondary">{{ strtoupper($log->module) }}</span></td>
                                <td class="text-center" data-label="Aksi"><span class="badge badge-primary">{{ $log->action }}</span></td>
                                <td class="text-center" data-label="Referensi">{{ $log->reference_number ?? '-' }}</td>
                                <td data-label="Keterangan">{{ $log->description }}</td>
                                <td class="text-center" data-label="IP">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada catatan audit log.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} log</small>
                <div>{{ $logs->links('pagination::bootstrap-4') }}</div>
            </div>
        </div>
    </div>
@endsection
