<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemeliharaan Sistem - Sistem Absensi</title>
    <link rel="shorcut icon" href="{{ url('assets/img/absensi.png') }}">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ url('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ url('adminlte/dist/css/adminlte.min.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .maintenance-container {
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

        .maintenance-icon {
            font-size: 80px;
            color: #f5576c;
            margin-bottom: 20px;
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .maintenance-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .maintenance-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn-retry {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            box-shadow: 0 10px 20px rgba(245, 87, 108, 0.4);
            color: white;
            text-decoration: none;
        }

        .progress-container {
            margin: 20px 0;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            animation: progress 2s ease-in-out infinite;
        }

        @keyframes progress {
            0% {
                width: 0%;
            }

            50% {
                width: 70%;
            }

            100% {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-cog"></i>
        </div>
        <h1 class="maintenance-title">Sedang Dalam Pemeliharaan</h1>
        <p class="maintenance-message">
            Sistem sedang dalam proses pemeliharaan untuk meningkatkan layanan.
            <br>Mohon coba lagi dalam beberapa menit.
        </p>
        <div class="progress-container">
            <div class="progress">
                <div class="progress-bar" role="progressbar"></div>
            </div>
        </div>
        <a href="javascript:location.reload()" class="btn-retry">
            <i class="fas fa-redo-alt mr-2"></i> Cek Status
        </a>
    </div>

    <script>
        // Auto refresh every 60 seconds
        setTimeout(() => location.reload(), 60000);
    </script>
</body>

</html>