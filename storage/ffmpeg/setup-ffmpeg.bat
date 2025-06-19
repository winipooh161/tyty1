@echo off
echo Добавление FFmpeg в системную переменную PATH
echo =============================================
echo.
echo Проверка привилегий администратора...
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo Ошибка: Запустите этот скрипт от имени администратора!
    echo.
    pause
    exit /b 1
)
echo.
echo Добавление "%binPath%" в PATH...
setx PATH "%PATH%;C:\OSPanel\domains\tyty\storage\ffmpeg\bin" /M
if %errorLevel% neq 0 (
    echo Ошибка при добавлении пути в переменную PATH!
) else (
    echo FFmpeg успешно добавлен в PATH.
    echo Перезапустите командную строку или приложение, чтобы изменения вступили в силу.
)
echo.
pause
