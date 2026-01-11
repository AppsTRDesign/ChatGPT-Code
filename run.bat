@echo off
setlocal

if not exist venv (
    echo Sanal ortam bulunamadi. Lutfen once install.bat calistirin.
    pause
    exit /b 1
)

call venv\Scripts\activate.bat
python -m google_maps_gui.app

pause
