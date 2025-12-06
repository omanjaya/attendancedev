#!/bin/bash

# Development Setup Script for School Attendance System
# This script sets up the complete development environment

echo "🏫 School Attendance System - Development Setup"
echo "================================================="

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Please run this script from the project root directory"
    exit 1
fi

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check prerequisites
echo "📋 Checking prerequisites..."

if ! command_exists php; then
    echo "❌ PHP is not installed. Please install PHP 8.2 or higher"
    exit 1
fi

if ! command_exists composer; then
    echo "❌ Composer is not installed. Please install Composer"
    exit 1
fi

if ! command_exists node; then
    echo "❌ Node.js is not installed. Please install Node.js 18 or higher"
    exit 1
fi

if ! command_exists npm; then
    echo "❌ npm is not installed. Please install npm"
    exit 1
fi

echo "✅ All prerequisites are installed"

# Setup environment file
echo "⚙️ Setting up environment file..."
if [ ! -f ".env" ]; then
    if [ -f ".env.development" ]; then
        cp .env.development .env
        echo "✅ Environment file created from .env.development"
    elif [ -f ".env.example" ]; then
        cp .env.example .env
        echo "✅ Environment file created from .env.example"
    else
        echo "❌ No environment template found"
        exit 1
    fi
else
    echo "ℹ️ Environment file already exists"
fi

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install

# Install JavaScript dependencies
echo "📦 Installing JavaScript dependencies..."
npm install

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate

# Setup database
echo "🗄️ Setting up database..."
if [ ! -f "database/database.sqlite" ]; then
    touch database/database.sqlite
    echo "✅ SQLite database file created"
fi

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate

# Seed database
echo "🌱 Seeding database with sample data..."
php artisan db:seed

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# Build frontend assets
echo "🏗️ Building frontend assets..."
npm run build

# Set permissions (Linux/Mac only)
if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "win32" ]]; then
    echo "🔐 Setting file permissions..."
    chmod -R 755 storage bootstrap/cache
fi

# Create super admin user
echo "👤 Creating super admin user..."
read -p "Enter admin email (default: admin@school.com): " admin_email
admin_email=${admin_email:-admin@school.com}

read -s -p "Enter admin password (default: password): " admin_password
echo
admin_password=${admin_password:-password}

php artisan make:admin-user --email="$admin_email" --password="$admin_password" --force

echo
echo "🎉 Development setup completed successfully!"
echo
echo "📋 What's next:"
echo "1. Start the development server:"
echo "   composer dev"
echo
echo "2. Or start services individually:"
echo "   php artisan serve (Laravel server)"
echo "   npm run dev (Frontend with hot reload)"
echo "   php artisan queue:work (Background jobs)"
echo
echo "3. Access the application:"
echo "   🌐 Application: http://localhost:8000"
echo "   📚 API Documentation: http://localhost:8000/api/documentation"
echo "   ❤️ Health Check: http://localhost:8000/api/health"
echo
echo "4. Login credentials:"
echo "   📧 Email: $admin_email"
echo "   🔑 Password: $admin_password"
echo
echo "💡 Tip: Run 'composer dev' to start all services at once!"
echo "📖 For more information, check DEVELOPMENT_SETUP_COMPLETE.md"