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
                <form method="post" class="p-4" action="{{ url('/data-absen/'.$data_absen->id.'/proses-edit-masuk') }}" enctype="multipart/form-data">
                    @method('put')
                    @csrf
                    <div class="form-group">
                        <label for="jam_absen">Jam Absen</label>
                        <input type="text" class="form-control clockpicker @error('jam_absen') is-invalid @enderror" id="jam_absen" name="jam_absen" value="{{ old('jam_absen', $data_absen->jam_absen) }}">
                        @error('jam_absen')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Dropdown Status Absen: Admin bisa set Izin Telat --}}
                    <div class="form-group">
                        <label for="status_absen">Status Absen <span class="text-muted small">(Admin)</span></label>
                        <select name="status_absen" id="status_absen" class="form-control">
                            <option value="Masuk" {{ old('status_absen', $data_absen->status_absen) == 'Masuk' ? 'selected' : '' }}>Masuk</option>
                            <option value="Izin Telat" {{ old('status_absen', $data_absen->status_absen) == 'Izin Telat' ? 'selected' : '' }}>Izin Telat (Telat akan di-nol-kan otomatis)</option>
                            <option value="Tidak Masuk" {{ old('status_absen', $data_absen->status_absen) == 'Tidak Masuk' ? 'selected' : '' }}>Tidak Masuk</option>
                        </select>
                        <div id="info-izin-telat" class="alert alert-info mt-2" style="display:none;">
                            <i class="fas fa-info-circle me-1"></i>
                            Status <strong>Izin Telat</strong>: jam absen akan diset ke jam shift masuk, dan durasi telat otomatis di-nol-kan.
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="foto_jam_absen">Foto Absen Masuk (opsional)</label>
                        <input type="file" class="form-control @error('foto_jam_absen') is-invalid @enderror" name="foto_jam_absen" id="foto_jam_absen">
                        @error('foto_jam_absen')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <input type="hidden" name="foto_jam_absen_lama" value="{{ $data_absen->foto_jam_absen }}">
                    </div>
                    <input type="hidden" name="lat_absen" value="{{ $lokasi_kantor ? $lokasi_kantor->lat_kantor : 0 }}">
                    <input type="hidden" name="long_absen" value="{{ $lokasi_kantor ? $lokasi_kantor->long_kantor : 0 }}">
                    <input type="hidden" name="telat">
                    <input type="hidden" name="jarak_masuk" value="0">
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

                // Tampilkan info saat Izin Telat dipilih
                $('#status_absen').on('change', function() {
                    if ($(this).val() === 'Izin Telat') {
                        $('#info-izin-telat').show();
                        $('#jam_absen').attr('readonly', true).css('background','#f0f8ff');
                    } else {
                        $('#info-izin-telat').hide();
                        $('#jam_absen').removeAttr('readonly').css('background','');
                    }
                }).trigger('change');
            });
        </script>
    @endpush
@endsection
