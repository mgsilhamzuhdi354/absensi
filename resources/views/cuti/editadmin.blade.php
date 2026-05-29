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
                        <a href="{{ url('/data-cuti') }}" class="btn btn-danger btn-sm ms-2">Back</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <form method="post" action="{{ url('/data-cuti/edit-proses/'.$data_cuti_karyawan->id) }}" class="p-4">
                    @method('put')
                    @csrf
                    <div class="form-row">
                        <div class="col mb-4">
                            <label for="user_id">Nama Pegawai</label>
                            <input type="text" disabled class="form-control" value="{{ $data_cuti_karyawan->User->name }}" id="user_id">
                        </div>
                        <div class="col mb-4">
                            <label for="nama_cuti">Nama Cuti</label>
                            <input type="text" class="form-control" value="{{ $data_cuti_karyawan->nama_cuti }}" id="nama_cuti" disabled>
                            <input type="hidden" name="nama_cuti" value="{{ $data_cuti_karyawan->nama_cuti }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col mb-4">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror" name="tanggal" id="tanggal" value="{{ $data_cuti_karyawan->tanggal }}">
                            @error('tanggal')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <div class="col mb-4">
                            <label for="alasan_cuti">Alasan Cuti</label>
                            <input type="text" disabled class="form-control" value="{{ $data_cuti_karyawan->alasan_cuti }}">
                        </div>
                    </div>
                    <div class="form-row">
                        @php
                            $status_cuti = array(
                            [
                                "status_cuti" => "Pending"
                            ],
                            [
                                "status_cuti" => "Ditolak"
                            ],
                            [
                                "status_cuti" => "Diterima"
                            ]);
                        @endphp
                        <div class="col mb-4">
                            <label for="status_cuti">Status Cuti</label>
                            <select name="status_cuti" class="form-control @error('status_cuti') is-invalid @enderror selectpicker" data-live-search="true" id="status_cuti">
                                @foreach ($status_cuti as $sc)
                                    @if(old('status_cuti', $data_cuti_karyawan->status_cuti) == $sc["status_cuti"])
                                        <option value="{{ $sc["status_cuti"] }}" selected>{{ $sc["status_cuti"] }}</option>
                                    @else
                                        <option value="{{ $sc["status_cuti"] }}">{{ $sc["status_cuti"] }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('status_cuti')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <div class="col mb-4">
                            <label for="catatan">Catatan</label>
                            <input type="text" class="form-control @error('catatan') is-invalid @enderror" name="catatan" id="catatan" value="{{ old('catatan', $data_cuti_karyawan->catatan) }}">
                            @error('catatan')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    {{-- Section Sakit: muncul hanya jika nama_cuti = Sakit --}}
                    @if($data_cuti_karyawan->nama_cuti === 'Sakit')
                    <div class="form-row">
                        <div class="col-12 mb-3">
                            {{-- Tampilkan foto lampiran yang di-upload user --}}
                            @if($data_cuti_karyawan->foto_cuti)
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="fas fa-paperclip me-2"></i>
                                    <div>
                                        <strong>Lampiran dari Karyawan:</strong>
                                        <a href="{{ url('storage/'.$data_cuti_karyawan->foto_cuti) }}" target="_blank" class="ms-2">
                                            <i class="fas fa-image"></i> Lihat Foto Lampiran
                                        </a>
                                        <span class="badge badge-success ms-2">📎 Karyawan mengirim foto/surat</span>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Tidak ada lampiran foto dari karyawan.</strong>
                                </div>
                            @endif
                        </div>
                        <div class="col mb-4">
                            <label class="font-weight-bold">Jenis Sakit</label>
                            @php
                                $tipeLabels = [
                                    'surat_dokter'      => ['label' => '🟢 Sakit Dengan Surat Dokter', 'keterangan' => 'Tidak dipotong gaji'],
                                    'tanpa_surat_dokter' => ['label' => '🔴 Sakit Tanpa Surat Dokter', 'keterangan' => 'Dipotong gaji'],
                                    'keluarga_meninggal' => ['label' => '💙 Keluarga Meninggal',       'keterangan' => 'Tidak dipotong gaji'],
                                ];
                                $tipe = $data_cuti_karyawan->tipe_sakit;
                                // Auto-detect: jika ada foto dan belum ada tipe, suggest surat_dokter
                                if (!$tipe && $data_cuti_karyawan->foto_cuti) {
                                    $tipe = 'surat_dokter';
                                }
                            @endphp
                            <select name="tipe_sakit" id="tipe_sakit_admin" class="form-control">
                                <option value="">-- Pilih Jenis Sakit --</option>
                                @foreach($tipeLabels as $key => $val)
                                    <option value="{{ $key }}" {{ old('tipe_sakit', $tipe) == $key ? 'selected' : '' }}>
                                        {{ $val['label'] }} ({{ $val['keterangan'] }})
                                    </option>
                                @endforeach
                            </select>
                            @if(!$data_cuti_karyawan->tipe_sakit && $data_cuti_karyawan->foto_cuti)
                                <small class="text-info"><i class="fas fa-magic"></i> Otomatis dipilih "Surat Dokter" karena karyawan mengirim foto.</small>
                            @endif
                        </div>
                        <div class="col mb-4" id="field-potongan-gaji" style="{{ old('tipe_sakit', $tipe) === 'tanpa_surat_dokter' ? '' : 'display:none;' }}">
                            <label for="potongan_gaji" class="font-weight-bold text-danger">
                                Nominal Potongan Gaji (Rp) <span class="text-muted small">— diputuskan admin</span>
                            </label>
                            @php
                                $potongan_per_hari = ($gaji_pokok > 0) ? round($gaji_pokok / 22) : 0;
                                $existing_potongan = $data_cuti_karyawan->potongan_gaji ?? $potongan_per_hari;
                            @endphp
                            <input type="number" name="potongan_gaji" id="potongan_gaji" class="form-control"
                                value="{{ old('potongan_gaji', $existing_potongan) }}"
                                placeholder="Masukkan nominal potongan (Rp)" min="0" step="1000">
                            <small class="text-muted">
                                Gaji Pokok: <strong>Rp {{ number_format($gaji_pokok, 0, ',', '.') }}</strong>
                                &nbsp;|&nbsp; Potongan/hari (÷22): <strong>Rp {{ number_format($potongan_per_hari, 0, ',', '.') }}</strong>
                            </small>
                        </div>
                    </div>
                    @endif

                    <input type="hidden" name="jam_absen">
                    <input type="hidden" name="telat">
                    <input type="hidden" name="lat_absen">
                    <input type="hidden" name="long_absen">
                    <input type="hidden" name="jarak_masuk">
                    <input type="hidden" name="foto_jam_absen">
                    <input type="hidden" name="jam_pulang">
                    <input type="hidden" name="pulang_cepat">
                    <input type="hidden" name="foto_jam_pulang">
                    <input type="hidden" name="lat_pulang">
                    <input type="hidden" name="long_pulang">
                    <input type="hidden" name="jarak_pulang">
                    <input type="hidden" name="status_absen">
                    <input type="hidden" name="izin_cuti">
                    <input type="hidden" name="izin_lainnya">
                    <input type="hidden" name="izin_telat">
                    <input type="hidden" name="izin_pulang_cepat">
                    <button type="submit" class="btn btn-primary">Submit</button>
                  </form>
            </div>
        </div>
    </div>
    @push('script')
    <script>
        $(document).ready(function() {
            var gajiPokok = {{ $gaji_pokok ?? 0 }};
            var potonganPerHari = gajiPokok > 0 ? Math.round(gajiPokok / 22) : 0;

            function togglePotonganGaji() {
                if ($('#tipe_sakit_admin').val() === 'tanpa_surat_dokter') {
                    $('#field-potongan-gaji').show();
                    // Jika potongan masih 0, auto-fill dengan gaji/22
                    if (parseInt($('#potongan_gaji').val()) === 0 || !$('#potongan_gaji').val()) {
                        $('#potongan_gaji').val(potonganPerHari);
                    }
                } else {
                    $('#field-potongan-gaji').hide();
                    $('#potongan_gaji').val(0);
                }
            }
            $('#tipe_sakit_admin').on('change', togglePotonganGaji);
        });
    </script>
    @endpush
@endsection
