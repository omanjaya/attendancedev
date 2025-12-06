# 🛠️ Development Guide

Panduan lengkap untuk development di local environment.

## Prerequisites

| Software | Versi Minimum | Cek Versi |
|----------|---------------|-----------|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20+ | `node -v` |
| npm | 10+ | `npm -v` |
| PostgreSQL | 15+ | `psql --version` |
| Redis | 7+ (optional) | `redis-server -v` |

## Quick Setup

### 1. Clone & Install Dependencies

```bash
git clone https://github.com/your-repo/attendance-system.git
cd attendance-system

# Install root dependencies
npm install

# Install backend dependencies
cd backend
composer install
cd ..

# Install frontend dependencies (otomatis dari npm install root)
```

### 2. Environment Configuration

```bash
cd backend
cp .env.example .env
php artisan key:generate
```

Edit `backend/.env`:

```env
# Development dengan SQLite (simple)
DB_CONNECTION=sqlite

# ATAU Development dengan PostgreSQL (recommended)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=attendance_dev
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 3. Database Setup

**SQLite (Quick Start):**

```bash
touch backend/database/database.sqlite
php backend/artisan migrate --seed
```

**PostgreSQL (Recommended):**

```bash
# Buat database
createdb attendance_dev

# Jalankan migration
php backend/artisan migrate --seed
```

### 4. Start Development Servers

```bash
# Dari root directory - start semua services
npm run dev
```

**Akses:**

| Service | URL |
|---------|-----|
| Frontend | <http://localhost:5173> |
| API | <http://localhost:8000> |
| API Docs | <http://localhost:8000/api/documentation> |

## Available Commands

### Development

```bash
npm run dev              # Start semua services
npm run dev:backend      # Laravel server saja
npm run dev:frontend     # Vite dev server saja
```

### Building

```bash
npm run build            # Build semua packages
npm run build:frontend   # Build frontend untuk production
npm run build:backend    # Optimize backend dependencies
```

### Testing

```bash
npm run test             # Run semua tests
npm run test:backend     # PHPUnit tests
npm run test:frontend    # Vitest tests
npm run test:e2e         # Playwright E2E tests
```

### Code Quality

```bash
npm run lint             # ESLint check
cd frontend && npm run quality   # Full quality check
```

### Database

```bash
cd backend

# Fresh migration dengan seed
php artisan migrate:fresh --seed

# Run seeder saja
php artisan db:seed

# Create new migration
php artisan make:migration create_xxx_table
```

### API Documentation

```bash
cd backend
php artisan l5-swagger:generate
# Akses: http://localhost:8000/api/documentation
```

## Project Structure

```
attendance-system/
├── backend/                 # Laravel 12 API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/  # API Controllers
│   │   │   └── Requests/         # Form Requests
│   │   ├── Models/               # Eloquent Models
│   │   ├── Services/             # Business Logic
│   │   ├── Repositories/         # Data Access
│   │   └── Policies/             # Authorization
│   ├── database/
│   │   ├── migrations/           # Database migrations
│   │   └── seeders/              # Database seeders
│   ├── routes/
│   │   └── api.php               # API routes
│   └── .env                      # Environment config
│
├── frontend/                # React 19 SPA
│   ├── src/
│   │   ├── pages/                # Route pages
│   │   ├── components/
│   │   │   └── ui/               # shadcn/ui components
│   │   ├── lib/
│   │   │   └── api/              # API clients
│   │   ├── stores/               # Zustand stores
│   │   ├── hooks/                # Custom hooks
│   │   └── types/                # TypeScript types
│   ├── public/                   # Static assets
│   └── vite.config.ts            # Vite config
│
├── python-services/         # Face Recognition Service
│   └── face-recognition/
│       ├── app.py                # Flask app
│       ├── start-cluster.sh      # Start script
│       └── requirements.txt      # Python deps
│
├── shared/                  # Shared TypeScript
│   ├── types/                    # Shared interfaces
│   └── constants/                # Shared constants
│
├── scripts/                 # Automation scripts
│   └── install-vps.sh            # VPS installation
│
└── docs/                    # Documentation
```

## Face Recognition Service (Optional)

Untuk development dengan face recognition:

```bash
cd python-services/face-recognition

# Install dependencies
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# Start single instance
python app.py

# Atau start cluster (5 instances)
./start-cluster.sh
```

## Troubleshooting

### Port Already in Use

```bash
# Check port usage
lsof -i :8000   # Backend
lsof -i :5173   # Frontend

# Kill process
kill -9 <PID>
```

### Database Connection Error

```bash
# PostgreSQL: pastikan service running
sudo systemctl status postgresql
sudo systemctl start postgresql

# SQLite: pastikan file ada
ls backend/database/database.sqlite
```

### CORS Error

Pastikan `CORS_ALLOWED_ORIGINS` di backend `.env` include frontend URL:

```env
CORS_ALLOWED_ORIGINS=http://localhost:5173
```

### API Not Responding

```bash
# Check backend running
curl http://localhost:8000/api/health

# Check logs
tail -f backend/storage/logs/laravel.log
```

## Default Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | <admin@admin.com> | password |

## IDE Setup (VSCode)

Recommended extensions:

- PHP Intelephense
- Laravel Extension Pack
- ES7+ React/Redux/React-Native snippets
- Tailwind CSS IntelliSense
- Prettier
- ESLint
