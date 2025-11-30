# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Monorepo** structure with Laravel 12 API backend and React 19 SPA frontend. Includes face recognition, GPS verification, payroll calculation, and schedule management.

**Stack:**
- **Backend**: Laravel 12 (PHP 8.2+), Sanctum, SQLite (dev) / PostgreSQL (prod)
- **Frontend**: React 19, TypeScript, TanStack Router/Query, Tailwind CSS 4
- **Shared**: TypeScript types and constants

## Monorepo Structure

```
attendancedev/
├── backend/              # Laravel 12 API
│   ├── app/              # Controllers, Services, Repositories, Models
│   ├── routes/           # API routes
│   ├── database/         # Migrations, seeders
│   └── tests/            # PHPUnit tests
├── frontend/             # React SPA
│   ├── src/              # Components, pages, hooks, stores
│   ├── e2e/              # Playwright tests
│   └── public/           # Static assets
└── shared/               # Shared types and constants
    ├── types/            # TypeScript interfaces
    └── constants/        # Shared constants
```

## Development Commands

```bash
# From root directory:
npm run dev                          # Start all services (backend + frontend)
npm run dev:backend                  # Laravel API only (port 8000)
npm run dev:frontend                 # React SPA only (port 5173)

# Building
npm run build                        # Build all packages
npm run build:frontend               # Build frontend only
npm run build:backend                # Install backend deps optimized

# Testing
npm run test                         # Run all tests
npm run test:backend                 # PHPUnit tests
npm run test:frontend                # Vitest tests
npm run test:e2e                     # Playwright E2E tests

# Code Quality
npm run lint                         # ESLint
cd frontend && npm run quality       # Full frontend quality check

# Database
cd backend && php artisan migrate:fresh --seed

# API Documentation
cd backend && php artisan l5-swagger:generate  # /api/documentation
```

## Architecture

### Backend (Service Layer Pattern)

Location: `backend/app/`

```
backend/app/
├── Http/Controllers/    # Thin controllers - delegate to services
│   └── Api/             # API controllers
├── Services/            # Business logic (18+ services)
├── Repositories/        # Data access layer (12+ repos)
├── Models/              # Eloquent models with relationships
├── Http/Requests/       # Form request validation
└── Policies/            # Authorization policies
```

**Key conventions:**
- Controllers only handle HTTP concerns, delegate to services
- Services contain business logic, use transactions for related operations
- Use Form Requests for validation, not inline controller validation
- Use PHP 8.2+ features (enums, readonly properties, named arguments)
- All routes in `backend/routes/` are API-only (no web UI)

### Frontend (React + TypeScript)

Location: `frontend/src/`

```
frontend/src/
├── pages/               # Route pages (TanStack Router)
├── components/          # Reusable React components
│   └── ui/              # shadcn/ui components
├── lib/                 # Utilities and API clients
│   └── api/             # API client layer
├── stores/              # Zustand stores
├── hooks/               # Custom React hooks
└── types/               # Frontend-specific types
```

**Key conventions:**
- Use functional components with hooks
- Zustand for global state, React Query for server state
- TypeScript strict mode enabled
- Import shared types from `@attendance/shared`
- Use shadcn/ui components for consistent UI

### Shared Package

Location: `shared/`

Contains types and constants shared between frontend and backend:
- `types/index.ts` - Common TypeScript interfaces
- `constants/index.ts` - Shared constants and enums
- Imported as `@attendance/shared` in frontend

## Design System

Uses Glassmorphism design with CSS variable-based color system. See `.claude/skills/tailwind-components/` for component patterns.

## Key Features

- **Face Recognition:** MediaPipe/Face-API.js integration for check-in/out
- **GPS Verification:** Radius-based location verification
- **Payroll:** Configurable tax brackets, deductions, overtime in `.env`
- **Scheduling:** Interactive schedule builder with conflict detection
- **Real-time:** Pusher for notifications

## Testing

- **PHPUnit:** `backend/tests/Unit/` and `backend/tests/Feature/` - uses SQLite in-memory
- **Vitest:** `frontend/src/` - unit tests for React components
- **Playwright:** `frontend/e2e/` - end-to-end browser tests
- Run all tests: `npm test` from root

## Environment

Copy `backend/.env.example` to `backend/.env` and configure:
- Database connection (SQLite for dev, PostgreSQL for prod)
- Payroll settings (PAYROLL_* variables)
- Feature flags (ENABLE_FACE_RECOGNITION, ENABLE_GPS_VERIFICATION)
- CORS settings for frontend (already configured for localhost:5173)

## Skills

Project-specific skills are available in `.claude/skills/`:
- `face-recognition` - Face detection, verification, enrollment, and liveness patterns
- `laravel-patterns` - Laravel best practices and service layer patterns
- `tailwind-components` - Reusable component patterns (shadcn/ui based)
- `frontend-design` - UI/UX design guidelines
- `database-performance` - Query optimization strategies

## UI Components

Uses **shadcn/ui** (Tailwind CSS 4 + Radix UI):
- Documentation: https://docs.shadcnblocks.com/blocks/shadcn-cli/
- CLI: `npx shadcn@latest add [component]` from `frontend/` directory
- Tailwind 4 config: https://www.shadcnblocks.com/tailwind/globals.css
- All components in `frontend/src/components/ui/`