@extends('layouts.app')

@section('content')
    <div id="stocksTabGroup" data-tab-group>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-boxes text-primary me-1"></i> Mutasi & Kartu Stok Produk</h4>
                <small class="text-muted">Seluruh perubahan stok tercatat di ledger (penerimaan, penjualan POS, pemakaian treatment, penyesuaian).</small>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($lowStockProducts->isNotEmpty())
            <div class="alert alert-warning py-2">
                <strong><i class="fas fa-exclamation-triangle me-1"></i> Peringatan Stok Minimum ({{ $lowStockProducts->count() }} Produk):</strong>
                @foreach ($lowStockProducts as $lp)
                    <span class="badge bg-danger ms-1">{{ $lp->name }} (Sisa: {{ number_format((float) $lp->current_stock, 0) }} {{ $lp->unit?->symbol }})</span>
                @endforeach
            </div>
        @endif

        {{-- Top Segmented Tabs --}}
        <div class="mobile-segmented-tabs" role="tablist">
            <button type="button" class="seg-tab {{ $errors->any() ? '' : 'active' }}" data-tab-target="ledger"
                onclick="switchMobileTab('stocksTabGroup', 'ledger')">
                <i class="fas fa-clipboard-list"></i> Kartu Stok (Ledger)
            </button>
            <button type="button" class="seg-tab {{ $errors->any() ? 'active' : '' }}" data-tab-target="mutation"
                onclick="switchMobileTab('stocksTabGroup', 'mutation')">
                <i class="fas fa-plus-circle"></i> + Posting Mutasi Stok
            </button>
        </div>

        {{-- TAB 1: Kartu Stok / Ledger --}}
        <div data-tab-pane="ledger" class="{{ $errors->any() ? 'd-none' : '' }}">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="m-0 font-weight-bold text-primary">Kartu Stok (Stock Ledger)</h6>
                    <form method="GET" action="{{ route('stocks.index') }}" class="d-flex flex-wrap gap-2">
                        <select name="product_id" class="form-select form-select-sm" style="width: 210px;" onchange="this.form.submit()">
                            <option value="">-- Semua Produk --</option>
                            @foreach ($products as $prd)
                                <option value="{{ $prd->id }}" {{ $productId == $prd->id ? 'selected' : '' }}>
                                    {{ $prd->name }} (Stok: {{ number_format((float) $prd->current_stock, 0) }})
                                </option>
                            @endforeach
                        </select>
                        <select name="movement_type" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                            <option value="">-- Semua Tipe --</option>
                            @foreach (['receive' => 'Penerimaan', 'sale' => 'Penjualan POS', 'usage' => 'Pemakaian Treatment', 'adjustment' => 'Penyesuaian', 'refund' => 'Refund POS'] as $k => $v)
                                <option value="{{ $k }}" {{ $movementType === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-mobile-card align-middle">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>Waktu & No. Dokumen</th>
                                    <th>Tipe</th>
                                    <th>Produk</th>
                                    <th>Masuk</th>
                                    <th>Keluar</th>
                                    <th>Saldo Akhir</th>
                                    <th>Keterangan / User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ledgers as $row)
                                    @php
                                        $mType = $row->header?->movement_type;
                                        $mBadge = match($mType) {
                                            'receive' => 'success',
                                            'sale' => 'primary',
                                            'usage' => 'warning',
                                            'adjustment' => 'info',
                                            'refund' => 'secondary',
                                            default => 'dark',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="small" data-label="Dokumen">
                                            <div>
                                                <div class="fw-bold">{{ $row->header?->document_number }}</div>
                                                <div class="text-muted">{{ $row->header?->movement_date?->format('d/m/Y H:i') }}</div>
                                            </div>
                                        </td>
                                        <td class="text-center" data-label="Tipe">
                                            <span class="badge bg-{{ $mBadge }} text-uppercase">{{ $mType }}</span>
                                        </td>
                                        <td data-label="Produk">
                                            <div>
                                                <div class="fw-bold">{{ $row->product?->name }}</div>
                                                <small class="text-muted">{{ $row->product?->sku }}</small>
                                            </div>
                                        </td>
                                        <td class="text-center text-success fw-bold" data-label="Masuk">
                                            {{ (float) $row->qty_in > 0 ? '+' . number_format((float) $row->qty_in, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-center text-danger fw-bold" data-label="Keluar">
                                            {{ (float) $row->qty_out > 0 ? '-' . number_format((float) $row->qty_out, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-center fw-bold" data-label="Saldo Akhir">
                                            {{ number_format((float) $row->balance_after, 0, ',', '.') }} {{ $row->product?->unit?->symbol }}
                                        </td>
                                        <td class="small" data-label="Keterangan">
                                            <div>
                                                <div>{{ $row->notes ?: $row->header?->reason }}</div>
                                                <small class="text-muted">Oleh: {{ $row->header?->creator?->name ?? 'System' }}</small>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">Belum ada mutasi stok tercatat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $ledgers->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: Form Input Mutasi Stok --}}
        <div data-tab-pane="mutation" class="{{ $errors->any() ? '' : 'd-none' }}">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plus-circle me-1"></i> Posting Dokumen Mutasi Stok</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('stocks.store') }}" method="POST">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label fw-bold">Jenis Mutasi <span class="text-danger">*</span></label>
                                    <select name="movement_action" class="form-control" required>
                                        <option value="receive">Penerimaan / Pembelian Stok (+)</option>
                                        <option value="usage">Pemakaian Produk Treatment (-)</option>
                                        <option value="adjustment_in">Penyesuaian Stok Masuk / Opname (+)</option>
                                        <option value="adjustment_out">Penyesuaian Stok Keluar / Rusak (-)</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold">Pilih Produk <span class="text-danger">*</span></label>
                                    <select name="product_id" class="form-control" required>
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($products as $prd)
                                            <option value="{{ $prd->id }}" {{ $productId == $prd->id ? 'selected' : '' }}>
                                                {{ $prd->name }} (Sisa: {{ number_format((float) $prd->current_stock, 0) }} {{ $prd->unit?->symbol }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <label class="form-label fw-bold">Kuantitas <span class="text-danger">*</span></label>
                                        <input type="number" step="1" min="1" name="quantity" class="form-control" value="1" required>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label fw-bold">Harga Beli/Unit (Rp)</label>
                                        <input type="number" step="100" min="0" name="unit_cost" class="form-control" placeholder="Opsional">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Supplier (Khusus Penerimaan)</label>
                                    <select name="supplier_id" class="form-control">
                                        <option value="">-- Tanpa Supplier --</option>
                                        @foreach ($suppliers as $sup)
                                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Alasan / Referensi Dokumen <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="2" placeholder="Contoh: Faktur pembelian PO-001 / Pemakaian hair spa pelanggan..." required></textarea>
                                </div>

                                <button type="submit" class="btn btn-salon w-100 py-2" data-requires-online="true">
                                    <i class="fas fa-save me-1"></i> Posting Mutasi Stok
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
