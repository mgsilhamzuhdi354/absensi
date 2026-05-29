<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Izin / Cuti - {{ $cuti->User->name ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            background: #f5f5f5;
            padding: 0;
        }
        .page {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 30px 40px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .print-bar {
            max-width: 800px;
            margin: 20px auto 0;
            text-align: right;
        }
        .print-bar button {
            background: #1a56db;
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 8px;
        }
        .print-bar button:hover { background: #1443b0; }
        .print-bar .btn-back {
            background: #6b7280;
        }
        .print-bar .btn-back:hover { background: #4b5563; }
        @media print {
            body { background: #fff; padding: 0; }
            .page { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; }
            .print-bar { display: none !important; }
            .watermark { display: block !important; }
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1a56db;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            color: #1a56db;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header p {
            font-size: 11px;
            color: #555;
            margin-top: 3px;
        }
        .no-surat {
            text-align: center;
            margin-bottom: 18px;
            font-size: 11px;
            color: #555;
        }
        .section-title {
            font-weight: bold;
            font-size: 13px;
            background: #e8f0fe;
            color: #1a56db;
            padding: 6px 12px;
            border-left: 4px solid #1a56db;
            margin-bottom: 12px;
            margin-top: 18px;
        }
        table.info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.info td {
            padding: 6px 10px;
            vertical-align: top;
            border: 1px solid #dce8f0;
        }
        table.info td:first-child {
            width: 38%;
            font-weight: bold;
            background: #f0f4ff;
            color: #333;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            color: #fff;
        }
        .badge-success { background: #16a34a; }
        .badge-danger  { background: #dc2626; }
        .badge-warning { background: #d97706; }
        .badge-info    { background: #0284c7; }
        .foto-section {
            margin-top: 18px;
            text-align: center;
        }
        .foto-section img {
            max-width: 260px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #dce8f0;
            margin-top: 8px;
        }
        .status-box {
            margin-top: 22px;
            border: 2px solid #1a56db;
            border-radius: 8px;
            padding: 12px 18px;
            background: #f0f4ff;
        }
        .status-box h3 {
            font-size: 13px;
            color: #1a56db;
            margin-bottom: 6px;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #dce8f0;
            padding-top: 14px;
            display: flex;
            justify-content: space-between;
        }
        .ttd-box {
            text-align: center;
            width: 45%;
        }
        .ttd-box .ttd-line {
            border-bottom: 1px solid #333;
            margin-top: 55px;
            margin-bottom: 4px;
        }
        .ttd-box p { font-size: 11px; color: #555; }
        .watermark {
            position: fixed;
            top: 38%;
            left: 20%;
            font-size: 60px;
            color: rgba(26, 86, 219, 0.06);
            font-weight: bold;
            transform: rotate(-30deg);
            white-space: nowrap;
            pointer-events: none;
        }
        .tipe-sakit-info {
            margin-top: 8px;
            padding: 8px 12px;
            background: #fff3e0;
            border-left: 4px solid #fb8c00;
            border-radius: 4px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    {{-- PRINT BAR --}}
    <div class="print-bar">
        <button class="btn-back" onclick="history.back()">← Kembali</button>
        <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    </div>

    <div class="page">
        <div class="watermark">{{ strtoupper($cuti->status_cuti ?? 'PENDING') }}</div>

        {{-- HEADER --}}
        <div class="header">
            @if($company_name)
                <h1>{{ $company_name }}</h1>
            @else
                <h1>Sistem Absensi Karyawan</h1>
            @endif
            <p>Surat Keterangan Izin / Cuti Karyawan</p>
        </div>

        <div class="no-surat">
            No. Referensi: CUTI-{{ str_pad($cuti->id, 5, '0', STR_PAD_LEFT) }} &nbsp;|&nbsp; Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB
        </div>

        {{-- INFO KARYAWAN --}}
        <div class="section-title">Data Karyawan</div>
        <table class="info">
            <tr>
                <td>Nama Karyawan</td>
                <td>{{ $cuti->User->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Lokasi / Cabang</td>
                <td>{{ $cuti->lokasi->nama_lokasi ?? '-' }}</td>
            </tr>
        </table>

        {{-- INFO CUTI --}}
        <div class="section-title">Detail Izin / Cuti</div>
        <table class="info">
            <tr>
                <td>Jenis Izin / Cuti</td>
                <td>
                    <strong>{{ $cuti->nama_cuti ?? '-' }}</strong>
                    @if($cuti->nama_cuti === 'Sakit' && $cuti->tipe_sakit)
                        @php
                            $tipeLabels = [
                                'surat_dokter'      => ['label' => 'Dengan Surat Dokter',    'info' => 'Tidak dipotong gaji'],
                                'tanpa_surat_dokter' => ['label' => 'Tanpa Surat Dokter',    'info' => 'Dipotong gaji'],
                                'keluarga_meninggal' => ['label' => 'Keluarga Meninggal',    'info' => 'Tidak dipotong gaji'],
                            ];
                            $tipeInfo = $tipeLabels[$cuti->tipe_sakit] ?? null;
                        @endphp
                        @if($tipeInfo)
                            <div class="tipe-sakit-info">
                                🏥 {{ $tipeInfo['label'] }} — {{ $tipeInfo['info'] }}
                                @if($cuti->tipe_sakit === 'tanpa_surat_dokter' && $cuti->potongan_gaji > 0)
                                    <br>Potongan Gaji: <strong>Rp {{ number_format($cuti->potongan_gaji, 0, ',', '.') }}</strong>
                                @endif
                            </div>
                        @endif
                    @endif
                </td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>{{ \Carbon\Carbon::parse($cuti->tanggal)->translatedFormat('l, d F Y') }}</td>
            </tr>
            <tr>
                <td>Alasan</td>
                <td>{{ $cuti->alasan_cuti ?? '-' }}</td>
            </tr>
            <tr>
                <td>Catatan Admin</td>
                <td>{{ $cuti->catatan ?? '-' }}</td>
            </tr>
        </table>

        {{-- STATUS --}}
        <div class="status-box">
            <h3>Status Permohonan</h3>
            @php
                $badgeClass = match($cuti->status_cuti) {
                    'Diterima' => 'badge-success',
                    'Ditolak'  => 'badge-danger',
                    default    => 'badge-warning',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ strtoupper($cuti->status_cuti ?? 'PENDING') }}</span>
            @if($cuti->ua)
                &nbsp; Diproses oleh: <strong>{{ $cuti->ua->name }}</strong>
            @endif
        </div>

        {{-- FOTO --}}
        @if($cuti->foto_cuti)
        <div class="foto-section">
            <div class="section-title" style="text-align:left;">Lampiran Foto</div>
            <img src="{{ url('storage/'.$cuti->foto_cuti) }}" alt="Foto Cuti">
        </div>
        @endif

        {{-- FOOTER / TTD --}}
        <div class="footer">
            <div class="ttd-box">
                <p>Karyawan Bersangkutan</p>
                <div class="ttd-line"></div>
                <p><strong>{{ $cuti->User->name ?? '...' }}</strong></p>
            </div>
            <div class="ttd-box">
                <p>Admin / HRD</p>
                <div class="ttd-line"></div>
                <p><strong>{{ $cuti->ua->name ?? '(Belum diproses)' }}</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
