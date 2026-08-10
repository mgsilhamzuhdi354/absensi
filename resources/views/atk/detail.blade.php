@extends('templates.dashboard')

@section('isi')
    @php
        $stockSaatIni = round(max(0, (float) ($atk->stok ?? 0)), 2);
        $canStockOut = $stockSaatIni >= 0.01;
        $maxStockQuantity = $atk->formatStockValue($stockSaatIni);
    @endphp
    <div class="row atk-detail-page">
        <div class="col-md-12 project-list">
            <div class="card">
                <div class="row align-items-center">
                    <div class="col-md-6 mt-2 p-0 d-flex">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0 atk-detail-actions">
                        <a href="{{ url('/atk/edit/'.$atk->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <a href="{{ url('/atk') }}" class="btn btn-danger btn-sm ms-2">Back</a>
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
                    <h5 class="mb-0">{{ $atk->nama_atk }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if ($atk->foto_barang)
                            <div class="col-md-4 mb-3">
                                <img src="{{ asset('storage/'.$atk->foto_barang) }}" alt="{{ $atk->nama_atk }}" class="img-fluid rounded atk-detail-photo">
                            </div>
                        @endif
                        <div class="{{ $atk->foto_barang ? 'col-md-8' : 'col-md-12' }}">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <tbody>
                                        <tr>
                                            <th style="width: 210px;">Kode ATK</th>
                                            <td>{{ $atk->kode_atk ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Perusahaan</th>
                                            <td>{{ $atk->company->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Nama ATK</th>
                                            <td>{{ $atk->nama_atk ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Kategori</th>
                                            <td>{{ $atk->kategori ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Stok Saat Ini</th>
                                            <td>
                                                <strong>{{ $atk->formatted_stock }}</strong>
                                                <span class="badge bg-light text-dark border">{{ $atk->satuan ?? '-' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Stok Per Warna</th>
                                            <td>
                                                @include('partials.stock-color-summary', [
                                                    'variants' => $atk->stockVariants,
                                                    'unit' => $atk->satuan,
                                                ])
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Notifikasi Stok</th>
                                            <td>
                                                @include('partials.stock-alert-toggle', [
                                                    'action' => url('/atk/'.$atk->id.'/stock-alert'),
                                                    'enabled' => $atk->stock_alert_enabled ?? true,
                                                    'id' => 'atk_stock_alert_detail_'.$atk->id,
                                                ])
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Lokasi</th>
                                            <td>{{ $atk->lokasi ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                @if ($atk->active == 1)
                                                    <span class="badge badge-success">Aktif</span>
                                                @else
                                                    <span class="badge badge-danger">Non-Aktif</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Dibuat</th>
                                            <td>{{ optional($atk->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Update</th>
                                            <td>{{ optional($atk->updated_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <h6>Keterangan</h6>
                    <p>{!! $atk->keterangan ? nl2br(e($atk->keterangan)) : '-' !!}</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">QR Code</h5>
                </div>
                <div class="card-body text-center">
                    @if ($atk->qr_code_image)
                        <img src="{{ asset('storage/'.$atk->qr_code_image) }}" alt="QR {{ $atk->kode_atk }}" class="img-fluid mb-3" style="max-width: 240px;">
                    @else
                        <div class="alert alert-warning">QR Code belum dibuat.</div>
                    @endif
                    <div class="mb-3">
                        <small class="text-muted d-block text-break">{{ $atk->qr_code_value }}</small>
                    </div>
                    <a href="{{ url('/atk/'.$atk->id.'/qr/print') }}" target="_blank" class="btn btn-primary btn-sm mb-2">Cetak Label QR</a>
                    <a href="{{ url('/atk/'.$atk->id.'/qr/download') }}" class="btn btn-info btn-sm mb-2">Download QR</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Transfer Perusahaan</h5>
                </div>
                <div class="card-body">
                    @if (!$canStockOut)
                        <div class="alert alert-warning">Stok tersedia kosong. Transfer belum bisa diproses.</div>
                    @elseif (($transferCompanies ?? collect())->isEmpty())
                        <div class="alert alert-warning">Belum ada perusahaan tujuan aktif.</div>
                    @endif
                    <form method="post" action="{{ url('/atk/'.$atk->id.'/transfer-company') }}">
                        @csrf
                        <div class="form-group">
                            <label>Perusahaan Tujuan</label>
                            <select name="destination_company_id" class="form-control" {{ !$canStockOut || ($transferCompanies ?? collect())->isEmpty() ? 'disabled' : '' }} required>
                                <option value="">-- Pilih perusahaan --</option>
                                @foreach (($transferCompanies ?? collect()) as $company)
                                    <option value="{{ $company->id }}" {{ old('destination_company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }} ({{ $company->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal_transfer" class="form-control" value="{{ old('tanggal_transfer', date('Y-m-d')) }}" {{ !$canStockOut ? 'disabled' : '' }} required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Jumlah</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $maxStockQuantity }}" name="jumlah" class="form-control" value="{{ old('jumlah') }}" {{ !$canStockOut ? 'disabled' : '' }} required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Warna Barang</label>
                            <input type="text" name="warna_barang" class="form-control" list="atk_detail_color_options" value="{{ old('warna_barang', optional($atk->stockVariants->firstWhere('stok', '>', 0))->warna_barang ?? 'Umum') }}" {{ !$canStockOut ? 'disabled' : '' }}>
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3" {{ !$canStockOut ? 'disabled' : '' }}>{{ old('catatan') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" {{ !$canStockOut || ($transferCompanies ?? collect())->isEmpty() ? 'disabled' : '' }}>Transfer ATK</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stok Masuk</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ url('/atk/'.$atk->id.'/stock-in') }}">
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
                            <label>Warna Barang</label>
                            <input type="text" name="warna_barang" class="form-control" list="atk_detail_color_options" value="{{ old('warna_barang', 'Umum') }}" placeholder="Contoh: Merah, Hitam, Biru">
                        </div>
                        <div class="form-group">
                            <label>Sumber Barang / Supplier</label>
                            <input type="text" name="sumber_barang" class="form-control" value="{{ old('sumber_barang') }}">
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
                    <h5 class="mb-0">Stok Keluar</h5>
                </div>
                <div class="card-body">
                    @if (!$canStockOut)
                        <div class="alert alert-warning">Stok tersedia kosong. Stok keluar belum bisa diproses.</div>
                    @endif
                    <form method="post" action="{{ url('/atk/'.$atk->id.'/stock-out') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal_transaksi" class="form-control" value="{{ old('tanggal_transaksi', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Jumlah Keluar</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $maxStockQuantity }}" name="jumlah" class="form-control" value="{{ old('jumlah') }}" {{ !$canStockOut ? 'disabled' : '' }} required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Warna Barang</label>
                            <input type="text" name="warna_barang" class="form-control" list="atk_detail_color_options" value="{{ old('warna_barang', optional($atk->stockVariants->firstWhere('stok', '>', 0))->warna_barang ?? 'Umum') }}" placeholder="Pilih warna yang tersedia" {{ !$canStockOut ? 'disabled' : '' }}>
                        </div>
                        <div class="form-group">
                            <label>Penerima Barang</label>
                            <input type="text" name="penerima_barang" class="form-control" value="{{ old('penerima_barang') }}" {{ !$canStockOut ? 'disabled' : '' }}>
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3" {{ !$canStockOut ? 'disabled' : '' }}>{{ old('catatan') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" {{ !$canStockOut ? 'disabled' : '' }}>Simpan Stok Keluar</button>
                    </form>
                </div>
            </div>
        </div>

        <datalist id="atk_detail_color_options">
            <option value="Umum">
            <option value="Merah">
            <option value="Hitam">
            <option value="Biru">
            <option value="Putih">
            <option value="Hijau">
            @foreach ($atk->stockVariants as $variant)
                <option value="{{ $variant->warna_barang }}">
            @endforeach
        </datalist>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Riwayat Stok Barang</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive atk-history-responsive">
                        <table class="table table-striped table-bordered atk-history-table">
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
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($atk->stockTransactions as $transaction)
                                    <tr>
                                        <td>{{ optional($transaction->tanggal_transaksi)->format('d/m/Y') }}</td>
                                        <td>{{ $transaction->jenis_transaksi === 'keluar' ? 'Keluar' : 'Masuk' }}</td>
                                        <td>{{ $transaction->warna_barang ?? 'Umum' }}</td>
                                        <td>{{ $atk->formatStockValue($transaction->jumlah) }} {{ $atk->satuan }}</td>
                                        <td>{{ $atk->formatStockValue($transaction->stok_sebelum) }}</td>
                                        <td>{{ $atk->formatStockValue($transaction->stok_sesudah) }}</td>
                                        <td>{{ $transaction->processedBy->name ?? '-' }}</td>
                                        <td>
                                            @if ($transaction->jenis_transaksi === 'keluar')
                                                {{ $transaction->penerima_barang ?? '-' }}
                                            @else
                                                {{ $transaction->sumber_barang ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{!! $transaction->catatan ? nl2br(e($transaction->catatan)) : '-' !!}</td>
                                        <td>
                                            <form method="post" action="{{ url('/atk/transactions/'.$transaction->id) }}" class="d-inline atk-delete-transaction-form" data-confirm="Hapus riwayat stok ini? Stok ATK akan disesuaikan.">
                                                @method('delete')
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
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
                    <div class="table-responsive atk-history-responsive">
                        <table class="table table-striped table-bordered atk-history-table">
                            <thead>
                                <tr>
                                    <th>Tanggal Transaksi</th>
                                    <th>Jenis</th>
                                    <th>Warna</th>
                                    <th>Jumlah</th>
                                    <th>Diproses Oleh</th>
                                    <th>Penerima / Sumber</th>
                                    <th>Dihapus Oleh</th>
                                    <th>Waktu Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($deletedStockTransactions as $transaction)
                                    <tr>
                                        <td>{{ optional($transaction->tanggal_transaksi)->format('d/m/Y') }}</td>
                                        <td>{{ $transaction->jenis_transaksi === 'keluar' ? 'Keluar' : 'Masuk' }}</td>
                                        <td>{{ $transaction->warna_barang ?? 'Umum' }}</td>
                                        <td>{{ $atk->formatStockValue($transaction->jumlah) }} {{ $atk->satuan }}</td>
                                        <td>{{ $transaction->processedBy->name ?? '-' }}</td>
                                        <td>
                                            @if ($transaction->jenis_transaksi === 'keluar')
                                                {{ $transaction->penerima_barang ?? '-' }}
                                            @else
                                                {{ $transaction->sumber_barang ?? '-' }}
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

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Riwayat Transfer Perusahaan</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive atk-history-responsive">
                        <table class="table table-striped table-bordered atk-history-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Dari</th>
                                    <th>Ke</th>
                                    <th>Jumlah</th>
                                    <th>Warna</th>
                                    <th>Diproses Oleh</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($assetTransfers ?? collect()) as $transfer)
                                    <tr>
                                        <td>{{ optional($transfer->tanggal_transfer)->format('d/m/Y') }}</td>
                                        <td>{{ $transfer->sourceCompany->name ?? '-' }}</td>
                                        <td>{{ $transfer->destinationCompany->name ?? '-' }}</td>
                                        <td>{{ $atk->formatStockValue($transfer->jumlah) }} {{ $atk->satuan }}</td>
                                        <td>{{ $transfer->warna_barang ?? 'Umum' }}</td>
                                        <td>{{ $transfer->processedBy->name ?? '-' }}</td>
                                        <td>{!! $transfer->catatan ? nl2br(e($transfer->catatan)) : '-' !!}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Belum ada transfer perusahaan.</td>
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

@push('style')
    <style>
        .atk-detail-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .atk-detail-photo {
            max-height: 240px;
            width: 100%;
            object-fit: cover;
            border: 1px solid #e5e7eb;
        }

        .atk-history-responsive {
            max-height: 420px;
            overflow: auto;
        }

        .atk-history-table th,
        .atk-history-table td {
            min-width: 120px;
            vertical-align: middle;
        }

        @media (max-width: 767.98px) {
            .atk-detail-actions {
                justify-content: flex-start;
                margin-top: 10px;
            }

            .atk-history-responsive {
                max-height: 60vh;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.atk-delete-transaction-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitting === '1') {
                        event.preventDefault();
                        return;
                    }

                    if (!confirm(form.dataset.confirm || 'Hapus riwayat stok ini?')) {
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
        });
    </script>
@endpush
