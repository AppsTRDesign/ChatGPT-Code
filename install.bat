@echo off
setlocal
set "SCRIPT_DIR=%~dp0"
set "LOG_DIR=%SCRIPT_DIR%logs"
if not exist "%LOG_DIR%" (
    mkdir "%LOG_DIR%" >nul 2>&1
)
set "LOG_FILE=%LOG_DIR%\install-latest.log"
set "POWERSHELL=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
if not exist "%POWERSHELL%" set "POWERSHELL=powershell"
if not exist "%SCRIPT_DIR%scripts\install.ps1" (
    echo Kurulum betigi bulunamadi.
    echo Ayrintilar %LOG_FILE% dosyasina yazildi.
    echo Kurulum betigi bulunamadi.>"%LOG_FILE%"
    pause
    exit /b 1
)
echo Kurulum baslatiliyor...
"%POWERSHELL%" -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%scripts\install.ps1" -Root "%SCRIPT_DIR%"
set "EXITCODE=%ERRORLEVEL%"
if not exist "%LOG_FILE%" (
    echo Kurulum ciktisi olusturulamadi. >"%LOG_FILE%"
    echo Powershell ciktisi kaydedilemedi.>>"%LOG_FILE%"
)
if %EXITCODE% NEQ 0 (
    echo Kurulum tamamlanamadi. Detaylar icin %LOG_FILE% dosyasini kontrol edin.
    pause
    exit /b %EXITCODE%
)
echo Kurulum tamamlandi. Son ayrintilar icin %LOG_FILE% dosyasini inceleyebilirsiniz.
pause
endlocal & exit /b 0
