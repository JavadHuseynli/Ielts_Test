@echo off
:: ============================================
:: IIS QUICK FIX - Run as Administrator!
:: ============================================

title IIS Permissions Fix

color 0A
echo ========================================
echo IIS PERMISSIONS FIX
echo ========================================
echo.

:: Check Administrator
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    color 0C
    echo ERROR: Not running as Administrator!
    echo.
    echo Right-click this file and select:
    echo "Run as administrator"
    echo.
    pause
    exit
)

echo Running as Administrator... OK
echo.

:: Run PowerShell script
echo Starting fix process...
echo.
powershell.exe -ExecutionPolicy Bypass -File "%~dp0FIX_IIS_EASY.ps1"

echo.
echo ========================================
echo.
pause
