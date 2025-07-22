#!/bin/bash

# Deployment script for Attendance Management System
# Usage: ./deploy.sh

set -e

echo "🚀 Starting deployment..."

# Variables
APP_DIR="/var/www/attendance-system"
BACKUP_DIR="/var/backups/attendance-system"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Check if running as appropriate user
if [ "$EUID" -eq 0 ]; then 
   print_error "Please don't run this script as root"
   exit 1
fi

# 1. Create backup
echo "📦 Creating backup..."
if [ -d "$APP_DIR" ]; then
    mkdir -p "$BACKUP_DIR"
    tar -czf "$BACKUP_DIR/backup_${TIMESTAMP}.tar.gz" -C "$APP_DIR" .
    print_status "Backup created: backup_${TIMESTAMP}.tar.gz"
else
    print_warning "No existing installation found, skipping backup"
fi

# 2. Pull latest code
echo "📥 Pulling latest code..."
cd "$APP_DIR"
git pull origin main
print_status "Code updated"

# 3. Install/update dependencies
echo "📚 Installing dependencies..."
composer install --no-dev --optimize-autoloader
print_status "PHP dependencies installed"

npm ci --production
print_status "Node dependencies installed"

# 4. Build frontend assets
echo "🔨 Building frontend assets..."
npm run build
print_status "Assets built"

# 5. Setup production environment
echo "⚙️ Setting up production environment..."
cp .env.production .env
php artisan key:generate --force
print_status "Environment configured"

# 6. Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force
print_status "Database migrated"

# 7. Clear and optimize caches
echo "🧹 Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize autoloader
composer dump-autoload --optimize --no-dev

print_status "Application optimized"

# 7. Set proper permissions
echo "🔒 Setting permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
print_status "Permissions set"

# 8. Restart services
echo "🔄 Restarting services..."
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo supervisorctl restart all
print_status "Services restarted"

# 9. Run health check
echo "🏥 Running health check..."
response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health)
if [ $response -eq 200 ]; then
    print_status "Health check passed"
else
    print_error "Health check failed with status code: $response"
    exit 1
fi

# 10. Clear OPcache
echo "🧹 Clearing OPcache..."
curl -s http://localhost/opcache-clear.php > /dev/null
print_status "OPcache cleared"

echo ""
echo "✅ Deployment completed successfully!"
echo "🌐 Application URL: https://your-domain.com"
echo "📊 Deployment timestamp: ${TIMESTAMP}"

# Optional: Send notification
# curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK/URL \
#      -H 'Content-type: application/json' \
#      -d "{\"text\":\"✅ Deployment completed successfully at ${TIMESTAMP}\"}"