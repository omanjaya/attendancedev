@echo off
echo Starting Laravel on Windows...
echo.

REM Check if vendor directory exists
if not exist "vendor" (
    echo Installing Composer dependencies...
    composer install --optimize-autoloader
)

REM Clear caches first
echo Clearing caches...
php artisan config:clear
php artisan cache:clear
php artisan view:clear

REM Check database
if not exist "database\database.sqlite" (
    echo Creating database...
    type nul > database\database.sqlite
    php artisan migrate --seed
)

REM Start Laravel server
echo.
echo ===========================================
echo Laravel server starting on http://127.0.0.1:8000
echo Press Ctrl+C to stop
echo ===========================================
echo.
php artisan serve