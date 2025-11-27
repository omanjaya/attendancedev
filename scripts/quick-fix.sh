#!/bin/bash

echo "⚡ Quick Performance Fix for Laravel..."
echo ""

# 1. Add performance flag to .env
echo "PERFORMANCE_MONITOR_ENABLED=false" >> .env

# 2. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Optimize composer autoloader
composer dump-autoload -o

# 4. Create optimized cache
php artisan config:cache
php artisan route:cache

echo ""
echo "✅ Quick fix applied!"
echo ""
echo "🚀 Restart your server:"
echo "php artisan serve"
echo ""
echo "⚠️  IMPORTANT: For BEST performance (10-20x faster):"
echo "   Run: ./migrate-to-wsl2.sh"
echo "   This moves your project from Windows to WSL2 filesystem"