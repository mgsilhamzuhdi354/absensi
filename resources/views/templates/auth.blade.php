<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#102f50">
    <title>@yield('page-title', $title ?? 'Absensi') · Indoocean</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('myhr/app/icons/icon-192x192.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}" defer></script>
    @stack('style')
</head>
<body class="auth-page">
    <a class="auth-skip" href="#main-content">Lewati ke konten</a>
    <div class="auth-shell">
        <header class="auth-header">
            <a class="auth-brand" href="{{ route('welcome') }}" aria-label="Indoocean, halaman utama">
                <img src="{{ asset('images/logo.png') }}" width="44" height="44" alt="">
                <span><strong>INDOOCEAN</strong><small>CREW SERVICE</small></span>
            </a>
            <span class="auth-header-label"><span></span> Portal Karyawan</span>
        </header>
        <main id="main-content" class="auth-main" tabindex="-1">
            @php
                $authAlert = session()->pull('alert.config');
                $authAlert = is_string($authAlert) ? json_decode($authAlert, true) : $authAlert;
                $authAlertText = is_array($authAlert) ? ($authAlert['text'] ?? strip_tags($authAlert['html'] ?? '')) : '';
            @endphp
            @if(session('success'))
                <div class="auth-notice auth-notice-success" role="status">{{ session('success') }}</div>
            @elseif($authAlertText)
                <div class="auth-notice {{ ($authAlert['icon'] ?? '') === 'success' ? 'auth-notice-success' : '' }}" role="alert">{{ $authAlertText }}</div>
            @endif
            @yield('content')
        </main>
        <footer class="auth-footer">
            <span>&copy; {{ date('Y') }} {{ optional($selectedCompany ?? null)->name ?? 'PT Indoocean Crew Service' }}</span>
            @if(file_exists(public_path('app/absensi.apk')))
                <a href="{{ asset('app/absensi.apk') }}" download><i class="fab fa-android" aria-hidden="true"></i> Unduh aplikasi Android <i class="fas fa-arrow-down" aria-hidden="true"></i></a>
            @else
                <span>Sistem Absensi Karyawan</span>
            @endif
        </footer>
    </div>
    @stack('script')
</body>
</html>
