@echo off
echo Setting up database...
echo.

REM Check if PHP is available
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP is not installed or not in PATH!
    echo Please install PHP or XAMPP first.
    pause
    exit /b 1
)

echo Step 1: Creating SQLite database file...
if not exist "database" mkdir database
type nul > database\database.sqlite
echo Database file created: database\database.sqlite

echo.
echo Step 2: Running migrations...
php artisan migrate

echo.
echo Step 3: Seeding database with sample data...
php artisan db:seed

echo.
echo Step 4: Creating storage link...
php artisan storage:link

echo.
echo ===========================================
echo Database setup complete!
echo ===========================================
echo.
echo You can now run:
echo - serve-windows.bat (to start Laravel server)
echo - Or: php artisan serve
echo.
pause