@echo off
setlocal
set "SCRIPT_DIR=%~dp0"
if not exist "%SCRIPT_DIR%scripts\install.ps1" (
    echo Kurulum betigi bulunamadi.
    exit /b 1
)
powershell -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%scripts\install.ps1" -Root "%SCRIPT_DIR%"
if errorlevel 1 (
    echo Kurulum tamamlanamadi.
    exit /b 1
)
echo Kurulum tamamlandi.
endlocal
