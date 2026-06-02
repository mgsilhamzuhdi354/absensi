@php
    $tanggal = \Carbon\Carbon::parse($document->tanggal_surat);
    $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $tanggalTeks = $tanggal->format('d') . ' ' . $bulan[(int) $tanggal->format('n')] . ' ' . $tanggal->format('Y');
    $hariTeks = $hari[$tanggal->format('l')] ?? $tanggal->format('l');
    $pihakKedua = $document->nama_penerima ?: ($transaction->penerima_barang ?: '-');
    $jabatanPihakKedua = $document->jabatan_penerima ?: ($transaction->jabatan_penerima ?: '-');
    $deptPihakKedua = $transaction->departemen_penerima ?: '-';
    $pihakPertama = $document->nama_penyerah ?: ($transaction->processedBy->name ?? '-');
    $divisiInventory = $inventory->jabatan->nama_jabatan ?? null;
    $jabatanPihakPertama = $divisiInventory ?: ($document->jabatan_penyerah ?: 'IT Engineer');
    $deptPihakPertama = $divisiInventory ?: $jabatanPihakPertama;
    $signedAt = $document->signed_at ? \Carbon\Carbon::parse($document->signed_at) : null;
    $signedAtText = $signedAt ? $signedAt->format('d/m/Y H:i') : null;
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
        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding-top: 14px;
        }
        .signature-space {
            height: 62px;
        }
        .signature-digital {
            height: 62px;
            font-size: 9px;
            color: #1f4b86;
            padding-top: 10px;
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
            <td>
                <strong>PIHAK KEDUA</strong><br>
                Yang Menerima
                @if ($document->signed_at)
                    <div class="signature-digital">
                        Ditandatangani elektronik<br>
                        oleh {{ $document->receiver_signature_name ?: $pihakKedua }}<br>
                        {{ $signedAtText }}
                    </div>
                @else
                    <div class="signature-space"></div>
                @endif
                <strong>{{ $pihakKedua }}</strong><br>
                {{ $jabatanPihakKedua }}
            </td>
            <td>
                <strong>MENGETAHUI</strong><br>
                Perwakilan Perusahaan
                <div class="signature-space"></div>
                <strong>{{ $document->nama_mengetahui ?: '(____________________)' }}</strong><br>
                HRD / Manager
            </td>
            <td>
                <strong>PIHAK PERTAMA</strong><br>
                Yang Menyerahkan
                <div class="signature-space"></div>
                <strong>{{ $pihakPertama }}</strong><br>
                {{ $jabatanPihakPertama }}
            </td>
        </tr>
    </table>
</body>
</html>
