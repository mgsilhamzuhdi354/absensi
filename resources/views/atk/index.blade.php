@extends('templates.dashboard')

@section('isi')
    <div class="row atk-page">
        <div class="col-md-12 project-list">
            <div class="card">
                <div class="row align-items-center">
                    <div class="col-md-6 mt-2 p-0 d-flex">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0 atk-header-actions">
                        <a href="{{ url('/atk/export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-success">Export Excel</a>
                        <a href="{{ url('/atk/tambah') }}" class="btn btn-primary">+ Tambah</a>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="col-md-12">
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <form action="{{ url('/atk') }}">
                        <div class="row mb-2 atk-filter-row">
                            <div class="col-md-6 col-12 mb-2 mb-md-0">
                                <input type="text" class="form-control" name="search" placeholder="Search..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3 col-8">
                                <select name="status" class="form-control">
                                    <option value="">Semua Status</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non-Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-4">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th class="text-center">No.</th>
                                    <th style="min-width: 110px;" class="text-center">Foto</th>
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
                                        <td colspan="11" class="text-center">Tidak Ada Data</td>
                                    </tr>
                                @else
                                    @foreach ($atks as $key => $atk)
                                        <tr>
                                            <td>{{ ($atks->currentpage() - 1) * $atks->perpage() + $key + 1 }}.</td>
                                            <td class="text-center">
                                                @if ($atk->foto_barang)
                                                    <img src="{{ asset('storage/'.$atk->foto_barang) }}" alt="{{ $atk->nama_atk }}" class="atk-table-photo js-atk-photo" data-id="{{ $atk->id }}" data-name="{{ $atk->nama_atk }}" data-code="{{ $atk->kode_atk }}" data-stock="{{ $atk->formatted_stock }}" data-unit="{{ $atk->satuan }}" data-photo="{{ asset('storage/'.$atk->foto_barang) }}" data-detail-url="{{ url('/atk/'.$atk->id.'/detail') }}" data-stock-in-url="{{ url('/atk/'.$atk->id.'/stock-in') }}" data-stock-out-url="{{ url('/atk/'.$atk->id.'/stock-out') }}">
                                                @else
                                                    <button type="button" class="atk-photo-placeholder js-atk-photo" data-id="{{ $atk->id }}" data-name="{{ $atk->nama_atk }}" data-code="{{ $atk->kode_atk }}" data-stock="{{ $atk->formatted_stock }}" data-unit="{{ $atk->satuan }}" data-photo="" data-detail-url="{{ url('/atk/'.$atk->id.'/detail') }}" data-stock-in-url="{{ url('/atk/'.$atk->id.'/stock-in') }}" data-stock-out-url="{{ url('/atk/'.$atk->id.'/stock-out') }}"><i class="fa fa-image"></i></button>
                                                @endif
                                            </td>
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

                    <div class="d-md-none atk-mobile-list">
                        @forelse ($atks as $atk)
                            <div class="atk-mobile-card">
                                <button type="button" class="atk-mobile-photo js-atk-photo" data-id="{{ $atk->id }}" data-name="{{ $atk->nama_atk }}" data-code="{{ $atk->kode_atk }}" data-stock="{{ $atk->formatted_stock }}" data-unit="{{ $atk->satuan }}" data-photo="{{ $atk->foto_barang ? asset('storage/'.$atk->foto_barang) : '' }}" data-detail-url="{{ url('/atk/'.$atk->id.'/detail') }}" data-stock-in-url="{{ url('/atk/'.$atk->id.'/stock-in') }}" data-stock-out-url="{{ url('/atk/'.$atk->id.'/stock-out') }}">
                                    @if ($atk->foto_barang)
                                        <img src="{{ asset('storage/'.$atk->foto_barang) }}" alt="{{ $atk->nama_atk }}">
                                    @else
                                        <i class="fa fa-image"></i>
                                    @endif
                                </button>
                                <div class="atk-mobile-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6>{{ $atk->nama_atk }}</h6>
                                            <div class="text-muted small">{{ $atk->kode_atk }}</div>
                                        </div>
                                        @if ($atk->active == 1)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-danger">Non-Aktif</span>
                                        @endif
                                    </div>
                                    <div class="atk-mobile-meta">
                                        <span>{{ $atk->kategori ?? 'Tanpa kategori' }}</span>
                                        <span>{{ $atk->lokasi ?? 'Tanpa lokasi' }}</span>
                                    </div>
                                    <div class="atk-stock-line">
                                        <strong>{{ $atk->formatted_stock }}</strong>
                                        <span>{{ $atk->satuan }}</span>
                                    </div>
                                    <div class="atk-mobile-actions">
                                        <a href="{{ url('/atk/'.$atk->id.'/detail') }}" class="btn btn-primary btn-sm">Detail</a>
                                        <button type="button" class="btn btn-success btn-sm js-atk-open-stock" data-name="{{ $atk->nama_atk }}" data-stock="{{ $atk->formatted_stock }}" data-unit="{{ $atk->satuan }}" data-stock-in-url="{{ url('/atk/'.$atk->id.'/stock-in') }}" data-stock-out-url="{{ url('/atk/'.$atk->id.'/stock-out') }}">Ubah Stok</button>
                                        <a href="{{ url('/atk/edit/'.$atk->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">Tidak Ada Data</div>
                        @endforelse
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        {{ $atks->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="atkQuickStockModal" tabindex="-1" aria-labelledby="atkQuickStockTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" id="atkQuickStockForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="atkQuickStockTitle">Ubah Stok ATK</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border mb-3">
                            <strong id="atkQuickStockName">-</strong>
                            <div class="small text-muted">Stok saat ini: <span id="atkQuickStockCurrent">0</span></div>
                        </div>
                        <div class="form-group">
                            <label>Jenis Perubahan</label>
                            <select class="form-control" id="atkQuickStockType">
                                <option value="masuk">Stok Masuk</option>
                                <option value="keluar">Stok Keluar</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal_transaksi" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-6 form-group">
                                <label>Jumlah</label>
                                <input type="number" step="0.01" min="0.01" name="jumlah" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group" id="atkQuickStockSourceGroup">
                            <label>Sumber Barang</label>
                            <input type="text" name="sumber_barang" class="form-control">
                        </div>
                        <div class="form-group d-none" id="atkQuickStockReceiverGroup">
                            <label>Penerima Barang</label>
                            <input type="text" name="penerima_barang" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Stok</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <br>
@endsection

@push('style')
    <style>
        .atk-header-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .atk-table-photo,
        .atk-photo-placeholder {
            width: 62px;
            height: 52px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            cursor: pointer;
        }

        .atk-photo-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            background: #f8fafc;
        }

        .atk-mobile-list {
            max-height: 68vh;
            overflow-y: auto;
            padding-right: 2px;
        }

        .atk-mobile-card {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #edf2f7;
        }

        .atk-mobile-photo {
            width: 86px;
            height: 86px;
            flex: 0 0 86px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0;
        }

        .atk-mobile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .atk-mobile-body {
            min-width: 0;
            flex: 1;
        }

        .atk-mobile-body h6 {
            margin: 0;
            font-size: 14px;
            line-height: 1.35;
        }

        .atk-mobile-meta {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin: 8px 0;
            color: #64748b;
            font-size: 12px;
        }

        .atk-stock-line {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 8px;
            background: #eef6ff;
            color: #0f172a;
            font-size: 13px;
        }

        .atk-mobile-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .atk-swal-photo {
            width: 100%;
            max-height: 260px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .atk-page .card {
                border-radius: 8px;
            }

            .atk-header-actions {
                justify-content: flex-start;
                margin-top: 10px;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var quickStockForm = document.getElementById('atkQuickStockForm');
            var quickStockType = document.getElementById('atkQuickStockType');
            var quickStockModalElement = document.getElementById('atkQuickStockModal');
            var quickStockModal = window.bootstrap && quickStockModalElement ? new bootstrap.Modal(quickStockModalElement) : null;
            var quickStockUrls = { masuk: '', keluar: '' };

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
                });
            }

            function syncQuickStockForm() {
                var type = quickStockType.value;
                quickStockForm.action = type === 'keluar' ? quickStockUrls.keluar : quickStockUrls.masuk;
                document.getElementById('atkQuickStockSourceGroup').classList.toggle('d-none', type === 'keluar');
                document.getElementById('atkQuickStockReceiverGroup').classList.toggle('d-none', type !== 'keluar');
            }

            function openQuickStock(trigger) {
                quickStockUrls.masuk = trigger.getAttribute('data-stock-in-url') || '';
                quickStockUrls.keluar = trigger.getAttribute('data-stock-out-url') || '';
                document.getElementById('atkQuickStockName').textContent = trigger.getAttribute('data-name') || '-';
                document.getElementById('atkQuickStockCurrent').textContent = (trigger.getAttribute('data-stock') || '0') + ' ' + (trigger.getAttribute('data-unit') || '');
                quickStockForm.reset();
                quickStockForm.querySelector('[name="tanggal_transaksi"]').value = '{{ date('Y-m-d') }}';
                quickStockType.value = 'masuk';
                syncQuickStockForm();

                if (quickStockModal) {
                    quickStockModal.show();
                }
            }

            if (quickStockType) {
                quickStockType.addEventListener('change', syncQuickStockForm);
            }

            document.querySelectorAll('.js-atk-open-stock').forEach(function (button) {
                button.addEventListener('click', function () {
                    openQuickStock(button);
                });
            });

            document.querySelectorAll('.js-atk-photo').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    var photo = trigger.getAttribute('data-photo');
                    var name = trigger.getAttribute('data-name') || '-';
                    var code = trigger.getAttribute('data-code') || '-';
                    var stock = trigger.getAttribute('data-stock') || '0';
                    var unit = trigger.getAttribute('data-unit') || '';
                    var imageHtml = photo
                        ? '<img src="' + escapeHtml(photo) + '" class="atk-swal-photo" alt="' + escapeHtml(name) + '">'
                        : '<div class="atk-swal-photo d-flex align-items-center justify-content-center text-muted" style="height:180px;"><i class="fa fa-image fa-2x"></i></div>';

                    if (!window.Swal) {
                        window.location.href = trigger.getAttribute('data-detail-url');
                        return;
                    }

                    Swal.fire({
                        title: escapeHtml(name),
                        html: imageHtml + '<div class="mt-3 text-start"><div><strong>Kode:</strong> ' + escapeHtml(code) + '</div><div><strong>Stok:</strong> ' + escapeHtml(stock + ' ' + unit) + '</div></div>',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Detail',
                        denyButtonText: 'Ubah Stok',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#4361ee',
                        denyButtonColor: '#16a34a',
                        cancelButtonColor: '#64748b',
                        width: 420
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            window.location.href = trigger.getAttribute('data-detail-url');
                        } else if (result.isDenied) {
                            openQuickStock(trigger);
                        }
                    });
                });
            });

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
