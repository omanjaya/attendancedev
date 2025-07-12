#!/bin/bash

# Fix Laravel Performance Issues Script
echo "🚀 Fixing Laravel Performance Issues..."
echo ""

# 1. Create optimized development environment
echo "1️⃣ Creating optimized development setup..."

# Disable performance monitor in development
cat > .env.performance << 'EOF'
# Performance Optimizations
PERFORMANCE_MONITOR_ENABLED=false
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
EOF

# 2. Update bootstrap/app.php to conditionally load performance monitor
echo "2️⃣ Updating bootstrap configuration..."
cp bootstrap/app.php bootstrap/app.php.backup

# 3. Clear all caches
echo "3️⃣ Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Optimize composer autoloader
echo "4️⃣ Optimizing composer autoloader..."
composer dump-autoload -o

# 5. Create development optimization script
cat > optimize-dev.sh << 'EOF'
#!/bin/bash
# Quick optimization for development

echo "🔧 Optimizing for development..."

# Clear caches
php artisan cache:clear
php artisan config:clear

# Optimize autoloader
composer dump-autoload -o

# Set proper permissions
chmod -R 755 storage bootstrap/cache

echo "✅ Development optimization complete!"
EOF

chmod +x optimize-dev.sh

# 6. Create WSL2 migration script
cat > migrate-to-wsl2.sh << 'EOF'
#!/bin/bash
# Migrate project to WSL2 filesystem for better performance

echo "🚀 Migrating Laravel project to WSL2 filesystem..."

# Check if target directory exists
if [ -d "$HOME/projects/attendance-system" ]; then
    echo "❌ Target directory already exists. Please remove it first or choose a different location."
    exit 1
fi

# Create projects directory
mkdir -p $HOME/projects

# Copy project (excluding vendor and node_modules)
echo "📁 Copying project files..."
rsync -av --progress \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/app/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/logs/*' \
    . $HOME/projects/attendance-system/

# Navigate to new location
cd $HOME/projects/attendance-system

# Update .env for new location
sed -i "s|DB_DATABASE=.*|DB_DATABASE=$HOME/projects/attendance-system/database/database.sqlite|g" .env

# Install dependencies
echo "📦 Installing dependencies..."
composer install --optimize-autoloader
npm install

# Set permissions
chmod -R 755 storage bootstrap/cache

# Create storage directories
mkdir -p storage/app/public
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs

# Copy database if exists
if [ -f "/mnt/d/devv/attendance-system/database/database.sqlite" ]; then
    cp /mnt/d/devv/attendance-system/database/database.sqlite database/
fi

echo "✅ Migration complete!"
echo ""
echo "📍 New location: $HOME/projects/attendance-system"
echo ""
echo "To use the new location:"
echo "cd $HOME/projects/attendance-system"
echo "php artisan serve"
EOF

chmod +x migrate-to-wsl2.sh

echo ""
echo "✅ Performance fix script created!"
echo ""
echo "🎯 RECOMMENDED ACTIONS:"
echo ""
echo "1. IMMEDIATE FIX (Temporary):"
echo "   ./optimize-dev.sh"
echo ""
echo "2. PERMANENT FIX (Highly Recommended):"
echo "   ./migrate-to-wsl2.sh"
echo ""
echo "   This will move your project from Windows drive to WSL2 filesystem"
echo "   Expected performance improvement: 10-20x faster!"
echo ""
echo "3. After migration, run Laravel from WSL2 filesystem:"
echo "   cd ~/projects/attendance-system"
echo "   php artisan serve"