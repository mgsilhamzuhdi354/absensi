<!DOCTYPE html>
<html lang="en">

<head>
    @php
        $settings = App\Models\settings::first();
    @endphp
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <!-- Favicon and Touch Icons  -->
    <link rel="shortcut icon" href="{{ url('/storage/'.$settings->logo) }}" />
    <link rel="apple-touch-icon-precomposed" href="{{ url('/storage/'.$settings->logo) }}" />
    <!-- Font -->
    <link rel="stylesheet" href="{{ url('/myhr/fonts/fonts.css') }}" />
    <!-- Icons -->
    <link rel="stylesheet" href="{{ url('/myhr/fonts/icons-alipay.css') }}">
    <link rel="stylesheet" href="{{ url('/myhr/styles/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ url('/myhr/styles/swiper-bundle.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/myhr/styles/styles.css') }}" />
    <link rel="manifest" href="{{ url('/myhr/_manifest.json') }}" data-pwa-version="set_in_manifest_and_pwa_js">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ url('/myhr/app/icons/icon-192x192.png') }}">
    <link rel="stylesheet" href="{{ url('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/select2.css') }}">
    <link rel="stylesheet" href="{{ url('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ url('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ url('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ url('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ url('https://unpkg.com/leaflet@1.8.0/dist/leaflet.css') }}" integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ==" crossorigin=""/>
    <script src="{{ url('https://unpkg.com/leaflet@1.8.0/dist/leaflet.js') }}" integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ==" crossorigin=""></script>
    <link rel="stylesheet" type="text/css" href="{{ url('clock/dist/bootstrap-clockpicker.min.css') }}">
    <style>
        .select2-container .select2-selection--single {
            height: 45px;
            line-height: 45px;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 45px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 45px;
        }

        .select2-results__option {
            line-height: 45px;
        }

        .select2-selection__choice {
            line-height: 45px;
        }
        
        /* Fix sidebar menu links clickable */
        .tf-panel.panel-open .panel-box.panel-sidebar {
            z-index: 1400 !important;
        }
        
        .panel-sidebar .box-nav {
            position: relative;
            z-index: 10;
        }
        
        .panel-sidebar .box-nav li {
            position: relative;
            z-index: 10;
        }
        
        .panel-sidebar .box-nav li a.nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            position: relative;
            z-index: 10;
            cursor: pointer;
            pointer-events: auto !important;
        }
        
        .panel-sidebar .box-nav li a.nav-link:hover {
            background-color: rgba(83, 61, 234, 0.1);
            border-radius: 8px;
            padding-left: 8px;
            margin-left: -8px;
        }
        
        .panel-sidebar .box-nav li a.nav-link i {
            width: 20px;
            text-align: center;
        }
    </style>
    @stack('style')
</head>

