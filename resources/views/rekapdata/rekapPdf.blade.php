
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Rekap Pdf</title>
    <style>
        body {
          font-family: Arial, sans-serif;
        }
        .container {
          max-width: 800px;
          margin: 0 auto;
        }
        .header {
          font-size: 20px;
          font-weight: bold;
          margin-bottom: 20px;
        }
      </style>
</head>
<body>
    @php
        $settings = App\Models\settings::first();
        $logo_path = storage_path('app/public/' . $settings->logo);
        if (file_exists($logo_path)) {
            $logo_mime = mime_content_type($logo_path);
            $logo_data = base64_encode(file_get_contents($logo_path));
        } else {
            $logo_mime = null;
            $logo_data = null;
        }
    @endphp
    <div class="container">
        @if($logo_data)
            <img src="data:{{ $logo_mime }};base64,{{ $logo_data }}" style="width: 80px; float:right">
        @endif
        <h3 style="text-transform: uppercase;">{{ $settings->name }}</h3>
        <span style="font-size: 10px; color:rgb(112, 112, 112);">{{ $settings->alamat }}</span>
        <br>
        <span style="font-size: 10px; color:rgb(112, 112, 112);">{{ $settings->email }} - {{ $settings->phone }}</span>
        <hr>
        <center>
        <div class="header">Export Rekap</div>
        </center>


        <table style="border-collapse: collapse; width: 100%; font-size: 8px;">
            <thead>
                <tr>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Nama Pegawai</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Cuti</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Izin Masuk</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Izin Telat</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Izin Pulang Cepat</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Hadir</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Dinas Luar</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Alfa</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Libur</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Total Telat</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Total Pulang Cepat</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Total Lembur</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; text-transform: uppercase;">Persentase Kehadiran</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $d)
                    @php
                        $summary = $rekap_summaries[$d->id] ?? [];
                    @endphp
                    <tr>
                        <td style="border: 1px solid black; padding: 8px;">{{ $d->name }}</td>
                        <td style="border: 1px solid black; padding: 8px;">{{ $summary['cuti'] ?? 0 }} x</td>
                        <td style="border: 1px solid black; padding: 8px;">{{ $summary['izin_masuk'] ?? 0 }} x</td>
                        <td style="border: 1px solid black; padding: 8px;">{{ $summary['izin_telat'] ?? 0 }} x</td>
                        <td style="border: 1px solid black; padding: 8px;">{{ $summary['izin_pulang_cepat'] ?? 0 }} x</td>
                        <td style="border: 1px solid black; padding: 8px;">{{ $summary['total_hadir'] ?? 0 }} x</td>
                        <td style="border: 1px solid black; padding: 8px;">{{ $summary['total_dinas_luar'] ?? 0 }} x</td>
                        <td style="border: 1px solid black; padding: 8px;">{{ $summary['total_alfa'] ?? 0 }} x</td>
                        <td style="border: 1px solid black; padding: 8px;">{{ $summary['libur'] ?? 0 }} x</td>
                        <td style="border: 1px solid black; padding: 8px;">
                            <p>{{ $summary['telat_duration']['label'] ?? '0 Jam 0 Menit' }}</p>
                            <p>{{ ($summary['jumlah_telat'] ?? 0) . " x" }}</p>
                        </td>
                        <td style="border: 1px solid black; padding: 8px;">
                            <p>{{ $summary['pulang_cepat_duration']['label'] ?? '0 Jam 0 Menit' }}</p>
                            <p>{{ ($summary['jumlah_pulang_cepat'] ?? 0) . " x" }}</p>
                        </td>
                        <td style="border: 1px solid black; padding: 8px;">{{ $summary['lembur_duration']['label'] ?? '0 Jam 0 Menit' }}</td>
                        <td style="border: 1px solid black; padding: 8px;">{{ number_format($summary['persentase_kehadiran'] ?? 0, 1) }} %</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</body>
</html>
