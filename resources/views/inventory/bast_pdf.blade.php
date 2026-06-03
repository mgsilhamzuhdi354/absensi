@php
    $tanggal = \Carbon\Carbon::parse($document->tanggal_surat);
    $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $tanggalTeks = $tanggal->format('d') . ' ' . $bulan[(int) $tanggal->format('n')] . ' ' . $tanggal->format('Y');
    $hariTeks = $hari[$tanggal->format('l')] ?? $tanggal->format('l');
    $divisiInventory = $inventory->jabatan->nama_jabatan ?? null;
    $pihakKedua = $document->nama_penerima ?: ($transaction->penerima_barang ?: '-');
    $jabatanPihakKedua = $document->jabatan_penerima ?: ($transaction->jabatan_penerima ?: '-');
    $deptPihakKedua = $transaction->departemen_penerima ?: $jabatanPihakKedua;
    $pihakPertama = optional($document->firstParty)->name ?: ($document->nama_penyerah ?: ($transaction->processedBy->name ?? '-'));
    $jabatanPihakPertama = optional(optional($document->firstParty)->Jabatan)->nama_jabatan ?: ($document->jabatan_penyerah ?: ($divisiInventory ?: 'IT Engineer'));
    $deptPihakPertama = $jabatanPihakPertama ?: ($divisiInventory ?: '-');
    $namaMengetahui = optional($document->knownBy)->name ?: ($document->nama_mengetahui ?: '(____________________)');
    $jabatanMengetahui = optional(optional($document->knownBy)->Jabatan)->nama_jabatan ?: 'HRD / Manager';
    $signatureSrc = function ($path) {
        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($path));
    };
    $signatureRows = [
        'receiver' => [
            'heading' => 'PIHAK KEDUA',
            'subtitle' => 'Yang Menerima',
            'name' => $document->receiver_signature_name ?: $pihakKedua,
            'position' => $jabatanPihakKedua,
            'signed_at' => $document->signed_at,
            'image' => $signatureSrc($document->receiver_signature_image),
        ],
        'known' => [
            'heading' => 'MENGETAHUI',
            'subtitle' => 'Perwakilan Perusahaan',
            'name' => $document->known_signature_name ?: $namaMengetahui,
            'position' => $jabatanMengetahui,
            'signed_at' => $document->known_signed_at,
            'image' => $signatureSrc($document->known_signature_image),
        ],
        'first_party' => [
            'heading' => 'PIHAK PERTAMA',
            'subtitle' => 'Yang Menyerahkan',
            'name' => $document->first_party_signature_name ?: $pihakPertama,
            'position' => $jabatanPihakPertama,
            'signed_at' => $document->first_party_signed_at,
            'image' => $signatureSrc($document->first_party_signature_image),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST {{ $document->nomor_surat }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.45;
            margin: 18px 24px;
        }
        .center {
            text-align: center;
        }
        .company-title {
            font-size: 27px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin: 0;
        }
        .company-subtitle {
            font-size: 10px;
            color: #4b5563;
            margin: 4px 0 10px;
        }
        .hr-line {
            border-top: 2px solid #1f3c68;
            margin: 6px 0 12px;
        }
        .doc-title {
            font-size: 22px;
            font-weight: 700;
            color: #1f3c68;
            margin: 0 0 4px;
        }
        .doc-number {
            font-size: 12px;
            margin: 0 0 14px;
        }
        .paragraph {
            margin: 0 0 8px;
            text-align: justify;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .party-table {
            margin: 8px 0 12px;
        }
        .party-table td {
            width: 50%;
            padding: 0 8px 0 0;
            vertical-align: top;
        }
        .party-card {
            border: 1px solid #b6c2d3;
        }
        .party-head {
            background: #e7eff8;
            color: #1f4b86;
            font-weight: 700;
            border-bottom: 1px solid #b6c2d3;
            padding: 6px 8px;
            font-size: 10px;
        }
        .party-body {
            width: 100%;
        }
        .party-body td {
            border: none;
            padding: 3px 8px;
            font-size: 10px;
        }
        .party-body td:first-child {
            width: 76px;
            color: #374151;
        }
        .items {
            margin-top: 8px;
        }
        .items th,
        .items td {
            border: 1px solid #8aa0bc;
            padding: 6px;
            vertical-align: top;
        }
        .items th {
            background: #1f3c68;
            color: #fff;
            font-weight: 700;
            font-size: 10px;
            text-align: center;
        }
        .signatures {
            margin-top: 14px;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 6px;
        }
        .signature-box {
            height: 86px;
            border: 1px solid #d8e1ef;
            margin: 8px 0 7px;
            padding: 5px;
            background: #fbfdff;
        }
        .signature-image {
            max-width: 135px;
            max-height: 47px;
            margin-top: 2px;
        }
        .signature-placeholder {
            color: #6b7280;
            font-size: 9px;
            margin-top: 26px;
        }
        .signature-digital {
            font-size: 9px;
            color: #1f4b86;
            margin-top: 14px;
        }
        .verified-stamp {
            color: #1f4b86;
            font-size: 8.5px;
            font-weight: 700;
            border: 1px solid #9db7da;
            display: inline-block;
            padding: 1px 5px;
            margin-top: 4px;
        }
        .signature-time {
            color: #315f9d;
            font-size: 8.5px;
            margin-top: 2px;
        }
        ol {
            padding-left: 18px;
            margin-top: 6px;
        }
        .muted {
            color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="center">
        <h2 class="company-title">PT INDO OCEANCREW SERVICE</h2>
        <div class="company-subtitle">Email: ios@indooceancrew.co.id | Telp: +62 822-6012-1933 | Jakarta, Indonesia</div>
    </div>
    <div class="hr-line"></div>
    <div class="center">
        <div class="doc-title">BERITA ACARA SERAH TERIMA BARANG</div>
        <div class="doc-number">Nomor: {{ $document->nomor_surat }}</div>
    </div>

    <p class="paragraph">
        Pada hari ini, {{ $hariTeks }} tanggal {{ $tanggalTeks }}, bertempat di kantor PT Indo Ocean Crew Service,
        telah dilakukan penyerahan aset/inventaris perusahaan berupa fasilitas kerja kepada karyawan dengan rincian
        pihak-pihak terkait sebagai berikut:
    </p>

    <table class="party-table">
        <tr>
            <td>
                <div class="party-card">
                    <div class="party-head">PIHAK PERTAMA (YANG MENYERAHKAN)</div>
                    <table class="party-body">
                        <tr>
                            <td>Nama</td>
                            <td>: {{ $pihakPertama }}</td>
                        </tr>
                        <tr>
                            <td>Jabatan</td>
                            <td>: {{ $jabatanPihakPertama }}</td>
                        </tr>
                        <tr>
                            <td>Departemen</td>
                            <td>: {{ $deptPihakPertama }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="party-card">
                    <div class="party-head">PIHAK KEDUA (YANG MENERIMA)</div>
                    <table class="party-body">
                        <tr>
                            <td>Nama</td>
                            <td>: {{ $pihakKedua }}</td>
                        </tr>
                        <tr>
                            <td>Jabatan</td>
                            <td>: {{ $jabatanPihakKedua }}</td>
                        </tr>
                        <tr>
                            <td>Departemen</td>
                            <td>: {{ $deptPihakKedua }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <p class="paragraph">
        PIHAK PERTAMA menyerahkan barang inventaris perusahaan kepada PIHAK KEDUA, dan PIHAK KEDUA menyatakan
        telah menerima barang tersebut dalam kondisi baik, lengkap, dan berfungsi normal dengan rincian spesifikasi
        sebagai berikut:
    </p>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 45px;">NO</th>
                <th>JENIS BARANG</th>
                <th>SPESIFIKASI & SERIAL NUMBER (SN)</th>
                <th style="width: 85px;">JUMLAH</th>
                <th style="width: 120px;">KONDISI</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">1</td>
                <td>{{ $inventory->nama_barang ?: ($inventory->jenis_barang ?: '-') }}</td>
                <td>
                    Merk/Tipe: {{ $inventory->merk_tipe ?: '-' }}<br>
                    SN: {{ $inventory->serial_number ?: '-' }}<br>
                    Spesifikasi: {{ $inventory->spesifikasi ?: $inventory->desc ?: '-' }}
                </td>
                <td class="center">{{ $inventory->formatStockValue($transaction->jumlah) }} {{ $inventory->display_uom }}</td>
                <td>{{ $transaction->kondisi_barang ?: $inventory->kondisi ?: '-' }}</td>
            </tr>
        </tbody>
    </table>

    <p class="paragraph"><strong>Ketentuan & Tanggung Jawab Penggunaan:</strong></p>
    <ol>
        <li>PIHAK KEDUA bertanggung jawab penuh atas keutuhan, kebersihan, perawatan, dan keamanan unit selama masa penggunaan.</li>
        <li>Fasilitas ini diberikan semata-mata untuk mendukung kelancaran operasional kerja dan tugas kedinasan di PT Indo Ocean Crew Service.</li>
        <li>Segala bentuk kerusakan akibat kelalaian seperti terjatuh, terkena air, atau kehilangan unit menjadi tanggung jawab PIHAK KEDUA dan akan diproses sesuai peraturan internal perusahaan.</li>
        <li>Apabila PIHAK KEDUA mengakhiri masa kontrak/hubungan kerja, maka wajib menyerahkan kembali aset ini kepada departemen {{ $deptPihakPertama }} dalam kondisi baik dan lengkap.</li>
    </ol>

    <p class="paragraph">Jakarta, {{ $tanggalTeks }}</p>

    <table class="signatures">
        <tr>
            @foreach ($signatureRows as $signature)
                <td>
                    <strong>{{ $signature['heading'] }}</strong><br>
                    {{ $signature['subtitle'] }}
                    <div class="signature-box">
                        @if ($signature['signed_at'] && $signature['image'])
                            <img class="signature-image" src="{{ $signature['image'] }}" alt="Tanda tangan">
                            <div class="verified-stamp">Terverifikasi elektronik</div>
                            <div class="signature-time">{{ \Carbon\Carbon::parse($signature['signed_at'])->format('d/m/Y H:i') }}</div>
                        @elseif ($signature['signed_at'])
                            <div class="signature-digital">
                                Ditandatangani elektronik<br>
                                oleh {{ $signature['name'] }}<br>
                                {{ \Carbon\Carbon::parse($signature['signed_at'])->format('d/m/Y H:i') }}
                            </div>
                            <div class="verified-stamp">Terverifikasi elektronik</div>
                        @else
                            <div class="signature-placeholder">Menunggu tanda tangan</div>
                        @endif
                    </div>
                    <strong>{{ $signature['name'] }}</strong><br>
                    {{ $signature['position'] }}
                </td>
            @endforeach
        </tr>
    </table>
</body>
</html>
