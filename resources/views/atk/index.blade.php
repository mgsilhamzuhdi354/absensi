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
                        <a href="{{ url('/atk/export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-success">Export Excel</a>
                        <a href="{{ url('/atk/tambah') }}" class="btn btn-primary">+ Tambah</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <form action="{{ url('/atk') }}">
                        <div class="row mb-2">
                            <div class="col-6">
                                <input type="text" class="form-control" name="search" placeholder="Search..." value="{{ request('search') }}">
                            </div>
                            <div class="col-3">
                                <select name="status" class="form-control">
                                    <option value="">Semua Status</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non-Aktif</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <button type="submit" class="border-0 mt-3" style="background-color: transparent;"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th class="text-center">No.</th>
                                    <th style="min-width: 160px;" class="text-center">Kode ATK</th>
                                    <th style="min-width: 240px;" class="text-center">Nama ATK</th>
                                    <th style="min-width: 160px;" class="text-center">Kategori</th>
                                    <th style="min-width: 100px;" class="text-center">Stok</th>
                                    <th style="min-width: 120px;" class="text-center">Satuan</th>
                                    <th style="min-width: 180px;" class="text-center">Lokasi</th>
                                    <th style="min-width: 240px;" class="text-center">Keterangan</th>
                                    <th style="min-width: 100px;" class="text-center">Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($atks) <= 0)
                                    <tr>
                                        <td colspan="10" class="text-center">Tidak Ada Data</td>
                                    </tr>
                                @else
                                    @foreach ($atks as $key => $atk)
                                        <tr>
                                            <td>{{ ($atks->currentpage() - 1) * $atks->perpage() + $key + 1 }}.</td>
                                            <td class="text-center">{{ $atk->kode_atk }}</td>
                                            <td>{{ $atk->nama_atk }}</td>
                                            <td class="text-center">{{ $atk->kategori ?? '-' }}</td>
                                            <td class="text-center">{{ $atk->formatted_stock }}</td>
                                            <td class="text-center">{{ $atk->satuan }}</td>
                                            <td class="text-center">{{ $atk->lokasi ?? '-' }}</td>
                                            <td>{!! $atk->keterangan ? nl2br(e($atk->keterangan)) : '-' !!}</td>
                                            <td class="text-center">
                                                @if ($atk->active == 1)
                                                    <span class="badge badge-success">Aktif</span>
                                                @else
                                                    <span class="badge badge-danger">Non-Aktif</span>
                                                @endif
                                            </td>
                                            <td>
                                                <ul class="action">
                                                    <li>
                                                        <a href="{{ url('/atk/'.$atk->id.'/detail') }}" title="Detail"><i class="fa fa-solid fa-eye"></i></a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('/atk/'.$atk->id.'/qr/print') }}" target="_blank" title="Cetak QR"><i class="fa fa-solid fa-qrcode"></i></a>
                                                    </li>
                                                    <li class="edit">
                                                        <a href="{{ url('/atk/edit/'.$atk->id) }}"><i class="fa fa-solid fa-edit"></i></a>
                                                    </li>
                                                    <li class="delete">
                                                        <form action="{{ url('/atk/delete/'.$atk->id) }}" method="post" class="d-inline atk-delete-form" data-confirm="Hapus data ATK ini?">
                                                            @method('delete')
                                                            @csrf
                                                            <button type="submit" class="border-0" style="background-color: transparent;"><i class="fa fa-solid fa-trash"></i></button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $atks->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.atk-delete-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!confirm(form.dataset.confirm || 'Hapus data ini?')) {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
@endpush
