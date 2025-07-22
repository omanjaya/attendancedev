#!/bin/bash

# Simple Deployment Package Creator for Attendance Management System
# This creates a tar.gz package ready for VPS upload

set -e

echo "📦 Creating deployment package for VPS..."

# Variables
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
PACKAGE_NAME="attendance-system-deployment-${TIMESTAMP}.tar.gz"

echo "📋 Creating package: $PACKAGE_NAME"

# Create tar package excluding unnecessary files
tar -czf "$PACKAGE_NAME" \
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
    --exclude='*.temp' \
    --exclude='*.swp' \
    --exclude='*~' \
    .

# Get file size
FILE_SIZE=$(du -h "$PACKAGE_NAME" | cut -f1)

echo ""
echo "✅ Package created successfully!"
echo ""
echo "📦 Package Details:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📄 File: $PACKAGE_NAME"
echo "📊 Size: $FILE_SIZE"
echo "📍 Location: $(pwd)/$PACKAGE_NAME"
echo ""
echo "🚀 Upload to VPS:"
echo "scp $PACKAGE_NAME root@168.231.121.98:/root/"
echo ""
echo "🔧 Extract on VPS:"
echo "ssh root@168.231.121.98"
echo "cd /root"
echo "tar -xzf $PACKAGE_NAME"
echo "cd attendancedev"
echo "./setup-vps.sh"
echo ""
echo "📝 Package contains:"
echo "• Complete Laravel application"
echo "• VPS setup script (setup-vps.sh)"
echo "• Deployment script (deploy.sh)"
echo "• Production environment (.env.production)"
echo "• Complete documentation"
echo ""
echo "⚠️  Ready for VPS deployment!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"