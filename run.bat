@echo off
title NoaSoft Autosurf Calistir

if not exist .venv (
    echo .venv klasoru bulunamadi. Once install.bat calistir.
    pause
    exit /b 1
)

call .venv\Scripts\activate
python surf_gui.py
pause
