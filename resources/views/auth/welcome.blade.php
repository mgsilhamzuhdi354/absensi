@extends('templates.auth')
@section('page-title', 'Selamat datang')
@section('content')
    <div class="auth-layout">
        @include('auth.partials.intro')
        <section class="auth-card auth-welcome-card" aria-labelledby="welcome-title">
            <div class="auth-card-top">
                <span class="auth-eyebrow">SELAMAT DATANG</span>
                <div class="auth-clock"><i class="far fa-clock" aria-hidden="true"></i><time data-clock>{{ now()->format('H:i') }}</time></div>
            </div>
            <ol class="auth-steps" aria-label="Langkah absensi">
                <li class="{{ $selectedCompany ? 'is-complete' : 'is-current' }}" @if(!$selectedCompany) aria-current="step" @endif><span>1</span> Perusahaan</li>
                <li class="{{ $selectedCompany ? 'is-current' : '' }}" @if($selectedCompany) aria-current="step" @endif><span>2</span> Mulai aktivitas</li>
            </ol>
            @if($selectedCompany)
                <h1 class="auth-title" id="welcome-title">Siap memulai hari?</h1>
                <p class="auth-description">Pilih cara absensi atau masuk ke dashboard Anda.</p>
                <div class="auth-selected-company">
                    <span class="auth-company-icon"><i class="far fa-building" aria-hidden="true"></i></span>
                    <span><small>Perusahaan Anda</small><strong>{{ $selectedCompany->name }}</strong></span>
                    <a href="{{ route('welcome') }}" class="auth-change">Ganti<span class="auth-sr-only"> perusahaan</span></a>
                </div>
                <div class="auth-methods">
                    <a href="{{ url('/attendance/face?company_id=' . $selectedCompany->id) }}" class="auth-method">
                        <span class="auth-method-icon"><i class="fas fa-user-circle" aria-hidden="true"></i></span>
                        <span><strong>Face Recognition</strong><small>Absen dengan pengenalan wajah</small></span>
                        <i class="fas fa-arrow-right auth-arrow" aria-hidden="true"></i>
                    </a>
                    <a href="{{ url('/attendance/qr?company_id=' . $selectedCompany->id) }}" class="auth-method auth-method-teal">
                        <span class="auth-method-icon"><i class="fas fa-qrcode" aria-hidden="true"></i></span>
                        <span><strong>QR Code</strong><small>Pindai kode untuk mencatat kehadiran</small></span>
                        <i class="fas fa-arrow-right auth-arrow" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="auth-divider"><span>Akses akun Anda</span></div>
                <a href="{{ url('/login?company_id=' . $selectedCompany->id) }}" class="auth-submit">Masuk ke Dashboard <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            @else
                <h1 class="auth-title" id="welcome-title">Pilih Perusahaan</h1>
                <p class="auth-description">Mulai dengan memilih perusahaan tempat Anda bekerja.</p>
                <div class="auth-companies">
                    @forelse(($companies ?? collect()) as $company)
                        <a href="{{ route('welcome', ['company_id' => $company->id]) }}" class="auth-company">
                            <span class="auth-company-icon"><i class="far fa-building" aria-hidden="true"></i></span>
                            <span class="auth-company-copy"><strong>{{ $company->name }}</strong><small>Lanjut ke menu absensi</small></span>
                            <i class="fas fa-chevron-right auth-arrow" aria-hidden="true"></i>
                        </a>
                    @empty
                        <a href="{{ url('/attendance/face') }}" class="auth-company"><span class="auth-company-icon"><i class="fas fa-user-circle" aria-hidden="true"></i></span><span class="auth-company-copy"><strong>Face Recognition</strong><small>Absen dengan pengenalan wajah</small></span><i class="fas fa-chevron-right auth-arrow" aria-hidden="true"></i></a>
                        <a href="{{ url('/attendance/qr') }}" class="auth-company"><span class="auth-company-icon"><i class="fas fa-qrcode" aria-hidden="true"></i></span><span class="auth-company-copy"><strong>QR Code</strong><small>Absen dengan memindai kode</small></span><i class="fas fa-chevron-right auth-arrow" aria-hidden="true"></i></a>
                    @endforelse
                </div>
                <div class="auth-help"><i class="fas fa-info-circle" aria-hidden="true"></i><p>Gunakan akun karyawan untuk melihat riwayat absensi dan mengakses dashboard.</p></div>
                <a href="{{ route('login') }}" class="auth-submit auth-submit-secondary">Masuk ke Dashboard <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            @endif
        </section>
    </div>
@endsection
