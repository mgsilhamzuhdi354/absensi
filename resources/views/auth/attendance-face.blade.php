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
    .back-link:hover { color: #667eea; }
    
    .page-title {
        font-size: 1.5rem; font-weight: 700; color: #1e293b;
        text-align: center; margin-bottom: 10px;
    }
    
    .page-subtitle {
        font-size: 0.9rem; color: #64748b; text-align: center;
        margin-bottom: 25px;
    }
    
    .form-group { margin-bottom: 20px; }
    .form-label {
        display: block; margin-bottom: 8px; font-weight: 600;
        color: #334155; font-size: 0.9rem;
    }
    
    .form-control {
        width: 100%; padding: 14px 16px;
        border: 2px solid #e2e8f0; border-radius: 12px;
        font-size: 16px; background-color: #f8fafc; color: #1e293b;
    }
    .form-control:focus {
        border-color: #667eea; outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }
    
    .video-container {
        width: 100%; height: 280px; background: #1e293b;
        border-radius: 16px; overflow: hidden; position: relative;
        margin-bottom: 20px;
    }
    
    #video { width: 100%; height: 100%; object-fit: cover; }
    
    .video-overlay {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 0.9rem; background: rgba(0,0,0,0.5);
    }
    
    .btn-group { display: flex; gap: 10px; margin-bottom: 15px; }
    
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
    
    .hidden { display: none; }
    
    @media (max-width: 450px) {
        .attendance-card { padding: 20px 18px; }
        .video-container { height: 220px; }
    }
</style>

<div class="landing-bg"></div>

<div class="attendance-container">
    <div class="attendance-card">
        <a href="{{ url('/') }}" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        
        <h1 class="page-title">
            <i class="fas fa-user-circle" style="color: #667eea;"></i> Face Recognition
        </h1>
        <p class="page-subtitle">Absen menggunakan pengenalan wajah</p>
        
        <div class="form-group">
            <label class="form-label">Username / NIP</label>
            <input type="text" class="form-control" id="username" placeholder="Masukkan username">
        </div>
        
        <div class="video-container">
            <video id="video" autoplay playsinline></video>
            <div class="video-overlay" id="videoOverlay">
                <span><i class="fas fa-camera"></i> Klik tombol di bawah untuk mulai</span>
            </div>
        </div>
        
        <canvas id="canvas" class="hidden"></canvas>
        
        <div class="btn-group">
            <button class="btn btn-masuk" onclick="absen('masuk')">
                <i class="fas fa-sign-in-alt"></i> Absen Masuk
            </button>
            <button class="btn btn-pulang" onclick="absen('pulang')">
                <i class="fas fa-sign-out-alt"></i> Absen Pulang
            </button>
        </div>
        
        <div class="status-message" id="statusMessage"></div>
        
        <input type="hidden" id="lat">
        <input type="hidden" id="long">
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let stream = null;
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const overlay = document.getElementById('videoOverlay');
    const statusMsg = document.getElementById('statusMessage');
    
    // Start camera on page load
    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'user', width: 640, height: 480 } 
            });
            video.srcObject = stream;
            overlay.style.display = 'none';
        } catch (err) {
            overlay.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Kamera tidak dapat diakses</span>';
        }
    }
    
    startCamera();
    
    // Get location
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById('lat').value = position.coords.latitude;
                document.getElementById('long').value = position.coords.longitude;
            });
        }
    }
    getLocation();
    setInterval(getLocation, 5000);
    
    function showStatus(message, type) {
        statusMsg.textContent = message;
        statusMsg.className = 'status-message status-' + type;
    }
    
    function absen(type) {
        const username = document.getElementById('username').value.trim();
        
        if (!username) {
            Swal.fire('Oops!', 'Masukkan username terlebih dahulu', 'warning');
            return;
        }
        
        if (!stream) {
            Swal.fire('Oops!', 'Kamera belum aktif', 'warning');
            return;
        }
        
        showStatus('Memproses absensi...', 'loading');
        
        // Capture image
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        const imageData = canvas.toDataURL('image/png');
        
        const url = type === 'masuk' ? '/attendance/face/masuk' : '/attendance/face/pulang';
        
        fetch('{{ url("") }}' + url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                username: username,
                lat: document.getElementById('lat').value,
                long: document.getElementById('long').value,
                image: imageData
            })
        })
        .then(res => res.json())
        .then(data => {
            // Handle ip_blocked response (object with status and message)
            if (typeof data === 'object' && data.status === 'ip_blocked') {
                Swal.fire('Akses Ditolak', data.message || 'Anda harus terhubung ke WiFi Kantor untuk absensi', 'error');
                showStatus(data.message || 'IP tidak diizinkan', 'error');
                return;
            }
            
            if (data === 'masuk') {
                Swal.fire({
                    icon: 'success',
                    title: 'Absen Masuk Berhasil!',
                    text: 'Selamat bekerja!',
                    confirmButtonColor: '#667eea'
                });
                showStatus('✓ Absen masuk berhasil!', 'success');
            } else if (data === 'pulang') {
                Swal.fire({
                    icon: 'success', 
                    title: 'Absen Pulang Berhasil!',
                    text: 'Hati-hati di jalan!',
                    confirmButtonColor: '#11998e'
                });
                showStatus('✓ Absen pulang berhasil!', 'success');
            } else if (data === 'selesai') {
                Swal.fire('Info', 'Anda sudah absen hari ini', 'info');
                showStatus('Sudah absen hari ini', 'error');
            } else if (data === 'noMs') {
                Swal.fire('Info', 'Tidak ada jadwal shift untuk hari ini', 'info');
                showStatus('Tidak ada jadwal shift', 'error');
            } else if (data === 'noUser') {
                Swal.fire('Error', 'Username tidak ditemukan', 'error');
                showStatus('Username tidak ditemukan', 'error');
            } else if (data === 'outlocation') {
                Swal.fire('Error', 'Anda berada di luar area kantor', 'error');
                showStatus('Di luar area kantor', 'error');
            } else {
                showStatus('Gagal: ' + data, 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Terjadi kesalahan: ' + err.message, 'error');
            showStatus('Error: ' + err.message, 'error');
        });
    }
</script>
@endsection