<body>
     <div class="preload preload-container">
        <div class="preload-logo"></div>
    </div>

    @if (Request::is('dashboard*'))
        <div class="app-header">
            <div class="tf-container">
                <div class="tf-topbar d-flex justify-content-between align-items-center">
                    <a class="user-info d-flex justify-content-between align-items-center" href="{{ url('/my-profile') }}">
                        @if(auth()->user()->foto_karyawan == null)
                            <img src="{{ url('assets/img/foto_default.jpg') }}" alt="image">
                        @else
                            <img src="{{ url('/storage/'.auth()->user()->foto_karyawan) }}" alt="image">
                        @endif

                        <div class="content">
                            <h4 class="white_color">{{ auth()->user()->name }}</h4>
                            <p class="white_color fw_4">{{ auth()->user()->Jabatan->nama_jabatan }}</p>
                        </div>
                    </a>
                    <div class="d-flex align-items-center gap-4">
                        @if(auth()->user()->is_admin == 'admin' && session('dashboard_view') == 'user')
                          <a href="{{ url('/switch/admin') }}" class="btn btn-sm" style="background: #4361ee; color: white; border-radius: 20px; font-size: 12px; padding: 5px 12px;">
                            <i class="fas fa-cog me-1"></i>Admin
                          </a>
                        @endif
                        <a href="javascript:void(0);" id="btn-popup-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <path d="M7.25687 5.89462C8.06884 5.35208 9.02346 5.0625 10 5.0625C11.3095 5.0625 12.5654 5.5827 13.4913 6.50866C14.4173 7.43462 14.9375 8.6905 14.9375 10C14.9375 10.9765 14.6479 11.9312 14.1054 12.7431C13.5628 13.5551 12.7917 14.188 11.8895 14.5617C10.9873 14.9354 9.99452 15.0331 9.03674 14.8426C8.07896 14.6521 7.19918 14.1819 6.50866 13.4913C5.81814 12.8008 5.34789 11.921 5.15737 10.9633C4.96686 10.0055 5.06464 9.01271 5.43835 8.1105C5.81205 7.20829 6.44491 6.43716 7.25687 5.89462ZM8.29857 12.5464C8.80219 12.8829 9.3943 13.0625 10 13.0625C10.8122 13.0625 11.5912 12.7398 12.1655 12.1655C12.7398 11.5912 13.0625 10.8122 13.0625 10C13.0625 9.3943 12.8829 8.80219 12.5464 8.29857C12.2099 7.79494 11.7316 7.40241 11.172 7.17062C10.6124 6.93883 9.99661 6.87818 9.40254 6.99635C8.80847 7.11451 8.26279 7.40619 7.83449 7.83449C7.40619 8.26279 7.11451 8.80847 6.99635 9.40254C6.87818 9.99661 6.93883 10.6124 7.17062 11.172C7.40241 11.7316 7.79494 12.2099 8.29857 12.5464ZM24.7431 14.1054C23.9312 14.6479 22.9765 14.9375 22 14.9375C20.6905 14.9375 19.4346 14.4173 18.5087 13.4913C17.5827 12.5654 17.0625 11.3095 17.0625 10C17.0625 9.02346 17.3521 8.06884 17.8946 7.25687C18.4372 6.44491 19.2083 5.81205 20.1105 5.43835C21.0127 5.06464 22.0055 4.96686 22.9633 5.15737C23.921 5.34789 24.8008 5.81814 25.4913 6.50866C26.1819 7.19918 26.6521 8.07896 26.8426 9.03674C27.0331 9.99452 26.9354 10.9873 26.5617 11.8895C26.1879 12.7917 25.5551 13.5628 24.7431 14.1054ZM23.7014 7.45363C23.1978 7.11712 22.6057 6.9375 22 6.9375C21.1878 6.9375 20.4088 7.26016 19.8345 7.83449C19.2602 8.40882 18.9375 9.18778 18.9375 10C18.9375 10.6057 19.1171 11.1978 19.4536 11.7014C19.7901 12.2051 20.2684 12.5976 20.828 12.8294C21.3876 13.0612 22.0034 13.1218 22.5975 13.0037C23.1915 12.8855 23.7372 12.5938 24.1655 12.1655C24.5938 11.7372 24.8855 11.1915 25.0037 10.5975C25.1218 10.0034 25.0612 9.38763 24.8294 8.82803C24.5976 8.26844 24.2051 7.79014 23.7014 7.45363ZM7.25687 17.8946C8.06884 17.3521 9.02346 17.0625 10 17.0625C11.3095 17.0625 12.5654 17.5827 13.4913 18.5087C14.4173 19.4346 14.9375 20.6905 14.9375 22C14.9375 22.9765 14.6479 23.9312 14.1054 24.7431C13.5628 25.5551 12.7917 26.1879 11.8895 26.5617C10.9873 26.9354 9.99452 27.0331 9.03674 26.8426C8.07896 26.6521 7.19918 26.1819 6.50866 25.4913C5.81814 24.8008 5.34789 23.921 5.15737 22.9633C4.96686 22.0055 5.06464 21.0127 5.43835 20.1105C5.81205 19.2083 6.44491 18.4372 7.25687 17.8946ZM8.29857 24.5464C8.80219 24.8829 9.3943 25.0625 10 25.0625C10.8122 25.0625 11.5912 24.7398 12.1655 24.1655C12.7398 23.5912 13.0625 22.8122 13.0625 22C13.0625 21.3943 12.8829 20.8022 12.5464 20.2986C12.2099 19.7949 11.7316 19.4024 11.172 19.1706C10.6124 18.9388 9.99661 18.8782 9.40254 18.9963C8.80847 19.1145 8.26279 19.4062 7.83449 19.8345C7.40619 20.2628 7.11451 20.8085 6.99635 21.4025C6.87818 21.9966 6.93883 22.6124 7.17062 23.172C7.40241 23.7316 7.79494 24.2099 8.29857 24.5464ZM19.2569 17.8946C20.0688 17.3521 21.0235 17.0625 22 17.0625C23.3095 17.0625 24.5654 17.5827 25.4913 18.5087C26.4173 19.4346 26.9375 20.6905 26.9375 22C26.9375 22.9765 26.6479 23.9312 26.1054 24.7431C25.5628 25.5551 24.7917 26.1879 23.8895 26.5617C22.9873 26.9354 21.9945 27.0331 21.0367 26.8426C20.079 26.6521 19.1992 26.1819 18.5087 25.4913C17.8181 24.8008 17.3479 23.921 17.1574 22.9633C16.9669 22.0055 17.0646 21.0127 17.4383 20.1105C17.8121 19.2083 18.4449 18.4372 19.2569 17.8946ZM20.2986 24.5464C20.8022 24.8829 21.3943 25.0625 22 25.0625C22.8122 25.0625 23.5912 24.7398 24.1655 24.1655C24.7398 23.5912 25.0625 22.8122 25.0625 22C25.0625 21.3943 24.8829 20.8022 24.5464 20.2986C24.2099 19.7949 23.7316 19.4024 23.172 19.1706C22.6124 18.9388 21.9966 18.8782 21.4025 18.9963C20.8085 19.1145 20.2628 19.4062 19.8345 19.8345C19.4062 20.2628 19.1145 20.8085 18.9963 21.4025C18.8782 21.9966 18.9388 22.6124 19.1706 23.172C19.4024 23.7316 19.7949 24.2099 20.2986 24.5464Z" fill="white" stroke="white" stroke-width="0.125"/>
                            </svg>
                        </a>
                        <a href="{{ url('/notifications') }}" class="icon-notification1">
                            @if (auth()->user()->notifications()->whereNull('read_at')->count() > 0)
                                <span>{{ auth()->user()->notifications()->whereNull('read_at')->count() }}</span>
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @elseif(Request::is('my-profile*') || Request::is('laporan-kerja') || Request::is('laporan-kerja/show*') || Request::is('cuti*') || Request::is('pengajuan-keuangan*') || Request::is('berita-user*') || Request::is('informasi-user*'))
        <div class="header is-fixed">
            <div class="tf-container">
                <div class="tf-statusbar d-flex justify-content-center align-items-center">
                    <a href="#" class="back-btn"> <i class="icon-left"></i> </a>
                    <h3>{{ $title }}</h3>
                </div>
            </div>
        </div>
    @else
        <div class="app-header st1">
            <div class="tf-container">
                <div class="tf-topbar d-flex justify-content-center align-items-center">
                    <a href="#" class="back-btn">
                        <i class="icon-left white_color"></i>
                    </a>
                    <h3 class="white_color">{{ $title }}</h3>
                </div>
                <div class="tf-topbar d-flex justify-content-between align-items-center">
                    <a class="user-info d-flex justify-content-between align-items-center"></a>
                    <div class="d-flex align-items-center gap-4">
                        <a href="javascript:void(0);" id="btn-popup-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <path d="M7.25687 5.89462C8.06884 5.35208 9.02346 5.0625 10 5.0625C11.3095 5.0625 12.5654 5.5827 13.4913 6.50866C14.4173 7.43462 14.9375 8.6905 14.9375 10C14.9375 10.9765 14.6479 11.9312 14.1054 12.7431C13.5628 13.5551 12.7917 14.188 11.8895 14.5617C10.9873 14.9354 9.99452 15.0331 9.03674 14.8426C8.07896 14.6521 7.19918 14.1819 6.50866 13.4913C5.81814 12.8008 5.34789 11.921 5.15737 10.9633C4.96686 10.0055 5.06464 9.01271 5.43835 8.1105C5.81205 7.20829 6.44491 6.43716 7.25687 5.89462ZM8.29857 12.5464C8.80219 12.8829 9.3943 13.0625 10 13.0625C10.8122 13.0625 11.5912 12.7398 12.1655 12.1655C12.7398 11.5912 13.0625 10.8122 13.0625 10C13.0625 9.3943 12.8829 8.80219 12.5464 8.29857C12.2099 7.79494 11.7316 7.40241 11.172 7.17062C10.6124 6.93883 9.99661 6.87818 9.40254 6.99635C8.80847 7.11451 8.26279 7.40619 7.83449 7.83449C7.40619 8.26279 7.11451 8.80847 6.99635 9.40254C6.87818 9.99661 6.93883 10.6124 7.17062 11.172C7.40241 11.7316 7.79494 12.2099 8.29857 12.5464ZM24.7431 14.1054C23.9312 14.6479 22.9765 14.9375 22 14.9375C20.6905 14.9375 19.4346 14.4173 18.5087 13.4913C17.5827 12.5654 17.0625 11.3095 17.0625 10C17.0625 9.02346 17.3521 8.06884 17.8946 7.25687C18.4372 6.44491 19.2083 5.81205 20.1105 5.43835C21.0127 5.06464 22.0055 4.96686 22.9633 5.15737C23.921 5.34789 24.8008 5.81814 25.4913 6.50866C26.1819 7.19918 26.6521 8.07896 26.8426 9.03674C27.0331 9.99452 26.9354 10.9873 26.5617 11.8895C26.1879 12.7917 25.5551 13.5628 24.7431 14.1054ZM23.7014 7.45363C23.1978 7.11712 22.6057 6.9375 22 6.9375C21.1878 6.9375 20.4088 7.26016 19.8345 7.83449C19.2602 8.40882 18.9375 9.18778 18.9375 10C18.9375 10.6057 19.1171 11.1978 19.4536 11.7014C19.7901 12.2051 20.2684 12.5976 20.828 12.8294C21.3876 13.0612 22.0034 13.1218 22.5975 13.0037C23.1915 12.8855 23.7372 12.5938 24.1655 12.1655C24.5938 11.7372 24.8855 11.1915 25.0037 10.5975C25.1218 10.0034 25.0612 9.38763 24.8294 8.82803C24.5976 8.26844 24.2051 7.79014 23.7014 7.45363ZM7.25687 17.8946C8.06884 17.3521 9.02346 17.0625 10 17.0625C11.3095 17.0625 12.5654 17.5827 13.4913 18.5087C14.4173 19.4346 14.9375 20.6905 14.9375 22C14.9375 22.9765 14.6479 23.9312 14.1054 24.7431C13.5628 25.5551 12.7917 26.1879 11.8895 26.5617C10.9873 26.9354 9.99452 27.0331 9.03674 26.8426C8.07896 26.6521 7.19918 26.1819 6.50866 25.4913C5.81814 24.8008 5.34789 23.921 5.15737 22.9633C4.96686 22.0055 5.06464 21.0127 5.43835 20.1105C5.81205 19.2083 6.44491 18.4372 7.25687 17.8946ZM8.29857 24.5464C8.80219 24.8829 9.3943 25.0625 10 25.0625C10.8122 25.0625 11.5912 24.7398 12.1655 24.1655C12.7398 23.5912 13.0625 22.8122 13.0625 22C13.0625 21.3943 12.8829 20.8022 12.5464 20.2986C12.2099 19.7949 11.7316 19.4024 11.172 19.1706C10.6124 18.9388 9.99661 18.8782 9.40254 18.9963C8.80847 19.1145 8.26279 19.4062 7.83449 19.8345C7.40619 20.2628 7.11451 20.8085 6.99635 21.4025C6.87818 21.9966 6.93883 22.6124 7.17062 23.172C7.40241 23.7316 7.79494 24.2099 8.29857 24.5464ZM19.2569 17.8946C20.0688 17.3521 21.0235 17.0625 22 17.0625C23.3095 17.0625 24.5654 17.5827 25.4913 18.5087C26.4173 19.4346 26.9375 20.6905 26.9375 22C26.9375 22.9765 26.6479 23.9312 26.1054 24.7431C25.5628 25.5551 24.7917 26.1879 23.8895 26.5617C22.9873 26.9354 21.9945 27.0331 21.0367 26.8426C20.079 26.6521 19.1992 26.1819 18.5087 25.4913C17.8181 24.8008 17.3479 23.921 17.1574 22.9633C16.9669 22.0055 17.0646 21.0127 17.4383 20.1105C17.8121 19.2083 18.4449 18.4372 19.2569 17.8946ZM20.2986 24.5464C20.8022 24.8829 21.3943 25.0625 22 25.0625C22.8122 25.0625 23.5912 24.7398 24.1655 24.1655C24.7398 23.5912 25.0625 22.8122 25.0625 22C25.0625 21.3943 24.8829 20.8022 24.5464 20.2986C24.2099 19.7949 23.7316 19.4024 23.172 19.1706C22.6124 18.9388 21.9966 18.8782 21.4025 18.9963C20.8085 19.1145 20.2628 19.4062 19.8345 19.8345C19.4062 20.2628 19.1145 20.8085 18.9963 21.4025C18.8782 21.9966 18.9388 22.6124 19.1706 23.172C19.4024 23.7316 19.7949 24.2099 20.2986 24.5464Z" fill="white" stroke="white" stroke-width="0.125"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @yield('container')

    <div class="bottom-navigation-bar">
        <div class="tf-container">
            <ul class="tf-navigation-bar">
                <li class="{{ Request::is('dashboard*') ? 'active' : '' }}"><a class="fw_6 d-flex justify-content-center align-items-center flex-column"
                        href="{{ url('/dashboard') }}"><i class="{{ Request::is('dashboard*') ? 'icon-home2' : 'icon-home' }}"></i> Home</a> </li>
                <li class="{{ Request::is('my-absen*') ? 'active' : '' }}"><a class="fw_4 d-flex justify-content-center align-items-center flex-column" href="{{ url('/my-absen') }}"><i
                            class="icon-history"></i> History</a> </li>
                <li><a class="fw_4 d-flex justify-content-center align-items-center flex-column" href="{{ url('/absen') }}"><i
                            class="icon-scan-qr-code"></i> </a> </li>
                <li class="{{ Request::is('my-dinas-luar*') ? 'active' : '' }}"><a class="fw_4 d-flex justify-content-center align-items-center flex-column"
                        href="{{ url('/my-dinas-luar') }}"><svg width="25" height="24" viewBox="0 0 25 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12.25" cy="12" r="9.5" stroke="{{ Request::is('my-dinas-luar*') ? '#0000FF' : '#717171' }}" />
                            <path
                                d="M17.033 11.5318C17.2298 11.3316 17.2993 11.0377 17.2144 10.7646C17.1293 10.4914 16.9076 10.2964 16.6353 10.255L14.214 9.88781C14.1109 9.87213 14.0218 9.80462 13.9758 9.70702L12.8933 7.41717C12.7717 7.15989 12.525 7 12.2501 7C11.9754 7 11.7287 7.15989 11.6071 7.41717L10.5244 9.70723C10.4784 9.80483 10.3891 9.87234 10.286 9.88802L7.86469 10.2552C7.59257 10.2964 7.3707 10.4916 7.2856 10.7648C7.2007 11.038 7.27018 11.3318 7.46702 11.532L9.2189 13.3144C9.29359 13.3905 9.32783 13.5 9.31021 13.607L8.89692 16.1239C8.86027 16.3454 8.91594 16.5609 9.0533 16.7308C9.26676 16.9956 9.6394 17.0763 9.93735 16.9128L12.1027 15.7244C12.1932 15.6749 12.3072 15.6753 12.3975 15.7244L14.563 16.9128C14.6684 16.9707 14.7807 17 14.8966 17C15.1083 17 15.3089 16.9018 15.4469 16.7308C15.5845 16.5609 15.6399 16.345 15.6033 16.1239L15.1898 13.607C15.1722 13.4998 15.2064 13.3905 15.2811 13.3144L17.033 11.5318Z"
                                stroke="{{ Request::is('my-dinas-luar*') ? '#0000FF' : '#717171' }}" stroke-width="1.25" />
                        </svg>
                        Dinas</a> </li>
                <li class="{{ Request::is('my-profile*') ? 'active' : '' }}"><a class="fw_4 d-flex justify-content-center align-items-center flex-column" href="{{ url('/my-profile') }}"><i
                            class="icon-user-outline"></i> Profile</a> </li>
            </ul>
        </div>
    </div>

    @php
        $settings = App\Models\settings::first();
    @endphp

    <div class="tf-panel left">
        <div class="panel_overlay"></div>
        <div class="panel-box panel-left panel-sidebar">
            <div class="header-sidebar bg_white_color is-fixed">
                <div class="tf-container">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ url('/') }}" class="sidebar-logo">
                            <img src="{{ asset('/storage/'.$settings->logo) }}" alt="logo">
                            <h5>Absensi</h5>
                        </a>
                        <a href="javascript:void(0);" class="clear-panel"> <i class="icon-close1"></i> </a>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="tf-container">
                    <div class="box-content">

                        <ul class="box-nav">
                            <li class="nav-title">MENU</li>
                            <li>
                                <a href="{{ url('/dashboard') }}" class="nav-link" >
                                    <i class="fas fa-home" style="{{ Request::is('dashboard*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('dashboard*') ? 'color: blue' : '' }}">Home</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/my-profile') }}" class="nav-link">
                                    <i class="fas fa-user" style="{{ Request::is('my-profile*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('my-profile*') ? 'color: blue' : '' }}">My Profile</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/pegawai') }}" class="nav-link">
                                    <i class="fas fa-users" style="{{ Request::is('pegawai*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('pegawai*') ? 'color: blue' : '' }}">Pegawai</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/payroll') }}" class="nav-link">
                                    <i class="fa fa-file-invoice-dollar" style="{{ Request::is('payroll*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('payroll*') ? 'color: blue' : '' }}">Payroll</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/reimbursement') }}" class="nav-link">
                                    <i class="fa fa-thumbtack" style="{{ Request::is('reimbursement*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('reimbursement*') ? 'color: blue' : '' }}">Reimbursement</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/kunjungan') }}" class="nav-link">
                                    <i class="fa fa-plane-arrival" style="{{ Request::is('kunjungan*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('kunjungan*') ? 'color: blue' : '' }}">Kunjungan</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/kinerja-pegawai-user') }}" class="nav-link">
                                    <i class="fa fa-x-ray" style="{{ Request::is('kinerja-pegawai-user*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('kinerja-pegawai-user*') ? 'color: blue' : '' }}">Kinerja Pegawai</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/penugasan-kerja') }}" class="nav-link">
                                    <i class="fas fa-wave-square" style="{{ Request::is('penugasan-kerja*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('penugasan-kerja*') ? 'color: blue' : '' }}">Penugasan Kerja</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/rapat-kerja') }}" class="nav-link">
                                    <i class="fas fa-vihara" style="{{ Request::is('rapat-kerja*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('rapat-kerja*') ? 'color: blue' : '' }}">Rapat Kerja</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/inventory') }}" class="nav-link">
                                    <i class="fas fa-warehouse" style="{{ Request::is('inventory*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('inventory*') ? 'color: blue' : '' }}">Inventory</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/exit') }}" class="nav-link">
                                    <i class="fas fa-user-minus" style="{{ Request::is('exit*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('exit*') ? 'color: blue' : '' }}">Pegawai Keluar</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/patroli') }}" class="nav-link">
                                    <i class="fas fa-shield-alt" style="{{ Request::is('patroli*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('patroli*') ? 'color: blue' : '' }}">Patroli</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/data-patroli') }}" class="nav-link">
                                    <i class="fas fa-oil-can" style="{{ Request::is('data-patroli*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('data-patroli*') ? 'color: blue' : '' }}">Data Patroli</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/my-dokumen') }}" class="nav-link">
                                    <i class="fa fa-folder-open" style="{{ Request::is('my-dokumen*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('my-dokumen*') ? 'color: blue' : '' }}">Dokumen</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/kasbon') }}" class="nav-link">
                                    <i class="fa fa-comments-dollar" style="{{ Request::is('kasbon*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('kasbon*') ? 'color: blue' : '' }}">Kasbon</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/cuti') }}" class="nav-link">
                                    <i class="fa fa-hourglass-half" style="{{ Request::is('cuti*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('cuti*') ? 'color: blue' : '' }}">Cuti / Izin</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/absen') }}" class="nav-link">
                                    <i class="fa fa-camera" style="{{ Request::is('absen*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('absen*') ? 'color: blue' : '' }}">Absensi</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/my-absen') }}" class="nav-link">
                                    <i class="fa fa-table" style="{{ Request::is('my-absen*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('my-absen*') ? 'color: blue' : '' }}">My Absen</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/dinas-luar') }}" class="nav-link">
                                    <i class="fa fa-stopwatch" style="{{ Request::is('dinas-luar*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('dinas-luar*') ? 'color: blue' : '' }}">Dinas Luar</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/my-dinas-luar') }}" class="nav-link">
                                    <i class="fa fa-user-secret" style="{{ Request::is('my-dinas-luar*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('my-dinas-luar*') ? 'color: blue' : '' }}">My Dinas Luar</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/lembur') }}" class="nav-link">
                                    <i class="fa fa-user-clock" style="{{ Request::is('lembur*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('lembur*') ? 'color: blue' : '' }}">Lembur</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/my-lembur') }}" class="nav-link">
                                    <i class="fa fa-business-time" style="{{ Request::is('my-lembur*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('my-lembur*') ? 'color: blue' : '' }}">My Lembur</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/request-location') }}" class="nav-link">
                                    <i class="fa fa-holly-berry" style="{{ Request::is('request-location*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('request-location*') ? 'color: blue' : '' }}">Request Location</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/euforia') }}" class="nav-link">
                                    <i class="fa fa-baby" style="{{ Request::is('euforia*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('euforia*') ? 'color: blue' : '' }}">Euforia</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/pengajuan-absensi') }}" class="nav-link">
                                    <i class="fas fa-envelope-open-text" style="{{ Request::is('pengajuan-absensi*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('pengajuan-absensi*') ? 'color: blue' : '' }}">Pengajuan Absensi</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/notifications') }}" class="nav-link">
                                    <i class="fas fa-bell" style="{{ Request::is('notifications*') ? 'color: blue' : 'color: black' }}"></i>
                                    <span style="{{ Request::is('notifications*') ? 'color: blue' : '' }}">Notifications</span>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" class="nav-link" onclick="confirmLogout()">
                                    <i class="fas fa-sign-out-alt" style="color: #ef4444"></i>
                                    <span style="color: #ef4444">Log Out</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <script type="text/javascript" src="{{ url('/myhr/javascript/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/bootstrap.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/swiper-bundle.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/swiper.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/main.js') }}"></script>
    <script src="{{ url('https://cdn.jsdelivr.net/npm/flatpickr') }}"></script>
    <script src="{{ url('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ url('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ url('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ url('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ url('adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ url('adminlte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ url('adminlte/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ url('adminlte/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ url('adminlte/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/clock/dist/bootstrap-clockpicker.min.js') }}"></script>
    <script src="{{ url('accounting.min.js') }}"></script>
    <script>
        // Config untuk time-only picker (untuk input jam saja)
        var timeConfig = {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
        }

        // Config untuk date-time picker (untuk input tanggal + jam)
        var dateTimeConfig = {
            enableTime: true,
            noCalendar: false,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
        }

        flatpickr("input[type=datetime-local]", dateTimeConfig)
        flatpickr("input[type=datetime]", dateTimeConfig)
        flatpickr("input[type=time]", timeConfig)

        $(function () {

            $('#tablePayroll').DataTable( {
                "responsive": true,
                "paging": false,
                "info": false,
                "scrollCollapse": true,
                "autoWidth": false,
                'searching': false
            });
             $("#tableprint").DataTable({
                "responsive": true, "autoWidth": false,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                dom: 'flrtip'
                // "buttons": ["excel", "pdf", "print"]
                // "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#tableprint_wrapper .col-md-6:eq(0)');


        });

    </script>
    <script src="{{ url('/html/assets/js/select2/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ url('/push/bin/push.js') }}"></script>
    <script src="{{ url('/js/app.js') }}"></script>
    <script>
        // Konfigurasi Push.js dengan scope dan serviceWorker spesifik
        Push.config({
            serviceWorker: '{{ url("/push/bin/serviceWorker.min.js") }}',
            scope: window.location.hostname // Hanya menerima notifikasi dari domain ini
        });
        
        // PUSHER DISABLED - Untuk mengaktifkan kembali:
        // 1. Isi PUSHER_APP_KEY, PUSHER_APP_ID, PUSHER_APP_SECRET di .env dengan credentials Anda sendiri
        // 2. Jalankan: npm run dev (di folder aplikasiabsensibygerry)
        // 3. Uncomment kode di bawah ini
        
        /*
        window.Echo.channel("messages").listen("NotifApproval", (event) => {
            var user_id = {{ auth()->user()->id }};
            if (event.user_id == user_id) {
                if (event.type == "Approved") {
                    Swal.fire({
                        icon: "success",
                        title: "Approved",
                        text: event.notif,
                        footer: "<a href=" + event.url + ">View Application</a>",
                    });
                } else if (event.type == "Approval" || event.type == "Info") {
                    Swal.fire({
                        icon: "info",
                        title: "",
                        text: event.notif,
                        footer: "<a href=" + event.url + ">View Application</a>",
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Rejected",
                        text: event.notif,
                        footer: "<a href=" + event.url + ">View Application</a>",
                    });
                }
                Push.create(event.notif);
            }
        });
        */

        // === POLLING NOTIFICATION SYSTEM (tanpa Pusher) ===
        // Memeriksa notifikasi baru setiap 10 detik
        var pageLoadTime = Math.floor(Date.now() / 1000); // Timestamp saat page load
        var lastNotifCount = {{ auth()->user()->notifications()->whereNull('read_at')->count() }};
        var shownNotifIds = [];

        function checkNewNotifications() {
            // JANGAN polling jika ada modal yang terbuka (untuk mencegah flickering)
            if (document.querySelector('.modal.show') || document.querySelector('.swal2-container')) {
                return; // Skip polling saat modal sedang terbuka
            }

            fetch('/notifications/check-new?since=' + pageLoadTime)
                .then(response => response.json())
                .then(data => {
                    // Cek lagi apakah modal sudah terbuka (setelah fetch selesai)
                    if (document.querySelector('.modal.show') || document.querySelector('.swal2-container')) {
                        return;
                    }

                    // Cek apakah ada notifikasi BARU (yang dibuat setelah page load)
                    if (data.new_count > 0) {
                        data.notifications.forEach(notif => {
                            if (!shownNotifIds.includes(notif.id)) {
                                shownNotifIds.push(notif.id);
                                
                                Swal.fire({
                                    title: '<span style="color: #1e293b; font-weight: 700;">🔔 Notifikasi Baru</span>',
                                    html: `
                                        <div style="padding: 15px;">
                                            <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: pulseNotif 1.5s ease-in-out infinite;">
                                                <i class="fas fa-bell" style="font-size: 35px; color: white;"></i>
                                            </div>
                                            <p style="font-size: 16px; color: #475569; margin-bottom: 10px; font-weight: 500;">${notif.from}</p>
                                            <p style="font-size: 14px; color: #64748b; line-height: 1.6;">${notif.message}</p>
                                            <p style="font-size: 12px; color: #94a3b8; margin-top: 10px;"><i class="fas fa-clock"></i> ${notif.created_at}</p>
                                        </div>
                                        <style>
                                            @keyframes pulseNotif { 
                                                0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.4); } 
                                                50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(67, 97, 238, 0); } 
                                            }
                                        </style>
                                    `,
                                    showCancelButton: true,
                                    confirmButtonText: '<i class="fas fa-eye me-2"></i> Lihat Detail',
                                    cancelButtonText: '<i class="fas fa-times me-2"></i> Tutup',
                                    confirmButtonColor: '#4361ee',
                                    cancelButtonColor: '#64748b',
                                    background: '#ffffff',
                                    backdrop: 'rgba(0,0,0,0.5)',
                                    showClass: {
                                        popup: 'animate__animated animate__bounceIn'
                                    },
                                    hideClass: {
                                        popup: 'animate__animated animate__fadeOutUp'
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = notif.action;
                                    }
                                });

                                // Browser push notification
                                if (typeof Push !== 'undefined') {
                                    Push.create('🔔 Notifikasi Baru', {
                                        body: notif.message,
                                        icon: '{{ url("/storage/".App\Models\settings::first()->logo ?? "") }}',
                                        timeout: 5000,
                                        onClick: function() {
                                            window.location.href = notif.action;
                                            this.close();
                                        }
                                    });
                                }
                            }
                        });
                    }
                    
                    // Update badge count jika ada perubahan
                    if (data.count !== lastNotifCount) {
                        lastNotifCount = data.count;
                        var badge = document.querySelector('.icon-notification1 span');
                        if (badge) {
                            badge.textContent = data.count;
                        } else if (data.count > 0) {
                            // Buat badge baru jika belum ada
                            var bellIcon = document.querySelector('.icon-notification1');
                            if (bellIcon) {
                                var newBadge = document.createElement('span');
                                newBadge.textContent = data.count;
                                bellIcon.appendChild(newBadge);
                            }
                        }
                    }
                })
                .catch(error => console.log('Notification check error:', error));
        }

        // Mulai polling setiap 10 detik
        setInterval(checkNewNotifications, 10000);
    </script>
    <script>
      // Animated Logout Function
      function confirmLogout() {
          Swal.fire({
              title: '<span style="color: #1e293b">Keluar dari Sistem?</span>',
              html: `
                  <div style="padding: 20px;">
                      <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: pulseIcon 1.5s ease-in-out infinite;">
                          <i class="fas fa-sign-out-alt" style="font-size: 35px; color: white;"></i>
                      </div>
                      <p style="color: #64748b; font-size: 15px; margin: 0;">Sesi Anda akan berakhir dan Anda perlu login kembali.</p>
                  </div>
                  <style>
                      @keyframes pulseIcon { 0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); } }
                  </style>
              `,
              showCancelButton: true,
              confirmButtonText: '<i class="fas fa-sign-out-alt me-2"></i> Ya, Logout',
              cancelButtonText: '<i class="fas fa-times me-2"></i> Batal',
              confirmButtonColor: '#ef4444',
              cancelButtonColor: '#64748b',
              reverseButtons: true
          }).then((result) => {
              if (result.isConfirmed) {
                  Swal.fire({
                      title: 'Logging Out...',
                      html: '<div style="padding: 30px;"><div style="width: 60px; height: 60px; margin: 0 auto; border: 4px solid #e2e8f0; border-top: 4px solid #4361ee; border-radius: 50%; animation: spin 0.8s linear infinite;"></div><p style="margin-top: 20px; color: #64748b;">Mengakhiri sesi...</p></div><style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>',
                      showConfirmButton: false,
                      allowOutsideClick: false
                  });
                  setTimeout(function() { window.location.href = '{{ url("/logout") }}'; }, 1000);
              }
          });
      }
    </script>
    @include('sweetalert::alert')
    @stack('script')


</body>

</html>
