@echo off
setlocal EnableDelayedExpansion
title NoovaPOS — Update Script
color 0A

echo.
echo  ███╗   ██╗ ██████╗  ██████╗ ██╗   ██╗ █████╗ ██████╗  ██████╗ ███████╗
echo  ████╗  ██║██╔═══██╗██╔═══██╗██║   ██║██╔══██╗██╔══██╗██╔═══██╗██╔════╝
echo  ██╔██╗ ██║██║   ██║██║   ██║██║   ██║███████║██████╔╝██║   ██║███████╗
echo  ██║╚██╗██║██║   ██║██║   ██║╚██╗ ██╔╝██╔══██║██╔═══╝ ██║   ██║╚════██║
echo  ██║ ╚████║╚██████╔╝╚██████╔╝ ╚████╔╝ ██║  ██║██║     ╚██████╔╝███████║
echo  ╚═╝  ╚═══╝ ╚═════╝  ╚═════╝   ╚═══╝  ╚═╝  ╚═╝╚═╝      ╚═════╝ ╚══════╝
echo.
echo  Update Script — Multi-Tenant SaaS POS + ERP
echo  ═══════════════════════════════════════════════════════════════════════
echo.

:: ── Locate PHP ────────────────────────────────────────────────────────────
set PHP=php
where php >nul 2>&1
if errorlevel 1 (
    if exist "C:\laragon\bin\php\php8.2*\php.exe" (
        for /d %%i in ("C:\laragon\bin\php\php8.2*") do set PHP=%%i\php.exe
    ) else if exist "C:\laragon\bin\php\php8.1*\php.exe" (
        for /d %%i in ("C:\laragon\bin\php\php8.1*") do set PHP=%%i\php.exe
    ) else (
        echo [ERROR] PHP not found. Make sure Laragon is running or PHP is in PATH.
        pause & exit /b 1
    )
)

:: ── Locate Composer ───────────────────────────────────────────────────────
set COMPOSER=composer
where composer >nul 2>&1
if errorlevel 1 (
    if exist "%APPDATA%\Composer\vendor\bin\composer.bat" (
        set COMPOSER=%APPDATA%\Composer\vendor\bin\composer.bat
    ) else if exist "C:\ProgramData\ComposerSetup\bin\composer.bat" (
        set COMPOSER=C:\ProgramData\ComposerSetup\bin\composer.bat
    ) else (
        echo [ERROR] Composer not found. Install from https://getcomposer.org
        pause & exit /b 1
    )
)

:: ── Step counter ──────────────────────────────────────────────────────────
set STEP=0
set /a TOTAL=9

call :step "Checking Node.js version"
node --version
npm --version

call :step "Updating PHP dependencies (composer update)"
%COMPOSER% update --no-interaction --prefer-dist
if errorlevel 1 (
    echo [WARN] composer update had warnings — check output above.
)

call :step "Clearing old config/route/view caches"
%PHP% artisan optimize:clear

call :step "Running database migrations"
%PHP% artisan migrate --force
if errorlevel 1 (
    echo [ERROR] Migration failed. Fix errors above before continuing.
    pause & exit /b 1
)

call :step "Re-seeding default data (safe — uses firstOrCreate)"
%PHP% artisan db:seed --class=DefaultPermissionsSeeder --force
%PHP% artisan db:seed --class=EnsurePlatformRolesSeeder --force

call :step "Refreshing permission cache"
%PHP% artisan permission:cache-reset

call :step "Installing/updating Node.js packages"
npm install --legacy-peer-deps
if errorlevel 1 (
    echo [WARN] npm install had warnings — check output above.
)

call :step "Building frontend assets"
npm run dev
if errorlevel 1 (
    echo [WARN] Frontend build had warnings.
)

call :step "Final: re-caching config + routes"
%PHP% artisan optimize

echo.
echo  ═══════════════════════════════════════════════════════════════════════
echo  ✓  NoovaPOS update complete!
echo  ─────────────────────────────────────────────────────────────────────
echo  Visit: http://127.0.0.1:8000  ^|  Admin: superadmin@noovapos.com / 123456
echo  ═══════════════════════════════════════════════════════════════════════
echo.
pause
exit /b 0

:step
set /a STEP+=1
echo.
echo  [%STEP%/%TOTAL%] %~1
echo  ───────────────────────────────────────────────────────────────────────
goto :eof
