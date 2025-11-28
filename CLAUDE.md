# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 12 + Vue.js 3 attendance management system with face recognition, GPS verification, payroll calculation, and schedule management.

**Stack:** Laravel 12 (PHP 8.2+), Vue.js 3 (Composition API + TypeScript), Vite, Tailwind CSS, Pinia, SQLite (dev) / PostgreSQL (prod)

## Development Commands

```bash
# Start all development services (recommended)
composer dev

# Individual services
php artisan serve                    # Laravel server (port 8000)
npm run dev                          # Vite dev server with HMR

# Testing
php artisan test                     # All PHP tests
php artisan test --filter=TestName   # Single test
npm run test                         # Vue component tests (Vitest)
npm run test:run                     # Single test run

# Code Quality
npm run quality                      # Full check: type-check, lint, format, test
npm run lint:fix                     # ESLint auto-fix
npm run format                       # Prettier formatting

# Database
php artisan migrate:fresh --seed     # Reset database with seeders

# API Documentation
php artisan l5-swagger:generate      # Generate OpenAPI docs at /api/documentation
```

## Architecture

### Backend (Service Layer Pattern)

```
app/
├── Http/Controllers/    # Thin controllers - delegate to services
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
- Use PHP 8.1+ enums for status fields

### Frontend (Composition API)

```
resources/js/
├── components/          # Vue components by feature (Face/, Schedule/, Security/)
├── composables/         # Reusable composition functions
├── stores/              # Pinia stores
├── services/            # API client layer
├── types/               # TypeScript interfaces
└── tests/               # Vitest component tests
```

**Key conventions:**
- Use `<script setup lang="ts">` for all components
- Define typed props with `defineProps<Props>()` and defaults with `withDefaults`
- Use typed emits with `defineEmits<{...}>()`
- Prefer `ref` for primitives and reassignable objects, `reactive` for mutated objects

### Routes Organization

Routes are split into domain-specific files in `routes/`:
- `attendance.php`, `employees.php`, `payroll.php`, `schedules.php`, `leave.php`
- `api_v1.php` for versioned API endpoints
- `api_face_recognition.php` for face recognition endpoints

## Design System

Uses Glassmorphism design with CSS variable-based color system. See `.claude/skills/tailwind-components/` for component patterns.

## Key Features

- **Face Recognition:** MediaPipe/Face-API.js integration for check-in/out
- **GPS Verification:** Radius-based location verification
- **Payroll:** Configurable tax brackets, deductions, overtime in `.env`
- **Scheduling:** Interactive schedule builder with conflict detection
- **Real-time:** Pusher for notifications

## Testing

- **PHPUnit:** `tests/Unit/` and `tests/Feature/` - uses SQLite in-memory
- **Vitest:** `resources/js/tests/` - uses happy-dom environment
- **Dusk:** Browser tests for end-to-end flows

## Environment

Copy `.env.example` to `.env` and configure:
- Database connection (SQLite for dev, PostgreSQL for prod)
- Payroll settings (PAYROLL_* variables)
- Feature flags (ENABLE_FACE_RECOGNITION, ENABLE_GPS_VERIFICATION)

## Skills

Project-specific skills are available in `.claude/skills/`:
- `face-recognition` - Face detection, verification, enrollment, and liveness patterns
- `laravel-patterns` - Laravel best practices and service layer patterns
- `vuejs-patterns` - Vue 3 Composition API patterns
- `tailwind-components` - Reusable component patterns
- `frontend-design` - UI/UX design guidelines
- `database-performance` - Query optimization strategies
- remember to check this documentation untuk memakai shadcnblocks https://docs.shadcnblocks.com/blocks/shadcn-cli/