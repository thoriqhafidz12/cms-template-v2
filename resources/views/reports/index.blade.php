@extends('layouts.app')

@section('content')
    <div id="reportsTabGroup" data-tab-group>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-chart-line text-primary me-1"></i> Laporan Operasional & Komisi</h4>
                <small class="text-muted">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</small>
            </div>
            <form method="GET" action="{{ route('reports.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="date" name="start_date" class="form-control form-control-sm" style="width: 145px;" value="{{ $startDate }}">
                <input type="date" name="end_date" class="form-control form-control-sm" style="width: 145px;" value="{{ $endDate }}">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="{{ route('reports.export-csv', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </form>
        </div>

        {{-- Top Segmented Tabs (4 Tab Laporan) --}}
        <div class="mobile-segmented-tabs no-print" role="tablist">
            <button type="button" class="seg-tab active" data-tab-target="sales"
                onclick="switchMobileTab('reportsTabGroup', 'sales')">
                <i class="fas fa-wallet"></i> Penjualan & Metode Bayar
            </button>
            <button type="button" class="seg-tab" data-tab-target="commissions"
                onclick="switchMobileTab('reportsTabGroup', 'commissions')">
                <i class="fas fa-cut"></i> Komisi Stylist
            </button>
            <button type="button" class="seg-tab" data-tab-target="top-items"
                onclick="switchMobileTab('reportsTabGroup', 'top-items')">
                <i class="fas fa-star"></i> Item Terlaris
            </button>
            <button type="button" class="seg-tab" data-tab-target="stock-booking"
                onclick="switchMobileTab('reportsTabGroup', 'stock-booking')">
                <i class="fas fa-boxes"></i> Stok & Kehadiran
            </button>
        </div>

        {{-- TAB 1: Penjualan & Penerimaan per Metode Pembayaran --}}
        <div data-tab-pane="sales">
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card card-stats card-round shadow-sm mb-0 h-100">
                        <div class="card-body p-3">
                            <p class="card-category small mb-1">Omzet Kotor (Gross)</p>
                            <h5 class="card-title fw-bold text-dark mb-0">Rp {{ number_format($grossSales, 0, ',', '.') }}</h5>
                            <small class="text-muted">{{ $paidCount }} Transaksi Lunas</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-stats card-round shadow-sm mb-0 h-100">
                        <div class="card-body p-3">
                            <p class="card-category small mb-1">Diskon & Refund</p>
                            <h5 class="card-title fw-bold text-danger mb-0">Rp {{ number_format($totalDiscount + $totalRefund, 0, ',', '.') }}</h5>
                            <small class="text-muted">Refund: Rp {{ number_format($totalRefund, 0, ',', '.') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-stats card-round shadow-sm mb-0 h-100">
                        <div class="card-body p-3">
                            <p class="card-category small mb-1">Penjualan Bersih (Net)</p>
                            <h5 class="card-title fw-bold text-success mb-0">Rp {{ number_format($netSales, 0, ',', '.') }}</h5>
                            <small class="text-muted">Avg: Rp {{ number_format($avgTransaction, 0, ',', '.') }}/trx</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-stats card-round shadow-sm mb-0 h-100">
                        <div class="card-body p-3">
                            <p class="card-category small mb-1">Total Komisi Stylist</p>
                            <h5 class="card-title fw-bold text-primary mb-0">Rp {{ number_format($totalCommissionEarned, 0, ',', '.') }}</h5>
                            <small class="text-muted">Booking Selesai: {{ $bookingSummary['completed'] }}/{{ $bookingSummary['total'] }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-wallet me-1"></i> Penerimaan per Metode Pembayaran</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-mobile-card align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>Metode Pembayaran</th>
                                    <th>Jml Bayar</th>
                                    <th>Total Diterima</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($paymentsByMethod as $pm)
                                    <tr>
                                        <td class="fw-bold" data-label="Metode">{{ $pm->paymentMethod?->name ?? '-' }}</td>
                                        <td class="text-center" data-label="Frekuensi">{{ $pm->trx_count }}x</td>
                                        <td class="text-end fw-bold text-success" data-label="Total Diterima">Rp {{ number_format((float) $pm->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada pembayaran tercatat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: Komisi Stylist --}}
        <div data-tab-pane="commissions" class="d-none">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cut me-1"></i> Laporan Performa & Komisi Stylist</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-mobile-card align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>Stylist</th>
                                    <th>Jml Layanan Selesai</th>
                                    <th>Omzet Layanan</th>
                                    <th>Total Komisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stylistCommissions as $sc)
                                    <tr>
                                        <td class="fw-bold" data-label="Stylist">{{ $sc->employee?->name ?? '-' }}</td>
                                        <td class="text-center" data-label="Layanan">{{ $sc->total_services }} layanan</td>
                                        <td class="text-end" data-label="Omzet">Rp {{ number_format((float) $sc->total_omzet, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-primary" data-label="Total Komisi">Rp {{ number_format((float) $sc->total_commission, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data komisi pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 3: Layanan & Produk Terlaris --}}
        <div data-tab-pane="top-items" class="d-none">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-star me-1"></i> Layanan & Produk Terlaris</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-mobile-card align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>Tipe</th>
                                    <th>Nama Item</th>
                                    <th>Qty Terjual</th>
                                    <th>Total Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topItems as $ti)
                                    <tr>
                                        <td class="text-center" data-label="Tipe">
                                            <span class="badge {{ $ti->item_type === 'service' ? 'badge-salon' : 'bg-info text-white' }}">
                                                {{ strtoupper($ti->item_type) }}
                                            </span>
                                        </td>
                                        <td class="fw-bold" data-label="Item">{{ $ti->item_name_snapshot }}</td>
                                        <td class="text-center" data-label="Qty Terjual">{{ number_format((float) $ti->total_qty, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold" data-label="Pendapatan">Rp {{ number_format((float) $ti->total_revenue, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada penjualan pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 4: Stok Minimum & Ringkasan Booking --}}
        <div data-tab-pane="stock-booking" class="d-none">
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-calendar-check me-1"></i> Ringkasan Booking & Kehadiran</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>Total Booking Dibuat:</span>
                                <span class="fw-bold">{{ $bookingSummary['total'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2 text-success">
                                <span>Booking Selesai (Completed):</span>
                                <span class="fw-bold">{{ $bookingSummary['completed'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2 text-warning">
                                <span>Pelanggan Tidak Hadir (No-Show):</span>
                                <span class="fw-bold">{{ $bookingSummary['no_show'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 text-danger">
                                <span>Booking Dibatalkan (Cancelled):</span>
                                <span class="fw-bold">{{ $bookingSummary['cancelled'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-exclamation-circle me-1"></i> Produk di Bawah Stok Minimum</h6>
                            <a href="{{ route('stocks.index', ['tab' => 'mutation']) }}" class="btn btn-xs btn-outline-danger">+ Restock</a>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-bordered table-mobile-card mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-center">Stok</th>
                                            <th class="text-center">Min</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($lowStockProducts as $lp)
                                            <tr>
                                                <td data-label="Produk" class="fw-bold">{{ $lp->name }}</td>
                                                <td class="text-center text-danger fw-bold" data-label="Stok Saat Ini">{{ number_format((float) $lp->current_stock, 0) }} {{ $lp->unit?->symbol }}</td>
                                                <td class="text-center" data-label="Stok Min">{{ number_format((float) $lp->min_stock, 0) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">Seluruh stok produk berada di atas batas minimum.</td>
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
