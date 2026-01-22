<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $lokasi->nama_lokasi }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 90vh;
        }

        .kartu {
            width: 250px;
            background-color: #f5f5f5;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .barcode {
            width: 100%;
            padding: 15px;
            background-color: #fff;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .barcode img {
            width: 150px;
            height: 150px;
        }

        .member-name {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0 5px;
            color: #333;
        }

        .location-type {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    @php
        // Generate QR code dengan ukuran lebih kecil untuk menghindari timeout
        $result = Endroid\QrCode\Builder\Builder::create()
            ->writer(new Endroid\QrCode\Writer\PngWriter())
            ->writerOptions([])
            ->data($lokasi->nama_lokasi)
            ->encoding(new Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(new Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow())
            ->size(150)
            ->margin(5)
            ->roundBlockSizeMode(new Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin())
            ->validateResult(false)
            ->build();

        // Gunakan data URI langsung, tidak perlu save ke file
        $dataUri = $result->getDataUri();
    @endphp

    <div class="container">
        <div class="kartu">
            <div class="barcode">
                <img src="{{ $dataUri }}" alt="QR Code">
            </div>
            <h2 class="member-name">{{ $lokasi->nama_lokasi }}</h2>
            <p class="location-type">{{ $lokasi->keterangan ?? 'Lokasi Kantor' }}</p>
        </div>
    </div>
</body>
</html>
