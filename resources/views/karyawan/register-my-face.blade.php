@extends('templates.app')
@section('container')
    @push('style')
        <style>
            .registration-card {
                background: white;
                border-radius: 20px;
                padding: 15px;
                margin: 10px;
            }

            .video-wrapper {
                position: relative;
                width: 100%;
                max-width: 400px;
                margin: 0 auto;
                border-radius: 12px;
                overflow: hidden;
                background: #1e293b;
            }

            #video {
                width: 100%;
                height: auto;
                display: block;
                transform: scaleX(-1);
                /* Flip untuk non-mirror */
            }

            #overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                transform: scaleX(-1);
                /* Match video flip */
                pointer-events: none;
            }

            .step {
                display: none;
            }

            .step.active {
                display: block;
            }

            .quality-indicator {
                padding: 5px 10px;
                border-radius: 12px;
                margin: 2px;
                font-size: 11px;
                font-weight: 600;
                display: inline-block;
            }

            .quality-good {
                background: #d1e7dd;
                color: #0f5132;
            }

            .quality-bad {
                background: #f8d7da;
                color: #842029;
            }

            .capture-preview {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 6px;
                border: 2px solid #198754;
                margin: 2px;
                display: inline-block;
            }

            .progress {
                height: 22px;
                font-size: 12px;
                font-weight: 600;
            }

            .category-badge {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 8px;
                font-size: 9px;
                font-weight: 600;
            }

            .badge-angle {
                background: #cfe2ff;
                color: #084298;
            }

            .badge-distance {
                background: #fff3cd;
                color: #856404;
            }

            .badge-expression {
                background: #d1e7dd;
                color: #0f5132;
            }

            .instruction-box {
                background: #e0e7ff;
                border-radius: 10px;
                padding: 10px;
                text-align: center;
                font-weight: 600;
                color: #3730a3;
                margin: 10px 0;
            }

            @media (max-width: 480px) {
                .registration-card {
                    padding: 12px;
                    margin: 5px;
                    padding-bottom: 80px;
                }

                .capture-preview {
                    width: 40px;
                    height: 40px;
                }
            }

            /* Ensure content is not hidden behind bottom navbar */
            .tf-container {
                padding-bottom: 100px;
            }
        </style>
    @endpush

    <div class="tf-container">
        <div class="registration-card">
            <!-- Header -->
            <div class="text-center mb-2">
                <h5 class="mb-1"><i class="fas fa-user-check"></i> Daftarkan Wajah</h5>
                <small class="text-muted">Training ML dengan 12 foto</small>
            </div>

            <!-- Progress Bar -->
            <div class="progress mb-2">
                <div id="progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 0%">
                    0/12
                </div>
            </div>

            <!-- Step 1: Prepare -->
            <div id="step-prepare" class="step active">
                <div class="alert alert-info small p-2 mb-2">
                    <strong><i class="fas fa-info-circle"></i> Petunjuk:</strong>
                    <ul class="mb-0 small ps-3">
                        <li>Posisi wajah di tengah</li>
                        <li>Pencahayaan cukup</li>
                        <li>Jarak 30-50cm</li>
                        <li>Ikuti instruksi pose</li>
                    </ul>
                </div>

                <div class="text-center">
                    <button id="btn-start" class="btn btn-primary">
                        <i class="fas fa-camera"></i> Mulai Training
                    </button>
                </div>
            </div>

            <!-- Step 2: Capture -->
            <div id="step-capture" class="step">
                <!-- Quality Indicators -->
                <div class="text-center mb-2">
                    <span id="indicator-face" class="quality-indicator quality-bad">
                        <i class="fas fa-times"></i> No Face
                    </span>
                    <span id="indicator-quality" class="quality-indicator quality-bad">
                        <i class="fas fa-check"></i> Quality
                    </span>
                </div>

                <!-- Video Preview -->
                <div class="video-wrapper mb-2">
                    <video id="video" autoplay playsinline muted></video>
                    <canvas id="overlay"></canvas>
                </div>

                <!-- Instruction -->
                <div class="instruction-box">
                    <span id="capture-instruction">Posisikan wajah...</span>
                </div>

                <!-- Captured Preview -->
                <div class="text-center mb-2">
                    <div id="captures-preview"></div>
                </div>

                <!-- Capture Button -->
                <div class="text-center">
                    <button id="btn-capture" class="btn btn-success btn-lg w-100" disabled>
                        <i class="fas fa-camera"></i> Ambil (<span id="capture-count">0</span>/12)
                    </button>
                </div>
            </div>

            <!-- Step 3: Verify -->
            <div id="step-verify" class="step">
                <div class="alert alert-success text-center small p-2">
                    <i class="fas fa-check-circle"></i> <strong>Training Selesai!</strong>
                </div>

                <div class="text-center mb-2">
                    <div id="final-preview"></div>
                </div>

                <div class="text-center">
                    <button id="btn-retry" class="btn btn-secondary btn-sm me-2">
                        <i class="fas fa-redo"></i> Ulangi
                    </button>
                    <button id="btn-save" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>

            <!-- Step 4: Success -->
            <div id="step-success" class="step">
                <div class="alert alert-success text-center">
                    <h5><i class="fas fa-check-circle"></i> Berhasil!</h5>
                    <p class="mb-0">Wajah terdaftar dengan 12 data training</p>
                </div>

                <div class="text-center">
                    <a href="{{ url('/test-face') }}" class="btn btn-primary btn-sm me-2">
                        <i class="fas fa-vial"></i> Test
                    </a>
                    <a href="{{ url('/my-profile') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-user"></i> Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script src="{{ url('/face/dist/face-api.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            const MODEL_URL = '{{ url("/face/weights") }}';
            const CAPTURE_TARGET = 12;
            const USER_ID = {{ auth()->user()->id }};

            const TRAINING_PLAN = [
                { category: 'angle', instruction: '📐 Wajah lurus ke depan' },
                { category: 'angle', instruction: '📐 Toleh sedikit ke KANAN' },
                { category: 'angle', instruction: '📐 Toleh sedikit ke KIRI' },
                { category: 'angle', instruction: '📐 Angkat dagu sedikit' },
                { category: 'distance', instruction: '📏 Jarak normal' },
                { category: 'distance', instruction: '📏 Sedikit lebih DEKAT' },
                { category: 'distance', instruction: '📏 Sedikit lebih JAUH' },
                { category: 'expression', instruction: '😐 Ekspresi netral' },
                { category: 'expression', instruction: '😊 Senyum tipis' },
                { category: 'expression', instruction: '👀 Mata lebih lebar' },
                { category: 'expression', instruction: '😯 Mulut sedikit terbuka' },
                { category: 'expression', instruction: '🙂 Kombinasi natural' }
            ];

            let video = document.getElementById('video');
            let canvas = document.getElementById('overlay');
            let modelsLoaded = false;
            let detectionInterval = null;
            let captures = [];
            let currentFaceDetection = null;

            // Initialize
            async function init() {
                try {
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                    ]);
                    modelsLoaded = true;
                    console.log('ML models loaded');
                } catch (error) {
                    console.error('Load error:', error);
                }
            }

            // Start camera
            async function startCamera() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'user',
                            width: { ideal: 480 },
                            height: { ideal: 360 }
                        },
                        audio: false
                    });
                    video.srcObject = stream;

                    await new Promise(resolve => video.onloadedmetadata = resolve);

                    // Set canvas to match video display size
                    setTimeout(() => {
                        canvas.width = video.offsetWidth;
                        canvas.height = video.offsetHeight;
                        startDetection();
                    }, 100);
                } catch (error) {
                    Swal.fire('Error', 'Kamera tidak tersedia', 'error');
                }
            }

            // Real-time detection
            function startDetection() {
                detectionInterval = setInterval(async () => {
                    if (!modelsLoaded) return;

                    try {
                        const detection = await faceapi
                            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
                                inputSize: 224,
                                scoreThreshold: 0.4
                            }))
                            .withFaceLandmarks()
                            .withFaceDescriptor();

                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);

                        if (detection) {
                            currentFaceDetection = detection;

                            // Scale detection to canvas size
                            const scaleX = canvas.width / video.videoWidth;
                            const scaleY = canvas.height / video.videoHeight;

                            const box = detection.detection.box;
                            const scaledBox = {
                                x: box.x * scaleX,
                                y: box.y * scaleY,
                                width: box.width * scaleX,
                                height: box.height * scaleY
                            };

                            // Draw box
                            ctx.strokeStyle = '#00ff00';
                            ctx.lineWidth = 2;
                            ctx.strokeRect(scaledBox.x, scaledBox.y, scaledBox.width, scaledBox.height);

                            // Draw landmarks
                            const landmarks = detection.landmarks.positions;
                            ctx.fillStyle = '#00ffff';
                            landmarks.forEach(point => {
                                ctx.beginPath();
                                ctx.arc(point.x * scaleX, point.y * scaleY, 2, 0, 2 * Math.PI);
                                ctx.fill();
                            });

                            updateQualityIndicators(detection, scaleX);
                        } else {
                            currentFaceDetection = null;
                            updateQualityIndicators(null);
                        }
                    } catch (e) {
                        // Silent error
                    }
                }, 300);
            }

            // Update quality indicators
            function updateQualityIndicators(detection, scaleX = 1) {
                const face = document.getElementById('indicator-face');
                const quality = document.getElementById('indicator-quality');
                const btnCapture = document.getElementById('btn-capture');

                if (!detection) {
                    face.className = 'quality-indicator quality-bad';
                    face.innerHTML = '<i class="fas fa-times"></i> No Face';
                    quality.className = 'quality-indicator quality-bad';
                    quality.innerHTML = '<i class="fas fa-times"></i> -';
                    btnCapture.disabled = true;
                    return;
                }

                face.className = 'quality-indicator quality-good';
                face.innerHTML = '<i class="fas fa-check"></i> Face OK';

                const box = detection.detection.box;
                const faceSize = (box.width * scaleX) * (box.height * scaleX);
                const frameSize = canvas.width * canvas.height;
                const sizeRatio = faceSize / frameSize;
                const score = detection.detection.score;

                let isGood = sizeRatio > 0.06 && sizeRatio < 0.4 && score > 0.6;

                if (isGood) {
                    quality.className = 'quality-indicator quality-good';
                    quality.innerHTML = '<i class="fas fa-check"></i> OK';
                    btnCapture.disabled = false;
                } else {
                    quality.className = 'quality-indicator quality-bad';
                    if (sizeRatio <= 0.06) {
                        quality.innerHTML = '<i class="fas fa-arrow-right"></i> Too Far';
                    } else if (sizeRatio >= 0.4) {
                        quality.innerHTML = '<i class="fas fa-arrow-left"></i> Too Close';
                    } else {
                        quality.innerHTML = '<i class="fas fa-lightbulb"></i> Low Light';
                    }
                    btnCapture.disabled = true;
                }
            }

            // Capture photo
            function capturePhoto() {
                if (!currentFaceDetection) return;

                const captureCanvas = document.createElement('canvas');
                captureCanvas.width = video.videoWidth;
                captureCanvas.height = video.videoHeight;
                const ctx = captureCanvas.getContext('2d');

                // Flip horizontal to match display
                ctx.translate(captureCanvas.width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(video, 0, 0);

                const imageData = captureCanvas.toDataURL('image/png');
                const descriptor = Array.from(currentFaceDetection.descriptor);
                const currentPlan = TRAINING_PLAN[captures.length];

                captures.push({
                    image: imageData,
                    descriptor: descriptor,
                    category: currentPlan.category
                });

                updateCapturesPreview();
                updateProgress();

                if (captures.length >= CAPTURE_TARGET) {
                    clearInterval(detectionInterval);
                    showStep('step-verify');
                    showFinalPreview();
                } else {
                    updateCaptureInstruction();
                }
            }

            function updateCapturesPreview() {
                const preview = document.getElementById('captures-preview');
                preview.innerHTML = '';

                captures.forEach((capture, index) => {
                    const img = document.createElement('img');
                    img.src = capture.image;
                    img.className = 'capture-preview';
                    preview.appendChild(img);
                });

                document.getElementById('capture-count').textContent = captures.length;
            }

            function updateProgress() {
                const progress = document.getElementById('progress-bar');
                const percent = (captures.length / CAPTURE_TARGET) * 100;
                progress.style.width = percent + '%';
                progress.textContent = `${captures.length}/${CAPTURE_TARGET}`;
            }

            function updateCaptureInstruction() {
                const instruction = document.getElementById('capture-instruction');
                const currentPlan = TRAINING_PLAN[captures.length];
                instruction.textContent = currentPlan.instruction;
            }

            function showFinalPreview() {
                const preview = document.getElementById('final-preview');
                preview.innerHTML = '';

                captures.forEach(capture => {
                    const img = document.createElement('img');
                    img.src = capture.image;
                    img.className = 'capture-preview';
                    preview.appendChild(img);
                });
            }

            // Save face data
            async function saveFaceData() {
                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const descriptors = captures.map(c => c.descriptor);
                    const avgDescriptor = averageDescriptors(descriptors);

                    const metadata = {
                        count: captures.length,
                        registered_at: new Date().toISOString()
                    };

                    const bestPhoto = captures[0].image;

                    const response = await fetch('{{ url("/my-face/save") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            user_id: USER_ID,
                            photo: bestPhoto,
                            descriptor: {
                                descriptors: descriptors,
                                average: avgDescriptor,
                                metadata: metadata
                            }
                        })
                    });

                    const text = await response.text();
                    let result;
                    try {
                        result = JSON.parse(text);
                    } catch (e) {
                        throw new Error('Server error');
                    }

                    if (result.success) {
                        Swal.close();
                        showStep('step-success');
                    } else {
                        throw new Error(result.message || 'Gagal menyimpan');
                    }
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            }

            function averageDescriptors(descriptors) {
                const length = descriptors[0].length;
                const avg = new Array(length).fill(0);

                descriptors.forEach(desc => {
                    desc.forEach((val, i) => {
                        avg[i] += val;
                    });
                });

                return avg.map(val => val / descriptors.length);
            }

            function showStep(stepId) {
                document.querySelectorAll('.step').forEach(step => {
                    step.classList.remove('active');
                });
                document.getElementById(stepId).classList.add('active');
            }

            // Event listeners
            document.getElementById('btn-start').addEventListener('click', async function () {
                showStep('step-capture');
                await startCamera();
                updateCaptureInstruction();
            });

            document.getElementById('btn-capture').addEventListener('click', capturePhoto);

            document.getElementById('btn-retry').addEventListener('click', function () {
                captures = [];
                updateProgress();
                showStep('step-capture');
                startDetection();
                updateCaptureInstruction();
            });

            document.getElementById('btn-save').addEventListener('click', saveFaceData);

            // Initialize on load
            init();
        </script>
    @endpush
@endsection