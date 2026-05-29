@extends('templates.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Input Dinas Luar Manual</h5>
                    <a href="{{ url('/data-pengajuan-dinas') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ url('/pengajuan-dinas-luar/manual') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Karyawan <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">-- Pilih Karyawan --</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
                                            {{ $u->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Shift <span class="text-danger">*</span></label>
                                <select name="shift_id" class="form-select" required>
                                    <option value="">-- Pilih Shift --</option>
                                    @foreach($shifts as $s)
                                        <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                            {{ $s->nama_shift }} ({{ $s->jam_masuk }} - {{ $s->jam_keluar }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_mulai" class="form-control"
                                    value="{{ old('tanggal_mulai', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tanggal Akhir <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_akhir" class="form-control"
                                    value="{{ old('tanggal_akhir', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Lokasi / Daerah Tujuan <span class="text-danger">*</span></label>
                                <input type="text" name="lokasi" class="form-control"
                                    placeholder="Contoh: Palembang, Sumatera Selatan"
                                    value="{{ old('lokasi') }}" required>
                                <small class="text-muted">Masukkan nama kota/daerah tujuan dinas luar</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Alasan / Keperluan</label>
                                <textarea name="alasan" class="form-control" rows="3"
                                    placeholder="Jelaskan keperluan dinas luar...">{{ old('alasan') }}</textarea>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-1"></i>
                            Data dinas luar manual akan langsung dibuat dengan status <strong>Approved</strong>
                            untuk setiap hari dalam rentang tanggal yang dipilih.
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i> Simpan Dinas Luar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
