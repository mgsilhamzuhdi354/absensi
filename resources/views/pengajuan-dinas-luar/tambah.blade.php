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
                    <a href="{{ url('/pengajuan-dinas-luar') }}" class="btn btn-danger btn-sm ms-2">Back</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card">
            <form method="post" action="{{ url('/pengajuan-dinas-luar/store') }}" class="p-4" enctype="multipart/form-data">
                @csrf
                <div class="form-row mb-3">
                    <div class="col mb-3">
                        <label for="shift_id">Pilih Shift <span class="text-danger">*</span></label>
                        <select name="shift_id" id="shift_id" class="form-control selectpicker @error('shift_id') is-invalid @enderror" data-live-search="true" required>
                            <option value="">-- Pilih Shift --</option>
                            @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->nama_shift }} ({{ $s->jam_masuk }} - {{ $s->jam_keluar }})
                                </option>
                            @endforeach
                        </select>
                        @error('shift_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col mb-3">
                        <label for="lokasi_tujuan">Lokasi Tujuan</label>
                        <input type="text" class="form-control @error('lokasi_tujuan') is-invalid @enderror" name="lokasi_tujuan" id="lokasi_tujuan" value="{{ old('lokasi_tujuan') }}" placeholder="Contoh: Kantor Pusat Jakarta">
                        @error('lokasi_tujuan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-row mb-3">
                    <div class="col mb-3">
                        <label for="tanggal_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal_mulai') is-invalid @enderror" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required>
                        @error('tanggal_mulai')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col mb-3">
                        <label for="tanggal_akhir">Tanggal Akhir <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal_akhir') is-invalid @enderror" name="tanggal_akhir" id="tanggal_akhir" value="{{ old('tanggal_akhir') }}" required>
                        @error('tanggal_akhir')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-row mb-3">
                    <div class="col">
                        <label for="alasan">Alasan / Keperluan Dinas <span class="text-danger">*</span></label>
                        <textarea name="alasan" id="alasan" rows="3" class="form-control @error('alasan') is-invalid @enderror" placeholder="Jelaskan keperluan dinas luar..." required>{{ old('alasan') }}</textarea>
                        @error('alasan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-row mb-3">
                    <div class="col">
                        <label for="foto_bukti">Foto Bukti (Surat Tugas / Bukti Dinas)</label>
                        <input type="file" class="form-control @error('foto_bukti') is-invalid @enderror" name="foto_bukti" id="foto_bukti" accept="image/*">
                        @error('foto_bukti')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Format: JPG, PNG. Maks: 5MB</small>
                    </div>
                </div>
                <input type="hidden" name="lat_pengajuan" id="lat_pengajuan">
                <input type="hidden" name="long_pengajuan" id="long_pengajuan">
                <div class="alert alert-info mt-2">
                    <i class="fas fa-info-circle me-2"></i>
                    Setelah diajukan, admin akan meninjau dan menyetujui pengajuan Anda. Anda akan mendapatkan notifikasi hasilnya.
                </div>
                <button type="submit" class="btn btn-primary mt-2"><i class="fas fa-paper-plane me-1"></i> Ajukan</button>
            </form>
        </div>
    </div>
</div>
@push('script')
<script>
    $(document).ready(function() {
        $('#tanggal_mulai').change(function() {
            $('#tanggal_akhir').val($(this).val());
            $('#tanggal_akhir').attr('min', $(this).val());
        });

        // Auto-detect GPS
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                $('#lat_pengajuan').val(pos.coords.latitude);
                $('#long_pengajuan').val(pos.coords.longitude);
            });
        }
    });
</script>
@endpush
@endsection
