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
                    <a class="btn btn-primary btn-sm" href="{{ url('/pengajuan-dinas-luar/tambah') }}">+ Ajukan Dinas Luar</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form action="{{ url('/pengajuan-dinas-luar') }}">
                    <div class="row">
                        <div class="col-3">
                            <input type="date" class="form-control" name="mulai" placeholder="Tanggal Mulai" value="{{ request('mulai') }}">
                        </div>
                        <div class="col-3">
                            <input type="date" class="form-control" name="akhir" placeholder="Tanggal Akhir" value="{{ request('akhir') }}">
                        </div>
                        <div class="col-3">
                            <button type="submit" class="border-0 mt-3" style="background-color: transparent;"><i class="fas fa-search"></i></button>
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
                                <th>Shift</th>
                                <th>Tgl Mulai</th>
                                <th>Tgl Akhir</th>
                                <th>Lokasi Tujuan</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Catatan Admin</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $key => $d)
                            <tr>
                                <td>{{ ($data->currentpage() - 1) * $data->perpage() + $key + 1 }}.</td>
                                <td>{{ $d->Shift->nama_shift ?? '-' }} <br><small class="text-muted">{{ $d->Shift->jam_masuk ?? '' }} - {{ $d->Shift->jam_keluar ?? '' }}</small></td>
                                <td>{{ $d->tanggal_mulai }}</td>
                                <td>{{ $d->tanggal_akhir }}</td>
                                <td>{{ $d->lokasi_tujuan ?? '-' }}</td>
                                <td>{{ $d->alasan }}</td>
                                <td>
                                    @if($d->status == 'Approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif($d->status == 'Ditolak')
                                        <span class="badge badge-danger">Ditolak</span>
                                    @else
                                        <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $d->catatan ?? '-' }}</td>
                                <td>
                                    @if($d->status == 'Pending')
                                        <form action="{{ url('/pengajuan-dinas-luar/delete/'.$d->id) }}" method="post" class="d-inline" onsubmit="return confirm('Hapus pengajuan ini?')">
                                            @method('delete')
                                            @csrf
                                            <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @else
                                        <span class="badge badge-info">{{ $d->status }}</span>
                                    @endif
                                </td>
                            </tr>
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
