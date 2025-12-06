# 📋 Sistem Absensi Modern

**Attendance Management System** dengan Face Recognition, GPS Verification, dan Payroll Calculation.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19.x-blue.svg)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-blue.svg)](https://www.typescriptlang.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-blue.svg)](https://www.postgresql.org)

## 🚀 Quick Start (Development)

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+
- PostgreSQL 15+ (atau SQLite untuk development cepat)

### Installation

```bash
# Clone repository
git clone https://github.com/your-repo/attendance-system.git
cd attendance-system

# Install all dependencies
npm install
cd backend && composer install && cd ..

# Setup environment
cp backend/.env.example backend/.env
php backend/artisan key:generate

# Database (SQLite untuk development cepat)
touch backend/database/database.sqlite
php backend/artisan migrate --seed
```

### Start Development (Satu Command!)

```bash
# Start SEMUA services sekaligus (Backend + Frontend + DeepFace)
./scripts/start-all.sh

# Atau gunakan npm
npm run dev
```

**Commands lainnya:**

```bash
./scripts/start-all.sh stop     # Stop semua services
./scripts/start-all.sh status   # Cek status services
./scripts/start-all.sh restart  # Restart semua
./scripts/start-all.sh logs     # Lihat semua logs
```

**Access:**

- 🌐 Frontend: <http://localhost:5173>
- 🔧 API: <http://localhost:8000>
- 📚 API Docs: <http://localhost:8000/api/documentation>

## 🖥️ VPS Deployment (Production)

Untuk deploy ke VPS, lihat [📘 Deployment Guide](docs/DEPLOYMENT.md) atau jalankan:

```bash
# Di VPS setelah clone repository
sudo bash scripts/install-vps.sh
```

## 📁 Project Structure

```
attendance-system/
├── backend/          # Laravel 12 API
├── frontend/         # React 19 SPA
├── python-services/  # DeepFace Recognition Service
├── shared/           # Shared TypeScript types
├── scripts/          # Automation scripts
└── docs/             # Documentation
```

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🔐 **Authentication** | Multi-role auth dengan 2FA |
| 👤 **Face Recognition** | DeepFace ArcFace (99.82% accuracy) |
| 📍 **GPS Verification** | Radius-based location check |
| 📅 **Schedule Management** | Dynamic scheduling system |
| 💰 **Payroll** | Automatic calculation dengan tax brackets |
| 📊 **Reports** | Excel/PDF export |
| 🔔 **Notifications** | Real-time dengan Pusher |

## 📚 Documentation

- [🛠️ Development Guide](docs/DEVELOPMENT.md)
- [🚀 Deployment Guide](docs/DEPLOYMENT.md)
- [🏗️ Architecture](docs/ARCHITECTURE.md)
- [🔧 API Reference](docs/API.md)
- [❓ Troubleshooting](docs/TROUBLESHOOTING.md)

## 🔑 Default Accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | <admin@admin.com> | password |

## 📄 License

MIT License - lihat [LICENSE](LICENSE) untuk detail.
