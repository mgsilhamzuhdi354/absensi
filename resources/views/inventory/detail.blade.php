@extends('templates.dashboard')
@section('isi')
    @php
        $stockSaatIni = (float) $inventory->stock_quantity;
        $wholeStockStep = $inventory->usesWholeStock() ? '1' : '0.01';
        $minimumStockQuantity = $inventory->usesWholeStock() ? '1' : '0.01';
        $canStockOut = $stockSaatIni >= (float) $minimumStockQuantity;
        $maxStockQuantity = $inventory->usesWholeStock()
            ? (string) (int) $stockSaatIni
            : $inventory->formatStockValue($stockSaatIni);
    @endphp
    <div class="row">
        <div class="col-md-12 project-list">
            <div class="card">
                <div class="row">
                    <div class="col-md-6 mt-2 p-0 d-flex">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0 inventory-page-actions">
                        <a href="{{ url('/inventory/edit/'.$inventory->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <a href="{{ url('/inventory') }}" class="btn btn-danger btn-sm ms-2">Back</a>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="col-md-12">
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $inventory->nama_barang }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if ($inventory->foto_barang)
                            <div class="col-md-4 mb-3">
                                <img src="{{ asset('storage/'.$inventory->foto_barang) }}" alt="{{ $inventory->nama_barang }}" class="img-fluid rounded" style="max-height: 240px; object-fit: cover; width: 100%;">
                            </div>
                        @endif
                        <div class="{{ $inventory->foto_barang ? 'col-md-8' : 'col-md-12' }}">
                            <div class="table-responsive inventory-info-responsive">
                                <table class="table table-sm table-bordered inventory-info-table">
                                    <tbody>
                                        <tr>
                                            <th style="width: 210px;">Kode Barang</th>
                                            <td>{{ $inventory->kode_barang ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Jenis Barang</th>
                                            <td>{{ $inventory->jenis_barang ?? '-' }}</td>
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
                                            <th>Stok Saat Ini</th>
                                            <td>
                                                <strong>{{ $inventory->formatted_stock }}</strong>
                                                <span class="badge bg-light text-dark border">{{ $inventory->display_uom }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Stok Per Warna</th>
                                            <td>
                                                @include('partials.stock-color-summary', [
                                                    'variants' => $inventory->stockVariants,
                                                    'unit' => $inventory->display_uom,
                                                ])
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Notifikasi Stok</th>
                                            <td>
                                                @include('partials.stock-alert-toggle', [
                                                    'action' => url('/inventory/'.$inventory->id.'/stock-alert'),
                                                    'enabled' => $inventory->stock_alert_enabled ?? true,
                                                    'id' => 'inventory_stock_alert_detail_'.$inventory->id,
                                                ])
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Pemegang Saat Ini</th>
                                            <td>
                                                @if ($currentHolderTransaction)
                                                    <strong>{{ $currentHolderTransaction->penerima_barang ?? $currentHolderTransaction->penerima->name ?? '-' }}</strong>
                                                    <div class="small text-muted">
                                                        {{ $currentHolderTransaction->jabatan_penerima ?: ($currentHolderTransaction->penerima->Jabatan->nama_jabatan ?? '-') }}
                                                        @if ($currentHolderTransaction->tanggal_transaksi)
                                                            <span class="mx-1">|</span> Sejak {{ $currentHolderTransaction->tanggal_transaksi->format('d/m/Y') }}
                                                        @endif
                                                        <span class="mx-1">|</span> Jumlah {{ $inventory->formatStockValue($currentHolderTransaction->jumlah) }} {{ $inventory->display_uom }}
                                                        <span class="mx-1">|</span> Warna {{ $currentHolderTransaction->warna_barang ?? 'Umum' }}
                                                    </div>
                                                @else
                                                    <span class="badge bg-secondary">Belum pindah tangan</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Kondisi</th>
                                            <td>{{ $inventory->kondisi ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status Barang</th>
                                            <td>{{ $inventory->status_barang ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Lokasi</th>
                                            <td>{{ $inventory->lokasi->nama_lokasi ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Divisi / Jabatan</th>
                                            <td>{{ $inventory->jabatan->nama_jabatan ?? '-' }}</td>
                                        </tr>
                        <tr>
                            <th>Tanggal Masuk</th>
                            <td>{{ optional($inventory->tanggal_masuk)->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Bukti Pembelian / Nota</th>
                            <td>
                                @if ($inventory->bukti_pembelian)
                                    <a href="{{ url('/inventory/'.$inventory->id.'/purchase-proof/download') }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-download me-1"></i> Download Nota
                                    </a>
                                    <div class="small text-muted mt-1">{{ $inventory->bukti_pembelian_nama_asli ?: basename($inventory->bukti_pembelian) }}</div>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($inventory->bukti_pembelian)
        @php
            $proofExtension = strtolower(pathinfo($inventory->bukti_pembelian, PATHINFO_EXTENSION));
        @endphp
        <div class="mt-3">
            <h6>Bukti Pembelian / Nota</h6>
            @if (in_array($proofExtension, ['jpg', 'jpeg', 'png'], true))
                <a href="{{ asset('storage/'.$inventory->bukti_pembelian) }}" target="_blank">
                    <img src="{{ asset('storage/'.$inventory->bukti_pembelian) }}" alt="Bukti pembelian {{ $inventory->nama_barang }}" class="img-fluid rounded border" style="max-height: 260px; object-fit: contain;">
                </a>
            @else
                <a href="{{ url('/inventory/'.$inventory->id.'/purchase-proof/download') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-file-pdf me-1"></i> Download PDF Nota
                </a>
            @endif
        </div>
    @endif

    <h6>Spesifikasi</h6>
                    <p>{!! $inventory->spesifikasi ? nl2br(e($inventory->spesifikasi)) : '-' !!}</p>

                    <h6>Description</h6>
                    <p>{!! $inventory->desc ? nl2br(e($inventory->desc)) : '-' !!}</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">QR Code</h5>
                </div>
                <div class="card-body text-center">
                    @if ($inventory->qr_code_image)
                        <img src="{{ asset('storage/'.$inventory->qr_code_image) }}" alt="QR {{ $inventory->kode_barang }}" class="img-fluid mb-3 inventory-qr-image">
                    @else
                        <div class="alert alert-warning">QR Code belum dibuat.</div>
                    @endif
                    <div class="mb-3">
                        <small class="text-muted d-block text-break">{{ $inventory->qr_code_value }}</small>
                    </div>
                    <a href="{{ url('/inventory/'.$inventory->id.'/qr/print') }}" target="_blank" class="btn btn-primary btn-sm mb-2">Cetak Label QR</a>
                    <a href="{{ url('/inventory/'.$inventory->id.'/qr/download') }}" class="btn btn-info btn-sm mb-2">Download QR</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stok Masuk</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ url('/inventory/'.$inventory->id.'/stock-in') }}" class="inventory-stock-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal_transaksi" class="form-control" value="{{ old('tanggal_transaksi', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Jumlah Masuk</label>
                                <input type="number" step="{{ $wholeStockStep }}" min="{{ $minimumStockQuantity }}" name="jumlah" class="form-control" value="{{ old('jumlah') }}" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Warna Barang</label>
                            <input type="text" name="warna_barang" class="form-control" list="inventory_detail_color_options" value="{{ old('warna_barang', 'Umum') }}" placeholder="Contoh: Hitam, Putih, Abu-abu">
                        </div>
                        <div class="form-group">
                            <label>Sumber Barang / Supplier</label>
                            <input type="text" name="sumber_barang" class="form-control" value="{{ old('sumber_barang') }}">
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Kondisi Barang</label>
                                <input type="text" name="kondisi_barang" class="form-control" value="{{ old('kondisi_barang', $inventory->kondisi) }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Lokasi Penyimpanan</label>
                                <select name="lokasi_id" class="form-control selectpicker" data-live-search="true">
                                    <option value="">-- Tidak berubah --</option>
                                    @foreach ($lokasi as $lok)
                                        <option value="{{ $lok->id }}" {{ old('lokasi_id', $inventory->lokasi_id) == $lok->id ? 'selected' : '' }}>{{ $lok->nama_lokasi }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3">{{ old('catatan') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Simpan Stok Masuk</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stok Keluar / Pindah Tangan</h5>
                </div>
                <div class="card-body">
                    @if (!$canStockOut)
                        <div class="alert alert-warning">Stok tersedia kurang dari 1 {{ $inventory->display_uom }}. Stok keluar / pindah tangan belum bisa diproses.</div>
                    @endif
                    <form method="post" action="{{ url('/inventory/'.$inventory->id.'/stock-out') }}" class="inventory-stock-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal_transaksi" class="form-control" value="{{ old('tanggal_transaksi', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Jumlah Keluar</label>
                                <input type="number" step="{{ $wholeStockStep }}" min="{{ $minimumStockQuantity }}" max="{{ $maxStockQuantity }}" name="jumlah" class="form-control" value="{{ old('jumlah', $canStockOut ? $minimumStockQuantity : '') }}" {{ !$canStockOut ? 'disabled' : '' }} required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Warna Barang</label>
                            <input type="text" name="warna_barang" class="form-control" list="inventory_detail_color_options" value="{{ old('warna_barang', optional($inventory->stockVariants->firstWhere('stok', '>', 0))->warna_barang ?? 'Umum') }}" placeholder="Pilih warna yang tersedia" {{ !$canStockOut ? 'disabled' : '' }}>
                        </div>
                        <div class="form-group">
                            <label>Pilih Karyawan</label>
                            <select name="penerima_user_id" id="penerima_user_id" class="form-control selectpicker" data-live-search="true" {{ !$canStockOut ? 'disabled' : '' }}>
                                <option value="">-- Isi manual --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" data-name="{{ $user->name }}" data-divisi="{{ $user->Jabatan->nama_jabatan ?? '' }}" {{ old('penerima_user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Nama Penerima</label>
                                <input type="text" name="penerima_barang" id="penerima_barang" class="form-control" value="{{ old('penerima_barang') }}" {{ !$canStockOut ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Jabatan</label>
                                <input type="text" name="jabatan_penerima" id="jabatan_penerima" class="form-control" value="{{ old('jabatan_penerima') }}" {{ !$canStockOut ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Divisi</label>
                                <input type="text" name="departemen_penerima" id="departemen_penerima" class="form-control" value="{{ old('departemen_penerima') }}" {{ !$canStockOut ? 'disabled' : '' }}>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Keperluan Penggunaan</label>
                            <input type="text" name="keperluan" class="form-control" value="{{ old('keperluan') }}" {{ !$canStockOut ? 'disabled' : '' }}>
                        </div>
                        <div class="form-group">
                            <label>Kondisi Saat Diserahkan</label>
                            <input type="text" name="kondisi_barang" class="form-control" value="{{ old('kondisi_barang', $inventory->kondisi) }}" {{ !$canStockOut ? 'disabled' : '' }}>
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3" {{ !$canStockOut ? 'disabled' : '' }}>{{ old('catatan') }}</textarea>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="buat_bast_otomatis" name="buat_bast_otomatis" value="1" {{ old('buat_bast_otomatis', '1') ? 'checked' : '' }} {{ !$canStockOut ? 'disabled' : '' }}>
                                <label for="buat_bast_otomatis" class="form-check-label">
                                    Buat surat BAST otomatis saat pindah tangan
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mengetahui (HRD / Manager)</label>
                            <select name="known_by_user_id" class="form-control selectpicker" data-live-search="true" {{ !$canStockOut ? 'disabled' : '' }}>
                                <option value="">-- Pilih akun HRD / Manager --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('known_by_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}{{ $user->Jabatan ? ' - '.$user->Jabatan->nama_jabatan : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pihak Pertama / IT</label>
                            <select name="first_party_user_id" class="form-control selectpicker" data-live-search="true" {{ !$canStockOut ? 'disabled' : '' }}>
                                <option value="">-- Gunakan admin yang memproses --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('first_party_user_id', auth()->id()) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}{{ $user->Jabatan ? ' - '.$user->Jabatan->nama_jabatan : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger" {{ !$canStockOut ? 'disabled' : '' }}>Simpan Pindah Tangan</button>
                    </form>
                </div>
            </div>
        </div>

        <datalist id="inventory_detail_color_options">
            <option value="Umum">
            <option value="Hitam">
            <option value="Putih">
            <option value="Abu-abu">
            <option value="Biru">
            <option value="Merah">
            @foreach ($inventory->stockVariants as $variant)
                <option value="{{ $variant->warna_barang }}">
            @endforeach
        </datalist>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Riwayat Stok Barang</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive inventory-history-responsive">
                        <table class="table table-striped table-bordered inventory-history-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Warna</th>
                                    <th>Jumlah</th>
                                    <th>Stok Sebelum</th>
                                    <th>Stok Sesudah</th>
                                    <th>Diproses Oleh</th>
                                    <th>Penerima / Sumber</th>
                                    <th>Catatan</th>
                                    <th>BAST</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inventory->stockTransactions as $transaction)
                                    <tr>
                                        <td>{{ optional($transaction->tanggal_transaksi)->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($transaction->jenis_transaksi === 'keluar' && ($transaction->penerima_barang || $transaction->penerima_user_id))
                                                Keluar / Pindah Tangan
                                            @else
                                                {{ ucfirst($transaction->jenis_transaksi) }}
                                            @endif
                                        </td>
                                        <td>{{ $transaction->warna_barang ?? 'Umum' }}</td>
                                        <td>{{ $inventory->formatStockValue($transaction->jumlah) }}</td>
                                        <td>{{ $inventory->formatStockValue($transaction->stok_sebelum) }}</td>
                                        <td>{{ $inventory->formatStockValue($transaction->stok_sesudah) }}</td>
                                        <td>{{ $transaction->processedBy->name ?? '-' }}</td>
                                        <td>
                                            @if ($transaction->jenis_transaksi === 'keluar')
                                                {{ $transaction->penerima_barang ?? '-' }}
                                            @else
                                                {{ $transaction->sumber_barang ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{!! $transaction->catatan ? nl2br(e($transaction->catatan)) : '-' !!}</td>
                                        <td style="min-width: 220px;">
                                            @if ($transaction->jenis_transaksi === 'keluar')
                                                @if ($transaction->bastDocument)
                                                    <a href="{{ url('/inventory/bast/'.$transaction->bastDocument->id.'/download') }}" class="btn btn-sm btn-info">Download BAST</a>
                                                    <div class="small mt-1">{{ $transaction->bastDocument->nomor_surat }}</div>
                                                    @php
                                                        $bast = $transaction->bastDocument;
                                                        $signatureRows = [
                                                            ['label' => 'Penerima', 'assigned' => (bool) $transaction->penerima_user_id, 'signed_at' => $bast->signed_at, 'name' => $bast->receiver_signature_name],
                                                            ['label' => 'HRD', 'assigned' => (bool) $bast->known_by_user_id, 'signed_at' => $bast->known_signed_at, 'name' => $bast->known_signature_name],
                                                            ['label' => 'IT', 'assigned' => (bool) $bast->first_party_user_id, 'signed_at' => $bast->first_party_signed_at, 'name' => $bast->first_party_signature_name],
                                                        ];
                                                    @endphp
                                                    @foreach ($signatureRows as $signatureRow)
                                                        @if ($signatureRow['signed_at'])
                                                            <span class="badge bg-success mt-1">TTD {{ $signatureRow['label'] }}: {{ $signatureRow['name'] ?? '-' }}</span>
                                                            <div class="small text-muted">{{ $signatureRow['signed_at']->format('d/m/Y H:i') }}</div>
                                                        @elseif ($signatureRow['assigned'])
                                                            <span class="badge bg-warning text-dark mt-1">Menunggu TTD {{ $signatureRow['label'] }}</span>
                                                        @endif
                                                    @endforeach
                                                    <details class="mt-2">
                                                        <summary class="btn btn-sm btn-outline-warning">Edit Detail BAST</summary>
                                                        <form method="post" action="{{ url('/inventory/bast/'.$bast->id) }}" class="mt-2">
                                                            @method('put')
                                                            @csrf
                                                            <input type="date" name="tanggal_surat" class="form-control form-control-sm mb-1" value="{{ old('tanggal_surat', optional($bast->tanggal_surat)->format('Y-m-d')) }}" required>
                                                            <div class="small fw-bold mt-2">Pihak Pertama</div>
                                                            <input type="text" name="nama_penyerah" class="form-control form-control-sm mb-1" value="{{ old('nama_penyerah', $bast->nama_penyerah) }}" placeholder="Nama pihak pertama">
                                                            <input type="text" name="jabatan_penyerah" class="form-control form-control-sm mb-1" value="{{ old('jabatan_penyerah', $bast->jabatan_penyerah) }}" placeholder="Jabatan pihak pertama">
                                                            <input type="text" name="departemen_penyerah" class="form-control form-control-sm mb-1" value="{{ old('departemen_penyerah', $bast->departemen_penyerah ?: $bast->jabatan_penyerah) }}" placeholder="Departemen pihak pertama">
                                                            <div class="small fw-bold mt-2">Pihak Kedua</div>
                                                            <input type="text" name="nama_penerima" class="form-control form-control-sm mb-1" value="{{ old('nama_penerima', $bast->nama_penerima) }}" placeholder="Nama pihak kedua">
                                                            <input type="text" name="jabatan_penerima" class="form-control form-control-sm mb-1" value="{{ old('jabatan_penerima', $bast->jabatan_penerima) }}" placeholder="Jabatan pihak kedua">
                                                            <input type="text" name="departemen_penerima" class="form-control form-control-sm mb-1" value="{{ old('departemen_penerima', $bast->departemen_penerima ?: ($transaction->departemen_penerima ?: $bast->jabatan_penerima)) }}" placeholder="Departemen pihak kedua">
                                                            <input type="text" name="nama_mengetahui" class="form-control form-control-sm mb-1" value="{{ old('nama_mengetahui', $bast->nama_mengetahui) }}" placeholder="Nama mengetahui">
                                                            <button type="submit" class="btn btn-sm btn-warning w-100">Simpan & Regenerate PDF</button>
                                                        </form>
                                                    </details>
                                                @else
                                                    <form method="post" action="{{ url('/inventory/transactions/'.$transaction->id.'/bast') }}">
                                                        @csrf
                                                        <input type="date" name="tanggal_surat" class="form-control form-control-sm mb-1" value="{{ date('Y-m-d') }}">
                                                        <select name="known_by_user_id" class="form-control form-control-sm mb-1">
                                                            <option value="">-- Pilih HRD / Manager --</option>
                                                            @foreach ($users as $user)
                                                                <option value="{{ $user->id }}">{{ $user->name }}{{ $user->Jabatan ? ' - '.$user->Jabatan->nama_jabatan : '' }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="first_party_user_id" class="form-control form-control-sm mb-1">
                                                            <option value="">-- Gunakan admin yang memproses --</option>
                                                            @foreach ($users as $user)
                                                                <option value="{{ $user->id }}" {{ auth()->id() == $user->id ? 'selected' : '' }}>{{ $user->name }}{{ $user->Jabatan ? ' - '.$user->Jabatan->nama_jabatan : '' }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-primary">Buat Surat BAST</button>
                                                    </form>
                                                @endif
                                            @elseif (($inventoryReturnTablesReady ?? false) && $transaction->jenis_transaksi === 'masuk' && $transaction->returnDocument)
                                                <a href="{{ url('/exit/asset-return/'.$transaction->returnDocument->id.'/download') }}" class="btn btn-sm btn-info">Download BAST Pengembalian</a>
                                                <div class="small mt-1">{{ $transaction->returnDocument->nomor_surat }}</div>
                                                <span class="badge bg-success mt-1">Pengembalian Pegawai Keluar</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td style="min-width: 110px;">
                                            <form method="post" action="{{ url('/inventory/transactions/'.$transaction->id) }}" class="d-inline inventory-delete-form" data-confirm="Hapus riwayat stok ini? Stok barang akan disesuaikan dan nama penghapus akan dicatat.">
                                                @method('delete')
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">Belum ada riwayat stok.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Riwayat Stok Dihapus</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive inventory-history-responsive">
                        <table class="table table-striped table-bordered inventory-history-table">
                            <thead>
                                <tr>
                                    <th>Tanggal Transaksi</th>
                                    <th>Jenis</th>
                                    <th>Warna</th>
                                    <th>Jumlah</th>
                                    <th>Diproses Oleh</th>
                                    <th>Penerima / Sumber</th>
                                    <th>BAST</th>
                                    <th>Dihapus Oleh</th>
                                    <th>Waktu Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($deletedStockTransactions as $transaction)
                                    <tr>
                                        <td>{{ optional($transaction->tanggal_transaksi)->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($transaction->jenis_transaksi === 'keluar' && ($transaction->penerima_barang || $transaction->penerima_user_id))
                                                Keluar / Pindah Tangan
                                            @else
                                                {{ ucfirst($transaction->jenis_transaksi) }}
                                            @endif
                                        </td>
                                        <td>{{ $transaction->warna_barang ?? 'Umum' }}</td>
                                        <td>{{ $inventory->formatStockValue($transaction->jumlah) }}</td>
                                        <td>{{ $transaction->processedBy->name ?? '-' }}</td>
                                        <td>
                                            @if ($transaction->jenis_transaksi === 'keluar')
                                                {{ $transaction->penerima_barang ?? '-' }}
                                            @else
                                                {{ $transaction->sumber_barang ?? '-' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($transaction->bastDocument)
                                                <a href="{{ url('/inventory/bast/'.$transaction->bastDocument->id.'/download') }}" class="btn btn-sm btn-info">Download BAST</a>
                                                <div class="small mt-1">{{ $transaction->bastDocument->nomor_surat }}</div>
                                                @php
                                                    $bast = $transaction->bastDocument;
                                                    $signatureRows = [
                                                        ['label' => 'Penerima', 'assigned' => (bool) $transaction->penerima_user_id, 'signed_at' => $bast->signed_at, 'name' => $bast->receiver_signature_name],
                                                        ['label' => 'HRD', 'assigned' => (bool) $bast->known_by_user_id, 'signed_at' => $bast->known_signed_at, 'name' => $bast->known_signature_name],
                                                        ['label' => 'IT', 'assigned' => (bool) $bast->first_party_user_id, 'signed_at' => $bast->first_party_signed_at, 'name' => $bast->first_party_signature_name],
                                                    ];
                                                @endphp
                                                @foreach ($signatureRows as $signatureRow)
                                                    @if ($signatureRow['signed_at'])
                                                        <span class="badge bg-success mt-1">TTD {{ $signatureRow['label'] }}: {{ $signatureRow['name'] ?? '-' }}</span>
                                                        <div class="small text-muted">{{ $signatureRow['signed_at']->format('d/m/Y H:i') }}</div>
                                                    @elseif ($signatureRow['assigned'])
                                                        <span class="badge bg-warning text-dark mt-1">Menunggu TTD {{ $signatureRow['label'] }}</span>
                                                    @endif
                                                @endforeach
                                            @elseif (($inventoryReturnTablesReady ?? false) && $transaction->returnDocument)
                                                <a href="{{ url('/exit/asset-return/'.$transaction->returnDocument->id.'/download') }}" class="btn btn-sm btn-info">Download BAST Pengembalian</a>
                                                <div class="small mt-1">{{ $transaction->returnDocument->nomor_surat }}</div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $transaction->deletedBy->name ?? '-' }}</td>
                                        <td>{{ optional($transaction->deleted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Belum ada riwayat stok yang dihapus.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.stock-alert-toggle-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitting === '1') {
                        event.preventDefault();
                        return;
                    }

                    form.dataset.submitting = '1';
                    form.style.pointerEvents = 'none';
                });
            });

            var receiverSelect = document.getElementById('penerima_user_id');
            if (!receiverSelect) {
                return;
            }

            function fillReceiverFields() {
                var option = receiverSelect.options[receiverSelect.selectedIndex];
                var nameInput = document.getElementById('penerima_barang');
                var jabatanInput = document.getElementById('jabatan_penerima');
                var departemenInput = document.getElementById('departemen_penerima');

                if (!option || !option.value) {
                    nameInput.readOnly = false;
                    jabatanInput.readOnly = false;
                    departemenInput.readOnly = false;
                    return;
                }

                var divisi = option.getAttribute('data-divisi') || '';
                nameInput.value = option.getAttribute('data-name') || option.text;
                jabatanInput.value = divisi;
                departemenInput.value = divisi;
                nameInput.readOnly = true;
                jabatanInput.readOnly = true;
                departemenInput.readOnly = true;
            }

            receiverSelect.addEventListener('change', fillReceiverFields);

            if (window.jQuery) {
                window.jQuery(receiverSelect).on('changed.bs.select change', fillReceiverFields);
            }

            fillReceiverFields();

            document.querySelectorAll('.inventory-delete-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitting === '1') {
                        event.preventDefault();
                        return;
                    }

                    if (!confirm(form.dataset.confirm || 'Hapus data ini?')) {
                        event.preventDefault();
                        return;
                    }

                    form.dataset.submitting = '1';
                    form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                        button.disabled = true;
                        button.textContent = 'Menghapus...';
                    });
                });
            });
        });
    </script>
@endpush
