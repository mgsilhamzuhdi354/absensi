<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Slip Gaji - {{ $data->user->name ?? 'Karyawan' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            position: relative;
            margin-bottom: 20px;
        }

        .confidential {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #D4A439;
            font-style: italic;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }

        .logo-section img {
            width: 70px;
            height: auto;
        }

        .company-section {
            display: table-cell;
            vertical-align: middle;
            padding-left: 15px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a4a6e;
            margin-bottom: 3px;
        }

        .slip-title {
            font-size: 14px;
            font-weight: bold;
            color: #D4A439;
        }

        /* Employee Info */
        .employee-info {
            background: #f9f9f9;
            border: 1px solid #ddd;
            margin-bottom: 15px;
            padding: 10px 15px;
        }

        .employee-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .employee-info td {
            padding: 4px 0;
        }

        .employee-info .label {
            width: 150px;
            font-weight: bold;
            color: #1a4a6e;
            text-transform: uppercase;
            font-size: 10px;
        }

        .employee-info .colon {
            width: 10px;
        }

        .employee-info .value {
            font-weight: 500;
        }

        /* Main Content Table */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .main-table th {
            background: #D4A439;
            color: #000;
            padding: 8px 10px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #D4A439;
        }

        .main-table>tbody>tr>td {
            padding: 0;
            vertical-align: top;
            border: 1px solid #D4A439;
            width: 50%;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
        }

        .item-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }

        .item-label {
            width: 55%;
        }

        .item-colon {
            width: 5%;
            text-align: center;
        }

        .item-value {
            width: 40%;
            text-align: right;
        }

        .total-row {
            background: #D4A439;
        }

        .total-row td {
            color: #000;
            font-weight: bold;
            padding: 6px 8px !important;
            border: none !important;
        }

        /* Bottom Section */
        .bottom-section {
            margin-bottom: 15px;
        }

        .penerima-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .penerima-content {
            display: table;
            width: 100%;
        }

        .penerima-left {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        .penerima-right {
            display: table-cell;
            width: 60%;
            text-align: right;
            vertical-align: top;
        }

        .penerima-table {
            font-size: 10px;
        }

        .penerima-table td {
            padding: 2px 0;
        }

        .gaji-label {
            font-size: 10px;
            color: #D4A439;
            text-align: right;
        }

        .gaji-amount {
            font-size: 22px;
            font-weight: bold;
            color: #c0392b;
            text-align: right;
        }

        .sisa-cuti {
            font-size: 10px;
            color: #666;
            margin-top: 8px;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 9px;
            color: #888;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    @php
        // Calculate totals
        $gaji_pokok = $data->user->gaji_pokok ?? 0;
        $makan_transport = $data->user->makan_transport ?? 0;
        $kehadiran = $data->user->kehadiran ?? 0;
        $lembur = ($data->user->lembur ?? 0) * ($data->total_jam_lembur ?? 0);
        $tunjangan_makan = $data->user->bonus_pribadi ?? 0;
        $tunjangan_transport = $data->user->bonus_team ?? 0;
        $tunjangan_komunikasi = $data->user->bonus_jackpot ?? 0;
        $thr = $data->user->thr ?? 0;
        $reimbursement = $data->reimbursement ?? 0;

        $total_penghasilan = $gaji_pokok + $makan_transport + $kehadiran + $lembur +
            $tunjangan_makan + $tunjangan_transport + $tunjangan_komunikasi + $thr + $reimbursement;

        // Deductions
        $keterlambatan = ($data->user->terlambat ?? 0) * $data->total_telat;
        $mangkir = ($data->user->mangkir ?? 0) * $data->total_mangkir;
        $izin = ($data->user->izin ?? 0) * $data->total_izin;
        $kasbon = $data->kasbon ?? 0;
        $potongan_lain = $data->potongan_lain ?? 0;

        // BPJS JHT 2% employee
        $bpjs_jht_karyawan = $gaji_pokok * 0.02;

        // BPJS Kesehatan 1% employee
        $bpjs_kes_karyawan = $gaji_pokok * 0.01;

        $total_potongan = $keterlambatan + $mangkir + $izin + $kasbon + $potongan_lain + $bpjs_jht_karyawan + $bpjs_kes_karyawan;

        $gaji_dibayarkan = $total_penghasilan - $total_potongan;

        // Format period using separate bulan and tahun fields
        $bulan = $data->bulan ?? 1;
        $tahun = $data->tahun ?? date('Y');
        try {
            $start_day = \Carbon\Carbon::parse($data->tanggal_mulai)->format('d');
            $end_day = \Carbon\Carbon::parse($data->tanggal_akhir)->format('d');
        } catch (\Exception $e) {
            $start_day = '01';
            $end_day = '31';
        }

        $nama_bulan = [
            1 => 'JANUARI',
            2 => 'FEBRUARI',
            3 => 'MARET',
            4 => 'APRIL',
            5 => 'MEI',
            6 => 'JUNI',
            7 => 'JULI',
            8 => 'AGUSTUS',
            9 => 'SEPTEMBER',
            10 => 'OKTOBER',
            11 => 'NOVEMBER',
            12 => 'DESEMBER'
        ];

        $periode = $start_day . '-' . $end_day . ' ' . ($nama_bulan[(int) $bulan] ?? 'JANUARI') . ' ' . $tahun;
    @endphp

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="confidential">Pribadi dan Rahasia</div>
            <div class="header-content">
                <div class="logo-section">
                    <img src="{{ public_path('images/logo.png') }}" alt="Logo">
                </div>
                <div class="company-section">
                    <div class="company-name">PT. INDOCEAN CREW SERVICE</div>
                    <div class="slip-title">SLIP GAJI KARYAWAN</div>
                </div>
            </div>
        </div>

        <!-- Employee Information -->
        <div class="employee-info">
            <table>
                <tr>
                    <td class="label">PERIODE</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $periode }}</td>
                </tr>
                <tr>
                    <td class="label">NAMA KARYAWAN</td>
                    <td class="colon">:</td>
                    <td class="value">{{ strtoupper($data->user->name ?? '-') }}</td>
                </tr>
                <tr>
                    <td class="label">NIK</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $data->user->ktp ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">JABATAN</td>
                    <td class="colon">:</td>
                    <td class="value">{{ strtoupper($data->user->Jabatan->nama_jabatan ?? '-') }}</td>
                </tr>
            </table>
        </div>

        <!-- Main Table -->
        <table class="main-table">
            <thead>
                <tr>
                    <th>Penghasilan</th>
                    <th>Potongan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <!-- Penghasilan Column -->
                    <td>
                        <table class="item-table">
                            <tr>
                                <td class="item-label">Gaji Pokok</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($gaji_pokok, 0, ',', '.') }}</td>
                            </tr>

                            <tr>
                                <td class="item-label">Bonus 100% Kehadiran</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($kehadiran, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">Lembur ({{ $data->total_jam_lembur ?? 0 }} jam)</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($lembur, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">Tunjangan Makan</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($tunjangan_makan, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">Tunjangan Transport</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($tunjangan_transport, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">Tunjangan Komunikasi</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($tunjangan_komunikasi, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">THR/Bonus/Insentif</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($thr, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">Reimbursement</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($reimbursement, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="total-row">
                                <td class="item-label">Total Penghasilan</td>
                                <td class="item-colon"></td>
                                <td class="item-value">Rp {{ number_format($total_penghasilan, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>

                    <!-- Potongan Column -->
                    <td>
                        <table class="item-table">
                            <tr>
                                <td class="item-label">Keterlambatan ({{ $data->total_telat ?? 0 }}x)</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($keterlambatan, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">Mangkir ({{ $data->total_mangkir ?? 0 }} hari)</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($mangkir, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">Izin ({{ $data->total_izin ?? 0 }} hari)</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($izin, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">Kasbon</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($kasbon, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">Loss/Potongan Lain</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($potongan_lain, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">BPJS JHT 2% Karyawan</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($bpjs_jht_karyawan, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">BPJS Kesehatan 1% Karyawan</td>
                                <td class="item-colon">:</td>
                                <td class="item-value">Rp {{ number_format($bpjs_kes_karyawan, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="item-label">&nbsp;</td>
                                <td class="item-colon"></td>
                                <td class="item-value">&nbsp;</td>
                            </tr>
                            <tr class="total-row">
                                <td class="item-label">Total Potongan</td>
                                <td class="item-colon"></td>
                                <td class="item-value">Rp {{ number_format($total_potongan, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Bottom Section -->
        <div class="bottom-section">
            <div class="penerima-title">Penerima</div>
            <div class="penerima-content">
                <div class="penerima-left">
                    <table class="penerima-table">
                        <tr>
                            <td style="width: 80px;">Bank</td>
                            <td style="width: 10px;">:</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>Atas Nama</td>
                            <td>:</td>
                            <td>{{ $data->user->nama_rekening ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>No. Rekening</td>
                            <td>:</td>
                            <td>{{ $data->user->rekening ?? '-' }}</td>
                        </tr>
                    </table>
                    <div class="sisa-cuti">Sisa Cuti: {{ $data->user->izin_cuti ?? 0 }} Kali / Tahun</div>
                </div>
                <div class="penerima-right">
                    <div class="gaji-label">Gaji Dibayarkan</div>
                    <div class="gaji-amount">Rp {{ number_format($gaji_dibayarkan, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <!-- BPJS Ketenagakerjaan Section -->
        @php
            $bpjs_jkk = $gaji_pokok * 0.0024;
            $bpjs_jkm = $gaji_pokok * 0.003;
            $bpjs_jht_perusahaan = $gaji_pokok * 0.037;
        @endphp
        <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 10px 15px; margin-bottom: 15px;">
            <div style="font-weight: bold; font-size: 11px; color: #1a4a6e; margin-bottom: 8px;">
                Kontribusi BPJS Ketenagakerjaan (Ditanggung Perusahaan - Tidak Dipotong dari Gaji)
            </div>
            <table style="width: 100%; font-size: 10px;">
                <tr>
                    <td style="width: 15%;">BPJS JKK 0,24%</td>
                    <td style="width: 5%;">:</td>
                    <td style="width: 30%;">Rp {{ number_format($bpjs_jkk, 0, ',', '.') }}</td>
                    <td style="width: 15%;">BPJS JHT 3,7%</td>
                    <td style="width: 5%;">:</td>
                    <td style="width: 30%;">Rp {{ number_format($bpjs_jht_perusahaan, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>BPJS JKM 0,3%</td>
                    <td>:</td>
                    <td>Rp {{ number_format($bpjs_jkm, 0, ',', '.') }}</td>
                    <td colspan="3"></td>
                </tr>
            </table>
            <div style="font-size: 9px; color: #888; margin-top: 5px; font-style: italic;">
                *Kontribusi ini dibayarkan oleh perusahaan
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Auto generated by system {{ date('Y') }} PT. Indocean Crew Service
        </div>
    </div>
</body>

</html>