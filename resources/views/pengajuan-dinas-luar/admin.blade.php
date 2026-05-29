@extends('templates.dashboard')
@section('isi')
<div class="row">
    <div class="col-md-12 project-list">
        <div class="card">
            <div class="row">
                <div class="col-md-6 mt-2 p-0 d-flex">
                    <h4>{{ $title }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form action="{{ url('/data-pengajuan-dinas') }}">
                    <div class="row">
                        <div class="col-3">
                            <select name="user_id" class="form-control selectpicker" data-live-search="true">
                                <option value="">-- Semua Karyawan --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-2">
                            <input type="date" class="form-control" name="mulai" value="{{ request('mulai') }}" placeholder="Tgl Mulai">
                        </div>
                        <div class="col-2">
                            <input type="date" class="form-control" name="akhir" value="{{ request('akhir') }}" placeholder="Tgl Akhir">
                        </div>
                        <div class="col-2">
                            <select name="status" class="form-control">
                                <option value="">-- Semua Status --</option>
                                <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                                <option value="Ditolak" {{ request('status') == 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        </div>
                        <div class="col-1">
                            <button type="submit" class="border-0 mt-2" style="background-color:transparent;"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped text-center" id="mytable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Karyawan</th>
                                <th>Shift</th>
                                <th>Tgl Mulai</th>
                                <th>Tgl Akhir</th>
                                <th>Lokasi Tujuan</th>
                                <th>Alasan</th>
                                <th>Foto Bukti</th>
                                <th>Status</th>
                                <th>Diproses Oleh</th>
                                <th>Catatan</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $key => $d)
                            <tr>
                                <td>{{ ($data->currentpage() - 1) * $data->perpage() + $key + 1 }}.</td>
                                <td>{{ $d->User->name ?? '-' }}</td>
                                <td>
                                    {{ $d->Shift->nama_shift ?? '-' }}<br>
                                    <small class="text-muted">{{ $d->Shift->jam_masuk ?? '' }} - {{ $d->Shift->jam_keluar ?? '' }}</small>
                                </td>
                                <td>{{ $d->tanggal_mulai }}</td>
                                <td>{{ $d->tanggal_akhir }}</td>
                                <td>{{ $d->lokasi_tujuan ?? '-' }}</td>
                                <td>{{ $d->alasan }}</td>
                                <td>
                                    @if($d->foto_bukti)
                                        <a href="{{ url('/storage/'.$d->foto_bukti) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-image"></i> Lihat
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($d->status == 'Approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif($d->status == 'Ditolak')
                                        <span class="badge badge-danger">Ditolak</span>
                                    @else
                                        <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $d->approvedBy->name ?? '-' }}</td>
                                <td>{{ $d->catatan ?? '-' }}</td>
                                <td>
                                    @if($d->status == 'Pending')
                                        <!-- Tombol Approve/Tolak -->
                                        <button type="button" class="btn btn-sm btn-success me-1"
                                            data-bs-toggle="modal" data-bs-target="#modalApproval{{ $d->id }}">
                                            <i class="fas fa-check"></i> Proses
                                        </button>
                                    @else
                                        <span class="text-muted small">Sudah diproses</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- Modal Approval --}}
                            <div class="modal fade" id="modalApproval{{ $d->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="{{ url('/data-pengajuan-dinas/approval/'.$d->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Proses Pengajuan Dinas Luar</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Karyawan:</strong> {{ $d->User->name ?? '-' }}</p>
                                                <p><strong>Shift:</strong> {{ $d->Shift->nama_shift ?? '-' }} ({{ $d->Shift->jam_masuk ?? '' }} - {{ $d->Shift->jam_keluar ?? '' }})</p>
                                                <p><strong>Tanggal:</strong> {{ $d->tanggal_mulai }} s/d {{ $d->tanggal_akhir }}</p>
                                                <p><strong>Tujuan:</strong> {{ $d->lokasi_tujuan ?? '-' }}</p>
                                                <p><strong>Alasan:</strong> {{ $d->alasan }}</p>
                                                @if($d->foto_bukti)
                                                    <p><strong>Foto Bukti:</strong></p>
                                                    <div class="mb-2">
                                                        <img src="{{ url('/storage/'.$d->foto_bukti) }}" alt="Foto Bukti" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                                                    </div>
                                                @endif
                                                @if($d->lat_pengajuan && $d->long_pengajuan)
                                                    <p><strong>Lokasi Pengajuan (GPS):</strong>
                                                        <a href="https://www.google.com/maps?q={{ $d->lat_pengajuan }},{{ $d->long_pengajuan }}" target="_blank" class="text-primary">
                                                            <i class="fas fa-map-marker-alt"></i> Lihat di Maps
                                                        </a>
                                                    </p>
                                                @endif
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Keputusan <span class="text-danger">*</span></label>
                                                    <div class="d-flex gap-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="status" value="Approved" id="approve{{ $d->id }}" checked required>
                                                            <label class="form-check-label text-success fw-bold" for="approve{{ $d->id }}">✔ Approved</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="status" value="Ditolak" id="tolak{{ $d->id }}" required>
                                                            <label class="form-check-label text-danger fw-bold" for="tolak{{ $d->id }}">✖ Tolak</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Catatan (opsional)</label>
                                                    <textarea name="catatan" rows="2" class="form-control" placeholder="Tuliskan catatan jika perlu..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Kirim Keputusan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mr-4">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
