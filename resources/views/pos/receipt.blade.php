@extends('layouts.app')

@php
    $is58 = ($paperWidth ?? '80mm') === '58mm';
    $contentWidth = $is58 ? '48mm' : '72mm';
    $pageSize = $is58 ? '58mm auto' : '80mm auto';
@endphp

@push('styles')
<style>
    .receipt-wrapper {
        background: #fff;
        width: {{ $contentWidth }};
        margin: 0 auto;
        padding: 12px 10px;
        font-family: 'Courier New', Courier, monospace;
        font-size: {{ $is58 ? '10px' : '11px' }};
        color: #000;
        border: 1px dashed #ccc;
        box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    }
    .receipt-divider {
        border-top: 1px dashed #000;
        margin: 6px 0;
    }
    @media print {
        @page {
            size: {{ $pageSize }};
            margin: 0;
        }
        html, body {
            width: {{ $contentWidth }};
            margin: 0 auto !important;
            padding: 0 !important;
            background: #fff !important;
        }
        .sidebar, .main-header, .footer, .no-print, .mobile-bottom-nav, .loading-page, #Moduleloader-page {
            display: none !important;
        }
        .main-panel, .container, .container-fluid {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .receipt-wrapper {
            width: 100% !important;
            border: none !important;
            box-shadow: none !important;
            padding: 2mm !important;
        }
    }
</style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 no-print gap-2">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-receipt text-success me-1"></i> Struk Transaksi ({{ $transaction->transaction_number }})</h4>
            <small class="text-muted">Ukuran Kertas Aktif: <strong>Thermal {{ $paperWidth }}</strong></small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('pos.receipt', ['transaction' => $transaction->id, 'width' => '58mm']) }}"
                class="btn btn-sm {{ $is58 ? 'btn-primary' : 'btn-outline-primary' }}">
                58mm
            </a>
            <a href="{{ route('pos.receipt', ['transaction' => $transaction->id, 'width' => '80mm']) }}"
                class="btn btn-sm {{ !$is58 ? 'btn-primary' : 'btn-outline-primary' }}">
                80mm
            </a>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-salon">
                <i class="fas fa-print me-1"></i> Cetak Struk
            </button>
            <button type="button"
                onclick="shareMobileReceipt(@js($transaction->transaction_number), @js($transaction->customer?->name ?? 'Pelanggan'), @js('Rp ' . number_format((float) $transaction->grand_total, 0, ',', '.')))"
                class="btn btn-sm btn-info text-white">
                <i class="fas fa-share-alt me-1"></i> Bagikan Struk
            </button>
            <a href="{{ route('pos.index') }}" class="btn btn-sm btn-success">
                <i class="fas fa-plus me-1"></i> Transaksi Baru
            </a>
            <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-list me-1"></i> Riwayat
            </a>
        </div>
    </div>

    <div class="receipt-wrapper">
        <div class="text-center">
            <div class="fw-bold" style="font-size: 13px;">{{ strtoupper($setting->salon_name) }}</div>
            @if ($setting->tagline)
                <div>{{ $setting->tagline }}</div>
            @endif
            @if ($setting->address)
                <div>{{ $setting->address }}</div>
            @endif
            @if ($setting->phone)
                <div>Telp: {{ $setting->phone }}</div>
            @endif
        </div>

        <div class="receipt-divider"></div>

        <div>
            <div class="d-flex justify-content-between">
                <span>No. Struk:</span>
                <span class="fw-bold">{{ $transaction->transaction_number }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Tanggal:</span>
                <span>{{ $transaction->transaction_date?->format('d/m/Y H:i') }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Kasir:</span>
                <span>{{ $transaction->creator?->name ?? '-' }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Pelanggan:</span>
                <span>{{ $transaction->customer?->name ?? 'Umum' }}</span>
            </div>
            @if ($transaction->status === 'refunded')
                <div class="text-center fw-bold mt-1">[ TRANSAKSI REFUND / VOID ]</div>
            @endif
        </div>

        <div class="receipt-divider"></div>

        {{-- Daftar Item --}}
        @foreach ($transaction->details as $det)
            <div class="mb-1">
                <div class="fw-bold">{{ $det->item_name_snapshot }}</div>
                @if ($det->employee)
                    <div style="font-size: 10px;">Stylist: {{ $det->employee->name }}</div>
                @endif
                <div class="d-flex justify-content-between">
                    <span>
                        {{ number_format((float) $det->quantity, 0, ',', '.') }} x {{ number_format((float) $det->unit_price, 0, ',', '.') }}
                        @if ((float) $det->discount_amount > 0)
                            (-{{ number_format((float) $det->discount_amount, 0, ',', '.') }})
                        @endif
                    </span>
                    <span>{{ number_format((float) $det->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach

        <div class="receipt-divider"></div>

        <div class="d-flex justify-content-between">
            <span>Subtotal</span>
            <span>Rp {{ number_format((float) $transaction->subtotal, 0, ',', '.') }}</span>
        </div>
        @if ((float) $transaction->discount_amount > 0)
            <div class="d-flex justify-content-between">
                <span>Diskon</span>
                <span>-Rp {{ number_format((float) $transaction->discount_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        @if ((float) $transaction->tax_amount > 0)
            <div class="d-flex justify-content-between">
                <span>PPN</span>
                <span>Rp {{ number_format((float) $transaction->tax_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        <div class="d-flex justify-content-between fw-bold" style="font-size: 12px;">
            <span>TOTAL</span>
            <span>Rp {{ number_format((float) $transaction->grand_total, 0, ',', '.') }}</span>
        </div>

        <div class="receipt-divider"></div>

        @foreach ($transaction->payments as $pay)
            <div class="d-flex justify-content-between">
                <span>{{ $pay->paymentMethod?->name ?? 'Bayar' }} {{ $pay->reference_number ? '(' . $pay->reference_number . ')' : '' }}</span>
                <span>Rp {{ number_format((float) $pay->amount, 0, ',', '.') }}</span>
            </div>
        @endforeach
        <div class="d-flex justify-content-between">
            <span>Kembali</span>
            <span>Rp {{ number_format((float) $transaction->change_amount, 0, ',', '.') }}</span>
        </div>

        <div class="receipt-divider"></div>

        <div class="text-center mt-1">
            <div>{{ $setting->receipt_footer ?: 'Terima kasih atas kunjungan Anda!' }}</div>
            <div style="font-size: 9px;" class="mt-1">Dicetak: {{ now()->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    async function shareMobileReceipt(txNo, customerName, grandTotal) {
        const salonName = @js($setting->salon_name);
        const shareData = {
            title: 'Struk ' + txNo + ' — ' + salonName,
            text: 'Terima kasih ' + customerName + ', transaksi Anda (' + txNo + ') di ' + salonName + ' sebesar ' + grandTotal + ' telah lunas.',
            url: window.location.href
        };
        try {
            if (navigator.share) {
                await navigator.share(shareData);
            } else if (navigator.clipboard) {
                await navigator.clipboard.writeText(window.location.href);
                alert('Tautan struk telah disalin ke clipboard!');
            }
        } catch (e) {}
    }
</script>
@endpush
