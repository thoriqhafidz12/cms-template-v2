@extends('layouts.app')

@push('styles')
<style>
    .pos-catalog-card {
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        user-select: none;
    }
    .pos-catalog-card:hover, .pos-catalog-card:active {
        border-color: #841CC9;
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(132, 28, 201, 0.16);
    }
    .quick-cash-chip {
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 600;
        padding: 4px 12px;
    }
</style>
@endpush

@section('content')
    <div id="posTabGroup" data-tab-group>
        {{-- Header Status Shift --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-cash-register text-primary me-1"></i> Kasir / POS Salon</h4>
                <small class="text-muted">Alur 3-Tab: Pilih Katalog → Atur Keranjang & Stylist → Selesaikan Pembayaran</small>
            </div>
            <div>
                @if ($activeShift)
                    <span class="badge bg-success px-3 py-2">
                        <i class="fas fa-unlock me-1"></i> Shift: {{ $activeShift->shift_number }} (Kas: Rp {{ number_format((float) $activeShift->expected_cash, 0, ',', '.') }})
                    </span>
                @else
                    <span class="badge bg-danger px-3 py-2">
                        <i class="fas fa-lock me-1"></i> Shift Kasir Belum Dibuka
                    </span>
                @endif
            </div>
        </div>

        @if (!$activeShift)
            <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center shadow-sm py-2">
                <div class="small">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i> Shift Kasir Belum Aktif!</strong>
                    Buka shift kasir terlebih dahulu untuk mengaktifkan pembayaran.
                </div>
                <form action="{{ route('cash-shifts.open') }}" method="POST" class="d-flex gap-2 align-items-center mt-2 mt-md-0">
                    @csrf
                    <input type="hidden" name="redirect_pos" value="1">
                    <input type="number" name="opening_cash" class="form-control form-control-sm" style="width: 140px;" value="200000" placeholder="Modal Awal Rp" required>
                    <button type="submit" class="btn btn-sm btn-salon" data-requires-online="true">
                        <i class="fas fa-door-open me-1"></i> Buka Shift Cepat
                    </button>
                </form>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Top Segmented Workflow Tabs (1. Katalog | 2. Keranjang | 3. Bayar) --}}
        <div class="mobile-segmented-tabs" role="tablist">
            <button type="button" class="seg-tab {{ $selectedServiceOrder ? '' : 'active' }}" data-tab-target="catalog"
                onclick="switchMobileTab('posTabGroup', 'catalog')">
                <i class="fas fa-th-large"></i> 1. Katalog
            </button>
            <button type="button" class="seg-tab {{ $selectedServiceOrder ? 'active' : '' }}" data-tab-target="cart"
                onclick="switchMobileTab('posTabGroup', 'cart')">
                <i class="fas fa-shopping-basket"></i> 2. Keranjang & Stylist
                <span class="badge bg-danger ms-1" id="tabBadgeCartCount">0</span>
            </button>
            <button type="button" class="seg-tab" data-tab-target="payment"
                onclick="switchMobileTab('posTabGroup', 'payment')">
                <i class="fas fa-credit-card"></i> 3. Bayar
            </button>
        </div>

        {{-- SATU FORM CHECKOUT MEMBUNGKUS TAB KATALOG, KERANJANG & PEMBAYARAN --}}
        <form action="{{ route('pos.checkout') }}" method="POST" id="posCheckoutForm" onsubmit="return handleCheckoutSubmit()">
            @csrf
            <input type="hidden" name="cash_shift_id" value="{{ $activeShift?->id }}">
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

            {{-- TAB 1: KATALOG LAYANAN & PRODUK + QUICK LOAD ANTRIAN --}}
            <div data-tab-pane="catalog" class="{{ $selectedServiceOrder ? 'd-none' : '' }}">
                {{-- Quick Load Antrian Aktif / Selesai --}}
                <div class="card shadow-sm mb-3 border-start border-4 border-primary">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <label class="small fw-bold text-primary mb-0">
                                <i class="fas fa-bolt me-1"></i> Muat Cepat dari Antrian Layanan:
                            </label>
                            <select name="service_order_id" class="form-select form-select-sm" style="max-width: 340px;"
                                onchange="if(this.value) window.location.href='{{ route('pos.index') }}?service_order_id='+this.value; else window.location.href='{{ route('pos.index') }}';">
                                <option value="">-- Transaksi Walk-In / Manual Baru --</option>
                                @foreach ($pendingOrders as $po)
                                    <option value="{{ $po->id }}" {{ $selectedServiceOrder?->id == $po->id ? 'selected' : '' }}>
                                        #{{ str_pad($po->queue_number, 2, '0', STR_PAD_LEFT) }} - {{ $po->service_order_number }} ({{ $po->customer?->name ?? 'Walk-In' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header py-2">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <ul class="nav nav-pills card-header-pills" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active btn-sm" data-bs-toggle="tab" data-bs-target="#catalog-services" type="button">
                                        <i class="fas fa-spa me-1"></i> Layanan ({{ $services->count() }})
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link btn-sm" data-bs-toggle="tab" data-bs-target="#catalog-products" type="button">
                                        <i class="fas fa-pump-soap me-1"></i> Produk ({{ $products->count() }})
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <input type="text" id="catalogSearch" class="form-control form-control-sm"
                            placeholder="Ketik nama layanan / kode / produk..." oninput="filterCatalog(this.value)">
                    </div>

                    <div class="card-body">
                        <div class="tab-content">
                            {{-- Katalog Layanan --}}
                            <div class="tab-pane fade show active" id="catalog-services">
                                <div class="row g-2">
                                    @foreach ($services as $svc)
                                        <div class="col-6 col-md-4 col-lg-3 catalog-item" data-search="{{ strtolower($svc->name . ' ' . $svc->service_code . ' ' . ($svc->category?->name ?? '')) }}">
                                            <div class="p-3 pos-catalog-card h-100 d-flex flex-column justify-content-between"
                                                onclick="addItemToCart('service', {{ $svc->id }}, @js($svc->name), {{ (float) $svc->price }})">
                                                <div>
                                                    <span class="badge badge-salon mb-1">{{ $svc->category?->name ?? 'Layanan' }}</span>
                                                    <div class="fw-bold small text-dark">{{ $svc->name }}</div>
                                                    <small class="text-muted"><i class="far fa-clock me-1"></i>{{ $svc->duration_minutes }} mnt</small>
                                                </div>
                                                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-primary small">Rp {{ number_format((float) $svc->price, 0, ',', '.') }}</span>
                                                    <span class="btn btn-xs btn-salon rounded-circle"><i class="fas fa-plus"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Katalog Produk --}}
                            <div class="tab-pane fade" id="catalog-products">
                                <div class="row g-2">
                                    @foreach ($products as $prd)
                                        <div class="col-6 col-md-4 col-lg-3 catalog-item" data-search="{{ strtolower($prd->name . ' ' . $prd->sku . ' ' . ($prd->category?->name ?? '')) }}">
                                            <div class="p-3 pos-catalog-card h-100 d-flex flex-column justify-content-between"
                                                onclick="addItemToCart('product', {{ $prd->id }}, @js($prd->name), {{ (float) $prd->sell_price }}, {{ (float) $prd->current_stock }})">
                                                <div>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="badge bg-light text-dark border mb-1">{{ $prd->sku }}</span>
                                                        <span class="badge {{ (float) $prd->current_stock > 0 ? 'bg-info' : 'bg-danger' }} mb-1">
                                                            Stok: {{ number_format((float) $prd->current_stock, 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                    <div class="fw-bold small text-dark">{{ $prd->name }}</div>
                                                </div>
                                                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-primary small">Rp {{ number_format((float) $prd->sell_price, 0, ',', '.') }}</span>
                                                    <span class="btn btn-xs btn-salon rounded-circle"><i class="fas fa-plus"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3 pt-2 border-top">
                            <button type="button" class="btn btn-salon px-4" onclick="switchMobileTab('posTabGroup', 'cart')">
                                Lanjut ke Keranjang & Stylist <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: KERANJANG & PEMILIHAN STYLIST --}}
            <div data-tab-pane="cart" class="{{ $selectedServiceOrder ? '' : 'd-none' }}">
                <div class="card shadow-sm mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-shopping-basket me-1"></i> Daftar Item & Stylist</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-xs btn-outline-primary" onclick="switchMobileTab('posTabGroup', 'catalog')">
                                <i class="fas fa-plus me-1"></i> Tambah Item
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-danger" onclick="clearCart()">
                                <i class="fas fa-trash-alt me-1"></i> Kosongkan
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div id="cartItemsContainer"></div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="switchMobileTab('posTabGroup', 'catalog')">
                                <i class="fas fa-arrow-left me-1"></i> Kembali ke Katalog
                            </button>
                            <button type="button" class="btn btn-salon px-4" onclick="switchMobileTab('posTabGroup', 'payment')">
                                Lanjut ke Pembayaran <i class="fas fa-credit-card ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 3: RINGKASAN TAGIHAN & MULTI-PAYMENT --}}
            <div data-tab-pane="payment" class="d-none">
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        <div class="card shadow-sm border-top border-4 border-primary">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-file-invoice-dollar me-1"></i> Ringkasan & Pembayaran</h6>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="switchMobileTab('posTabGroup', 'cart')">
                                    <i class="fas fa-arrow-left me-1"></i> Ubah Keranjang
                                </button>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold mb-1">Pelanggan</label>
                                    <select name="customer_id" class="form-select">
                                        <option value="">-- Pelanggan Umum --</option>
                                        @foreach ($customers as $c)
                                            <option value="{{ $c->id }}" {{ ($selectedServiceOrder?->customer_id == $c->id) ? 'selected' : '' }}>
                                                {{ $c->name }} ({{ $c->phone }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="border-top pt-2 mb-2 small">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Subtotal Item:</span>
                                        <span class="fw-bold" id="summarySubtotal">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted">Diskon Transaksi (Rp):</span>
                                        <input type="number" step="100" min="0" name="discount_amount" id="inputHeaderDiscount"
                                            class="form-control form-control-sm text-end" style="width: 130px;" value="0"
                                            oninput="recalculateTotals()">
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted">PPN ({{ (float) $setting->tax_percentage }}%):</span>
                                        <span id="summaryTax">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded border mt-2">
                                        <span class="fw-bold">TOTAL TAGIHAN:</span>
                                        <span class="fs-5 fw-bold text-primary" id="summaryGrandTotal">Rp 0</span>
                                    </div>
                                </div>

                                <hr class="my-2">

                                {{-- Quick Cash Nominal Chips --}}
                                <div class="mb-2">
                                    <small class="text-muted d-block mb-1">Nominal Cepat:</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button type="button" class="btn btn-outline-primary quick-cash-chip" onclick="setQuickCash('exact')">Uang Pas</button>
                                        <button type="button" class="btn btn-outline-secondary quick-cash-chip" onclick="setQuickCash(50000)">Rp 50.000</button>
                                        <button type="button" class="btn btn-outline-secondary quick-cash-chip" onclick="setQuickCash(100000)">Rp 100.000</button>
                                        <button type="button" class="btn btn-outline-secondary quick-cash-chip" onclick="setQuickCash(200000)">Rp 200.000</button>
                                        <button type="button" class="btn btn-outline-secondary quick-cash-chip" onclick="setQuickCash(500000)">Rp 500.000</button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="small fw-bold mb-0">Metode Pembayaran</label>
                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="addPaymentRow()">
                                        <i class="fas fa-plus"></i> Split / Multi-Pay
                                    </button>
                                </div>

                                <div id="paymentRowsContainer"></div>

                                <div class="p-2 rounded bg-light border mt-2 mb-3 small">
                                    <div class="d-flex justify-content-between">
                                        <span>Total Dibayar:</span>
                                        <span class="fw-bold text-success" id="summaryPaid">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Kembalian:</span>
                                        <span class="fw-bold text-primary" id="summaryChange">Rp 0</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Catatan struk (opsional)...">
                                </div>

                                <button type="submit" id="btnCheckoutSubmit" class="btn btn-salon w-100 py-3 fw-bold fs-6 shadow"
                                    data-requires-online="true"
                                    {{ !$activeShift ? 'disabled' : '' }}>
                                    <i class="fas fa-check-circle me-1"></i> Proses Bayar & Cetak Struk
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        {{-- STICKY FLOATING POS CHECKOUT BAR (Di atas Bottom Navigation Bar) --}}
        <div id="posFloatingBar" class="pos-floating-bar no-print">
            <div>
                <div class="small opacity-75"><span id="floatingCartCount">0</span> Item di Keranjang</div>
                <div class="fw-bold fs-6" id="floatingGrandTotal">Rp 0</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" id="floatingNextBtn" class="btn btn-sm btn-light fw-bold text-primary px-3 rounded-pill"
                    onclick="handleFloatingAction()">
                    Keranjang & Stylist <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const STYLISTS = @js($stylists->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values());
    const PAYMENT_METHODS = @js($paymentMethods->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'type' => $p->type])->values());
    const TAX_PERCENT = {{ (float) ($setting->tax_percentage ?? 0) }};

    let cart = @js($initialCart);
    let currentPosTab = @js($selectedServiceOrder ? 'cart' : 'catalog');
    let payments = [
        {
            payment_method_id: PAYMENT_METHODS.length ? PAYMENT_METHODS[0].id : '',
            amount: 0,
            reference_number: ''
        }
    ];

    function formatRupiah(num) {
        return 'Rp ' + Math.round(num || 0).toLocaleString('id-ID');
    }

    function filterCatalog(keyword) {
        const q = (keyword || '').toLowerCase().trim();
        document.querySelectorAll('.catalog-item').forEach((el) => {
            const hay = el.dataset.search || '';
            el.style.display = (!q || hay.includes(q)) ? '' : 'none';
        });
    }

    function addItemToCart(type, itemId, name, price, stock = null) {
        if (type === 'product' && stock !== null && stock <= 0) {
            swalError('Stok Habis', 'Produk ini sedang kehabisan stok.');
            return;
        }

        if (type === 'product') {
            const existing = cart.find(c => c.item_type === 'product' && c.item_id === itemId);
            if (existing) {
                if (stock !== null && existing.quantity + 1 > stock) {
                    swalError('Stok Tidak Cukup', 'Jumlah melebihi stok tersedia.');
                    return;
                }
                existing.quantity += 1;
                renderCart();
                return;
            }
        }

        cart.push({
            uid: 'item_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
            item_type: type,
            item_id: itemId,
            name: name,
            price: Number(price),
            quantity: 1,
            discount_amount: 0,
            employee_id: STYLISTS.length ? STYLISTS[0].id : ''
        });

        renderCart();
    }

    function removeCartItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        cart = [];
        renderCart();
    }

    function updateCartField(index, field, val) {
        if (!cart[index]) return;
        if (field === 'quantity') {
            cart[index].quantity = Math.max(1, Number(val) || 1);
        } else if (field === 'discount_amount') {
            cart[index].discount_amount = Math.max(0, Number(val) || 0);
        } else {
            cart[index][field] = val;
        }
        recalculateTotals(false);
    }

    function setQuickCash(val) {
        const { grand } = getGrandTotal();
        if (!payments.length) {
            addPaymentRow();
        }
        payments[0].amount = val === 'exact' ? grand : Number(val);
        renderPaymentRows();
    }

    function handleFloatingAction() {
        if (currentPosTab === 'catalog') {
            switchMobileTab('posTabGroup', 'cart');
        } else if (currentPosTab === 'cart') {
            switchMobileTab('posTabGroup', 'payment');
        }
    }

    document.addEventListener('mobileTabChanged', function (e) {
        if (e.detail && e.detail.groupId === 'posTabGroup') {
            currentPosTab = e.detail.tabId;
            updateFloatingBarState();
        }
    });

    function updateFloatingBarState() {
        const bar = document.getElementById('posFloatingBar');
        const nextBtn = document.getElementById('floatingNextBtn');
        if (!bar || !nextBtn) return;

        if (currentPosTab === 'payment') {
            bar.classList.add('d-none');
        } else {
            bar.classList.remove('d-none');
            if (currentPosTab === 'catalog') {
                nextBtn.innerHTML = 'Keranjang & Stylist <i class="fas fa-arrow-right ms-1"></i>';
            } else {
                nextBtn.innerHTML = 'Lanjut Bayar <i class="fas fa-credit-card ms-1"></i>';
            }
        }
    }

    function renderCart() {
        const container = document.getElementById('cartItemsContainer');
        const totalQty = cart.reduce((acc, i) => acc + (Number(i.quantity) || 1), 0);

        const badgeEl = document.getElementById('tabBadgeCartCount');
        if (badgeEl) badgeEl.textContent = totalQty;

        const floatCountEl = document.getElementById('floatingCartCount');
        if (floatCountEl) floatCountEl.textContent = totalQty;

        if (!cart.length) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-shopping-bag fa-2x mb-2 opacity-50"></i>
                    <p class="small mb-2">Keranjang masih kosong.</p>
                    <button type="button" class="btn btn-sm btn-salon rounded-pill px-3" onclick="switchMobileTab('posTabGroup', 'catalog')">
                        <i class="fas fa-plus me-1"></i> Pilih Layanan / Produk di Katalog
                    </button>
                </div>
            `;
            recalculateTotals(true);
            return;
        }

        let html = '';
        cart.forEach((item, idx) => {
            const stylistOptions = STYLISTS.map(st =>
                `<option value="${st.id}" ${String(item.employee_id) === String(st.id) ? 'selected' : ''}>${st.name}</option>`
            ).join('');

            html += `
                <div class="border rounded-3 p-3 mb-2 bg-white shadow-xs">
                    <input type="hidden" name="items[${idx}][item_type]" value="${item.item_type}">
                    <input type="hidden" name="items[${idx}][item_id]" value="${item.item_id}">

                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge ${item.item_type === 'service' ? 'badge-salon' : 'bg-info text-white'} me-1">
                                ${item.item_type === 'service' ? 'Layanan' : 'Produk'}
                            </span>
                            <strong class="small">${item.name}</strong>
                        </div>
                        <button type="button" class="btn btn-xs btn-link text-danger p-0" onclick="removeCartItem(${idx})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>

                    <div class="row g-2 align-items-center small">
                        <div class="col-12 col-sm-5">
                            <label class="text-muted" style="font-size:11px;">Stylist Pelaksana:</label>
                            <select name="items[${idx}][employee_id]" class="form-select form-select-sm"
                                onchange="updateCartField(${idx}, 'employee_id', this.value)">
                                <option value="">- Tanpa Stylist -</option>
                                ${stylistOptions}
                            </select>
                        </div>
                        <div class="col-5 col-sm-3">
                            <label class="text-muted" style="font-size:11px;">Qty:</label>
                            <input type="number" min="1" step="1" name="items[${idx}][quantity]"
                                class="form-control form-control-sm text-center" value="${item.quantity}"
                                oninput="updateCartField(${idx}, 'quantity', this.value)">
                        </div>
                        <div class="col-7 col-sm-4">
                            <label class="text-muted" style="font-size:11px;">Diskon Item (Rp):</label>
                            <input type="number" min="0" step="100" name="items[${idx}][discount_amount]"
                                class="form-control form-control-sm text-end" value="${item.discount_amount}"
                                oninput="updateCartField(${idx}, 'discount_amount', this.value)">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small">
                        <span class="text-muted">@ ${formatRupiah(item.price)}</span>
                        <span class="fw-bold text-primary" id="lineTotal_${idx}">
                            ${formatRupiah(Math.max(0, item.price * item.quantity - item.discount_amount))}
                        </span>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        recalculateTotals(true);
    }

    function renderPaymentRows() {
        const container = document.getElementById('paymentRowsContainer');
        let html = '';

        payments.forEach((pay, idx) => {
            const pmOptions = PAYMENT_METHODS.map(pm =>
                `<option value="${pm.id}" ${String(pay.payment_method_id) === String(pm.id) ? 'selected' : ''}>${pm.name}</option>`
            ).join('');

            html += `
                <div class="border rounded p-2 mb-2 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <select name="payments[${idx}][payment_method_id]" class="form-select form-select-sm me-1"
                            onchange="payments[${idx}].payment_method_id = this.value">
                            ${pmOptions}
                        </select>
                        ${payments.length > 1 ? `
                            <button type="button" class="btn btn-xs btn-outline-danger" onclick="removePaymentRow(${idx})">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                    <div class="row g-1">
                        <div class="col-7">
                            <input type="number" step="100" min="0" name="payments[${idx}][amount]"
                                class="form-control form-control-sm fw-bold" placeholder="Nominal Rp"
                                value="${pay.amount}"
                                oninput="payments[${idx}].amount = Number(this.value) || 0; updatePaymentSummary();">
                        </div>
                        <div class="col-5">
                            <input type="text" name="payments[${idx}][reference_number]"
                                class="form-control form-control-sm" placeholder="No. Ref / QRIS"
                                value="${pay.reference_number || ''}"
                                oninput="payments[${idx}].reference_number = this.value">
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        updatePaymentSummary();
    }

    function addPaymentRow() {
        payments.push({
            payment_method_id: PAYMENT_METHODS.length > 1 ? PAYMENT_METHODS[1].id : (PAYMENT_METHODS[0]?.id || ''),
            amount: 0,
            reference_number: ''
        });
        renderPaymentRows();
    }

    function removePaymentRow(idx) {
        payments.splice(idx, 1);
        renderPaymentRows();
    }

    function getGrandTotal() {
        let subtotal = 0;
        cart.forEach((item, idx) => {
            const lineSubtotal = Math.max(0, (item.price * item.quantity) - item.discount_amount);
            subtotal += lineSubtotal;
            const lineEl = document.getElementById('lineTotal_' + idx);
            if (lineEl) lineEl.textContent = formatRupiah(lineSubtotal);
        });

        const headerDisc = Math.min(subtotal, Math.max(0, Number(document.getElementById('inputHeaderDiscount')?.value) || 0));
        const afterDisc = Math.max(0, subtotal - headerDisc);
        const tax = Math.round(afterDisc * (TAX_PERCENT / 100));
        const grand = afterDisc + tax;

        return { subtotal, headerDisc, tax, grand };
    }

    function recalculateTotals(syncDefaultPayment = false) {
        const { subtotal, tax, grand } = getGrandTotal();

        document.getElementById('summarySubtotal').textContent = formatRupiah(subtotal);
        document.getElementById('summaryTax').textContent = formatRupiah(tax);
        document.getElementById('summaryGrandTotal').textContent = formatRupiah(grand);

        const floatGrand = document.getElementById('floatingGrandTotal');
        if (floatGrand) floatGrand.textContent = formatRupiah(grand);

        if (syncDefaultPayment && payments.length === 1) {
            payments[0].amount = grand;
            renderPaymentRows();
        } else {
            updatePaymentSummary();
        }
    }

    function updatePaymentSummary() {
        const { grand } = getGrandTotal();
        const totalPaid = payments.reduce((acc, p) => acc + (Number(p.amount) || 0), 0);
        const change = Math.max(0, totalPaid - grand);

        document.getElementById('summaryPaid').textContent = formatRupiah(totalPaid);
        document.getElementById('summaryChange').textContent = formatRupiah(change);
    }

    function handleCheckoutSubmit() {
        if (!cart.length) {
            swalError('Keranjang Kosong', 'Tambahkan minimal 1 layanan atau produk terlebih dahulu.');
            switchMobileTab('posTabGroup', 'catalog');
            return false;
        }
        const { grand } = getGrandTotal();
        const totalPaid = payments.reduce((acc, p) => acc + (Number(p.amount) || 0), 0);
        if (totalPaid < grand) {
            swalError('Pembayaran Kurang', 'Total pembayaran belum mencukupi total tagihan.');
            switchMobileTab('posTabGroup', 'payment');
            return false;
        }

        const btn = document.getElementById('btnCheckoutSubmit');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses Transaksi...';
        }
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        renderCart();
        renderPaymentRows();
        updateFloatingBarState();
    });
</script>
@endpush
