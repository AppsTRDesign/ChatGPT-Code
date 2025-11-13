@echo off
setlocal

if not exist venv (
    echo Lutfen once install.bat calistirarak sanal ortami kurun.
    exit /b 1
)

call venv\Scripts\activate.bat
python -m licence.generate_license %*
set EXIT_CODE=%ERRORLEVEL%
echo.
echo Islemi tamamladiniz. Bu pencereyi kapatmak icin bir tusa basin.
pause >nul
endlocal & exit /b %EXIT_CODE%
