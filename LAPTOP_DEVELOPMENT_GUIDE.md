# Panduan Development di Laptop

## Langkah-langkah Setup di Laptop Anda

### 1. Install Software yang Diperlukan

#### PHP 8.2+
- Download dari: https://www.php.net/downloads
- Atau gunakan XAMPP: https://www.apachefriends.org/
- Pastikan PHP bisa diakses via command line

#### Composer
- Download dari: https://getcomposer.org/download/
- Install secara global

#### Node.js & npm
- Download dari: https://nodejs.org/ (gunakan LTS version)

#### Git
- Download dari: https://git-scm.com/downloads

### 2. Clone Project dari GitHub

```bash
# Clone repository ini ke laptop Anda
git clone https://github.com/YOUR_USERNAME/attendance-system.git
cd attendance-system
```

### 3. Setup Otomatis (Recommended)

```bash
# Jalankan script setup otomatis
./scripts/dev-setup.sh

# Atau di Windows (PowerShell/Command Prompt):
php scripts/dev-setup.php
```

### 4. Setup Manual (Jika script otomatis tidak berjalan)

```bash
# 1. Copy environment file
cp .env.development .env

# 2. Install dependencies
composer install
npm install

# 3. Generate application key
php artisan key:generate

# 4. Setup database SQLite
touch database/database.sqlite
php artisan migrate
php artisan db:seed

# 5. Create admin user
php artisan make:admin-user

# 6. Build frontend assets
npm run build
```

### 5. Jalankan Development Server

#### Cara 1: Semua Service Sekaligus (Recommended)
```bash
composer dev
```

#### Cara 2: Manual (Terminal Terpisah)
```bash
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Frontend dengan Hot Reload
npm run dev

# Terminal 3: Queue Worker (optional)
php artisan queue:work
```

### 6. Akses Aplikasi

- **Website**: http://localhost:8000
- **Login**: admin@school.com / password
- **API Docs**: http://localhost:8000/api/documentation

## File Penting untuk Development

### Environment (.env)
```env
APP_NAME="School Attendance System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Face Recognition
FACE_RECOGNITION_ENABLED=true
FACE_CONFIDENCE_THRESHOLD=0.8

# GPS Verification
GPS_VERIFICATION_ENABLED=true
GPS_RADIUS_METERS=100
```

### Package.json Scripts
```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "watch": "vite build --watch"
  }
}
```

### Composer Scripts
```json
{
  "scripts": {
    "dev": [
      "Composer\\Config::disableProcessTimeout",
      "@php artisan serve --host=localhost --port=8000 & npm run dev"
    ],
    "test": "php artisan test",
    "build": "npm run build"
  }
}
```

## Struktur Project

```
attendance-system/
├── app/                          # Laravel application code
│   ├── Http/Controllers/         # Controllers
│   ├── Models/                   # Eloquent models
│   ├── Services/                 # Business logic services
│   └── ...
├── database/                     # Database files
│   ├── migrations/               # Database migrations
│   ├── seeders/                  # Database seeders
│   └── database.sqlite           # SQLite database file
├── resources/                    # Frontend resources
│   ├── js/                       # JavaScript/TypeScript files
│   │   ├── components/           # Vue components
│   │   └── app.ts               # Main JS entry point
│   ├── views/                    # Blade templates
│   │   ├── pages/               # Page templates
│   │   └── components/          # Blade components
│   └── css/                      # CSS files
├── routes/                       # Route definitions
├── public/                       # Public assets
├── scripts/                      # Development scripts
├── .env                         # Environment configuration
└── composer.json                # PHP dependencies
```

## Features yang Tersedia

### 1. Dashboard
- Dashboard utama dengan statistik real-time
- Responsive design untuk mobile dan desktop
- Dark/light mode

### 2. Attendance Management
- Check-in/out dengan face recognition
- GPS verification
- Manual attendance entry
- History dan reporting

### 3. Schedule Builder
- Builder jadwal interaktif
- Drag & drop teacher assignment
- Export ke Excel
- Conflict detection

### 4. Employee Management
- CRUD employees lengkap
- Role-based permissions
- Leave management integration

### 5. Security Features
- Two-factor authentication
- Role-based access control
- Audit logging
- Session management

## Development Commands

### Laravel Commands
```bash
# Start server
php artisan serve

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan make:admin-user

# Cache
php artisan optimize:clear
php artisan config:cache

# Testing
php artisan test
```

### Frontend Commands
```bash
# Development with hot reload
npm run dev

# Production build
npm run build

# Watch mode
npm run watch
```

## Troubleshooting

### Common Issues

1. **PHP Extensions Missing**
```bash
# Check loaded extensions
php -m

# Common missing extensions in XAMPP:
# Enable in php.ini:
extension=pdo_sqlite
extension=gd
extension=curl
```

2. **Permission Issues (Linux/Mac)**
```bash
chmod -R 755 storage bootstrap/cache
```

3. **Database Issues**
```bash
# Reset database
php artisan migrate:fresh --seed
```

4. **Asset Building Issues**
```bash
# Clear npm cache
npm cache clean --force
npm install
npm run build
```

### Windows Specific

1. **Path Issues**: Pastikan PHP, Composer, dan Node.js ada di PATH
2. **Symlink Issues**: Jalankan Command Prompt sebagai Administrator
3. **SQLite**: Pastikan SQLite extension aktif di php.ini

## Git Workflow untuk Development

### Setup Git
```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

### Development Workflow
```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes and commit
git add .
git commit -m "feat: add new feature"

# Push to GitHub
git push origin feature/new-feature

# Create Pull Request di GitHub
```

## Tips untuk Development

1. **Use Hot Reload**: Selalu gunakan `npm run dev` untuk frontend development
2. **Database Reset**: Gunakan `php artisan migrate:fresh --seed` untuk reset data
3. **Clear Cache**: Jalankan `php artisan optimize:clear` jika ada issue aneh
4. **Check Logs**: Monitor `storage/logs/laravel.log` untuk debugging
5. **API Testing**: Gunakan Postman atau API docs di `/api/documentation`

## Support

Jika ada masalah:
1. Check error di `storage/logs/laravel.log`
2. Jalankan `php artisan optimize:clear`
3. Pastikan semua dependencies ter-install dengan benar
4. Check file `.env` sudah benar

Happy coding! 🚀