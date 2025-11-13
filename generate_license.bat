@echo off
setlocal
set "ROOT=%~dp0"
set "SCRIPT=%ROOT%licence\generate_license.py"

if not exist "%SCRIPT%" (
    echo Lisans olusturma betigi bulunamadi: %SCRIPT%
    exit /b 1
)

if not exist "%ROOT%venv" (
    echo Virtual environment not found. Run install.bat first.
    exit /b 1
)

call "%ROOT%venv\Scripts\activate.bat" >nul 2>&1
py "%SCRIPT%" %*
endlocal
