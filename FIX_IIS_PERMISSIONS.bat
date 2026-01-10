@echo off
:: ============================================
:: IIS Permissions Fix - Run as Administrator
:: ============================================

echo ========================================
echo IIS Permissions Fix
echo ========================================
echo.

:: Check for Administrator privileges
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running as Administrator... OK
    echo.
) else (
    echo ERROR: Not running as Administrator!
    echo.
    echo Right-click this file and select "Run as administrator"
    echo.
    pause
    exit
)

:: Run PowerShell script
echo Running PowerShell script...
echo.
powershell.exe -ExecutionPolicy Bypass -File "%~dp0fix_permissions.ps1"

echo.
echo Press any key to exit...
pause >nul
