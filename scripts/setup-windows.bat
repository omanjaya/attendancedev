@echo off
echo ===========================================
echo  Laravel Attendance System - Windows Setup
echo ===========================================
echo.

REM Check if Node.js is installed
echo Checking Node.js...
node --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Node.js is not installed!
    echo Please download and install Node.js from: https://nodejs.org/
    echo.
    pause
    exit /b 1
) else (
    echo Node.js version:
    node --version
)

REM Check if PHP is installed
echo.
echo Checking PHP...
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP is not installed!
    echo Please install PHP or XAMPP
    echo.
    pause
    exit /b 1
) else (
    echo PHP version:
    php --version | findstr PHP
)

REM Check if Composer is installed
echo.
echo Checking Composer...
composer --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Composer is not installed!
    echo Please download and install Composer from: https://getcomposer.org/
    echo.
    pause
    exit /b 1
) else (
    echo Composer version:
    composer --version
)

echo.
echo ===========================================
echo All prerequisites are installed!
echo ===========================================
echo.

REM Install Node dependencies
echo Installing Node.js dependencies...
npm install

REM Install PHP dependencies
echo.
echo Installing PHP dependencies...
composer install --optimize-autoloader

REM Create storage directories
echo.
echo Setting up storage directories...
if not exist "storage\app\public" mkdir storage\app\public
if not exist "storage\framework\sessions" mkdir storage\framework\sessions
if not exist "storage\framework\views" mkdir storage\framework\views
if not exist "storage\framework\cache" mkdir storage\framework\cache
if not exist "storage\logs" mkdir storage\logs

REM Copy environment file if not exists
if not exist ".env" (
    echo Setting up environment file...
    copy .env.example .env
    php artisan key:generate
)

echo.
echo ===========================================
echo Setup complete!
echo ===========================================
echo.
echo Next steps:
echo 1. Run: optimize-windows.bat
echo 2. Run: serve-windows.bat
echo 3. Open browser: http://127.0.0.1:8000
echo.
pause