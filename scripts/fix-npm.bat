@echo off
echo Fixing npm dependency issue...
echo.

echo Step 1: Removing node_modules and package-lock.json...
if exist "node_modules" rmdir /s /q node_modules
if exist "package-lock.json" del package-lock.json

echo.
echo Step 2: Clearing npm cache...
npm cache clean --force

echo.
echo Step 3: Installing dependencies with legacy peer deps...
npm install --legacy-peer-deps

echo.
echo Step 4: Testing Vite...
npx vite --version

echo.
echo ===========================================
echo Fix complete! Now try: npm run dev
echo ===========================================
pause