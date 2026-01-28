# Wasmer Deploy Fix Script for Windows
# This script attempts to work around Windows error 1920

Write-Host "Wasmer Deploy Fix Script" -ForegroundColor Cyan
Write-Host "=========================" -ForegroundColor Cyan
Write-Host ""

# Check if running as administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "WARNING: Not running as Administrator" -ForegroundColor Yellow
    Write-Host "Error 1920 often requires admin privileges. Consider running PowerShell as Administrator." -ForegroundColor Yellow
    Write-Host ""
}

# Check for locked files
Write-Host "Checking for common issues..." -ForegroundColor Cyan

# Check if vendor exists and is accessible
if (Test-Path "vendor") {
    Write-Host "✓ vendor directory exists" -ForegroundColor Green
    try {
        Get-ChildItem "vendor" -ErrorAction Stop | Out-Null
        Write-Host "✓ vendor directory is accessible" -ForegroundColor Green
    } catch {
        Write-Host "✗ vendor directory may be locked: $($_.Exception.Message)" -ForegroundColor Red
    }
} else {
    Write-Host "⚠ vendor directory not found (may need composer install)" -ForegroundColor Yellow
}

# Check if node_modules exists
if (Test-Path "node_modules") {
    Write-Host "✓ node_modules directory exists" -ForegroundColor Green
    Write-Host "⚠ node_modules can be very large and may cause issues" -ForegroundColor Yellow
}

# Check for .git directory
if (Test-Path ".git") {
    Write-Host "✓ .git directory exists" -ForegroundColor Green
    Write-Host "⚠ .git directory contains many files that may not be needed" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Attempting deployment..." -ForegroundColor Cyan
Write-Host ""

# Try deploying
try {
    wasmer deploy
} catch {
    Write-Host ""
    Write-Host "Deployment failed. Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Alternative solutions:" -ForegroundColor Yellow
    Write-Host "1. Run PowerShell as Administrator and try again" -ForegroundColor White
    Write-Host "2. Close all file explorers and IDEs, then try again" -ForegroundColor White
    Write-Host "3. Use GitHub Actions workflow (push to main branch)" -ForegroundColor White
    Write-Host "4. Try WSL if available: cd /mnt/e/Project/family\\ erp && wasmer deploy" -ForegroundColor White
    exit 1
}
