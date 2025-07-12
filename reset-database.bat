@echo off
echo Resetting database and running fixed migrations...
echo.

echo Step 1: Backup existing database...
if exist "database\database.sqlite" (
    copy database\database.sqlite database\database.sqlite.backup
    echo Database backed up to database\database.sqlite.backup
)

echo.
echo Step 2: Delete old database...
if exist "database\database.sqlite" del database\database.sqlite

echo.
echo Step 3: Create fresh database...
type nul > database\database.sqlite

echo.
echo Step 4: Clear all caches...
php artisan config:clear
php artisan cache:clear

echo.
echo Step 5: Run migrations...
php artisan migrate

echo.
echo Step 6: Seed database with sample data...
php artisan db:seed

echo.
echo ===========================================
echo Database reset complete!
echo ===========================================
echo.
echo Now you can run:
echo - php artisan serve
echo.
pause