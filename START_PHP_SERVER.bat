@echo off
:: ============================================
:: PHP Built-in Development Server
:: Port: 7777 (Network accessible)
:: ============================================

title IELTS Test System - PHP Server

echo ========================================
echo IELTS Test System - PHP Development Server
echo ========================================
echo.
echo Server will start on:
echo   - http://localhost:7777/
echo   - http://172.18.250.21:7777/
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

:: Check if php.ini exists
if exist "php.ini" (
    echo [OK] Using custom php.ini
    php -c php.ini -S 0.0.0.0:7777
) else (
    echo [WARNING] php.ini not found, using default PHP settings
    echo [INFO] Upload limit may be limited to 2MB
    echo.
    php -S 0.0.0.0:7777
)

pause
