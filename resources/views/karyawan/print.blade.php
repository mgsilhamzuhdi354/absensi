<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $user->name }}</title>
    <style>
        @page {
            margin: 16px;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #172033;
        }

        .card-wrap {
            text-align: center;
        }

        .id-card {
            width: 285px;
            min-height: 430px;
            border: 1px solid #d7dde8;
            border-radius: 12px;
            padding: 16px;
            margin: 0 auto 14px;
            background: #ffffff;
        }

        .company-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .company-name {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 12px;
        }

        .photo {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e5e7eb;
            margin-bottom: 8px;
        }

        .name {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .meta {
            font-size: 12px;
            color: #64748b;
            margin: 0 0 12px;
        }

        .qr {
            width: 185px;
            height: 185px;
            object-fit: contain;
            margin: 4px auto 8px;
        }

        .mode {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            color: #0f766e;
            background: #ccfbf1;
        }

        .hint {
            font-size: 10px;
            color: #64748b;
            margin-top: 8px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@php
    $cards = [];
    if ($mode === 'both' || $mode === \App\Services\EmployeeQrService::MODE_PROFILE) {
        $cards[] = [
            'label' => 'QR Profil Dynamic',
            'hint' => 'Scan untuk membuka profil dan simpan kontak',
            'image' => $profileQrDataUri,
        ];
    }
    if ($mode === 'both' || $mode === \App\Services\EmployeeQrService::MODE_VCARD) {
        $cards[] = [
            'label' => 'QR Simpan Kontak',
            'hint' => 'Scan untuk menyimpan kontak langsung',
            'image' => $vcardQrDataUri,
        ];
    }
@endphp

<div class="card-wrap">
    @foreach($cards as $index => $card)
        <div class="id-card">
            @if($logoDataUri)
                <img class="company-logo" src="{{ $logoDataUri }}" alt="Logo">
            @endif
            <div class="company-name">{{ $settings->name ?? config('app.name') }}</div>

            @if($photoDataUri)
                <img class="photo" src="{{ $photoDataUri }}" alt="{{ $user->name }}">
            @endif

            <h1 class="name">{{ $user->name }}</h1>
            <p class="meta">{{ $user->employee_id ?? $user->username ?? '-' }}</p>
            <p class="meta">{{ $user->Jabatan->nama_jabatan ?? '-' }}</p>

            @if($card['image'])
                <img class="qr" src="{{ $card['image'] }}" alt="{{ $card['label'] }}">
            @endif
            <div class="mode">{{ $card['label'] }}</div>
            <div class="hint">{{ $card['hint'] }}</div>
        </div>

        @if($mode === 'both' && $index < count($cards) - 1)
            <div class="page-break"></div>
        @endif
    @endforeach
</div>
</body>
</html>
