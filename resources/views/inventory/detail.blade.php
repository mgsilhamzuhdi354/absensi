@extends('templates.dashboard')
@section('isi')
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
                                            <td><strong>{{ number_format($inventory->stok ?? 0, 2) }}</strong> {{ $inventory->uom ?? '' }}</td>
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
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

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
                                <input type="number" step="0.01" min="0.01" name="jumlah" class="form-control" value="{{ old('jumlah') }}" required>
                            </div>
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
                    @if (($inventory->stok ?? 0) <= 0)
                        <div class="alert alert-warning">Stok kosong. Stok keluar belum bisa diproses.</div>
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
                                <input type="number" step="0.01" min="0.01" name="jumlah" class="form-control" value="{{ old('jumlah') }}" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }} required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Pilih Karyawan</label>
                            <select name="penerima_user_id" id="penerima_user_id" class="form-control selectpicker" data-live-search="true" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>
                                <option value="">-- Isi manual --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" data-name="{{ $user->name }}" data-divisi="{{ $user->Jabatan->nama_jabatan ?? '' }}" {{ old('penerima_user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Nama Penerima</label>
                                <input type="text" name="penerima_barang" id="penerima_barang" class="form-control" value="{{ old('penerima_barang') }}" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Jabatan</label>
                                <input type="text" name="jabatan_penerima" id="jabatan_penerima" class="form-control" value="{{ old('jabatan_penerima') }}" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Divisi</label>
                                <input type="text" name="departemen_penerima" id="departemen_penerima" class="form-control" value="{{ old('departemen_penerima') }}" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Keperluan Penggunaan</label>
                            <input type="text" name="keperluan" class="form-control" value="{{ old('keperluan') }}" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>
                        </div>
                        <div class="form-group">
                            <label>Kondisi Saat Diserahkan</label>
                            <input type="text" name="kondisi_barang" class="form-control" value="{{ old('kondisi_barang', $inventory->kondisi) }}" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>{{ old('catatan') }}</textarea>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="buat_bast_otomatis" name="buat_bast_otomatis" value="1" {{ old('buat_bast_otomatis', '1') ? 'checked' : '' }} {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>
                                <label for="buat_bast_otomatis" class="form-check-label">
                                    Buat surat BAST otomatis saat pindah tangan
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mengetahui (HRD / Manager)</label>
                            <input type="text" name="nama_mengetahui" class="form-control" value="{{ old('nama_mengetahui') }}" placeholder="Contoh: Rina Amelia" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>
                        </div>
                        <button type="submit" class="btn btn-danger" {{ ($inventory->stok ?? 0) <= 0 ? 'disabled' : '' }}>Simpan Pindah Tangan</button>
                    </form>
                </div>
            </div>
        </div>

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
                                        <td>{{ number_format($transaction->jumlah, 2) }}</td>
                                        <td>{{ number_format($transaction->stok_sebelum, 2) }}</td>
                                        <td>{{ number_format($transaction->stok_sesudah, 2) }}</td>
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
                                                @else
                                                    <form method="post" action="{{ url('/inventory/transactions/'.$transaction->id.'/bast') }}">
                                                        @csrf
                                                        <input type="date" name="tanggal_surat" class="form-control form-control-sm mb-1" value="{{ date('Y-m-d') }}">
                                                        <input type="text" name="nama_mengetahui" class="form-control form-control-sm mb-1" placeholder="Nama HRD / Manager">
                                                        <button type="submit" class="btn btn-sm btn-primary">Buat Surat BAST</button>
                                                    </form>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td style="min-width: 110px;">
                                            <form method="post" action="{{ url('/inventory/transactions/'.$transaction->id) }}" class="d-inline">
                                                @method('delete')
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus riwayat stok ini? Stok barang akan disesuaikan dan nama penghapus akan dicatat.')">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">Belum ada riwayat stok.</td>
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
                                        <td>{{ number_format($transaction->jumlah, 2) }}</td>
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
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $transaction->deletedBy->name ?? '-' }}</td>
                                        <td>{{ optional($transaction->deleted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Belum ada riwayat stok yang dihapus.</td>
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
        });
    </script>
@endpush
