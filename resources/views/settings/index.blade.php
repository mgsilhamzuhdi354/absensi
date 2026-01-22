@extends('templates.dashboard')
@section('isi')
    <style>
        .settings-header {
            background: linear-gradient(135deg, var(--primary-color, #4361ee) 0%, #7c3aed 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
        }
        
        .settings-header h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .settings-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }
        
        .settings-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .settings-card-header {
            background: #f8fafc;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .settings-card-header h5 {
            margin: 0;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .settings-card-header h5 i {
            color: var(--primary-color, #4361ee);
        }
        
        .settings-card-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #334155;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color, #4361ee);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            outline: none;
        }
        
        /* Theme Color Swatches */
        .color-swatches {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 0.5rem;
        }
        
        .color-swatch {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .color-swatch:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .color-swatch.active {
            border-color: #1e293b;
        }
        
        .color-swatch.active::after {
            content: '✓';
            color: white;
            font-weight: bold;
            font-size: 1.25rem;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        
        .color-swatch.blue { background: #4361ee; }
        .color-swatch.purple { background: #7c3aed; }
        .color-swatch.green { background: #10b981; }
        .color-swatch.orange { background: #f59e0b; }
        .color-swatch.red { background: #ef4444; }
        .color-swatch.teal { background: #14b8a6; }
        .color-swatch.pink { background: #ec4899; }
        .color-swatch.indigo { background: #6366f1; }
        
        /* Theme Mode Toggle */
        .theme-toggle-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .toggle-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .toggle-option:hover {
            border-color: var(--primary-color, #4361ee);
        }
        
        .toggle-option.active {
            border-color: var(--primary-color, #4361ee);
            background: rgba(67, 97, 238, 0.1);
        }
        
        .toggle-option i {
            font-size: 1.25rem;
        }
        
        .toggle-option.light-mode i { color: #f59e0b; }
        .toggle-option.dark-mode i { color: #1e293b; }
        
        /* Preview Card */
        .preview-card {
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .preview-card h6 {
            margin: 0 0 1rem;
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .preview-elements {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        
        .preview-btn {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            border: none;
            cursor: default;
            transition: all 0.3s ease;
        }
        
        .preview-btn.primary {
            background: var(--preview-color, #4361ee);
            color: white;
        }
        
        .preview-btn.outline {
            background: transparent;
            color: var(--preview-color, #4361ee);
            border: 2px solid var(--preview-color, #4361ee);
        }
        
        .preview-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(67, 97, 238, 0.15);
            color: var(--preview-color, #4361ee);
        }
        
        /* Submit Button */
        .btn-save {
            background: linear-gradient(135deg, var(--primary-color, #4361ee) 0%, #7c3aed 100%);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3);
        }
        
        .logo-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }
    </style>

    <div class="row">
        <!-- Header -->
        <div class="col-12">
            <div class="settings-header">
                <h4><i class="fa fa-cog me-2"></i> {{ $title }}</h4>
                <p>Atur pengaturan aplikasi dan tampilan</p>
            </div>
        </div>
        
        <div class="col-12">
            <form method="post" action="{{ url('/settings/store') }}" enctype="multipart/form-data">
                @csrf
                
                <!-- Company Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h5><i class="fa fa-building"></i> Pengaturan Perusahaan</h5>
                    </div>
                    <div class="settings-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Nama Perusahaan</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           name="name" value="{{ old('name', $data->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           name="email" value="{{ old('email', $data->email) }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Alamat</label>
                                    <input type="text" class="form-control @error('alamat') is-invalid @enderror" 
                                           name="alamat" value="{{ old('alamat', $data->alamat) }}">
                                    @error('alamat')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Telepon</label>
                                    <input type="number" class="form-control @error('phone') is-invalid @enderror" 
                                           name="phone" value="{{ old('phone', $data->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Nomor WhatsApp <span style="color: #ef4444; font-style: italic;">(berawalan 62)</span></label>
                                    <input type="number" class="form-control @error('whatsapp') is-invalid @enderror" 
                                           name="whatsapp" value="{{ old('whatsapp', $data->whatsapp) }}">
                                    @error('whatsapp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">API URL</label>
                                    <input type="text" class="form-control @error('api_url') is-invalid @enderror" 
                                           name="api_url" value="{{ old('api_url', $data->api_url) }}">
                                    @error('api_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">API Key WhatsApp</label>
                                    <input type="text" class="form-control @error('api_whatsapp') is-invalid @enderror" 
                                           name="api_whatsapp" value="{{ old('api_whatsapp', $data->api_whatsapp) }}">
                                    @error('api_whatsapp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Logo</label>
                                    <div class="d-flex align-items-center gap-3">
                                        @if($data->logo)
                                            <img src="{{ asset('/storage/'.$data->logo) }}" class="logo-preview" alt="Logo">
                                        @endif
                                        <input type="file" class="form-control @error('logo') is-invalid @enderror" 
                                               name="logo" accept="image/*">
                                    </div>
                                    @error('logo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Theme Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h5><i class="fa fa-palette"></i> Tampilan & Tema</h5>
                    </div>
                    <div class="settings-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Warna Tema</label>
                                    <div class="color-swatches">
                                        <div class="color-swatch blue {{ ($data->theme_color ?? '#4361ee') == '#4361ee' ? 'active' : '' }}" 
                                             data-color="#4361ee" onclick="selectColor(this, '#4361ee')"></div>
                                        <div class="color-swatch purple {{ ($data->theme_color ?? '') == '#7c3aed' ? 'active' : '' }}" 
                                             data-color="#7c3aed" onclick="selectColor(this, '#7c3aed')"></div>
                                        <div class="color-swatch green {{ ($data->theme_color ?? '') == '#10b981' ? 'active' : '' }}" 
                                             data-color="#10b981" onclick="selectColor(this, '#10b981')"></div>
                                        <div class="color-swatch orange {{ ($data->theme_color ?? '') == '#f59e0b' ? 'active' : '' }}" 
                                             data-color="#f59e0b" onclick="selectColor(this, '#f59e0b')"></div>
                                        <div class="color-swatch red {{ ($data->theme_color ?? '') == '#ef4444' ? 'active' : '' }}" 
                                             data-color="#ef4444" onclick="selectColor(this, '#ef4444')"></div>
                                        <div class="color-swatch teal {{ ($data->theme_color ?? '') == '#14b8a6' ? 'active' : '' }}" 
                                             data-color="#14b8a6" onclick="selectColor(this, '#14b8a6')"></div>
                                        <div class="color-swatch pink {{ ($data->theme_color ?? '') == '#ec4899' ? 'active' : '' }}" 
                                             data-color="#ec4899" onclick="selectColor(this, '#ec4899')"></div>
                                        <div class="color-swatch indigo {{ ($data->theme_color ?? '') == '#6366f1' ? 'active' : '' }}" 
                                             data-color="#6366f1" onclick="selectColor(this, '#6366f1')"></div>
                                    </div>
                                    <input type="hidden" name="theme_color" id="theme_color" value="{{ $data->theme_color ?? '#4361ee' }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Mode Tampilan</label>
                                    <div class="theme-toggle-wrapper">
                                        <div class="toggle-option light-mode {{ ($data->theme_mode ?? 'light') == 'light' ? 'active' : '' }}" 
                                             onclick="selectMode(this, 'light')">
                                            <i class="fa fa-sun"></i>
                                            <span>Light</span>
                                        </div>
                                        <div class="toggle-option dark-mode {{ ($data->theme_mode ?? '') == 'dark' ? 'active' : '' }}" 
                                             onclick="selectMode(this, 'dark')">
                                            <i class="fa fa-moon"></i>
                                            <span>Dark</span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="theme_mode" id="theme_mode" value="{{ $data->theme_mode ?? 'light' }}">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="preview-card">
                                    <h6>Preview</h6>
                                    <div class="preview-elements">
                                        <button type="button" class="preview-btn primary" id="preview-primary">Primary Button</button>
                                        <button type="button" class="preview-btn outline" id="preview-outline">Outline Button</button>
                                        <span class="preview-badge" id="preview-badge">Badge</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
                
                <!-- BPJS Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h5><i class="fa fa-shield-alt"></i> Pengaturan BPJS Ketenagakerjaan</h5>
                    </div>
                    <div class="settings-card-body">
                        <p class="text-muted mb-4">
                            <i class="fa fa-info-circle me-1"></i> 
                            Atur persentase iuran BPJS yang akan dihitung otomatis pada slip gaji berdasarkan gaji pokok.
                        </p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa fa-user me-1 text-primary"></i>
                                        BPJS JHT Karyawan (%)
                                    </label>
                                    <input type="number" step="0.01" min="0" max="100" 
                                           class="form-control @error('bpjs_jht_karyawan_persen') is-invalid @enderror" 
                                           name="bpjs_jht_karyawan_persen" 
                                           value="{{ old('bpjs_jht_karyawan_persen', $data->bpjs_jht_karyawan_persen ?? 2.00) }}"
                                           placeholder="2.00">
                                    <small class="text-muted">Default: 2% (Ditanggung Karyawan)</small>
                                    @error('bpjs_jht_karyawan_persen')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa fa-building me-1 text-success"></i>
                                        BPJS JHT Perusahaan (%)
                                    </label>
                                    <input type="number" step="0.01" min="0" max="100" 
                                           class="form-control @error('bpjs_jht_perusahaan_persen') is-invalid @enderror" 
                                           name="bpjs_jht_perusahaan_persen" 
                                           value="{{ old('bpjs_jht_perusahaan_persen', $data->bpjs_jht_perusahaan_persen ?? 3.70) }}"
                                           placeholder="3.70">
                                    <small class="text-muted">Default: 3.7% (Ditanggung Perusahaan)</small>
                                    @error('bpjs_jht_perusahaan_persen')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa fa-briefcase-medical me-1 text-warning"></i>
                                        BPJS JKK Perusahaan (%)
                                    </label>
                                    <input type="number" step="0.01" min="0" max="100" 
                                           class="form-control @error('bpjs_jkk_persen') is-invalid @enderror" 
                                           name="bpjs_jkk_persen" 
                                           value="{{ old('bpjs_jkk_persen', $data->bpjs_jkk_persen ?? 0.24) }}"
                                           placeholder="0.24">
                                    <small class="text-muted">Default: 0.24% (Jaminan Kecelakaan Kerja)</small>
                                    @error('bpjs_jkk_persen')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa fa-heart me-1 text-danger"></i>
                                        BPJS JKM Perusahaan (%)
                                    </label>
                                    <input type="number" step="0.01" min="0" max="100" 
                                           class="form-control @error('bpjs_jkm_persen') is-invalid @enderror" 
                                           name="bpjs_jkm_persen" 
                                           value="{{ old('bpjs_jkm_persen', $data->bpjs_jkm_persen ?? 0.30) }}"
                                           placeholder="0.30">
                                    <small class="text-muted">Default: 0.3% (Jaminan Kematian)</small>
                                    @error('bpjs_jkm_persen')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- BPJS Preview Calculator -->
                        <div class="preview-card mt-3">
                            <h6><i class="fa fa-calculator me-1"></i> Simulasi Perhitungan (Gaji Pokok Rp 5.000.000)</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center p-2">
                                        <small class="text-muted d-block">JHT Karyawan</small>
                                        <strong class="text-primary" id="bpjs-preview-jht-karyawan">Rp 100.000</strong>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-2">
                                        <small class="text-muted d-block">JHT Perusahaan</small>
                                        <strong class="text-success" id="bpjs-preview-jht-perusahaan">Rp 185.000</strong>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-2">
                                        <small class="text-muted d-block">JKK Perusahaan</small>
                                        <strong class="text-warning" id="bpjs-preview-jkk">Rp 12.000</strong>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-2">
                                        <small class="text-muted d-block">JKM Perusahaan</small>
                                        <strong class="text-danger" id="bpjs-preview-jkm">Rp 15.000</strong>
                                    </div>
                                </div>
                            </div>
                    </div>
                </div>
                
                <!-- Attendance Security Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h5><i class="fa fa-lock"></i> Pengaturan Keamanan Absensi</h5>
                    </div>
                    <div class="settings-card-body">
                        <p class="text-muted mb-4">
                            <i class="fa fa-info-circle me-1"></i> 
                            Atur keamanan absensi untuk memastikan karyawan absen dari lokasi yang benar.
                        </p>
                        
                        <div class="row">
                            <!-- IP Restriction -->
                            <div class="col-12 mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_ip_restriction" 
                                           name="enable_ip_restriction" value="1" 
                                           {{ old('enable_ip_restriction', $data->enable_ip_restriction ?? false) ? 'checked' : '' }}
                                           onchange="toggleIpSettings()">
                                    <label class="form-check-label fw-bold" for="enable_ip_restriction">
                                        <i class="fa fa-wifi me-1 text-primary"></i>
                                        Aktifkan Pembatasan IP WiFi Kantor
                                    </label>
                                </div>
                                <small class="text-muted">Karyawan hanya bisa absen jika terhubung ke jaringan kantor</small>
                            </div>
                            
                            <div id="ip-settings" style="{{ old('enable_ip_restriction', $data->enable_ip_restriction ?? false) ? '' : 'display: none;' }}">
                                <div class="col-12 mb-3">
                                    <label class="form-label">IP Address yang Diizinkan</label>
                                    <div id="ip-list">
                                        @php
                                            $allowedIps = json_decode($data->allowed_ip_addresses ?? '[]', true) ?? [];
                                        @endphp
                                        @forelse($allowedIps as $ip)
                                        <div class="input-group mb-2 ip-entry">
                                            <input type="text" class="form-control" name="ip_addresses[]" value="{{ $ip }}" placeholder="Contoh: 192.168.1.1">
                                            <button type="button" class="btn btn-outline-danger" onclick="removeIp(this)">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                        @empty
                                        <div class="input-group mb-2 ip-entry">
                                            <input type="text" class="form-control" name="ip_addresses[]" placeholder="Contoh: 192.168.1.1">
                                            <button type="button" class="btn btn-outline-danger" onclick="removeIp(this)">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                        @endforelse
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addIpField()">
                                        <i class="fa fa-plus me-1"></i> Tambah IP
                                    </button>
                                    <small class="d-block text-muted mt-2">
                                        <i class="fa fa-info-circle me-1"></i> 
                                        IP Publik Anda saat ini: <strong>{{ request()->ip() }}</strong>
                                    </small>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label class="form-label">Pesan Jika Diluar Jaringan</label>
                                    <input type="text" class="form-control" name="ip_restriction_message" 
                                           value="{{ old('ip_restriction_message', $data->ip_restriction_message ?? 'Anda harus terhubung ke WiFi Kantor untuk absensi') }}"
                                           placeholder="Pesan error yang ditampilkan">
                                </div>
                            </div>
                            
                            <!-- Daily QR Code -->
                            <div class="col-12 mb-4 mt-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_daily_qr" 
                                           name="enable_daily_qr" value="1" 
                                           {{ old('enable_daily_qr', $data->enable_daily_qr ?? false) ? 'checked' : '' }}
                                           onchange="toggleQrSettings()">
                                    <label class="form-check-label fw-bold" for="enable_daily_qr">
                                        <i class="fa fa-qrcode me-1 text-success"></i>
                                        Aktifkan QR Code Harian
                                    </label>
                                </div>
                                <small class="text-muted">Karyawan harus scan QR Code yang berubah otomatis</small>
                            </div>
                            
                            <div id="qr-settings" style="{{ old('enable_daily_qr', $data->enable_daily_qr ?? false) ? '' : 'display: none;' }}">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Rotasi QR Code</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="qr_rotation" 
                                                   id="qr_daily" value="daily" 
                                                   {{ old('qr_rotation', $data->qr_rotation ?? 'daily') == 'daily' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="qr_daily">
                                                <i class="fa fa-calendar-day me-1"></i> Setiap Hari
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="qr_rotation" 
                                                   id="qr_hourly" value="hourly"
                                                   {{ old('qr_rotation', $data->qr_rotation ?? 'daily') == 'hourly' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="qr_hourly">
                                                <i class="fa fa-clock me-1"></i> Setiap Jam
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <a href="{{ url('/attendance/qr-display') }}" class="btn btn-success" target="_blank">
                                        <i class="fa fa-qrcode me-1"></i> Lihat QR Code Hari Ini
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="text-end">
                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    @push('script')
    <script>
        function selectColor(element, color) {
            // Remove active class from all swatches
            document.querySelectorAll('.color-swatch').forEach(swatch => {
                swatch.classList.remove('active');
            });
            
            // Add active class to selected swatch
            element.classList.add('active');
            
            // Update hidden input
            document.getElementById('theme_color').value = color;
            
            // Update preview
            updatePreview(color);
        }
        
        function selectMode(element, mode) {
            // Remove active class from all options
            document.querySelectorAll('.toggle-option').forEach(option => {
                option.classList.remove('active');
            });
            
            // Add active class to selected option
            element.classList.add('active');
            
            // Update hidden input
            document.getElementById('theme_mode').value = mode;
        }
        
        function updatePreview(color) {
            // Update preview elements
            const primaryBtn = document.getElementById('preview-primary');
            const outlineBtn = document.getElementById('preview-outline');
            const badge = document.getElementById('preview-badge');
            
            primaryBtn.style.background = color;
            outlineBtn.style.color = color;
            outlineBtn.style.borderColor = color;
            badge.style.color = color;
            badge.style.background = hexToRgba(color, 0.15);
        }
        
        function hexToRgba(hex, alpha) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
        
        // Initialize preview with current color
        document.addEventListener('DOMContentLoaded', function() {
            const currentColor = document.getElementById('theme_color').value || '#4361ee';
            updatePreview(currentColor);
        });
        
        // Attendance Security Functions
        function toggleIpSettings() {
            const checkbox = document.getElementById('enable_ip_restriction');
            const settings = document.getElementById('ip-settings');
            settings.style.display = checkbox.checked ? '' : 'none';
        }
        
        function toggleQrSettings() {
            const checkbox = document.getElementById('enable_daily_qr');
            const settings = document.getElementById('qr-settings');
            settings.style.display = checkbox.checked ? '' : 'none';
        }
        
        function addIpField() {
            const ipList = document.getElementById('ip-list');
            const newEntry = document.createElement('div');
            newEntry.className = 'input-group mb-2 ip-entry';
            newEntry.innerHTML = `
                <input type="text" class="form-control" name="ip_addresses[]" placeholder="Contoh: 192.168.1.1">
                <button type="button" class="btn btn-outline-danger" onclick="removeIp(this)">
                    <i class="fa fa-times"></i>
                </button>
            `;
            ipList.appendChild(newEntry);
        }
        
        function removeIp(button) {
            const entry = button.closest('.ip-entry');
            const ipList = document.getElementById('ip-list');
            if (ipList.querySelectorAll('.ip-entry').length > 1) {
                entry.remove();
            } else {
                entry.querySelector('input').value = '';
            }
        }
    </script>
    @endpush
@endsection
