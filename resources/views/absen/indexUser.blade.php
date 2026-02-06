@extends('templates.app')
@section('container')
    <div class="card-secton transfer-section">
        <div class="tf-container">
            <div class="tf-balance-box">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="inner-left d-flex justify-content-between align-items-center">
                        <span>Tanggal Shift</span>
                    </div>
                    <span>{{ $shift_karyawan->tanggal ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="inner-left d-flex justify-content-between align-items-center">
                        <span>Shift</span>
                    </div>
                    <span>{{ $shift_karyawan->Shift->nama_shift ?? '' }} ({{ $shift_karyawan->Shift->jam_masuk ?? '' }} -
                        {{ $shift_karyawan->Shift->jam_keluar ?? '' }})</span>
                </div>
            </div>
        </div>
    </div>

    <br>
    <style>
        /* Camera container - centered and responsive */
        #my_camera {
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            border-radius: 12px;
            overflow: visible;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Pastikan kamera TIDAK mirror dan full display */
        #my_camera video,
        #my_camera canvas {
            transform: scaleX(1) !important;
            -webkit-transform: scaleX(1) !important;
            width: 100% !important;
            height: auto !important;
            min-height: 400px !important;
            display: block;
            object-fit: contain !important;
        }

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

    <div class="transfer-content">
        @if (!$shift_karyawan)
            <center>
                <h2>Hubungi Admin Untuk Input Shift Anda</h2>
            </center>
        @elseif($shift_karyawan->status_absen == 'Libur')
            <center>
                <h2>Hari Ini Anda Libur</h2>
            </center>
        @elseif($shift_karyawan->status_absen == "Cuti")
            <center>
                <h2>Hari Ini Anda Cuti</h2>
            </center>
        @else
            @if ($shift_karyawan->jam_absen == null)
                <form class="tf-form" action="{{ url('/absen/masuk/' . $shift_karyawan->id) }}" method="POST">
                    @method('PUT')
                    @csrf
                    <div class="tf-container">
                        <center>
                            <h2>Absen Masuk: </h2>
                            <div class="webcam mb-4" id="my_camera"></div>
                            <div id="results" style="display:none;"></div>
                        </center>
                        @if ($shift_karyawan->lock_location == null)
                            <div class="group-input">
                                <label>Keterangan Masuk</label>
                                <textarea name="keterangan_masuk"
                                    class="@error('keterangan_masuk') is-invalid @enderror">{{ old('keterangan_masuk') }}</textarea>
                                @error('keterangan_masuk')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        @endif
                        <input type="hidden" name="jam_absen">
                        <input type="hidden" name="foto_jam_absen" class="image-tag">
                        <input type="hidden" name="lat_absen" id="lat">
                        <input type="hidden" name="long_absen" id="long">
                        <input type="hidden" name="telat">
                        <input type="hidden" name="jarak_masuk">
                        <input type="hidden" name="status_absen">
                        <button type="submit" class="tf-btn accent large" id="btnMasuk"
                            onClick="take_snapshot(); setTimeout(function(){ document.getElementById('btnMasuk').disabled=true; document.getElementById('btnMasuk').innerHTML='Memproses...'; }, 100);">Save</button>
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
                        width: 480,
                        height: 640,  // 4:3 aspect ratio for better portrait fit
                        image_format: 'png',
                        jpeg_quality: 90,
                        flip_horiz: true,  // true = NOT mirrored (normal view)
                        dest_width: 480,
                        dest_height: 640
                    });

                    Webcam.on('error', function (err) {
                        let errorMessage = 'Tidak dapat mengakses kamera.';
                        let solution = '';

                        if (err.name === 'NotAllowedError' || (err.message && err.message.includes('Permission denied'))) {
                            errorMessage = 'Izin kamera ditolak.';
                            solution = '1. Klik ikon gembok di address bar<br>2. Ubah Camera menjadi Allow<br>3. Refresh halaman';
                        } else if (err.name === 'NotFoundError') {
                            errorMessage = 'Kamera tidak ditemukan.';
                            solution = 'Pastikan perangkat memiliki kamera yang terhubung.';
                        } else if (err.name === 'NotReadableError') {
                            errorMessage = 'Kamera tidak bisa diakses.';
                            solution = '<b>Tutup aplikasi lain yang menggunakan kamera</b> (seperti Zoom, Teams, Skype, atau browser tab lain), lalu refresh halaman ini.';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Kamera Error',
                            html: '<b>' + errorMessage + '</b><br><br>' + solution,
                            confirmButtonColor: '#4f46e5'
                        });
                    });

                    Webcam.attach('#my_camera');
                </script>
                <script language="JavaScript">
                    function take_snapshot() {
                        // Validate webcam is ready
                        if (!Webcam.live) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Kamera Belum Siap',
                                text: 'Mohon tunggu kamera siap terlebih dahulu.',
                                confirmButtonColor: '#3085d6'
                            });
                            var btnMasuk = document.getElementById('btnMasuk');
                            if (btnMasuk) {
                                btnMasuk.disabled = false;
                                btnMasuk.innerHTML = 'Save';
                            }
                            return false;
                        }

                        Webcam.snap(function (data_uri) {
                            // Validate captured image
                            if (!data_uri || data_uri === 'data:,' || !data_uri.includes(';base64,')) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal Mengambil Foto',
                                    text: 'Foto tidak dapat diambil. Silakan coba lagi.',
                                    confirmButtonColor: '#3085d6'
                                });
                                var btnMasuk = document.getElementById('btnMasuk');
                                if (btnMasuk) {
                                    btnMasuk.disabled = false;
                                    btnMasuk.innerHTML = 'Save';
                                }
                                return false;
                            }

                            $(".image-tag").val(data_uri);
                            document.getElementById('my_camera').style.display = 'none';
                            document.getElementById('results').style.display = 'block';
                            document.getElementById('results').innerHTML = '<img src="' + data_uri + '"/>';
                        });
                    }
                </script>
            @elseif($shift_karyawan->jam_pulang == null)
                <form class="tf-form" action="{{ url('/absen/pulang/' . $shift_karyawan->id) }}" method="POST">
                    @method('PUT')
                    @csrf
                    <div class="tf-container">
                        <center>
                            <h2>Absen Pulang: </h2>
                            <div class="webcam mb-4" id="my_camera"></div>
                            <div id="results" style="display:none;"></div>
                        </center>
                        @if ($shift_karyawan->lock_location == null)
                            <div class="group-input">
                                <label>Keterangan Pulang</label>
                                <textarea name="keterangan_pulang"
                                    class="@error('keterangan_pulang') is-invalid @enderror">{{ old('keterangan_pulang') }}</textarea>
                                @error('keterangan_pulang')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        @endif
                        <input type="hidden" name="jam_pulang">
                        <input type="hidden" name="foto_jam_pulang" class="image-tag">
                        <input type="hidden" name="lat_pulang" id="lat">
                        <input type="hidden" name="long_pulang" id="long">
                        <input type="hidden" name="pulang_cepat">
                        <input type="hidden" name="jarak_pulang">
                        <button type="submit" class="tf-btn accent large" id="btnPulang"
                            onClick="take_snapshot(); setTimeout(function(){ document.getElementById('btnPulang').disabled=true; document.getElementById('btnPulang').innerHTML='Memproses...'; }, 100);">Save</button>
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
                        width: 480,
                        height: 480,
                        image_format: 'png',
                        jpeg_quality: 90,
                        flip_horiz: true  // true = NOT mirrored (normal view)
                    });

                    Webcam.on('error', function (err) {
                        let errorMessage = 'Tidak dapat mengakses kamera.';
                        let solution = '';

                        if (err.name === 'NotAllowedError' || (err.message && err.message.includes('Permission denied'))) {
                            errorMessage = 'Izin kamera ditolak.';
                            solution = '1. Klik ikon gembok di address bar<br>2. Ubah Camera menjadi Allow<br>3. Refresh halaman';
                        } else if (err.name === 'NotFoundError') {
                            errorMessage = 'Kamera tidak ditemukan.';
                            solution = 'Pastikan perangkat memiliki kamera yang terhubung.';
                        } else if (err.name === 'NotReadableError') {
                            errorMessage = 'Kamera tidak bisa diakses.';
                            solution = '<b>Tutup aplikasi lain yang menggunakan kamera</b> (seperti Zoom, Teams, Skype, atau browser tab lain), lalu refresh halaman ini.';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Kamera Error',
                            html: '<b>' + errorMessage + '</b><br><br>' + solution,
                            confirmButtonColor: '#4f46e5'
                        });
                    });

                    Webcam.attach('#my_camera');
                </script>
                <script language="JavaScript">
                    function take_snapshot() {
                        // Validate webcam is ready
                        if (!Webcam.live) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Kamera Belum Siap',
                                text: 'Mohon tunggu kamera siap terlebih dahulu.',
                                confirmButtonColor: '#3085d6'
                            });
                            var btnPulang = document.getElementById('btnPulang');
                            if (btnPulang) {
                                btnPulang.disabled = false;
                                btnPulang.innerHTML = 'Save';
                            }
                            return false;
                        }

                        Webcam.snap(function (data_uri) {
                            // Validate captured image
                            if (!data_uri || data_uri === 'data:,' || !data_uri.includes(';base64,')) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal Mengambil Foto',
                                    text: 'Foto tidak dapat diambil. Silakan coba lagi.',
                                    confirmButtonColor: '#3085d6'
                                });
                                var btnPulang = document.getElementById('btnPulang');
                                if (btnPulang) {
                                    btnPulang.disabled = false;
                                    btnPulang.innerHTML = 'Save';
                                }
                                return false;
                            }

                            $(".image-tag").val(data_uri);
                            document.getElementById('my_camera').style.display = 'none';
                            document.getElementById('results').style.display = 'block';
                            document.getElementById('results').innerHTML = '<img src="' + data_uri + '"/>';
                        });
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
            }

            setInterval(getLocation, 1000);
        </script>
    @endpush

    <!-- Face Verification Modal -->
    <div class="modal fade" id="faceVerificationModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-check"></i> Verifikasi Wajah Diperlukan
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-2"><strong>Absensi tidak dapat diproses karena:</strong></p>
                    <div class="alert alert-danger" id="faceErrorReason" style="font-size: 0.9rem;"></div>

                    <p class="fw-bold mb-3" id="faceInstruction">
                        {{ \App\Models\settings::first()->face_verification_message ?? 'Silakan verifikasi wajah Anda untuk melanjutkan absensi' }}
                    </p>

                    <!-- Camera preview overlay -->
                    <div style="position: relative;">
                        <canvas id="faceCanvas" width="480" height="640"
                            style="max-width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></canvas>
                    </div>

                    <div id="faceStatus" class="mt-3">
                        <p class="text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Memuat face recognition...
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnCaptureFace" style="display:none;">
                        <i class="fas fa-check-circle"></i> Verifikasi Wajah
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('myscript')
        <!-- Face-API.js from LOCAL (same as pegawai face recognition) -->
        <script src="{{ url('/face/dist/face-api.min.js') }}"></script>

        <script>
            // Wait for face-api.js to load
            document.addEventListener('DOMContentLoaded', function () {
                const FACE_ENABLED = {{ \App\Models\settings::first()->enable_face_verification_fallback ? 'true' : 'false' }};
                const FACE_THRESHOLD = {{ \App\Models\settings::first()->face_match_threshold ?? 70 }};

                if (!FACE_ENABLED) {
                    console.log('Face verification disabled in settings');
                    return;
                }

                console.log('Face verification enabled. Threshold:', FACE_THRESHOLD + '%');

                // Wait for face-api to be available
                const waitForFaceAPI = setInterval(function () {
                    if (typeof faceapi !== 'undefined') {
                        clearInterval(waitForFaceAPI);
                        initFaceRecognition();
                    }
                }, 100);
            });

            // Face Recognition Variables
            let faceModelsLoaded = false;
            let referenceFaceDescriptor = null;
            let faceDetectionInterval = null;

            // Initialize face recognition
            async function initFaceRecognition() {
                const MODEL_URL = '{{ url("/face/weights") }}'; // USE LOCAL WEIGHTS (same as pegawai)

                try {
                    console.log('🔄 Loading face recognition models from local...');

                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                    ]);

                    faceModelsLoaded = true;
                    console.log('✅ Face models loaded successfully');

                    // Preload user's reference face
                    await loadReferenceFace();
                } catch (error) {
                    console.error('❌ Error loading face models:', error);
                }
            }

            // Load reference face from user's profile photo
            async function loadReferenceFace() {
                try {
                    const photoUrl = '{{ url("/storage/" . (auth()->user()->foto_karyawan ?? "default.jpg")) }}';
                    const img = await faceapi.fetchImage(photoUrl);

                    const detection = await faceapi
                        .detectSingleFace(img)
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (detection) {
                        referenceFaceDescriptor = detection.descriptor;
                        console.log('✅ Reference face loaded');
                    } else {
                        console.warn('⚠️ No face in reference photo');
                    }
                } catch (error) {
                    console.error('❌ Error loading reference:', error);
                }
            }

            // Show face verification modal
            window.showFaceVerification = function (errorMessage) {
                if (!faceModelsLoaded) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Loading...',
                        text: 'Face recognition sedang dimuat, tunggu beberapa detik'
                    });
                    return;
                }

                if (!referenceFaceDescriptor) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Foto Referensi Tidak Ada',
                        html: 'Upload foto profil Anda dengan wajah yang jelas'
                    });
                    return;
                }

                document.getElementById('faceErrorReason').innerText = errorMessage;

                const modal = new bootstrap.Modal(document.getElementById('faceVerificationModal'));
                modal.show();

                startFaceDetection();
            };

            // Real-time face detection
            function startFaceDetection() {
                const canvas = document.getElementById('faceCanvas');
                const video = document.querySelector('#my_camera video');
                const statusDiv = document.getElementById('faceStatus');
                const btnCapture = document.getElementById('btnCaptureFace');

                if (!video) {
                    statusDiv.innerHTML = '<p class="text-danger">Kamera tidak tersedia</p>';
                    return;
                }

                // Match canvas to video dimensions
                video.addEventListener('loadedmetadata', () => {
                    const displaySize = { width: video.videoWidth || 480, height: video.videoHeight || 640 };
                    faceapi.matchDimensions(canvas, displaySize);
                });

                statusDiv.innerHTML = '<p class="text-info"><i class="fas fa-search fa-spin"></i> Mendeteksi wajah...</p>';

                // Clear previous interval
                if (faceDetectionInterval) clearInterval(faceDetectionInterval);

                // Detect face every 500ms
                faceDetectionInterval = setInterval(async () => {
                    try {
                        const detection = await faceapi
                            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 128, scoreThreshold: 0.5 }))
                            .withFaceLandmarks()
                            .withFaceDescriptor();

                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);

                        if (detection) {
                            // Draw face box
                            const displaySize = { width: canvas.width, height: canvas.height };
                            const resized = faceapi.resizeResults(detection, displaySize);
                            faceapi.draw.drawDetections(canvas, [resized]);
                            faceapi.draw.drawFaceLandmarks(canvas, [resized]);

                            statusDiv.innerHTML = '<p class="text-success"><i class="fas fa-check-circle"></i> Wajah terdeteksi!</p>';
                            btnCapture.style.display = 'inline-block';
                        } else {
                            statusDiv.innerHTML = '<p class="text-warning"><i class="fas fa-exclamation-triangle"></i> Posisikan wajah di kamera</p>';
                            btnCapture.style.display = 'none';
                        }
                    } catch (err) {
                        console.error('Detection error:', err);
                    }
                }, 500);
            }

            document.getElementById('btnCaptureFace').addEventListener('click', async function () {
                const video = document.querySelector('#my_camera video');
                const statusDiv = document.getElementById('faceStatus');
                const THRESHOLD = {{ \App\Models\settings::first()->face_match_threshold ?? 70 }};

                this.disabled = true;
                statusDiv.innerHTML = '<p class="text-info"><i class="fas fa-spinner fa-spin"></i> Memverifikasi...</p>';

                try {
                    if (faceDetectionInterval) {
                        clearInterval(faceDetectionInterval);
                        faceDetectionInterval = null;
                    }

                    const detection = await faceapi
                        .detectSingleFace(video)
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (!detection) throw new Error('Wajah tidak terdeteksi');

                    // Calculate similarity
                    const distance = faceapi.euclideanDistance(referenceFaceDescriptor, detection.descriptor);
                    const similarity = (1 - distance) * 100;

                    console.log('Face match:', similarity.toFixed(1) + '%');

                    if (similarity >= THRESHOLD) {
                        // SUCCESS!
                        statusDiv.innerHTML = `<p class="text-success"><i class="fas fa-check-circle"></i> Cocok ${similarity.toFixed(0)}%!</p>`;

                        bootstrap.Modal.getInstance(document.getElementById('faceVerificationModal')).hide();

                        Swal.fire({
                            icon: 'success',
                            title: 'Terverifikasi!',
                            text: 'Wajah cocok ' + similarity.toFixed(0) + '%. Memproses absensi...',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        submitWithFaceVerification();
                    } else {
                        // FAILED
                        statusDiv.innerHTML = `<p class="text-danger">Tidak cocok (${similarity.toFixed(0)}%, min ${THRESHOLD}%)</p>`;
                        this.disabled = false;
                        setTimeout(() => startFaceDetection(), 2000);
                    }
                } catch (error) {
                    statusDiv.innerHTML = '<p class="text-danger">' + error.message + '</p>';
                    this.disabled = false;
                    setTimeout(() => startFaceDetection(), 2000);
                }
            });

            // Submit attendance with face verification flag
            function submitWithFaceVerification() {
                // Find the absen form
                const form = document.querySelector('form[action*="absen"]') || document.getElementById('absen-form');

                if (!form) {
                    Swal.fire('Error', 'Form tidak ditemukan', 'error');
                    return;
                }

                // Add face_verified flag
                let input = form.querySelector('input[name="face_verified"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'face_verified';
                    form.appendChild(input);
                }
                input.value = '1';

                // Submit
                form.submit();
            }

            // Cleanup on modal close
            document.getElementById('faceVerificationModal').addEventListener('hidden.bs.modal', function () {
                if (faceDetectionInterval) {
                    clearInterval(faceDetectionInterval);
                    faceDetectionInterval = null;
                }

                const canvas = document.getElementById('faceCanvas');
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            });
        </script>
    @endpush

@endsection