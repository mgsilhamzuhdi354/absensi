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
                        <a href="{{ url('/data-absen/export') }}{{ $_GET ? '?' . $_SERVER['QUERY_STRING'] : '' }}"
                            class="btn btn-success">Export</a>
                        <a href="{{ url('/smart-import-absen') }}" class="btn btn-primary ms-1">
                            <i class="fas fa-robot me-1"></i>Smart Import
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="cold-md-12">
            <div class="card">
                <div class="card-header">
                    <form action="{{ url('/data-absen') }}">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <select name="user_id" id="user_id" class="form-control selectpicker"
                                    data-live-search="true">
                                    <option value="" selected>Pilih Pegawai</option>
                                    @foreach($user as $u)
                                        @if(request('user_id') == $u->id)
                                            <option value="{{ $u->id }}" selected>{{ $u->name }}</option>
                                        @else
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <input type="datetime" class="form-control" name="mulai" placeholder="Tanggal Mulai"
                                    id="mulai" value="{{ request('mulai') }}">
                            </div>
                            <div class="col-md-2 mb-2">
                                <input type="datetime" class="form-control" name="akhir" placeholder="Tanggal Akhir"
                                    id="akhir" value="{{ request('akhir') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <select name="pegawai_status" class="form-control">
                                    <option value="aktif" {{ ($pegawaiStatus ?? request('pegawai_status', 'aktif')) === 'aktif' ? 'selected' : '' }}>Absen Aktif</option>
                                    <option value="keluar" {{ ($pegawaiStatus ?? request('pegawai_status')) === 'keluar' ? 'selected' : '' }}>Absen PHK / Pegawai Keluar</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <button type="submit" id="search" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
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
                                    <th>Shift</th>
                                    <th>Tanggal</th>
                                    <th>Jam Masuk</th>
                                    <th>Telat</th>
                                    <th>Lokasi Masuk</th>
                                    <th>Foto Masuk</th>
                                    <th>Keterangan Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Pulang Cepat</th>
                                    <th>Lokasi Pulang</th>
                                    <th>Foto Pulang</th>
                                    <th>Keterangan Pulang</th>
                                    <th>Status Absen</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data_absen as $key => $da)
                                    <tr>
                                        @php
                                            $statusAbsen = trim((string) $da->status_absen);
                                            $isTidakMasuk = in_array($statusAbsen, ['Tidak Masuk', 'Alpha', 'Alfa'], true);
                                            $statusTanpaScanLabel = $isTidakMasuk ? 'Tidak Masuk' : null;
                                            $statusTanpaScanBadge = $isTidakMasuk ? 'badge-danger' : 'badge-success';
                                        @endphp
                                        <td>{{ ($data_absen->currentpage() - 1) * $data_absen->perpage() + $key + 1 }}.</td>
                                        <td>{{ $da->name }}</td>
                                        <td>
                                            @if ($da->Shift)
                                                {{ $da->Shift->nama_shift }} ({{ $da->Shift->jam_masuk }} -
                                                {{ $da->Shift->jam_keluar }})
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $da->tanggal ?? '-' }}</td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @else
                                                {{ $da->jam_absen }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($da->status_absen == 'Izin Telat')
                                                <span class="badge badge-warning">Izin Telat</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @else
                                                                            <?php
                                                $telat = $da->telat;
                                                $jam = floor($telat / (60 * 60));
                                                $menit = $telat - ($jam * (60 * 60));
                                                $menit2 = floor($menit / 60);
                                                $detik = $telat % 60;
                                                                            ?>
                                                                            @if($jam <= 0 && $menit2 <= 0)
                                                                                <span class="badge badge-success">Tepat Waktu</span>
                                                                            @else
                                                                                <span class="badge badge-danger">{{ $jam . " Jam " . $menit2 . " Menit" }}</span>
                                                                            @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @else
                                                @php
                                                    $jarak_masuk = explode(".", $da->jarak_masuk);
                                                @endphp
                                                <a href="{{ url('/maps/' . $da->lat_absen . '/' . $da->long_absen . '/' . $da->User->id) }}"
                                                    style="background-color: rgb(146, 146, 146)" class="btn btn-xs"
                                                    target="_blank"><i class="fa fa-eye" class="me-2"></i> Lihat</a>
                                                <span class="badge badge-warning">{{ $jarak_masuk[0] }} Meter</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @else
                                        @if ($da->foto_jam_absen)
                                            <img src="{{ url('storage/' . $da->foto_jam_absen) }}" 
                                                 class="photo-thumbnail" 
                                                 style="width: 60px;border-radius:60px" 
                                                 onclick="openLightbox('{{ url('storage/' . $da->foto_jam_absen) }}', 'Foto Absen Masuk - {{ $da->name }} ({{ $da->tanggal }})')" 
                                                 alt="Foto Masuk">
                                        @else
                                            -
                                        @endif
                                    @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @else
                                                {{ $da->keterangan_masuk }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @elseif($da->jam_pulang == null)
                                                <span class="badge badge-warning">Belum Pulang</span>
                                            @else
                                                {{ $da->jam_pulang }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($da->status_absen == 'Izin Pulang Cepat')
                                                <span class="badge badge-warning">Izin Pulang Cepat</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @elseif($da->jam_pulang == null)
                                                <span class="badge badge-warning">Belum Pulang</span>
                                            @else
                                                                            <?php
                                                $pulang_cepat = $da->pulang_cepat;

                                                $jam = floor($pulang_cepat / (60 * 60));
                                                $menit = $pulang_cepat - ($jam * (60 * 60));
                                                $menit2 = floor($menit / 60);
                                                $detik = $pulang_cepat % 60;
                                                                                ?>
                                                                            @if($jam <= 0 && $menit2 <= 0)
                                                                                <span class="badge badge-success">Tidak Pulang Cepat</span>
                                                                            @else
                                                                                <span class="badge badge-danger">{{ $jam . " Jam " . $menit2 . " Menit" }}</span>
                                                                            @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @elseif($da->jam_pulang == null)
                                                <span class="badge badge-warning">Belum Pulang</span>
                                            @else
                                                @php
                                                    $jarak_pulang = explode(".", $da->jarak_pulang);
                                                @endphp
                                                <a href="{{ url('/maps/' . $da->lat_pulang . '/' . $da->long_pulang . '/' . $da->User->id) }}"
                                                    style="background-color: rgb(146, 146, 146)" class="btn btn-xs"
                                                    target="_blank"><i class="fa fa-eye" class="me-2"></i> Lihat</a>
                                                <span class="badge badge-warning">{{ $jarak_pulang[0] }} Meter</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @elseif($da->jam_pulang == null)
                                                <span class="badge badge-warning">Belum Pulang</span>
                                            @else
                                        @if ($da->foto_jam_pulang)
                                            <img src="{{ url('storage/' . $da->foto_jam_pulang) }}" 
                                                 class="photo-thumbnail" 
                                                 style="width: 60px;border-radius:60px" 
                                                 onclick="openLightbox('{{ url('storage/' . $da->foto_jam_pulang) }}', 'Foto Absen Pulang - {{ $da->name }} ({{ $da->tanggal }})')" 
                                                 alt="Foto Pulang">
                                        @else
                                            -
                                        @endif
                                    @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">Libur</span>
                                            @elseif($da->status_absen == 'Cuti')
                                                <span class="badge badge-warning">Sedang Cuti</span>
                                            @elseif($da->status_absen == 'Izin Masuk')
                                                <span class="badge badge-warning">Sedang Izin masuk</span>
                                            @elseif($da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">Sedang Sakit</span>
                                            @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                <span class="badge badge-danger">Belum Absen</span>
                                            @elseif($da->jam_pulang == null)
                                                <span class="badge badge-warning">Belum Pulang</span>
                                            @else
                                                {{ $da->keterangan_pulang }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($da->status_absen == 'Libur')
                                                <span class="badge badge-info">{{ $da->status_absen }}</span>
                                            @elseif($da->status_absen == 'Cuti' || $da->status_absen == 'Izin Telat' || $da->status_absen == 'Izin Pulang Cepat' || $da->status_absen == 'Izin Masuk' || $da->status_absen == 'Sakit')
                                                <span class="badge badge-warning">{{ $da->status_absen }}</span>
                                            @elseif($da->status_absen == 'Masuk')
                                                <span class="badge badge-success">{{ $da->status_absen }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ $da->status_absen }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <ul class="action">
                                                @if($da->status_absen == 'Libur')
                                                    <li class="me-2">
                                                        <span class="badge badge-info">Libur</span>
                                                    </li>
                                                @elseif($da->status_absen == 'Cuti')
                                                    <li class="me-2">
                                                        <span class="badge badge-warning">Sedang Cuti</span>
                                                    </li>
                                                @elseif($da->status_absen == 'Izin Masuk')
                                                    <li class="me-2">
                                                        <span class="badge badge-warning">Sedang Izin Masuk</span>
                                                    </li>
                                                @elseif($da->status_absen == 'Sakit')
                                                    <li class="me-2">
                                                        <span class="badge badge-warning">Sedang Sakit</span>
                                                    </li>
                                                @else
                                                    @if ($da->id)
                                                        <li class="me-2">
                                                            <a href="{{ url('/data-absen/' . $da->id . '/edit-masuk') }}"
                                                                class="btn btn-xs btn-warning">Edit Masuk</a>
                                                        </li>
                                                    @endif
                                                @endif

                                                @if($da->status_absen == 'Libur')
                                                    <li class="me-2">
                                                        <span class="badge badge-info">Libur</span>
                                                    </li>
                                                @elseif($da->status_absen == 'Cuti')
                                                    <li class="me-2">
                                                        <span class="badge badge-warning">Sedang Cuti</span>
                                                    </li>
                                                @elseif($da->status_absen == 'Izin Masuk')
                                                    <li class="me-2">
                                                        <span class="badge badge-warning">Sedang Izin Masuk</span>
                                                    </li>
                                                @elseif($da->status_absen == 'Sakit')
                                                    <li class="me-2">
                                                        <span class="badge badge-warning">Sedang Sakit</span>
                                                    </li>
                                                @elseif($statusTanpaScanLabel)
                                                <span class="badge {{ $statusTanpaScanBadge }}">{{ $statusTanpaScanLabel }}</span>
                                            @elseif($da->jam_absen == null)
                                                    <li class="me-2">
                                                        <span class="badge badge-danger">Belum Masuk</span>
                                                    </li>
                                                @else
                                                    @if ($da->id)
                                                        <li class="me-2">
                                                            <a href="{{ url('/data-absen/' . $da->id . '/edit-pulang') }}"
                                                                class="btn btn-xs btn-warning">Edit Pulang</a>
                                                        </li>
                                                    @endif
                                                @endif

                                                @if ($da->id)
                                                    <li class="delete">
                                                        <form action="{{ url('/data-absen/' . $da->id . '/delete') }}" method="post"
                                                            class="d-inline">
                                                            @method('delete')
                                                            @csrf
                                                            <button class="border-0" style="background-color: transparent"
                                                                onClick="return confirm('Are You Sure')"><i
                                                                    class="fas fa-trash"></i></button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $data_absen->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox Modal untuk Zoom Foto -->
    <div id="photoLightbox" class="photo-lightbox" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <img class="lightbox-content" id="lightboxImg">
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>

    <style>
        /* Clickable photo style */
        .photo-thumbnail {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .photo-thumbnail:hover {
            transform: scale(1.1);
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        /* Lightbox styles */
        .photo-lightbox {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            animation: fadeIn 0.3s ease;
        }

        .photo-lightbox.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.5);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .lightbox-content {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 12px;
            animation: zoomIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .lightbox-close {
            position: absolute;
            top: 30px;
            right: 45px;
            color: #fff;
            font-size: 45px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10000;
        }

        .lightbox-close:hover {
            color: #667eea;
            transform: rotate(90deg);
        }

        .lightbox-caption {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            background: rgba(0, 0, 0, 0.7);
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from {
                bottom: 0;
                opacity: 0;
            }

            to {
                bottom: 30px;
                opacity: 1;
            }
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .lightbox-content {
                max-width: 95%;
                max-height: 80%;
            }

            .lightbox-close {
                top: 15px;
                right: 20px;
                font-size: 35px;
            }

            .lightbox-caption {
                bottom: 15px;
                font-size: 14px;
                padding: 10px 18px;
            }
        }
    </style>

    @push('script')
        <script>
            $(document).ready(function () {
                $('#mulai').change(function () {
                    var mulai = $(this).val();
                    $('#akhir').val(mulai);
                });
            });

            // Lightbox Functions
            function openLightbox(imgSrc, caption) {
                const lightbox = document.getElementById('photoLightbox');
                const lightboxImg = document.getElementById('lightboxImg');
                const lightboxCaption = document.getElementById('lightboxCaption');

                lightbox.classList.add('active');
                lightboxImg.src = imgSrc;
                lightboxCaption.textContent = caption;

                // Prevent body scroll when lightbox is open
                document.body.style.overflow = 'hidden';
            }

            function closeLightbox() {
                const lightbox = document.getElementById('photoLightbox');
                lightbox.classList.remove('active');

                // Restore body scroll
                document.body.style.overflow = 'auto';
            }

            // Close on ESC key
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeLightbox();
                }
            });
        </script>
    @endpush
@endsection
