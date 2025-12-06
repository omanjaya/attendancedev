#!/bin/bash

#===============================================================================
# 🔄 ATTENDANCE SYSTEM - DEPLOYMENT UPDATE SCRIPT
#===============================================================================
#
# Script untuk update aplikasi setelah git pull
# Usage: ./deploy.sh
#
#===============================================================================

set -e

APP_DIR="/var/www/attendance-system"
LOG_DIR="/var/log/attendance-system"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_step() {
    echo -e "${BLUE}▶${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

#-------------------------------------------------------------------------------
# MAIN
#-------------------------------------------------------------------------------

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  🔄 DEPLOYING ATTENDANCE SYSTEM UPDATE${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

cd ${APP_DIR}

# 1. Enable maintenance mode
print_step "Enabling maintenance mode..."
cd backend
php artisan down --render="errors::503" || true
cd ..

# 2. Pull latest code
print_step "Pulling latest code..."
git pull origin main

# 3. Install backend dependencies
print_step "Installing backend dependencies..."
cd backend
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Run migrations
print_step "Running database migrations..."
php artisan migrate --force

# 5. Install frontend dependencies
print_step "Installing frontend dependencies..."
cd ../frontend
npm ci --production

# 6. Build frontend
print_step "Building frontend assets..."
npm run build

# 7. Clear and rebuild caches
print_step "Optimizing application..."
cd ../backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Set permissions
print_step "Setting permissions..."
sudo chown -R deploy:www-data ${APP_DIR}
sudo chmod -R 775 ${APP_DIR}/backend/storage
sudo chmod -R 775 ${APP_DIR}/backend/bootstrap/cache

# 9. Restart services
print_step "Restarting services..."
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo supervisorctl restart all

# 10. Disable maintenance mode
print_step "Disabling maintenance mode..."
php artisan up

echo ""
print_success "Deployment completed successfully!"
echo ""
echo "  📊 Check status: sudo supervisorctl status"
echo "  📝 View logs: tail -f storage/logs/laravel.log"
echo ""