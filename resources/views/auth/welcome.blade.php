@extends('templates.login')

@push('style')
<style>
    html,
    body,
    .login-section,
    .login-section > div {
        width: 100% !important;
        max-width: none !important;
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .preload-container {
        display: none !important;
    }
</style>
@endpush

@section('container')
<style>
    * {
        box-sizing: border-box;
    }

    html,
    body {
        min-height: 100vh;
        overflow-x: hidden;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    }

    .landing-page {
        min-height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 18px;
        background:
            linear-gradient(135deg, rgba(7, 54, 116, 0.92), rgba(25, 102, 201, 0.9)),
            url('{{ asset('images/logo.png') }}');
        background-size: cover, 420px;
        background-position: center;
    }

    .landing-shell {
        width: min(920px, 100%);
        color: #fff;
    }

    .brand-header {
        text-align: center;
        margin-bottom: 26px;
    }

    .brand-logo {
        width: 88px;
        height: 88px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.22);
    }

    .brand-logo img {
        width: 64px;
        height: 64px;
        object-fit: contain;
    }

    .brand-title {
        margin: 0;
        font-size: clamp(1.45rem, 4vw, 2.2rem);
        font-weight: 800;
        line-height: 1.2;
    }

    .brand-subtitle {
        margin: 8px 0 0;
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.98rem;
    }

    .time-display {
        width: fit-content;
        min-width: 210px;
        margin: 0 auto 24px;
        padding: 12px 22px;
        text-align: center;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.13);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .current-time {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .current-date {
        margin-top: 5px;
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.88rem;
    }

    .section-title {
        margin: 0 0 14px;
        text-align: center;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .company-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 14px;
        margin: 0 auto;
    }

    .company-button,
    .feature-button,
    .login-button,
    .back-company-button {
        text-decoration: none;
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.13);
        color: #fff;
        transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
    }

    .company-button:hover,
    .feature-button:hover,
    .login-button:hover,
    .back-company-button:hover {
        color: #fff;
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.22);
        border-color: rgba(255, 255, 255, 0.38);
    }

    .company-button {
        display: flex;
        align-items: center;
        gap: 14px;
        min-height: 86px;
        padding: 16px;
        border-radius: 18px;
    }

    .company-code {
        width: 58px;
        height: 54px;
        flex: 0 0 58px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
        border-radius: 15px;
        background: rgba(255, 255, 255, 0.18);
        font-size: clamp(0.62rem, 2vw, 0.82rem);
        font-weight: 800;
        line-height: 1.05;
        text-align: center;
        overflow-wrap: anywhere;
    }

    .company-info {
        min-width: 0;
    }

    .company-name {
        display: block;
        font-weight: 800;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .company-action {
        display: block;
        margin-top: 5px;
        color: rgba(255, 255, 255, 0.75);
        font-size: 0.82rem;
    }

    .selected-company {
        width: fit-content;
        max-width: 100%;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0 auto 22px;
        padding: 12px 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.13);
        border: 1px solid rgba(255, 255, 255, 0.22);
    }

    .feature-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 220px));
        justify-content: center;
        gap: 18px;
        margin: 0 auto 24px;
    }

    .feature-button {
        display: flex;
        min-height: 180px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 22px;
        text-align: center;
        border-radius: 20px;
    }

    .feature-icon {
        width: 68px;
        height: 68px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        border-radius: 18px;
        font-size: 30px;
    }

    .icon-face {
        background: linear-gradient(135deg, #5266d8, #8b59c8);
    }

    .icon-qr {
        background: linear-gradient(135deg, #0fa082, #28c96d);
    }

    .feature-title {
        display: block;
        font-weight: 800;
        font-size: 1rem;
    }

    .feature-desc {
        display: block;
        margin-top: 7px;
        color: rgba(255, 255, 255, 0.72);
        font-size: 0.84rem;
        line-height: 1.35;
    }

    .action-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
    }

    .login-button,
    .back-company-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 48px;
        padding: 12px 24px;
        border-radius: 999px;
        font-weight: 800;
    }

    .landing-footer {
        margin-top: 28px;
        text-align: center;
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.85rem;
    }

    .landing-footer a {
        color: rgba(255, 255, 255, 0.78);
        text-decoration: none;
        font-weight: 700;
    }

    @media (max-width: 560px) {
        .landing-page {
            align-items: flex-start;
            padding-top: 28px;
        }

        .brand-logo {
            width: 76px;
            height: 76px;
        }

        .brand-logo img {
            width: 56px;
            height: 56px;
        }

        .company-grid,
        .feature-grid {
            grid-template-columns: 1fr;
        }

        .feature-button {
            min-height: 142px;
        }

        .current-time {
            font-size: 1.7rem;
        }
    }
