@extends('layouts.app')

@section('content')
    <div id="shiftsTabGroup" data-tab-group>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-vault text-primary me-1"></i> Manajemen Shift Kasir</h4>
                <small class="text-muted">Buka dan tutup shift kasir untuk rekonsiliasi uang tunai laci kasir.</small>
            </div>
        </div>

        {{-- Top Segmented Tabs --}}
        <div class="mobile-segmented-tabs" role="tablist">
            <button type="button" class="seg-tab active" data-tab-target="active-shift"
                onclick="switchMobileTab('shiftsTabGroup', 'active-shift')">
                <i class="fas {{ $activeShift ? 'fa-unlock text-warning' : 'fa-door-open' }}"></i>
                {{ $activeShift ? 'Shift Aktif & Rekonsiliasi' : 'Buka Shift Baru' }}
            </button>
            <button type="button" class="seg-tab" data-tab-target="history"
                onclick="switchMobileTab('shiftsTabGroup', 'history')">
                <i class="fas fa-list-alt"></i> Riwayat Shift Kasir
            </button>
        </div>

        {{-- TAB 1: Active Shift / Buka Shift --}}
        <div data-tab-pane="active-shift">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    @if ($activeShift)
                        <div class="card shadow-sm mb-4 border-top border-4 border-success">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-success">
                                    <i class="fas fa-unlock me-1"></i> Shift Aktif: {{ $activeShift->shift_number }}
                                </h6>
                                <span class="badge bg-success">OPEN</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">Kasir Pembuka:</span>
                                    <span class="fw-bold">{{ $activeShift->user?->name }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">Waktu Buka:</span>
                                    <span>{{ $activeShift->opened_at?->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">Modal Awal Tunai:</span>
                                    <span class="fw-bold">Rp {{ number_format((float) $activeShift->opening_cash, 0, ',', '.') }}</span>
                                </div>
                                <div class="p-3 bg-light rounded border mb-3 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">Saldo Kas Sistem (Expected):</span>
                                    <span class="fs-5 fw-bold text-primary">Rp {{ number_format((float) $activeShift->expected_cash, 0, ',', '.') }}</span>
                                </div>

                                <hr>
                                <h6 class="fw-bold text-danger mb-2"><i class="fas fa-lock me-1"></i> Tutup Shift Kasir</h6>
                                <form action="{{ route('cash-shifts.close', $activeShift->id) }}" method="POST">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label fw-bold">Uang Tunai Fisik di Laci (Rp) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="actual_cash" class="form-control"
                                            value="{{ (float) $activeShift->expected_cash }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Catatan Penutupan Shift</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan selisih kas atau serah terima...">{{ $activeShift->notes }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger w-100 py-2" data-requires-online="true">
                                        <i class="fas fa-lock me-1"></i> Tutup Shift & Rekonsiliasi Kas
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="card shadow-sm mb-4 border-top border-4 border-warning">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-door-open me-1"></i> Buka Shift Kasir Baru</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning small">
                                    Belum ada shift kasir yang terbuka. Buka shift kasir terlebih dahulu sebelum memproses pembayaran di POS.
                                </div>
                                <form action="{{ route('cash-shifts.open') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Modal Awal Kas Laci (Rp) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="opening_cash" class="form-control" value="200000" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Catatan Shift</label>
                                        <input type="text" name="notes" class="form-control" placeholder="Shift Pagi / Siang">
                                    </div>
                                    <button type="submit" class="btn btn-salon w-100 py-2" data-requires-online="true">
                                        <i class="fas fa-check-circle me-1"></i> Buka Shift Kasir Sekarang
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- TAB 2: Shift History --}}
        <div data-tab-pane="history" class="d-none">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Shift Kasir</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-mobile-card align-middle">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>No. Shift</th>
                                    <th>Kasir</th>
                                    <th>Waktu Buka/Tutup</th>
                                    <th>Modal Awal</th>
                                    <th>Kas Sistem</th>
                                    <th>Kas Aktual</th>
                                    <th>Selisih</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($shifts as $sh)
                                    <tr>
                                        <td class="fw-bold text-center" data-label="Shift">{{ $sh->shift_number }}</td>
                                        <td class="text-center" data-label="Kasir">{{ $sh->user?->name }}</td>
                                        <td class="small" data-label="Waktu">
                                            <div>
                                                <div>Buka: {{ $sh->opened_at?->format('d/m H:i') }}</div>
                                                @if ($sh->closed_at)
                                                    <div class="text-muted">Tutup: {{ $sh->closed_at->format('d/m H:i') }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end" data-label="Modal">Rp {{ number_format((float) $sh->opening_cash, 0, ',', '.') }}</td>
                                        <td class="text-end" data-label="Sistem">Rp {{ number_format((float) $sh->expected_cash, 0, ',', '.') }}</td>
                                        <td class="text-end" data-label="Aktual">
                                            {{ $sh->actual_cash !== null ? 'Rp ' . number_format((float) $sh->actual_cash, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-end fw-bold {{ ($sh->difference_cash ?? 0) < 0 ? 'text-danger' : 'text-success' }}" data-label="Selisih">
                                            {{ $sh->difference_cash !== null ? 'Rp ' . number_format((float) $sh->difference_cash, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-center" data-label="Status">
                                            <span class="badge bg-{{ $sh->status === 'open' ? 'success' : 'secondary' }} text-uppercase">
                                                {{ $sh->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada riwayat shift kasir.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $shifts->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
