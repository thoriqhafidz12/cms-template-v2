<x-template-top />

@php
    $brandSetting = $salonProfile ?? \App\Models\SalonSetting::current();
@endphp

<style>
    .auth-shell {
        min-height: 100vh;
        background-color: #D4C7B6;
        background-image:
            radial-gradient(at 0% 0%, rgba(132, 28, 201, 0.08) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(255, 255, 255, 0.35) 0px, transparent 50%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 1.5rem 1rem;
    }

    .auth-container {
        width: 100%;
        max-width: 980px;
        margin: 0 auto;
    }

    .auth-card {
        background: #FFFFFF;
        border: 1px solid #BFB09C;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(35, 31, 32, 0.1);
        overflow: hidden;
    }

    /* Panel Kiri: Kombinasi Main Color (#D4C7B6), Second Color (#FFFFFF) & Aksen (#841CC9) */
    .auth-aside {
        background: linear-gradient(165deg, #D4C7B6 0%, #C6B6A2 100%);
        color: #231F20;
        padding: 2.5rem 2rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-bottom: 1px solid #BFB09C;
    }

    @media (min-width: 992px) {
        .auth-aside {
            border-bottom: 0;
            border-right: 1px solid #BFB09C;
        }
    }

    .auth-brand-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.75rem;
        background: #841CC9;
        border: 1px solid #841CC9;
        color: #FFFFFF;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .auth-brand-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #231F20;
        letter-spacing: -0.01em;
        margin-top: 1rem;
        margin-bottom: 0.25rem;
        line-height: 1.25;
    }

    .auth-brand-tagline {
        color: #4a423b;
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
    }

    .auth-feature-list {
        list-style: none;
        padding: 0;
        margin: 0 0 1.5rem 0;
        display: flex;
        flex-direction: column;
        gap: 0.875rem;
    }

    .auth-feature-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        font-size: 0.825rem;
        color: #3f3832;
        line-height: 1.45;
    }

    .auth-feature-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: #841CC9;
        color: #FFFFFF;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.8rem;
        margin-top: 1px;
    }

    .auth-meta-box {
        background: #FFFFFF;
        border: 1px solid #BFB09C;
        border-radius: 8px;
        padding: 0.875rem 1rem;
        font-size: 0.78rem;
        color: #3f3832;
    }

    /* Panel Kanan: Second Color (#FFFFFF) */
    .auth-main {
        padding: 2.5rem 2.25rem;
        background: #FFFFFF;
    }

    @media (max-width: 575.98px) {
        .auth-shell {
            padding: 0.75rem;
        }
        .auth-aside {
            padding: 1.5rem 1.25rem;
        }
        .auth-main {
            padding: 1.5rem 1.25rem;
        }
    }

    .auth-form-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #231F20;
        margin-bottom: 0.25rem;
    }

    .auth-form-subtitle {
        font-size: 0.85rem;
        color: #5c5349;
        margin-bottom: 1.5rem;
    }

    .auth-label {
        display: block;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #231F20;
        margin-bottom: 0.375rem;
    }

    .auth-input-group {
        position: relative;
        display: flex;
        align-items: stretch;
    }

    .auth-input-icon {
        position: absolute;
        left: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b6156;
        font-size: 0.875rem;
        pointer-events: none;
        z-index: 2;
    }

    .auth-input {
        width: 100%;
        height: 44px;
        padding: 0.5rem 0.875rem 0.5rem 2.5rem;
        font-size: 0.875rem;
        color: #231F20;
        background-color: #FFFFFF;
        border: 1px solid #C6B6A2;
        border-radius: 8px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .auth-input--with-Action {
        padding-right: 2.75rem;
    }

    .auth-input:focus {
        outline: none;
        border-color: #841CC9;
        box-shadow: 0 0 0 3px rgba(132, 28, 201, 0.16);
    }

    .auth-input.is-invalid {
        border-color: #dc2626;
    }

    .auth-pw-toggle {
        position: absolute;
        right: 0.375rem;
        top: 50%;
        transform: translateY(-50%);
        width: 34px;
        height: 34px;
        border: none;
        background: transparent;
        color: #6b6156;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .auth-pw-toggle:hover,
    .auth-pw-toggle:focus-visible {
        background-color: #F5EAFC;
        color: #841CC9;
        outline: none;
    }

    .auth-submit-btn {
        width: 100%;
        height: 44px;
        background-color: #841CC9;
        color: #FFFFFF;
        border: 1px solid #841CC9;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background-color 0.15s ease, transform 0.1s ease;
    }

    .auth-submit-btn:hover,
    .auth-submit-btn:focus-visible {
        background-color: #6B15A3;
        color: #FFFFFF;
        outline: 2px solid #841CC9;
        outline-offset: 2px;
    }

    .auth-submit-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .auth-role-section {
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid #e5e0d8;
    }

    .auth-role-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .auth-role-chip {
        background: #FFFFFF;
        border: 1px solid #C6B6A2;
        color: #231F20;
        border-radius: 6px;
        padding: 0.35rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .auth-role-chip:hover,
    .auth-role-chip:focus-visible {
        background: #F5EAFC;
        border-color: #841CC9;
        color: #841CC9;
        outline: none;
    }

    .auth-footer-note {
        text-align: center;
        font-size: 0.775rem;
        color: #3f3832;
        margin-top: 1rem;
    }
</style>

<body>
    <main class="auth-shell">
        <div class="auth-container">
            <div class="auth-card">
                <div class="row g-0">
                    {{-- Kolom Kiri: Identitas & Status Operasional Salon --}}
                    <div class="col-lg-5 auth-aside">
                        <div>
                            <div class="auth-brand-badge">
                                <i class="fas fa-spa" aria-hidden="true"></i>
                                <span>POS & Salon Management</span>
                            </div>

                            <h1 class="auth-brand-title">{{ $brandSetting->salon_name }}</h1>
                            <p class="auth-brand-tagline">
                                {{ $brandSetting->tagline ?: 'Sistem Kasir, Booking Antrian, Komisi Stylist & Inventori' }}
                            </p>

                            <ul class="auth-feature-list d-none d-sm-flex" aria-label="Fitur Utama Sistem">
                                <li class="auth-feature-item">
                                    <span class="auth-feature-icon" aria-hidden="true">
                                        <i class="fas fa-calendar-check"></i>
                                    </span>
                                    <div>
                                        <strong class="d-block text-dark">Booking & Antrian Walk-In</strong>
                                        <span>Pantau jadwal stylist dan durasi pengerjaan layanan secara real-time.</span>
                                    </div>
                                </li>
                                <li class="auth-feature-item">
                                    <span class="auth-feature-icon" aria-hidden="true">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </span>
                                    <div>
                                        <strong class="d-block text-dark">Kasir POS & Multi-Metode Bayar</strong>
                                        <span>Checkout gabungan layanan & produk ritel, rekonsiliasi shift, dan struk thermal 58/80mm.</span>
                                    </div>
                                </li>
                                <li class="auth-feature-item">
                                    <span class="auth-feature-icon" aria-hidden="true">
                                        <i class="fas fa-boxes"></i>
                                    </span>
                                    <div>
                                        <strong class="d-block text-dark">Komisi Stylist & Kartu Stok</strong>
                                        <span>Perhitungan komisi otomatis per tindakan dan pemotongan stok bahan/ritel.</span>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="auth-meta-box">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-semibold text-dark">
                                    <i class="fas fa-clock text-primary me-1" aria-hidden="true"></i> Jam Operasional
                                </span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    {{ $brandSetting->open_time ?: '09:00' }} – {{ $brandSetting->close_time ?: '20:00' }}
                                </span>
                            </div>
                            @if ($brandSetting->address)
                                <div class="text-truncate" title="{{ $brandSetting->address }}">
                                    <i class="fas fa-map-marker-alt me-1 opacity-75" aria-hidden="true"></i>
                                    {{ $brandSetting->address }}
                                </div>
                            @endif
                            @if ($brandSetting->phone)
                                <div class="mt-1">
                                    <i class="fas fa-phone-alt me-1 opacity-75" aria-hidden="true"></i>
                                    {{ $brandSetting->phone }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Kolom Kanan: Form Login --}}
                    <div class="col-lg-7 auth-main">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h2 class="auth-form-title mb-0">Masuk ke Panel Kerja</h2>
                            <span class="badge badge-salon px-2 py-1">
                                <i class="fas fa-shield-alt me-1" aria-hidden="true"></i> Akses Staf
                            </span>
                        </div>
                        <p class="auth-form-subtitle">
                            Gunakan akun sesuai peran kerja Anda untuk membuka modul operasional {{ $brandSetting->salon_name }}.
                        </p>

                        {{-- Pesan Error / Status --}}
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-exclamation-circle mt-1" aria-hidden="true"></i>
                                    <div>
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                </div>
                                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Tutup peringatan"></button>
                            </div>
                        @endif

                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
                                <i class="fas fa-check-circle me-1" aria-hidden="true"></i> {{ session('status') }}
                                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Tutup pesan"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" id="login-form" novalidate>
                            @csrf
                            <input type="hidden" name="recaptcha_token" id="recaptcha_token">

                            <div class="mb-3">
                                <label for="exampleInputEmail" class="auth-label">Alamat Email</label>
                                <div class="auth-input-group">
                                    <i class="fas fa-envelope auth-input-icon" aria-hidden="true"></i>
                                    <input
                                        type="email"
                                        class="auth-input @error('email') is-invalid @enderror"
                                        id="exampleInputEmail"
                                        name="email"
                                        value="{{ old('email') }}"
                                        placeholder="nama@salon.test"
                                        autocomplete="email"
                                        required
                                        autofocus
                                        aria-invalid="@error('email') true @else false @enderror"
                                        @error('email') aria-describedby="email-error" @enderror
                                    >
                                </div>
                                @error('email')
                                    <div id="email-error" class="invalid-feedback d-block small mt-1" role="alert">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="exampleInputPassword" class="auth-label">Kata Sandi</label>
                                <div class="auth-input-group">
                                    <i class="fas fa-lock auth-input-icon" aria-hidden="true"></i>
                                    <input
                                        type="password"
                                        class="auth-input auth-input--with-Action @error('password') is-invalid @enderror"
                                        id="exampleInputPassword"
                                        name="password"
                                        placeholder="Masukkan kata sandi"
                                        autocomplete="current-password"
                                        required
                                        aria-invalid="@error('password') true @else false @enderror"
                                        @error('password') aria-describedby="password-error" @enderror
                                    >
                                    <button
                                        type="button"
                                        class="auth-pw-toggle"
                                        id="togglePasswordBtn"
                                        aria-label="Tampilkan kata sandi"
                                        aria-pressed="false"
                                        onclick="toggleLoginPassword()"
                                    >
                                        <i class="fas fa-eye" id="togglePasswordIcon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div id="password-error" class="invalid-feedback d-block small mt-1" role="alert">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="form-check mb-0">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="customCheck"
                                        name="remember"
                                        {{ old('remember') ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label small text-secondary user-select-none" for="customCheck">
                                        Ingat sesi perangkat ini
                                    </label>
                                </div>
                                <span class="small text-muted">
                                    <i class="fas fa-calendar-day me-1" aria-hidden="true"></i>
                                    {{ now()->timezone($brandSetting->timezone ?: 'Asia/Jakarta')->format('d M Y') }}
                                </span>
                            </div>

                            <button type="submit" class="auth-submit-btn" id="loginSubmitBtn">
                                <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                                <span id="loginSubmitText">Masuk ke Aplikasi</span>
                            </button>
                        </form>

                        {{-- Pil Peran Cepat (Memudahkan Pengujian Multi-Role) --}}
                        <div class="auth-role-section">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-semibold text-secondary">
                                    <i class="fas fa-user-tag me-1 text-primary" aria-hidden="true"></i>
                                    Pilih Cepat Akun Peran (Demo)
                                </span>
                                <small class="text-muted">Klik untuk isi otomatis</small>
                            </div>
                            <div class="auth-role-grid" role="group" aria-label="Pilih akun peran demo">
                                <button type="button" class="auth-role-chip" onclick="fillDemoRole('admin@cms.test', '1234')">
                                    <i class="fas fa-crown text-warning" aria-hidden="true"></i> Owner
                                </button>
                                <button type="button" class="auth-role-chip" onclick="fillDemoRole('resepsionis@salon.test', '1234')">
                                    <i class="fas fa-concierge-bell text-primary" aria-hidden="true"></i> Resepsionis
                                </button>
                                <button type="button" class="auth-role-chip" onclick="fillDemoRole('kasir@salon.test', '1234')">
                                    <i class="fas fa-cash-register text-success" aria-hidden="true"></i> Kasir POS
                                </button>
                                <button type="button" class="auth-role-chip" onclick="fillDemoRole('stylist@salon.test', '1234')">
                                    <i class="fas fa-cut text-danger" aria-hidden="true"></i> Stylist
                                </button>
                                <button type="button" class="auth-role-chip" onclick="fillDemoRole('stok@salon.test', '1234')">
                                    <i class="fas fa-boxes text-info" aria-hidden="true"></i> Petugas Stok
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-footer-note">
                &copy; {{ date('Y') }} <strong>{{ $brandSetting->salon_name }}</strong>
                @if ($brandSetting->tagline)
                    — {{ $brandSetting->tagline }}
                @endif
            </div>
        </div>
    </main>

    <script>
        function toggleLoginPassword() {
            const input = document.getElementById('exampleInputPassword');
            const btn = document.getElementById('togglePasswordBtn');
            const icon = document.getElementById('togglePasswordIcon');
            if (!input || !btn || !icon) return;

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            btn.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        }

        function fillDemoRole(email, password) {
            const emailInput = document.getElementById('exampleInputEmail');
            const pwInput = document.getElementById('exampleInputPassword');
            const submitBtn = document.getElementById('loginSubmitBtn');

            if (emailInput && pwInput) {
                emailInput.value = email;
                pwInput.value = password;
                if (submitBtn) {
                    submitBtn.focus();
                }
            }
        }

        document.getElementById('login-form')?.addEventListener('submit', function () {
            const submitBtn = document.getElementById('loginSubmitBtn');
            const submitText = document.getElementById('loginSubmitText');
            if (submitBtn && submitText) {
                submitBtn.setAttribute('aria-busy', 'true');
                submitText.textContent = 'Memproses masuk...';
            }
        });
    </script>

    @if (config('services.recaptcha.enabled'))
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
        <script>
            document.getElementById('login-form').addEventListener('submit', function(event) {
                event.preventDefault();
                const form = this;
                grecaptcha.ready(function() {
                    grecaptcha.execute(
                        '{{ config('services.recaptcha.site_key') }}', {
                            action: 'login'
                        }
                    ).then(function(token) {
                        document.getElementById('recaptcha_token').value = token;
                        form.submit();
                    });
                });
            });
        </script>
    @endif
</body>

<x-template-bottom />
