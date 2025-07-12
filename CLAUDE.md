# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## School Attendance Management System

This is a production-ready school attendance management system built with Laravel 12 and Vue 3, featuring face recognition, GPS verification, and comprehensive employee management.

## Development Commands

### Unified Development Environment
```bash
composer dev    # Runs Laravel server, queue, logs, and Vite concurrently (recommended)
```

### Individual Commands
```bash
# Backend Development
php artisan serve                    # Laravel development server (port 8000)
php artisan queue:listen            # Process background jobs
php artisan pail                    # Real-time log viewing

# Frontend Development  
npm run dev                         # Vite development server with hot reload
npm run build                       # Production build
npm run watch                       # Watch mode for development

# Database Management
php artisan migrate                 # Run migrations
php artisan migrate:fresh --seed    # Fresh database with sample data
php artisan db:seed                 # Seed database with sample data
php artisan backup:run              # Create system backup

# Testing
php artisan test                    # Run PHPUnit test suite
php artisan test --filter=AttendanceTest  # Run specific test
php artisan dusk                    # Run browser tests

# Performance & Maintenance
php artisan optimize                # Cache routes, config, views
php artisan optimize:clear          # Clear all caches
php artisan performance:analyze     # Analyze system performance
php artisan backup:clean            # Clean old backups
```

## Architecture Overview

### Technology Stack
- **Backend**: Laravel 12 with PHP 8.2+, Spatie Permissions, Laravel Sanctum
- **Frontend**: Vue 3 (Composition API) embedded in Blade templates with Vite
- **Database**: SQLite (development), PostgreSQL (production) with UUID primary keys
- **UI**: Tailwind CSS with Shadcn/UI design system (100% Bootstrap-free)
- **Security**: 2FA, face recognition, GPS verification, comprehensive audit logging

### Application Structure

#### Core Domain Models
```php
// Primary relationships
User (1:1) Employee (1:Many) Attendance
Employee (Many:Many) Schedule (Many:One) Period
Employee (1:Many) Leave (1:Many) LeaveApproval
Employee (1:Many) Payroll
Location (1:Many) Employee
```

#### Vue.js Integration Pattern
The application uses multiple Vue 3 apps mounted on specific pages:
- `AttendanceDashboard.vue` - Sidebar layout dashboard
- `ModernDashboard.vue` - Redesigned interface
- `PerformanceDashboard.vue` - High-performance metrics dashboard
- Components are lazy-loaded and optimized for performance

#### Service Layer Architecture
```php
app/Services/
├── AttendanceService.php       # Core attendance business logic
├── FaceDetectionService.php    # Face recognition processing
├── LocationService.php         # GPS verification logic
├── PayrollService.php          # Payroll calculations
└── BackupService.php           # System backup/restore
```

## Database Design

### Key Schema Features
- **UUID Primary Keys**: Scalable for distributed systems
- **JSONB Metadata**: Face embeddings, GPS coordinates, flexible data storage
- **Comprehensive Indexing**: Performance-optimized queries
- **Soft Deletes**: Audit trail preservation
- **Foreign Key Constraints**: Data integrity enforcement

### Critical Tables
- `employees` - Core employee data with salary structures and metadata
- `attendances` - Time tracking with face recognition and GPS data
- `employee_schedules` - Many-to-many schedule assignments with conflict detection
- `leaves` - Leave management with approval workflow
- `payrolls` - Automated salary calculations with manual overrides

## API Architecture

### Authentication Patterns
```php
// Sanctum API for external clients
Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {
    // Mobile app, third-party integrations
});

// Session-based API for Vue components
Route::prefix('api')->middleware(['auth', 'verified'])->group(function () {
    // Internal Vue.js components
});
```

### Face Detection Integration
- Local processing with Face-API.js and MediaPipe
- No cloud dependencies for privacy compliance
- Confidence scoring and liveness detection
- Face embeddings stored as encrypted JSONB

## Security Implementation

