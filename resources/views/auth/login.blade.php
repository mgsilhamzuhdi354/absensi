@extends('templates.login')
@push('style')
<style>
    /* Override all parent styles - body has max-width: 800px from external CSS */
    html, body {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 auto !important;
        padding: 0 !important;
    }
    .preload-container {
        display: none !important;
    }
    .login-section,
    .login-section > div {
        width: 100% !important;
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        min-height: 100vh !important;
    }
</style>
@endpush
@section('container')
    <style>
        /* ============================================
           LOGIN PAGE - MOBILE FIRST SIMPLE DESIGN
           ============================================ */
        
        * {
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        /* Simple Gradient Background */
        .login-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: -1;
        }
        
        /* Main Container - CENTERED */
        .login-section,
        .login-section > div {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .login-container {
            min-height: 100vh;
            width: 100%;
            display: flex !important;
            flex-direction: column;
            justify-content: center !important;
            align-items: center !important;
            padding: 20px;
        }
        
        /* Login Card - Simple & Mobile Friendly */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px 25px;
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
        }
        
        /* Logo */
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .login-logo img {
            width: 70px;
            height: 70px;
            border-radius: 14px;
            object-fit: contain;
        }
        
        .login-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            text-align: center;
            margin: 0 0 5px 0;
        }
        
        .login-subtitle {
            font-size: 0.9rem;
            color: #64748b;
            text-align: center;
            margin: 0 0 25px 0;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #334155;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px; /* Prevents iOS zoom */
            background-color: #f8fafc;
            color: #1e293b;
            transition: all 0.2s ease;
            -webkit-appearance: none;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            outline: none;
            background-color: #fff;
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }
        
        .is-invalid {
            border-color: #ef4444 !important;
        }
        
        .invalid-feedback {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }
        
        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            cursor: pointer;
            padding: 5px;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 5px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* Forgot Password */
        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-link:hover {
            color: #667eea;
        }
        
        /* Footer Info */
        .footer-info {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 0.8rem;
        }
        
        .footer-info a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        /* Mobile Responsive - Already mobile first */
        @media (max-width: 400px) {
            .login-card {
                padding: 25px 20px;
                margin: 0 10px;
            }
            
            .login-title {
                font-size: 1.2rem;
            }
        }
        
        /* Landscape phone */
        @media (max-height: 500px) and (orientation: landscape) {
            .login-container {
                padding: 10px 20px;
            }
            
            .login-card {
                padding: 20px;
            }
            
            .login-logo img {
                width: 50px;
                height: 50px;
            }
            
            .login-subtitle {
                margin-bottom: 15px;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
        }
        /* Back Button */
        .btn-back {
            display: inline-flex;
            align-items: center;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 20px;
            transition: color 0.2s;
        }
        
        .btn-back:hover {
            color: #667eea;
        }
        
        .btn-back i {
            margin-right: 8px;
        }
    </style>
    
    <!-- Background -->
    <div class="login-bg"></div>
    
    <!-- Main Container -->
    <div class="login-container">
        <div class="login-card">
            <a href="{{ route('welcome') }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> Kembali <!-- Back Button -->
            </a>
            
            <!-- Logo -->
            <div class="login-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
            </div>
            
            <h1 class="login-title">PT Indoocean Crew Service</h1>
            <p class="login-subtitle">Silakan masuk ke akun Anda</p>
            
            <!-- Login Form -->
            <form action="{{ url('/login-proses') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa fa-user"></i> Username
                    </label>
                    <input type="text" 
                           class="form-control @error('username') is-invalid @enderror" 
                           placeholder="Masukkan username" 
                           name="username" 
                           value="{{ old('username') }}"
                           autocomplete="username">
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa fa-lock"></i> Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               placeholder="Masukkan password" 
                               name="password"
                               id="password-field"
                               autocomplete="current-password">
                        <a class="password-toggle" id="password-addon" onclick="togglePassword()">
                            <i class="fa fa-eye" id="toggle-icon"></i>
                        </a>
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fa fa-sign-in-alt"></i> Masuk
                </button>
                
                <a href="{{ url('/forgot-password') }}" class="forgot-link">
                    Lupa Password?
                </a>
            </form>
            
            <!-- Footer -->
            <div class="footer-info">
                <p>Belum punya akun? Hubungi Admin</p>
                <a href="{{ asset('app/absensi.apk') }}" download>
                    <i class="fab fa-android"></i> Download App Android
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password-field');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
@endsection
