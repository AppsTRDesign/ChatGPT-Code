@echo off
setlocal

if not exist venv (
    echo [1/3] Sanal ortam olusturuluyor...
    py -3.11 -m venv venv
)

call venv\Scripts\activate.bat

python -m pip install --upgrade pip
pip install -r requirements.txt

echo Kurulum tamamlandi.
pause