### Multi-layered Security
- **RBAC**: 4 roles (Super Admin, Admin, Manager, Employee) with 27 granular permissions
- **2FA**: Google Authenticator integration with backup codes
- **Face Recognition**: Local processing with confidence thresholds
- **GPS Verification**: Configurable radius checking with location spoofing protection
- **Audit Logging**: Comprehensive activity tracking with risk classification
- **Security Headers**: CSP, HSTS, X-Frame-Options middleware

### Permission System Usage
```php
// Route-level protection
Route::middleware('permission:view_attendance')->group(function () {
    // Protected routes
});

// Controller-level checks
$this->authorize('view', $attendance);

// Blade template conditions
@can('edit_employees')
    <!-- Admin-only content -->
@endcan
```

## Performance Optimization

### Database Performance
- Strategic indexing on frequently queried columns
- Eager loading to prevent N+1 queries
- Query optimization with performance monitoring
- Database connection pooling for concurrent users

### Frontend Performance
- Virtual scrolling for large datasets (1000+ items)
- Lazy loading for chart components
- Optimized asset bundling with Vite
- Progressive Web App (PWA) capabilities

### Monitoring & Analytics
```bash
php artisan performance:analyze     # Generate performance report
php artisan performance:monitor     # Real-time performance tracking
```

## Testing Strategy

### Test Structure
```bash
tests/
├── Feature/                    # End-to-end functionality tests
├── Unit/                       # Individual component tests
├── Browser/                    # Laravel Dusk browser tests
└── Performance/                # System optimization validation
```

### Test Database
- In-memory SQLite for fast test execution
- Factory pattern for realistic test data
- Mocked external services (face detection, GPS)

## Business Logic Patterns

### Attendance Workflow
1. **GPS Verification** → Check employee location within radius
2. **Face Detection** → Capture and verify face with confidence scoring
3. **Liveness Check** → Random gesture prompts (blink, nod, smile)
4. **Status Calculation** → Determine on-time, late, or early status
5. **Working Hours** → Automatic calculation for payroll integration

### Employee Types & Salary Structures
- **Permanent Staff**: Monthly salary with benefits
- **Honorary Teachers**: Hourly rate based on teaching periods
- **Part-time Staff**: Hourly rate with flexible scheduling

### Leave Management Workflow
- Employee submission → Manager review → HR approval → Balance adjustment
- Integration with attendance for automatic leave detection
- Carry-forward and expiration handling

## Deployment Configuration

### Environment Requirements
- PHP 8.2+ with extensions: gd, pgsql, redis, intl
- Node.js 18+ for frontend builds
- PostgreSQL 13+ for production database
- Redis for caching and session management
- HTTPS certificate for face detection camera access

### Production Setup
```bash
# Initial deployment
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Background Processing
- Queue worker for face processing and notifications
- Scheduled tasks for cleanup and backups
- Performance monitoring and alerting

## Development Patterns

### Code Organization Principles
- Service layer for complex business logic
- Repository pattern for data access
- Trait system for shared model functionality
- Event-driven architecture for decoupled features
- Resource classes for consistent API responses

### Vue.js Component Development
- Composition API with `<script setup>` syntax
- Composables for reusable logic (`useFaceDetection`, `useLocation`)
- Props validation and TypeScript support for complex components
- Scoped CSS to prevent style conflicts

### Error Handling & Logging
- Structured logging with context
- User-friendly error messages
- Comprehensive exception handling
- Performance monitoring with alerts

## Current Development Status

The system is production-ready with comprehensive features:
- ✅ Complete CRUD operations for all entities
- ✅ Advanced security with 2FA and biometric verification  
- ✅ Mobile-responsive PWA interface
- ✅ Comprehensive testing suite
- ✅ Performance monitoring and optimization
- ✅ Automated backup and maintenance
- 🔄 Production deployment documentation in progress

This codebase follows enterprise Laravel development practices with modern Vue.js integration, making it suitable for school environments requiring secure, biometric-based attendance tracking.