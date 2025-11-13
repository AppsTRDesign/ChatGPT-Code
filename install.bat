@echo off
setlocal
set "RAW_SCRIPT_DIR=%~dp0"
for %%I in ("%RAW_SCRIPT_DIR%.") do set "SCRIPT_DIR=%%~fI"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "LOG_DIR=%SCRIPT_DIR%\logs"
if not exist "%LOG_DIR%" (
    mkdir "%LOG_DIR%" >nul 2>&1
)
set "LOG_FILE=%LOG_DIR%\install-latest.log"
set "POWERSHELL=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
if not exist "%POWERSHELL%" set "POWERSHELL=powershell"
set "PS_SCRIPT=%SCRIPT_DIR%\scripts\install.ps1"
if not exist "%PS_SCRIPT%" (
    echo Kurulum betigi bulunamadi.
    echo Ayrintilar %LOG_FILE% dosyasina yazildi.
    echo Kurulum betigi bulunamadi.>"%LOG_FILE%"
    pause
    exit /b 1
)
echo Kurulum baslatiliyor...
"%POWERSHELL%" -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%PS_SCRIPT%" -Root "%SCRIPT_DIR%"
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
