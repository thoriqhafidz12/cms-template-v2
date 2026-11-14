@extends('layouts.app')

@section('content')
    <div id="bookingTabGroup" data-tab-group>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-calendar-check text-primary me-1"></i> Booking & Antrian Salon</h4>
                <small class="text-muted">Kelola antrian walk-in, jadwal reservasi, check-in, dan status layanan stylist.</small>
            </div>
            <form method="GET" action="{{ route('bookings.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="date" name="date" class="form-control form-control-sm" style="width: 145px;" value="{{ $date }}">
                <select name="employee_id" class="form-select form-select-sm" style="width: 155px;">
                    <option value="">Semua Stylist</option>
                    @foreach ($stylists as $sty)
                        <option value="{{ $sty->id }}" {{ $stylistId == $sty->id ? 'selected' : '' }}>{{ $sty->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm" style="width: 135px;">
                    <option value="">Semua Status</option>
                    @foreach (['pending', 'confirmed', 'checked_in', 'in_service', 'completed', 'cancelled', 'no_show'] as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ strtoupper($st) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
            </form>
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

        {{-- Top Segmented Tabs (Mobile App Style) --}}
        <div class="mobile-segmented-tabs" role="tablist">
            <button type="button" class="seg-tab {{ $errors->any() ? '' : 'active' }}" data-tab-target="queue"
                onclick="switchMobileTab('bookingTabGroup', 'queue')">
                <i class="fas fa-users"></i> Antrian Hari Ini
                <span class="badge bg-primary ms-1">{{ $queues->count() }}</span>
            </button>
            <button type="button" class="seg-tab" data-tab-target="appointments"
                onclick="switchMobileTab('bookingTabGroup', 'appointments')">
                <i class="fas fa-calendar-alt"></i> Jadwal Booking
                <span class="badge bg-secondary ms-1">{{ $appointments->count() }}</span>
            </button>
            <button type="button" class="seg-tab {{ $errors->any() ? 'active' : '' }}" data-tab-target="create"
                onclick="switchMobileTab('bookingTabGroup', 'create')">
                <i class="fas fa-plus-circle"></i> + Walk-In / Booking Baru
            </button>
        </div>

        {{-- TAB 1: Antrian Aktif Hari Ini (Service Orders) --}}
        <div data-tab-pane="queue" class="{{ $errors->any() ? 'd-none' : '' }}">
            <div class="card shadow-sm mb-4 border-top border-4 border-primary">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users me-1"></i> Antrian Layanan & Walk-In ({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }})
                    </h6>
                    <button type="button" class="btn btn-xs btn-salon" onclick="switchMobileTab('bookingTabGroup', 'create')">
                        <i class="fas fa-plus me-1"></i> Tambah Antrian
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-mobile-card align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th width="8%">No.</th>
                                    <th>No. Order & Pelanggan</th>
                                    <th>Layanan & Stylist</th>
                                    <th>Status</th>
                                    <th width="28%">Aksi Layanan / Kasir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($queues as $q)
                                    @php
                                        $badgeColor = match($q->status) {
                                            'waiting' => 'warning',
                                            'called' => 'info',
                                            'in_service' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-center fw-bold fs-5 text-primary" data-label="Antrian">
                                            #{{ str_pad($q->queue_number, 2, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td data-label="Pelanggan">
                                            <div>
                                                <div class="fw-bold">{{ $q->customer?->name ?? 'Pelanggan Umum (Walk-In)' }}</div>
                                                <small class="text-muted">{{ $q->service_order_number }}</small>
                                                @if ($q->appointment)
                                                    <span class="badge bg-light text-dark border ms-1">Booking</span>
                                                @else
                                                    <span class="badge bg-light text-secondary border ms-1">Walk-In</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="Layanan">
                                            <div>
                                                @foreach ($q->details as $det)
                                                    <div class="small">
                                                        • <strong>{{ $det->service_name_snapshot }}</strong>
                                                        <span class="text-muted">({{ $det->employee?->name ?? 'Any Stylist' }})</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center" data-label="Status">
                                            <span class="badge bg-{{ $badgeColor }} text-uppercase">{{ str_replace('_', ' ', $q->status) }}</span>
                                        </td>
                                        <td class="text-center" data-label="Aksi">
                                            <div class="d-flex flex-wrap justify-content-center gap-1">
                                                @if ($q->status === 'waiting')
                                                    <form action="{{ route('bookings.queue.status', $q->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="status" value="called">
                                                        <button class="btn btn-xs btn-info text-white" title="Panggil Antrian">
                                                            <i class="fas fa-bullhorn"></i> Panggil
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (in_array($q->status, ['waiting', 'called']))
                                                    <form action="{{ route('bookings.queue.status', $q->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="status" value="in_service">
                                                        <button class="btn btn-xs btn-primary" title="Mulai Kerjakan">
                                                            <i class="fas fa-play"></i> Mulai
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($q->status === 'in_service')
                                                    <form action="{{ route('bookings.queue.status', $q->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="status" value="completed">
                                                        <button class="btn btn-xs btn-success" title="Selesai Layanan">
                                                            <i class="fas fa-check"></i> Selesai
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (!$q->transaction && $q->status !== 'cancelled')
                                                    <a href="{{ route('pos.index', ['service_order_id' => $q->id]) }}"
                                                        class="btn btn-xs btn-salon">
                                                        <i class="fas fa-cash-register"></i> Ke Kasir
                                                    </a>
                                                @elseif ($q->transaction)
                                                    <a href="{{ route('pos.receipt', $q->transaction->id) }}"
                                                        class="btn btn-xs btn-outline-success">
                                                        <i class="fas fa-receipt"></i> Lunas
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Belum ada antrian aktif pada tanggal ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: Daftar & Jadwal Booking --}}
        <div data-tab-pane="appointments" class="d-none">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt me-1"></i> Jadwal Booking ({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }})
                    </h6>
                    <button type="button" class="btn btn-xs btn-primary" onclick="switchMobileTab('bookingTabGroup', 'create')">
                        <i class="fas fa-calendar-plus me-1"></i> Buat Booking
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-mobile-card align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>Jam</th>
                                    <th>No. Booking & Pelanggan</th>
                                    <th>Layanan & Stylist</th>
                                    <th>Estimasi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($appointments as $apt)
                                    @php
                                        $aptBadge = match($apt->status) {
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'checked_in' => 'primary',
                                            'in_service' => 'primary',
                                            'completed' => 'success',
                                            'cancelled', 'no_show' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-center fw-bold" data-label="Jam">
                                            <div>
                                                {{ $apt->start_at?->format('H:i') }} - {{ $apt->end_at?->format('H:i') }}
                                                <div class="small text-muted">{{ $apt->total_duration_minutes }} mnt</div>
                                            </div>
                                        </td>
                                        <td data-label="Pelanggan">
                                            <div>
                                                <div class="fw-bold">{{ $apt->customer?->name }}</div>
                                                <small class="text-muted">{{ $apt->appointment_number }} • {{ $apt->customer?->phone }}</small>
                                                @if ($apt->notes)
                                                    <div class="small text-info"><i class="fas fa-sticky-note me-1"></i>{{ $apt->notes }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="Layanan">
                                            <div>
                                                @foreach ($apt->details as $d)
                                                    <div class="small">
                                                        • {{ $d->service_name_snapshot }}
                                                        <span class="text-muted">({{ $d->employee?->name ?? 'Bebas' }})</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-end" data-label="Estimasi">
                                            Rp {{ number_format((float) $apt->estimated_total, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center" data-label="Status">
                                            <span class="badge bg-{{ $aptBadge }} text-uppercase">{{ str_replace('_', ' ', $apt->status) }}</span>
                                        </td>
                                        <td class="text-center" data-label="Aksi">
                                            <div class="d-flex flex-wrap justify-content-center gap-1">
                                                @if (in_array($apt->status, ['pending', 'confirmed']) && !$apt->serviceOrder)
                                                    <form action="{{ route('bookings.checkin', $apt->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-xs btn-success" data-requires-online="true" title="Check-In ke Antrian">
                                                            <i class="fas fa-sign-in-alt"></i> Check-In
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (in_array($apt->status, ['pending', 'confirmed']))
                                                    <form action="{{ route('bookings.status', $apt->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="status" value="no_show">
                                                        <button type="submit" class="btn btn-xs btn-warning" title="Tandai No-Show">
                                                            No-Show
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('bookings.status', $apt->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <input type="hidden" name="cancel_reason" value="Dibatalkan oleh pelanggan/admin">
                                                        <button type="submit" class="btn btn-xs btn-danger" title="Batalkan Booking">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Belum ada jadwal booking pada tanggal ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 3: Buat Baru (Walk-In Langsung vs Booking Terjadwal) --}}
        <div data-tab-pane="create" class="{{ $errors->any() ? '' : 'd-none' }}">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-2">
                            <ul class="nav nav-pills nav-fill card-header-pills" id="bookingSubTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active btn-sm" id="tab-walkin" data-bs-toggle="tab"
                                        data-bs-target="#pane-walkin" type="button" role="tab">
                                        <i class="fas fa-walking me-1"></i> Antrian Walk-In Cepat
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link btn-sm" id="tab-booking" data-bs-toggle="tab"
                                        data-bs-target="#pane-booking" type="button" role="tab">
                                        <i class="fas fa-calendar-plus me-1"></i> Booking Terjadwal
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                {{-- Sub-Tab 1: Walk-In Langsung --}}
                                <div class="tab-pane fade show active" id="pane-walkin" role="tabpanel">
                                    <form action="{{ route('bookings.walkin') }}" method="POST">
                                        @csrf
                                        <div class="mb-2">
                                            <label class="form-label fw-bold">Pelanggan (Opsional untuk Walk-In)</label>
                                            <select name="customer_id" class="form-control">
                                                <option value="">-- Pelanggan Umum / Walk-In --</option>
                                                @foreach ($customers as $c)
                                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row bg-light rounded p-2 mb-2 mx-0 border">
                                            <div class="col-12"><small class="text-muted fw-bold">Atau Daftar Pelanggan Baru:</small></div>
                                            <div class="col-6">
                                                <input type="text" name="new_customer_name" class="form-control form-control-sm" placeholder="Nama...">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" name="new_customer_phone" class="form-control form-control-sm" placeholder="No. HP...">
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-bold">Stylist</label>
                                            <select name="employee_id" class="form-control">
                                                <option value="">-- Pilih Stylist --</option>
                                                @foreach ($stylists as $sty)
                                                    <option value="{{ $sty->id }}">{{ $sty->name }} — {{ $sty->specialization }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-bold">Pilih Layanan <span class="text-danger">*</span></label>
                                            <div class="border rounded p-2" style="max-height: 230px; overflow-y: auto;">
                                                @foreach ($services as $svc)
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox" name="service_ids[]"
                                                            value="{{ $svc->id }}" id="wi_svc_{{ $svc->id }}">
                                                        <label class="form-check-label small d-flex justify-content-between" for="wi_svc_{{ $svc->id }}">
                                                            <span>{{ $svc->name }} ({{ $svc->duration_minutes }}m)</span>
                                                            <span class="fw-bold">Rp {{ number_format((float) $svc->price, 0, ',', '.') }}</span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Catatan</label>
                                            <input type="text" name="notes" class="form-control" placeholder="Catatan layanan...">
                                        </div>

                                        <button type="submit" class="btn btn-salon w-100 py-2" data-requires-online="true">
                                            <i class="fas fa-ticket-alt me-1"></i> Ambil Nomor Antrian Walk-In
                                        </button>
                                    </form>
                                </div>

                                {{-- Sub-Tab 2: Booking Baru --}}
                                <div class="tab-pane fade" id="pane-booking" role="tabpanel">
                                    <form action="{{ route('bookings.store') }}" method="POST">
                                        @csrf
                                        <div class="mb-2">
                                            <label class="form-label fw-bold">Pelanggan Terdaftar</label>
                                            <select name="customer_id" class="form-control">
                                                <option value="">-- Pilih Pelanggan (atau isi baru di bawah) --</option>
                                                @foreach ($customers as $c)
                                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row bg-light rounded p-2 mb-2 mx-0 border">
                                            <div class="col-12"><small class="text-muted fw-bold">Atau Pelanggan Baru Cepat:</small></div>
                                            <div class="col-6">
                                                <input type="text" name="new_customer_name" class="form-control form-control-sm" placeholder="Nama baru...">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" name="new_customer_phone" class="form-control form-control-sm" placeholder="No. HP...">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-7 mb-2">
                                                <label class="form-label fw-bold">Tanggal Booking <span class="text-danger">*</span></label>
                                                <input type="date" name="appointment_date" class="form-control" value="{{ $date }}" required>
                                            </div>
                                            <div class="col-5 mb-2">
                                                <label class="form-label fw-bold">Jam Mulai <span class="text-danger">*</span></label>
                                                <input type="time" name="start_time" class="form-control" value="10:00" required>
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-bold">Stylist Pilihan</label>
                                            <select name="employee_id" class="form-control">
                                                <option value="">-- Stylist Bebas / Tersedia --</option>
                                                @foreach ($stylists as $sty)
                                                    <option value="{{ $sty->id }}">{{ $sty->name }} — {{ $sty->specialization }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-bold">Pilih Layanan <span class="text-danger">*</span></label>
                                            <div class="border rounded p-2" style="max-height: 230px; overflow-y: auto;">
                                                @foreach ($services as $svc)
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox" name="service_ids[]"
                                                            value="{{ $svc->id }}" id="bk_svc_{{ $svc->id }}">
                                                        <label class="form-check-label small d-flex justify-content-between" for="bk_svc_{{ $svc->id }}">
                                                            <span>{{ $svc->name }} <small class="text-muted">({{ $svc->duration_minutes }}m)</small></span>
                                                            <span class="fw-bold">Rp {{ number_format((float) $svc->price, 0, ',', '.') }}</span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Catatan Booking</label>
                                            <textarea name="notes" class="form-control" rows="2" placeholder="Request khusus pelanggan..."></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100 py-2" data-requires-online="true">
                                            <i class="fas fa-calendar-check me-1"></i> Simpan Jadwal Booking
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
