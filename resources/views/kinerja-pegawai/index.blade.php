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
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <form action="{{ url('/kinerja-pegawai') }}">
                        <div class="row mb-2">
                            <div class="col-6">
                                <input type="text" class="form-control" name="search" placeholder="Search..." id="search" value="{{ request('search') }}">
                            </div>
                            <div class="col-2">
                                <button type="submit" id="search"class="border-0 mt-3" style="background-color: transparent;"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="mytable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Nama Pegawai</th>
                                    <th>Kinerja</th>
                                    <th>Detail</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $key => $user)
                                    @php
                                        $lastLk = $user->lastlk($user->id);
                                        $hasData = $lastLk ? true : false;
                                        $kinerja = $hasData ? ($lastLk->penilaian_berjalan ?? 0) : null;
                                    @endphp
                                    <tr>
                                        <td>{{ ($users->currentpage() - 1) * $users->perpage() + $key + 1 }}.</td>
                                        <td>{{ $user->name ?? '-' }}</td>
                                        <td>{{ $kinerja ?? 0 }}</td>
                                        <td>
                                            @if(!$hasData)
                                                <span class="badge badge-secondary">Belum Ada Data</span>
                                            @elseif($kinerja < 0)
                                                <span class="badge badge-danger">Kinerja Buruk</span>
                                            @elseif($kinerja <= 50)
                                                <span class="badge badge-warning">Kinerja Cukup</span>
                                            @elseif($kinerja <= 100)
                                                <span class="badge badge-info">Kinerja Lumayan</span>
                                            @else
                                                <span class="badge badge-success">Kinerja Baik</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ url('/kinerja-pegawai/history/'.$user->id) }}" class="btn btn-info btn-sm" title="Lihat Riwayat">
                                                    <i class="fa fa-history"></i>
                                                </a>
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $user->id }}" title="Tambah Poin">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>

    <!-- Modals moved outside the table -->
    @foreach ($users as $user)
        <div class="modal fade" id="adjustModal{{ $user->id }}" tabindex="-1" role="dialog" aria-labelledby="adjustModalLabel{{ $user->id }}" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="{{ url('/kinerja-pegawai/manual-store') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="adjustModalLabel{{ $user->id }}">Adjust Poin: {{ $user->name }}</h5>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <div class="mb-3">
                                <label for="tanggal">Tanggal</label>
                                <input type="date" class="form-control" name="tanggal" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="jenis_kinerja_id">Jenis Kinerja</label>
                                <select class="form-select" name="jenis_kinerja_id" required>
                                    @foreach($jenis_kinerja as $jk)
                                        <option value="{{ $jk->id }}" data-bobot="{{ $jk->bobot }}">{{ $jk->nama }} (Bobot Default: {{ $jk->bobot }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="nilai">Nilai Poin (+/-)</label>
                                <input type="number" class="form-control" name="nilai" placeholder="Contoh: -5 or 10" required>
                                <small class="text-muted">Masukkan angka positif untuk menambah, negatif untuk mengurangi.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                            <button class="btn btn-primary" type="submit">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
