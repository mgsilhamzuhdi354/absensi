@extends('templates.dashboard')
@section('isi')
    <div class="row">
        <div class="col-md-12 project-list">
            <div class="card">
                <div class="row">
                    <div class="col-md-7 mt-2 p-0 d-flex">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-5 p-0 text-end">
                        <a href="{{ url('/exit') }}" class="btn btn-danger btn-sm ms-2">Back</a>
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

        @if (session('success'))
            <div class="col-md-12">
                <div class="alert alert-success">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="col-md-12">
                <div class="alert alert-danger">{{ session('error') }}</div>
            </div>
        @endif

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $pegawai_keluar->user->name ?? '-' }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Tanggal Keluar</strong>
                            <div>{{ $pegawai_keluar->tanggal ? \Carbon\Carbon::parse($pegawai_keluar->tanggal)->format('d/m/Y') : '-' }}</div>
                        </div>
                        <div class="col-md-3">
                            <strong>Jenis</strong>
                            <div>{{ $pegawai_keluar->jenis ?? '-' }}</div>
                        </div>
                        <div class="col-md-3">
                            <strong>Status Exit</strong>
                            <div>{{ $pegawai_keluar->status ?? '-' }}</div>
                        </div>
                        <div class="col-md-3">
                            <strong>Approval</strong>
                            <div>{{ $pegawai_keluar->approvedBy->name ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Aset Yang Harus Dikembalikan</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="min-width: 190px;">Aset</th>
                                    <th style="min-width: 120px;">Jumlah</th>
                                    <th style="min-width: 170px;">Tanggal Serah Terima</th>
                                    <th style="min-width: 150px;">Status</th>
                                    <th style="min-width: 380px;">Pengembalian / Pengecualian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clearances as $clearance)
                                    @php
                                        $transaction = $clearance->originalTransaction;
                                        $inventory = $transaction ? $transaction->inventory : null;
                                        $returnDocument = $clearance->returnDocument;
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $inventory->nama_barang ?? 'Aset kantor' }}</strong>
                                            <div class="small text-muted">{{ $inventory->kode_barang ?? '-' }}</div>
                                            <div class="small text-muted">SN: {{ $inventory->serial_number ?? '-' }}</div>
                                        </td>
                                        <td>
                                            {{ $inventory ? $inventory->formatStockValue($transaction->jumlah) : ($transaction->jumlah ?? '-') }}
                                            {{ $inventory ? $inventory->display_uom : '' }}
                                        </td>
                                        <td>{{ optional($transaction->tanggal_transaksi)->format('d/m/Y') ?? '-' }}</td>
                                        <td>
                                            @if ($clearance->status === \App\Models\PenyelesaianAsetPegawaiKeluar::STATUS_RETURNED)
                                                <span class="badge bg-success">{{ $clearance->status_label }}</span>
                                                @if ($returnDocument)
                                                    <div class="small mt-2">{{ $returnDocument->nomor_surat }}</div>
                                                    <a href="{{ url('/exit/asset-return/'.$returnDocument->id.'/download') }}" class="btn btn-sm btn-info mt-2">Download BAST</a>
                                                @endif
                                            @elseif ($clearance->status === \App\Models\PenyelesaianAsetPegawaiKeluar::STATUS_WAIVED)
                                                <span class="badge bg-secondary">{{ $clearance->status_label }}</span>
                                                <div class="small mt-2">{{ $clearance->waiver_reason }}</div>
                                                <div class="small text-muted">{{ $clearance->waivedBy->name ?? '-' }}</div>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ $clearance->status_label }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($clearance->status === \App\Models\PenyelesaianAsetPegawaiKeluar::STATUS_PENDING && $transaction)
                                                <form method="post" action="{{ url('/exit/'.$pegawai_keluar->id.'/assets/'.$transaction->id.'/return') }}" class="mb-3">
                                                    @csrf
                                                    <div class="row">
                                                        <div class="col-md-6 form-group">
                                                            <label>Tanggal Kembali</label>
                                                            <input type="date" name="tanggal_kembali" class="form-control form-control-sm" value="{{ old('tanggal_kembali', date('Y-m-d')) }}" required>
                                                        </div>
                                                        <div class="col-md-6 form-group">
                                                            <label>Kondisi Kembali</label>
                                                            <input type="text" name="kondisi_barang" class="form-control form-control-sm" value="{{ old('kondisi_barang', $inventory->kondisi ?? 'Baik') }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 form-group">
                                                            <label>Kelengkapan</label>
                                                            <input type="text" name="kelengkapan" class="form-control form-control-sm" value="{{ old('kelengkapan', 'Lengkap') }}" required>
                                                        </div>
                                                        <div class="col-md-6 form-group">
                                                            <label>Status Barang</label>
                                                            <input type="text" name="status_barang" class="form-control form-control-sm" value="{{ old('status_barang', 'Tersedia') }}">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4 form-group">
                                                            <label>Lokasi Simpan</label>
                                                            <select name="lokasi_id" class="form-control form-control-sm">
                                                                <option value="">-- Tidak berubah --</option>
                                                                @foreach ($lokasi as $lok)
                                                                    <option value="{{ $lok->id }}" {{ old('lokasi_id', $inventory->lokasi_id ?? null) == $lok->id ? 'selected' : '' }}>{{ $lok->nama_lokasi }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4 form-group">
                                                            <label>Penerima IT</label>
                                                            <select name="it_receiver_user_id" class="form-control form-control-sm">
                                                                <option value="">-- Admin ini --</option>
                                                                @foreach ($users as $user)
                                                                    <option value="{{ $user->id }}" {{ old('it_receiver_user_id', auth()->id()) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4 form-group">
                                                            <label>Mengetahui</label>
                                                            <select name="known_by_user_id" class="form-control form-control-sm">
                                                                <option value="">-- Tidak ada --</option>
                                                                @foreach ($users as $user)
                                                                    <option value="{{ $user->id }}" {{ old('known_by_user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Catatan</label>
                                                        <textarea name="catatan" class="form-control form-control-sm" rows="2">{{ old('catatan') }}</textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-success btn-sm">Proses Pengembalian</button>
                                                </form>

                                                <form method="post" action="{{ url('/exit/'.$pegawai_keluar->id.'/assets/'.$transaction->id.'/waive') }}">
                                                    @csrf
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="waiver_reason" class="form-control" placeholder="Alasan pengecualian" required>
                                                        <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('Beri pengecualian untuk aset ini?')">Beri Pengecualian</button>
                                                    </div>
                                                </form>
                                            @else
                                                <span class="text-muted">Selesai</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada aset aktif yang harus dikembalikan.</td>
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
