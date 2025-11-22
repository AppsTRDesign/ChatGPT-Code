@echo off
title NoaSoft Autosurf Kurulum
color 0A

echo [1/6] Python sanal ortam olusturuluyor...
py -m venv .venv

if %ERRORLEVEL% neq 0 (
    echo HATA: Python bulunamadi veya venv olusturulamadi!
    pause
    exit /b
)

echo [2/6] Sanal ortam aktif ediliyor...
call .venv\Scripts\activate

echo [3/6] Pip guncelleniyor...
py -m pip install --upgrade pip

echo [4/6] Gereksinimler yukleniyor...
py -m pip install -r requirements.txt

if %ERRORLEVEL% neq 0 (
    echo HATA: requirements kurulurken sorun olustu!
    pause
    exit /b
)

echo [5/6] Playwright tarayicilari yukleniyor...
playwright install

if %ERRORLEVEL% neq 0 (
    echo HATA: Playwright tarayici kurulumu basarisiz!
    pause
    exit /b
)

echo [6/6] Kurulum tamamlandi.
echo Proje hazir!

pause
exit