</style>

<div class="landing-page">
    <div class="landing-shell">
        <div class="brand-header">
            <div class="brand-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
            </div>
            <h1 class="brand-title">PT Indoocean Crew Service</h1>
            <p class="brand-subtitle">Sistem Absensi Karyawan</p>
        </div>

        <div class="time-display">
            <div class="current-time" id="currentTime">--:--:--</div>
            <div class="current-date" id="currentDate">Loading...</div>
        </div>

        @if($selectedCompany)
            <div class="selected-company">
                <span class="company-code">{{ $selectedCompany->code }}</span>
                <span class="company-info">
                    <span class="company-name">{{ $selectedCompany->name }}</span>
                    <span class="company-action">Perusahaan aktif</span>
                </span>
            </div>

            <div class="feature-grid">
                <a href="{{ url('/attendance/face?company_id=' . $selectedCompany->id) }}" class="feature-button">
                    <span class="feature-icon icon-face"><i class="fas fa-user-circle"></i></span>
                    <span class="feature-title">Face Recognition</span>
                    <span class="feature-desc">Absen dengan pengenalan wajah</span>
                </a>

                <a href="{{ url('/attendance/qr?company_id=' . $selectedCompany->id) }}" class="feature-button">
                    <span class="feature-icon icon-qr"><i class="fas fa-qrcode"></i></span>
                    <span class="feature-title">QR Code</span>
                    <span class="feature-desc">Scan QR untuk absensi</span>
                </a>
            </div>

            <div class="action-row">
                <a href="{{ route('welcome') }}" class="back-company-button">
                    <i class="fas fa-building"></i>
                    <span>Pilih PT Lain</span>
                </a>
                <a href="{{ url('/login?company_id=' . $selectedCompany->id) }}" class="login-button">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login Dashboard</span>
                </a>
            </div>
        @else
            <h2 class="section-title">Pilih Perusahaan</h2>
            <div class="company-grid">
                @forelse(($companies ?? collect()) as $company)
                    <a href="{{ route('welcome', ['company_id' => $company->id]) }}" class="company-button">
                        <span class="company-code">{{ $company->code }}</span>
                        <span class="company-info">
                            <span class="company-name">{{ $company->name }}</span>
                            <span class="company-action">Buka menu absensi</span>
                        </span>
                    </a>
                @empty
                    <a href="{{ url('/attendance/face') }}" class="company-button">
                        <span class="company-code">FACE</span>
                        <span class="company-info">
                            <span class="company-name">Face Recognition</span>
                            <span class="company-action">Buka absensi wajah</span>
                        </span>
                    </a>
                    <a href="{{ url('/attendance/qr') }}" class="company-button">
                        <span class="company-code">QR</span>
                        <span class="company-info">
                            <span class="company-name">QR Code</span>
                            <span class="company-action">Buka absensi QR</span>
                        </span>
                    </a>
                @endforelse
            </div>
        @endif

        <div class="landing-footer">
            <p>&copy; {{ date('Y') }} PT Indoocean Crew Service</p>
            <a href="{{ asset('app/absensi.apk') }}" download>
                <i class="fab fa-android"></i> Download App Android
            </a>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('currentTime').textContent = `${hours}:${minutes}:${seconds}`;

        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        document.getElementById('currentDate').textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    }

    updateClock();
    setInterval(updateClock, 1000);
</script>
@endsection
