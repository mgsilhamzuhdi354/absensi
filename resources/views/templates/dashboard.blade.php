<!DOCTYPE html>
<html lang="en">
  <head>
    @php
        $settings = App\Models\settings::first();
    @endphp
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author" content="pixelstrap">
    <link rel="shortcut icon" href="{{ url('/storage/'.$settings->logo) }}" />
    <link rel="apple-touch-icon-precomposed" href="{{ url('/storage/'.$settings->logo) }}" />
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/font-awesome.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/icofont.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/themify.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/flag-icon.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/feather-icon.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/scrollbar.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/animate.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/chartist.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/slick.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/slick-theme.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/prism.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/style.css') }}">
    <link id="color" rel="stylesheet" href="{{ url('/html/assets/css/color-1.css" media="screen') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/responsive.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/calendar.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/datatables.css') }}">
    <link rel="stylesheet" href="{{ url('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/html/assets/css/vendors/select2.css') }}">
    <link rel="stylesheet" href="{{ url('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ url('https://unpkg.com/leaflet@1.8.0/dist/leaflet.css') }}" integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ==" crossorigin=""/>
    <script src="{{ url('https://unpkg.com/leaflet@1.8.0/dist/leaflet.js') }}" integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ==" crossorigin=""></script>
    <link rel="stylesheet" type="text/css" href="{{ url('clock/dist/bootstrap-clockpicker.min.css') }}">
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" type="text/css" href="{{ url('/css/animations.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/css/themes.css') }}">
    <style>
        /* Dynamic Theme Colors from Settings */
        :root {
            --primary-color: {{ $settings->theme_color ?? '#4361ee' }};
            --primary-hover: {{ $settings->theme_color ?? '#4361ee' }}dd;
            --primary-light: {{ $settings->theme_color ?? '#4361ee' }}20;
        }
        
        .btn-grey {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        .btn-grey:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        .btn {
            border-radius: 10px
        }

        .borderi {
            border-color:rgb(201, 201, 201)
        }

        .select2-container--default .select2-selection--single {
            border-color: rgb(201, 201, 201) !important;
        }

        trix-toolbar [data-trix-button-group="file-tools"] {
            display: none;
        }
        
        /* Theme-aware sidebar */
        .sidebar-wrapper {
            background: var(--primary-color) !important;
        }
        
        .sidebar-wrapper .sidebar-main-title h6 {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        /* Theme-aware buttons */
        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover) !important;
            border-color: var(--primary-hover) !important;
        }
        
        /* Theme-aware badges */
        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        /* Card hover animation */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
    </style>


    @stack('style')
  </head>
  <body data-theme-mode="{{ $settings->theme_mode ?? 'light' }}">
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>
    <div class="loader-wrapper">
      <div class="loader"></div>
    </div>
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
      <div class="page-header">
        <div class="header-wrapper row m-0">
          <form class="form-inline search-full col" action="#" method="get">
            <div class="form-group w-100">
              <div class="Typeahead Typeahead--twitterUsers">
                <div class="u-posRelative">
                  <input class="demo-input Typeahead-input form-control-plaintext w-100" type="text" placeholder="Search In Enzo .." name="q" title="" autofocus>
                  <div class="spinner-border Typeahead-spinner" role="status"><span class="sr-only">Loading...</span></div><i class="close-search" data-feather="x"></i>
                </div>
                <div class="Typeahead-menu"></div>
              </div>
            </div>
          </form>
          <div class="header-logo-wrapper col-auto p-0">
            <div class="logo-wrapper"><a href="{{ url('/dashboard') }}"><img class="img-fluid" src="{{ url('/html/assets/images/logo/logo-icon.png') }}" alt=""></a></div>
            <div class="toggle-sidebar"><i class="status_toggle middle sidebar-toggle" data-feather="align-center"></i></div>
          </div>

          <div class="left-header col horizontal-wrapper ps-0">
            @if(auth()->user()->is_admin == 'admin')
              <a href="{{ url('/switch/user') }}" class="btn btn-warning" onclick="return confirm('Are You Sure ?')">Dashboard User</a>
            @endif
          </div>

          <div class="nav-right col-8 pull-right right-header p-0">
            <ul class="nav-menus">
              <li>
                <a class="notification-box" href="{{ url('/notifications') }}"><i class="fa fa-bell"></i>
                  @if (auth()->user()->notifications()->whereNull('read_at')->count() > 0)
                    <span class="badge rounded-pill badge-danger">{{ auth()->user()->notifications()->whereNull('read_at')->count() }}</span>
                  @endif
                </a>
              </li>
              <li class="profile-nav onhover-dropdown p-0 me-0">
                <div class="d-flex profile-media">
                  @if (auth()->user()->foto_karyawan)
                    <img class="b-r-50" src="{{ url('/storage/'.auth()->user()->foto_karyawan) }}" alt="" style="width: 50px">
                  @else
                    <img class="b-r-50" src="{{ url('/html/assets/images/dashboard/profile.jpg') }}" alt="">
                  @endif
                  <div class="flex-grow-1"><span>{{ auth()->user()->name }}</span>
                    <p class="mb-0 font-roboto">{{ auth()->user()->Jabatan->nama_jabatan }} <i class="middle fa fa-angle-down"></i></p>
                  </div>
                </div>
                <ul class="profile-dropdown onhover-show-div">
                  <li><a href="{{ url('/my-profile') }}"><i data-feather="user"></i><span>Account </span></a></li>
                  <li><a href="{{ url('/my-profile/edit-password') }}"><i data-feather="file-text"></i><span>Change Password</span></a></li>
                  <li><a href="javascript:void(0)" onclick="confirmLogout()"><i data-feather="log-out"> </i><span>Log Out</span></a></li>
                </ul>
              </li>
            </ul>
          </div>
          <script class="result-template" type="text/x-handlebars-template">
            <div class="ProfileCard u-cf">
            <div class="ProfileCard-avatar"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-airplay m-0"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path><polygon points="12 15 17 21 7 21 12 15"></polygon></svg></div>
            <div class="ProfileCard-details">
            <div class="ProfileCard-realName"></div>
            </div>
            </div>
          </script>
          <script class="empty-template" type="text/x-handlebars-template"><div class="EmptyMessage">Your search turned up 0 results. This most likely means the backend is down, yikes!</div></script>
        </div>
      </div>
      <div class="page-body-wrapper">
        <div class="sidebar-wrapper">
          <div>
            <div class="logo-wrapper" style="display: flex; align-items: center; padding: 15px 10px;">
              <a href="{{ url('/dashboard') }}" style="flex-shrink: 0;"><img class="img-fluid for-light" src="{{ asset('/storage/'.$settings->logo) }}" style="width: 40px; height: 40px; border-radius: 50%;" alt=""></a>
              <span style="font-size: 13px; color: white; font-weight: 600; margin-left: 8px; flex: 1; max-width: 140px; line-height: 1.2;">{{ $settings->name }}</span>
              <div class="toggle-sidebar" style="flex-shrink: 0; margin-left: auto;"><i class="fa fa-cog status_toggle middle sidebar-toggle"> </i></div>
            </div>
            <div class="logo-icon-wrapper"><a href="{{ url('/dashboard') }}"><img class="img-fluid" src="{{ url('/html/assets/images/logo/logo-icon1.png') }}" alt=""></a></div>
            <nav class="sidebar-main">
              <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
              <div id="sidebar-menu">
                <ul class="sidebar-links" id="simple-bar">
                  <li class="back-btn"><a href="{{ url('/dashboard') }}"><img class="img-fluid" src="{{ url('/html/assets/images/logo/logo-icon.png') }}" alt=""></a>
                    <div class="mobile-back text-end"><span>Back</span><i class="fa fa-angle-right ps-2" aria-hidden="true"></i></div>
                  </li>
                  <li class="sidebar-main-title">
                    <h6 class="lan-1">General </h6>
                  </li>
                  <li class="menu-box">
                    <ul>

                      <li class="sidebar-list">
                        <a class="sidebar-link sidebar-title link-nav" href="{{ url('/dashboard') }}"><i data-feather="home"> </i><span>Dashboard</span></a>
                      </li>

                      <li class="sidebar-list">
                        <a class="sidebar-link sidebar-title link-nav" href="{{ url('/notifications') }}"><i data-feather="bell"></i>
                          <span>Notifications</span>
                          @if (auth()->user()->notifications()->whereNull('read_at')->count() > 0)
                            <span class="badge rounded-pill badge-danger">{{ auth()->user()->notifications()->whereNull('read_at')->count() }}</span>
                          @endif
                        </a>
                      </li>

                      <li class="sidebar-list">
                        <a class="sidebar-link sidebar-title link-nav" href="{{ url('/my-profile') }}"><i data-feather="user-check"> </i><span>My Profile</span></a>
                      </li>

                      @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('hrd') || auth()->user()->hasRole('general_manager'))
                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/pegawai') }}"><i data-feather="users"> </i><span>Pegawai</span></a>
                        </li>
                      @endif

                      @if (auth()->user()->hasRole('admin'))
                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/role') }}"><i data-feather="airplay"> </i><span>Role</span></a>
                        </li>
                      @endif

                      @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('hrd') || auth()->user()->hasRole('general_manager'))
                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/kontrak') }}"><i data-feather="trending-up"> </i><span>Kontrak</span></a>
                        </li>

                        <li class="sidebar-list">
                          <a class="sidebar-link sidebar-title link-nav" href="{{ url('/exit') }}"><i data-feather="user-minus"> </i><span>Pegawai Keluar</span></a>
                        </li>

                        <li class="sidebar-list">
                          <a class="sidebar-link sidebar-title link-nav" href="{{ url('/shift') }}"><i data-feather="git-pull-request"> </i><span>Shift</span></a>
                        </li>



                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/jabatan') }}"><i data-feather="package"> </i><span>Divisi</span></a>
                        </li>
                      @endif

                      @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('kepala_cabang') || auth()->user()->hasRole('hrd') || auth()->user()->hasRole('general_manager'))
                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/lokasi-kantor') }}"><i data-feather="map-pin"> </i><span>Lokasi</span></a>
                        </li>
                      @endif

                      @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('general_manager') || auth()->user()->hasRole('finance'))
                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/rekap-data') }}"><i data-feather="credit-card"> </i><span>Rekap Data</span></a>
                        </li>
                      @endif

                      @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('general_manager') || auth()->user()->hasRole('hrd'))
                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/data-cuti') }}"><i data-feather="shuffle"> </i><span>Cuti</span></a>
                        </li>
                      @endif

                      <li class="sidebar-list"><a class="sidebar-link sidebar-title" href="javascript:void(0)"><i data-feather="clock"></i><span>Absensi</span></a>
                          <ul class="sidebar-submenu">
                          <li><a href="{{ url('/absen') }}">Absen</a></li>
                          <li><a href="{{ url('/data-absen') }}">Data Absen</a></li>
                          <li><a href="{{ url('/dinas-luar') }}">Absen Dinas Luar</a></li>
                          <li><a href="{{ url('/data-dinas-luar') }}">Data Dinas Luar</a></li>
                          </ul>
                      </li>

                      <li class="sidebar-list"><a class="sidebar-link sidebar-title" href="javascript:void(0)"><i data-feather="film"></i><span>Overtime</span></a>
                          <ul class="sidebar-submenu">
                          <li><a href="{{ url('/lembur') }}">Lembur</a></li>
                          <li><a href="{{ url('/data-lembur') }}">Data Lembur</a></li>
                          </ul>
                      </li>

                      @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('hrd') || auth()->user()->hasRole('general_manager'))

                        <li class="sidebar-list"><a class="sidebar-link sidebar-title" href="javascript:void(0)"><i data-feather="map"></i><span>Patroli</span></a>
                            <ul class="sidebar-submenu">
                            <li><a href="{{ url('/patroli') }}">Patroli</a></li>
                            <li><a href="{{ url('/data-patroli') }}">Data Patroli</a></li>
                            </ul>
                        </li>

                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/kunjungan') }}"><i data-feather="navigation"> </i><span>Kunjungan</span></a>
                        </li>

                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/penugasan') }}"><i data-feather="award"> </i><span>Penugasan</span></a>
                        </li>

                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav" href="{{ url('/rapat') }}"><i data-feather="monitor"> </i><span>Rapat</span></a>
                        </li>

                        <li class="sidebar-list"><a class="sidebar-link sidebar-title" href="javascript:void(0)"><i data-feather="wind"></i><span>Kinerja Pegawai</span></a>
                            <ul class="sidebar-submenu">
                              <li><a href="{{ url('/jenis-kinerja') }}">Jenis Kinerja</a></li>
                              <li><a href="{{ url('/laporan-kinerja') }}">Laporan Kinerja</a></li>
                              <li><a href="{{ url('/kinerja-pegawai') }}">Kinerja Pegawai</a></li>
                            </ul>
                        </li>

                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav {{ Request::is('laporan-kerja*') ? 'active' : '' }}" href="{{ url('/laporan-kerja') }}"><i data-feather="message-square"> </i><span>Laporan Kerja</span></a>
                        </li>
                      @endif

                      <li class="sidebar-list">
                        <a class="sidebar-link sidebar-title link-nav" href="{{ url('/inventory') }}"><i data-feather="git-merge"> </i><span>Inventory</span></a>
                      </li>

                      @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('general_manager') || auth()->user()->hasRole('finance') || auth()->user()->hasRole('regional_manager'))
                        <li class="sidebar-list"><a class="sidebar-link sidebar-title" href="javascript:void(0)"><i data-feather="dollar-sign"></i><span>Keuangan</span></a>
                            <ul class="sidebar-submenu">
                            <li><a href="{{ url('/payroll') }}">Payroll</a></li>
                            <li><a href="{{ url('/kasbon') }}">Kasbon</a></li>
                            <li><a href="{{ url('/reimbursement') }}">Reimbursement</a></li>
                            <li><a href="{{ url('/kategori') }}">Kategori Reimbursement</a></li>
                            <li><a href="{{ url('/list-pengajuan-keuangan') }}">Pengajuan Keuangan</a></li>
                            </ul>
                        </li>

                        <li class="sidebar-list"><a class="sidebar-link sidebar-title" href="javascript:void(0)"><i data-feather="sunrise"></i><span>Target</span></a>
                          <ul class="sidebar-submenu">
                            <li><a href="{{ url('/target-kinerja') }}">Target Kinerja</a></li>
                            <li><a href="{{ url('/detail-target-kinerja') }}">Detail Target</a></li>
                          </ul>
                        </li>
                      @endif


                      <li class="sidebar-list">
                        <a class="sidebar-link sidebar-title link-nav {{ Request::is('dokumen*') ? 'active' : '' }}" href="{{ url('/dokumen') }}"><i data-feather="folder"> </i><span>Dokumen Pegawai</span></a>
                      </li>

                      @if (auth()->user()->hasRole('admin'))
                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav {{ Request::is('berita*') ? 'active' : '' }}" href="{{ url('/berita') }}"><i data-feather="star"> </i><span>Berita & Informasi</span></a>
                        </li>

                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav {{ Request::is('settings*') ? 'active' : '' }}" href="{{ url('/settings') }}"><i data-feather="settings"> </i><span>Settings</span></a>
                        </li>

                        <li class="sidebar-list">
                            <a class="sidebar-link sidebar-title link-nav {{ Request::is('backup*') ? 'active' : '' }}" href="{{ url('/backup') }}"><i data-feather="database"> </i><span>Backup & Restore</span></a>
                        </li>
                      @endif

                    </ul>
                  </li>

                </ul>
              </div>
              <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
            </nav>
          </div>
        </div>
        <div class="page-body">
          <div class="container-fluid default-dash">
            @yield('isi')
          </div>
        </div>
        <footer class="footer">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-6 p-0 footer-left">
                <p class="mb-0">
                    Copyright © <?php echo date("Y"); ?> 
                    PT Indoocean Crew Service. All rights reserved.
                </p>
            </div>            
            </div>
          </div>
        </footer>
      </div>
    </div>
    <script src="{{ url('/html/assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/icons/feather-icon/feather.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/icons/feather-icon/feather-icon.js') }}"></script>
    <script src="{{ url('/html/assets/js/scrollbar/simplebar.js') }}"></script>
    <script src="{{ url('/html/assets/js/scrollbar/custom.js') }}"></script>
    <script src="{{ url('/html/assets/js/config.js') }}"></script>
    <script src="{{ url('/html/assets/js/sidebar-menu.js') }}"></script>
    <script src="{{ url('/html/assets/js/chart/chartist/chartist.js') }}"></script>
    <script src="{{ url('/html/assets/js/chart/chartist/chartist-plugin-tooltip.js') }}"></script>
    <script src="{{ url('/html/assets/js/chart/knob/knob.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/chart/knob/knob-chart.js') }}"></script>
    <script src="{{ url('/html/assets/js/chart/apex-chart/apex-chart.js') }}"></script>
    <script src="{{ url('/html/assets/js/chart/apex-chart/stock-prices.js') }}"></script>
    <script src="{{ url('/html/assets/js/prism/prism.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/clipboard/clipboard.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/custom-card/custom-card.js') }}"></script>
    <script src="{{ url('/html/assets/js/notify/bootstrap-notify.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/dashboard/default.js') }}"></script>
    <script src="{{ url('/html/assets/js/notify/index.js') }}"></script>
    <script src="{{ url('/html/assets/js/slick-slider/slick.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/slick-slider/slick-theme.js') }}"></script>
    <script src="{{ url('/html/assets/js/typeahead/handlebars.js') }}"></script>
    <script src="{{ url('/html/assets/js/typeahead/typeahead.bundle.js') }}"></script>
    <script src="{{ url('/html/assets/js/typeahead/typeahead.custom.js') }}"></script>
    <script src="{{ url('/html/assets/js/typeahead-search/handlebars.js') }}"></script>
    <script src="{{ url('/html/assets/js/typeahead-search/typeahead-custom.js') }}"></script>
    <script src="{{ url('/html/assets/js/script.js') }}"></script>
    <script src="{{ url('/html/assets/js/theme-customizer/customizer.js') }}"></script>
    <script src="{{ url('/html/assets/js/calendar/fullcalendar.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/datatable/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ url('/html/assets/js/datatable/datatables/datatable.custom.js') }}"></script>
    <script src="{{ url('/html/assets/js/select2/select2.full.min.js') }}"></script>
    <script src="{{ url('https://cdn.jsdelivr.net/npm/flatpickr') }}"></script>
    <script src="{{ url('accounting.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/clock/dist/bootstrap-clockpicker.min.js') }}"></script>
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
      function getLocation() {
          // JANGAN update lokasi jika ada modal yang terbuka (untuk mencegah flickering)
          if (document.querySelector('.modal.show') || document.querySelector('.swal2-container')) {
              return;
          }
          if (navigator.geolocation) {
              navigator.geolocation.getCurrentPosition(showPosition);
          } else {
              x.innerHTML = "Geolocation is not supported by this browser.";
          }
      }

      function showPosition(position) {
          $('#lat').val(position.coords.latitude);
          $('#lat2').val(position.coords.latitude);
          $('#long').val(position.coords.longitude);
          $('#long2').val(position.coords.longitude);
      }

      setInterval(getLocation, 1000);
    </script>
    <script>
      $(function(){
          $('form').on('submit', function(){
              $(':input[type="submit"]').prop('disabled', true);
          })
      })
      $(function () {
        $('.selectpicker').select2();
        $('#mytable').DataTable( {
            "responsive": true,
            "paging": false,
            "info": false,
            "scrollCollapse": true,
            "autoWidth": false,
            'searching': false
        });
      });
    </script>
    <script>
      config = {
          enableTime: true,
          noCalendar: true,
          dateFormat: "H:i",
          time_24hr: true,
      }

      flatpickr("input[type=datetime-local]", config)
      flatpickr("input[type=datetime]", {})
    </script>
    <script>
      // Animated Logout Function
      function confirmLogout() {
          Swal.fire({
              title: '<span style="color: #1e293b">Keluar dari Sistem?</span>',
              html: `
                  <div style="padding: 20px;">
                      <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: pulseIcon 1.5s ease-in-out infinite;">
                          <i class="fa fa-sign-out-alt" style="font-size: 35px; color: white;"></i>
                      </div>
                      <p style="color: #64748b; font-size: 15px; margin: 0;">Sesi Anda akan berakhir dan Anda perlu login kembali.</p>
                  </div>
                  <style>
                      @keyframes pulseIcon {
                          0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
                          50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
                      }
                  </style>
              `,
              showCancelButton: true,
              confirmButtonText: '<i class="fa fa-sign-out-alt me-2"></i> Ya, Logout',
              cancelButtonText: '<i class="fa fa-times me-2"></i> Batal',
              confirmButtonColor: '#ef4444',
              cancelButtonColor: '#64748b',
              reverseButtons: true,
              showClass: {
                  popup: 'animate__animated animate__fadeInDown animate__faster'
              },
              hideClass: {
                  popup: 'animate__animated animate__fadeOutUp animate__faster'
              },
              customClass: {
                  popup: 'logout-popup',
                  confirmButton: 'logout-confirm-btn',
                  cancelButton: 'logout-cancel-btn'
              }
          }).then((result) => {
              if (result.isConfirmed) {
                  // Show loading animation
                  Swal.fire({
                      title: 'Logging Out...',
                      html: `
                          <div style="padding: 30px;">
                              <div class="logout-spinner" style="width: 60px; height: 60px; margin: 0 auto; border: 4px solid #e2e8f0; border-top: 4px solid #4361ee; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                              <p style="margin-top: 20px; color: #64748b;">Mengakhiri sesi...</p>
                          </div>
                          <style>
                              @keyframes spin {
                                  0% { transform: rotate(0deg); }
                                  100% { transform: rotate(360deg); }
                              }
                          </style>
                      `,
                      showConfirmButton: false,
                      allowOutsideClick: false,
                      allowEscapeKey: false
                  });
                  
                  // Redirect to logout after animation
                  setTimeout(function() {
                      window.location.href = '{{ url("/logout") }}';
                  }, 1000);
              }
          });
      }
    </script>
    @stack('script')
    @include('sweetalert::alert')
  </body>
</html>
