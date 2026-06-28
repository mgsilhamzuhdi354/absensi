@extends('templates.dashboard')
@section('isi')
    <div class="row">
        <div class="col-md-12 m project-list">
            <div class="card">
                <div class="row">
                    <div class="col-md-6 p-0 d-flex mt-2">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="col-md-6 p-0">
                        <a href="{{ url('/pegawai') }}" class="btn btn-danger btn-sm ms-2">Back</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="employee-qr-layout">
                <div class="employee-summary">
                    <div class="employee-photo">
                        @if($user->foto_karyawan)
                            <img src="{{ asset('storage/'.$user->foto_karyawan) }}" alt="{{ $user->name }}">
                        @else
                            <img src="{{ asset('assets/img/foto_default.jpg') }}" alt="{{ $user->name }}">
                        @endif
                    </div>
                    <div>
                        <h2>{{ $user->name }}</h2>
                        <p>{{ $user->employee_id ?? $user->username ?? '-' }}</p>
                        <p>{{ $user->Jabatan->nama_jabatan ?? '-' }}</p>
                    </div>
                </div>

                @php
                    $iconCatalog = $customInfoIcons ?? \App\Services\EmployeeQrService::customInfoIconCatalog();
                    $oldLabels = old('info_labels');
                    $oldValues = old('info_values');
                    $oldIcons = old('info_icons');
                    $infoRows = [];

                    if (is_array($oldLabels) || is_array($oldValues) || is_array($oldIcons)) {
                        $oldLabels = is_array($oldLabels) ? $oldLabels : [];
                        $oldValues = is_array($oldValues) ? $oldValues : [];
                        $oldIcons = is_array($oldIcons) ? $oldIcons : [];
                        $rowCount = max(count($oldLabels), count($oldValues), count($oldIcons), 1);

                        for ($i = 0; $i < $rowCount; $i++) {
                            $icon = $oldIcons[$i] ?? 'info-circle';
                            $infoRows[] = [
                                'label' => $oldLabels[$i] ?? '',
                                'value' => $oldValues[$i] ?? '',
                                'icon' => array_key_exists($icon, $iconCatalog) ? $icon : 'info-circle',
                            ];
                        }
                    } else {
                        $infoRows = $customInfoItems ?? [];
                    }

                    if (empty($infoRows)) {
                        $infoRows[] = ['label' => '', 'value' => '', 'icon' => 'info-circle'];
                    }
                @endphp

                <div class="scan-info-panel">
                    <div class="scan-info-header">
                        <div>
                            <h5>Informasi yang Tampil Saat Scan</h5>
                            <p>Tambah informasi publik apa saja yang perlu terlihat di HP saat QR profil dibuka.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-qr-info">
                            <i class="fa fa-plus me-1"></i> Tambah Informasi
                        </button>
                    </div>

                    <form action="{{ url('/pegawai/'.$user->id.'/qr/info') }}" method="POST">
                        @csrf
                        <div id="qr-info-rows" class="qr-info-rows">
                            @foreach($infoRows as $row)
                                @php
                                    $selectedIcon = $row['icon'] ?? 'info-circle';
                                    $selectedIcon = array_key_exists($selectedIcon, $iconCatalog) ? $selectedIcon : 'info-circle';
                                @endphp
                                <div class="qr-info-row">
                                    <div class="icon-picker">
                                        <label>Ikon</label>
                                        <button type="button" class="icon-picker-toggle">
                                            <i class="fa fa-{{ $selectedIcon }}"></i>
                                            <span>{{ $iconCatalog[$selectedIcon] }}</span>
                                        </button>
                                        <input type="hidden" name="info_icons[]" value="{{ $selectedIcon }}">
                                        <div class="icon-picker-menu">
                                            <input type="text" class="form-control icon-search" placeholder="Cari ikon...">
                                            <div class="icon-options">
                                                @foreach($iconCatalog as $icon => $label)
                                                    <button type="button" class="icon-option{{ $icon === $selectedIcon ? ' is-selected' : '' }}" data-icon="{{ $icon }}" data-label="{{ $label }}" data-keywords="{{ strtolower($icon.' '.$label) }}">
                                                        <i class="fa fa-{{ $icon }}"></i>
                                                        <span>{{ $label }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label>Judul Info</label>
                                        <input type="text" name="info_labels[]" class="form-control @error('info_labels.*') is-invalid @enderror"
                                            value="{{ $row['label'] ?? '' }}" placeholder="Contoh: Golongan Darah">
                                    </div>
                                    <div>
                                        <label>Isi Info</label>
                                        <input type="text" name="info_values[]" class="form-control @error('info_values.*') is-invalid @enderror"
                                            value="{{ $row['value'] ?? '' }}" placeholder="Contoh: O+">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-qr-info" title="Hapus informasi">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        @if($errors->has('info_labels.*') || $errors->has('info_values.*') || $errors->has('info_icons.*'))
                            <div class="text-danger small mt-2">Judul maksimal 80 karakter, isi maksimal 500 karakter, dan ikon harus dari pilihan yang tersedia.</div>
                        @endif
                        <div class="scan-info-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save me-1"></i> Simpan Informasi
                            </button>
                        </div>
                    </form>
                </div>

                <div class="qr-grid">
                    <div class="qr-panel">
                        <div class="qr-header">
                            <h5>QR Profil Dynamic</h5>
                            <span>URL publik</span>
                        </div>
                        <img class="qr-image" src="{{ asset('storage/'.$user->employee_qr_profile_image) }}" alt="QR Profil {{ $user->name }}">
                        <div class="qr-value">{{ $user->employee_qr_profile_value }}</div>
                        <div class="qr-actions">
                            <a href="{{ $user->employee_qr_profile_value }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-external-link-alt me-1"></i> Preview
                            </a>
                            <a href="{{ url('/pegawai/'.$user->id.'/qr/profile/download') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fa fa-download me-1"></i> PNG
                            </a>
                            <a href="{{ url('/pegawai/print/'.$user->id.'?mode=profile') }}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fa fa-print me-1"></i> Cetak
                            </a>
                        </div>
                    </div>

                    <div class="qr-panel">
                        <div class="qr-header">
                            <h5>QR Simpan Kontak</h5>
                            <span>URL VCF pendek</span>
                        </div>
                        <img class="qr-image" src="{{ asset('storage/'.$user->employee_qr_vcard_image) }}" alt="QR Simpan Kontak {{ $user->name }}">
                        <div class="qr-value">{{ $user->employee_qr_vcard_value }}</div>
                        <div class="qr-actions">
                            <a href="{{ $user->employee_qr_vcard_value }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-address-card me-1"></i> VCF
                            </a>
                            <a href="{{ url('/pegawai/'.$user->id.'/qr/vcard/download') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fa fa-download me-1"></i> PNG
                            </a>
                            <a href="{{ url('/pegawai/print/'.$user->id.'?mode=vcard') }}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fa fa-print me-1"></i> Cetak
                            </a>
                        </div>
                    </div>
                </div>

                <div class="footer-actions">
                    <a href="{{ url('/pegawai/print/'.$user->id.'?mode=both') }}" target="_blank" class="btn btn-success">
                        <i class="fa fa-print me-1"></i> Cetak Dua Mode
                    </a>
                    <form action="{{ url('/pegawai/'.$user->id.'/qr/regenerate') }}" method="POST" onsubmit="return confirm('Buat ulang token QR? URL lama tidak bisa dipakai lagi.')">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-sync-alt me-1"></i> Regenerate Token
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('style')
        <style>
            .employee-qr-layout {
                background: #fff;
                border-radius: 8px;
                padding: 24px;
                box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
            }

            .employee-summary {
                display: flex;
                align-items: center;
                gap: 18px;
                margin-bottom: 18px;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 18px;
            }

            .employee-photo img {
                width: 88px;
                height: 88px;
                border-radius: 50%;
                object-fit: cover;
                border: 3px solid #e5e7eb;
            }

            .employee-summary h2 {
                margin: 0 0 4px;
                font-size: 24px;
                font-weight: 700;
            }

            .employee-summary p {
                margin: 0;
                color: #64748b;
            }

            .scan-info-panel {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 18px;
                margin-bottom: 18px;
                background: #fbfdff;
            }

            .scan-info-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
                margin-bottom: 14px;
            }

            .scan-info-header h5 {
                margin: 0 0 4px;
                font-size: 17px;
                font-weight: 700;
            }

            .scan-info-header p {
                margin: 0;
                color: #64748b;
                font-size: 13px;
            }

            .qr-info-rows {
                display: grid;
                gap: 10px;
            }

            .qr-info-row {
                display: grid;
                grid-template-columns: minmax(140px, 0.75fr) minmax(160px, 0.9fr) minmax(220px, 1.4fr) 42px;
                gap: 10px;
                align-items: end;
            }

            .qr-info-row label {
                display: block;
                margin-bottom: 5px;
                color: #475569;
                font-size: 12px;
                font-weight: 700;
            }

            .qr-info-row .remove-qr-info {
                width: 42px;
                height: 38px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .icon-picker {
                position: relative;
            }

            .icon-picker-toggle {
                width: 100%;
                height: 38px;
                border: 1px solid #d1d5db;
                border-radius: 4px;
                background: #fff;
                color: #1d4ed8;
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 0 10px;
                text-align: left;
            }

            .icon-picker-toggle i {
                width: 18px;
                text-align: center;
            }

            .icon-picker-toggle span {
                color: #334155;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .icon-picker-menu {
                display: none;
                position: absolute;
                z-index: 20;
                left: 0;
                top: calc(100% + 6px);
                width: 270px;
                max-width: 80vw;
                background: #fff;
                border: 1px solid #dbe3ef;
                border-radius: 8px;
                box-shadow: 0 16px 34px rgba(15, 23, 42, 0.16);
                padding: 10px;
            }

            .icon-picker.is-open .icon-picker-menu {
                display: block;
            }

            .icon-options {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 6px;
                margin-top: 8px;
                max-height: 214px;
                overflow: auto;
            }

            .icon-option {
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                background: #fff;
                color: #334155;
                display: flex;
                align-items: center;
                gap: 7px;
                min-height: 36px;
                padding: 7px 8px;
                text-align: left;
            }

            .icon-option:hover,
            .icon-option.is-selected {
                border-color: #2563eb;
                background: #eff6ff;
                color: #1d4ed8;
            }

            .icon-option i {
                width: 16px;
                color: #2563eb;
                text-align: center;
            }

            .icon-option span {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .scan-info-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 14px;
            }

            .qr-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 18px;
            }

            .qr-panel {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 18px;
                text-align: center;
            }

            .qr-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                margin-bottom: 14px;
                text-align: left;
            }

            .qr-header h5 {
                margin: 0;
                font-size: 17px;
                font-weight: 700;
            }

            .qr-header span {
                font-size: 12px;
                color: #64748b;
            }

            .qr-image {
                width: 230px;
                height: 230px;
                object-fit: contain;
                margin: 0 auto 12px;
            }

            .qr-value {
                min-height: 38px;
                font-size: 12px;
                color: #475569;
                word-break: break-word;
                margin-bottom: 14px;
            }

            .qr-actions,
            .footer-actions {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 8px;
            }

            .footer-actions {
                margin-top: 22px;
            }

            @media (max-width: 767.98px) {
                .scan-info-header {
                    display: block;
                }

                .scan-info-header .btn {
                    margin-top: 12px;
                    width: 100%;
                }

                .qr-info-row {
                    grid-template-columns: 1fr 42px;
                }

                .qr-info-row > div {
                    grid-column: 1 / -1;
                }

                .qr-info-row .remove-qr-info {
                    grid-column: 2;
                    justify-self: end;
                }

                .icon-picker-menu {
                    width: min(270px, calc(100vw - 72px));
                }
            }
        </style>
    @endpush

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var rows = document.getElementById('qr-info-rows');
                var addButton = document.getElementById('add-qr-info');
                var iconCatalog = @json($iconCatalog);
                var defaultIcon = 'info-circle';

                function optionButtons(icon) {
                    var html = '';
                    Object.keys(iconCatalog).forEach(function (key) {
                        var selected = key === icon ? ' is-selected' : '';
                        var keywords = (key + ' ' + iconCatalog[key]).toLowerCase();
                        html += '<button type="button" class="icon-option' + selected + '" data-icon="' + key + '" data-label="' + iconCatalog[key] + '" data-keywords="' + keywords + '">' +
                            '<i class="fa fa-' + key + '"></i>' +
                            '<span>' + iconCatalog[key] + '</span>' +
                        '</button>';
                    });

                    return html;
                }

                function makeRow() {
                    var row = document.createElement('div');
                    row.className = 'qr-info-row';
                    row.innerHTML =
                        '<div class="icon-picker">' +
                            '<label>Ikon</label>' +
                            '<button type="button" class="icon-picker-toggle">' +
                                '<i class="fa fa-' + defaultIcon + '"></i>' +
                                '<span>' + iconCatalog[defaultIcon] + '</span>' +
                            '</button>' +
                            '<input type="hidden" name="info_icons[]" value="' + defaultIcon + '">' +
                            '<div class="icon-picker-menu">' +
                                '<input type="text" class="form-control icon-search" placeholder="Cari ikon...">' +
                                '<div class="icon-options">' + optionButtons(defaultIcon) + '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div>' +
                            '<label>Judul Info</label>' +
                            '<input type="text" name="info_labels[]" class="form-control" value="" placeholder="Contoh: Golongan Darah">' +
                        '</div>' +
                        '<div>' +
                            '<label>Isi Info</label>' +
                            '<input type="text" name="info_values[]" class="form-control" value="" placeholder="Contoh: O+">' +
                        '</div>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger remove-qr-info" title="Hapus informasi">' +
                            '<i class="fa fa-trash"></i>' +
                        '</button>';

                    return row;
                }

                addButton.addEventListener('click', function () {
                    rows.appendChild(makeRow());
                });

                rows.addEventListener('click', function (event) {
                    var toggle = event.target.closest('.icon-picker-toggle');
                    if (toggle) {
                        var picker = toggle.closest('.icon-picker');
                        rows.querySelectorAll('.icon-picker.is-open').forEach(function (openPicker) {
                            if (openPicker !== picker) {
                                openPicker.classList.remove('is-open');
                            }
                        });
                        picker.classList.toggle('is-open');
                        var search = picker.querySelector('.icon-search');
                        if (picker.classList.contains('is-open') && search) {
                            search.focus();
                        }
                        return;
                    }

                    var option = event.target.closest('.icon-option');
                    if (option) {
                        var pickerOption = option.closest('.icon-picker');
                        var icon = option.getAttribute('data-icon') || defaultIcon;
                        var label = option.getAttribute('data-label') || iconCatalog[defaultIcon];
                        pickerOption.querySelector('input[type="hidden"]').value = icon;
                        pickerOption.querySelector('.icon-picker-toggle i').className = 'fa fa-' + icon;
                        pickerOption.querySelector('.icon-picker-toggle span').textContent = label;
                        pickerOption.querySelectorAll('.icon-option').forEach(function (button) {
                            button.classList.toggle('is-selected', button === option);
                        });
                        pickerOption.classList.remove('is-open');
                        return;
                    }

                    var button = event.target.closest('.remove-qr-info');
                    if (!button) {
                        return;
                    }

                    var currentRows = rows.querySelectorAll('.qr-info-row');
                    if (currentRows.length === 1) {
                        currentRows[0].querySelectorAll('input').forEach(function (input) {
                            input.value = '';
                        });
                        return;
                    }

                    button.closest('.qr-info-row').remove();
                });

                rows.addEventListener('input', function (event) {
                    if (!event.target.classList.contains('icon-search')) {
                        return;
                    }

                    var keyword = event.target.value.toLowerCase().trim();
                    event.target.closest('.icon-picker').querySelectorAll('.icon-option').forEach(function (option) {
                        var haystack = option.getAttribute('data-keywords') || '';
                        option.style.display = haystack.indexOf(keyword) !== -1 ? '' : 'none';
                    });
                });

                document.addEventListener('click', function (event) {
                    if (event.target.closest('.icon-picker')) {
                        return;
                    }

                    rows.querySelectorAll('.icon-picker.is-open').forEach(function (picker) {
                        picker.classList.remove('is-open');
                    });
                });
            });
        </script>
    @endpush
@endsection
