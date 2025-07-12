@echo off
echo Optimizing Laravel for Windows...
echo.

REM Clear all caches
echo Clearing caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

REM Optimize composer
echo.
echo Optimizing Composer autoloader...
composer dump-autoload -o

REM Cache configuration
echo.
echo Creating optimized caches...
php artisan config:cache
php artisan route:cache

echo.
echo Optimization complete!
echo Run "serve-windows.bat" to start the server
pause