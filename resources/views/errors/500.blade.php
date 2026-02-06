<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terjadi Kesalahan - Sistem Absensi</title>
    <link rel="shorcut icon" href="{{ url('assets/img/absensi.png') }}">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ url('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ url('adminlte/dist/css/adminlte.min.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
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

        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .error-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn-retry {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }

        .support-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #999;
        }

        .countdown {
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h1 class="error-title">Terjadi Kesalahan Sistem</h1>
        <p class="error-message">
            Mohon maaf, sistem sedang mengalami gangguan sementara.<br>
            Silakan coba beberapa saat lagi atau hubungi administrator.
        </p>
        <div>
            <a href="javascript:location.reload()" class="btn-retry">
                <i class="fas fa-redo-alt mr-2"></i> Coba Lagi
            </a>
            <a href="{{ url('/') }}" class="btn-retry btn-secondary">
                <i class="fas fa-home mr-2"></i> Beranda
            </a>
        </div>
        <div class="support-info">
            <p>Halaman akan otomatis di-refresh dalam <span class="countdown" id="countdown">30</span> detik</p>
            <p><i class="fas fa-info-circle"></i> Jika masalah berlanjut, hubungi IT Support</p>
        </div>
    </div>

    <script>
        // Auto refresh countdown
        let seconds = 30;
        const countdownEl = document.getElementById('countdown');
        const interval = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                location.reload();
            }
        }, 1000);
    </script>
</body>

</html>