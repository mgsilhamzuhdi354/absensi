@extends('templates.dashboard')
@section('isi')
<style>
    .qr-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 2rem;
        color: white;
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .qr-container h2 {
        margin: 0 0 0.5rem;
        font-weight: 700;
    }
    
    .qr-container .date {
        font-size: 1.1rem;
        opacity: 0.9;
    }
    
    .qr-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .qr-image-container {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        display: inline-block;
        margin: 1rem 0;
    }
    
    .qr-image-container img, 
    .qr-image-container svg {
        max-width: 250px;
        height: auto;
    }
    
    .expires-info {
        background: #f0f9ff;
        border: 1px solid #0ea5e9;
        border-radius: 10px;
        padding: 1rem;
        margin-top: 1.5rem;
    }
    
    .expires-info .label {
        color: #0369a1;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .expires-info .time {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }
    
    .action-buttons .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .fullscreen-btn {
        position: fixed;
        top: 10px;
        right: 10px;
        z-index: 9999;
        display: none;
    }
    
    .fullscreen-mode .qr-card {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9998;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 0;
    }
    
    .fullscreen-mode .qr-image-container {
        padding: 2rem;
    }
    
    .fullscreen-mode .qr-image-container img,
    .fullscreen-mode .qr-image-container svg {
        max-width: 400px;
    }
    
    .fullscreen-mode .fullscreen-btn {
        display: block;
    }
    
    .countdown {
        font-family: 'Courier New', monospace;
        font-size: 2rem;
        color: #1e293b;
        font-weight: 700;
    }
</style>

<div class="container-fluid">
    <div class="qr-container">
        <h2><i class="fa fa-qrcode me-2"></i> QR Code Absensi</h2>
        <p class="date">{{ \Carbon\Carbon::parse($code->date)->translatedFormat('l, d F Y') }}</p>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="qr-card" id="qr-card">
                <h4 class="mb-3">Scan QR Code ini untuk Absen</h4>
                
                <div class="qr-image-container" id="qr-display">
                    {{-- QR Code akan di-render di sini --}}
                    <div id="qrcode"></div>
                </div>
                
                <div class="expires-info">
                    <div class="label">
                        <i class="fa fa-clock me-1"></i> 
                        @if($settings && $settings->qr_rotation == 'hourly')
                            Reset setiap jam
                        @else
                            Berlaku hari ini sampai
                        @endif
                    </div>
                    <div class="time">
                        <span id="expires-time">{{ $code->expires_at ? $code->expires_at->format('H:i') : '23:59' }}</span>
                    </div>
                    <div class="countdown" id="countdown"></div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-outline-primary" onclick="toggleFullscreen()">
                        <i class="fa fa-expand"></i> Fullscreen
                    </button>
                    <form action="{{ url('/attendance/qr-regenerate') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Generate QR Code baru?')">
                            <i class="fa fa-sync-alt"></i> Generate Baru
                        </button>
                    </form>
                    <button class="btn btn-success" onclick="printQR()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <button class="btn btn-danger fullscreen-btn" onclick="toggleFullscreen()">
        <i class="fa fa-times"></i> Keluar Fullscreen
    </button>
</div>

@push('script')
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
    // Generate QR Code
    var qr = qrcode(0, 'M');
    qr.addData('{{ $code->qr_image }}');
    qr.make();
    document.getElementById('qrcode').innerHTML = qr.createSvgTag({ 
        scalable: true,
        cellSize: 8,
        margin: 4
    });
    
    // Countdown timer
    function updateCountdown() {
        var expiresTime = '{{ $code->expires_at ? $code->expires_at->format("Y-m-d H:i:s") : now()->endOfDay()->format("Y-m-d H:i:s") }}';
        var expires = new Date(expiresTime);
        var now = new Date();
        var diff = expires - now;
        
        if (diff > 0) {
            var hours = Math.floor(diff / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            document.getElementById('countdown').innerHTML = 
                String(hours).padStart(2, '0') + ':' + 
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0');
        } else {
            document.getElementById('countdown').innerHTML = 'EXPIRED';
            // Auto refresh if expired
            setTimeout(function() {
                location.reload();
            }, 2000);
        }
    }
    
    setInterval(updateCountdown, 1000);
    updateCountdown();
    
    // Fullscreen toggle
    function toggleFullscreen() {
        document.body.classList.toggle('fullscreen-mode');
    }
    
    // Print function
    function printQR() {
        var printContents = document.getElementById('qr-card').innerHTML;
        var originalContents = document.body.innerHTML;
        
        document.body.innerHTML = '<div style="text-align: center; padding: 50px;">' + printContents + '</div>';
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
    
    // Auto refresh every 5 minutes
    setInterval(function() {
        location.reload();
    }, 5 * 60 * 1000);
</script>
@endpush

@endsection
