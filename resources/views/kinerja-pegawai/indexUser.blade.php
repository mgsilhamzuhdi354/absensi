
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    <!-- Favicon and Touch Icons  -->
    <link rel="shortcut icon" href="{{ url('/myhr/images/logo.png') }}" />
    <link rel="apple-touch-icon-precomposed" href="{{ url('/myhr/images/logo.png') }}" />
    <!-- Font -->
    <link rel="stylesheet" href="{{ url('/myhr/fonts/fonts.css') }}" />
    <!-- Icons -->
    <link rel="stylesheet" href="{{ url('/myhr/fonts/icons-alipay.css') }}">
    <link rel="stylesheet" href="{{ url('/myhr/styles/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ url('/myhr/styles/swiper-bundle.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/myhr/styles/styles.css') }}" />
    <link rel="manifest" href="{{ url('/myhr/_manifest.json') }}" data-pwa-version="set_in_manifest_and_pwa_js">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ url('/myhr/app/icons/icon-192x192.png') }}">
    <style>
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
        }
        .delete-btn:hover {
            background: #c82333;
        }
        .source-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 5px;
        }
        .source-absensi { background: #17a2b8; color: white; }
        .source-manual { background: #ffc107; color: black; }
        .source-penugasan { background: #28a745; color: white; }
        .source-other { background: #6c757d; color: white; }
        .detail-link {
            cursor: pointer;
            text-decoration: underline;
        }
        .modal-dark {
            background: #1a1a2e;
            color: white;
        }
        .modal-dark .modal-header {
            border-bottom: 1px solid #333;
        }
        .modal-dark .modal-footer {
            border-top: 1px solid #333;
        }
        .table-dark-custom th, .table-dark-custom td {
            padding: 8px;
            border-bottom: 1px solid #333;
        }
    </style>
</head>

<body>
       <!-- preloade -->
       <div class="preload preload-container">
        <div class="preload-logo">
          <div class="spinner"></div>
        </div>
      </div>
    <!-- /preload -->
    <div class="header is-fixed">
        <div class="tf-container">
            <div class="tf-statusbar d-flex justify-content-center align-items-center">
                <a href="#" class="back-btn"> <i class="icon-left"></i> </a>
                <h3>{{ $title }}</h3>
            </div>
        </div>
    </div>
    <div id="app-wrap" class="style1">
        <div class="tf-container">
            <div class="repicient-content mt-8">
                <div class="tf-container">
                 <div class="box-user mt-5 text-center">
                     <div class="box-avatar">
                         @if(auth()->user()->foto_karyawan == null)
                             <img src="{{ url('/assets/img/foto_default.jpg') }}" alt="image">
                         @else
                             <img src="{{ url('/storage/'.auth()->user()->foto_karyawan) }}" alt="image">
                         @endif
                     </div>
                     <h3 class="fw_8 mt-3">{{ strtoupper(auth()->user()->name) }}</h3>
                     <h4 style="color: rgb(196, 196, 101)">SKOR : {{ $skor_akhir->penilaian_berjalan ?? 0 }}</h4>
                 </div>
                </div>
            </div>
            <div class="tf-tab">
                <ul class="menu-tabs tabs-food">
                    <li class="nav-tab active">List Penilaian</li>
                    <li class="nav-tab">Data Penilaian</li>
                </ul>
                <div class="content-tab pt-tab-space mb-5">
                    <!-- Tab 1: List Penilaian -->
                    <div id="tab-gift-item app-wrap">
                        <div class="bill-content">
                            <div class="tf-container">
                                <ul class="mt-3 mb-5">
                                    @foreach ($list_penilaian as $lp)
                                        <li class="list-card-invoice tf-topbar d-flex justify-content-between align-items-center">
                                            <div class="content-right" style="flex: 1;">
                                                <h4>
                                                    <a href="#">{{ $lp->jenis->nama ?? '-' }} 
                                                        <span class="btn btn-{{ $lp->nilai <= 0 ? 'danger' : 'primary' }}">{{ $lp->nilai ?? '-' }}</span>
                                                    </a>
                                                    <!-- Source badge -->
                                                    @if($lp->reference == 'App\Models\MappingShift')
                                                        <span class="source-badge source-absensi">Absensi</span>
                                                    @elseif($lp->reference == 'Manual Adjustment')
                                                        <span class="source-badge source-manual">Manual</span>
                                                    @elseif($lp->reference == 'App\Models\Penugasan')
                                                        <span class="source-badge source-penugasan">Penugasan</span>
                                                    @else
                                                        <span class="source-badge source-other">{{ $lp->reference ?? '-' }}</span>
                                                    @endif
                                                </h4>
                                                <p>
                                                    @if ($lp->tanggal)
                                                        @php
                                                            Carbon\Carbon::setLocale('id');
                                                            $tanggal = Carbon\Carbon::createFromFormat('Y-m-d', $lp->tanggal);
                                                            $new_tanggal = $tanggal->translatedFormat('l, d F Y');
                                                        @endphp
                                                        {{ $new_tanggal  }}
                                                    @else
                                                        -
                                                    @endif
                                                </p>
                                            </div>
                                        </li>
                                    @endforeach
                                    <div class="d-flex justify-content-end me-4 mt-4">
                                        {{ $list_penilaian->links() }}
                                    </div>
                                </ul>

                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Data Penilaian (KPI per Kategori) -->
                    <div id="tab-gift-item-2 app-wrap">
                        <div class="bill-content">
                            <div class="tf-container">
                                <p class="text-muted mb-2" style="font-size: 12px;">Klik kategori untuk melihat detail sumber poin</p>
                                <ul class="mt-3 mb-5">
                                    @foreach ($data_penilaian as $dp)
                                        <li class="list-card-invoice tf-topbar d-flex justify-content-between align-items-center" 
                                            style="cursor: pointer;" 
                                            onclick="showKpiDetail('{{ $dp->nama }}')">
                                            <div class="content-right">
                                                <h4>
                                                    <a href="#">{{ $dp->nama ?? '-' }} 
                                                        <span class="btn btn-{{ $dp->total_penilaian <= 0 ? 'danger' : 'primary' }}">{{ $dp->total_penilaian ?? '-' }}</span>
                                                    </a>
                                                    <small style="color: #aaa; font-size: 10px;">👆 tap untuk detail</small>
                                                </h4>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>

                            </div>
                        </div>
                    </div>

                </div>
            </div>


        </div>
    </div>

    <!-- Modal for KPI Detail -->
    <div class="modal fade" id="kpiDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content modal-dark">
                <div class="modal-header">
                    <h5 class="modal-title" id="kpiDetailTitle">Detail Kategori</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="kpiDetailBody">
                    <p>Loading...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="{{ url('/myhr/javascript/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/bootstrap.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/swiper-bundle.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/swiper.js') }}"></script>
    <script type="text/javascript" src="{{ url('/myhr/javascript/main.js') }}"></script>

    <script>
    function showKpiDetail(kategori) {
        var modal = new bootstrap.Modal(document.getElementById('kpiDetailModal'));
        document.getElementById('kpiDetailTitle').textContent = 'Detail: ' + kategori;
        document.getElementById('kpiDetailBody').innerHTML = '<p>Loading...</p>';
        modal.show();
        
        // Fetch detail data
        $.ajax({
            url: '{{ url("/kinerja-pegawai-user/detail-kategori") }}',
            type: 'GET',
            data: { kategori: kategori },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                var html = '<table class="table-dark-custom" style="width: 100%;">';
                html += '<thead><tr><th>Tanggal</th><th>Nilai</th><th>Sumber</th></tr></thead>';
                html += '<tbody>';
                
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(item) {
                        var sourceLabel = 'Lainnya';
                        var sourceClass = 'source-other';
                        
                        if (item.reference == 'App\\Models\\MappingShift') {
                            sourceLabel = 'Absensi';
                            sourceClass = 'source-absensi';
                        } else if (item.reference == 'Manual Adjustment') {
                            sourceLabel = 'Manual';
                            sourceClass = 'source-manual';
                        } else if (item.reference == 'App\\Models\\Penugasan') {
                            sourceLabel = 'Penugasan';
                            sourceClass = 'source-penugasan';
                        }
                        
                        html += '<tr>';
                        html += '<td>' + item.tanggal + '</td>';
                        html += '<td><span class="btn btn-' + (item.nilai <= 0 ? 'danger' : 'primary') + '" style="font-size:12px;padding:2px 8px;">' + item.nilai + '</span></td>';
                        html += '<td><span class="source-badge ' + sourceClass + '">' + sourceLabel + '</span></td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="3" style="text-align:center;">Tidak ada data</td></tr>';
                }
                
                html += '</tbody></table>';
                html += '<p class="mt-3"><strong>Total: ' + response.total + '</strong></p>';
                
                document.getElementById('kpiDetailBody').innerHTML = html;
            },
            error: function() {
                document.getElementById('kpiDetailBody').innerHTML = '<p>Gagal memuat data</p>';
            }
        });
    }
    </script>

    @include('sweetalert::alert')
</body>

</html>
