@extends('layouts.app')

@section('content')
    @php
        $brandSetting = $salonProfile ?? \App\Models\SalonSetting::current();
    @endphp
    <div id="dashboardTabGroup" data-tab-group>
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-1 pb-2">
            <div>
                <h4 class="fw-bold mb-0">
                    <span class="badge bg-primary me-1">{{ $brandSetting->salon_name }}</span>
                    Dashboard — {{ $brandSetting->tagline ?: $brandSetting->salon_name }}
                </h4>
                <small class="text-muted">Ringkasan operasional, antrian berjalan, dan performa stylist hari ini</small>
            </div>
        </div>

        {{-- Top Segmented Tabs --}}
        <div class="mobile-segmented-tabs" role="tablist">
            <button type="button" class="seg-tab active" data-tab-target="summary"
                onclick="switchMobileTab('dashboardTabGroup', 'summary')">
                <i class="fas fa-chart-pie"></i> Ringkasan Hari Ini
            </button>
            <button type="button" class="seg-tab" data-tab-target="queues"
                onclick="switchMobileTab('dashboardTabGroup', 'queues')">
                <i class="fas fa-user-clock"></i> Antrian Berjalan
                <span class="badge bg-danger ms-1">{{ $activeQueuesCount }}</span>
            </button>
            <button type="button" class="seg-tab" data-tab-target="performance"
                onclick="switchMobileTab('dashboardTabGroup', 'performance')">
                <i class="fas fa-award"></i> Stylist & Transaksi
            </button>
        </div>

        {{-- TAB 1: Ringkasan Hari Ini (KPI Cards + Ringkasan Cepat) --}}
        <div data-tab-pane="summary">
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card card-stats card-round mb-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icon-big text-center icon-primary bubble-shadow">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="numbers">
                                    <p class="card-category small mb-0">Booking Hari Ini</p>
                                    <h4 class="card-title mb-0">{{ $bookingsToday }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card card-stats card-round mb-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icon-big text-center icon-info bubble-shadow">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                                <div class="numbers">
                                    <p class="card-category small mb-0">Antrian Aktif</p>
                                    <h4 class="card-title mb-0">{{ $activeQueuesCount }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card card-stats card-round mb-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icon-big text-center icon-success bubble-shadow">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="numbers">
                                    <p class="card-category small mb-0">Omzet ({{ $transactionsTodayCount }} Trx)</p>
                                    <h5 class="card-title fw-bold mb-0">Rp {{ number_format($netSalesToday, 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card card-stats card-round mb-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icon-big text-center {{ $lowStockCount > 0 ? 'icon-danger' : 'icon-secondary' }} bubble-shadow">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <div class="numbers">
                                    <p class="card-category small mb-0">Stok Menipis</p>
                                    <h4 class="card-title mb-0">{{ $lowStockCount }} SKU</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-round shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title mb-0"><i class="fas fa-spa text-primary me-1"></i> Antrian Aktif Saat Ini</div>
                    <span class="badge badge-salon">{{ $activeQueuesCount }} Antrian</span>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-mobile-card align-items-center mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Antrian</th>
                                    <th>Pelanggan</th>
                                    <th>Layanan & Stylist</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activeQueues as $q)
                                    <tr>
                                        <td class="fw-bold text-primary" data-label="Antrian">#{{ str_pad($q->queue_number, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td data-label="Pelanggan">
                                            <div>
                                                <div class="fw-bold">{{ $q->customer?->name ?? 'Walk-In' }}</div>
                                                <small class="text-muted">{{ $q->service_order_number }}</small>
                                            </div>
                                        </td>
                                        <td class="small" data-label="Layanan">
                                            <div>
                                                @foreach ($q->details as $d)
                                                    <div>• {{ $d->service_name_snapshot }} ({{ $d->employee?->name ?? '-' }})</div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge bg-info text-uppercase">{{ str_replace('_', ' ', $q->status) }}</span>
                                        </td>
                                        <td class="text-end" data-label="Aksi">
                                            <a href="{{ route('pos.index', ['service_order_id' => $q->id]) }}" class="btn btn-xs btn-salon">
                                                <i class="fas fa-cash-register me-1"></i> Kasir
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Tidak ada antrian yang sedang menunggu saat ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: Antrian Layanan Aktif --}}
        <div data-tab-pane="queues" class="d-none">
            <div class="card card-round shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title mb-0"><i class="fas fa-spa text-primary me-1"></i> Antrian & Layanan Berlangsung Hari Ini</div>
                    <a href="{{ route('bookings.index') }}" class="btn btn-xs btn-outline-primary">Kelola Semua Antrian</a>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-mobile-card align-items-center mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Antrian</th>
                                    <th>Pelanggan</th>
                                    <th>Layanan & Stylist</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activeQueues as $q)
                                    <tr>
                                        <td class="fw-bold text-primary" data-label="Antrian">#{{ str_pad($q->queue_number, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td data-label="Pelanggan">
                                            <div>
                                                <div class="fw-bold">{{ $q->customer?->name ?? 'Walk-In' }}</div>
                                                <small class="text-muted">{{ $q->service_order_number }}</small>
                                            </div>
                                        </td>
                                        <td class="small" data-label="Layanan">
                                            <div>
                                                @foreach ($q->details as $d)
                                                    <div>• {{ $d->service_name_snapshot }} ({{ $d->employee?->name ?? '-' }})</div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge bg-info text-uppercase">{{ str_replace('_', ' ', $q->status) }}</span>
                                        </td>
                                        <td class="text-end" data-label="Aksi">
                                            <a href="{{ route('pos.index', ['service_order_id' => $q->id]) }}" class="btn btn-xs btn-salon">
                                                <i class="fas fa-cash-register me-1"></i> Bayar di POS
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Tidak ada antrian yang sedang menunggu saat ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 3: Performa Stylist & Transaksi Terakhir --}}
        <div data-tab-pane="performance" class="d-none">
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-round shadow-sm mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="card-title mb-0"><i class="fas fa-award text-warning me-1"></i> Top Stylist Bulan Ini</div>
                            <a href="{{ route('reports.index', ['tab' => 'commissions']) }}" class="btn btn-xs btn-outline-secondary">Detail</a>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-mobile-card align-items-center mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Stylist</th>
                                            <th class="text-center">Layanan</th>
                                            <th class="text-end">Komisi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($topStylistsThisMonth as $ts)
                                            <tr>
                                                <td class="fw-bold" data-label="Stylist">{{ $ts->employee?->name ?? '-' }}</td>
                                                <td class="text-center" data-label="Layanan">{{ $ts->service_count }}x</td>
                                                <td class="text-end fw-bold text-primary" data-label="Komisi">Rp {{ number_format((float) $ts->total_commission, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">Belum ada komisi tercatat bulan ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-round shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="card-title mb-0"><i class="fas fa-receipt text-success me-1"></i> Transaksi POS Terakhir</div>
                            <a href="{{ route('transactions.index') }}" class="btn btn-xs btn-outline-secondary">Semua</a>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-mobile-card align-items-center mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>No. Struk</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentTransactions as $trx)
                                            <tr>
                                                <td data-label="No. Struk">
                                                    <div>
                                                        <a href="{{ route('pos.receipt', $trx->id) }}" class="fw-bold text-decoration-none">
                                                            {{ $trx->transaction_number }}
                                                        </a>
                                                        <div class="small text-muted">{{ $trx->customer?->name ?? 'Umum' }}</div>
                                                    </div>
                                                </td>
                                                <td class="text-end fw-bold" data-label="Total">Rp {{ number_format((float) $trx->grand_total, 0, ',', '.') }}</td>
                                                <td class="text-center" data-label="Status">
                                                    <span class="badge bg-{{ $trx->status === 'paid' ? 'success' : 'danger' }}">
                                                        {{ strtoupper($trx->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">Belum ada transaksi.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
