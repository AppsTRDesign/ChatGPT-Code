@echo off
setlocal

if not exist venv (
    echo [1/4] Sanal ortam olusturuluyor...
    py -3.11 -m venv venv
)

echo [2/4] Sanal ortam aktif ediliyor...
call venv\Scripts\activate.bat

echo [3/4] Bagimliliklar yukleniyor...
python -m pip install --upgrade pip
python -m pip install -r requirements.txt

echo [4/4] Playwright Chromium indiriliyor...
python -m playwright install chromium

echo Kurulum tamamlandi.
pause
