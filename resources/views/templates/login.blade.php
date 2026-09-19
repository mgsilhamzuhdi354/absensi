
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    <!-- Favicon and Touch Icons  -->
    <link rel="shortcut icon" href="{{ url('/myhr/images/logo.png') }}" />
    <link rel="apple-touch-icon-precomposed" href="{{ url('/myhr/images/logo.png') }}" />
    <!-- Font -->
    <link rel="stylesheet" href="{{ url('/myhr/fonts/fonts.css') }}" />
    <!-- Icons -->
    <link rel="stylesheet" href="{{ url('/myhr/fonts/icons-alipay.css') }}">
    <link rel="stylesheet" href="{{ url('/myhr/styles/bootstrap.css') }}">
     <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ url('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/myhr/styles/styles.css') }}" />
    <link rel="manifest" href="{{ url('/manifest.json') }}" data-pwa-version="set_in_manifest_and_pwa_js">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ url('/myhr/app/icons/icon-192x192.png') }}">
    <style>
        html {
            min-height: 100%;
        }

        body.public-login {
            width: 100%;
            max-width: none;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .public-login .login-section,
        .public-login .login-content {
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            padding: 0;
        }

        .public-login .login-content {
            display: flex;
            justify-content: center;
            align-items: center;
            isolation: isolate;
        }

        .public-login .attendance-container {
            width: 100%;
        }

        .public-login .landing-bg,
        .public-login .login-bg {
            pointer-events: none;
        }

        .public-login a,
        .public-login button {
            touch-action: manipulation;
        }
    </style>
    @stack('style')
</head>

<body class="public-login">
    <main class="login-section">
        <div class="login-content">
            @yield('container')
        </div>
    </main>

    <script src="{{ url('adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{  url('/myhr/javascript/bootstrap.min.js') }}"></script>
    <script type="text/javascript" src="{{  url('/myhr/javascript/password-addon.js') }}"></script>
    <script type="text/javascript" src="{{  url('/myhr/javascript/main.js') }}"></script>
    @stack('script')
    @include('sweetalert::alert')

</body>

</html>
