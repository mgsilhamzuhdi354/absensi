@extends('templates.app')
@section('container')
    <div class="tf-spacing-20"></div>
    <div class="tf-spacing-20"></div>
    <div class="tf-spacing-20"></div>
    <div class="tf-spacing-20"></div>
    <div class="tf-spacing-20"></div>
    <div class="tf-spacing-20"></div>
    <div class="tf-spacing-20"></div>
    <div class="card-secton transfer-section">
        <div class="tf-container">
            <div class="tf-balance-box">
                <form class="tf-form p-4" method="post" action="{{ url('/cuti/tambah') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="group-input">
                        <label for="user_id" style="z-index:1000">Nama Pegawai</label>
                        <select id="user_id" name="user_id" id="">
                            <option value="{{ $data_user->id }}">{{ $data_user->name }}</option>
                        </select>
                    </div>
                    <div class="group-input">
                        @php
                            $izin_cuti = $data_user->izin_cuti;
                            $izin_lainnya = $data_user->izin_lainnya;
                            $izin_telat = $data_user->izin_telat;
                            $izin_pulang_cepat = $data_user->izin_pulang_cepat;

                            $data_cuti = array(
                                [
                                    'nama' => 'Cuti',
                                    'nama_cuti' => 'Cuti ('.$izin_cuti.')'
                                ],
                                [
                                    'nama' => 'Izin Masuk',
                                    'nama_cuti' => 'Izin Masuk ('.$izin_lainnya.')'
                                ],
                                [
                                    'nama' => 'Izin Telat',
                                    'nama_cuti' => 'Izin Telat ('.$izin_telat.')'
                                ],
                                [
                                    'nama' => 'Izin Pulang Cepat',
                                    'nama_cuti' => 'Izin Pulang Cepat ('.$izin_pulang_cepat.')'
                                ],
                                [
                                    'nama' => 'Sakit',
                                    'nama_cuti' => 'Sakit'
                                ],
                            );
                        @endphp
                        <label for="nama_cuti" style="z-index:1000">Jenis Cuti / Izin</label>
                        <select class="@error('nama_cuti') is-invalid @enderror" id="nama_cuti" name="nama_cuti" data-live-search="true">
                            <option value="">Pilih Cuti</option>
                            @foreach ($data_cuti as $dc)
                            @if(old('nama_cuti') == $dc["nama"])
                            <option value="{{ $dc["nama"] }}" selected>{{ $dc["nama_cuti"] }}</option>
                            @else
                            <option value="{{ $dc["nama"] }}">{{ $dc["nama_cuti"] }}</option>
                            @endif
                            @endforeach
                        </select>
                        @error('nama_cuti')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <div class="group-input">
                        <label for="tanggal_mulai">Tanggal Mulai</label>
                        <input type="date" class="@error('tanggal_mulai') is-invalid @enderror" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai') }}">
                        @error('tanggal_mulai')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="group-input">
                        <label for="tanggal_akhir">Tanggal Akhir</label>
                        <input type="date" class="@error('tanggal_akhir') is-invalid @enderror" name="tanggal_akhir" id="tanggal_akhir" value="{{ old('tanggal_akhir') }}">
                        @error('tanggal_akhir')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <input type="hidden" name="tanggal">

                    <div class="group-input">
                        <input type="file" name="foto_cuti" id="foto_cuti" class="form-control @error('foto_cuti') is-invalid @enderror">
                        @error('foto_cuti')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="group-input">
                        <label for="alasan_cuti">Alasan Cuti</label>
                        <input type="text" class="form-control @error('alasan_cuti') is-invalid @enderror" id="alasan_cuti" name="alasan_cuti" value="{{ old('alasan_cuti') }}">
                        @error('alasan_cuti')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <input type="hidden" name="status_cuti">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
    <div class="tf-spacing-20"></div>
    <div class="tf-spacing-20"></div>
    <div class="transfer-content">
        <div class="tf-container">
            <form action="{{ url('/cuti') }}">
                <div class="row">
                    <div class="col-4">
                        <input type="date" name="mulai" placeholder="Tanggal Mulai" id="mulai" value="{{ request('mulai') }}">
                    </div>
                    <div class="col-4">
                        <input type="date" name="akhir" placeholder="Tanggal Akhir" id="akhir" value="{{ request('akhir') }}">
                    </div>
                    <div class="col-4">
                        <button type="submit" id="search" class="form-control btn" style="width: 25px"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="tf-spacing-20"></div>
    <div class="transfer-content">
        <div class="tf-container">
            <table class="table table-striped" id="tablePayroll">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Pegawai</th>
                        <th>Lokasi Pegawai</th>
                        <th>Nama Cuti</th>
                        <th>Tanggal</th>
                        <th>Alasan Cuti</th>
                        <th>Foto Cuti</th>
                        <th>Status Cuti</th>
                        <th>User Approval</th>
                        <th>Catatan</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                   @foreach ($data_cuti_user as $key => $dcu)
                   <tr>
                        <td>{{ ($data_cuti_user->currentpage() - 1) * $data_cuti_user->perpage() + $key + 1 }}.</td>
                       <td>{{ $dcu->User->name ?? '-' }}</td>
                       <td>{{ $dcu->lokasi->nama_lokasi ?? '-' }}</td>
                       <td>{{ $dcu->nama_cuti ?? '-' }}</td>
                       <td>{{ $dcu->tanggal ?? '-' }}</td>
                       <td>{{ $dcu->alasan_cuti ?? '-' }}</td>
                       <td>
                            @if($dcu->foto_cuti)
                                <img src="{{ url('storage/'.$dcu->foto_cuti) }}"
                                     style="width:70px; height:70px; object-fit:cover; cursor:pointer; border-radius:6px; border:2px solid #ddd;"
                                     class="foto-lightbox"
                                     data-url="{{ url('storage/'.$dcu->foto_cuti) }}"
                                     alt="Foto Cuti"
                                     title="Klik untuk perbesar">
                            @else
                                <span class="text-muted">-</span>
                            @endif
                       </td>
                       <td>
                            @if($dcu->status_cuti == "Diterima")
                                {{ $dcu->status_cuti ?? '-' }}
                            @elseif($dcu->status_cuti == "Ditolak")
                                {{ $dcu->status_cuti ?? '-' }}
                            @else
                                {{ $dcu->status_cuti ?? '-' }}
                            @endif
                       </td>
                       <td>{{ $dcu->ua->name ?? '-' }}</td>
                       <td>{{ $dcu->catatan ?? '-' }}</td>
                       <td>
                            <div style="display: flex; gap: 5px; flex-wrap:wrap;">
                                {{-- Tombol PDF --}}
                                <a href="{{ url('/cuti/pdf/'.$dcu->id) }}" target="_blank"
                                   class="btn btn-sm btn-info btn-circle" title="Download PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                @if($dcu->status_cuti == "Diterima")
                                    Sudah Approve
                                @else
                                    <a href="{{ url('/cuti/edit/'.$dcu->id) }}" class="btn btn-sm btn-warning btn-circle"><i class="fa fa-solid fa-edit"></i></a>
                                @endif

                                @if($dcu->status_cuti == "Diterima")
                                    Sudah Approve
                                @else
                                    <form action="{{ url('/cuti/delete/'.$dcu->id) }}" method="post" class="d-inline">
                                        @method('delete')
                                        @csrf
                                        <button class="btn btn-sm btn-danger btn-circle" style="width: 30px" onClick="return confirm('Are You Sure')"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                       </td>
                   </tr>
                   @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mr-4">
            {{ $data_cuti_user->links() }}
        </div>
    </div>

    {{-- LIGHTBOX MODAL --}}
    <div id="lightbox-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:99999; justify-content:center; align-items:center; flex-direction:column;">
        <button onclick="closeLightbox()" style="position:absolute; top:18px; right:28px; background:none; border:none; color:#fff; font-size:2rem; cursor:pointer; z-index:100000;">&times;</button>
        <img id="lightbox-img" src="" style="max-width:90vw; max-height:85vh; border-radius:12px; box-shadow:0 8px 40px rgba(0,0,0,0.7);" alt="Foto Cuti">
        <a id="lightbox-download" href="" download target="_blank"
           style="margin-top:14px; color:#fff; background:#0d6efd; padding:8px 24px; border-radius:8px; text-decoration:none; font-size:0.95rem;">
            <i class="fas fa-download me-1"></i> Download Foto
        </a>
    </div>

    <br><br><br><br>
    @push('script')
        <script>
            $('select').select2();

            // Lightbox
            document.querySelectorAll('.foto-lightbox').forEach(function(img) {
                img.addEventListener('click', function() {
                    var url = this.getAttribute('data-url');
                    document.getElementById('lightbox-img').src = url;
                    document.getElementById('lightbox-download').href = url;
                    var modal = document.getElementById('lightbox-modal');
                    modal.style.display = 'flex';
                });
            });

            function closeLightbox() {
                document.getElementById('lightbox-modal').style.display = 'none';
                document.getElementById('lightbox-img').src = '';
            }

            // Klik luar gambar untuk tutup
            document.getElementById('lightbox-modal').addEventListener('click', function(e) {
                if (e.target === this) closeLightbox();
            });

            // ESC untuk tutup
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeLightbox();
            });
        </script>
    @endpush
@endsection
