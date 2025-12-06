# CLAUDE.md

Development guidelines untuk Claude/AI assistant.

## Project Overview

**Monorepo** attendance management system dengan:

- **Backend**: Laravel 12, PHP 8.2+, PostgreSQL, Redis
- **Frontend**: React 19, TypeScript, TanStack Router/Query, Tailwind CSS 4
- **Face Service**: Python DeepFace (ArcFace)

## Commands

```bash
# Development
npm run dev                 # Start all services
npm run dev:backend         # Laravel only (:8000)
npm run dev:frontend        # React only (:5173)

# Testing
npm run test               # All tests
npm run test:backend       # PHPUnit
npm run test:frontend      # Vitest

# Building
npm run build              # Build all
npm run build:frontend     # Production build

# Database
cd backend && php artisan migrate:fresh --seed

# API Docs
cd backend && php artisan l5-swagger:generate
```

## Architecture

### Backend (`backend/`)

```
app/
├── Http/Controllers/Api/   # API controllers
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

## UI Components

Uses **shadcn/ui** (Tailwind CSS 4 + Radix UI):

- CLI: `npx shadcn@latest add [component]` from `frontend/`
- All components in `frontend/src/components/ui/`

## Environment

Key variables in `backend/.env`:

- `DB_CONNECTION=pgsql` (production)
- `DEEPFACE_ENABLED=true`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`

## Face Recognition

DeepFace service (Python) runs on ports 8001-8005:

```bash
cd python-services/face-recognition
./start-cluster.sh
```

## Skills

Project skills in `.claude/skills/`:

- `face-recognition` - Face detection patterns
- `laravel-patterns` - Service layer patterns
- `tailwind-components` - UI patterns

## 🚀 Deployment

### Quick Deploy (Recommended)

**Option 1: Script Installation (No Docker)**

```bash
# Clone repo
git clone https://github.com/omanjaya/attendancedev.git /var/www/attendancedev
cd /var/www/attendancedev

# Run installation script
sudo DOMAIN=yourdomain.com bash scripts/install-vps.sh

# Setup SSL
sudo certbot --nginx -d yourdomain.com
```

**Option 2: Docker Deployment**

```bash
# Clone repo
git clone https://github.com/omanjaya/attendancedev.git /opt/attendancedev
cd /opt/attendancedev

# Setup environment
cp .env.docker.example .env
# Edit .env with your config

# Start services
docker compose -f docker-compose.prod.yml up -d

# Run migrations
docker compose exec backend php artisan migrate --seed
```

### Documentation Files

| File | Purpose |
|------|---------|
| `docs/DEPLOYMENT.md` | Manual deployment guide |
| `docs/DOCKER.md` | Docker deployment guide |
| `docs/PERFORMANCE.md` | Performance optimization |
| `docs/VPS_DEPLOY_PROMPT.md` | Prompt templates for Claude |
| `.env.docker.example` | Environment variables template |

### Default Credentials

After deployment:

- **Admin**: <admin@school.edu> / password
- **Super Admin**: <superadmin@school.edu> / password

### Required Ports

| Port | Service |
|------|---------|
| 80 | HTTP (Nginx) |
| 443 | HTTPS (Nginx) |
| 5432 | PostgreSQL (internal) |
| 6379 | Redis (internal) |
| 8001 | DeepFace (internal) |
