@extends('templates.dashboard')
@section('isi')
    <div class="row">
        <div class="col-md-12 project-list">
            <div class="card">
                <div class="row">
                    <div class="col-md-6 mt-2 p-0 d-flex">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0 text-end">
                        <a href="{{ url('/kinerja-pegawai') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Total Skor:</strong> 
                            @php
                                $lastPoint = $laporan->first();
                                $totalSkor = $lastPoint ? $lastPoint->penilaian_berjalan : 0;
                            @endphp
                            <span class="badge {{ $totalSkor >= 0 ? 'bg-success' : 'bg-danger' }}">{{ $totalSkor }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Tanggal</th>
                                    <th>Jenis Kinerja</th>
                                    <th>Nilai</th>
                                    <th>Skor Berjalan</th>
                                    <th>Reference</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($laporan as $key => $lp)
                                    <tr>
                                        <td>{{ ($laporan->currentpage() - 1) * $laporan->perpage() + $key + 1 }}.</td>
                                        <td>{{ $lp->tanggal }}</td>
                                        <td>{{ $lp->jenis->nama ?? '-' }}</td>
                                        <td>
                                            <span class="badge {{ $lp->nilai >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $lp->nilai >= 0 ? '+' : '' }}{{ $lp->nilai }}
                                            </span>
                                        </td>
                                        <td>{{ $lp->penilaian_berjalan }}</td>
                                        <td>
                                            @if($lp->reference == 'Manual Adjustment')
                                                <span class="badge bg-warning">Manual</span>
                                            @elseif($lp->reference == 'App\Models\MappingShift')
                                                <span class="badge bg-info">Absensi</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $lp->reference ?? '-' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ url('/kinerja-pegawai/edit/'.$lp->id) }}" class="btn btn-primary btn-sm">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ url('/kinerja-pegawai/delete/'.$lp->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus poin ini? Semua skor berjalan akan dihitung ulang.');">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Belum ada data penilaian</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $laporan->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
