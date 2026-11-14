@extends('layouts.app')

@section('content')
    <div id="transactionsTabGroup" data-tab-group>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-file-invoice text-primary me-1"></i> Riwayat Transaksi & Refund/Void</h4>
                <small class="text-muted">Transaksi final bersifat immutable. Koreksi dilakukan melalui prosedur Refund/Void tercatat.</small>
            </div>
        </div>

        {{-- Top Segmented Tabs --}}
        <div class="mobile-segmented-tabs" role="tablist">
            <button type="button" class="seg-tab active" data-tab-target="list"
                onclick="switchMobileTab('transactionsTabGroup', 'list')">
                <i class="fas fa-receipt"></i> Daftar Transaksi
            </button>
            <button type="button" class="seg-tab" data-tab-target="summary"
                onclick="switchMobileTab('transactionsTabGroup', 'summary')">
                <i class="fas fa-filter"></i> Filter & Ringkasan Periode
            </button>
        </div>

        {{-- TAB 2: Filter & Ringkasan --}}
        <div data-tab-pane="summary" class="d-none">
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="card card-stats card-round shadow-sm mb-0">
                        <div class="card-body py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Penjualan Lunas</small>
                                <h5 class="fw-bold text-success mb-0">Rp {{ number_format((float) $summaryPaid, 0, ',', '.') }}</h5>
                            </div>
                            <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card card-stats card-round shadow-sm mb-0">
                        <div class="card-body py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Total Refund / Void</small>
                                <h5 class="fw-bold text-danger mb-0">Rp {{ number_format((float) $summaryRefunded, 0, ',', '.') }}</h5>
                            </div>
                            <i class="fas fa-undo-alt fa-2x text-danger opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('transactions.index') }}" class="row g-2 align-items-end">
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>PAID (Lunas)</option>
                                <option value="refunded" {{ $status === 'refunded' ? 'selected' : '' }}>REFUNDED</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="No. Transaksi / Pelanggan..." value="{{ $search ?? '' }}">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- TAB 1: Daftar Transaksi --}}
        <div data-tab-pane="list">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span class="small fw-bold text-primary">
                        Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                    </span>
                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="switchMobileTab('transactionsTabGroup', 'summary')">
                        <i class="fas fa-sliders-h me-1"></i> Ubah Filter
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-mobile-card align-middle">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>No. Transaksi</th>
                                    <th>Waktu</th>
                                    <th>Pelanggan</th>
                                    <th>Item & Stylist</th>
                                    <th>Metode Bayar</th>
                                    <th>Grand Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $trx)
                                    <tr>
                                        <td class="fw-bold text-center" data-label="No. Transaksi">{{ $trx->transaction_number }}</td>
                                        <td class="text-center small" data-label="Waktu">
                                            <div>
                                                {{ $trx->transaction_date?->format('d/m/Y H:i') }}
                                                <div class="text-muted">Kasir: {{ $trx->creator?->name }}</div>
                                            </div>
                                        </td>
                                        <td data-label="Pelanggan">{{ $trx->customer?->name ?? 'Pelanggan Umum' }}</td>
                                        <td class="small" data-label="Item">
                                            <div>
                                                @foreach ($trx->details as $d)
                                                    <div>
                                                        • {{ $d->item_name_snapshot }} (x{{ number_format((float) $d->quantity, 0) }})
                                                        @if ($d->employee)
                                                            <span class="text-muted">[{{ $d->employee->name }}]</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="small text-center" data-label="Pembayaran">
                                            <div>
                                                @foreach ($trx->payments as $p)
                                                    <span class="badge bg-light text-dark border">
                                                        {{ $p->paymentMethod?->name }}: Rp {{ number_format((float) $p->amount, 0, ',', '.') }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold" data-label="Grand Total">
                                            Rp {{ number_format((float) $trx->grand_total, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center" data-label="Status">
                                            <span class="badge bg-{{ $trx->status === 'paid' ? 'success' : 'danger' }} text-uppercase">
                                                {{ $trx->status }}
                                            </span>
                                        </td>
                                        <td class="text-center" data-label="Aksi">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('pos.receipt', $trx->id) }}" class="btn btn-xs btn-info text-white" title="Cetak Struk">
                                                    <i class="fas fa-print"></i> Struk
                                                </a>
                                                @if ($trx->status === 'paid')
                                                    <button type="button" class="btn btn-xs btn-danger"
                                                        data-bs-toggle="modal" data-bs-target="#refundModal{{ $trx->id }}">
                                                        <i class="fas fa-undo"></i> Refund
                                                    </button>
                                                @endif
                                            </div>

                                            @if ($trx->status === 'paid')
                                                <div class="modal fade" id="refundModal{{ $trx->id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content text-start">
                                                            <form action="{{ route('transactions.refund', $trx->id) }}" method="POST">
                                                                @csrf
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title fw-bold text-danger">
                                                                        <i class="fas fa-exclamation-circle me-1"></i> Refund / Void Transaksi {{ $trx->transaction_number }}
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p class="small text-muted">
                                                                        Nilai Refund: <strong>Rp {{ number_format((float) $trx->grand_total, 0, ',', '.') }}</strong>.
                                                                        Tindakan ini akan membalik komisi stylist dan tercatat di Audit Log.
                                                                    </p>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Alasan Refund / Void <span class="text-danger">*</span></label>
                                                                        <textarea name="reason" class="form-control" rows="2" placeholder="Masukkan alasan pembatalan/refund..." required></textarea>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="restock_products" value="1" id="restock_{{ $trx->id }}" checked>
                                                                        <label class="form-check-label small" for="restock_{{ $trx->id }}">
                                                                            Kembalikan stok produk yang terjual ke persediaan (Kartu Stok)
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="btn btn-danger btn-sm" data-requires-online="true">Proses Refund Sekarang</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">Tidak ada data transaksi pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $transactions->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
