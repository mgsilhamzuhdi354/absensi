@echo off
echo ================================================
echo    DEPLOY KE DOMAINESIA - APLIKASI ABSENSI
echo ================================================
echo.
echo Menjalankan script deploy...
echo.
powershell -ExecutionPolicy Bypass -File "%~dp0deploy.ps1"
pause
