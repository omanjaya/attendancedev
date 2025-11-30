# Attendance Management System - Monorepo

Modern attendance management system with face recognition, GPS verification, and payroll calculation.

## Architecture

This is a monorepo containing:

```
attendancedev/
├── backend/              # Laravel 12 API
├── frontend/             # React + TypeScript SPA
└── shared/               # Shared types and constants
```

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+), Sanctum, SQLite/PostgreSQL
- **Frontend**: React 19, TypeScript, TanStack Router, TanStack Query
- **UI**: Tailwind CSS 4, shadcn/ui, Radix UI
- **Testing**: PHPUnit, Vitest, Playwright
- **Face Recognition**: Face-API.js, TensorFlow.js

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm 9+

### Installation

```bash
# Install all dependencies
npm install

# Install backend dependencies
cd backend && composer install

# Setup environment
cp backend/.env.example backend/.env
php backend/artisan key:generate
touch backend/database/database.sqlite

# Run migrations
php backend/artisan migrate --seed
```

### Development

```bash
# Start all services (from root)
npm run dev

# Or start individually
npm run dev:backend    # Laravel server on :8000
npm run dev:frontend   # Vite dev server on :5173
```

### Building

```bash
# Build all packages
npm run build

# Build individually
npm run build:frontend
npm run build:backend
npm run build:shared
```

### Testing

```bash
# Run all tests
npm run test

# Backend tests
npm run test:backend

# Frontend tests
npm run test:frontend

# E2E tests
npm run test:e2e
```

## Project Structure

### Backend (`/backend`)

Laravel API following service layer pattern:

- `app/Http/Controllers/` - Thin controllers
- `app/Services/` - Business logic
- `app/Repositories/` - Data access
- `app/Models/` - Eloquent models
- `routes/` - API routes

### Frontend (`/frontend`)

React SPA with modern patterns:

- `src/pages/` - Route pages
- `src/components/` - Reusable components
- `src/lib/` - Utilities and API clients
- `src/stores/` - Zustand stores
- `src/hooks/` - Custom hooks

### Shared (`/shared`)

TypeScript types and constants shared between frontend and backend:

- `types/` - Shared TypeScript interfaces
- `constants/` - Shared constants and enums

## Features

- ✅ Face recognition check-in/out
- ✅ GPS location verification
- ✅ Schedule management
- ✅ Employee management
- ✅ Payroll calculation
- ✅ Leave management
- ✅ Real-time notifications (Pusher)
- ✅ 2FA authentication
- ✅ Role-based access control

## Environment Variables

See `backend/.env.example` for all available configuration options.

Key variables:
- `DB_CONNECTION` - Database type (sqlite/pgsql)
- `ENABLE_FACE_RECOGNITION` - Enable/disable face recognition
- `ENABLE_GPS_VERIFICATION` - Enable/disable GPS checks
- `PAYROLL_*` - Payroll calculation settings

## Documentation

- See `CLAUDE.md` for development guidelines
- See `backend/README.md` for backend-specific docs
- See `frontend/README.md` for frontend-specific docs

## License

MIT
