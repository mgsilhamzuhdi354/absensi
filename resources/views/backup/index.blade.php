@extends('templates.dashboard')
@section('isi')
<style>
    /* Animation Styles */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .animate-fade-in {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    .animate-delay-1 { animation-delay: 0.1s; opacity: 0; }
    .animate-delay-2 { animation-delay: 0.2s; opacity: 0; }
    .animate-delay-3 { animation-delay: 0.3s; opacity: 0; }
    .animate-delay-4 { animation-delay: 0.4s; opacity: 0; }
    
    /* Card Styles */
    .backup-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .backup-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }
    
    .backup-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 20px 25px;
    }
    
    .backup-card .card-header.export-header {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    
    .backup-card .card-header.import-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .backup-card .card-header.danger-header {
        background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
    }
    
    /* Icon Box */
    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 15px;
    }
    
    .icon-box.bg-export { background: rgba(17, 153, 142, 0.15); color: #11998e; }
    .icon-box.bg-import { background: rgba(79, 172, 254, 0.15); color: #4facfe; }
    .icon-box.bg-danger { background: rgba(238, 9, 121, 0.15); color: #ee0979; }
    .icon-box.bg-info { background: rgba(102, 126, 234, 0.15); color: #667eea; }
    
    /* Action Button */
    .btn-action {
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    }
    
    .btn-export {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }
    
    .btn-import {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }
    
    .btn-danger-gradient {
        background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
        color: white;
    }
    
    /* Table Stats */
    .table-stat-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 15px;
        background: #f8fafc;
        border-radius: 10px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }
    
    .table-stat-item:hover {
        background: #e2e8f0;
        transform: translateX(5px);
    }
    
    .table-stat-item .table-name {
        font-weight: 500;
        color: #334155;
    }
    
    .table-stat-item .table-count {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        flex-direction: column;
    }
    
    .loading-overlay.show {
        display: flex;
    }
    
    .loading-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top-color: #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    .loading-text {
        color: white;
        margin-top: 20px;
        font-size: 1.1rem;
    }
    
    /* File Input */
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }
    
    .file-input-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    
    .file-drop-zone {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .file-drop-zone:hover, .file-drop-zone.dragover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
    }
    
    /* Stats Summary */
    .stats-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 25px;
        color: white;
    }
    
    .stats-summary h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    /* Delete Table Button */
    .btn-delete-table {
        padding: 4px 10px;
        font-size: 0.75rem;
        border-radius: 6px;
        background: #ef4444;
        color: white;
        border: none;
        transition: all 0.2s;
    }
    
    .btn-delete-table:hover {
        background: #dc2626;
        transform: scale(1.05);
    }
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div class="loading-text" id="loadingText">Memproses...</div>
</div>

