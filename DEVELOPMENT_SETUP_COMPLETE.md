# Complete Development Setup Guide

## Prerequisites

### Required Software (Install di Laptop Anda)
```bash
# PHP 8.2 atau lebih tinggi
# Download dari: https://www.php.net/downloads
php --version

# Composer (PHP Package Manager)
# Download dari: https://getcomposer.org/download/
composer --version

# Node.js 18+ dan npm
# Download dari: https://nodejs.org/
node --version
npm --version

# Git
# Download dari: https://git-scm.com/downloads
git --version
```

### Extensions PHP yang Diperlukan
Pastikan extension berikut sudah aktif di `php.ini`:
- pdo_sqlite
- mbstring
- openssl
- tokenizer
- xml
- curl
- zip
- gd
- bcmath

## Installation Steps

### 1. Clone Repository
```bash
git clone <your-github-repo-url>
cd attendance-system
```

### 2. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Generate application key
php artisan key:generate
```

### 3. Database Setup
```bash
# Create SQLite database (for development)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed with sample data
php artisan db:seed
```

### 4. Create Super Admin User
```bash
php artisan make:admin-user
# Follow prompts to create your admin account
```

### 5. Build Frontend Assets
```bash
# Development build with hot reload
npm run dev

# OR production build
npm run build
```

### 6. Start Development Server
```bash
# Unified development command (recommended)
composer dev

# OR individual commands:
# php artisan serve (port 8000)
# php artisan queue:work
# npm run dev
```

## Development Environment Variables

Edit `.env` file with these settings:

```env
APP_NAME="School Attendance System"
APP_ENV=local
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=log
LOG_CHANNEL=single

# Face Recognition Settings
FACE_RECOGNITION_ENABLED=true
FACE_CONFIDENCE_THRESHOLD=0.8

# GPS Settings
GPS_VERIFICATION_ENABLED=true
GPS_RADIUS_METERS=100

# Development Features
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=true
```

## Available Development Commands

### Laravel Commands
```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate
php artisan migrate:fresh --seed

# Create admin user
php artisan make:admin-user

# Clear caches
php artisan optimize:clear

# Run tests
php artisan test

# Generate documentation
php artisan l5-swagger:generate
```

### Frontend Commands
```bash
# Development with hot reload
npm run dev

# Production build
npm run build

# Watch for changes
npm run watch

# Lint code
npm run lint

# Format code
npm run format
```

### Combined Commands
```bash
# Start all development services
composer dev

# Run all tests
composer test

# Build production assets
composer build
```

## Development Tools

### Available URLs
- **Application**: http://localhost:8000
- **API Documentation**: http://localhost:8000/api/documentation
- **Health Check**: http://localhost:8000/api/health

### Default Accounts
After seeding, you can login with:

**Super Admin**:
- Email: admin@school.com
- Password: password

**Manager**:
- Email: manager@school.com
- Password: password

**Employee**:
- Email: employee@school.com
- Password: password

## Key Features

### 1. Dashboard System
- Modern responsive dashboard
- Real-time attendance tracking
- Performance analytics
- Mobile-optimized interface

### 2. Attendance Management
- Face recognition check-in/out
- GPS verification
- Manual attendance entry
- Comprehensive reporting

### 3. Schedule Builder
- Interactive schedule creation
- Teacher assignment system
- Excel export/import
- Conflict detection

### 4. Employee Management
- Complete CRUD operations
- Role-based permissions
- Leave management
- Payroll integration

### 5. Security Features
- Two-factor authentication
- Role-based access control
- Audit logging
- Device management

## Directory Structure

```
attendance-system/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   ├── Services/
│   └── ...
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── database.sqlite
├── resources/
│   ├── js/
│   │   ├── components/
│   │   └── app.ts
│   ├── views/
│   └── css/
├── routes/
├── tests/
└── ...
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `POST /api/auth/refresh` - Refresh token

### Attendance
- `GET /api/attendance` - List attendance records
- `POST /api/attendance/checkin` - Check in
- `POST /api/attendance/checkout` - Check out

### Employees
- `GET /api/employees` - List employees
- `POST /api/employees` - Create employee
- `GET /api/employees/{id}` - Get employee details

### Schedules
- `GET /api/schedules` - List schedules
- `POST /api/schedules` - Create schedule
- `GET /api/schedules/calendar` - Calendar view

## Troubleshooting

### Common Issues

1. **Permission Errors**
```bash
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

2. **Database Issues**
```bash
php artisan migrate:fresh --seed
```

3. **Asset Building Issues**
```bash
npm ci
npm run build
```

4. **Cache Issues**
```bash
php artisan optimize:clear
```

### Logs
- Application logs: `storage/logs/laravel.log`
- Web server logs: Check your server configuration

## Testing

### Run Tests
```bash
# All tests
php artisan test

# Specific test
php artisan test --filter AttendanceTest

# With coverage
php artisan test --coverage
```

### Test Database
Tests use separate SQLite database to avoid affecting development data.

## Production Deployment

### Environment Setup
```bash
# Production environment
APP_ENV=production
APP_DEBUG=false

# Database (PostgreSQL recommended)
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

# Cache and Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
```

### Deployment Commands
```bash
# Install production dependencies
composer install --optimize-autoloader --no-dev

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
```

## Support

For development issues:
1. Check the logs in `storage/logs/`
2. Review the documentation in `docs/`
3. Run `php artisan optimize:clear` to clear caches
4. Ensure all dependencies are installed correctly

## Git Workflow

### Branch Strategy
- `main` - Production ready code
- `develop` - Integration branch
- `feature/*` - Feature branches
- `hotfix/*` - Urgent fixes

### Commit Guidelines
```bash
# Feature commit
git commit -m "feat: add schedule builder with Excel export"

# Bug fix
git commit -m "fix: resolve calendar display issue on mobile"

# Documentation
git commit -m "docs: update development setup guide"
```