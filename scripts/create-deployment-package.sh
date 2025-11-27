#!/bin/bash

# Create Deployment Package for Attendance Management System
# This script creates a clean zip file ready for VPS deployment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

echo "📦 Creating deployment package for Attendance Management System..."
echo ""

# Variables
CURRENT_DIR=$(pwd)
PROJECT_NAME="attendance-system"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
PACKAGE_NAME="${PROJECT_NAME}_deployment_${TIMESTAMP}.tar.gz"
TEMP_DIR="/tmp/attendance_deployment_${TIMESTAMP}"

# Create temporary directory
echo "📁 Creating temporary directory..."
mkdir -p "$TEMP_DIR"
print_status "Temporary directory created: $TEMP_DIR"

# Copy all project files except unnecessary ones
echo "📋 Copying project files..."
rsync -av --progress "$CURRENT_DIR/" "$TEMP_DIR/" \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='.env.local' \
    --exclude='.env.testing' \
    --exclude='storage/app/public/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/logs/*' \
    --exclude='bootstrap/cache/*' \
    --exclude='public/hot' \
    --exclude='public/storage' \
    --exclude='*.log' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='*.tmp' \
    --exclude='*.temp'

print_status "Project files copied"

# Create storage directories
echo "📁 Creating required storage directories..."
mkdir -p "$TEMP_DIR/storage/app/public"
mkdir -p "$TEMP_DIR/storage/framework/cache/data"
mkdir -p "$TEMP_DIR/storage/framework/sessions"
mkdir -p "$TEMP_DIR/storage/framework/views"
mkdir -p "$TEMP_DIR/storage/logs"
mkdir -p "$TEMP_DIR/bootstrap/cache"
print_status "Storage directories created"

# Create .gitkeep files for empty directories
echo "📝 Creating .gitkeep files..."
touch "$TEMP_DIR/storage/app/public/.gitkeep"
touch "$TEMP_DIR/storage/framework/cache/data/.gitkeep"
touch "$TEMP_DIR/storage/framework/sessions/.gitkeep"
touch "$TEMP_DIR/storage/framework/views/.gitkeep"
touch "$TEMP_DIR/storage/logs/.gitkeep"
touch "$TEMP_DIR/bootstrap/cache/.gitkeep"
print_status ".gitkeep files created"

# Make scripts executable
echo "🔧 Setting script permissions..."
chmod +x "$TEMP_DIR/setup-vps.sh"
chmod +x "$TEMP_DIR/deploy.sh"
if [ -f "$TEMP_DIR/create-deployment-package.sh" ]; then
    chmod +x "$TEMP_DIR/create-deployment-package.sh"
fi
print_status "Script permissions set"

# Create deployment info file
echo "📄 Creating deployment info..."
cat > "$TEMP_DIR/DEPLOYMENT_INFO.txt" << EOF
Attendance Management System - Deployment Package
Created: $(date)
Package: $PACKAGE_NAME

VPS Configuration:
- IP: 168.231.121.98
- Domain: absensi.manufac.id
- OS: Ubuntu 22.04 LTS

Quick Start:
1. Upload this package to VPS:
   scp $PACKAGE_NAME root@168.231.121.98:/root/

2. Connect to VPS and extract:
   ssh root@168.231.121.98
   cd /root
   unzip $PACKAGE_NAME
   cd attendancedev

3. Run VPS setup (takes 15-20 minutes):
   ./setup-vps.sh

4. Deploy application:
   su - deploy
   cd /var/www/attendance-system
   cp -r /root/attendancedev/* .
   ./deploy.sh

5. Configure SSL:
   exit
   certbot --nginx -d absensi.manufac.id

System Requirements Included:
✓ PHP 8.2 with all extensions
✓ PostgreSQL 15
✓ Redis 7
✓ Nginx with security headers
✓ Node.js 20
✓ Composer
✓ Supervisor for queues
✓ UFW Firewall
✓ Fail2ban
✓ SSL/TLS with Certbot

For detailed instructions, see DEPLOYMENT.md
EOF

print_status "Deployment info created"

# Create the tar.gz file
echo "🗜️  Creating compressed package..."
cd "$(dirname "$TEMP_DIR")"
tar -czf "$CURRENT_DIR/$PACKAGE_NAME" "$(basename "$TEMP_DIR")"

# Verify tar.gz file was created
if [ -f "$CURRENT_DIR/$PACKAGE_NAME" ]; then
    FILE_SIZE=$(du -h "$CURRENT_DIR/$PACKAGE_NAME" | cut -f1)
    print_status "Package created successfully: $PACKAGE_NAME ($FILE_SIZE)"
else
    print_error "Failed to create package"
    exit 1
fi

# Clean up temporary directory
echo "🧹 Cleaning up..."
rm -rf "$TEMP_DIR"
print_status "Temporary files cleaned"

# Display final information
echo ""
echo "✅ Deployment package created successfully!"
echo ""
echo "📦 Package Details:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📄 File: $PACKAGE_NAME"
echo "📊 Size: $FILE_SIZE"
echo "📍 Location: $CURRENT_DIR/$PACKAGE_NAME"
echo ""
echo "🚀 Next Steps:"
echo "1. Upload to VPS:"
echo "   scp $PACKAGE_NAME root@168.231.121.98:/root/"
echo ""
echo "2. Connect to VPS and extract:"
echo "   ssh root@168.231.121.98"
echo "   cd /root"
echo "   tar -xzf $PACKAGE_NAME"
echo "   cd attendance_deployment_*"
echo ""
echo "3. Run setup script:"
echo "   ./setup-vps.sh"
echo ""
echo "📝 Files included:"
echo "• All Laravel application files"
echo "• VPS setup script (setup-vps.sh)"
echo "• Deployment script (deploy.sh)"
echo "• Production environment (.env.production)"
echo "• Complete documentation (DEPLOYMENT.md)"
echo "• Deployment instructions (DEPLOYMENT_INFO.txt)"
echo ""
print_warning "The zip file is ready for upload to your VPS!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"