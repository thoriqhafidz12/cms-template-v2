@extends('layouts.app')

@section('content')
    <div id="stylistScheduleTabGroup" data-tab-group>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-calendar-week text-primary me-1"></i> Jadwal Kerja & Cuti Stylist</h4>
                <small class="text-muted">Atur jam operasional per hari dan tanggal libur/cuti untuk validasi booking otomatis.</small>
            </div>
        </div>

        {{-- Top Segmented Tabs --}}
        <div class="mobile-segmented-tabs" role="tablist">
            <button type="button" class="seg-tab active" data-tab-target="weekly"
                onclick="switchMobileTab('stylistScheduleTabGroup', 'weekly')">
                <i class="fas fa-clock"></i> Jadwal Mingguan
            </button>
            <button type="button" class="seg-tab" data-tab-target="leaves"
                onclick="switchMobileTab('stylistScheduleTabGroup', 'leaves')">
                <i class="fas fa-calendar-times"></i> Cuti & Izin Stylist
                <span class="badge bg-secondary ms-1">{{ $leaves->count() }}</span>
            </button>
        </div>

        {{-- TAB 1: Jadwal Mingguan per Stylist --}}
        <div data-tab-pane="weekly">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="m-0 font-weight-bold text-primary">Jadwal Mingguan Stylist</h6>
                    <form method="GET" action="{{ route('stylist-schedules.index') }}" class="d-flex align-items-center gap-2">
                        <select name="employee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach ($stylists as $sty)
                                <option value="{{ $sty->id }}" {{ ($selectedStylist?->id == $sty->id) ? 'selected' : '' }}>
                                    {{ $sty->name }} ({{ $sty->employee_code }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    @if ($selectedStylist)
                        <form action="{{ route('stylist-schedules.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $selectedStylist->id }}">
                            <div class="table-responsive">
                                <table class="table table-bordered table-mobile-card align-middle">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th>Hari</th>
                                            <th>Jam Mulai</th>
                                            <th>Jam Selesai</th>
                                            <th>Libur Rutin?</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $schedByDay = $selectedStylist->schedules->keyBy('day_of_week');
                                        @endphp
                                        @foreach ($days as $day)
                                            @php
                                                $sc = $schedByDay->get($day);
                                            @endphp
                                            <tr>
                                                <td class="fw-bold" data-label="Hari">{{ $day }}</td>
                                                <td data-label="Jam Mulai">
                                                    <input type="time" name="schedules[{{ $day }}][start_time]"
                                                        class="form-control form-control-sm"
                                                        value="{{ $sc?->start_time ?? '09:00' }}">
                                                </td>
                                                <td data-label="Jam Selesai">
                                                    <input type="time" name="schedules[{{ $day }}][end_time]"
                                                        class="form-control form-control-sm"
                                                        value="{{ $sc?->end_time ?? '20:00' }}">
                                                </td>
                                                <td class="text-center" data-label="Libur Rutin">
                                                    <div>
                                                        <input type="checkbox" name="schedules[{{ $day }}][is_day_off]" value="1"
                                                            class="form-check-input" {{ $sc?->is_day_off ? 'checked' : '' }}>
                                                        <small class="text-muted ms-1">Off</small>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-salon w-100 py-2">
                                <i class="fas fa-save me-1"></i> Simpan Jadwal {{ $selectedStylist->name }}
                            </button>
                        </form>
                    @else
                        <p class="text-muted mb-0">Belum ada data stylist aktif.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- TAB 2: Leave / Cuti Stylist --}}
        <div data-tab-pane="leaves" class="d-none">
            <div class="row">
                <div class="col-md-5">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Input Cuti / Izin Tidak Masuk</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('stylist-schedules.leaves.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tab" value="leaves">
                                <div class="mb-2">
                                    <label class="form-label">Stylist <span class="text-danger">*</span></label>
                                    <select name="employee_id" class="form-control" required>
                                        @foreach ($stylists as $sty)
                                            <option value="{{ $sty->id }}" {{ ($selectedStylist?->id == $sty->id) ? 'selected' : '' }}>
                                                {{ $sty->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Alasan Cuti / Libur <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" class="form-control" placeholder="Cuti tahunan / acara keluarga" required>
                                </div>
                                <button type="submit" class="btn btn-salon w-100">
                                    <i class="fas fa-calendar-times me-1"></i> Catat Cuti Stylist
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Daftar Cuti / Libur Stylist</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-bordered table-mobile-card mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Stylist</th>
                                            <th>Tanggal</th>
                                            <th>Alasan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($leaves as $lv)
                                            <tr>
                                                <td data-label="Stylist" class="fw-bold">{{ $lv->employee?->name ?? '-' }}</td>
                                                <td data-label="Tanggal">
                                                    {{ \Carbon\Carbon::parse($lv->start_date)->format('d/m/Y') }}
                                                    @if ($lv->end_date !== $lv->start_date)
                                                        - {{ \Carbon\Carbon::parse($lv->end_date)->format('d/m/Y') }}
                                                    @endif
                                                </td>
                                                <td data-label="Alasan">{{ $lv->reason }}</td>
                                                <td class="text-center" data-label="Aksi">
                                                    <form action="{{ route('stylist-schedules.leaves.destroy', $lv->id) }}" method="POST" class="d-inline form-delete">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">Belum ada catatan cuti.</td>
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
