@echo off
echo Checking system requirements...
echo.

echo ===========================================
echo  CHECKING REQUIREMENTS
echo ===========================================

REM Check PHP
echo 1. Checking PHP...
php --version >nul 2>&1
if errorlevel 1 (
    echo [❌] PHP is NOT installed
    echo.
    echo INSTALL OPTIONS:
    echo Option 1 - XAMPP (Recommended):
    echo   Download: https://www.apachefriends.org/
    echo   After install, add C:\xampp\php to Windows PATH
    echo.
    echo Option 2 - Standalone PHP:
    echo   Download: https://windows.php.net/download/
    echo   Extract to C:\php and add to Windows PATH
    echo.
) else (
    echo [✅] PHP is installed
    php --version | findstr PHP
)

echo.
echo 2. Checking Composer...
composer --version >nul 2>&1
if errorlevel 1 (
    echo [❌] Composer is NOT installed
    echo Download: https://getcomposer.org/Composer-Setup.exe
) else (
    echo [✅] Composer is installed
    composer --version
)

echo.
echo 3. Checking Node.js...
node --version >nul 2>&1
if errorlevel 1 (
    echo [❌] Node.js is NOT installed
    echo Download: https://nodejs.org/
) else (
    echo [✅] Node.js is installed
    node --version
)

echo.
echo ===========================================
echo  INSTALLATION STATUS
echo ===========================================

REM Check if all are installed
php --version >nul 2>&1 && composer --version >nul 2>&1 && node --version >nul 2>&1
if errorlevel 1 (
    echo [❌] Some requirements are missing
    echo Please install missing components and try again
) else (
    echo [✅] All requirements are installed!
    echo You can now run: setup-database.bat
)

echo.
pause