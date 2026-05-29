@extends('templates.app')
@section('container')

<style>
.pengajuan-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px 15px 15px;
    color: white;
}
.pengajuan-header h2 {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 5px;
}
.pengajuan-header p { font-size: 12px; opacity: 0.85; margin: 0; }
.filter-section {
    background: white;
    padding: 12px 15px;
    border-bottom: 1px solid #f0f2f5;
}
.filter-section .form-control {
    border-radius: 8px;
    font-size: 13px;
    border: 1px solid #ddd;
}
.pengajuan-list { padding: 10px 15px; }
.pengajuan-item {
    background: white;
    border-radius: 14px;
    padding: 15px;
    margin-bottom: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    border-left: 4px solid #ddd;
}
.pengajuan-item.pending  { border-left-color: #f6a623; }
.pengajuan-item.approved { border-left-color: #27ae60; }
.pengajuan-item.ditolak  { border-left-color: #e74c3c; }
.status-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}
.badge-pending  { background: #fff3cd; color: #856404; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-ditolak  { background: #fee2e2; color: #991b1b; }
.item-label { font-size: 11px; color: #999; }
.item-val   { font-size: 13px; color: #333; font-weight: 600; }
.btn-hapus {
    background: #fee2e2;
    color: #dc2626;
    border: none;
    border-radius: 8px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}
.empty-state i { font-size: 50px; margin-bottom: 15px; opacity: 0.3; }
.empty-state p { font-size: 14px; }
.btn-ajukan-baru {
    display: block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
    padding: 13px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    margin: 10px 15px 20px;
}
.btn-ajukan-baru:hover { color: white; opacity: 0.9; }
.paginasi { padding: 0 15px 20px; }
</style>

{{-- Header --}}
<div class="pengajuan-header">
    <h2><i class="fas fa-clipboard-list me-2"></i>Pengajuan Dinas Luar</h2>
    <p>Riwayat pengajuan dinas luar Anda</p>
</div>

{{-- Alert --}}
@if(session('success'))
    <div style="margin: 10px 15px 0;">
        <div class="alert alert-success" style="border-radius: 10px; font-size: 13px; padding: 10px 15px;">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        </div>
    </div>
@endif
@if(session('error'))
    <div style="margin: 10px 15px 0;">
        <div class="alert alert-danger" style="border-radius: 10px; font-size: 13px; padding: 10px 15px;">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
        </div>
    </div>
@endif

{{-- Filter tanggal --}}
<div class="filter-section">
    <form action="{{ url('/pengajuan-dinas-luar') }}" method="get">
        <div class="row g-2">
            <div class="col-5">
                <input type="date" class="form-control form-control-sm" name="mulai" value="{{ request('mulai') }}" placeholder="Dari">
            </div>
            <div class="col-5">
                <input type="date" class="form-control form-control-sm" name="akhir" value="{{ request('akhir') }}" placeholder="Sampai">
            </div>
            <div class="col-2">
                <button type="submit" class="btn btn-sm btn-primary w-100" style="border-radius: 8px;">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Tombol Ajukan Baru --}}
<a href="{{ url('/pengajuan-dinas-luar/tambah') }}" class="btn-ajukan-baru">
    <i class="fas fa-plus-circle me-1"></i> Ajukan Dinas Luar Baru
</a>

{{-- Daftar Pengajuan --}}
<div class="pengajuan-list">
    @forelse($data as $key => $d)
        @php
            $statusClass = $d->status === 'Approved' ? 'approved' : ($d->status === 'Ditolak' ? 'ditolak' : 'pending');
            $badgeClass  = $d->status === 'Approved' ? 'badge-approved' : ($d->status === 'Ditolak' ? 'badge-ditolak' : 'badge-pending');
            $icon        = $d->status === 'Approved' ? '✅' : ($d->status === 'Ditolak' ? '❌' : '⏳');
        @endphp
        <div class="pengajuan-item {{ $statusClass }}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong style="font-size: 14px;">{{ $d->Shift->nama_shift ?? '-' }}</strong><br>
                    <small style="color:#888;">{{ $d->Shift->jam_masuk ?? '' }} - {{ $d->Shift->jam_keluar ?? '' }}</small>
                </div>
                <span class="status-badge {{ $badgeClass }}">{{ $icon }} {{ $d->status }}</span>
            </div>

            <div class="row mb-1">
                <div class="col-6">
                    <div class="item-label">Tanggal Mulai</div>
                    <div class="item-val">{{ $d->tanggal_mulai }}</div>
                </div>
                <div class="col-6">
                    <div class="item-label">Tanggal Akhir</div>
                    <div class="item-val">{{ $d->tanggal_akhir }}</div>
                </div>
            </div>
            <div class="mb-1">
                <div class="item-label">Lokasi Tujuan</div>
                <div class="item-val">{{ $d->lokasi_tujuan ?? '-' }}</div>
            </div>
            <div class="mb-1">
                <div class="item-label">Alasan</div>
                <div class="item-val">{{ Str::limit($d->alasan, 80) }}</div>
            </div>
            @if($d->catatan)
                <div class="mb-1" style="background:#f8f9fa; border-radius: 8px; padding: 8px;">
                    <div class="item-label"><i class="fas fa-comment-alt me-1"></i>Catatan Admin</div>
                    <div class="item-val" style="color:#6c757d;">{{ $d->catatan }}</div>
                </div>
            @endif
            @if($d->foto_bukti)
                <div class="mb-2">
                    <a href="{{ url('/storage/'.$d->foto_bukti) }}" target="_blank" style="font-size: 12px; color: #667eea;">
                        <i class="fas fa-image me-1"></i> Lihat Foto Bukti
                    </a>
                </div>
            @endif
            @if($d->status === 'Pending')
                <form action="{{ url('/pengajuan-dinas-luar/delete/'.$d->id) }}" method="post"
                      onsubmit="return confirm('Batalkan pengajuan ini?')">
                    @method('delete')
                    @csrf
                    <button class="btn-hapus"><i class="fas fa-times me-1"></i>Batalkan</button>
                </form>
            @endif
        </div>
    @empty
        <div class="empty-state">
            <i class="fas fa-folder-open d-block"></i>
            <p>Belum ada pengajuan dinas luar.</p>
            <a href="{{ url('/pengajuan-dinas-luar/tambah') }}" style="color: #667eea; font-weight: 600;">+ Buat Pengajuan</a>
        </div>
    @endforelse
</div>

{{-- Paginasi --}}
<div class="paginasi">
    {{ $data->links() }}
</div>

@endsection
