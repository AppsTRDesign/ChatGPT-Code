@echo off
title NoaSoft Autosurf Kurulum
color 0A

echo [1/7] Python sanal ortam olusturuluyor...
py -3.11 -m venv .venv

if %ERRORLEVEL% neq 0 (
    echo HATA: Python bulunamadi veya venv olusturulamadi!
    pause
    exit /b
)

echo [2/7] Sanal ortam aktif ediliyor...
call .venv\Scripts\activate

echo [3/7] Pip stabil surume cekiliyor (23.2.1)...
py -m pip install --upgrade pip

echo [4/7] Pip cache temizleniyor...
py -m pip cache purge

echo [5/7] Gereksinimler yukleniyor (Qt6.5 garanti)...
py -m pip install -r requirements.txt --no-cache-dir

if %ERRORLEVEL% neq 0 (
    echo HATA: requirements kurulurken sorun olustu!
    pause
    exit /b
)

echo [6/7] Playwright tarayicilari yukleniyor...
playwright install

if %ERRORLEVEL% neq 0 (
    echo HATA: Playwright tarayici kurulumu basarisiz!
    pause
    exit /b
)

echo [7/7] Kurulum tamamlandi.
echo Proje hazir!

pause
exit
