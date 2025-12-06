#!/bin/bash
# ============================================
# Laravel Performance Optimization Script
# ============================================
# Run this after deployment to optimize Laravel
# Usage: ./scripts/optimize-laravel.sh
# ============================================

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}🚀 Optimizing Laravel for Production...${NC}"

# Determine if running in Docker
if [ -f /.dockerenv ]; then
    PHP_CMD="php"
else
    # Running on host, use docker exec
    PHP_CMD="docker-compose exec -T backend php"
fi

# 1. Clear all caches first
echo -e "${GREEN}1. Clearing existing caches...${NC}"
$PHP_CMD artisan cache:clear
$PHP_CMD artisan config:clear
$PHP_CMD artisan route:clear
$PHP_CMD artisan view:clear

# 2. Optimize autoloader
echo -e "${GREEN}2. Optimizing Composer autoloader...${NC}"
if [ -f /.dockerenv ]; then
    composer dump-autoload --optimize --classmap-authoritative
else
    docker-compose exec -T backend composer dump-autoload --optimize --classmap-authoritative
fi

# 3. Cache configuration
echo -e "${GREEN}3. Caching configuration...${NC}"
$PHP_CMD artisan config:cache

# 4. Cache routes
echo -e "${GREEN}4. Caching routes...${NC}"
$PHP_CMD artisan route:cache

# 5. Cache views
echo -e "${GREEN}5. Caching views...${NC}"
$PHP_CMD artisan view:cache

# 6. Cache events
echo -e "${GREEN}6. Caching events...${NC}"
$PHP_CMD artisan event:cache || echo "Event caching not available"

# 7. Optimize icons (if using icon libraries)
echo -e "${GREEN}7. Optimizing icons...${NC}"
$PHP_CMD artisan icons:cache 2>/dev/null || echo "Icons not available"

# 8. Clear expired password reset tokens
echo -e "${GREEN}8. Cleaning up expired tokens...${NC}"
$PHP_CMD artisan auth:clear-resets || echo "No resets to clear"

# 9. Optimize database
echo -e "${GREEN}9. Running database optimizations...${NC}"
if [ -f /.dockerenv ]; then
    # Inside Docker
    php artisan db:seed --class=PerformanceIndexSeeder --force 2>/dev/null || echo "Performance indexes already exist"
else
    docker-compose exec -T backend php artisan db:seed --class=PerformanceIndexSeeder --force 2>/dev/null || echo "Performance indexes already exist"
fi

# 10. Warm up caches
echo -e "${GREEN}10. Warming up caches...${NC}"
$PHP_CMD artisan schedule:list > /dev/null 2>&1 || true
$PHP_CMD artisan about > /dev/null 2>&1 || true

echo ""
echo -e "${GREEN}✅ Laravel Optimization Complete!${NC}"
echo ""
echo "Performance tips:"
echo "  - Ensure Redis is configured for sessions and cache"
echo "  - Enable OPcache in production"
echo "  - Use database connection pooling if high traffic"
echo "  - Monitor slow queries with Laravel Debugbar (dev) or Telescope"
echo ""
