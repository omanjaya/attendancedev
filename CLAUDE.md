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
- 1. Masalah Izin Akses (Error 403 Forbidden)
Masalah: User mita (role: pegawai) mencoba mengakses data dashboard, tetapi ditolak server. Penyebab: Role pegawai belum memiliki izin (permission) view_attendance_reports di database. Solusi:

Saya mengedit file 
RolesAndPermissionsSeeder.php
 untuk menambahkan 'view_attendance_reports' ke dalam daftar izin untuk role guru dan pegawai.
Menjalankan seeder ulang (php artisan db:seed ...) dan mereset cache permission (php artisan permission:cache-reset). Pelajaran:
Selalu pastikan setiap Role memiliki izin yang tepat untuk mengakses fitur yang mereka butuhkan. Jika membuat fitur baru (seperti Dashboard untuk Pegawai), cek kembali 
RolesAndPermissionsSeeder
 apakah izinnya sudah diberikan.

2. Endpoint API Tidak Ditemukan (Error 404 Not Found)
Masalah: Frontend mencoba memanggil /api/v1/employees/with-face-data, tapi server membalas "Tidak Ditemukan". Penyebab: Route tersebut belum didaftarkan di 
routes/api.php
 dan method-nya belum ada di Controller. Solusi:

Saya menambahkan method 
withFaceData
 di 
EmployeeApiController.php
.
Saya mendaftarkan route baru di 
routes/api.php
: Route::get('/with-face-data', ...);. Pelajaran:
Saat frontend memanggil API baru, pastikan backend sudah siap menerimanya. Cek file 
routes/api.php
 untuk memastikan URL-nya sudah terdaftar dan mengarah ke Controller yang benar.

3. Salah Sambung Controller (Error 403/400 pada Cuti)
Masalah: Halaman Cuti error saat mengambil saldo. Penyebab: Route /api/v1/leave/balance "salah sambung" ke Controller lama (
LeaveBalanceController
) yang mungkin untuk admin, bukan ke Controller API (Api\LeaveApiController) yang khusus untuk aplikasi mobile/frontend React. Solusi:

Saya mengubah 
routes/api.php
 agar endpoint tersebut mengarah ke Api\LeaveApiController.
Saya menyesuaikan format data yang dikirim backend agar sesuai dengan yang diharapkan frontend (TypeScript interface). Pelajaran:
Pisahkan logika untuk Web (Admin Panel) dan API (Aplikasi Mobile/Frontend). Pastikan route API (
api.php
) selalu menggunakan Controller yang ada di folder App\Http\Controllers\Api.

4. Format Data Tidak Cocok
Masalah: Frontend mengharapkan data saldo cuti dalam format gabungan (total cuti tahunan, sakit, dll), tapi backend mengirim format mentah per baris database. Solusi:

Saya menulis ulang logika di 
LeaveApiController
 untuk "merapikan" (agregasi) data dari database menjadi format JSON yang persis sama dengan yang diminta frontend. Pelajaran:
Selalu cek tipe data di frontend (file 
.ts
 atau .tsx). Backend harus mengirim JSON dengan nama key dan struktur yang persis sama.