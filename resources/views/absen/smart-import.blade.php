@extends('templates.dashboard')
@section('isi')
<style>
    .smart-import-container { max-width: 1200px; margin: 0 auto; }
    .step-wizard { display: flex; justify-content: center; margin-bottom: 30px; gap: 0; }
    .step-item { display: flex; align-items: center; gap: 10px; padding: 12px 24px; background: #f1f5f9; border-radius: 12px; transition: all 0.4s ease; opacity: 0.5; }
    .step-item.active { background: linear-gradient(135deg, var(--primary-color), #7c3aed); color: white; opacity: 1; transform: scale(1.05); box-shadow: 0 8px 25px rgba(67,97,238,0.3); }
    .step-item.done { background: #10b981; color: white; opacity: 1; }
    .step-number { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
    .step-item.active .step-number { background: rgba(255,255,255,0.3); }
    .step-item.done .step-number { background: rgba(255,255,255,0.3); }
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
    .preview-table thead th { background: linear-gradient(135deg, #1e293b, #334155); color: white; padding: 12px 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 10; }
    .preview-table thead th:first-child { border-radius: 10px 0 0 0; }
    .preview-table thead th:last-child { border-radius: 0 10px 0 0; }
    .preview-table tbody td { padding: 10px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .preview-table tbody tr { transition: background 0.2s; }
    .preview-table tbody tr:hover { background: #f8fafc; }
    .preview-table tbody tr.row-exact { border-left: 4px solid #10b981; }
    .preview-table tbody tr.row-fuzzy { border-left: 4px solid #f59e0b; }
    .preview-table tbody tr.row-error { border-left: 4px solid #ef4444; background: #fef2f2; }

    .match-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .match-exact { background: #d1fae5; color: #065f46; }
    .match-fuzzy { background: #fef3c7; color: #92400e; }
    .match-error { background: #fee2e2; color: #991b1b; }
    .action-badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .action-create { background: #cffafe; color: #155e75; }
    .action-update { background: #ede9fe; color: #5b21b6; }

    .import-result { text-align: center; padding: 40px; }
    .import-result .success-icon { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; animation: bounceIn 0.6s; }
    .import-result .success-icon i { font-size: 40px; color: white; }
    @keyframes bounceIn { 0% { transform: scale(0); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }

    .progress-bar-import { height: 8px; border-radius: 4px; background: #e2e8f0; overflow: hidden; margin: 20px 0; }
    .progress-bar-import .fill { height: 100%; background: linear-gradient(90deg, #4361ee, #7c3aed); border-radius: 4px; transition: width 0.3s; }

    .table-scroll { max-height: 500px; overflow-y: auto; border-radius: 12px; border: 1px solid #e2e8f0; }
    #step1, #step2, #step3 { display: none; }
    #step1.active-step { display: block; }
    #step2.active-step { display: block; }
    #step3.active-step { display: block; }

    .form-section { background: white; border-radius: 16px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .form-section h5 { font-weight: 700; color: #1e293b; margin-bottom: 15px; }
    .form-section h5 i { margin-right: 8px; color: var(--primary-color); }
</style>

<div class="smart-import-container">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="background: linear-gradient(135deg, var(--primary-color), #7c3aed); border: none; border-radius: 16px;">
                <div class="card-body text-white p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="mb-1" style="font-weight: 800;"><i class="fas fa-robot me-2"></i>Smart Import Absensi</h3>
                            <p class="mb-0 opacity-75">Upload file Excel dari mesin sidik jari — sistem otomatis deteksi & isi rekap data</p>
                        </div>
                        <div>
                            <a href="{{ url('/data-absen') }}" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Step Wizard --}}
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

    {{-- STEP 1: Upload --}}
    <div id="step1" class="active-step">
        <div class="row">
            <div class="col-md-8">
                <div class="form-section">
                    <h5><i class="fas fa-file-upload"></i>Upload File Absensi</h5>
                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h5 style="color: #475569;">Drag & Drop file Excel di sini</h5>
                        <p class="text-muted">atau klik untuk memilih file (.xlsx, .xls, .csv)</p>
                        <div class="file-name" id="fileName" style="display:none;"></div>
                    </div>
                    <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none;">
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
                        <small class="text-muted">Shift ini akan dipakai untuk semua data yang di-import</small>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fas fa-info-circle text-info"></i> Format yang Didukung:</label>
                        <ul class="list-unstyled" style="font-size: 13px; color: #64748b;">
                            <li class="mb-1">✅ Kolom: Nama, Tanggal, Jam Masuk, Jam Pulang</li>
                            <li class="mb-1">✅ Tanggal: 01/05/2025, 2025-05-01</li>
                            <li class="mb-1">✅ Jam: 07:30, 07.30, 0730</li>
                            <li class="mb-1">✅ Data existing akan di-update</li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-3" id="btnPreview" disabled style="font-size: 16px; font-weight: 700; border-radius: 12px;">
                    <i class="fas fa-search me-2"></i>Scan & Preview
                </button>
            </div>
        </div>
    </div>

    {{-- STEP 2: Preview --}}
    <div id="step2">
        <div class="stats-grid" id="statsGrid"></div>
        <div class="form-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-table"></i>Hasil Scan File</h5>
                <div>
                    <button class="btn btn-outline-secondary btn-sm me-2" id="btnBackStep1"><i class="fas fa-arrow-left me-1"></i>Kembali</button>
                    <button class="btn btn-success px-4 py-2" id="btnImport" style="font-weight: 700; border-radius: 10px;">
                        <i class="fas fa-download me-2"></i>Import <span id="importCount">0</span> Data Sekarang
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="filterValid" checked>
                    <label class="form-check-label" for="filterValid"><span class="match-badge match-exact">✅ Valid</span></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="filterFuzzy" checked>
                    <label class="form-check-label" for="filterFuzzy"><span class="match-badge match-fuzzy">⚠️ Fuzzy</span></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="filterError">
                    <label class="form-check-label" for="filterError"><span class="match-badge match-error">❌ Error</span></label>
                </div>
            </div>
            <div class="table-scroll" id="previewTableContainer"></div>
        </div>
    </div>

    {{-- STEP 3: Result --}}
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
    let currentStep = 1;

    // Drag & Drop
    const zone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');

    ['dragenter','dragover'].forEach(e => {
        zone.addEventListener(e, function(ev) { ev.preventDefault(); zone.classList.add('dragover'); });
    });
    ['dragleave','drop'].forEach(e => {
        zone.addEventListener(e, function(ev) { ev.preventDefault(); zone.classList.remove('dragover'); });
    });
    zone.addEventListener('drop', function(ev) {
        ev.preventDefault();
        if (ev.dataTransfer.files.length) {
            fileInput.files = ev.dataTransfer.files;
            showFileName(ev.dataTransfer.files[0]);
        }
    });
    fileInput.addEventListener('change', function() {
        if (this.files.length) showFileName(this.files[0]);
    });

    function showFileName(file) {
        $('#fileName').show().html('<i class="fas fa-file-excel me-2"></i>' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)');
        checkReady();
    }
    $('#shiftSelect').on('change', checkReady);
    function checkReady() {
        $('#btnPreview').prop('disabled', !(fileInput.files.length && $('#shiftSelect').val()));
    }

    // Step navigation
    function goToStep(n) {
        currentStep = n;
        $('#step1, #step2, #step3').removeClass('active-step');
        $('#step' + n).addClass('active-step');
        $('#stepItem1, #stepItem2, #stepItem3').removeClass('active done');
        for (let i = 1; i < n; i++) { $('#stepItem' + i).addClass('done'); $('#conn' + (i)).addClass('done'); }
        $('#stepItem' + n).addClass('active');
        for (let i = n; i <= 2; i++) { $('#conn' + i).removeClass('done'); }
    }

    // Preview
    $('#btnPreview').on('click', function() {
        let formData = new FormData();
        formData.append('file_absen', fileInput.files[0]);
        formData.append('shift_id', $('#shiftSelect').val());
        formData.append('_token', '{{ csrf_token() }}');

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Memproses...');

        $.ajax({
            url: '{{ url("/smart-import-absen/preview") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                previewData = res.preview;
                renderStats(res.stats);
                renderTable(res.preview);
                goToStep(2);
                $('#btnPreview').prop('disabled', false).html('<i class="fas fa-search me-2"></i>Scan & Preview');
            },
            error: function(xhr) {
                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan';
                Swal.fire('Error', msg, 'error');
                $('#btnPreview').prop('disabled', false).html('<i class="fas fa-search me-2"></i>Scan & Preview');
            }
        });
    });

    function renderStats(stats) {
        $('#statsGrid').html(`
            <div class="stat-card stat-total"><div class="stat-number">${stats.total}</div><div class="stat-label">Total Baris</div></div>
            <div class="stat-card stat-valid"><div class="stat-number">${stats.valid}</div><div class="stat-label">Valid (Cocok)</div></div>
            <div class="stat-card stat-fuzzy"><div class="stat-number">${stats.fuzzy}</div><div class="stat-label">Fuzzy Match</div></div>
            <div class="stat-card stat-invalid"><div class="stat-number">${stats.invalid}</div><div class="stat-label">Tidak Dikenali</div></div>
            <div class="stat-card stat-create"><div class="stat-number">${stats.will_create}</div><div class="stat-label">Data Baru</div></div>
            <div class="stat-card stat-update"><div class="stat-number">${stats.will_update}</div><div class="stat-label">Update</div></div>
        `);
    }

    function renderTable(data) {
        let showValid = $('#filterValid').is(':checked');
        let showFuzzy = $('#filterFuzzy').is(':checked');
        let showError = $('#filterError').is(':checked');

        let filtered = data.filter(r => {
            if ((r.match_type === 'exact' || r.match_type === 'employee_id') && showValid) return true;
            if (r.match_type === 'fuzzy' && showFuzzy) return true;
            if (r.match_type === 'not_found' && showError) return true;
            if (!r.valid && showError) return true;
            return false;
        });

        let importable = data.filter(r => r.valid);
        $('#importCount').text(importable.length);
        $('#btnImport').prop('disabled', importable.length === 0);

        let html = '<table class="preview-table"><thead><tr>';
        html += '<th><input type="checkbox" id="checkAll" checked></th>';
        html += '<th>No</th><th>Nama (File)</th><th>Nama (Sistem)</th><th>Match</th>';
        html += '<th>Tanggal</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Status</th><th>Aksi</th>';
        html += '</tr></thead><tbody>';

        filtered.forEach((r, i) => {
            let rowClass = (r.match_type === 'exact' || r.match_type === 'employee_id') ? 'row-exact' : (r.match_type === 'fuzzy' ? 'row-fuzzy' : 'row-error');
            let matchBadge = (r.match_type === 'exact' || r.match_type === 'employee_id') ? '<span class="match-badge match-exact">OK ' + r.confidence + '%</span>' :
                (r.match_type === 'fuzzy' ? '<span class="match-badge match-fuzzy">⚠️ ' + r.confidence + '%</span>' :
                '<span class="match-badge match-error">❌</span>');
            let actionBadge = r.action === 'create' ? '<span class="action-badge action-create">Baru</span>' :
                (r.action === 'update' ? '<span class="action-badge action-update">Update</span>' : '<span class="badge bg-secondary">Skip</span>');

            html += `<tr class="${rowClass}" data-index="${r.row_index}">`;
            html += `<td><input type="checkbox" class="row-check" data-idx="${r.row_index}" ${r.valid ? 'checked' : 'disabled'}></td>`;
            html += `<td>${i + 1}</td>`;
            html += `<td><strong>${r.raw_nama || '-'}</strong></td>`;
            html += `<td>${r.user_name || '<em class="text-danger">-</em>'}</td>`;
            html += `<td>${matchBadge}</td>`;
            html += `<td>${r.tanggal || '<em class="text-danger">' + (r.raw_tanggal || '-') + '</em>'}</td>`;
            html += `<td>${r.jam_absen || '-'}</td>`;
            html += `<td>${r.jam_pulang || '-'}</td>`;
            html += `<td><span class="badge ${r.status_absen === 'Masuk' ? 'bg-success' : 'bg-warning'}">${r.status_absen}</span></td>`;
            html += `<td>${actionBadge}</td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        $('#previewTableContainer').html(html);

        // Check all handler
        $('#checkAll').on('change', function() {
            $('.row-check:not(:disabled)').prop('checked', $(this).is(':checked'));
            updateImportCount();
        });
        $('.row-check').on('change', updateImportCount);
    }

    function updateImportCount() {
        let count = $('.row-check:checked').length;
        $('#importCount').text(count);
        $('#btnImport').prop('disabled', count === 0);
    }

    // Filters
    $('#filterValid, #filterFuzzy, #filterError').on('change', function() { renderTable(previewData); });

    // Back button
    $('#btnBackStep1').on('click', function() { goToStep(1); });

    // Import
    $('#btnImport').on('click', function() {
        let selectedIndices = [];
        $('.row-check:checked').each(function() { selectedIndices.push(parseInt($(this).data('idx'))); });

        let importRows = previewData.filter(r => selectedIndices.includes(r.row_index) && r.valid);

        if (importRows.length === 0) {
            Swal.fire('Peringatan', 'Tidak ada data yang bisa di-import', 'warning');
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Import',
            html: `<p>Anda akan mengimport <strong>${importRows.length}</strong> data absensi ke sistem.</p><p class="text-muted">Data yang sudah ada akan di-update.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-download me-1"></i> Ya, Import!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#10b981'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $('#btnImport').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengimport...');

            $.ajax({
                url: '{{ url("/smart-import-absen/import") }}',
                type: 'POST',
                data: JSON.stringify({ import_rows: importRows, _token: '{{ csrf_token() }}' }),
                contentType: 'application/json',
                success: function(res) {
                    goToStep(3);
                    let s = res.stats;
                    let errHtml = s.errors.length ? '<div class="mt-3 text-start"><strong>Errors:</strong><ul>' + s.errors.map(e => '<li class="text-danger">' + e + '</li>').join('') + '</ul></div>' : '';
                    $('#importResult').html(`
                        <div class="success-icon"><i class="fas fa-check"></i></div>
                        <h3 style="font-weight:800; color:#1e293b;">Import Berhasil! 🎉</h3>
                        <p class="text-muted mb-4">Data absensi dari mesin sidik jari telah dimasukkan ke sistem.</p>
                        <div class="stats-grid" style="max-width:500px; margin:0 auto;">
                            <div class="stat-card stat-create"><div class="stat-number">${s.created}</div><div class="stat-label">Data Baru</div></div>
                            <div class="stat-card stat-update"><div class="stat-number">${s.updated}</div><div class="stat-label">Di-update</div></div>
                            <div class="stat-card stat-invalid"><div class="stat-number">${s.skipped}</div><div class="stat-label">Dilewati</div></div>
                        </div>
                        ${errHtml}
                        <div class="mt-4">
                            <a href="{{ url('/data-absen') }}" class="btn btn-primary px-4 py-2 me-2"><i class="fas fa-table me-1"></i>Lihat Data Absen</a>
                            <a href="{{ url('/rekap-data') }}" class="btn btn-success px-4 py-2 me-2"><i class="fas fa-chart-bar me-1"></i>Rekap Data</a>
                            <button class="btn btn-outline-secondary px-4 py-2" onclick="location.reload()"><i class="fas fa-redo me-1"></i>Import Lagi</button>
                        </div>
                    `);
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal mengimport data';
                    Swal.fire('Error', msg, 'error');
                    $('#btnImport').prop('disabled', false).html('<i class="fas fa-download me-2"></i>Import <span id="importCount">' + importRows.length + '</span> Data Sekarang');
                }
            });
        });
    });
});
</script>
@endpush
@endsection
