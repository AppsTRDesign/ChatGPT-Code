@echo off
setlocal
set "SCRIPT_DIR=%~dp0"
if not exist "%SCRIPT_DIR%.venv" (
    python -m venv "%SCRIPT_DIR%.venv"
)
call "%SCRIPT_DIR%.venv\Scripts\activate" >nul 2>&1
python -m pip install --upgrade pip
python -m pip install -r "%SCRIPT_DIR%requirements.txt"
echo Kurulum tamamlandi.
endlocal
