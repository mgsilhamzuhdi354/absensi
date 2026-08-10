@php
    $tanggal = \Carbon\Carbon::parse($document->tanggal_surat);
    $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $tanggalTeks = $tanggal->format('d') . ' ' . $bulan[(int) $tanggal->format('n')] . ' ' . $tanggal->format('Y');
    $hariTeks = $hari[$tanggal->format('l')] ?? $tanggal->format('l');
    $companyName = $company->name ?? 'PT Indo Ocean Crew Service';
    $companyEmail = $company->email ?? 'ios@indooceancrew.co.id';
    $companyPhone = $company->phone ?? '+62 822-6012-1933';
    $companyAddress = $company->address ?? 'Jakarta, Indonesia';
    $signatureSrc = function ($path) {
        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($path));
    };
    $signatureRows = [
        [
            'heading' => 'PIHAK PERTAMA',
            'subtitle' => 'Yang Mengembalikan',
            'name' => $document->employee_signature_name ?: ($document->nama_pengembali ?: '-'),
            'position' => $document->jabatan_pengembali ?: '-',
            'signed_at' => $document->employee_signed_at,
            'image' => $signatureSrc($document->employee_signature_image),
        ],
        [
            'heading' => 'MENGETAHUI',
            'subtitle' => 'Crewing / HRD / Manager',
            'name' => $document->known_signature_name ?: ($document->nama_mengetahui ?: '(____________________)'),
            'position' => optional(optional($document->knownBy)->Jabatan)->nama_jabatan ?: 'Crewing / HRD / Manager',
            'signed_at' => $document->known_signed_at,
            'image' => $signatureSrc($document->known_signature_image),
        ],
        [
            'heading' => 'PIHAK KEDUA',
            'subtitle' => 'Yang Menerima',
            'name' => $document->it_receiver_signature_name ?: ($document->nama_penerima ?: '-'),
            'position' => $document->jabatan_penerima ?: '-',
            'signed_at' => $document->it_receiver_signed_at,
            'image' => $signatureSrc($document->it_receiver_signature_image),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST Pengembalian {{ $document->nomor_surat }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.45; margin: 18px 24px; }
        .center { text-align: center; }
        .company-title { font-size: 26px; font-weight: 700; margin: 0; }
        .company-subtitle { font-size: 10px; color: #4b5563; margin: 4px 0 10px; }
        .hr-line { border-top: 2px solid #1f3c68; margin: 6px 0 12px; }
        .doc-title { font-size: 21px; font-weight: 700; color: #1f3c68; margin: 0 0 4px; }
        .doc-number { font-size: 12px; margin: 0 0 14px; }
        .paragraph { margin: 0 0 8px; text-align: justify; }
        table { width: 100%; border-collapse: collapse; }
        .party-table { margin: 8px 0 12px; }
        .party-table td { width: 50%; padding: 0 8px 0 0; vertical-align: top; }
        .party-card { border: 1px solid #b6c2d3; }
        .party-head { background: #e7eff8; color: #1f4b86; font-weight: 700; border-bottom: 1px solid #b6c2d3; padding: 6px 8px; font-size: 10px; }
        .party-body td { border: none; padding: 3px 8px; font-size: 10px; }
        .party-body td:first-child { width: 78px; color: #374151; }
        .items { margin-top: 8px; }
        .items th, .items td { border: 1px solid #8aa0bc; padding: 6px; vertical-align: top; }
        .items th { background: #1f3c68; color: #fff; font-weight: 700; font-size: 10px; text-align: center; }
        .signatures { margin-top: 16px; table-layout: fixed; }
        .signatures td { width: 33.33%; text-align: center; vertical-align: top; padding: 0 7px; }
        .signature-heading { font-size: 10.5px; font-weight: 700; min-height: 14px; }
        .signature-subtitle { font-size: 10px; min-height: 14px; }
        .signature-box { height: 92px; border: 1px solid #d8e1ef; margin: 8px 0; padding: 5px 6px; background: #fbfdff; overflow: hidden; }
        .signature-media { height: 50px; line-height: 50px; text-align: center; margin-bottom: 3px; }
        .signature-image { max-width: 135px; max-height: 50px; display: inline-block; vertical-align: middle; }
        .signature-placeholder { color: #6b7280; font-size: 9px; line-height: 50px; }
        .signature-digital { font-size: 9px; color: #1f4b86; line-height: 1.25; padding-top: 10px; }
        .verified-stamp { color: #1f4b86; font-size: 8.5px; font-weight: 700; border: 1px solid #9db7da; display: inline-block; padding: 2px 7px; background: #f5f9ff; line-height: 1.15; }
        .signature-time { color: #315f9d; font-size: 8.5px; margin-top: 4px; line-height: 1.2; }
        .signature-name { font-size: 10px; font-weight: 700; line-height: 1.25; }
        .signature-position { font-size: 10px; line-height: 1.25; margin-top: 3px; }
        ol { padding-left: 18px; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="center">
        <h2 class="company-title">{{ strtoupper($companyName) }}</h2>
        <div class="company-subtitle">Email: {{ $companyEmail }} | Telp: {{ $companyPhone }} | {{ $companyAddress }}</div>
    </div>
    <div class="hr-line"></div>
    <div class="center">
        <div class="doc-title">BERITA ACARA PENGEMBALIAN BARANG</div>
        <div class="doc-number">Nomor: {{ $document->nomor_surat }}</div>
    </div>

    <p class="paragraph">
        Pada hari ini, {{ $hariTeks }} tanggal {{ $tanggalTeks }}, bertempat di kantor {{ $companyName }},
        telah dilakukan pengembalian aset/inventaris perusahaan dari karyawan kepada perusahaan dengan rincian berikut:
    </p>

    <table class="party-table">
        <tr>
            <td>
                <div class="party-card">
                    <div class="party-head">PIHAK PERTAMA (YANG MENGEMBALIKAN)</div>
                    <table class="party-body">
                        <tr><td>Nama</td><td>: {{ $document->nama_pengembali ?: '-' }}</td></tr>
                        <tr><td>Jabatan</td><td>: {{ $document->jabatan_pengembali ?: '-' }}</td></tr>
                        <tr><td>Departemen</td><td>: {{ $document->departemen_pengembali ?: '-' }}</td></tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="party-card">
                    <div class="party-head">PIHAK KEDUA (YANG MENERIMA)</div>
                    <table class="party-body">
                        <tr><td>Nama</td><td>: {{ $document->nama_penerima ?: '-' }}</td></tr>
                        <tr><td>Jabatan</td><td>: {{ $document->jabatan_penerima ?: '-' }}</td></tr>
                        <tr><td>Departemen</td><td>: {{ $document->departemen_penerima ?: '-' }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 40px;">NO</th>
                <th>JENIS BARANG</th>
                <th>SPESIFIKASI & SERIAL NUMBER (SN)</th>
                <th style="width: 80px;">JUMLAH</th>
                <th style="width: 110px;">KONDISI</th>
                <th style="width: 110px;">KELENGKAPAN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">1</td>
                <td>{{ $inventory->nama_barang ?: ($inventory->jenis_barang ?: '-') }}</td>
                <td>
                    Kode: {{ $inventory->kode_barang ?: '-' }}<br>
                    Merk/Tipe: {{ $inventory->merk_tipe ?: '-' }}<br>
                    SN: {{ $inventory->serial_number ?: '-' }}<br>
                    Warna: {{ ($returnTransaction ? $returnTransaction->warna_barang : $originalTransaction->warna_barang) ?: 'Umum' }}<br>
                    Spesifikasi: {{ $inventory->spesifikasi ?: $inventory->desc ?: '-' }}
                </td>
                <td class="center">{{ $inventory->formatStockValue($returnTransaction ? $returnTransaction->jumlah : $originalTransaction->jumlah) }} {{ $inventory->display_uom }}</td>
                <td>{{ $document->kondisi_kembali ?: '-' }}</td>
                <td>{{ $document->kelengkapan ?: '-' }}</td>
            </tr>
        </tbody>
    </table>

    <p class="paragraph"><strong>Catatan:</strong> {{ $document->catatan ?: '-' }}</p>
    <ol>
        <li>PIHAK PERTAMA menyatakan telah mengembalikan aset perusahaan sebagaimana rincian di atas.</li>
        <li>PIHAK KEDUA menyatakan telah menerima aset dan mencatat kondisi serta kelengkapan saat pengembalian.</li>
        <li>Apabila terdapat kerusakan atau ketidaklengkapan, tindak lanjut akan diproses sesuai kebijakan internal perusahaan.</li>
    </ol>

    <p class="paragraph">Jakarta, {{ $tanggalTeks }}</p>

    <table class="signatures">
        <tr>
            @foreach ($signatureRows as $signature)
                <td>
                    <div class="signature-heading">{{ $signature['heading'] }}</div>
                    <div class="signature-subtitle">{{ $signature['subtitle'] }}</div>
                    <div class="signature-box">
                        @if ($signature['signed_at'] && $signature['image'])
                            <div class="signature-media"><img class="signature-image" src="{{ $signature['image'] }}" alt="Tanda tangan"></div>
                            <div><span class="verified-stamp">Terverifikasi elektronik</span></div>
                            <div class="signature-time">{{ \Carbon\Carbon::parse($signature['signed_at'])->format('d/m/Y H:i') }}</div>
                        @elseif ($signature['signed_at'])
                            <div class="signature-digital">
                                Ditandatangani elektronik<br>
                                oleh {{ $signature['name'] }}<br>
                                {{ \Carbon\Carbon::parse($signature['signed_at'])->format('d/m/Y H:i') }}
                            </div>
                            <div><span class="verified-stamp">Terverifikasi elektronik</span></div>
                        @else
                            <div class="signature-media"><div class="signature-placeholder">Menunggu tanda tangan</div></div>
                        @endif
                    </div>
                    <div class="signature-name">{{ $signature['name'] }}</div>
                    <div class="signature-position">{{ $signature['position'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>
</body>
</html>
