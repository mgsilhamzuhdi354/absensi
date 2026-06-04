@extends('templates.dashboard')
@section('isi')
<style>
    .smart-import-container { max-width: 1280px; margin: 0 auto; }
    .step-wizard { display: flex; justify-content: center; margin-bottom: 30px; gap: 0; }
    .step-item { display: flex; align-items: center; gap: 10px; padding: 12px 24px; background: #f1f5f9; border-radius: 12px; transition: all 0.4s ease; opacity: 0.5; }
    .step-item.active { background: linear-gradient(135deg, var(--primary-color), #7c3aed); color: white; opacity: 1; transform: scale(1.05); box-shadow: 0 8px 25px rgba(67,97,238,0.3); }
    .step-item.done { background: #10b981; color: white; opacity: 1; }
    .step-number { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
    .step-connector { width: 60px; height: 3px; background: #e2e8f0; align-self: center; margin: 0 8px; border-radius: 2px; transition: background 0.4s; }
    .step-connector.done { background: #10b981; }

    .upload-zone { border: 3px dashed #cbd5e1; border-radius: 20px; padding: 50px 30px; text-align: center; transition: all 0.3s; cursor: pointer; background: #f8fafc; }
    .upload-zone:hover, .upload-zone.dragover { border-color: var(--primary-color); background: rgba(67,97,238,0.05); transform: translateY(-2px); }
    .upload-zone i { font-size: 48px; color: #94a3b8; margin-bottom: 15px; }
    .upload-zone.dragover i { color: var(--primary-color); }
    .upload-zone .file-name { margin-top: 10px; font-weight: 600; color: #10b981; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .stat-card { padding: 15px; border-radius: 12px; text-align: center; color: white; }
    .stat-card .stat-number { font-size: 28px; font-weight: 800; }
    .stat-card .stat-label { font-size: 12px; opacity: 0.9; }
    .stat-total { background: linear-gradient(135deg, #4361ee, #3730a3); }
    .stat-valid { background: linear-gradient(135deg, #10b981, #059669); }
    .stat-fuzzy { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .stat-invalid { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .stat-create { background: linear-gradient(135deg, #06b6d4, #0891b2); }
    .stat-update { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

    .preview-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .preview-table thead th { background: linear-gradient(135deg, #1e293b, #334155); color: white; padding: 12px 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 10; white-space: nowrap; }
    .preview-table thead th:first-child { border-radius: 10px 0 0 0; }
    .preview-table thead th:last-child { border-radius: 0 10px 0 0; }
    .preview-table tbody td { padding: 10px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; white-space: nowrap; }
    .preview-table tbody tr:hover { background: #f8fafc; }
    .preview-table tbody tr.row-exact { border-left: 4px solid #10b981; }
    .preview-table tbody tr.row-fuzzy { border-left: 4px solid #f59e0b; }
    .preview-table tbody tr.row-error { border-left: 4px solid #ef4444; background: #fef2f2; }
    .raw-cell { max-width: 260px; overflow: hidden; text-overflow: ellipsis; }

    .match-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .match-exact { background: #d1fae5; color: #065f46; }
    .match-fuzzy { background: #fef3c7; color: #92400e; }
    .match-error { background: #fee2e2; color: #991b1b; }
    .action-badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .action-create { background: #cffafe; color: #155e75; }
    .action-update { background: #ede9fe; color: #5b21b6; }

    .preview-tabs { display: inline-flex; gap: 8px; background: #f1f5f9; padding: 5px; border-radius: 12px; }
    .preview-tab { border: 0; border-radius: 9px; padding: 8px 14px; font-weight: 700; color: #475569; background: transparent; }
    .preview-tab.active { color: white; background: linear-gradient(135deg, var(--primary-color), #7c3aed); }
    .raw-meta { font-size: 12px; color: #64748b; font-weight: 600; }

    .table-scroll { max-height: 520px; overflow: auto; border-radius: 12px; border: 1px solid #e2e8f0; }
    #step1, #step2, #step3 { display: none; }
    #step1.active-step, #step2.active-step, #step3.active-step { display: block; }

    .form-section { background: white; border-radius: 16px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .form-section h5 { font-weight: 700; color: #1e293b; margin-bottom: 15px; }
    .form-section h5 i { margin-right: 8px; color: var(--primary-color); }
    .import-result { text-align: center; padding: 40px; }
    .success-icon { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
    .success-icon i { font-size: 40px; color: white; }
</style>

<div class="smart-import-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="background: linear-gradient(135deg, var(--primary-color), #7c3aed); border: none; border-radius: 16px;">
                <div class="card-body text-white p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="mb-1" style="font-weight: 800;"><i class="fas fa-robot me-2"></i>Smart Import Absensi</h3>
                            <p class="mb-0 opacity-75">Upload file Excel dari mesin sidik jari, review semua kolom, lalu import data yang valid.</p>
                        </div>
                        <a href="{{ url('/data-absen') }}" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="step-wizard" id="stepWizard">
        <div class="step-item active" id="stepItem1">
            <div class="step-number">1</div>
            <span><strong>Upload File</strong></span>
        </div>
        <div class="step-connector" id="conn1"></div>
        <div class="step-item" id="stepItem2">
            <div class="step-number">2</div>
            <span><strong>Preview & Verifikasi</strong></span>
        </div>
        <div class="step-connector" id="conn2"></div>
        <div class="step-item" id="stepItem3">
            <div class="step-number">3</div>
            <span><strong>Hasil Import</strong></span>
        </div>
    </div>

    <div id="step1" class="active-step">
        <div class="row">
            <div class="col-md-8">
                <div class="form-section">
                    <h5><i class="fas fa-file-upload"></i>Upload File Absensi</h5>
                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h5 style="color: #475569;">Drag & Drop file Excel di sini</h5>
                        <p class="text-muted">Pilih satu file lama, atau paket mesin: Catatan Kehadiran, Tidak Normal, Laporan, Informasi Pengguna.</p>
                        <div class="file-name" id="fileName" style="display:none;"></div>
                    </div>
                    <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" multiple style="display:none;">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-section">
                    <h5><i class="fas fa-cog"></i>Pengaturan Import</h5>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Shift Default <span class="text-danger">*</span></label>
                        <select id="shiftSelect" class="form-control">
                            <option value="">-- Pilih Shift --</option>
                            @foreach($shifts as $s)
                                @if($s->id != 1)
                                    <option value="{{ $s->id }}">{{ $s->nama_shift }} ({{ $s->jam_masuk }} - {{ $s->jam_keluar }})</option>
                                @endif
                            @endforeach
                        </select>
                        <small class="text-muted">Shift ini dipakai saat data valid masuk ke sistem.</small>
                    </div>
                    <hr>
                    <ul class="list-unstyled mb-0" style="font-size: 13px; color: #64748b;">
                        <li class="mb-1">Kolom Excel asli akan tampil lengkap.</li>
                        <li class="mb-1">Data valid bisa dicentang satu per satu.</li>
                        <li class="mb-1">Data existing akan di-update.</li>
                    </ul>
                </div>
                <button class="btn btn-primary w-100 py-3" id="btnPreview" disabled style="font-size: 16px; font-weight: 700; border-radius: 12px;">
                    <i class="fas fa-search me-2"></i>Scan & Preview
                </button>
            </div>
        </div>
    </div>

    <div id="step2">
        <div class="stats-grid" id="statsGrid"></div>
        <div class="form-section">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="mb-0"><i class="fas fa-table"></i>Hasil Scan File</h5>
                <div>
                    <button class="btn btn-outline-secondary btn-sm me-2" id="btnBackStep1"><i class="fas fa-arrow-left me-1"></i>Kembali</button>
                    <button class="btn btn-success px-4 py-2" id="btnImport" style="font-weight: 700; border-radius: 10px;">
                        <i class="fas fa-download me-2"></i>Import <span id="importCount">0</span> Data Sekarang
                    </button>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="preview-tabs">
                    <button type="button" class="preview-tab active" id="tabSystem">Data Sistem</button>
                    <button type="button" class="preview-tab" id="tabRaw">Semua Kolom Excel</button>
                </div>
                <div class="raw-meta" id="rawMeta"></div>
            </div>
            <div id="machineInfo" class="mb-3" style="display:none;"></div>

            <div id="systemPreviewPanel">
                <div class="mb-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="filterValid" checked>
                        <label class="form-check-label" for="filterValid"><span class="match-badge match-exact">Valid</span></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="filterFuzzy" checked>
                        <label class="form-check-label" for="filterFuzzy"><span class="match-badge match-fuzzy">Fuzzy</span></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="filterError" checked>
                        <label class="form-check-label" for="filterError"><span class="match-badge match-error">Error</span></label>
                    </div>
                </div>
                <div class="table-scroll" id="previewTableContainer"></div>
            </div>

            <div id="rawPreviewPanel" style="display:none;">
                <div class="table-scroll" id="rawTableContainer"></div>
            </div>
        </div>
    </div>

    <div id="step3">
        <div class="form-section">
            <div class="import-result" id="importResult"></div>
        </div>
    </div>
</div>

@push('script')
<script>
$(document).ready(function() {
    let previewData = [];
    let rawPreview = { headers: [], rows: [], total_rows: 0, total_columns: 0 };
    const zone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    function showFileName(files) {
        files = Array.from(files || []);
        if (!files.length) {
            $('#fileName').hide().empty();
            checkReady();
            return;
        }

        let label = files.length === 1
            ? escapeHtml(files[0].name) + ' (' + (files[0].size / 1024).toFixed(1) + ' KB)'
            : files.length + ' file dipilih: ' + files.map(function(file) { return escapeHtml(file.name); }).join(', ');

        $('#fileName').show().html('<i class="fas fa-file-excel me-2"></i>' + label);
        checkReady();
    }

    function checkReady() {
        $('#btnPreview').prop('disabled', !(fileInput.files.length && $('#shiftSelect').val()));
    }

    ['dragenter', 'dragover'].forEach(function(eventName) {
        zone.addEventListener(eventName, function(event) {
            event.preventDefault();
            zone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function(eventName) {
        zone.addEventListener(eventName, function(event) {
            event.preventDefault();
            zone.classList.remove('dragover');
        });
    });

    zone.addEventListener('drop', function(event) {
        if (event.dataTransfer.files.length) {
            fileInput.files = event.dataTransfer.files;
            showFileName(event.dataTransfer.files);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length) {
            showFileName(this.files);
        }
    });

    $('#shiftSelect').on('change', checkReady);

    function goToStep(step) {
        $('#step1, #step2, #step3').removeClass('active-step');
        $('#step' + step).addClass('active-step');
        $('#stepItem1, #stepItem2, #stepItem3').removeClass('active done');

        for (let i = 1; i < step; i++) {
            $('#stepItem' + i).addClass('done');
            $('#conn' + i).addClass('done');
        }

        $('#stepItem' + step).addClass('active');
        for (let i = step; i <= 2; i++) {
            $('#conn' + i).removeClass('done');
        }
    }

    $('#btnPreview').on('click', function() {
        let formData = new FormData();
        Array.from(fileInput.files).forEach(function(file) {
            formData.append('file_absen[]', file);
        });
        formData.append('shift_id', $('#shiftSelect').val());
        formData.append('_token', '{{ csrf_token() }}');

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Memproses...');

        $.ajax({
            url: '{{ url("/smart-import-absen/preview") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                previewData = (response.preview || []).map(function(row, index) {
                    row._preview_key = row.preview_key || (String(row.row_index) + '-' + index);
                    return row;
                });
                rawPreview = response.raw_preview || { headers: response.headers || [], rows: [], total_rows: 0, total_columns: 0 };

                renderStats(response.stats || {});
                renderMachineInfo(response.machine || {});
                renderSystemTable();
                renderRawTable();
                $('#rawMeta').text((rawPreview.total_rows || 0) + ' baris Excel - ' + (rawPreview.total_columns || 0) + ' kolom');
                $('#tabSystem').trigger('click');
                goToStep(2);
                $('#btnPreview').prop('disabled', false).html('<i class="fas fa-search me-2"></i>Scan & Preview');
            },
            error: function(xhr) {
                let message = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan';
                Swal.fire('Error', message, 'error');
                $('#btnPreview').prop('disabled', false).html('<i class="fas fa-search me-2"></i>Scan & Preview');
            }
        });
    });

    function renderStats(stats) {
        $('#statsGrid').html(`
            <div class="stat-card stat-total"><div class="stat-number">${stats.total || 0}</div><div class="stat-label">Data Sistem</div></div>
            <div class="stat-card stat-valid"><div class="stat-number">${stats.valid || 0}</div><div class="stat-label">Valid</div></div>
            <div class="stat-card stat-fuzzy"><div class="stat-number">${stats.fuzzy || 0}</div><div class="stat-label">Fuzzy Match</div></div>
            <div class="stat-card stat-invalid"><div class="stat-number">${stats.invalid || 0}</div><div class="stat-label">Tidak Bisa Import</div></div>
            <div class="stat-card stat-create"><div class="stat-number">${stats.will_create || 0}</div><div class="stat-label">Data Baru</div></div>
            <div class="stat-card stat-update"><div class="stat-number">${stats.will_update || 0}</div><div class="stat-label">Update</div></div>
        `);
    }

    function renderMachineInfo(machine) {
        let files = Array.isArray(machine.files) ? machine.files : [];
        let warnings = Array.isArray(machine.warnings) ? machine.warnings : [];

        if (!files.length && !warnings.length) {
            $('#machineInfo').hide().empty();
            return;
        }

        let fileHtml = files.length
            ? '<div class="mb-2"><strong>File mesin:</strong> ' + files.map(function(file) {
                return escapeHtml(file.file) + ' <span class="badge bg-secondary">' + escapeHtml(file.type) + '</span>';
            }).join(' &nbsp; ') + '</div>'
            : '';

        let warningHtml = warnings.length
            ? '<div class="alert alert-warning mb-0 py-2"><strong>Validasi mesin:</strong><ul class="mb-0">' + warnings.slice(0, 10).map(function(warning) {
                return '<li>' + escapeHtml(warning) + '</li>';
            }).join('') + (warnings.length > 10 ? '<li>+' + (warnings.length - 10) + ' warning lainnya.</li>' : '') + '</ul></div>'
            : '';

        $('#machineInfo').show().html(fileHtml + warningHtml);
    }

    function rowCategory(row) {
        if (!row.valid) {
            return 'error';
        }
        if (row.match_type === 'fuzzy') {
            return 'fuzzy';
        }
        return 'exact';
    }

    function renderSystemTable() {
        let showValid = $('#filterValid').is(':checked');
        let showFuzzy = $('#filterFuzzy').is(':checked');
        let showError = $('#filterError').is(':checked');

        let filtered = previewData.filter(function(row) {
            let category = rowCategory(row);
            return (category === 'exact' && showValid) || (category === 'fuzzy' && showFuzzy) || (category === 'error' && showError);
        });

        let html = '<table class="preview-table"><thead><tr>';
        html += '<th><input type="checkbox" id="checkAll" checked></th>';
        html += '<th>No</th><th>Sumber</th><th>Nama (File)</th><th>Nama (Sistem)</th><th>Match</th>';
        html += '<th>Tanggal</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Status</th><th>Aksi</th><th>Catatan</th>';
        html += '</tr></thead><tbody>';

        filtered.forEach(function(row, index) {
            let category = rowCategory(row);
            let rowClass = category === 'exact' ? 'row-exact' : (category === 'fuzzy' ? 'row-fuzzy' : 'row-error');
            let matchBadge = category === 'exact'
                ? '<span class="match-badge match-exact">OK ' + escapeHtml(row.confidence || 0) + '%</span>'
                : (category === 'fuzzy'
                    ? '<span class="match-badge match-fuzzy">Fuzzy ' + escapeHtml(row.confidence || 0) + '%</span>'
                    : '<span class="match-badge match-error">Error</span>');
            let actionBadge = row.action === 'create'
                ? '<span class="action-badge action-create">Baru</span>'
                : (row.action === 'update'
                    ? '<span class="action-badge action-update">Update</span>'
                    : '<span class="badge bg-secondary">Skip</span>');
            let errors = Array.isArray(row.errors) && row.errors.length ? row.errors.join('; ') : '-';

            html += `<tr class="${rowClass}">`;
            html += `<td><input type="checkbox" class="row-check" data-key="${escapeHtml(row._preview_key)}" ${row.valid ? 'checked' : 'disabled'}></td>`;
            html += `<td>${index + 1}</td>`;
            html += `<td>${escapeHtml(row.source_format || '-')}</td>`;
            html += `<td><strong>${escapeHtml(row.raw_nama || row.raw_employee_id || '-')}</strong></td>`;
            html += `<td>${row.user_name ? escapeHtml(row.user_name) : '<em class="text-danger">-</em>'}</td>`;
            html += `<td>${matchBadge}</td>`;
            html += `<td>${row.tanggal ? escapeHtml(row.tanggal) : '<em class="text-danger">' + escapeHtml(row.raw_tanggal || '-') + '</em>'}</td>`;
            html += `<td>${escapeHtml(row.jam_absen || '-')}</td>`;
            html += `<td>${escapeHtml(row.jam_pulang || '-')}</td>`;
            html += `<td><span class="badge ${row.status_absen === 'Masuk' ? 'bg-success' : 'bg-warning'}">${escapeHtml(row.status_absen || '-')}</span></td>`;
            html += `<td>${actionBadge}</td>`;
            html += `<td class="raw-cell" title="${escapeHtml(errors)}">${escapeHtml(errors)}</td>`;
            html += '</tr>';
        });

        if (!filtered.length) {
            html += '<tr><td colspan="12" class="text-center text-muted py-4">Tidak ada data pada filter ini.</td></tr>';
        }

        html += '</tbody></table>';
        $('#previewTableContainer').html(html);

        $('#checkAll').on('change', function() {
            $('.row-check:not(:disabled)').prop('checked', $(this).is(':checked'));
            updateImportCount();
        });
        $('.row-check').on('change', updateImportCount);
        updateImportCount();
    }

    function renderRawTable() {
        let headers = rawPreview.headers || [];
        let rows = rawPreview.rows || [];
        let html = '<table class="preview-table"><thead><tr><th>No</th><th>Row</th>';
        headers.forEach(function(header) {
            html += '<th>' + escapeHtml(header) + '</th>';
        });
        html += '</tr></thead><tbody>';

        rows.forEach(function(row, index) {
            html += '<tr><td>' + (index + 1) + '</td><td>' + escapeHtml(row.row_number || '-') + '</td>';
            (row.values || []).forEach(function(value) {
                html += '<td class="raw-cell" title="' + escapeHtml(value) + '">' + (escapeHtml(value) || '-') + '</td>';
            });
            html += '</tr>';
        });

        if (!rows.length) {
            html += '<tr><td colspan="' + (headers.length + 2) + '" class="text-center text-muted py-4">Tidak ada baris Excel yang terbaca.</td></tr>';
        }

        html += '</tbody></table>';
        $('#rawTableContainer').html(html);
    }

    function updateImportCount() {
        let count = $('.row-check:checked').length;
        $('#importCount').text(count);
        $('#btnImport').prop('disabled', count === 0);
    }

    $('#filterValid, #filterFuzzy, #filterError').on('change', renderSystemTable);
    $('#btnBackStep1').on('click', function() { goToStep(1); });

    $('#tabSystem').on('click', function() {
        $('#tabSystem').addClass('active');
        $('#tabRaw').removeClass('active');
        $('#systemPreviewPanel').show();
        $('#rawPreviewPanel').hide();
    });

    $('#tabRaw').on('click', function() {
        $('#tabRaw').addClass('active');
        $('#tabSystem').removeClass('active');
        $('#systemPreviewPanel').hide();
        $('#rawPreviewPanel').show();
    });

    $('#btnImport').on('click', function() {
        let selectedKeys = [];
        $('.row-check:checked').each(function() {
            selectedKeys.push(String($(this).data('key')));
        });

        let importRows = previewData.filter(function(row) {
            return selectedKeys.includes(String(row._preview_key)) && row.valid;
        });

        if (!importRows.length) {
            Swal.fire('Peringatan', 'Tidak ada data valid yang dipilih untuk diimport.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Import',
            html: '<p>Anda akan mengimport <strong>' + importRows.length + '</strong> data absensi ke sistem.</p><p class="text-muted">Data yang sudah ada akan di-update.</p>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Import',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#10b981'
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            $('#btnImport').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengimport...');

            $.ajax({
                url: '{{ url("/smart-import-absen/import") }}',
                type: 'POST',
                data: JSON.stringify({ import_rows: importRows, _token: '{{ csrf_token() }}' }),
                contentType: 'application/json',
                success: function(response) {
                    goToStep(3);
                    let stats = response.stats || {};
                    let errors = Array.isArray(stats.errors) && stats.errors.length
                        ? '<div class="mt-3 text-start"><strong>Errors:</strong><ul>' + stats.errors.map(function(error) { return '<li class="text-danger">' + escapeHtml(error) + '</li>'; }).join('') + '</ul></div>'
                        : '';

                    $('#importResult').html(`
                        <div class="success-icon"><i class="fas fa-check"></i></div>
                        <h3 style="font-weight:800; color:#1e293b;">Import Berhasil</h3>
                        <p class="text-muted mb-4">Data absensi yang sudah direview telah dimasukkan ke sistem.</p>
                        <div class="stats-grid" style="max-width:500px; margin:0 auto;">
                            <div class="stat-card stat-create"><div class="stat-number">${stats.created || 0}</div><div class="stat-label">Data Baru</div></div>
                            <div class="stat-card stat-update"><div class="stat-number">${stats.updated || 0}</div><div class="stat-label">Di-update</div></div>
                            <div class="stat-card stat-invalid"><div class="stat-number">${stats.skipped || 0}</div><div class="stat-label">Dilewati</div></div>
                        </div>
                        ${errors}
                        <div class="mt-4">
                            <a href="{{ url('/data-absen') }}" class="btn btn-primary px-4 py-2 me-2"><i class="fas fa-table me-1"></i>Lihat Data Absen</a>
                            <a href="{{ url('/rekap-data') }}" class="btn btn-success px-4 py-2 me-2"><i class="fas fa-chart-bar me-1"></i>Rekap Data</a>
                            <button class="btn btn-outline-secondary px-4 py-2" onclick="location.reload()"><i class="fas fa-redo me-1"></i>Import Lagi</button>
                        </div>
                    `);
                },
                error: function(xhr) {
                    let message = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal mengimport data';
                    Swal.fire('Error', message, 'error');
                    $('#btnImport').prop('disabled', false).html('<i class="fas fa-download me-2"></i>Import <span id="importCount">' + importRows.length + '</span> Data Sekarang');
                }
            });
        });
    });
});
</script>
@endpush
@endsection