<div class="row">
    <!-- Header -->
    <div class="col-12 mb-4 animate-fade-in">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1">
                    <i data-feather="database" class="me-2"></i>
                    Backup & Restore Data
                </h4>
                <p class="text-muted mb-0">Kelola backup data, ekspor, impor, dan hapus data</p>
            </div>
            <div class="stats-summary mt-3 mt-md-0">
                <div class="d-flex align-items-center">
                    <i data-feather="hard-drive" style="width: 40px; height: 40px;" class="me-3"></i>
                    <div>
                        <h2 class="mb-0">{{ number_format($totalRecords) }}</h2>
                        <small>Total Records</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Card -->
    <div class="col-lg-6 mb-4 animate-fade-in animate-delay-1">
        <div class="card backup-card h-100">
            <div class="card-header export-header">
                <h5 class="mb-0">
                    <i data-feather="download" class="me-2"></i>
                    Ekspor Data
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start mb-4">
                    <div class="icon-box bg-export">
                        <i data-feather="file-text"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Backup Semua Data</h6>
                        <p class="text-muted small mb-0">
                            Ekspor semua data ke file JSON untuk backup atau migrasi
                        </p>
                    </div>
                </div>
                <button type="button" class="btn btn-action btn-export w-100" onclick="exportData()">
                    <i data-feather="download" class="me-2"></i>
                    Download Backup
                </button>
            </div>
        </div>
    </div>
    
    <!-- Import Card -->
    <div class="col-lg-6 mb-4 animate-fade-in animate-delay-2">
        <div class="card backup-card h-100">
            <div class="card-header import-header">
                <h5 class="mb-0">
                    <i data-feather="upload" class="me-2"></i>
                    Impor Data
                </h5>
            </div>
            <div class="card-body">
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="file-drop-zone mb-3" id="dropZone">
                        <i data-feather="upload-cloud" style="width: 40px; height: 40px;" class="text-muted mb-2"></i>
                        <p class="mb-1">Drag & drop file backup JSON di sini</p>
                        <small class="text-muted">atau klik untuk memilih file</small>
                        <input type="file" name="backup_file" id="backupFile" accept=".json">
                    </div>
                    <div id="selectedFile" class="alert alert-info d-none mb-3">
                        <i data-feather="file" class="me-2"></i>
                        <span id="fileName"></span>
                    </div>
                    <button type="submit" class="btn btn-action btn-import w-100" id="importBtn" disabled>
                        <i data-feather="upload" class="me-2"></i>
                        Import Data
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Data Statistics -->
    <div class="col-lg-6 mb-4 animate-fade-in animate-delay-3">
        <div class="card backup-card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i data-feather="bar-chart-2" class="me-2"></i>
                    Statistik Data
                </h5>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                @foreach($tables as $table)
                <div class="table-stat-item">
                    <div class="d-flex align-items-center">
                        <span class="table-name">{{ $table['label'] }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="table-count">{{ number_format($table['count']) }}</span>
                        @if($table['count'] > 0)
                        <button type="button" class="btn-delete-table" 
                                onclick="deleteTable('{{ $table['name'] }}', '{{ $table['label'] }}')"
                                title="Hapus data {{ $table['label'] }}">
                            <i data-feather="trash-2" style="width: 12px; height: 12px;"></i>
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Danger Zone -->
    <div class="col-lg-6 mb-4 animate-fade-in animate-delay-4">
        <div class="card backup-card h-100">
            <div class="card-header danger-header">
                <h5 class="mb-0">
                    <i data-feather="alert-triangle" class="me-2"></i>
                    Zona Bahaya
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning mb-4">
                    <h6 class="alert-heading">
                        <i data-feather="alert-circle" class="me-2"></i>
                        Peringatan!
                    </h6>
                    <p class="mb-0 small">
                        Menghapus semua data bersifat <strong>PERMANEN</strong> dan tidak dapat dikembalikan. 
                        Pastikan Anda sudah membuat backup sebelum melanjutkan.
                    </p>
                </div>
                
                <div class="d-flex align-items-start mb-4">
                    <div class="icon-box bg-danger">
                        <i data-feather="trash-2"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Hapus Semua Data</h6>
                        <p class="text-muted small mb-0">
                            Menghapus seluruh data absensi, cuti, kasbon, payroll, dan lainnya
                        </p>
                    </div>
                </div>
                
                <button type="button" class="btn btn-action btn-danger-gradient w-100" onclick="confirmDeleteAll()">
                    <i data-feather="trash-2" class="me-2"></i>
                    Hapus Semua Data
                </button>
            </div>
        </div>
    </div>
</div>

@push('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Initialize Feather Icons
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
    
    // Show/Hide Loading
    function showLoading(text = 'Memproses...') {
        document.getElementById('loadingText').textContent = text;
        document.getElementById('loadingOverlay').classList.add('show');
    }
    
    function hideLoading() {
        document.getElementById('loadingOverlay').classList.remove('show');
    }
    
    // Export Data
    function exportData() {
        Swal.fire({
            title: 'Ekspor Data',
            text: 'Anda akan mengunduh backup semua data dalam format JSON',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#11998e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-download me-2"></i>Download',
            cancelButtonText: 'Batal',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Menyiapkan backup...');
                window.location.href = '{{ url("/backup/export") }}';
                setTimeout(() => {
                    hideLoading();
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'File backup sedang diunduh',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }, 2000);
            }
        });
    }
    
    // File Drop Zone
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('backupFile');
    const importBtn = document.getElementById('importBtn');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'));
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'));
    });
    
    dropZone.addEventListener('drop', handleDrop);
    dropZone.addEventListener('click', () => fileInput.click());
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFileSelect();
    }
    
    fileInput.addEventListener('change', handleFileSelect);
    
    function handleFileSelect() {
        const file = fileInput.files[0];
        if (file) {
            document.getElementById('selectedFile').classList.remove('d-none');
            document.getElementById('fileName').textContent = file.name;
            importBtn.disabled = false;
        }
    }
    
    // Import Form
    document.getElementById('importForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Konfirmasi Import',
            html: `
                <p>Data yang ada akan <strong>ditimpa</strong> dengan data dari file backup.</p>
                <p class="text-danger"><strong>Proses ini tidak dapat dibatalkan!</strong></p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4facfe',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-upload me-2"></i>Ya, Import',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Mengimport data...');
                
                const formData = new FormData(this);
                
                fetch('{{ url("/backup/import") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#11998e'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.error || 'Gagal import data', 'error');
                    }
                })
                .catch(err => {
                    hideLoading();
                    Swal.fire('Error', 'Terjadi kesalahan: ' + err.message, 'error');
                });
            }
        });
    });
    
    // Delete Single Table
    function deleteTable(tableName, tableLabel) {
        Swal.fire({
            title: 'Hapus Data ' + tableLabel + '?',
            text: 'Semua data dalam tabel ini akan dihapus permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-2"></i>Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Menghapus data...');
                
                fetch('{{ url("/backup/delete-table") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ table: tableName })
                })
                .then(res => res.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#11998e'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.error || 'Gagal menghapus data', 'error');
                    }
                })
                .catch(err => {
                    hideLoading();
                    Swal.fire('Error', 'Terjadi kesalahan: ' + err.message, 'error');
                });
            }
        });
    }
    
    // Delete All Data
    function confirmDeleteAll() {
        Swal.fire({
            title: 'PERINGATAN!',
            html: `
                <div class="text-center">
                    <div style="font-size: 60px; color: #ef4444; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p>Anda akan menghapus <strong>SEMUA DATA</strong> dari sistem ini!</p>
                    <p class="text-danger"><strong>Tindakan ini tidak dapat dibatalkan!</strong></p>
                    <hr>
                    <p class="small">Ketik <strong>HAPUS SEMUA DATA</strong> untuk konfirmasi:</p>
                    <input type="text" id="confirmInput" class="form-control text-center" 
                           placeholder="Ketik di sini..." autocomplete="off">
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-2"></i>HAPUS SEMUA',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const input = document.getElementById('confirmInput').value;
                if (input !== 'HAPUS SEMUA DATA') {
                    Swal.showValidationMessage('Konfirmasi tidak sesuai!');
                    return false;
                }
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Menghapus semua data...');
                
                fetch('{{ url("/backup/delete-all") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ confirmation: 'HAPUS SEMUA DATA' })
                })
                .then(res => res.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        Swal.fire({
                            title: 'Selesai!',
                            text: 'Semua data telah dihapus',
                            icon: 'success',
                            confirmButtonColor: '#11998e'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.error || 'Gagal menghapus data', 'error');
                    }
                })
                .catch(err => {
                    hideLoading();
                    Swal.fire('Error', 'Terjadi kesalahan: ' + err.message, 'error');
                });
            }
        });
    }
</script>
@endpush
@endsection
