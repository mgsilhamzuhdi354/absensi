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
                        <a href="{{ url('/inventory/tambah') }}" class="btn btn-primary">+ Tambah</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <form action="{{ url('/inventory') }}">
                        <div class="row mb-2">
                            <div class="col-6">
                                <input type="text" class="form-control" name="search" placeholder="Search..." id="search" value="{{ request('search') }}">
                            </div>
                            <div class="col-3">
                                <button type="submit" id="search"class="border-0 mt-3" style="background-color: transparent;"><i class="fas fa-search"></i></button>
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
                                    <th style="min-width: 200px;" class="text-center">Kode Barang</th>
                                    <th style="min-width: 300px;" class="text-center">Nama Barang</th>
                                    <th style="min-width: 120px;" class="text-center">Stok</th>
                                    <th style="min-width: 180px;" class="text-center">Warna</th>
                                    <th style="min-width: 200px;" class="text-center">UoM</th>
                                    <th style="min-width: 500px;" class="text-center">Description</th>
                                    <th style="min-width: 300px;" class="text-center">Lokasi</th>
                                    <th style="min-width: 300px;" class="text-center">Divisi / Jabatan</th>
                                    <th style="min-width: 150px;" class="text-center">Notif Stok</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($inventories) <= 0)
                                    <tr>
                                        <td colspan="11" class="text-center">Tidak Ada Data</td>
                                    </tr>
                                @else
                                    @foreach ($inventories as $key => $inventory)
                                        <tr>
                                            <td>{{ ($inventories->currentpage() - 1) * $inventories->perpage() + $key + 1 }}.</td>
                                            <td class="text-center">{{ $inventory->kode_barang ?? '-' }}</td>
                                            <td class="text-center">{{ $inventory->nama_barang ?? '-' }}</td>
                                            <td class="text-center">{{ $inventory->formatted_stock }}</td>
                                            <td class="text-center">
                                                @include('partials.stock-color-summary', [
                                                    'variants' => $inventory->stockVariants,
                                                    'unit' => $inventory->display_uom,
                                                ])
                                            </td>
                                            <td class="text-center">{{ $inventory->display_uom }}</td>
                                            <td>{!! $inventory->desc ? nl2br(e($inventory->desc)) : '-' !!}</td>
                                            <td class="text-center">{{ $inventory->lokasi->nama_lokasi ?? '-' }}</td>
                                            <td class="text-center">{{ $inventory->jabatan->nama_jabatan ?? '-' }}</td>
                                            <td class="text-center">
                                                @include('partials.stock-alert-toggle', [
                                                    'action' => url('/inventory/'.$inventory->id.'/stock-alert'),
                                                    'enabled' => $inventory->stock_alert_enabled ?? true,
                                                    'id' => 'inventory_stock_alert_table_'.$inventory->id,
                                                ])
                                            </td>
                                            <td>
                                                <ul class="action">
                                                    <li>
                                                        <a href="{{ url('/inventory/'.$inventory->id.'/detail') }}" title="Detail"><i class="fa fa-solid fa-eye"></i></a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('/inventory/'.$inventory->id.'/qr/print') }}" target="_blank" title="Cetak QR"><i class="fa fa-solid fa-qrcode"></i></a>
                                                    </li>
                                                    <li class="edit">
                                                        <a href="{{ url('/inventory/edit/'.$inventory->id) }}"><i class="fa fa-solid fa-edit"></i></a>
                                                    </li>
                                                    <li class="delete">
                                                        <form action="{{ url('/inventory/delete/'.$inventory->id) }}" method="post" class="d-inline inventory-delete-form" data-confirm="Are You Sure">
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
                        {{ $inventories->links() }}
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
            document.querySelectorAll('.inventory-delete-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitting === '1') {
                        event.preventDefault();
                        return;
                    }

                    if (!confirm(form.dataset.confirm || 'Hapus data ini?')) {
                        event.preventDefault();
                        return;
                    }

                    form.dataset.submitting = '1';
                    form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                        button.disabled = true;
                        button.innerHTML = 'Menghapus...';
                    });
                });
            });

            document.querySelectorAll('.stock-alert-toggle-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitting === '1') {
                        event.preventDefault();
                        return;
                    }

                    form.dataset.submitting = '1';
                    form.style.pointerEvents = 'none';
                });
            });
        });
    </script>
@endpush
