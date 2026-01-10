# ============================================
# IIS Permissions Fix - Simple Version
# Run as Administrator!
# ============================================

Write-Host "========================================" -ForegroundColor Green
Write-Host "IIS PERMISSIONS FIX - SIMPLE VERSION" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Check Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "ERROR: Run as Administrator!" -ForegroundColor Red
    pause
    exit
}

# Get website path from user
Write-Host "Enter website path:" -ForegroundColor Yellow
Write-Host "Example: C:\inetpub\wwwroot\ingilis imtahan" -ForegroundColor Cyan
$websitePath = Read-Host "Path"

if (-not $websitePath) {
    $websitePath = "C:\inetpub\wwwroot\ingilis imtahan"
    Write-Host "Using default: $websitePath" -ForegroundColor Cyan
}

if (-not (Test-Path $websitePath)) {
    Write-Host "ERROR: Path not found!" -ForegroundColor Red
    pause
    exit
}

Write-Host ""
Write-Host "Working on: $websitePath" -ForegroundColor Green
Write-Host ""

# Step 1: Backup web.config
Write-Host "[1/6] Backing up web.config..." -ForegroundColor Yellow
$webConfigPath = Join-Path $websitePath "web.config"
if (Test-Path $webConfigPath) {
    Copy-Item $webConfigPath "$webConfigPath.backup" -Force
    Write-Host "      Backup created: web.config.backup" -ForegroundColor Green
}

# Step 2: Create simple web.config
Write-Host "[2/6] Creating simple web.config..." -ForegroundColor Yellow
$simpleConfig = @'
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <defaultDocument>
            <files>
                <add value="index.php" />
            </files>
        </defaultDocument>
        <staticContent>
            <mimeMap fileExtension=".mp3" mimeType="audio/mpeg" />
            <mimeMap fileExtension=".wav" mimeType="audio/wav" />
        </staticContent>
        <security>
            <requestFiltering>
                <requestLimits maxAllowedContentLength="31457280" />
            </requestFiltering>
        </security>
        <directoryBrowse enabled="false" />
    </system.webServer>
</configuration>
'@

Set-Content -Path $webConfigPath -Value $simpleConfig -Encoding UTF8
Write-Host "      Simple web.config created" -ForegroundColor Green

# Step 3: Set folder permissions
Write-Host "[3/6] Setting folder permissions..." -ForegroundColor Yellow
icacls $websitePath /grant "IIS_IUSRS:(OI)(CI)(RX)" /T /Q
icacls $websitePath /grant "IUSR:(OI)(CI)(RX)" /T /Q
Write-Host "      Folder permissions set" -ForegroundColor Green

# Step 4: Set web.config permissions
Write-Host "[4/6] Setting web.config permissions..." -ForegroundColor Yellow
icacls $webConfigPath /grant "IIS_IUSRS:R" /Q
icacls $webConfigPath /grant "IUSR:R" /Q
Write-Host "      web.config permissions set" -ForegroundColor Green

# Step 5: Create and set uploads folder
Write-Host "[5/6] Setting uploads folder..." -ForegroundColor Yellow
$uploadsPath = Join-Path $websitePath "uploads"
if (-not (Test-Path $uploadsPath)) {
    New-Item -ItemType Directory -Path $uploadsPath -Force | Out-Null
}
icacls $uploadsPath /grant "IIS_IUSRS:(OI)(CI)(M)" /Q
icacls $uploadsPath /grant "IUSR:(OI)(CI)(M)" /Q
Write-Host "      Uploads folder ready" -ForegroundColor Green

# Step 6: Restart IIS
Write-Host "[6/6] Restarting IIS..." -ForegroundColor Yellow
iisreset /restart | Out-Null
Start-Sleep -Seconds 3
Write-Host "      IIS restarted" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "DONE! Try accessing your website now." -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "URL: http://172.18.250.21:7777/" -ForegroundColor Cyan
Write-Host ""
Write-Host "If still not working:" -ForegroundColor Yellow
Write-Host "1. Check IIS Manager -> Site -> Basic Settings" -ForegroundColor White
Write-Host "2. Application Pool -> Advanced Settings -> Identity = ApplicationPoolIdentity" -ForegroundColor White
Write-Host "3. Restart computer (sometimes needed)" -ForegroundColor White
Write-Host ""

pause
