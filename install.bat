@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul

rem Resolve repository root and common paths
set "RAW_DIR=%~dp0"
for %%I in ("%RAW_DIR%.") do set "ROOT=%%~fI"
if "%ROOT:~-1%"=="\" set "ROOT=%ROOT:~0,-1%"
set "VENV_DIR=%ROOT%\.venv"
set "REQUIREMENTS=%ROOT%\requirements.txt"
set "LOG_DIR=%ROOT%\logs"
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%" >nul 2>&1
set "LOG_FILE=%LOG_DIR%\install-latest.log"
if exist "%LOG_FILE%" del "%LOG_FILE%" >nul 2>&1

echo Kurulum baslatildi: %DATE% %TIME%>"%LOG_FILE%"
echo Kurulum baslatildi. Ayrintilar "%LOG_FILE%" dosyasina yazilacak.

echo.

echo Python surumu aranıyor...
>>"%LOG_FILE%" echo Python surumu araniyor...
set "PY_LAUNCHER=py"
set "PY_VERSION=-3.13"
%PY_LAUNCHER% %PY_VERSION% --version >>"%LOG_FILE%" 2>&1
if errorlevel 1 (
    echo Python 3.13 bulunamadi, 3.11 denenecek.
    >>"%LOG_FILE%" echo Python 3.13 bulunamadi, 3.11 denenecek.
    set "PY_VERSION=-3.11"
    %PY_LAUNCHER% %PY_VERSION% --version >>"%LOG_FILE%" 2>&1
    if errorlevel 1 (
        echo Python 3.13 ya da 3.11 tespit edilemedi. Kurulum iptal edildi.
        >>"%LOG_FILE%" echo Python 3.13 ya da 3.11 tespit edilemedi. Kurulum iptal edildi.
        goto :error
    )
)

echo Python kullanicisi: %PY_LAUNCHER% %PY_VERSION%
>>"%LOG_FILE%" echo Python kullanicisi: %PY_LAUNCHER% %PY_VERSION%

set "TOTAL_STEPS=6"
set "STEP=1"

call :PrintStep "Python sürümü doğrulanıyor"
%PY_LAUNCHER% %PY_VERSION% --version >>"%LOG_FILE%" 2>&1 || goto :error
set /a STEP+=1

call :PrintStep "Sanal ortam hazirlaniyor"
if exist "%VENV_DIR%\Scripts\python.exe" (
    echo Sanal ortam zaten mevcut, adim atlandi.>>"%LOG_FILE%"
) else (
    %PY_LAUNCHER% %PY_VERSION% -m venv "%VENV_DIR%" >>"%LOG_FILE%" 2>&1 || goto :error
)
set /a STEP+=1

call :PrintStep "Sanal ortam etkinlestiriliyor"
call "%VENV_DIR%\Scripts\activate.bat" >>"%LOG_FILE%" 2>&1 || goto :error
set /a STEP+=1

call :PrintStep "pip guncelleniyor"
python -m pip install --upgrade pip >>"%LOG_FILE%" 2>&1 || goto :error
set /a STEP+=1

call :PrintStep "Bagimliliklar yukleniyor"
python -m pip install -r "%REQUIREMENTS%" >>"%LOG_FILE%" 2>&1 || goto :error
set /a STEP+=1

call :PrintStep "Uygulama dogrulaniyor"
python -m compileall "%ROOT%\app" >>"%LOG_FILE%" 2>&1 || goto :error


echo.
echo Kurulum tamamlandi. Detaylar icin "%LOG_FILE%" dosyasini inceleyebilirsiniz.
>>"%LOG_FILE%" echo Kurulum tamamlandi: %DATE% %TIME%
pause
endlocal
exit /b 0

:PrintStep
set "DESC=%~1"
echo [!STEP!/%TOTAL_STEPS%] !DESC!
echo [!STEP!/%TOTAL_STEPS%] !DESC!>>"%LOG_FILE%"
exit /b 0

:error
echo.
echo Bir hata olustu. Ayrintilar "%LOG_FILE%" dosyasinda bulunabilir.
>>"%LOG_FILE%" echo Kurulum hata ile sonlandi: %DATE% %TIME%
pause
endlocal & exit /b 1
