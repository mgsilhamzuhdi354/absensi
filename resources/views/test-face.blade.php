<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ML Face Recognition Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 10px;
        }

        .test-card {
            background: white;
            border-radius: 20px;
            padding: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            margin: 0 auto;
        }

        .video-wrapper {
            position: relative;
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        #video {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: scaleX(-1);
        }

        #overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            transform: scaleX(-1);
            pointer-events: none;
        }

        .status-box {
            padding: 12px;
            border-radius: 10px;
            margin: 10px 0;
            font-weight: 600;
            font-size: 14px;
        }

        .status-loading {
            background: #fff3cd;
            color: #856404;
        }

        .status-ready {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-detecting {
            background: #cfe2ff;
            color: #084298;
        }

        #debug {
            max-height: 150px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 10px;
            white-space: pre-wrap;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 8px;
        }

        .ml-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }

        .ref-photo {
            max-width: 120px;
            border-radius: 10px;
            border: 2px solid #667eea;
        }

        h3 {
            font-size: 1.2rem;
        }

        .btn-lg {
            padding: 10px 20px;
            font-size: 14px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .test-card {
                padding: 12px;
                border-radius: 15px;
            }

            h3 {
                font-size: 1rem;
            }

            .ref-photo {
                max-width: 80px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="test-card">
            <div class="text-center mb-3">
                <h3><i class="fas fa-vial"></i> Test Face Recognition</h3>
                <span class="ml-badge">
                    <i class="fas fa-brain"></i> ML Multi-Descriptor
                </span>
            </div>

            <div id="status" class="status-box status-loading">
                <i class="fas fa-spinner fa-spin"></i> Loading...
            </div>

            <!-- Live Video -->
            <div class="text-center mb-3">
                <div class="video-wrapper">
                    <video id="video" autoplay playsinline muted></video>
                    <canvas id="overlay"></canvas>
                </div>
            </div>

            <!-- Test Buttons -->
            <div class="text-center mb-3">
                <button id="btn-test" class="btn btn-success" disabled>
                    <i class="fas fa-microscope"></i> Test Match
                </button>
                <button id="btn-pause" class="btn btn-secondary btn-sm ms-2">
                    <i class="fas fa-pause"></i> Pause
                </button>
            </div>

            <!-- Match Result -->
            <div id="match-result"></div>

            <!-- Debug Log (collapsed) -->
            <details class="mt-2">
                <summary style="font-size:12px; color:#666;"><i class="fas fa-bug"></i> Debug Info</summary>
                <div id="debug" class="mt-1"></div>
            </details>
        </div>
    </div>

    <script src="{{ url('/face/dist/face-api.min.js') }}"></script>
    <script>
        const MODEL_URL = '{{ url("/face/weights") }}';

        let video = document.getElementById('video');
        let canvas = document.getElementById('overlay');
        let statusDiv = document.getElementById('status');
        let debugDiv = document.getElementById('debug');
        let btnTest = document.getElementById('btn-test');
        let btnPause = document.getElementById('btn-pause');

        let modelsLoaded = false;
        let detectionInterval = null;
        let referenceFace = null;
        let isPaused = false;

        // Logging
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const icon = type === 'error' ? '❌' : type === 'success' ? '✅' : '🔵';
            debugDiv.textContent += `[${timestamp}] ${icon} ${message}\n`;
            debugDiv.scrollTop = debugDiv.scrollHeight;
            console.log(message);
        }

        function setStatus(message, type = 'loading') {
            statusDiv.className = `status-box status-${type}`;
            statusDiv.innerHTML = message;
        }

        // Initialize
        async function init() {
            try {
                log('Starting ML initialization...');
                setStatus('<i class="fas fa-spinner fa-spin"></i> Loading ML models...', 'loading');

                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);

                modelsLoaded = true;
                log('✅ ML models loaded successfully', 'success');

                await startCamera();
                await loadReferenceFace();

                setStatus('<i class="fas fa-check-circle"></i> Ready for ML testing!', 'ready');
                btnTest.disabled = false;

            } catch (error) {
                log('❌ Initialization failed: ' + error.message, 'error');
                setStatus('<i class="fas fa-times-circle"></i> Error: ' + error.message, 'loading');
            }
        }

        // Start camera (NON-MIRRORED)
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    },
                    audio: false
                });

                video.srcObject = stream;
                await new Promise(resolve => video.onloadedmetadata = resolve);

                // Wait for video to render, then set canvas to DISPLAY size (not raw video size)
                setTimeout(() => {
                    canvas.width = video.offsetWidth;
                    canvas.height = video.offsetHeight;
                    log(`Canvas size: ${canvas.width}x${canvas.height}`, 'success');
                }, 100);

                log('✅ Camera started (non-mirrored)', 'success');
                startDetection();

            } catch (error) {
                log('❌ Camera error: ' + error.message, 'error');
                throw error;
            }
        }

        // Load reference face (ML-aware)
        async function loadReferenceFace() {
            try {
                log('Loading ML reference data...');

                const hasFaceReg = '{{ auth()->user()->foto_face_recognition }}' !== '';
                const photoUrl = hasFaceReg
                    ? '{{ url("/storage/" . auth()->user()->foto_face_recognition) }}'
                    : '{{ url("/storage/" . auth()->user()->foto_karyawan) }}';

                if (!hasFaceReg) {
                    log('⚠️ No ML data - using profile photo (accuracy ~60-70%)', 'error');
                }

                const img = await faceapi.fetchImage(photoUrl);
                const detection = await faceapi
                    .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({
                        inputSize: 224,
                        scoreThreshold: 0.4
                    }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!detection) {
                    throw new Error('No face found in reference photo');
                }

                // Parse ML descriptor data
                const descriptorJSON = `{!! addslashes(auth()->user()->face_descriptor) !!}`;

                if (descriptorJSON && descriptorJSON !== '' && hasFaceReg) {
                    try {
                        const data = JSON.parse(descriptorJSON);

                        if (data.descriptors && Array.isArray(data.descriptors)) {
                            // ML multi-descriptor format
                            referenceFace = {
                                descriptors: data.descriptors,
                                average: data.average,
                                metadata: data.metadata
                            };
                            log(`✅ ML data loaded: ${data.descriptors.length} training descriptors (accuracy ~95-99%)`, 'success');
                        } else if (Array.isArray(data)) {
                            referenceFace = { average: data };
                            log('✅ Single descriptor loaded (legacy)', 'success');
                        } else if (data.average) {
                            referenceFace = { average: data.average };
                            log('✅ Average descriptor loaded', 'success');
                        } else {
                            throw new Error('Unknown format');
                        }
                    } catch (e) {
                        log('⚠️ Parse error, using photo: ' + e.message, 'error');
                        referenceFace = { average: Array.from(detection.descriptor) };
                    }
                } else {
                    referenceFace = { average: Array.from(detection.descriptor) };
                    log('⚠️ No ML descriptor, extracted from photo', 'error');
                }

            } catch (error) {
                log('❌ Reference load error: ' + error.message, 'error');
                throw error;
            }
        }

        // Real-time detection
        function startDetection() {
            detectionInterval = setInterval(async () => {
                if (isPaused) return;

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
                        // Scale detection box to match displayed canvas size
                        const scaleX = canvas.width / video.videoWidth;
                        const scaleY = canvas.height / video.videoHeight;

                        const box = detection.detection.box;
                        const scaledBox = {
                            x: box.x * scaleX,
                            y: box.y * scaleY,
                            width: box.width * scaleX,
                            height: box.height * scaleY
                        };

                        // Draw face box
                        ctx.strokeStyle = '#00ff00';
                        ctx.lineWidth = 2;
                        ctx.strokeRect(scaledBox.x, scaledBox.y, scaledBox.width, scaledBox.height);

                        // Draw landmarks
                        const landmarks = detection.landmarks.positions;
                        ctx.fillStyle = '#ff00ff';
                        landmarks.forEach(point => {
                            ctx.beginPath();
                            ctx.arc(point.x * scaleX, point.y * scaleY, 2, 0, 2 * Math.PI);
                            ctx.fill();
                        });

                        setStatus('<i class="fas fa-check-circle"></i> Face detected - Ready to test match', 'detecting');
                    } else {
                        setStatus('<i class="fas fa-search"></i> Looking for face...', 'loading');
                    }
                } catch (error) {
                    log('Detection error: ' + error.message, 'error');
                }
            }, 300);
        }

        // Test ML match
        btnTest.addEventListener('click', async function () {
            if (!referenceFace) {
                alert('Reference face not loaded!');
                return;
            }

            this.disabled = true;
            setStatus('<i class="fas fa-spinner fa-spin"></i> Running ML match test...', 'loading');

            try {
                const detection = await faceapi
                    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
                        inputSize: 224,
                        scoreThreshold: 0.4
                    }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!detection) {
                    throw new Error('No face detected. Position your face clearly.');
                }

                // ML Multi-Descriptor Matching
                let matchResults;

                if (referenceFace.descriptors && Array.isArray(referenceFace.descriptors)) {
                    // ML Mode: Match against ALL descriptors
                    const similarities = referenceFace.descriptors.map(refDesc => {
                        const distance = faceapi.euclideanDistance(refDesc, detection.descriptor);
                        return (1 - distance) * 100;
                    });

                    matchResults = {
                        best: Math.max(...similarities),
                        average: similarities.reduce((a, b) => a + b) / similarities.length,
                        worst: Math.min(...similarities),
                        count: similarities.length,
                        isML: true
                    };

                    log(`✅ ML Match - Best: ${matchResults.best.toFixed(2)}%, Avg: ${matchResults.average.toFixed(2)}%, Worst: ${matchResults.worst.toFixed(2)}%`, 'success');
                } else if (referenceFace.average) {
                    // Single descriptor mode
                    const distance = faceapi.euclideanDistance(referenceFace.average, detection.descriptor);
                    const similarity = (1 - distance) * 100;

                    matchResults = {
                        best: similarity,
                        average: similarity,
                        worst: similarity,
                        count: 1,
                        isML: false
                    };

                    log(`Single descriptor match: ${similarity.toFixed(2)}%`, 'success');
                }

                const threshold = 70;
                const passed = matchResults.best >= threshold;

                // Display result
                const resultDiv = document.getElementById('match-result');
                if (matchResults.isML) {
                    resultDiv.innerHTML = `
                        <div class="alert ${passed ? 'alert-success' : 'alert-danger'}">
                            <h5><i class="fas ${passed ? 'fa-check-circle' : 'fa-times-circle'}"></i> 
                                ${passed ? 'MATCH VERIFIED ✅' : 'NO MATCH ❌'}</h5>
                            <div class="row mt-3 text-center">
                                <div class="col-4">
                                    <strong>Best Match:</strong><br>
                                    <span class="badge ${matchResults.best >= threshold ? 'bg-success' : 'bg-danger'}" style="font-size: 20px; padding: 10px 15px;">
                                        ${matchResults.best.toFixed(2)}%
                                    </span>
                                </div>
                                <div class="col-4">
                                    <strong>Average:</strong><br>
                                    <span class="badge bg-primary" style="font-size: 20px; padding: 10px 15px;">
                                        ${matchResults.average.toFixed(2)}%
                                    </span>
                                </div>
                                <div class="col-4">
                                    <strong>Worst Match:</strong><br>
                                    <span class="badge bg-secondary" style="font-size: 20px; padding: 10px 15px;">
                                        ${matchResults.worst.toFixed(2)}%
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <small class="text-muted">
                                <i class="fas fa-brain"></i> <strong>ML Mode:</strong> Tested against ${matchResults.count} training descriptors<br>
                                <i class="fas fa-chart-line"></i> <strong>Threshold:</strong> ${threshold}% | <strong>Result:</strong> ${passed ? 'PASS' : 'FAIL'}
                            </small>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert ${passed ? 'alert-warning' : 'alert-danger'}">
                            <h5><i class="fas ${passed ? 'fa-exclamation-triangle' : 'fa-times-circle'}"></i> 
                                ${passed ? 'Match (Low Confidence)' : 'NO MATCH'}</h5>
                            <p class="mb-2">Similarity: <strong>${matchResults.best.toFixed(2)}%</strong></p>
                            <small class="text-muted">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Single descriptor mode</strong> (accuracy ~60-70%)<br>
                                <i class="fas fa-info-circle"></i> Please register via <a href="{{ url('/my-face/register') }}">ML Training</a> for 95-99% accuracy
                            </small>
                        </div>
                    `;
                }

                setStatus('<i class="fas fa-check-circle"></i> Test complete!', 'ready');

            } catch (error) {
                log('❌ Test error: ' + error.message, 'error');
                document.getElementById('match-result').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> <strong>Error:</strong> ${error.message}
                    </div>
                `;
                setStatus('<i class="fas fa-times-circle"></i> ' + error.message, 'loading');
            } finally {
                this.disabled = false;
            }
        });

        // Pause button
        btnPause.addEventListener('click', function () {
            isPaused = !isPaused;
            this.innerHTML = isPaused
                ? '<i class="fas fa-play"></i> Resume'
                : '<i class="fas fa-pause"></i> Pause';
        });

        // Auto-init
        init();
    </script>
</body>

</html>