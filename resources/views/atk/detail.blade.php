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
                        <a href="{{ url('/atk/edit/'.$atk->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <a href="{{ url('/atk') }}" class="btn btn-danger btn-sm ms-2">Back</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $atk->nama_atk }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <tbody>
                                <tr>
                                    <th style="width: 210px;">Kode ATK</th>
                                    <td>{{ $atk->kode_atk ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Nama ATK</th>
                                    <td>{{ $atk->nama_atk ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Kategori</th>
                                    <td>{{ $atk->kategori ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Stok Saat Ini</th>
                                    <td>
                                        <strong>{{ $atk->formatted_stock }}</strong>
                                        <span class="badge bg-light text-dark border">{{ $atk->satuan ?? '-' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Lokasi</th>
                                    <td>{{ $atk->lokasi ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if ($atk->active == 1)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-danger">Non-Aktif</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tanggal Dibuat</th>
                                    <td>{{ optional($atk->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Update</th>
                                    <td>{{ optional($atk->updated_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6>Keterangan</h6>
                    <p>{!! $atk->keterangan ? nl2br(e($atk->keterangan)) : '-' !!}</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">QR Code</h5>
                </div>
                <div class="card-body text-center">
                    @if ($atk->qr_code_image)
                        <img src="{{ asset('storage/'.$atk->qr_code_image) }}" alt="QR {{ $atk->kode_atk }}" class="img-fluid mb-3" style="max-width: 240px;">
                    @else
                        <div class="alert alert-warning">QR Code belum dibuat.</div>
                    @endif
                    <div class="mb-3">
                        <small class="text-muted d-block text-break">{{ $atk->qr_code_value }}</small>
                    </div>
                    <a href="{{ url('/atk/'.$atk->id.'/qr/print') }}" target="_blank" class="btn btn-primary btn-sm mb-2">Cetak Label QR</a>
                    <a href="{{ url('/atk/'.$atk->id.'/qr/download') }}" class="btn btn-info btn-sm mb-2">Download QR</a>
                </div>
            </div>
        </div>
    </div>
@endsection
