@extends('templates.app')
@section('container')

<style>
.form-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px 15px;
    color: white;
}
.form-header h2 { font-size: 20px; font-weight: 700; margin: 0 0 5px; }
.form-header p  { font-size: 12px; opacity: 0.85; margin: 0; }
.form-body { padding: 15px; }
.form-card {
    background: white;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    margin-bottom: 15px;
}
.form-card .form-label {
    font-size: 13px;
    font-weight: 600;
    color: #555;
    margin-bottom: 5px;
}
.form-card .form-control,
.form-card .form-select {
    border-radius: 10px;
    border: 1.5px solid #e5e7eb;
    padding: 10px 14px;
    font-size: 14px;
    color: #333;
    transition: border-color 0.2s;
}
.form-card .form-control:focus,
.form-card .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
}
.form-card textarea { resize: none; }
.btn-submit {
    display: block;
    width: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.2s;
}
.btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }
.btn-back {
    display: flex;
    align-items: center;
    gap: 6px;
    color: white;
    font-size: 13px;
    text-decoration: none;
    margin-bottom: 8px;
    opacity: 0.9;
}
.btn-back:hover { color: white; opacity: 1; }
.upload-zone {
    border: 2px dashed #c4b5fd;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    background: #f5f3ff;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}
.upload-zone:hover { border-color: #667eea; background: #ede9fe; }
.upload-zone input[type="file"] {
    position: absolute; inset: 0;
    opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.upload-zone i   { font-size: 28px; color: #764ba2; margin-bottom: 8px; }
.upload-zone p   { font-size: 12px; color: #7c3aed; margin: 0; font-weight: 600; }
.upload-zone small { color: #9ca3af; font-size: 11px; }
.foto-preview {
    max-width: 100%; max-height: 160px;
    border-radius: 10px; margin-top: 10px;
    border: 2px solid #e5e7eb; display: none;
}
.gps-indicator {
    display: flex; align-items: center; gap: 8px;
    background: #ecfdf5; border-radius: 8px;
    padding: 8px 12px; margin-top: 8px;
    font-size: 12px; color: #065f46;
}
.gps-indicator i { color: #10b981; }
.info-box {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border-radius: 10px; padding: 12px 15px;
    font-size: 12px; color: #1e40af;
    border-left: 4px solid #3b82f6;
}
</style>

<div class="form-header">
    <a href="{{ url('/pengajuan-dinas-luar') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
    <h2><i class="fas fa-paper-plane me-2"></i>Ajukan Dinas Luar</h2>
    <p>Isi form berikut untuk mengajukan dinas luar</p>
</div>

<div class="form-body">

    {{-- Error messages --}}
    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 10px; font-size: 13px; padding: 12px 15px; margin-bottom: 15px;">
            <i class="fas fa-exclamation-triangle me-1"></i>
            @foreach($errors->all() as $err)
                {{ $err }}<br>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ url('/pengajuan-dinas-luar/store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Shift --}}
        <div class="form-card">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-clock me-1" style="color:#667eea;"></i>Pilih Shift <span class="text-danger">*</span></label>
                <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Shift --</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->nama_shift }} ({{ $s->jam_masuk }} - {{ $s->jam_keluar }})
                        </option>
                    @endforeach
                </select>
                @error('shift_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="fas fa-map-marker-alt me-1" style="color:#667eea;"></i>Lokasi Tujuan</label>
                <input type="text" name="lokasi_tujuan" class="form-control @error('lokasi_tujuan') is-invalid @enderror"
                    value="{{ old('lokasi_tujuan') }}" placeholder="Contoh: Kantor Pusat Jakarta">
                @error('lokasi_tujuan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Tanggal --}}
        <div class="form-card">
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label"><i class="fas fa-calendar me-1" style="color:#667eea;"></i>Tgl Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                        value="{{ old('tanggal_mulai', date('Y-m-d')) }}" required>
                    @error('tanggal_mulai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-6">
                    <label class="form-label"><i class="fas fa-calendar-check me-1" style="color:#667eea;"></i>Tgl Akhir <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control @error('tanggal_akhir') is-invalid @enderror"
                        value="{{ old('tanggal_akhir', date('Y-m-d')) }}" required>
                    @error('tanggal_akhir')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Alasan --}}
        <div class="form-card">
            <div class="mb-0">
                <label class="form-label"><i class="fas fa-edit me-1" style="color:#667eea;"></i>Alasan / Keperluan <span class="text-danger">*</span></label>
                <textarea name="alasan" rows="4" class="form-control @error('alasan') is-invalid @enderror"
                    placeholder="Jelaskan keperluan dinas luar Anda..." required>{{ old('alasan') }}</textarea>
                @error('alasan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Foto Bukti --}}
        <div class="form-card">
            <label class="form-label"><i class="fas fa-camera me-1" style="color:#667eea;"></i>Foto Bukti (Surat Tugas)</label>
            <div class="upload-zone" id="uploadZone">
                <input type="file" name="foto_bukti" id="foto_bukti" accept="image/*" capture="environment" onchange="previewFoto(this)">
                <i class="fas fa-cloud-upload-alt d-block"></i>
                <p id="uploadText">Tap untuk ambil foto / pilih file</p>
                <small>JPG, PNG – Maks 5MB</small>
            </div>
            <img id="fotoPreview" class="foto-preview" alt="Preview Foto">
            @error('foto_bukti')
                <div class="text-danger mt-1" style="font-size: 12px;">{{ $message }}</div>
            @enderror
        </div>

        {{-- GPS hidden --}}
        <input type="hidden" name="lat_pengajuan"  id="lat_pengajuan">
        <input type="hidden" name="long_pengajuan" id="long_pengajuan">

        {{-- GPS status indicator --}}
        <div class="gps-indicator" id="gpsStatus">
            <i class="fas fa-satellite-dish"></i>
            <span id="gpsText">Mendeteksi lokasi GPS...</span>
        </div>

        {{-- Info --}}
        <div class="info-box my-3">
            <i class="fas fa-info-circle me-1"></i>
            Setelah diajukan, admin akan meninjau dan menyetujui pengajuan Anda. Shift dinas luar akan <strong>otomatis terbuat</strong> setelah disetujui.
        </div>

        <button type="submit" class="btn-submit">
            <i class="fas fa-paper-plane me-2"></i>Kirim Pengajuan
        </button>
    </form>
</div>

@push('script')
<script>
    // GPS Detection
    function initGPS() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    document.getElementById('lat_pengajuan').value  = pos.coords.latitude;
                    document.getElementById('long_pengajuan').value = pos.coords.longitude;
                    document.getElementById('gpsText').textContent =
                        'Lokasi terdeteksi: ' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4);
                    document.getElementById('gpsStatus').style.background = '#d1fae5';
                    document.getElementById('gpsStatus').style.color = '#065f46';
                },
                function() {
                    document.getElementById('gpsText').textContent = 'Lokasi tidak dapat dideteksi.';
                    document.getElementById('gpsStatus').style.background = '#fee2e2';
                    document.getElementById('gpsStatus').style.color = '#991b1b';
                }
            );
        }
    }

    // Foto Preview
    function previewFoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('fotoPreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
                document.getElementById('uploadText').textContent = input.files[0].name;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Auto set min date
    document.getElementById('tanggal_mulai').addEventListener('change', function() {
        const val = this.value;
        const akhir = document.getElementById('tanggal_akhir');
        akhir.min = val;
        if (akhir.value < val) akhir.value = val;
    });

    // Init on load
    document.addEventListener('DOMContentLoaded', initGPS);
</script>
@endpush

@endsection
