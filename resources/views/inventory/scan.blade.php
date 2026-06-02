@extends('templates.dashboard')
@section('isi')
    <div class="row">
        <div class="col-md-12 project-list">
            <div class="card">
                <div class="row">
                    <div class="col-md-6 mt-2 p-0 d-flex">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0">
                        <a href="{{ url('/inventory') }}" class="btn btn-danger btn-sm ms-2">Back</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mx-auto">
            <div class="card inventory-scan-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Scanner QR Barang</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="retry-camera">Muat Ulang Kamera</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="toggle-manual">Input Manual</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mx-auto inventory-reader" id="reader"></div>
                    <div id="scan-status" class="alert alert-info mt-3">Kamera siap membaca QR barang.</div>

                    <form id="manual-scan-form" class="mt-3" style="display: none;">
                        <label>Kode QR / Kode Barang</label>
                        <div class="input-group">
                            <input type="text" id="manual-code" class="form-control" autocomplete="off">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">Cari</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        var scanner = null;
        var scanLocked = false;
        var scanStarted = false;
        var lastScannedCode = '';
        var lastScanAt = 0;
        var scannerLibraryPromise = null;

        function setScanStatus(message, type) {
            var status = document.getElementById('scan-status');
            status.className = 'alert alert-' + type + ' mt-3';
            status.textContent = message;
        }

        function normalizeScanValue(value) {
            return String(value || '').replace(/\s+/g, ' ').trim();
        }

        function showManualForm(focusInput) {
            var form = document.getElementById('manual-scan-form');
            form.style.display = 'block';
            if (focusInput) {
                document.getElementById('manual-code').focus();
            }
        }

        function loadScannerLibrary() {
            if (typeof Html5Qrcode !== 'undefined') {
                return Promise.resolve(true);
            }

            if (scannerLibraryPromise) {
                return scannerLibraryPromise;
            }

            scannerLibraryPromise = new Promise(function (resolve, reject) {
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js';
                script.onload = function () {
                    if (typeof Html5Qrcode !== 'undefined') {
                        resolve(true);
                        return;
                    }
                    reject(new Error('Scanner QR belum bisa dimuat.'));
                };
                script.onerror = function () {
                    reject(new Error('Scanner QR belum bisa dimuat.'));
                };
                document.head.appendChild(script);
            });

            return scannerLibraryPromise;
        }

        function lookupInventory(code) {
            code = normalizeScanValue(code);
            if (!code || scanLocked) {
                return;
            }

            var now = Date.now();
            if (code === lastScannedCode && (now - lastScanAt) < 2200) {
                return;
            }
            lastScannedCode = code;
            lastScanAt = now;

            scanLocked = true;
            setScanStatus('Memproses QR barang...', 'info');

            fetch('{{ url('/inventory/scan/lookup') }}?code=' + encodeURIComponent(code), {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    var contentType = response.headers.get('content-type') || '';

                    if (contentType.indexOf('application/json') === -1) {
                        if (response.redirected && response.url) {
                            window.location.href = response.url;
                            throw new Error('Sesi login dialihkan. Silakan login lalu scan ulang.');
                        }

                        throw new Error('Server tidak mengirim data scan. Refresh halaman lalu coba lagi.');
                    }

                    return response.json().then(function (data) {
                        if (!response.ok) {
                            throw new Error(data.message || 'Barang tidak ditemukan.');
                        }
                        if (!data.url) {
                            throw new Error('Data scan tidak lengkap. Coba scan ulang.');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    window.location.href = data.url;
                })
                .catch(function (error) {
                    setScanStatus(error.message, 'danger');
                    scanLocked = false;
                    if (scanner) {
                        try {
                            scanner.resume();
                        } catch (e) {}
                    }
                });
        }

        function onScanSuccess(decodedText) {
            if (scanner) {
                try {
                    scanner.pause();
                } catch (e) {}
            }
            lookupInventory(decodedText);
        }

        async function startInventoryScanner() {
            if (scanStarted) {
                return;
            }

            if (!window.isSecureContext) {
                setScanStatus('Kamera hanya dapat dipakai di koneksi HTTPS. Gunakan input manual.', 'warning');
                showManualForm(false);
                return;
            }

            try {
                await loadScannerLibrary();
            } catch (libraryError) {
                setScanStatus(libraryError.message + ' Gunakan input manual.', 'warning');
                showManualForm(false);
                return;
            }

            if (!scanner) {
                scanner = new Html5Qrcode('reader');
            }

            var qrConfig = { fps: 10, qrbox: { width: 250, height: 250 } };
            if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
                qrConfig.formatsToSupport = [Html5QrcodeSupportedFormats.QR_CODE];
            }
            qrConfig.disableFlip = true;

            try {
                setScanStatus('Menyalakan kamera...', 'info');
                await scanner.start({ facingMode: 'environment' }, qrConfig, onScanSuccess, function () {});
                scanStarted = true;
                setScanStatus('Kamera aktif. Arahkan ke QR barang.', 'success');
                return;
            } catch (primaryError) {
                try {
                    var cameras = await Html5Qrcode.getCameras();
                    var selectedCamera = null;
                    if (Array.isArray(cameras) && cameras.length) {
                        selectedCamera = cameras.find(function (camera) {
                            return /back|rear|environment/i.test(camera.label || '');
                        }) || cameras[0];
                    }

                    if (selectedCamera && selectedCamera.id) {
                        await scanner.start({ deviceId: { exact: selectedCamera.id } }, qrConfig, onScanSuccess, function () {});
                        scanStarted = true;
                        setScanStatus('Kamera aktif. Arahkan ke QR barang.', 'success');
                        return;
                    }
                } catch (cameraListError) {}

                try {
                    await scanner.start({ facingMode: 'user' }, qrConfig, onScanSuccess, function () {});
                    scanStarted = true;
                    setScanStatus('Kamera depan aktif. Arahkan ke QR barang.', 'success');
                    return;
                } catch (fallbackError) {
                    scanStarted = false;
                    showManualForm(false);
                    setScanStatus('Kamera tidak dapat diakses. Gunakan input manual.', 'warning');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('toggle-manual').addEventListener('click', function () {
                var form = document.getElementById('manual-scan-form');
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
                if (form.style.display === 'block') {
                    document.getElementById('manual-code').focus();
                }
            });

            document.getElementById('manual-scan-form').addEventListener('submit', function (event) {
                event.preventDefault();
                lookupInventory(document.getElementById('manual-code').value);
            });

            document.getElementById('retry-camera').addEventListener('click', function () {
                if (scanner && scanStarted) {
                    scanner.stop().catch(function () {}).finally(function () {
                        scanStarted = false;
                        startInventoryScanner();
                    });
                    return;
                }
                startInventoryScanner();
            });

            startInventoryScanner();
        });
    </script>
@endpush
