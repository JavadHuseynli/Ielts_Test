# ============================================
# IIS Permissions Fix Script
# Run as Administrator!
# ============================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "IIS Permissions Fix Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    pause
    exit
}

# Website path - UPDATE THIS PATH!
$websitePath = "C:\inetpub\wwwroot\imtahan ingilis"

Write-Host "Website Path: $websitePath" -ForegroundColor Green
Write-Host ""

# Check if path exists
if (-not (Test-Path $websitePath)) {
    Write-Host "ERROR: Path not found: $websitePath" -ForegroundColor Red
    Write-Host "Please update the path in this script!" -ForegroundColor Yellow
    pause
    exit
}

Write-Host "Step 1: Setting folder permissions..." -ForegroundColor Yellow
try {
    # Give permissions to IIS_IUSRS
    icacls $websitePath /grant "IIS_IUSRS:(OI)(CI)(RX)" /T
    Write-Host "  [OK] IIS_IUSRS permissions set" -ForegroundColor Green

    # Give permissions to IUSR
    icacls $websitePath /grant "IUSR:(OI)(CI)(RX)" /T
    Write-Host "  [OK] IUSR permissions set" -ForegroundColor Green

    # Give permissions to IIS AppPool
    icacls $websitePath /grant "IIS AppPool\DefaultAppPool:(OI)(CI)(RX)" /T
    Write-Host "  [OK] IIS AppPool permissions set" -ForegroundColor Green
} catch {
    Write-Host "  [WARNING] Some permissions may have failed" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Step 2: Setting web.config permissions..." -ForegroundColor Yellow
$webConfigPath = Join-Path $websitePath "web.config"

if (Test-Path $webConfigPath) {
    try {
        icacls $webConfigPath /grant "IIS_IUSRS:R"
        icacls $webConfigPath /grant "IUSR:R"
        Write-Host "  [OK] web.config permissions set" -ForegroundColor Green
    } catch {
        Write-Host "  [WARNING] web.config permissions failed" -ForegroundColor Yellow
    }
} else {
    Write-Host "  [INFO] web.config not found, skipping..." -ForegroundColor Cyan
}

Write-Host ""
Write-Host "Step 3: Setting uploads folder permissions..." -ForegroundColor Yellow
$uploadsPath = Join-Path $websitePath "uploads"

if (-not (Test-Path $uploadsPath)) {
    Write-Host "  [INFO] Creating uploads folder..." -ForegroundColor Cyan
    New-Item -ItemType Directory -Path $uploadsPath -Force | Out-Null
}

try {
    # Full control for uploads folder
    icacls $uploadsPath /grant "IIS_IUSRS:(OI)(CI)(M)"
    icacls $uploadsPath /grant "IUSR:(OI)(CI)(M)"
    icacls $uploadsPath /grant "IIS AppPool\DefaultAppPool:(OI)(CI)(M)"
    Write-Host "  [OK] Uploads folder permissions set (Write enabled)" -ForegroundColor Green
} catch {
    Write-Host "  [WARNING] Uploads folder permissions failed" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Step 4: Restarting IIS..." -ForegroundColor Yellow
try {
    iisreset /restart | Out-Null
    Write-Host "  [OK] IIS restarted successfully" -ForegroundColor Green
} catch {
    Write-Host "  [WARNING] IIS restart failed, try manually: iisreset" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "DONE! Permissions have been set." -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Open IIS Manager" -ForegroundColor White
Write-Host "2. Select your website" -ForegroundColor White
Write-Host "3. Basic Settings -> Test Settings (should be green)" -ForegroundColor White
Write-Host "4. Try accessing: http://172.18.250.21:7777/" -ForegroundColor White
Write-Host ""

# Optional: Use simple web.config
Write-Host "If still having issues, use simplified web.config:" -ForegroundColor Yellow
Write-Host "1. Rename current web.config to web.config.backup" -ForegroundColor White
Write-Host "2. Rename web.config.simple to web.config" -ForegroundColor White
Write-Host ""

pause
