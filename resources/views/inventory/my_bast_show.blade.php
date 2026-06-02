@extends('templates.app')
@section('container')
    @php
        $transaction = $document->transaction;
        $inventory = $transaction ? $transaction->inventory : null;
    @endphp

    <div class="card-secton transfer-section">
        <div class="tf-container">
            <div class="tf-balance-box">
                <div class="tf-spacing-16"></div>
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h3 class="mb-1">{{ $inventory->nama_barang ?? 'BAST Inventory' }}</h3>
                        <p class="text-muted mb-0">{{ $document->nomor_surat }}</p>
                    </div>
                    @if ($document->signed_at)
                        <span class="badge bg-success">Sudah TTD</span>
                    @else
                        <span class="badge bg-warning text-dark">Menunggu TTD</span>
                    @endif
                </div>
                <div class="tf-spacing-16"></div>
            </div>
        </div>
    </div>

    <div id="app-wrap">
        <div class="bill-content">
            <div class="tf-container">
                @if ($errors->any())
                    <div class="alert alert-danger mt-3">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="p-3 mt-3" style="background: #fff; border-radius: 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);">
                    <h4 class="mb-3">Informasi Surat</h4>
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 140px;">Nomor</th>
                                <td>{{ $document->nomor_surat }}</td>
                            </tr>
                            <tr>
                                <th>Tanggal</th>
                                <td>{{ optional($document->tanggal_surat)->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Penyerah</th>
                                <td>{{ $document->nama_penyerah ?? ($transaction->processedBy->name ?? '-') }}</td>
                            </tr>
                            <tr>
                                <th>Penerima</th>
                                <td>{{ $document->nama_penerima ?? auth()->user()->name }}</td>
                            </tr>
                            <tr>
                                <th>Status TTD</th>
                                <td>
                                    @if ($document->signed_at)
                                        Ditandatangani oleh {{ $document->receiver_signature_name ?? auth()->user()->name }}
                                        pada {{ $document->signed_at->format('d/m/Y H:i') }}
                                    @else
                                        Menunggu tanda tangan Anda
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 mt-3" style="background: #fff; border-radius: 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);">
                    <h4 class="mb-3">Barang Yang Diterima</h4>
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 140px;">Kode</th>
                                <td>{{ $inventory->kode_barang ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Nama</th>
                                <td>{{ $inventory->nama_barang ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Merk / Tipe</th>
                                <td>{{ $inventory->merk_tipe ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Serial Number</th>
                                <td>{{ $inventory->serial_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Kondisi</th>
                                <td>{{ $transaction->kondisi_barang ?? ($inventory->kondisi ?? '-') }}</td>
                            </tr>
                            <tr>
                                <th>Keperluan</th>
                                <td>{{ $transaction->keperluan ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 mt-3 mb-5" style="background: #fff; border-radius: 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="{{ url('/my-inventory-bast/'.$document->id.'/download') }}" class="btn btn-primary">
                            Download BAST
                        </a>
                        <a href="{{ url('/my-inventory-bast') }}" class="btn btn-light">
                            Kembali
                        </a>
                    </div>

                    @if (!$document->signed_at)
                        <form method="post" action="{{ url('/my-inventory-bast/'.$document->id.'/sign') }}">
                            @csrf
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="agreement" name="agreement" value="1">
                                <label class="form-check-label" for="agreement">
                                    Saya menyatakan sudah menerima barang sesuai informasi di surat BAST ini dan bersedia menandatangani secara elektronik.
                                </label>
                            </div>
                            <button type="submit" class="btn btn-success"
                                onclick="return confirm('Tandatangani BAST ini atas nama {{ auth()->user()->name }}?')">
                                Tanda Tangani / Terima Barang
                            </button>
                        </form>
                    @else
                        <div class="alert alert-success mb-0">
                            Surat ini sudah ditandatangani secara elektronik pada {{ $document->signed_at->format('d/m/Y H:i') }}.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <br><br><br><br>
@endsection
