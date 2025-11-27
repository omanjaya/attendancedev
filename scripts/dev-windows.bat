@echo off
echo Starting Laravel Development Environment on Windows...
echo.

REM Check if node_modules exists
if not exist "node_modules" (
    echo Installing Node dependencies...
    npm install
)

REM Start Laravel server in background
echo Starting Laravel server...
start "Laravel Server" cmd /k "php artisan serve"

REM Wait a moment for server to start
timeout /t 3 /nobreak >nul

REM Start Vite dev server
echo Starting Vite development server...
echo.
echo ===========================================
echo  Development servers are now running:
echo  - Laravel: http://127.0.0.1:8000
echo  - Vite: http://127.0.0.1:5173
echo ===========================================
echo.
npm run dev