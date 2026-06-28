<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    <link rel="shortcut icon" href="{{ url('/myhr/images/logo.png') }}" />
    <link rel="apple-touch-icon-precomposed" href="{{ url('/myhr/images/logo.png') }}" />
    <link rel="stylesheet" href="{{ url('/myhr/fonts/fonts.css') }}" />
    <link rel="stylesheet" href="{{ url('/myhr/fonts/icons-alipay.css') }}">
    <link rel="stylesheet" href="{{ url('/myhr/styles/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ url('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/myhr/styles/styles.css') }}" />
    <link rel="manifest" href="{{ url('/myhr/_manifest.json') }}" data-pwa-version="set_in_manifest_and_pwa_js">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ url('/myhr/app/icons/icon-192x192.png') }}">
    <style>
        .id-card-shell {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 6px 22px rgba(15, 23, 42, 0.1);
        }

        .id-photo {
            width: 86px;
            height: 86px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e5e7eb;
            margin-bottom: 10px;
        }

        .id-name {
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 3px;
            color: #172033;
        }

        .id-meta {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .qr-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 18px;
        }

        .qr-box {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 8px;
            background: #f8fafc;
        }

        .qr-box img {
            width: 140px;
            height: 140px;
            object-fit: contain;
        }

        .qr-box strong {
            display: block;
            font-size: 12px;
            color: #334155;
            margin-top: 6px;
        }

        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .actions .btn {
            border-radius: 12px;
        }
    </style>
</head>

<body class="bg_surface_color">
     <div class="preload preload-container">
        <div class="preload-logo">
          <div class="spinner"></div>
        </div>
      </div>

    <div class="header is-fixed">
        <div class="tf-container">
            <div class="tf-statusbar d-flex justify-content-center align-items-center">
                <a href="#" class="back-btn"> <i class="icon-left"></i> </a>
                <h3>{{ $title }}</h3>
            </div>
        </div>
    </div>
    <div id="app-wrap">
        <div class="bill-payment-content">
            <div class="tf-container">
                <div class="wrapper-bill">
                    <div class="archive-bottom">
                        <div class="id-card-shell">
                            @if($user->foto_karyawan)
                                <img class="id-photo" src="{{ asset('storage/'.$user->foto_karyawan) }}" alt="{{ $user->name }}">
                            @else
                                <img class="id-photo" src="{{ asset('assets/img/foto_default.jpg') }}" alt="{{ $user->name }}">
                            @endif

                            <h2 class="id-name">{{ $user->name }}</h2>
                            <p class="id-meta">{{ $user->employee_id ?? $user->username ?? '-' }}</p>
                            <p class="id-meta">{{ $user->Jabatan->nama_jabatan ?? '-' }}</p>

                            <div class="qr-tabs">
                                <div class="qr-box">
                                    <img src="{{ asset('storage/'.$user->employee_qr_profile_image) }}" alt="QR Profil">
                                    <strong>Profil</strong>
                                </div>
                                <div class="qr-box">
                                    <img src="{{ asset('storage/'.$user->employee_qr_vcard_image) }}" alt="QR Simpan Kontak">
                                    <strong>Kontak</strong>
                                </div>
                            </div>

                            <div class="actions">
                                <a href="{{ $user->employee_qr_profile_value }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-eye me-1"></i> Preview
                                </a>
                                <a href="{{ url('/pegawai/print/'.$user->id.'?mode=profile') }}" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fa fa-print me-1"></i> Cetak Profil
                                </a>
                                <a href="{{ url('/pegawai/print/'.$user->id.'?mode=vcard') }}" target="_blank" class="btn btn-secondary btn-sm">
                                    <i class="fa fa-address-card me-1"></i> Cetak Kontak
                                </a>
                            </div>
                        </div>
                    </div>
                 </div>
            </div>
         </div>
    </div>

    <script type="text/javascript" src="{{ url('/myhr/javascript/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/bootstrap.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/count-down.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/main.js') }}"></script>
    @include('sweetalert::alert')

</body>

</html>
