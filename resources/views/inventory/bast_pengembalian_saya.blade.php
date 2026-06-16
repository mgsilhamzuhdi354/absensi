@extends('templates.app')
@section('container')
    @php
        $signatureRoles = \App\Models\DokumenPengembalianAset::signatureRoles();
        $pendingCount = $documents->getCollection()->filter(function ($document) use ($signatureRoles) {
            foreach ($signatureRoles as $role => $config) {
                if ($document->canUserSignRole(auth()->user(), $role) && !$document->{$config['signed_at']}) {
                    return true;
                }
            }

            return false;
        })->count();
    @endphp

    <div class="card-secton transfer-section">
        <div class="tf-container">
            <div class="tf-balance-box">
                <div class="tf-spacing-16"></div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">BAST Pengembalian Aset</h3>
                        <p class="text-muted mb-0">Surat pengembalian aset yang terkait dengan akun Anda.</p>
                    </div>
                    @if ($pendingCount > 0)
                        <span class="badge bg-warning text-dark">{{ $pendingCount }} perlu TTD</span>
                    @endif
                </div>
                <div class="tf-spacing-16"></div>
            </div>
        </div>
    </div>

    <div id="app-wrap">
        <div class="bill-content">
            <div class="tf-container">
                <ul class="mt-3 mb-5">
                    @forelse ($documents as $document)
                        @php
                            $inventory = $document->inventory;
                            $userRoles = collect($signatureRoles)->filter(function ($config, $role) use ($document) {
                                return $document->canUserSignRole(auth()->user(), $role);
                            });
                            $hasPending = $userRoles->contains(function ($config) use ($document) {
                                return !$document->{$config['signed_at']};
                            });
                        @endphp
                        <li class="list-card-invoice tf-topbar d-flex justify-content-between align-items-center p-3 mb-3"
                            style="background-color: #fff; border-radius: 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);">
                            <a href="{{ url('/my-inventory-return-bast/'.$document->id) }}" class="w-100" style="text-decoration: none; color: inherit;">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <h4 class="mb-1">{{ $inventory->nama_barang ?? 'Aset Kantor' }}</h4>
                                        <p class="mb-1 text-muted">{{ $document->nomor_surat }}</p>
                                        <p class="mb-0 text-muted">
                                            {{ optional($document->tanggal_surat)->format('d/m/Y') ?? '-' }}
                                            @if ($inventory)
                                                - {{ $inventory->kode_barang ?? '-' }}
                                            @endif
                                        </p>
                                        <div class="mt-2">
                                            @foreach ($userRoles as $config)
                                                @if ($document->{$config['signed_at']})
                                                    <span class="badge bg-success me-1">{{ $config['short_label'] }} sudah TTD</span>
                                                @else
                                                    <span class="badge bg-warning text-dark me-1">{{ $config['short_label'] }} perlu TTD</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @if ($hasPending)
                                        <span class="badge bg-warning text-dark">Menunggu TTD</span>
                                    @else
                                        <span class="badge bg-success">Selesai</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="p-4 text-center text-muted" style="background-color: #fff; border-radius: 14px;">
                            Belum ada BAST pengembalian aset untuk Anda.
                        </li>
                    @endforelse
                </ul>

                <div class="d-flex justify-content-end me-4 mt-4">
                    {{ $documents->links() }}
                </div>
            </div>
        </div>
    </div>
    <br><br><br><br>
@endsection
