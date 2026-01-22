@extends('templates.login')
@section('container')
<style>
    /* ============================================
       LANDING PAGE - MODERN ATTENDANCE SYSTEM
       ============================================ */
    
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    html, body {
        min-height: 100vh;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        overflow-x: hidden;
    }
    
    /* Animated Gradient Background */
    .landing-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(-45deg, #0052CC, #1a73e8, #0066FF, #0052CC);
        background-size: 400% 400%;
        animation: gradientShift 15s ease infinite;
        z-index: -2;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    /* Floating Particles */
    .particles {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: -1;
    }
    
    .particle {
        position: absolute;
        width: 6px;
        height: 6px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        animation: float 20s infinite;
    }
    
    .particle:nth-child(1) { left: 10%; animation-delay: 0s; animation-duration: 15s; }
    .particle:nth-child(2) { left: 20%; animation-delay: 2s; animation-duration: 18s; }
    .particle:nth-child(3) { left: 30%; animation-delay: 4s; animation-duration: 20s; }
    .particle:nth-child(4) { left: 40%; animation-delay: 1s; animation-duration: 16s; }
    .particle:nth-child(5) { left: 50%; animation-delay: 3s; animation-duration: 22s; }
    .particle:nth-child(6) { left: 60%; animation-delay: 5s; animation-duration: 17s; }
    .particle:nth-child(7) { left: 70%; animation-delay: 2.5s; animation-duration: 19s; }
    .particle:nth-child(8) { left: 80%; animation-delay: 1.5s; animation-duration: 21s; }
    .particle:nth-child(9) { left: 90%; animation-delay: 4.5s; animation-duration: 14s; }
    .particle:nth-child(10) { left: 15%; animation-delay: 3.5s; animation-duration: 23s; }
    
    @keyframes float {
        0%, 100% {
            transform: translateY(100vh) rotate(0deg);
            opacity: 0;
        }
        10% {
            opacity: 1;
        }
        90% {
            opacity: 1;
        }
        100% {
            transform: translateY(-100vh) rotate(720deg);
            opacity: 0;
        }
    }
    
    /* Main Container */
    .landing-container {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    /* Header Section */
    .landing-header {
        text-align: center;
        margin-bottom: 40px;
        animation: fadeInDown 1s ease;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .landing-logo {
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        border: 2px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .landing-logo img {
        width: 70px;
        height: 70px;
        object-fit: contain;
        border-radius: 15px;
    }
    
    .landing-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 10px;
        text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
    }
    
    .landing-subtitle {
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 400;
    }
    
    /* Feature Cards Container */
    .feature-cards {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        flex-wrap: wrap;
        justify-content: center;
        max-width: 600px;
    }
    
    /* Feature Card */
    .feature-card {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 30px 25px;
        width: 180px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.15);
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        text-decoration: none;
        animation: fadeInUp 1s ease;
    }
    
    .feature-card:nth-child(2) {
        animation-delay: 0.1s;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .feature-card:hover {
        transform: translateY(-10px) scale(1.03);
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.3);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    }
    
    .feature-card:active {
        transform: translateY(-5px) scale(1);
    }
    
    .feature-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        transition: all 0.3s ease;
    }
    
    .feature-card:hover .feature-icon {
        transform: scale(1.1) rotate(5deg);
    }
    
    .icon-face {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    }
    
    .icon-qr {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        box-shadow: 0 10px 30px rgba(17, 153, 142, 0.4);
    }
    
    .feature-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 8px;
    }
    
    .feature-desc {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.4;
    }
    
    /* Time Display */
    .time-display {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 15px 30px;
        margin-bottom: 25px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        animation: fadeIn 1s ease 0.3s both;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .current-time {
        font-size: 2.5rem;
        font-weight: 700;
        color: #fff;
        font-variant-numeric: tabular-nums;
    }
    
    .current-date {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.7);
        margin-top: 5px;
    }
    
    /* Login Button */
    .login-section {
        animation: fadeInUp 1s ease 0.2s both;
    }
    
    .btn-login-main {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 16px 50px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        border-radius: 50px;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    
    .btn-login-main:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.4);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        color: #fff;
    }
    
    .btn-login-main i {
        font-size: 1.2rem;
    }
    
    /* Footer */
    .landing-footer {
        margin-top: 40px;
        text-align: center;
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.85rem;
        animation: fadeIn 1s ease 0.5s both;
    }
    
    .landing-footer a {
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
    }
    
    .landing-footer a:hover {
        color: #fff;
    }
    
    /* Color Settings Button */
    .color-settings-btn {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 1000;
        color: #fff;
        font-size: 18px;
    }
    .color-settings-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.1);
    }
    
    .color-panel {
        position: fixed;
        top: 75px;
        right: 20px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 16px;
        padding: 15px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        z-index: 1001;
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .color-panel.show { display: block; }
    
    .color-panel-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 12px;
        text-align: center;
    }
    
    .color-options {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }
    
    .color-option {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid transparent;
    }
    .color-option:hover { transform: scale(1.15); }
    .color-option.active { border-color: #333; box-shadow: 0 0 0 2px #fff; }
    
    /* Mobile Responsive */
    @media (max-width: 450px) {
        .landing-title {
            font-size: 1.4rem;
        }
        
        .feature-cards {
            flex-direction: column;
            align-items: center;
        }
        
        .feature-card {
            width: 90%;
            max-width: 280px;
        }
        
        .current-time {
            font-size: 2rem;
        }
    }
    
    /* Landscape Mode */
    @media (max-height: 600px) and (orientation: landscape) {
        .landing-container {
            padding: 10px 20px;
        }
        
        .landing-header {
            margin-bottom: 15px;
        }
        
        .landing-logo {
            width: 60px;
            height: 60px;
            margin-bottom: 10px;
        }
        
        .landing-logo img {
            width: 40px;
            height: 40px;
        }
        
        .landing-title {
            font-size: 1.2rem;
        }
        
        .feature-cards {
            margin-bottom: 15px;
        }
        
        .feature-card {
            padding: 15px;
            width: 150px;
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .time-display {
            display: none;
        }
        
        .landing-footer {
            margin-top: 15px;
        }
    }
</style>

<!-- Color Settings Button -->
<div class="color-settings-btn" onclick="toggleColorPanel()">
    <i class="fas fa-palette"></i>
</div>

<!-- Color Panel -->
<div class="color-panel" id="colorPanel">
    <div class="color-panel-title">Pilih Tema Warna</div>
    <div class="color-options">
        <div class="color-option" style="background: linear-gradient(135deg, #0052CC, #1a73e8);" data-colors="#0052CC,#1a73e8,#0066FF,#0052CC" title="Biru"></div>
        <div class="color-option" style="background: linear-gradient(135deg, #667eea, #764ba2);" data-colors="#667eea,#764ba2,#6B73FF,#764ba2" title="Ungu"></div>
        <div class="color-option" style="background: linear-gradient(135deg, #11998e, #38ef7d);" data-colors="#11998e,#38ef7d,#00c853,#11998e" title="Hijau"></div>
        <div class="color-option" style="background: linear-gradient(135deg, #ee0979, #ff6a00);" data-colors="#ee0979,#ff6a00,#f857a6,#ff5858" title="Pink-Orange"></div>
        <div class="color-option" style="background: linear-gradient(135deg, #1a1a2e, #16213e);" data-colors="#1a1a2e,#16213e,#0f3460,#1a1a2e" title="Gelap"></div>
        <div class="color-option" style="background: linear-gradient(135deg, #0f0c29, #302b63);" data-colors="#0f0c29,#302b63,#24243e,#1a1a2e" title="Ungu Gelap"></div>
        <div class="color-option" style="background: linear-gradient(135deg, #f093fb, #f5576c);" data-colors="#f093fb,#f5576c,#ff6b6b,#f093fb" title="Pink"></div>
        <div class="color-option" style="background: linear-gradient(135deg, #4facfe, #00f2fe);" data-colors="#4facfe,#00f2fe,#43e97b,#4facfe" title="Cyan"></div>
    </div>
</div>

<!-- Background -->
<div class="landing-bg" id="landingBg"></div>

<!-- Floating Particles -->
<div class="particles">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
</div>

<!-- Main Container -->
<div class="landing-container">
    <!-- Header -->
    <div class="landing-header">
        <div class="landing-logo">
            @php
                $settings = App\Models\settings::first();
            @endphp
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
        </div>
        <h1 class="landing-title">PT Indoocean Crew Service</h1>
        <p class="landing-subtitle">Sistem Absensi Karyawan</p>
    </div>
    
    <!-- Time Display -->
    <div class="time-display">
        <div class="current-time" id="currentTime">--:--:--</div>
        <div class="current-date" id="currentDate">Loading...</div>
    </div>
    
    <!-- Feature Cards -->
    <div class="feature-cards">
        <a href="{{ url('/attendance/face') }}" class="feature-card">
            <div class="feature-icon icon-face">
                <i class="fas fa-user-circle"></i>
            </div>
            <h3 class="feature-title">Face Recognition</h3>
            <p class="feature-desc">Absen dengan pengenalan wajah</p>
        </a>
        
        <a href="{{ url('/attendance/qr') }}" class="feature-card">
            <div class="feature-icon icon-qr">
                <i class="fas fa-qrcode"></i>
            </div>
            <h3 class="feature-title">QR Code</h3>
            <p class="feature-desc">Scan QR untuk absensi</p>
        </a>
    </div>
    
    <!-- Login Button -->
    <div class="login-section">
        <a href="{{ url('/login') }}" class="btn-login-main">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login Dashboard</span>
        </a>
    </div>
    
    <!-- Footer -->
    <div class="landing-footer">
        <p>&copy; {{ date('Y') }} PT Indoocean Crew Service</p>
        <a href="{{ asset('app/absensi.apk') }}" download style="display: inline-flex; align-items: center; gap: 5px; margin-top: 10px;">
            <i class="fab fa-android"></i> Download App Android
        </a>
    </div>
</div>

<script>
    // Real-time Clock
    function updateClock() {
        const now = new Date();
        
        // Time
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('currentTime').textContent = `${hours}:${minutes}:${seconds}`;
        
        // Date
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const dayName = days[now.getDay()];
        const date = now.getDate();
        const month = months[now.getMonth()];
        const year = now.getFullYear();
        document.getElementById('currentDate').textContent = `${dayName}, ${date} ${month} ${year}`;
    }
    
    updateClock();
    setInterval(updateClock, 1000);
    
    // Color Settings
    function toggleColorPanel() {
        document.getElementById('colorPanel').classList.toggle('show');
    }
    
    // Close panel when clicking outside
    document.addEventListener('click', function(e) {
        const panel = document.getElementById('colorPanel');
        const btn = document.querySelector('.color-settings-btn');
        if (!panel.contains(e.target) && !btn.contains(e.target)) {
            panel.classList.remove('show');
        }
    });
    
    // Color options click handler
    document.querySelectorAll('.color-option').forEach(option => {
        option.addEventListener('click', function() {
            const colors = this.dataset.colors;
            applyTheme(colors);
            
            // Update active state
            document.querySelectorAll('.color-option').forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            
            // Save to localStorage
            localStorage.setItem('landingTheme', colors);
        });
    });
    
    function applyTheme(colors) {
        const bg = document.getElementById('landingBg');
        bg.style.background = `linear-gradient(-45deg, ${colors})`;
        bg.style.backgroundSize = '400% 400%';
    }
    
    // Load saved theme
    const savedTheme = localStorage.getItem('landingTheme');
    if (savedTheme) {
        applyTheme(savedTheme);
        // Mark active option
        document.querySelectorAll('.color-option').forEach(option => {
            if (option.dataset.colors === savedTheme) {
                option.classList.add('active');
            }
        });
    } else {
        // Default active
        document.querySelector('.color-option').classList.add('active');
    }
</script>
@endsection