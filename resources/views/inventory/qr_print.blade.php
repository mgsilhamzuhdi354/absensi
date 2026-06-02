<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR {{ $inventory->kode_barang }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 24px;
            color: #222;
        }
        .label {
            width: 320px;
            border: 1px solid #d8d8d8;
            border-radius: 8px;
            padding: 18px;
            text-align: center;
        }
        .label img {
            width: 210px;
            height: 210px;
        }
        .code {
            margin-top: 10px;
            font-size: 16px;
            font-weight: 700;
        }
        .name {
            margin-top: 4px;
            font-size: 14px;
        }
        .meta {
            margin-top: 6px;
            font-size: 12px;
            color: #555;
        }
        .actions {
            margin-bottom: 16px;
        }
        @media print {
            .actions {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Cetak</button>
    </div>
    <div class="label">
        <img src="{{ asset('storage/'.$inventory->qr_code_image) }}" alt="QR {{ $inventory->kode_barang }}">
        <div class="code">{{ $inventory->kode_barang }}</div>
        <div class="name">{{ $inventory->nama_barang }}</div>
        <div class="meta">{{ $inventory->lokasi->nama_lokasi ?? '-' }}</div>
    </div>
</body>
</html>
