@echo off
echo Fixing .env for Windows...
echo.

REM Create backup of current .env
copy .env .env.backup

REM Get current directory in Windows format
set "CURRENT_DIR=%cd%"

echo Current directory: %CURRENT_DIR%
echo.

REM Create Windows .env
echo Creating Windows-compatible .env...

(
echo APP_NAME="Attendance Management System"
echo APP_ENV=local
echo APP_KEY=base64:jsQiflEMEKyxiqjYe2tgz2dnlEzMEQM9ykR7bvM+YZg=
echo APP_DEBUG=true
echo APP_URL=http://localhost:8000
echo.
echo APP_LOCALE=en
echo APP_FALLBACK_LOCALE=en
echo APP_FAKER_LOCALE=en_US
echo.
echo APP_MAINTENANCE_DRIVER=file
echo.
echo PHP_CLI_SERVER_WORKERS=4
echo.
echo BCRYPT_ROUNDS=12
echo.
echo LOG_CHANNEL=stack
echo LOG_STACK=single
echo LOG_DEPRECATIONS_CHANNEL=null
echo LOG_LEVEL=error
echo.
echo # Windows-compatible database path
echo DB_CONNECTION=sqlite
echo DB_DATABASE=%CURRENT_DIR%\database\database.sqlite
echo.
echo SESSION_DRIVER=file
echo SESSION_LIFETIME=120
echo SESSION_ENCRYPT=false
echo SESSION_PATH=/
echo SESSION_DOMAIN=null
echo.
echo BROADCAST_CONNECTION=log
echo FILESYSTEM_DISK=local
echo QUEUE_CONNECTION=sync
echo.
echo CACHE_STORE=file
echo CACHE_PREFIX=
echo.
echo MAIL_MAILER=log
echo MAIL_HOST=127.0.0.1
echo MAIL_PORT=2525
echo MAIL_USERNAME=null
echo MAIL_PASSWORD=null
echo MAIL_ENCRYPTION=null
echo MAIL_FROM_ADDRESS="hello@example.com"
echo MAIL_FROM_NAME="${APP_NAME}"
echo.
echo AWS_ACCESS_KEY_ID=
echo AWS_SECRET_ACCESS_KEY=
echo AWS_DEFAULT_REGION=us-east-1
echo AWS_BUCKET=
echo AWS_USE_PATH_STYLE_ENDPOINT=false
echo.
echo VITE_APP_NAME="${APP_NAME}"
echo PERFORMANCE_MONITOR_ENABLED=false
) > .env

echo.
echo Creating database file...
if not exist "database" mkdir database
type nul > database\database.sqlite

echo.
echo Testing database connection...
php artisan migrate:status

echo.
echo ===========================================
echo .env fixed for Windows!
echo ===========================================
echo.
echo Database path: %CURRENT_DIR%\database\database.sqlite
echo.
echo Now you can run:
echo - php artisan migrate --seed
echo - php artisan serve
echo.
pause