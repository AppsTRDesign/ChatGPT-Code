@echo off
setlocal
set "SCRIPT_DIR=%~dp0"
if not exist "%SCRIPT_DIR%.venv" (
    echo Virtual environment not found. Run install.bat first.
    exit /b 1
)
call "%SCRIPT_DIR%.venv\Scripts\activate" >nul 2>&1
python -m app.main
endlocal
