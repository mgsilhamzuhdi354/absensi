@extends('templates.app')
@section('container')
    <div class="card-secton transfer-section">
        <div class="tf-container">
            <div class="tf-balance-box">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="inner-left d-flex justify-content-between align-items-center">
                        <span>Tanggal Shift</span>
                    </div>
                    <span>{{ $dinas_luar->tanggal ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="inner-left d-flex justify-content-between align-items-center">
                        <span>Shift</span>
                    </div>
                    <span>{{ $dinas_luar->Shift->nama_shift ?? '' }} ({{ $dinas_luar->Shift->jam_masuk ?? '' }} - {{ $dinas_luar->Shift->jam_keluar ?? '' }})</span>
                </div>
            </div>
        </div>
    </div>

    <br>
    <style>
        .jam-digital-malasngoding {
          overflow: hidden;
          float: center;
          width: 100px;
          margin: 2px auto;
          border: 0px solid #efefef;
        }

        .kotak {
          float: left;
          width: 30px;
          height: 30px;
          background-color: #189fff;
        }

        .jam-digital-malasngoding p {
          color: #fff;
          font-size: 16px;
          text-align: center;
          margin-top: 3px;
        }

        /* Styles untuk form pengajuan dinas luar */
        .pengajuan-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 20px;
            margin: 10px 15px;
            color: white;
        }
        .pengajuan-card h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
        }
        .pengajuan-card .form-control,
        .pengajuan-card .form-select {
            border-radius: 10px;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            background: rgba(255,255,255,0.95);
            color: #333;
        }
        .pengajuan-card .form-control:focus,
        .pengajuan-card .form-select:focus {
            box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
        }
        .pengajuan-card label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: rgba(255,255,255,0.9);
        }
        .pengajuan-card textarea {
            resize: none;
        }
        .btn-ajukan {
            background: #fff;
            color: #764ba2;
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 700;
            font-size: 15px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .btn-ajukan:hover {
            background: rgba(255,255,255,0.9);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Status pengajuan cards */
        .status-card {
            border-radius: 12px;
            padding: 15px;
            margin: 8px 15px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .status-card .badge-pending {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            color: #333;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-card .badge-approved {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-card .badge-ditolak {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-card .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .status-card .info-label {
            color: #888;
            font-weight: 500;
        }
        .status-card .info-value {
            color: #333;
            font-weight: 600;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin: 15px 15px 10px;
        }
        .link-riwayat {
            display: block;
            text-align: center;
            margin: 10px 15px;
            padding: 10px;
            background: #f0f2f5;
            border-radius: 10px;
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
        }
        .link-riwayat:hover {
            background: #e4e7eb;
        }
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(255,255,255,0.95);
            border: 2px dashed rgba(255,255,255,0.5);
            border-radius: 10px;
            padding: 15px;
            color: #764ba2;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-label:hover {
            border-color: rgba(255,255,255,0.8);
            background: rgba(255,255,255,1);
        }
        .foto-preview {
            max-width: 100%;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 8px;
            display: none;
        }
    </style>

    <div class="jam-digital-malasngoding">
        <div class="kotak">
          <p id="jam"></p>
        </div>
        <div class="kotak">
          <p id="menit"></p>
        </div>
        <div class="kotak">
          <p id="detik"></p>
        </div>
    </div>

    <script>
        window.setTimeout("waktu()", 1000);

        function waktu() {
          var waktu = new Date();
          setTimeout("waktu()", 1000);
          document.getElementById("jam").innerHTML = waktu.getHours();
          document.getElementById("menit").innerHTML = waktu.getMinutes();
          document.getElementById("detik").innerHTML = waktu.getSeconds();
        }
    </script>
    <br>

    <div class="d-flex justify-content-center mb-4">
        <form action="{{ url('/my-location') }}" method="get">
            @csrf
            <input type="hidden" name="lat" id="lat2">
            <input type="hidden" name="long" id="long2">
            <input type="hidden" name="userid" value="{{ auth()->user()->id }}">
            <button type="submit" class="btn btn-success">Lihat Lokasi Saya</button>
        </form>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div style="margin: 0 15px 10px;">
            <div class="alert alert-success" style="border-radius: 10px; font-size: 13px; padding: 10px 15px;">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div style="margin: 0 15px 10px;">
            <div class="alert alert-danger" style="border-radius: 10px; font-size: 13px; padding: 10px 15px;">
                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            </div>
        </div>
    @endif
    @if($errors->any())
        <div style="margin: 0 15px 10px;">
            <div class="alert alert-danger" style="border-radius: 10px; font-size: 13px; padding: 10px 15px;">
                <i class="fas fa-exclamation-triangle me-1"></i>
                @foreach($errors->all() as $err)
                    {{ $err }}<br>
                @endforeach
            </div>
        </div>
    @endif

    <div class="transfer-content">
        @if (!$dinas_luar)
            {{-- ===== TIDAK ADA SHIFT: Tampilkan Form Pengajuan + Status ===== --}}

            {{-- Status Pengajuan Aktif --}}
            @if($pengajuan_aktif->count() > 0)
                <div class="section-title">
                    <i class="fas fa-clock" style="color: #667eea;"></i> Pengajuan Aktif Anda
                </div>
                @foreach($pengajuan_aktif as $pa)
                    <div class="status-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong style="font-size: 14px;">{{ $pa->Shift->nama_shift ?? '-' }}</strong>
                            @if($pa->status == 'Pending')
                                <span class="badge-pending">⏳ Menunggu</span>
                            @elseif($pa->status == 'Approved')
                                <span class="badge-approved">✅ Disetujui</span>
                            @else
                                <span class="badge-ditolak">❌ Ditolak</span>
                            @endif
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tanggal</span>
                            <span class="info-value">{{ $pa->tanggal_mulai }} s/d {{ $pa->tanggal_akhir }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Lokasi</span>
                            <span class="info-value">{{ $pa->lokasi_tujuan ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Alasan</span>
                            <span class="info-value">{{ Str::limit($pa->alasan, 50) }}</span>
                        </div>
                        @if($pa->catatan)
                            <div class="info-row">
                                <span class="info-label">Catatan Admin</span>
                                <span class="info-value">{{ $pa->catatan }}</span>
                            </div>
                        @endif
                        @if($pa->foto_bukti)
                            <div style="margin-top: 5px;">
                                <a href="{{ url('/storage/'.$pa->foto_bukti) }}" target="_blank" style="font-size: 12px; color: #667eea;">
                                    <i class="fas fa-image"></i> Lihat Foto Bukti
                                </a>
                            </div>
                        @endif
                        @if($pa->status == 'Pending')
                            <form action="{{ url('/pengajuan-dinas-luar/delete/'.$pa->id) }}" method="post" class="mt-2" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                @method('delete')
                                @csrf
                                <button class="btn btn-sm" style="background: #fee2e2; color: #dc2626; border-radius: 8px; font-size: 12px; border: none;">
                                    <i class="fas fa-times"></i> Batalkan
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            @endif

            {{-- Form Pengajuan Dinas Luar --}}
            <div class="pengajuan-card">
                <h3><i class="fas fa-paper-plane me-2"></i>Ajukan Dinas Luar</h3>
                <p style="font-size: 12px; opacity: 0.85; margin-bottom: 15px;">Isi form di bawah untuk mengajukan dinas luar. Admin akan meninjau dan menyetujui pengajuan Anda.</p>

                <form method="post" action="{{ url('/dinas-luar/ajukan') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="shift_id"><i class="fas fa-clock me-1"></i>Pilih Shift *</label>
                        <select name="shift_id" id="shift_id" class="form-control" required>
                            <option value="">-- Pilih Shift --</option>
                            @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->nama_shift }} ({{ $s->jam_masuk }} - {{ $s->jam_keluar }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="lokasi_tujuan"><i class="fas fa-map-marker-alt me-1"></i>Lokasi Tujuan</label>
                        <input type="text" name="lokasi_tujuan" id="lokasi_tujuan" class="form-control" value="{{ old('lokasi_tujuan') }}" placeholder="Contoh: Kantor Pusat Jakarta">
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="tanggal_mulai"><i class="fas fa-calendar me-1"></i>Tgl Mulai *</label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-6">
                            <label for="tanggal_akhir"><i class="fas fa-calendar-check me-1"></i>Tgl Akhir *</label>
                            <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="{{ old('tanggal_akhir', date('Y-m-d')) }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="alasan"><i class="fas fa-edit me-1"></i>Alasan / Keperluan *</label>
                        <textarea name="alasan" id="alasan" rows="3" class="form-control" placeholder="Jelaskan keperluan dinas luar..." required>{{ old('alasan') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label><i class="fas fa-camera me-1"></i>Foto Bukti (Surat Tugas / Bukti)</label>
                        <div class="file-upload-wrapper">
                            <div class="file-upload-label" id="uploadLabel">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span id="uploadText">Tap untuk ambil foto / pilih file</span>
                            </div>
                            <input type="file" name="foto_bukti" id="foto_bukti" accept="image/*" capture="environment" onchange="previewFoto(this)">
                        </div>
                        <img id="fotoPreview" class="foto-preview" alt="Preview">
                    </div>

                    {{-- GPS Hidden Fields --}}
                    <input type="hidden" name="lat_pengajuan" id="lat_pengajuan">
                    <input type="hidden" name="long_pengajuan" id="long_pengajuan">

                    <button type="submit" class="btn-ajukan">
                        <i class="fas fa-paper-plane me-1"></i> Ajukan Dinas Luar
                    </button>
                </form>
            </div>

            {{-- Link ke halaman riwayat pengajuan --}}
            <a href="{{ url('/pengajuan-dinas-luar') }}" class="link-riwayat">
                <i class="fas fa-history me-1"></i> Lihat Semua Riwayat Pengajuan
            </a>

        @elseif($dinas_luar->status_absen == 'Libur')
            <center>
                <h2>Hari Ini Anda Libur</h2>
            </center>
        @elseif($dinas_luar->status_absen == "Cuti")
            <center>
                <h2>Hari Ini Anda Cuti</h2>
            </center>
        @else
            @if ($dinas_luar->jam_absen == null)
                <form method="post" action="{{ url('/dinas-luar/masuk/'.$dinas_luar->id) }}">
                    @method('PUT')
                    @csrf
                    <div class="tf-container">
                        <center>
                            <h2>Masuk Dinas Luar: </h2>
                            <div class="webcam" id="results"></div>
                        </center>
                        <input type="hidden" name="jam_absen">
                        <input type="hidden" name="foto_jam_absen" class="image-tag">
                        <input type="hidden" name="lat_absen" id="lat">
                        <input type="hidden" name="long_absen" id="long">
                        <input type="hidden" name="telat">
                        <input type="hidden" name="status_absen">
                        <button type="submit" class="tf-btn accent large" onClick="take_snapshot()">Save</button>
                    </div>
                </form>
                <br>
                <br>
                <br>
                <br>
                <br>
                <script type="text/javascript" src="{{ url('webcamjs/webcam.min.js') }}"></script>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script language="JavaScript">
                Webcam.set({
                    width: 320,
                    height: 320,
                    image_format: 'jpeg',
                    jpeg_quality: 50
                });
                
                Webcam.on('error', function(err) {
                    let errorMessage = 'Tidak dapat mengakses kamera.';
                    if (err.name === 'NotAllowedError' || (err.message && err.message.includes('Permission denied'))) {
                        errorMessage = 'Izin kamera ditolak. Silakan:<br><br>1. Klik ikon gembok di address bar<br>2. Ubah Camera menjadi Allow<br>3. Refresh halaman<br><br><b>Catatan:</b> Kamera hanya berfungsi di HTTPS atau localhost.';
                    } else if (err.name === 'NotFoundError') {
                        errorMessage = 'Kamera tidak ditemukan.';
                    } else if (err.name === 'NotReadableError') {
                        errorMessage = 'Kamera sedang digunakan aplikasi lain.';
                    }
                    Swal.fire({ icon: 'error', title: 'Kamera Error', html: errorMessage, confirmButtonColor: '#4f46e5' });
                });
                
                Webcam.attach( '.webcam' );
                </script>
                <script language="JavaScript">
                function take_snapshot() {
                    Webcam.snap( function(data_uri) {
                            $(".image-tag").val(data_uri);
                    document.getElementById('results').innerHTML =
                        '<img src="'+data_uri+'"/>';
                    } );
                }
                </script>
            @elseif($dinas_luar->jam_pulang == null)
                <form method="post" action="{{ url('/dinas-luar/pulang/'.$dinas_luar->id) }}">
                    @method('PUT')
                    @csrf
                    <div class="tf-container">
                        <center>
                            <h2>Pulang Dinas Luar: </h2>
                            <div class="webcam" id="results"></div>
                        </center>
                        <input type="hidden" name="jam_pulang">
                        <input type="hidden" name="foto_jam_pulang" class="image-tag">
                        <input type="hidden" name="lat_pulang" id="lat">
                        <input type="hidden" name="long_pulang" id="long">
                        <input type="hidden" name="pulang_cepat">
                        <button type="submit" class="tf-btn accent large" onClick="take_snapshot()">Save</button>
                    </div>
                </form>
                <br>
                <br>
                <br>
                <br>
                <br>
                <script type="text/javascript" src="{{ url('webcamjs/webcam.min.js') }}"></script>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script language="JavaScript">
                Webcam.set({
                    width: 320,
                    height: 320,
                    image_format: 'jpeg',
                    jpeg_quality: 50
                });
                
                Webcam.on('error', function(err) {
                    let errorMessage = 'Tidak dapat mengakses kamera.';
                    if (err.name === 'NotAllowedError' || (err.message && err.message.includes('Permission denied'))) {
                        errorMessage = 'Izin kamera ditolak. Silakan:<br><br>1. Klik ikon gembok di address bar<br>2. Ubah Camera menjadi Allow<br>3. Refresh halaman<br><br><b>Catatan:</b> Kamera hanya berfungsi di HTTPS atau localhost.';
                    } else if (err.name === 'NotFoundError') {
                        errorMessage = 'Kamera tidak ditemukan.';
                    } else if (err.name === 'NotReadableError') {
                        errorMessage = 'Kamera sedang digunakan aplikasi lain.';
                    }
                    Swal.fire({ icon: 'error', title: 'Kamera Error', html: errorMessage, confirmButtonColor: '#4f46e5' });
                });
                
                Webcam.attach( '.webcam' );
                </script>
                <script language="JavaScript">
                function take_snapshot() {
                    Webcam.snap( function(data_uri) {
                            $(".image-tag").val(data_uri);
                    document.getElementById('results').innerHTML =
                        '<img src="'+data_uri+'"/>';
                    } );
                }
                </script>
            @else
                <center>
                    <h2>Anda Sudah Selesai Absen</h2>
                </center>
            @endif
        @endif
    </div>

    @push('script')
        <script>
            function getLocation() {
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
                // Also fill pengajuan GPS fields
                $('#lat_pengajuan').val(position.coords.latitude);
                $('#long_pengajuan').val(position.coords.longitude);
            }

            setInterval(getLocation, 1000);

            // Preview foto bukti
            function previewFoto(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('fotoPreview').src = e.target.result;
                        document.getElementById('fotoPreview').style.display = 'block';
                        document.getElementById('uploadText').textContent = input.files[0].name;
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            // Auto set min date for tanggal_akhir
            $(document).ready(function() {
                $('#tanggal_mulai').change(function() {
                    var val = $(this).val();
                    $('#tanggal_akhir').val(val);
                    $('#tanggal_akhir').attr('min', val);
                });
            });
        </script>
    @endpush

@endsection
