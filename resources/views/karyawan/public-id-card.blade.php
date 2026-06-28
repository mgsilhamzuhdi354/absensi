<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ in_array('name', $visibleFields, true) ? $user->name : ($settings->name ?? 'ID Card Karyawan') }}</title>
    <link rel="stylesheet" href="{{ url('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #eef2f7;
            color: #172033;
        }

        .page {
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
            padding: 20px;
        }

        .profile {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        }

        .brand {
            padding: 18px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .brand strong {
            display: block;
            font-size: 15px;
            text-transform: uppercase;
        }

        .brand span {
            color: #64748b;
            font-size: 12px;
        }

        .hero {
            text-align: center;
            padding: 24px 20px 18px;
        }

        .photo {
            width: 112px;
            height: 112px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #e5e7eb;
            margin-bottom: 14px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 25px;
            line-height: 1.2;
        }

        .position {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .rows {
            padding: 0 20px 20px;
        }

        .row-item {
            display: flex;
            gap: 12px;
            padding: 13px 0;
            border-top: 1px solid #edf0f5;
            align-items: flex-start;
        }

        .row-item i {
            width: 20px;
            color: #2563eb;
            margin-top: 3px;
            text-align: center;
        }

        .row-label {
            display: block;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 3px;
        }

        .row-value,
        .row-value a {
            color: #172033;
            font-size: 15px;
            text-decoration: none;
            word-break: break-word;
        }

        .actions {
            display: grid;
            gap: 10px;
            padding: 0 20px 22px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 10px;
            padding: 13px 14px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-outline {
            background: #fff;
            color: #2563eb;
            border-color: #bfdbfe;
        }

        .empty {
            padding: 0 20px 22px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="profile">
            <div class="brand">
                <img src="{{ $logoUrl }}" alt="Logo">
                <div>
                    <strong>{{ $settings->name ?? config('app.name') }}</strong>
                    <span>{{ $settings->email ?? $settings->phone ?? '' }}</span>
                </div>
            </div>

            <div class="hero">
                @if(in_array('foto', $visibleFields, true))
                    <img class="photo" src="{{ $photoUrl }}" alt="{{ $user->name }}">
                @endif

                @if(in_array('name', $visibleFields, true))
                    <h1>{{ $user->name }}</h1>
                @else
                    <h1>ID Card Karyawan</h1>
                @endif

                @if(in_array('jabatan', $visibleFields, true) && optional($user->Jabatan)->nama_jabatan)
                    <p class="position">{{ $user->Jabatan->nama_jabatan }}</p>
                @endif
            </div>

            @if(count($profileRows) > 0)
                <div class="rows">
                    @foreach($profileRows as $row)
                        <div class="row-item">
                            <i class="fa {{ $row['icon'] }}"></i>
                            <div>
                                <span class="row-label">{{ $row['label'] }}</span>
                                <span class="row-value">
                                    @if(!empty($row['href']))
                                        <a href="{{ $row['href'] }}">{{ $row['value'] }}</a>
                                    @else
                                        {{ $row['value'] }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty">Informasi publik belum dipilih.</div>
            @endif

            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/e/'.$user->employee_qr_token.'/v') }}">
                    <i class="fa fa-address-card"></i> Simpan Kontak
                </a>
                @if(in_array('telepon', $visibleFields, true) && $user->telepon)
                    <a class="btn btn-outline" href="tel:{{ $user->telepon }}">
                        <i class="fa fa-phone"></i> Hubungi
                    </a>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
