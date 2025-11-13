@echo off
setlocal
set "SCRIPT_DIR=%~dp0"
set "LOG_FILE=%SCRIPT_DIR%logs\install-latest.log"
if not exist "%SCRIPT_DIR%scripts\install.ps1" (
    echo Kurulum betigi bulunamadi.
    exit /b 1
)
powershell -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%scripts\install.ps1" -Root "%SCRIPT_DIR%"
if errorlevel 1 (
    echo Kurulum tamamlanamadi. Detaylar icin %LOG_FILE% dosyasini kontrol edin.
    exit /b 1
)
echo Kurulum tamamlandi. Son ayrintilar icin %LOG_FILE% dosyasini inceleyebilirsiniz.
endlocal
