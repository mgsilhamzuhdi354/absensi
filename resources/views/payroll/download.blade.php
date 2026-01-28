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
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #4a6fa5;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .slip-title {
            font-size: 14px;
            font-weight: bold;
            color: #4a6fa5;
            margin-top: 10px;
        }

        .period {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Employee Info */
        .employee-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .employee-info table {
            width: 100%;
        }

        .employee-info td {
            padding: 3px 5px;
        }

        .employee-info .label {
            width: 130px;
            font-weight: bold;
            color: #555;
        }

        /* Main Content */
        .content {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 5px;
        }

        .section {
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #fff;
            padding: 8px 10px;
            margin-bottom: 0;
        }

        .section-title.pendapatan {
            background: #d4af37;
            /* Gold color matching reference */
            color: #000;
        }

        .section-title.potongan {
            background: #d4af37;
            /* Gold color matching reference */
            color: #000;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #eee;
        }

        .data-table td:last-child {
            text-align: right;
            font-weight: 500;
        }

        .data-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .subtotal-row {
            background: #f0f0f0 !important;
            font-weight: bold;
        }

        .subtotal-row td {
            border-top: 2px solid #ddd;
        }

        /* Grand Total */
        .grand-total {
            background: linear-gradient(135deg, #4a6fa5, #2c3e50);
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }

        .grand-total table {
            width: 100%;
        }

        .grand-total td {
            padding: 5px 0;
        }

        .grand-total .label {
            font-size: 14px;
            font-weight: bold;
        }

        .grand-total .amount {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
        }

        /* Footer */
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        .signature-area {
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 10px;
        }

        .signature-box .title {
            font-size: 10px;
            color: #666;
            margin-bottom: 50px;
        }

        .signature-box .line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
        }

        .note {
            font-size: 9px;
            color: #888;
            text-align: center;
            margin-top: 15px;
            font-style: italic;
        }

        /* Helpers */
        .text-right {
            text-align: right;
        }

        .text-success {
            color: #27ae60;
        }

        .text-danger {
            color: #e74c3c;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">PT. INDO OCEAN CREW</div>
            <div class="slip-title">SLIP GAJI KARYAWAN</div>
            @php
                $bulanNames = [
                    1 => 'Januari',
                    2 => 'Februari',
                    3 => 'Maret',
                    4 => 'April',
                    5 => 'Mei',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'Agustus',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Desember'
                ];
                $bulanName = $bulanNames[$data->bulan] ?? $data->bulan;
            @endphp
            <div class="period">Periode: {{ $bulanName }} {{ $data->tahun }}</div>
        </div>

        <!-- Employee Info -->
        <div class="employee-info">
            <table>
                <tr>
                    <td class="label">Nama Karyawan</td>
                    <td>: {{ $data->user->name ?? '-' }}</td>
                    <td class="label">No. Slip</td>
                    <td>: {{ $data->no_gaji ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Jabatan</td>
                    <td>: {{ $data->user->Jabatan->nama_jabatan ?? '-' }}</td>
                    <td class="label">Periode Kerja</td>
                    <td>: {{ $data->tanggal_mulai ?? '-' }} s/d {{ $data->tanggal_akhir ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Kehadiran</td>
                    <td>: {{ $data->persentase_kehadiran ?? 0 }}%</td>
                    <td class="label">Jumlah Kehadiran</td>
                    <td>: {{ $data->jumlah_kehadiran ?? 0 }} Hari</td>
                </tr>
            </table>
        </div>

        <!-- Content: Pendapatan & Potongan -->
        <div class="content">
            <!-- Pendapatan -->
            <div class="column">
                <div class="section">
                    <div class="section-title pendapatan">PENDAPATAN</div>
                    <table class="data-table">
                        <tr>
                            <td>Gaji Pokok</td>
                            <td>Rp {{ number_format($data->gaji_pokok ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Uang Transport</td>
                            <td>Rp {{ number_format($data->uang_transport ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Reimbursement</td>
                            <td>Rp {{ number_format($data->total_reimbursement ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Lembur ({{ $data->jumlah_lembur ?? 0 }} jam)</td>
                            <td>Rp {{ number_format($data->total_lembur ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Makan</td>
                            <td>Rp {{ number_format($data->bonus_pribadi ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Transport</td>
                            <td>Rp {{ number_format($data->bonus_team ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Komunikasi</td>
                            <td>Rp {{ number_format($data->bonus_jackpot ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Insentif Kehadiran 100%</td>
                            <td>Rp {{ number_format($data->total_kehadiran ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Thr/Bonus/Insentif ({{ $data->jumlah_thr ?? 0 }}x)</td>
                            <td>Rp {{ number_format($data->total_thr ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="subtotal-row">
                            <td>Total Pendapatan</td>
                            <td class="text-success">Rp {{ number_format($data->total_penjumlahan ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Potongan -->
            <div class="column">
                <div class="section">
                    <div class="section-title potongan">POTONGAN</div>
                    <table class="data-table">
                        <tr>
                            <td>Mangkir ({{ $data->jumlah_mangkir ?? 0 }}x)</td>
                            <td>Rp {{ number_format($data->total_mangkir ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Izin Masuk ({{ $data->jumlah_izin ?? 0 }}x)</td>
                            <td>Rp {{ number_format($data->total_izin ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Terlambat ({{ $data->jumlah_terlambat ?? 0 }}x)</td>
                            <td>Rp {{ number_format($data->total_terlambat ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Pembayaran Kasbon</td>
                            <td>Rp {{ number_format($data->bayar_kasbon ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Loss</td>
                            <td>Rp {{ number_format($data->loss ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>BPJS Ketenagakerjaan (2%)</td>
                            <td>Rp {{ number_format($data->bpjs_tk_karyawan ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="subtotal-row">
                            <td>Total Potongan</td>
                            <td class="text-danger">Rp {{ number_format($data->total_pengurangan ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>

                    <!-- Saldo Kasbon Info -->
                    <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;">
                        <table style="width: 100%;">
                            <tr>
                                <td style="font-size: 10px; color: #856404;">Saldo Kasbon</td>
                                <td style="text-align: right; font-weight: bold; color: #856404;">Rp
                                    {{ number_format($data->saldo_kasbon ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- BPJS Info -->
                <div style="margin-top: 10px; padding: 5px; font-size: 9px; color: #666; border: 1px dashed #ccc;">
                    <strong>Info Tunjangan BPJS Perusahaan (Tidak dipotong dari gaji):</strong><br>
                    <table style="width: 100%;">
                        <tr>
                            <td>BPJS JKK (0.24%)</td>
                            <td align="right">Rp {{ number_format($data->bpjs_jkk ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>BPJS JKM (0.3%)</td>
                            <td align="right">Rp {{ number_format($data->bpjs_jkm ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>BPJS JHT Perusahaan (3.7%)</td>
                            <td align="right">Rp {{ number_format($data->bpjs_tk_perusahaan ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Grand Total -->
    <div class="grand-total">
        <table>
            <tr>
                <td class="label">GAJI YANG DITERIMA</td>
                <td class="amount">Rp {{ number_format($data->grand_total ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Footer with Signatures -->
    <div class="footer">
        <div class="signature-area">
            <div class="signature-box">
                <div class="title">Disiapkan oleh,</div>
                <div class="line">HRD</div>
            </div>
            <div class="signature-box">
                <div class="title">Disetujui oleh,</div>
                <div class="line">Manager</div>
            </div>
            <div class="signature-box">
                <div class="title">Diterima oleh,</div>
                <div class="line">{{ $data->user->name ?? 'Karyawan' }}</div>
            </div>
        </div>
        <div class="note">
            Dokumen ini dicetak secara elektronik dan sah tanpa tanda tangan basah.<br>
            Dicetak pada: {{ date('d/m/Y H:i') }}
        </div>
    </div>
    </div>
</body>

</html>