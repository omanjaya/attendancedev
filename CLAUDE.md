# CLAUDE.md

Development guidelines untuk Claude/AI assistant.

## Project Overview

**Monorepo** attendance management system dengan:

- **Backend**: Laravel 12, PHP 8.3, PostgreSQL 16, Redis 7
- **Frontend**: React 19, TypeScript, TanStack Router/Query, Tailwind CSS 4
- **Face Service**: Python DeepFace (ArcFace)

## Quick Commands

```bash
# Docker Development (Recommended)
docker compose up -d                    # Start all services
docker compose watch                    # Hot-reload mode
docker compose logs -f                  # View logs
docker compose down                     # Stop all

# Artisan Commands (dalam container)
docker exec attendancedev-backend php artisan migrate
docker exec attendancedev-backend php artisan db:seed
docker exec attendancedev-backend php artisan tinker

# Frontend Build
cd frontend && npm run build

# Type Check
cd frontend && npx tsc --noEmit
```

## Architecture

### Backend (`backend/`)

```
app/
├── Http/Controllers/Api/   # API controllers (thin)
├── Services/               # Business logic
├── Repositories/           # Data access
├── Models/                 # Eloquent models
└── Policies/               # Authorization
```

### Frontend (`frontend/src/`)

```
├── pages/           # Route pages
├── components/ui/   # shadcn/ui components
├── lib/api/         # API clients
├── stores/          # Zustand stores
└── hooks/           # Custom hooks
```

## Key Conventions

1. **Controllers** → thin, delegate to Services
2. **Services** → business logic, use transactions
3. **Form Requests** → validation (not inline)
4. **shadcn/ui** → component library
5. **Zustand** → global state
6. **TanStack Query** → server state

## Docker Structure

Project: **attendancedev**

```
Services:
├── attendancedev-postgres      # PostgreSQL 16
├── attendancedev-redis         # Redis 7
├── attendancedev-backend       # Laravel (PHP 8.3)
├── attendancedev-frontend      # React (Vite)
├── attendancedev-deepface      # Python DeepFace
├── attendancedev-nginx         # Nginx
└── attendancedev-adminer       # DB Admin
```

## User Roles

| Role | Description |
|------|-------------|
| `super-admin` | Full access |
| `admin` | Manage all except system settings |
| `kepala-sekolah` | View reports, approve leave |
| `guru` | Teaching schedules, own attendance |
| `pegawai` | Own attendance only |

## API Endpoints

Key prefixes:

- `/api/v1/auth/*` - Authentication
- `/api/v1/employees/*` - Employee management
- `/api/v1/attendance/*` - Attendance
- `/api/v1/schedules/*` - Schedules
- `/api/v1/leave-requests/*` - Leave management

## Deployment

```bash
# CI/CD: Push to main branch triggers:
# 1. Build Docker images
# 2. Push to Docker Hub
# 3. Deploy to VPS via SSH

# Manual deployment:
docker compose -f docker-compose.hub.yml pull
docker compose -f docker-compose.hub.yml up -d
docker exec attendancedev-backend php artisan migrate --force
```

## Documentation

| File | Description |
|------|-------------|
| `docs/DEPLOYMENT_GUIDE.md` | Full VPS deployment with CI/CD |
| `docs/SYSTEM_FLOW_ANALYSIS.md` | Complete system flow diagrams |
| `docs/API.md` | API endpoints reference |
| `docs/ARCHITECTURE.md` | System architecture |

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | <superadmin@school.edu> | password |
| Admin | <admin@school.edu> | password |
