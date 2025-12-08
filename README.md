# 📋 Sistem Absensi Modern (Attendance System)

**Attendance Management System** dengan Face Recognition, GPS Verification, dan Payroll Calculation.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19.x-blue.svg)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-blue.svg)](https://www.typescriptlang.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-blue.svg)](https://www.postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://www.docker.com)

## 🚀 Quick Start (Docker - Recommended)

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

```bash
# Clone repository
git clone https://github.com/your-repo/attendancedev.git
cd attendancedev

# Setup environment
cp .env.docker.example .env
# Edit .env sesuai kebutuhan

# Start semua services
docker compose up -d

# Run migrations
docker exec attendancedev-backend php artisan migrate --seed
```

### Access

- 🌐 **Frontend**: <http://localhost:5173>
- 🔧 **API**: <http://localhost/api>
- 🗄️ **Adminer (DB)**: <http://localhost:8080>

### Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | <admin@school.edu> | password |

## 🐳 Docker Commands

```bash
# Start development
docker compose up -d

# Hot-reload development
docker compose watch

# View logs
docker compose logs -f

# Stop all
docker compose down

# Production build
docker compose -f docker-compose.prod.yml up -d
```

## 🖥️ VPS Deployment

Untuk deploy ke VPS dengan CI/CD, lihat [📘 Deployment Guide](docs/DEPLOYMENT_GUIDE.md)

```bash
# Quick VPS setup
curl -sSL https://raw.githubusercontent.com/USER/attendancedev/main/scripts/vps-setup.sh | sudo bash
```

## 📁 Project Structure

```
attendancedev/
├── backend/           # Laravel 12 API
├── frontend/          # React 19 SPA
├── python-services/   # DeepFace Recognition Service
├── docker/            # Docker configurations
├── scripts/           # Automation scripts
└── docs/              # Documentation
```

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🔐 **Multi-Role Auth** | Admin, Kepala Sekolah, Guru, Pegawai |
| 👤 **Face Recognition** | DeepFace ArcFace (99.82% accuracy) |
| 📍 **GPS Verification** | Radius-based location check |
| 📅 **Schedule Management** | Monthly & weekly schedules |
| 📊 **Reports** | Monthly recap, Excel/PDF export |
| 🏖️ **Leave Management** | Request, approve, track balance |

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [Development Guide](docs/DEVELOPMENT.md) | Setup development environment |
| [Deployment Guide](docs/DEPLOYMENT_GUIDE.md) | Deploy to VPS with Docker Hub CI/CD |
| [Architecture](docs/ARCHITECTURE.md) | System architecture overview |
| [API Reference](docs/API.md) | API endpoints documentation |
| [System Flow](docs/SYSTEM_FLOW_ANALYSIS.md) | Complete system flow diagrams |
| [Troubleshooting](docs/TROUBLESHOOTING.md) | Common issues & solutions |

## 🔧 Scripts

| Script | Description |
|--------|-------------|
| `scripts/deploy.sh` | Docker deployment helper |
| `scripts/vps-setup.sh` | Quick VPS initial setup |
| `scripts/install-vps-full.sh` | Full VPS installation (non-Docker) |

## 🛡️ Tech Stack

- **Backend**: Laravel 12, PHP 8.3, PostgreSQL 16, Redis 7
- **Frontend**: React 19, TypeScript 5, TailwindCSS 4, TanStack Query
- **Face Recognition**: Python DeepFace (ArcFace)
- **Infrastructure**: Docker, Nginx, GitHub Actions

## 📄 License

MIT License
# CI/CD Test - Mon Dec  8 06:36:56 UTC 2025
