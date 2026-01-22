@extends('templates.login')
@section('container')
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    
    .landing-bg {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(-45deg, #0f0c29, #302b63, #24243e);
        z-index: -1;
    }
    
    .attendance-container {
        min-height: 100vh; display: flex; flex-direction: column;
        align-items: center; justify-content: center; padding: 20px;
    }
    
    .attendance-card {
        background: rgba(255, 255, 255, 0.95); border-radius: 24px;
        padding: 30px 25px; width: 100%; max-width: 400px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .back-link {
        display: inline-flex; align-items: center; gap: 8px;
        color: #64748b; text-decoration: none; margin-bottom: 20px;
        font-size: 0.9rem;
    }
    .back-link:hover { color: #11998e; }
    
    .page-title {
        font-size: 1.5rem; font-weight: 700; color: #1e293b;
        text-align: center; margin-bottom: 10px;
    }
    
    .page-subtitle {
        font-size: 0.9rem; color: #64748b; text-align: center;
        margin-bottom: 25px;
    }
    
    .scanner-container {
        width: 100%; height: 320px; background: #1e293b;
        border-radius: 16px; overflow: hidden; position: relative;
        margin-bottom: 20px;
    }
    
    #reader { width: 100%; height: 100%; }
    #reader video { object-fit: cover !important; }
    
    .scan-overlay {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 200px; height: 200px;
        border: 3px solid #38ef7d;
        border-radius: 16px;
        animation: scan-pulse 2s ease-in-out infinite;
        pointer-events: none;
        z-index: 10;
    }
    
    @keyframes scan-pulse {
        0%, 100% { border-color: #38ef7d; box-shadow: 0 0 20px rgba(56, 239, 125, 0.3); }
        50% { border-color: #11998e; box-shadow: 0 0 30px rgba(17, 153, 142, 0.5); }
    }
    
    .scan-line {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 180px; height: 2px;
        background: linear-gradient(90deg, transparent, #38ef7d, transparent);
        animation: scan-line 2s ease-in-out infinite;
        pointer-events: none;
        z-index: 11;
    }
    
    @keyframes scan-line {
        0% { transform: translate(-50%, -100px); }
        50% { transform: translate(-50%, 100px); }
        100% { transform: translate(-50%, -100px); }
    }
    
    .btn-action {
        display: flex; gap: 10px; margin-bottom: 15px;
    }
    
    .btn {
        flex: 1; padding: 14px; border: none; border-radius: 12px;
        font-size: 0.95rem; font-weight: 600; cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-masuk {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    .btn-masuk:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
    
    .btn-pulang {
        background: linear-gradient(135deg, #11998e, #38ef7d);
        color: white;
    }
    .btn-pulang:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(17, 153, 142, 0.4); }
    
    .status-message {
        padding: 12px; border-radius: 10px; text-align: center;
        font-weight: 500; font-size: 0.9rem; display: none;
    }
    .status-success { background: #d1fae5; color: #065f46; display: block; }
    .status-error { background: #fee2e2; color: #991b1b; display: block; }
    .status-loading { background: #e0e7ff; color: #3730a3; display: block; }
    .status-info { background: #fef3c7; color: #92400e; display: block; }
    
    .instruction {
        background: #f0fdf4; border: 1px solid #86efac;
        border-radius: 10px; padding: 12px; margin-bottom: 20px;
        text-align: center; font-size: 0.85rem; color: #166534;
    }
    
    .scanned-user {
        background: #eff6ff; border: 1px solid #93c5fd;
        border-radius: 10px; padding: 15px; margin-bottom: 15px;
        text-align: center;
    }
    .scanned-user .name {
        font-size: 1.1rem; font-weight: 700; color: #1e40af;
    }
    .scanned-user .username {
        font-size: 0.85rem; color: #64748b; margin-top: 3px;
    }
    
    .hidden { display: none; }
    
    @media (max-width: 450px) {
        .attendance-card { padding: 20px 18px; }
        .scanner-container { height: 280px; }
    }
</style>

<div class="landing-bg"></div>

<div class="attendance-container">
    <div class="attendance-card">
        <a href="{{ url('/') }}" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        
        <h1 class="page-title">
            <i class="fas fa-qrcode" style="color: #11998e;"></i> QR Code Scanner
        </h1>
        <p class="page-subtitle">Scan QR Code ID Pegawai untuk absensi</p>
        
        <div class="instruction">
            <i class="fas fa-info-circle"></i> Arahkan kamera ke QR Code ID Pegawai
        </div>
        
        <div class="scanner-container">
            <div id="reader"></div>
            <div class="scan-overlay"></div>
            <div class="scan-line"></div>
        </div>
        
        <div id="scannedUserInfo" class="scanned-user hidden">
            <div class="name" id="scannedName">-</div>
            <div class="username" id="scannedUsername">-</div>
        </div>
        
        <div id="actionButtons" class="btn-action hidden">
            <button class="btn btn-masuk" onclick="processAbsen('masuk')">
                <i class="fas fa-sign-in-alt"></i> Absen Masuk
            </button>
            <button class="btn btn-pulang" onclick="processAbsen('pulang')">
                <i class="fas fa-sign-out-alt"></i> Absen Pulang
            </button>
        </div>
        
        <div class="status-message" id="statusMessage"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let scannedUsername = '';
    let html5QrcodeScanner = null;
    
    function showStatus(message, type) {
        const statusMsg = document.getElementById('statusMessage');
        statusMsg.textContent = message;
        statusMsg.className = 'status-message status-' + type;
    }
    
    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanner temporarily
        if (html5QrcodeScanner) {
            html5QrcodeScanner.pause();
        }
        
        scannedUsername = decodedText;
        
        // Show user info
        document.getElementById('scannedName').textContent = 'Username: ' + decodedText;
        document.getElementById('scannedUsername').textContent = 'QR Code berhasil di-scan';
        document.getElementById('scannedUserInfo').classList.remove('hidden');
        document.getElementById('actionButtons').classList.remove('hidden');
        
        showStatus('QR Code terdeteksi! Pilih jenis absen.', 'info');
        
        // Play beep sound
        const beep = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2teleIb2a36b+olybWmJ85l8e5OOkHtreHR0gY6VjZWKi2tubXh2eH+ViIR/c3Jxf4+JhI5/dW5ycHqAdJVvcnRudn5ydo51d3BzaGhlbXN5gXx5dW5mb3N2fnt7dG9qam1zdXt8d3RubGxucXd3fnp2cW5rb3JzdXZ2c3BtbG5wcnd3d3Rwbm1tb3Fzc3NycG5tbm9wcnNzcHBub25vcHFycnFwb25ub29wcXFxcHBvb29vcHBwcHBwb29vb29wcHBwcG9vb29vb3BwcHBwb29vb29vb3BwcHBvb29vb29vcHBwcHBvb29vb29vcHBwcHBvb29vb29vb3BwcHBwb29vb29vb29wcHBw');
        beep.play().catch(e => {});
    }
    
    function onScanFailure(error) {
        // Silent fail - keep scanning
    }
    
    function processAbsen(type) {
        if (!scannedUsername) {
            Swal.fire('Error', 'Scan QR Code terlebih dahulu', 'error');
            return;
        }
        
        showStatus('Memproses absensi...', 'loading');
        
        const url = type === 'masuk' ? '/attendance/qr/masuk' : '/attendance/qr/pulang';
        
        fetch('{{ url("") }}' + url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ username: scannedUsername })
        })
        .then(res => res.json())
        .then(data => {
            if (data === 'masuk') {
                Swal.fire({
                    icon: 'success',
                    title: 'Absen Masuk Berhasil!',
                    text: 'Selamat bekerja!',
                    confirmButtonColor: '#667eea'
                }).then(() => resetScanner());
                showStatus('✓ Absen masuk berhasil!', 'success');
            } else if (data === 'pulang') {
                Swal.fire({
                    icon: 'success',
                    title: 'Absen Pulang Berhasil!',
                    text: 'Hati-hati di jalan!',
                    confirmButtonColor: '#11998e'
                }).then(() => resetScanner());
                showStatus('✓ Absen pulang berhasil!', 'success');
            } else if (data === 'selesai') {
                Swal.fire('Info', 'Anda sudah absen hari ini', 'info').then(() => resetScanner());
                showStatus('Sudah absen hari ini', 'error');
            } else if (data === 'noMs') {
                Swal.fire('Info', 'Tidak ada jadwal shift hari ini', 'info').then(() => resetScanner());
                showStatus('Tidak ada jadwal shift', 'error');
            } else if (data === 'noUser') {
                Swal.fire('Error', 'User tidak ditemukan', 'error').then(() => resetScanner());
                showStatus('User tidak ditemukan', 'error');
            } else {
                Swal.fire('Error', 'Terjadi kesalahan', 'error').then(() => resetScanner());
                showStatus('Gagal memproses', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Terjadi kesalahan: ' + err.message, 'error');
            showStatus('Error: ' + err.message, 'error');
        });
    }
    
    function resetScanner() {
        scannedUsername = '';
        document.getElementById('scannedUserInfo').classList.add('hidden');
        document.getElementById('actionButtons').classList.add('hidden');
        document.getElementById('statusMessage').className = 'status-message';
        
        if (html5QrcodeScanner) {
            html5QrcodeScanner.resume();
        }
    }
    
    // Initialize scanner
    document.addEventListener('DOMContentLoaded', function() {
        html5QrcodeScanner = new Html5Qrcode("reader");
        
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 200, height: 200 } },
            onScanSuccess,
            onScanFailure
        ).catch(err => {
            // Try front camera if back camera fails
            html5QrcodeScanner.start(
                { facingMode: "user" },
                { fps: 10, qrbox: { width: 200, height: 200 } },
                onScanSuccess,
                onScanFailure
            ).catch(err2 => {
                showStatus('Tidak dapat mengakses kamera: ' + err2.message, 'error');
            });
        });
    });
</script>
@endsection
