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
                <div class="card-body">
                    <form action="{{ url('/kinerja-pegawai/update/'.$laporan->id) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pegawai</label>
                            <input type="text" class="form-control" value="{{ $laporan->user->name ?? '-' }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="text" class="form-control" value="{{ $laporan->tanggal }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kinerja</label>
                            <select class="form-select" name="jenis_kinerja_id" required>
                                @foreach($jenis_kinerja as $jk)
                                    <option value="{{ $jk->id }}" {{ $laporan->jenis_kinerja_id == $jk->id ? 'selected' : '' }}>
                                        {{ $jk->nama }} (Bobot Default: {{ $jk->bobot }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai Poin</label>
                            <input type="number" class="form-control" name="nilai" value="{{ $laporan->nilai }}" required>
                            <small class="text-muted">Angka positif untuk menambah, negatif untuk mengurangi.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference</label>
                            <input type="text" class="form-control" value="{{ $laporan->reference ?? '-' }}" disabled>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="{{ url('/kinerja-pegawai') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
