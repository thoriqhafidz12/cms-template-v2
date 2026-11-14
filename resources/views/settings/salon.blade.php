@extends('layouts.app')

@section('content')
    <div id="salonSettingTabGroup" data-tab-group>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-sliders-h text-primary me-1"></i> Pengaturan Salon, Pajak & Printer</h4>
                <small class="text-muted">Konfigurasi profil cabang, jam operasional, penomoran dokumen, dan struk thermal.</small>
            </div>
        </div>

        {{-- Top Segmented Tabs --}}
        <div class="mobile-segmented-tabs" role="tablist">
            <button type="button" class="seg-tab active" data-tab-target="profile"
                onclick="switchMobileTab('salonSettingTabGroup', 'profile')">
                <i class="fas fa-spa"></i> Profil & Jam Buka
            </button>
            <button type="button" class="seg-tab" data-tab-target="printer"
                onclick="switchMobileTab('salonSettingTabGroup', 'printer')">
                <i class="fas fa-print"></i> Pajak, Prefix & Printer
            </button>
        </div>

        <form action="{{ route('salon-settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            {{-- TAB 1: Profil & Jam Operasional --}}
            <div data-tab-pane="profile">
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-spa me-1"></i> Profil & Jam Operasional Salon</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Nama Salon <span class="text-danger">*</span></label>
                                    <input type="text" name="salon_name" class="form-control" value="{{ old('salon_name', $setting->salon_name) }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tagline / Subjudul</label>
                                    <input type="text" name="tagline" class="form-control" value="{{ old('tagline', $setting->tagline) }}">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Telepon / WhatsApp</label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $setting->phone) }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $setting->email) }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Alamat Lengkap (Tampil di Struk)</label>
                                    <textarea name="address" class="form-control" rows="2">{{ old('address', $setting->address) }}</textarea>
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Jam Buka Operasional <span class="text-danger">*</span></label>
                                        <input type="time" name="open_time" class="form-control" value="{{ old('open_time', $setting->open_time) }}" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Jam Tutup Operasional <span class="text-danger">*</span></label>
                                        <input type="time" name="close_time" class="form-control" value="{{ old('close_time', $setting->close_time) }}" required>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between gap-2">
                                    <button type="button" class="btn btn-outline-primary" onclick="switchMobileTab('salonSettingTabGroup', 'printer')">
                                        Lanjut ke Pajak & Printer <i class="fas fa-arrow-right ms-1"></i>
                                    </button>
                                    <button type="submit" class="btn btn-salon">
                                        <i class="fas fa-save me-1"></i> Simpan Pengaturan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: Pajak, Format Dokumen & Printer --}}
            <div data-tab-pane="printer" class="d-none">
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-print me-1"></i> Pajak, Format Dokumen & Printer Struk</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Pajak / PPN Default (%)</label>
                                        <input type="number" step="0.01" name="tax_percentage" class="form-control" value="{{ old('tax_percentage', $setting->tax_percentage) }}" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Diskon Default (%)</label>
                                        <input type="number" step="0.01" name="default_discount_percentage" class="form-control" value="{{ old('default_discount_percentage', $setting->default_discount_percentage) }}" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4 mb-3">
                                        <label class="form-label">Prefix Booking</label>
                                        <input type="text" name="booking_prefix" class="form-control" value="{{ old('booking_prefix', $setting->booking_prefix) }}" required>
                                    </div>
                                    <div class="col-4 mb-3">
                                        <label class="form-label">Prefix Antrian</label>
                                        <input type="text" name="service_order_prefix" class="form-control" value="{{ old('service_order_prefix', $setting->service_order_prefix) }}" required>
                                    </div>
                                    <div class="col-4 mb-3">
                                        <label class="form-label">Prefix Transaksi</label>
                                        <input type="text" name="transaction_prefix" class="form-control" value="{{ old('transaction_prefix', $setting->transaction_prefix) }}" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ukuran Kertas Thermal <span class="text-danger">*</span></label>
                                        <select name="receipt_width" class="form-control" required>
                                            <option value="80mm" {{ $setting->receipt_width === '80mm' ? 'selected' : '' }}>Thermal 80mm (Standar Kasir)</option>
                                            <option value="58mm" {{ $setting->receipt_width === '58mm' ? 'selected' : '' }}>Thermal 58mm (Mini/Portable)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Batas Stok Minimum Default</label>
                                        <input type="number" name="low_stock_threshold_default" class="form-control" value="{{ old('low_stock_threshold_default', $setting->low_stock_threshold_default) }}" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Catatan Kaki (Footer) Struk</label>
                                    <textarea name="receipt_footer" class="form-control" rows="2">{{ old('receipt_footer', $setting->receipt_footer) }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-salon w-100 py-2">
                                    <i class="fas fa-save me-1"></i> Simpan Pengaturan Salon
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
