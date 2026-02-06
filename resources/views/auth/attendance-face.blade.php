@extends('templates.login')
@section('container')
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .landing-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(-45deg, #0f0c29, #302b63, #24243e);
            z-index: -1;
        }

        .attendance-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .attendance-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 25px 20px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            text-align: center;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 15px;
        }

        .ml-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }

        .video-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto 12px auto;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            background: #1e293b;
        }

        #video {
            width: 100%;
            height: auto;
            display: block;
            transform: scaleX(-1);
        }

        #faceCanvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            transform: scaleX(-1);
            pointer-events: none;
        }

        .debug-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 8px;
            font-size: 11px;
            color: #6c757d;
            margin-bottom: 10px;
            text-align: center;
        }

        .user-info {
            background: linear-gradient(135deg, #d1e7dd, #c3e6cb);
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 12px;
            text-align: center;
            display: none;
        }

        .user-info.active {
            display: block;
        }

        .user-info .name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f5132;
        }

        .user-info .match-score {
            font-size: 0.8rem;
            color: #198754;
        }

        .searching {
            background: #e0e7ff;
            border-radius: 12px;
            padding: 12px 15px;
            text-align: center;
            color: #3730a3;
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-masuk {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-pulang {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
        }

        .status-message {
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
            font-size: 0.85rem;
            display: none;
        }

        .status-success {
            background: #d1fae5;
            color: #065f46;
            display: block;
        }

        .status-error {
            background: #fee2e2;
            color: #991b1b;
            display: block;
        }

        .status-loading {
            background: #e0e7ff;
            color: #3730a3;
            display: block;
        }

        .hidden {
            display: none !important;
        }
    </style>

    <div class="landing-bg"></div>

    <div class="attendance-container">
        <div class="attendance-card">
            <a href="{{ url('/') }}" class="back-link">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>

            <h1 class="page-title">
                <i class="fas fa-user-check" style="color: #667eea;"></i> Face Recognition
            </h1>
            <p class="page-subtitle">Arahkan wajah ke kamera untuk absen</p>

            <div class="text-center">
                <span class="ml-badge">
                    <i class="fas fa-brain"></i> Auto-Detect ML
                </span>
            </div>

            <div class="video-container">
                <video id="video" autoplay playsinline muted></video>
                <canvas id="faceCanvas"></canvas>
            </div>

            <div class="debug-info" id="debugInfo">
                Users: {{ count($faceUsers) }} | Waiting...
            </div>

            <div class="searching" id="searchingIndicator">
                <i class="fas fa-search"></i> Mencari wajah...
            </div>

            <div class="user-info" id="userInfo">
                <div class="name" id="userName">-</div>
                <div class="match-score" id="matchScore">-</div>
            </div>

            <canvas id="canvas" class="hidden"></canvas>

            <div class="btn-group">
                <button class="btn btn-masuk" id="btnMasuk" onclick="absen('masuk')" disabled>
                    <i class="fas fa-sign-in-alt"></i> Absen Masuk
                </button>
                <button class="btn btn-pulang" id="btnPulang" onclick="absen('pulang')" disabled>
                    <i class="fas fa-sign-out-alt"></i> Absen Pulang
                </button>
            </div>

            <div class="status-message" id="statusMessage"></div>

            <input type="hidden" id="lat">
            <input type="hidden" id="long">
        </div>
    </div>

    <script src="{{ url('/face/dist/face-api.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const MODEL_URL = '{{ url("/face/weights") }}';
        const MATCH_THRESHOLD = 50;

        // Data langsung dari PHP - tidak perlu API call
        const FACE_USERS_RAW = @json($faceUsers);

        let stream = null;
        let modelsLoaded = false;
        let allUsers = [];
        let detectedUser = null;
        let lastMatchScore = 0;
        let detectionInterval = null;

        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const faceCanvas = document.getElementById('faceCanvas');
        const debugInfo = document.getElementById('debugInfo');
        const userInfo = document.getElementById('userInfo');
        const searchingIndicator = document.getElementById('searchingIndicator');
        const btnMasuk = document.getElementById('btnMasuk');
        const btnPulang = document.getElementById('btnPulang');
        const statusMsg = document.getElementById('statusMessage');

        async function init() {
            try {
                debugInfo.textContent = 'Loading ML models...';

                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);

                modelsLoaded = true;
                console.log('ML models loaded');

                // Parse face users from embedded data
                parseUsers();

                await startCamera();

            } catch (error) {
                console.error('Init error:', error);
                debugInfo.textContent = 'Error: ' + error.message;
            }
        }

        function parseUsers() {
            console.log('Raw users from PHP:', FACE_USERS_RAW);
            console.log('Total raw users:', FACE_USERS_RAW.length);

            allUsers = [];

            for (const user of FACE_USERS_RAW) {
                try {
                    let descriptors = null;
                    let rawDesc = user.face_descriptor;

                    // Parse if string
                    if (typeof rawDesc === 'string') {
                        rawDesc = JSON.parse(rawDesc);
                    }

                    console.log('Parsing user:', user.name, 'type:', typeof rawDesc);

                    if (rawDesc && rawDesc.descriptors && Array.isArray(rawDesc.descriptors)) {
                        descriptors = rawDesc.descriptors.map(d => new Float32Array(d));
                        console.log('Found', descriptors.length, 'descriptors for', user.name);
                    } else if (rawDesc && rawDesc.average && Array.isArray(rawDesc.average)) {
                        descriptors = [new Float32Array(rawDesc.average)];
                        console.log('Found average descriptor for', user.name);
                    } else if (Array.isArray(rawDesc)) {
                        descriptors = [new Float32Array(rawDesc)];
                        console.log('Found direct array for', user.name);
                    }

                    if (descriptors && descriptors.length > 0) {
                        allUsers.push({
                            id: user.id,
                            name: user.name,
                            username: user.username,
                            descriptors: descriptors
                        });
                    }
                } catch (e) {
                    console.error('Parse error for', user.name, e);
                }
            }

            console.log('Parsed users with descriptors:', allUsers.length);
            debugInfo.textContent = `Users: ${allUsers.length} | Waiting...`;

            if (allUsers.length > 0) {
                startDetection();
            } else {
                debugInfo.textContent = 'No registered faces found';
            }
        }

        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 360 } },
                    audio: false
                });
                video.srcObject = stream;

                await new Promise(resolve => video.onloadedmetadata = resolve);

                setTimeout(() => {
                    faceCanvas.width = video.offsetWidth;
                    faceCanvas.height = video.offsetHeight;
                }, 100);
            } catch (err) {
                console.error('Camera error:', err);
                debugInfo.textContent = 'Camera error';
            }
        }

        function startDetection() {
            detectionInterval = setInterval(async () => {
                if (!modelsLoaded || !stream || allUsers.length === 0) return;

                try {
                    const detection = await faceapi
                        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
                            inputSize: 224,
                            scoreThreshold: 0.4
                        }))
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    const ctx = faceCanvas.getContext('2d');
                    ctx.clearRect(0, 0, faceCanvas.width, faceCanvas.height);

                    if (detection) {
                        const scaleX = faceCanvas.width / video.videoWidth;
                        const scaleY = faceCanvas.height / video.videoHeight;

                        const box = detection.detection.box;
                        ctx.strokeStyle = '#00ff00';
                        ctx.lineWidth = 2;
                        ctx.strokeRect(box.x * scaleX, box.y * scaleY, box.width * scaleX, box.height * scaleY);

                        const landmarks = detection.landmarks.positions;
                        ctx.fillStyle = '#00ffff';
                        landmarks.forEach(point => {
                            ctx.beginPath();
                            ctx.arc(point.x * scaleX, point.y * scaleY, 2, 0, 2 * Math.PI);
                            ctx.fill();
                        });

                        const match = findBestMatch(detection.descriptor);

                        debugInfo.textContent = `Users: ${allUsers.length} | Best: ${match.score.toFixed(1)}%`;

                        if (match.score >= MATCH_THRESHOLD) {
                            detectedUser = match.user;
                            lastMatchScore = match.score;

                            document.getElementById('userName').textContent = match.user.name;
                            document.getElementById('matchScore').innerHTML =
                                '<i class="fas fa-check-circle"></i> Match: ' + match.score.toFixed(1) + '%';

                            userInfo.classList.add('active');
                            searchingIndicator.style.display = 'none';

                            btnMasuk.disabled = false;
                            btnPulang.disabled = false;
                        } else {
                            detectedUser = null;
                            userInfo.classList.remove('active');
                            searchingIndicator.style.display = 'block';
                            searchingIndicator.innerHTML = '<i class="fas fa-search"></i> Match: ' + match.score.toFixed(1) + '% (need ' + MATCH_THRESHOLD + '%)';
                            btnMasuk.disabled = true;
                            btnPulang.disabled = true;
                        }
                    } else {
                        detectedUser = null;
                        userInfo.classList.remove('active');
                        searchingIndicator.style.display = 'block';
                        searchingIndicator.innerHTML = '<i class="fas fa-search"></i> Mencari wajah...';
                        btnMasuk.disabled = true;
                        btnPulang.disabled = true;
                    }
                } catch (e) {
                    console.warn('Detection error:', e);
                }
            }, 400);
        }

        function findBestMatch(liveDescriptor) {
            let bestMatch = { user: null, score: 0 };

            for (const user of allUsers) {
                for (const refDesc of user.descriptors) {
                    try {
                        const distance = faceapi.euclideanDistance(refDesc, liveDescriptor);
                        const score = (1 - distance) * 100;

                        if (score > bestMatch.score) {
                            bestMatch = { user: user, score: score };
                        }
                    } catch (e) { }
                }
            }

            return bestMatch;
        }

        // Location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                pos => {
                    document.getElementById('lat').value = pos.coords.latitude;
                    document.getElementById('long').value = pos.coords.longitude;
                },
                err => { }
            );
        }

        function showStatus(message, type) {
            statusMsg.textContent = message;
            statusMsg.className = 'status-message status-' + type;
        }

        function absen(type) {
            if (!detectedUser) {
                showStatus('Wajah belum dikenali', 'error');
                return;
            }

            showStatus('Memproses...', 'loading');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0);
            const imageData = canvas.toDataURL('image/png');

            const url = type === 'masuk' ? '/attendance/face/masuk' : '/attendance/face/pulang';

            fetch('{{ url("") }}' + url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    username: detectedUser.username,
                    lat: document.getElementById('lat').value,
                    long: document.getElementById('long').value,
                    image: imageData,
                    match_score: lastMatchScore.toFixed(2),
                    verified: true
                })
            })
                .then(res => res.text())
                .then(text => {
                    let data;
                    try { data = JSON.parse(text); } catch (e) { data = text; }

                    if (typeof data === 'object' && data.status === 'ip_blocked') {
                        Swal.fire('Akses Ditolak', data.message, 'warning');
                        return;
                    }

                    if (data === 'masuk') {
                        Swal.fire({ icon: 'success', title: 'Absen Masuk Berhasil!', html: '<strong>' + detectedUser.name + '</strong>' });
                        showStatus('Berhasil!', 'success');
                    } else if (data === 'pulang') {
                        Swal.fire({ icon: 'success', title: 'Absen Pulang Berhasil!', html: '<strong>' + detectedUser.name + '</strong>' });
                        showStatus('Berhasil!', 'success');
                    } else if (data === 'selesai') {
                        Swal.fire('Info', 'Sudah absen hari ini', 'info');
                    } else if (data === 'noMs') {
                        Swal.fire('Info', 'Tidak ada jadwal shift', 'info');
                    } else if (data === 'noUser') {
                        Swal.fire('Info', 'User tidak ditemukan', 'warning');
                    } else if (data === 'outlocation') {
                        Swal.fire('Info', 'Di luar area kantor', 'warning');
                    } else if (data === 'tooEarly') {
                        Swal.fire('Belum Waktunya', 'Max 30 menit sebelum shift', 'warning');
                    } else if (data === 'notClockedIn') {
                        Swal.fire('Info', 'Harus absen masuk dulu', 'warning');
                    } else {
                        showStatus('OK', 'success');
                    }
                })
                .catch(err => {
                    showStatus('Gagal: ' + err.message, 'error');
                });
        }

        init();
    </script>
@endsection