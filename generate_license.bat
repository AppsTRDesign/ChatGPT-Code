@echo off
setlocal
set "ROOT=%~dp0"
set "SCRIPT=%ROOT%licence\generate_license.py"

if not exist "%SCRIPT%" (
    echo Lisans olusturma betigi bulunamadi: %SCRIPT%
    exit /b 1
)

echo Python 3.13 ile lisans olusturuluyor...
py -3.13 "%SCRIPT%" %*
if %ERRORLEVEL% EQU 0 goto :EOF

echo Python 3.13 bulunamadi veya calistirilamadi, Python 3.11 denenecek...
py -3.11 "%SCRIPT%" %*
if %ERRORLEVEL% EQU 0 goto :EOF

echo Python yorumlayicisi bulunamadi. Lueften Python 3.11 veya uzerini kurup PATH'e ekleyin.
exit /b 1

:EOF
endlocal
