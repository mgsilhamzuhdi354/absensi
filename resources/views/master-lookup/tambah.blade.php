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
                        <a href="{{ url('/master-data') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Kembali</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ url('/master-data/store') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Modul <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-control selectpicker @error('type') is-invalid @enderror" data-live-search="true" required>
                                <option value="">-- Pilih Modul --</option>
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: PHK, Pertemuan Online, Office">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="value" class="form-label">Value (opsional)</label>
                            <input type="text" class="form-control @error('value') is-invalid @enderror" id="value" name="value" value="{{ old('value') }}" placeholder="Kosongkan jika sama dengan nama">
                            <small class="text-muted">Value yang akan disimpan ke database. Jika kosong, akan menggunakan Nama.</small>
                            @error('value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Urutan</label>
                            <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                            <small class="text-muted">Urutan tampil di dropdown (angka kecil = tampil lebih dulu)</small>
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="active" name="active" {{ old('active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">Aktif</label>
                            </div>
                            <small class="text-muted">Data yang tidak aktif tidak akan muncul di dropdown</small>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                            <a href="{{ url('/master-data') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fa fa-info-circle"></i> Panduan</h5>
                </div>
                <div class="card-body">
                    <h6>Jenis Modul:</h6>
                    <ul class="small">
                        <li><strong>Jenis Keberhentian:</strong> PHK, Mengundurkan Diri, Meninggal Dunia, Pensiun</li>
                        <li><strong>Jenis Pertemuan:</strong> Pertemuan Offline, Pertemuan Online</li>
                        <li><strong>Keterangan Lokasi:</strong> Office, Patroli</li>
                    </ul>
                    <hr>
                    <p class="small text-muted mb-0">
                        <i class="fa fa-lightbulb"></i> Anda dapat menambahkan opsi baru atau menonaktifkan opsi yang tidak digunakan.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
