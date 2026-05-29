@extends('templates.dashboard')
@section('isi')
    <div class="row">
        <div class="col-md-12 m project-list">
            <div class="card">
                <div class="row">
                    <div class="col-md-6 p-0 d-flex mt-2">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0">                    
                        <a href="{{ url('/data-absen') }}" class="btn btn-danger btn-sm ms-2">Back</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <form method="post" class="p-4" action="{{ url('/data-absen/'.$data_absen->id.'/proses-edit-pulang') }}" enctype="multipart/form-data">
                    @method('put')
                    @csrf
                    <div class="form-group">
                        <label for="jam_pulang">Jam Pulang</label>
                        <input type="text" class="form-control clockpicker @error('jam_pulang') is-invalid @enderror" name="jam_pulang" id="jam_pulang" value="{{ old('jam_pulang', $data_absen->jam_pulang) }}">
                        @error('jam_pulang')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Dropdown Status Absen saat Pulang: Admin bisa set Izin Pulang Cepat --}}
                    <div class="form-group">
                        <label for="status_pulang">Status Pulang <span class="text-muted small">(Admin)</span></label>
                        <select name="status_absen" id="status_pulang" class="form-control">
                            <option value="Masuk" {{ old('status_absen', $data_absen->status_absen) == 'Masuk' ? 'selected' : '' }}>Masuk (Normal)</option>
                            <option value="Izin Pulang Cepat" {{ old('status_absen', $data_absen->status_absen) == 'Izin Pulang Cepat' ? 'selected' : '' }}>Izin Pulang Cepat (Pulang cepat otomatis di-nol-kan)</option>
                        </select>
                        <div id="info-izin-pulang" class="alert alert-info mt-2" style="display:none;">
                            <i class="fas fa-info-circle me-1"></i>
                            Status <strong>Izin Pulang Cepat</strong>: jam pulang akan diset ke jam shift keluar, dan durasi pulang cepat otomatis di-nol-kan.
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="foto_jam_pulang">Foto Absen Pulang (opsional)</label>
                        <input type="file" class="form-control @error('foto_jam_pulang') is-invalid @enderror" id="foto_jam_pulang" name="foto_jam_pulang">
                        @error('foto_jam_pulang')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <input type="hidden" name="foto_jam_pulang_lama" value="{{ $data_absen->foto_jam_pulang }}">
                    </div>
                    <input type="hidden" name="lat_pulang" value="{{ $lokasi_kantor ? $lokasi_kantor->lat_kantor : 0 }}">
                    <input type="hidden" name="long_pulang" value="{{ $lokasi_kantor ? $lokasi_kantor->long_kantor : 0 }}">
                    <input type="hidden" name="pulang_cepat">
                    <input type="hidden" name="jarak_pulang" value="0">
                    <button type="submit" class="btn btn-primary">Submit</button>
                  </form>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            $(document).ready(function(){
                $('.clockpicker').clockpicker({ donetext: 'Done' });

                $('body').on('keyup', '.clockpicker', function (event) {
                    var val = $(this).val();
                    val = val.replace(/[^0-9:]/g, '');
                    val = val.replace(/:+/g, ':');
                    $(this).val(val);
                });

                // Tampilkan info saat Izin Pulang Cepat dipilih
                $('#status_pulang').on('change', function() {
                    if ($(this).val() === 'Izin Pulang Cepat') {
                        $('#info-izin-pulang').show();
                        $('#jam_pulang').attr('readonly', true).css('background','#f0f8ff');
                    } else {
                        $('#info-izin-pulang').hide();
                        $('#jam_pulang').removeAttr('readonly').css('background','');
                    }
                }).trigger('change');
            });
        </script>
    @endpush
@endsection
