@echo off
echo Installing Node.js dependencies...
echo.

REM Clean install
if exist "node_modules" (
    echo Removing existing node_modules...
    rmdir /s /q node_modules
)

if exist "package-lock.json" (
    echo Removing package-lock.json...
    del package-lock.json
)

echo Installing fresh dependencies...
npm install

echo.
echo Testing Vite installation...
npx vite --version

echo.
echo ===========================================
echo Dependencies installed successfully!
echo ===========================================
echo.
echo You can now run:
echo - npm run dev        (for development)
echo - npm run build      (for production)
echo.
pause