<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sesi Berakhir - Sistem Absensi</title>
    <link rel="shorcut icon" href="{{ url('assets/img/absensi.png') }}">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ url('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ url('adminlte/dist/css/adminlte.min.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .session-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .session-icon {
            font-size: 80px;
            color: #4facfe;
            margin-bottom: 20px;
        }

        .session-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .session-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn-login {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
            padding: 12px 40px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 172, 254, 0.4);
            color: white;
            text-decoration: none;
        }

        .countdown-info {
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }

        .countdown {
            font-weight: bold;
            color: #4facfe;
        }
    </style>
</head>

<body>
    <div class="session-container">
        <div class="session-icon">
            <i class="fas fa-clock"></i>
        </div>
        <h1 class="session-title">Sesi Telah Berakhir</h1>
        <p class="session-message">
            Sesi login Anda telah berakhir karena tidak ada aktivitas.
            <br>Silakan login kembali untuk melanjutkan.
        </p>
        <a href="{{ url('/login') }}" class="btn-login">
            <i class="fas fa-sign-in-alt mr-2"></i> Login Kembali
        </a>
        <div class="countdown-info">
            <p>Anda akan dialihkan ke halaman login dalam <span class="countdown" id="countdown">5</span> detik</p>
        </div>
    </div>

    <script>
        // Auto redirect to login
        let seconds = 5;
        const countdownEl = document.getElementById('countdown');
        const interval = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = '{{ url("/login") }}';
            }
        }, 1000);
    </script>
</body>

</html>