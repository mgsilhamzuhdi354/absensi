@extends('templates.dashboard')
@section('isi')
  <div class="row">
    <!-- Dashboard Header -->
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center">
          <h4 class="mb-0">Dashboard Kehadiran - {{ date('F Y') }}</h4>
          <div class="d-flex align-items-center">
            <span class="badge bg-primary p-2 me-2">
              <i data-feather="calendar" class="me-1" style="width: 16px; height: 16px;"></i> {{ date('d F Y') }}
            </span>
            <span class="badge bg-info p-2">
              <i data-feather="clock" class="me-1" style="width: 16px; height: 16px;"></i> <span id="live-time"></span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="col-12">
      <div class="row">
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-primary-light me-3">
                  <i data-feather="users" class="text-primary"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Total Pegawai</h6>
                  <h4 class="mb-0">{{ $jumlah_user }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-success-light me-3">
                  <i data-feather="check-circle" class="text-success"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Masuk</h6>
                  <h4 class="mb-0">{{ $jumlah_masuk + $jumlah_izin_telat + $jumlah_izin_pulang_cepat }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-danger-light me-3">
                  <i data-feather="x-circle" class="text-danger"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Alfa</h6>
                  <h4 class="mb-0">{{ ($jumlah_user - ($jumlah_masuk + $jumlah_izin_telat + $jumlah_izin_pulang_cepat + $jumlah_libur + $jumlah_cuti + $jumlah_izin_masuk + $jumlah_sakit)) }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-warning-light me-3">
                  <i data-feather="calendar" class="text-warning"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Libur</h6>
                  <h4 class="mb-0">{{ $jumlah_libur }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Additional Stats -->
    <div class="col-12">
      <div class="row">
        {{-- Lembur card hidden per request --}}
        
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-primary-light me-3">
                  <i data-feather="credit-card" class="text-primary"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Cuti</h6>
                  <h4 class="mb-0">{{ $jumlah_cuti }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-warning-light me-3">
                  <i data-feather="thermometer" class="text-warning"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Sakit</h6>
                  <h4 class="mb-0">{{ $jumlah_sakit }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-success-light me-3">
                  <i data-feather="umbrella" class="text-success"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Izin</h6>
                  <h4 class="mb-0">{{ $jumlah_izin_masuk }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Izin Stats -->
    <div class="col-12">
      <div class="row">
        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-info-light me-3">
                  <i data-feather="clock" class="text-info"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Izin Telat</h6>
                  <h4 class="mb-0">{{ $jumlah_izin_telat }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-danger-light me-3">
                  <i data-feather="log-out" class="text-danger"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Izin Pulang Cepat</h6>
                  <h4 class="mb-0">{{ $jumlah_izin_pulang_cepat }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-primary-light me-3">
                  <i data-feather="dollar-sign" class="text-primary"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Payroll {{ date('F Y') }}</h6>
                  <h4 class="mb-0">Rp {{ number_format($payroll) }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Financial Stats -->
    <div class="col-12">
      <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-warning-light me-3">
                  <i data-feather="git-commit" class="text-warning"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Kasbon {{ date('F Y') }}</h6>
                  <h4 class="mb-0">Rp {{ number_format($kasbon) }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-3 bg-success-light me-3">
                  <i data-feather="pocket" class="text-success"></i>
                </div>
                <div>
                  <h6 class="text-muted mb-1">Reimbursement {{ date('F Y') }}</h6>
                  <h4 class="mb-0">Rp {{ number_format($reimbursement) }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- KPI Performance Section -->
    <div class="col-12 mb-4">
      <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; overflow: hidden;">
        <div class="card-header bg-transparent border-0 py-4">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i data-feather="trending-up" class="me-2" style="width: 24px; height: 24px;"></i>Skor Kinerja (KPI) - {{ date('F Y') }}</h5>
            <a href="{{ url('/kinerja-pegawai') }}" class="btn btn-light btn-sm shadow-sm" style="border-radius: 20px;">
              <i class="fa fa-eye me-1"></i> Lihat Detail
            </a>
          </div>
        </div>
        <div class="card-body pt-0">
          <div class="row">
            <!-- KPI Distribution Donut Chart -->
            <div class="col-xl-4 col-lg-6 col-md-12 mb-4">
              <div class="card h-100 border-0 shadow-lg kpi-card-animate" style="border-radius: 16px; background: linear-gradient(145deg, #ffffff 0%, #f8f9ff 100%);">
                <div class="card-header border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 16px 16px 0 0;">
                  <h6 class="mb-0 text-white"><i class="fa fa-chart-pie me-2"></i>Distribusi Kinerja Pegawai</h6>
                </div>
                <div class="card-body">
                  <div id="kpiDistributionChart"></div>
                  <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: linear-gradient(90deg, rgba(239,68,68,0.1) 0%, transparent 100%);">
                      <span><i class="fa fa-circle me-2" style="color: #ef4444;"></i>Kinerja Buruk (≤0)</span>
                      <span class="badge" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">{{ $kpi_buruk }} orang</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: linear-gradient(90deg, rgba(245,158,11,0.1) 0%, transparent 100%);">
                      <span><i class="fa fa-circle me-2" style="color: #f59e0b;"></i>Cukup (1-50)</span>
                      <span class="badge" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">{{ $kpi_cukup }} orang</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: linear-gradient(90deg, rgba(6,182,212,0.1) 0%, transparent 100%);">
                      <span><i class="fa fa-circle me-2" style="color: #06b6d4;"></i>Lumayan (51-100)</span>
                      <span class="badge" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">{{ $kpi_lumayan }} orang</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: linear-gradient(90deg, rgba(16,185,129,0.1) 0%, transparent 100%);">
                      <span><i class="fa fa-circle me-2" style="color: #10b981;"></i>Baik (>100)</span>
                      <span class="badge" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">{{ $kpi_baik }} orang</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: linear-gradient(90deg, rgba(107,114,128,0.1) 0%, transparent 100%);">
                      <span><i class="fa fa-circle me-2" style="color: #6b7280;"></i>Belum Ada Data</span>
                      <span class="badge" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);">{{ $kpi_belum_ada_data ?? 0 }} orang</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- KPI Trend Line Chart -->
            <div class="col-xl-8 col-lg-6 col-md-12 mb-4">
              <div class="card h-100 border-0 shadow-lg kpi-card-animate" style="border-radius: 16px; background: linear-gradient(145deg, #ffffff 0%, #f8f9ff 100%); animation-delay: 0.1s;">
                <div class="card-header border-0" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 16px 16px 0 0;">
                  <h6 class="mb-0 text-white"><i class="fa fa-chart-line me-2"></i>Tren Kinerja 6 Bulan Terakhir</h6>
                </div>
                <div class="card-body">
                  <div id="kpiTrendChart" style="min-height: 300px;"></div>
                </div>
              </div>
            </div>
            
            <!-- Top 10 Performers -->
            <div class="col-12 mb-4">
              <div class="card border-0 shadow-lg kpi-card-animate" style="border-radius: 16px; background: linear-gradient(145deg, #ffffff 0%, #f8f9ff 100%); animation-delay: 0.2s;">
                <div class="card-header border-0" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); border-radius: 16px 16px 0 0;">
                  <h6 class="mb-0 text-white"><i class="fa fa-trophy me-2"></i>Top 10 Pegawai dengan Kinerja Terbaik</h6>
                </div>
                <div class="card-body">
                  <div id="topPerformersChart" style="min-height: 400px;"></div>
                </div>
              </div>
            </div>
            
            <!-- KPI by Category -->
            @if($kpi_by_jenis->count() > 0)
            <div class="col-12">
              <div class="card border-0 shadow-lg kpi-card-animate" style="border-radius: 16px; background: linear-gradient(145deg, #ffffff 0%, #f8f9ff 100%); animation-delay: 0.3s;">
                <div class="card-header border-0" style="background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); border-radius: 16px 16px 0 0;">
                  <h6 class="mb-0 text-white"><i class="fa fa-tasks me-2"></i>Nilai KPI per Kategori Kinerja (Bulan Ini)</h6>
                </div>
                <div class="card-body">
                  <div id="kpiByJenisChart" style="min-height: 300px;"></div>
                </div>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
    

    <!-- Calendar -->
    <div class="col-12 mb-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Kalender Kehadiran - {{ date('F Y') }}</h5>
            <div class="calendar-legend d-flex flex-wrap">
              <span class="badge bg-danger me-2 mb-1">Ulang Tahun</span>
              <span class="badge bg-warning me-2 mb-1">Sakit</span>
              <span class="badge bg-primary me-2 mb-1">Cuti</span>
              <span class="badge bg-info me-2 mb-1">Izin</span>
              <span class="badge bg-success me-2 mb-1">Izin Telat</span>
              <span class="badge bg-secondary mb-1">Izin Pulang Cepat</span>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <div id="calendar"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  @push('script')
    <script>
      // KPI Charts Initialization
      document.addEventListener('DOMContentLoaded', function() {
        // 1. KPI Distribution Donut Chart
        var kpiDistributionOptions = {
          series: [{{ $kpi_buruk }}, {{ $kpi_cukup }}, {{ $kpi_lumayan }}, {{ $kpi_baik }}, {{ $kpi_belum_ada_data ?? 0 }}],
          chart: {
            type: 'donut',
            height: 280,
            animations: {
              enabled: true,
              easing: 'easeinout',
              speed: 1200,
              animateGradually: {
                enabled: true,
                delay: 150
              },
              dynamicAnimation: {
                enabled: true,
                speed: 350
              }
            },
            dropShadow: {
              enabled: true,
              top: 3,
              left: 0,
              blur: 10,
              opacity: 0.15
            }
          },
          labels: ['Buruk (≤0)', 'Cukup (1-50)', 'Lumayan (51-100)', 'Baik (>100)', 'Belum Ada Data'],
          colors: ['#ef4444', '#f59e0b', '#06b6d4', '#10b981', '#6b7280'],
          legend: {
            show: false
          },
          plotOptions: {
            pie: {
              donut: {
                size: '70%',
                labels: {
                  show: true,
                  name: {
                    show: true,
                    fontSize: '14px'
                  },
                  value: {
                    show: true,
                    fontSize: '24px',
                    fontWeight: 700,
                    color: '#1e293b',
                    formatter: function(val) {
                      return val + ' orang';
                    }
                  },
                  total: {
                    show: true,
                    label: 'Total Pegawai',
                    fontSize: '12px',
                    color: '#64748b',
                    formatter: function(w) {
                      return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + ' orang';
                    }
                  }
                }
              }
            }
          },
          responsive: [{
            breakpoint: 480,
            options: {
              chart: {
                height: 250
              }
            }
          }]
        };
        
        var kpiDistributionChart = new ApexCharts(document.querySelector("#kpiDistributionChart"), kpiDistributionOptions);
        kpiDistributionChart.render();
        
        // 2. KPI Monthly Trend Line Chart
        var kpiTrendOptions = {
          series: [{
            name: 'Rata-rata Skor KPI',
            data: [
              @foreach($kpi_trend as $trend)
                {{ $trend['score'] }},
              @endforeach
            ]
          }],
          chart: {
            type: 'area',
            height: 300,
            toolbar: {
              show: false
            },
            animations: {
              enabled: true,
              easing: 'easeinout',
              speed: 1200,
              animateGradually: {
                enabled: true,
                delay: 200
              }
            },
            dropShadow: {
              enabled: true,
              top: 5,
              left: 0,
              blur: 10,
              color: '#4facfe',
              opacity: 0.2
            }
          },
          colors: ['#4facfe'],
          fill: {
            type: 'gradient',
            gradient: {
              shade: 'light',
              type: 'vertical',
              shadeIntensity: 0.5,
              gradientToColors: ['#00f2fe'],
              inverseColors: false,
              opacityFrom: 0.8,
              opacityTo: 0.1,
              stops: [0, 100]
            }
          },
          dataLabels: {
            enabled: true,
            formatter: function(val) {
              return val.toFixed(1);
            },
            style: {
              fontSize: '12px',
              fontWeight: 700,
              colors: ['#1e293b']
            },
            background: {
              enabled: true,
              foreColor: '#fff',
              borderRadius: 4,
              padding: 4,
              opacity: 0.9,
              borderWidth: 0,
              dropShadow: {
                enabled: false
              }
            }
          },
          stroke: {
            curve: 'smooth',
            width: 4
          },
          markers: {
            size: 6,
            colors: ['#4facfe'],
            strokeColors: '#fff',
            strokeWidth: 3,
            hover: {
              size: 9
            }
          },
          xaxis: {
            categories: [
              @foreach($kpi_trend as $trend)
                '{{ $trend['month'] }}',
              @endforeach
            ],
            labels: {
              style: {
                fontSize: '12px',
                fontWeight: 500,
                colors: '#64748b'
              }
            },
            axisBorder: {
              show: false
            },
            axisTicks: {
              show: false
            }
          },
          yaxis: {
            min: 0,
            labels: {
              formatter: function(val) {
                return val.toFixed(0);
              },
              style: {
                fontSize: '12px',
                colors: '#64748b'
              }
            }
          },
          tooltip: {
            theme: 'dark',
            y: {
              formatter: function(val) {
                return val.toFixed(2) + ' poin';
              }
            }
          },
          grid: {
            borderColor: '#e2e8f0',
            strokeDashArray: 4,
            padding: {
              top: 10,
              bottom: 10
            }
          }
        };
        
        var kpiTrendChart = new ApexCharts(document.querySelector("#kpiTrendChart"), kpiTrendOptions);
        kpiTrendChart.render();
        
        // 3. Top 10 Performers Horizontal Bar Chart
        var topPerformersOptions = {
          series: [{
            name: 'Skor KPI',
            data: [
              @foreach($top_performers as $performer)
                {{ $performer->kpi_score ?? 0 }},
              @endforeach
            ]
          }],
          chart: {
            type: 'bar',
            height: 400,
            toolbar: {
              show: false
            },
            animations: {
              enabled: true,
              easing: 'easeinout',
              speed: 800
            }
          },
          plotOptions: {
            bar: {
              horizontal: true,
              barHeight: '70%',
              borderRadius: 6,
              distributed: true,
              dataLabels: {
                position: 'right'
              }
            }
          },
          colors: ['#10b981', '#22c55e', '#34d399', '#4ade80', '#6ee7b7', '#86efac', '#a7f3d0', '#bbf7d0', '#d1fae5', '#ecfdf5'],
          dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
              return val + ' poin';
            },
            style: {
              fontSize: '12px',
              fontWeight: 700,
              colors: ['#1e293b']
            },
            offsetX: 10
          },
          legend: {
            show: false
          },
          xaxis: {
            categories: [
              @foreach($top_performers as $performer)
                '{{ $performer->name }}',
              @endforeach
            ],
            labels: {
              formatter: function(val) {
                return val.toFixed(0);
              }
            }
          },
          yaxis: {
            labels: {
              style: {
                fontSize: '12px',
                fontWeight: 500
              },
              maxWidth: 200
            }
          },
          tooltip: {
            y: {
              formatter: function(val) {
                return val + ' poin';
              }
            }
          },
          grid: {
            borderColor: '#e9ecef'
          }
        };
        
        var topPerformersChart = new ApexCharts(document.querySelector("#topPerformersChart"), topPerformersOptions);
        topPerformersChart.render();
        
        // 4. KPI by Jenis Chart
        @if($kpi_by_jenis->count() > 0)
        var kpiByJenisOptions = {
          series: [{
            name: 'Total Nilai',
            data: [
              @foreach($kpi_by_jenis as $jenis)
                {{ $jenis->total ?? 0 }},
              @endforeach
            ]
          }],
          chart: {
            type: 'bar',
            height: 300,
            toolbar: {
              show: false
            }
          },
          plotOptions: {
            bar: {
              horizontal: false,
              columnWidth: '60%',
              borderRadius: 8,
              distributed: true
            }
          },
          colors: ['#4361ee', '#7c3aed', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#f97316'],
          dataLabels: {
            enabled: true,
            formatter: function(val) {
              return val.toFixed(0);
            },
            style: {
              fontSize: '11px',
              fontWeight: 600
            }
          },
          legend: {
            show: false
          },
          xaxis: {
            categories: [
              @foreach($kpi_by_jenis as $jenis)
                '{{ Str::limit($jenis->nama, 15) }}',
              @endforeach
            ],
            labels: {
              style: {
                fontSize: '10px'
              },
              rotate: -45,
              rotateAlways: true
            }
          },
          yaxis: {
            min: 0
          },
          tooltip: {
            y: {
              formatter: function(val) {
                return val + ' poin';
              }
            }
          },
          grid: {
            borderColor: '#e9ecef'
          }
        };
        
        var kpiByJenisChart = new ApexCharts(document.querySelector("#kpiByJenisChart"), kpiByJenisOptions);
        kpiByJenisChart.render();
        @endif
      });
    </script>
    
    <script>
      // Live time function
      function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        });
        document.getElementById('live-time').textContent = timeString;
      }
      
      // Update time every second
      setInterval(updateTime, 1000);
      updateTime(); // Initial call
      
      // Calendar initialization
      document.addEventListener("DOMContentLoaded", function () {
        var date = new Date();
        var d = date.getDate();
        m = date.getMonth();
        y = date.getFullYear();

        var containerEl = document.getElementById("external-events-list");
        if (containerEl) {
          new FullCalendar.Draggable(containerEl, {
            itemSelector: ".fc-event",
            eventData: function (eventEl) {
              return {
                title: eventEl.innerText.trim(),
              };
            },
          });
        }

        var calendarEl = document.getElementById("calendar");
        var calendar = new FullCalendar.Calendar(calendarEl, {
          headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek",
          },
          initialView: "dayGridMonth",
          navLinks: true, // can click day/week names to navigate views
          editable: false,
          selectable: true,
          nowIndicator: true,
          dayMaxEvents: true, // allow "more" link when too many events
          height: 'auto',
          events: [
            @php
              $tahun_skrg = date('Y');
              $bulan_skrg = date('m');
              $jmlh_bulan = cal_days_in_month(CAL_GREGORIAN,$bulan_skrg,$tahun_skrg);
              $tgl_mulai = date('1945-01-01');
              $tgl_akhir = date('Y-m-'.$jmlh_bulan);
              $data_user = App\Models\User::select('name', 'tgl_lahir')->whereBetween('tgl_lahir', [$tgl_mulai, $tgl_akhir])->get();
              $data_sakit = App\Models\MappingShift::where('status_absen', 'Sakit')->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])->get();
              $data_cuti = App\Models\MappingShift::where('status_absen', 'Cuti')->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])->get();
              $data_izin_masuk = App\Models\MappingShift::where('status_absen', 'Izin Masuk')->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])->get();
              $data_izin_telat = App\Models\MappingShift::where('status_absen', 'Izin Telat')->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])->get();
              $data_izin_pulang_cepat = App\Models\MappingShift::where('status_absen', 'Izin Pulang Cepat')->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])->get();
            @endphp
            
            @foreach($data_user as $du)
              @php
                $pecah = explode("-", $du->tgl_lahir)
              @endphp
              {
                title: 'Ulang Tahun {{ $du->name }}',
                start: '{{ $tahun_skrg }}-{{ $pecah[1] }}-{{ $pecah[2] }}',
                backgroundColor: '#dc3545',
                borderColor: '#dc3545',
                textColor: '#fff'
              },
            @endforeach
            
            @foreach($data_sakit as $ds)
              {
                title: 'Sakit: {{ $ds->user->name }}',
                start: '{{ $ds->tanggal }}',
                backgroundColor: '#ffc107',
                borderColor: '#ffc107',
                textColor: '#212529'
              },
            @endforeach
            
            @foreach($data_cuti as $dc)
              {
                title: 'Cuti: {{ $dc->user->name }}',
                start: '{{ $dc->tanggal }}',
                backgroundColor: '#0d6efd',
                borderColor: '#0d6efd',
                textColor: '#fff'
              },
            @endforeach
            
            @foreach($data_izin_masuk as $dim)
              {
                title: 'Izin: {{ $dim->user->name }}',
                start: '{{ $dim->tanggal }}',
                backgroundColor: '#0dcaf0',
                borderColor: '#0dcaf0',
                textColor: '#212529'
              },
            @endforeach
            
            @foreach($data_izin_telat as $dit)
              {
                title: 'Izin Telat: {{ $dit->user->name }}',
                start: '{{ $dit->tanggal }}',
                backgroundColor: '#198754',
                borderColor: '#198754',
                textColor: '#fff'
              },
            @endforeach
            
            @foreach($data_izin_pulang_cepat as $dipc)
              {
                title: 'Izin Pulang Cepat: {{ $dipc->user->name }}',
                start: '{{ $dipc->tanggal }}',
                backgroundColor: '#6c757d',
                borderColor: '#6c757d',
                textColor: '#fff'
              },
            @endforeach
          ],
          eventClick: function(info) {
            info.jsEvent.preventDefault();
            Swal.fire({
              title: info.event.title,
              text: 'Tanggal: ' + moment(info.event.start).format('DD MMMM YYYY'),
              icon: 'info',
              confirmButtonColor: '#0d6efd'
            });
          },
          windowResize: function(view) {
            if(window.innerWidth < 768) {
              calendar.changeView('listWeek');
            } else {
              calendar.changeView('dayGridMonth');
            }
          }
        });
        
        // Initial view based on screen size
        if(window.innerWidth < 768) {
          calendar.changeView('listWeek');
        }
        
        calendar.render();
      });
    </script>
    <style>
      /* Custom background colors for cards - Theme Aware */
      .bg-primary-light {
        background-color: var(--primary-light, rgba(67, 97, 238, 0.15));
      }
      .bg-success-light {
        background-color: rgba(16, 185, 129, 0.15);
      }
      .bg-warning-light {
        background-color: rgba(245, 158, 11, 0.15);
      }
      .bg-danger-light {
        background-color: rgba(239, 68, 68, 0.15);
      }
      .bg-info-light {
        background-color: rgba(6, 182, 212, 0.15);
      }
      
      /* Icon colors - Theme Aware */
      .text-primary {
        color: var(--primary-color, #4361ee) !important;
      }
      
      /* Card styling with animations */
      .stat-card-wrapper {
        opacity: 0;
        animation: fadeInUp 0.6s ease-out forwards;
      }
      
      .stat-card-wrapper:nth-child(1) { animation-delay: 0.1s; }
      .stat-card-wrapper:nth-child(2) { animation-delay: 0.2s; }
      .stat-card-wrapper:nth-child(3) { animation-delay: 0.3s; }
      .stat-card-wrapper:nth-child(4) { animation-delay: 0.4s; }
      
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      /* KPI Card Animations */
      .kpi-card-animate {
        opacity: 0;
        animation: slideInUp 0.8s ease-out forwards;
      }
      
      .kpi-card-animate:nth-child(1) { animation-delay: 0.1s; }
      .kpi-card-animate:nth-child(2) { animation-delay: 0.2s; }
      .kpi-card-animate:nth-child(3) { animation-delay: 0.3s; }
      .kpi-card-animate:nth-child(4) { animation-delay: 0.4s; }
      
      @keyframes slideInUp {
        from {
          opacity: 0;
          transform: translateY(40px) scale(0.95);
        }
        to {
          opacity: 1;
          transform: translateY(0) scale(1);
        }
      }
      
      /* KPI Card Hover Effects */
      .kpi-card-animate:hover {
        transform: translateY(-8px) !important;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.3s ease;
      }
      
      /* Gradient text animation */
      @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
      }
      
      /* Badge pulse animation */
      .badge {
        transition: all 0.3s ease;
      }
      
      .badge:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      }
      
      .card {
        transition: transform 0.3s, box-shadow 0.3s;
        border-radius: 0.75rem;
      }
      
      .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12) !important;
      }
      
      /* Stat number animation */
      .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
      }
      
      /* Calendar styling - Theme Aware */
      .fc-theme-standard td, .fc-theme-standard th {
        border-color: #e9ecef;
      }
      
      .fc-theme-standard .fc-scrollgrid {
        border-color: #e9ecef;
      }
      
      .fc .fc-button-primary {
        background-color: var(--primary-color, #4361ee) !important;
        border-color: var(--primary-color, #4361ee) !important;
        transition: all 0.3s ease;
      }
      
      .fc .fc-button-primary:hover {
        background-color: var(--primary-hover, #3651d4) !important;
        border-color: var(--primary-hover, #3651d4) !important;
        transform: translateY(-2px);
      }
      
      .fc .fc-button-primary:disabled {
        background-color: #6c757d;
        border-color: #6c757d;
      }
      
      .fc .fc-daygrid-day.fc-day-today {
        background-color: var(--primary-light, rgba(67, 97, 238, 0.1)) !important;
      }
      
      /* Header section enhancement */
      .dashboard-header {
        background: linear-gradient(135deg, var(--primary-color, #4361ee) 0%, #7c3aed 100%);
        color: white;
        border-radius: 16px;
        padding: 1.5rem;
      }
      
      .dashboard-header h4 {
        color: white;
        margin: 0;
      }
      
      .dashboard-header .badge {
        background: rgba(255, 255, 255, 0.2) !important;
        backdrop-filter: blur(10px);
      }
      
      /* Responsive adjustments */
      @media (max-width: 767.98px) {
        .calendar-legend {
          margin-top: 0.5rem;
          justify-content: flex-start;
          width: 100%;
        }
        
        .card-header {
          flex-direction: column;
          align-items: flex-start !important;
        }
        
        .card-header h5 {
          margin-bottom: 0.5rem;
        }
        
        .stat-number {
          font-size: 1.5rem;
        }
      }
    </style>
    
    <script>
      // Counting Animation for Statistics
      function animateCounter(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
          if (!startTimestamp) startTimestamp = timestamp;
          const progress = Math.min((timestamp - startTimestamp) / duration, 1);
          const easeOutQuad = 1 - (1 - progress) * (1 - progress);
          const current = Math.floor(easeOutQuad * (end - start) + start);
          
          // Check if it's a currency value
          if (element.dataset.isCurrency) {
            element.textContent = 'Rp ' + current.toLocaleString('id-ID');
          } else {
            element.textContent = current.toLocaleString('id-ID');
          }
          
          if (progress < 1) {
            window.requestAnimationFrame(step);
          }
        };
        window.requestAnimationFrame(step);
      }
      
      // Initialize counters when elements are in viewport
      document.addEventListener('DOMContentLoaded', function() {
        const counters = document.querySelectorAll('.count-up');
        
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.dataset.counted) {
              entry.target.dataset.counted = true;
              const endValue = parseInt(entry.target.dataset.value) || 0;
              animateCounter(entry.target, 0, endValue, 1500);
            }
          });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => observer.observe(counter));
      });
    </script>
  @endpush
@endsection
