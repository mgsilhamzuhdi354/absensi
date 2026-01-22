# ================================================
# SCRIPT DEPLOY KE DOMAINESIA VIA FTP
# ================================================

# Konfigurasi
$FTP_HOST = "indooceancrew.com"
$FTP_USER = "absensi@indooceancrew.com"
$FTP_TARGET = "/public_html/absensi/aplikasiabsensibygerry"
$LOCAL_PATH = "c:\xampp\htdocs\absensi\aplikasiabsensibygerry"

# Folder yang TIDAK perlu di-upload (hemat waktu & bandwidth)
$EXCLUDE_FOLDERS = @(
    "vendor",
    "node_modules", 
    ".git",
    "storage\logs",
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views"
)

# Warna output
function Write-ColorOutput($ForegroundColor, $Message) {
    Write-Host $Message -ForegroundColor $ForegroundColor
}

# Header
Clear-Host
Write-ColorOutput "Cyan" "================================================"
Write-ColorOutput "Cyan" "   DEPLOY KE DOMAINESIA - APLIKASI ABSENSI"
Write-ColorOutput "Cyan" "================================================"
Write-Host ""
Write-ColorOutput "Yellow" "FTP Host    : $FTP_HOST"
Write-ColorOutput "Yellow" "Username    : $FTP_USER"
Write-ColorOutput "Yellow" "Target      : $FTP_TARGET"
Write-Host ""

# Minta password
$SecurePassword = Read-Host "Masukkan FTP Password" -AsSecureString
$BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($SecurePassword)
$FTP_PASS = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)

Write-Host ""
Write-ColorOutput "Green" "Memulai proses deploy..."
Write-Host ""

# Cek apakah WinSCP tersedia
$WinSCPPath = "C:\Program Files (x86)\WinSCP\WinSCP.com"
if (-not (Test-Path $WinSCPPath)) {
    $WinSCPPath = "C:\Program Files\WinSCP\WinSCP.com"
}

if (Test-Path $WinSCPPath) {
    # Menggunakan WinSCP untuk sync
    Write-ColorOutput "Cyan" "Menggunakan WinSCP untuk sinkronisasi..."
    
    # Buat script WinSCP
    $WinSCPScript = @"
open ftp://${FTP_USER}:${FTP_PASS}@${FTP_HOST}/
option batch abort
option confirm off
lcd "$LOCAL_PATH"
cd $FTP_TARGET
synchronize remote -delete -excludes="vendor/;node_modules/;.git/;storage/logs/*;storage/framework/cache/*;storage/framework/sessions/*;storage/framework/views/*;.env"
close
exit
"@
    
    $ScriptFile = "$env:TEMP\winscp_deploy.txt"
    $WinSCPScript | Out-File -FilePath $ScriptFile -Encoding ASCII
    
    # Jalankan WinSCP
    & $WinSCPPath /script=$ScriptFile /log="$env:TEMP\winscp_log.txt"
    
    # Hapus script temp
    Remove-Item $ScriptFile -Force -ErrorAction SilentlyContinue
    
    Write-Host ""
    Write-ColorOutput "Green" "================================================"
    Write-ColorOutput "Green" "   DEPLOY SELESAI!"
    Write-ColorOutput "Green" "================================================"
} else {
    # WinSCP tidak ditemukan, gunakan metode manual
    Write-ColorOutput "Red" "WinSCP tidak ditemukan!"
    Write-Host ""
    Write-ColorOutput "Yellow" "Silakan install WinSCP terlebih dahulu:"
    Write-ColorOutput "Cyan" "https://winscp.net/eng/download.php"
    Write-Host ""
    Write-ColorOutput "Yellow" "Atau gunakan FileZilla untuk upload manual:"
    Write-Host ""
    Write-Host "1. Buka FileZilla"
    Write-Host "2. Host: $FTP_HOST"
    Write-Host "3. Username: $FTP_USER"
    Write-Host "4. Password: [password Anda]"
    Write-Host "5. Port: 21"
    Write-Host "6. Upload folder: $LOCAL_PATH"
    Write-Host "7. Ke folder: $FTP_TARGET"
}

Write-Host ""
Write-Host "Tekan Enter untuk keluar..."
$null = Read-Host
