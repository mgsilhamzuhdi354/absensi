@extends('templates.app')

@push('style')
    <style>
        .bast-panel {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .signature-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            height: 100%;
        }

        .signature-preview {
            min-height: 96px;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 10px;
        }

        .signature-preview img {
            max-width: 100%;
            max-height: 72px;
        }

        .signature-canvas {
            width: 100%;
            height: 160px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            touch-action: none;
        }
    </style>
@endpush

@section('container')
    @php
        $transaction = $document->transaction;
        $inventory = $transaction ? $transaction->inventory : null;
        $signatureRoles = \App\Models\InventoryBastDocument::signatureRoles();
        $roleDetails = [
            'receiver' => [
                'heading' => 'PIHAK KEDUA',
                'subtitle' => 'Yang Menerima',
                'name' => $document->receiver_signature_name ?: ($document->nama_penerima ?: (optional($transaction->penerima)->name ?: '-')),
                'position' => $document->jabatan_penerima ?: ($transaction->jabatan_penerima ?? '-'),
            ],
            'known' => [
                'heading' => 'MENGETAHUI',
                'subtitle' => 'Perwakilan Perusahaan',
                'name' => $document->known_signature_name ?: ($document->nama_mengetahui ?: (optional($document->knownBy)->name ?: '-')),
                'position' => optional(optional($document->knownBy)->Jabatan)->nama_jabatan ?: 'HRD / Manager',
            ],
            'first_party' => [
                'heading' => 'PIHAK PERTAMA',
                'subtitle' => 'Yang Menyerahkan',
                'name' => $document->first_party_signature_name ?: ($document->nama_penyerah ?: (optional($document->firstParty)->name ?: '-')),
                'position' => optional(optional($document->firstParty)->Jabatan)->nama_jabatan ?: ($document->jabatan_penyerah ?: '-'),
            ],
        ];
        $hasPendingForUser = collect($signatureRoles)->contains(function ($config, $role) use ($document) {
            return $document->canUserSignRole(auth()->user(), $role) && !$document->{$config['signed_at']};
        });
    @endphp

    <div class="card-secton transfer-section">
        <div class="tf-container">
            <div class="tf-balance-box">
                <div class="tf-spacing-16"></div>
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h3 class="mb-1">{{ $inventory->nama_barang ?? 'BAST Inventory' }}</h3>
                        <p class="text-muted mb-0">{{ $document->nomor_surat }}</p>
                    </div>
                    @if ($hasPendingForUser)
                        <span class="badge bg-warning text-dark">Perlu TTD</span>
                    @else
                        <span class="badge bg-success">Tidak ada tugas TTD</span>
                    @endif
                </div>
                <div class="tf-spacing-16"></div>
            </div>
        </div>
    </div>

    <div id="app-wrap">
        <div class="bill-content">
            <div class="tf-container">
                @if ($errors->any())
                    <div class="alert alert-danger mt-3">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success mt-3">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                @endif

                <div class="p-3 mt-3 bast-panel">
                    <h4 class="mb-3">Informasi Surat</h4>
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 140px;">Nomor</th>
                                <td>{{ $document->nomor_surat }}</td>
                            </tr>
                            <tr>
                                <th>Tanggal</th>
                                <td>{{ optional($document->tanggal_surat)->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Penyerah</th>
                                <td>{{ $document->nama_penyerah ?? ($transaction->processedBy->name ?? '-') }}</td>
                            </tr>
                            <tr>
                                <th>Penerima</th>
                                <td>{{ $document->nama_penerima ?? (optional($transaction->penerima)->name ?: '-') }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @foreach ($signatureRoles as $role => $config)
                                        @if ($document->{$config['signed_at']})
                                            <span class="badge bg-success me-1">{{ $config['short_label'] }} selesai</span>
                                        @elseif ($document->canUserSignRole(auth()->user(), $role))
                                            <span class="badge bg-warning text-dark me-1">{{ $config['short_label'] }} perlu TTD</span>
                                        @else
                                            <span class="badge bg-light text-dark me-1">{{ $config['short_label'] }} menunggu</span>
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 mt-3 bast-panel">
                    <h4 class="mb-3">Barang Yang Diterima</h4>
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 140px;">Kode</th>
                                <td>{{ $inventory->kode_barang ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Nama</th>
                                <td>{{ $inventory->nama_barang ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Merk / Tipe</th>
                                <td>{{ $inventory->merk_tipe ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Serial Number</th>
                                <td>{{ $inventory->serial_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Kondisi</th>
                                <td>{{ $transaction->kondisi_barang ?? ($inventory->kondisi ?? '-') }}</td>
                            </tr>
                            <tr>
                                <th>Keperluan</th>
                                <td>{{ $transaction->keperluan ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 mt-3 bast-panel">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="{{ url('/my-inventory-bast/'.$document->id.'/download') }}" class="btn btn-primary">
                            Download BAST
                        </a>
                        <a href="{{ url('/my-inventory-bast') }}" class="btn btn-light">
                            Kembali
                        </a>
                    </div>

                    <h4 class="mb-3">Tanda Tangan</h4>
                    <div class="row g-3">
                        @foreach ($signatureRoles as $role => $config)
                            @php
                                $isSigned = (bool) $document->{$config['signed_at']};
                                $canSign = $document->canUserSignRole(auth()->user(), $role);
                                $imagePath = $document->{$config['image']};
                                $signedAt = $document->{$config['signed_at']};
                            @endphp
                            <div class="col-md-4">
                                <div class="signature-card">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <strong>{{ $roleDetails[$role]['heading'] }}</strong>
                                            <div class="text-muted small">{{ $roleDetails[$role]['subtitle'] }}</div>
                                        </div>
                                        @if ($isSigned)
                                            <span class="badge bg-success">Selesai</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Menunggu</span>
                                        @endif
                                    </div>

                                    <div class="signature-preview mb-2">
                                        @if ($isSigned && $imagePath)
                                            <img src="{{ asset('storage/'.$imagePath) }}" alt="Tanda tangan {{ $config['short_label'] }}">
                                        @elseif ($isSigned)
                                            <div class="text-primary small">
                                                Ditandatangani elektronik<br>
                                                {{ optional($signedAt)->format('d/m/Y H:i') }}
                                            </div>
                                        @else
                                            <div class="text-muted small">Menunggu tanda tangan</div>
                                        @endif
                                    </div>

                                    <div class="text-center">
                                        <strong>{{ $roleDetails[$role]['name'] }}</strong>
                                        <div class="text-muted small">{{ $roleDetails[$role]['position'] }}</div>
                                        @if ($signedAt)
                                            <div class="text-primary small mt-1">Terverifikasi {{ $signedAt->format('d/m/Y H:i') }}</div>
                                        @endif
                                    </div>

                                    @if ($canSign && !$isSigned)
                                        @if ($transaction && method_exists($transaction, 'trashed') && $transaction->trashed())
                                            <div class="alert alert-warning mt-3 mb-0">
                                                BAST ini belum bisa ditandatangani karena transaksi stoknya sudah dihapus.
                                            </div>
                                        @else
                                            <form method="post" action="{{ url('/my-inventory-bast/'.$document->id.'/sign/'.$role) }}" class="mt-3" data-signature-form>
                                                @csrf
                                                <input type="hidden" name="signature_data" data-signature-data>
                                                <canvas class="signature-canvas" data-signature-canvas></canvas>
                                                <div class="d-flex gap-2 mt-2">
                                                    <button type="button" class="btn btn-light btn-sm" data-clear-signature>Hapus</button>
                                                    <button type="submit" class="btn btn-success btn-sm">Tanda Tangani</button>
                                                </div>
                                                <div class="form-check mt-2">
                                                    <input type="checkbox" class="form-check-input" id="agreement_{{ $role }}" name="agreement" value="1" required>
                                                    <label class="form-check-label small" for="agreement_{{ $role }}">
                                                        Saya menyetujui tanda tangan elektronik sebagai {{ $config['label'] }}.
                                                    </label>
                                                </div>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br><br><br><br>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-signature-form]').forEach(function (form) {
                var canvas = form.querySelector('[data-signature-canvas]');
                var input = form.querySelector('[data-signature-data]');
                var clearButton = form.querySelector('[data-clear-signature]');
                var context = canvas.getContext('2d');
                var drawing = false;
                var hasInk = false;

                function resizeCanvas() {
                    var ratio = window.devicePixelRatio || 1;
                    var rect = canvas.getBoundingClientRect();
                    canvas.width = rect.width * ratio;
                    canvas.height = rect.height * ratio;
                    context.setTransform(ratio, 0, 0, ratio, 0, 0);
                    resetCanvas();
                }

                function resetCanvas() {
                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, canvas.width, canvas.height);
                    context.lineWidth = 2.4;
                    context.lineCap = 'round';
                    context.lineJoin = 'round';
                    context.strokeStyle = '#111827';
                    hasInk = false;
                    input.value = '';
                }

                function point(event) {
                    var rect = canvas.getBoundingClientRect();
                    return {
                        x: event.clientX - rect.left,
                        y: event.clientY - rect.top
                    };
                }

                canvas.addEventListener('pointerdown', function (event) {
                    drawing = true;
                    hasInk = true;
                    canvas.setPointerCapture(event.pointerId);
                    var current = point(event);
                    context.beginPath();
                    context.moveTo(current.x, current.y);
                });

                canvas.addEventListener('pointermove', function (event) {
                    if (!drawing) {
                        return;
                    }

                    var current = point(event);
                    context.lineTo(current.x, current.y);
                    context.stroke();
                });

                function stopDrawing() {
                    if (drawing) {
                        drawing = false;
                        context.closePath();
                    }
                }

                canvas.addEventListener('pointerup', stopDrawing);
                canvas.addEventListener('pointerleave', stopDrawing);
                canvas.addEventListener('pointercancel', stopDrawing);

                clearButton.addEventListener('click', function () {
                    resetCanvas();
                });

                form.addEventListener('submit', function (event) {
                    if (!hasInk) {
                        event.preventDefault();
                        alert('Bubuhkan tanda tangan terlebih dahulu.');
                        return;
                    }

                    input.value = canvas.toDataURL('image/png');
                });

                resizeCanvas();
            });
        });
    </script>
@endpush
