# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this
repository.

## School Attendance Management System

This is a production-ready school attendance management system built with Laravel 12 and Vue 3,
featuring face recognition, GPS verification, and comprehensive employee management.

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
- **⚠️ CRITICAL**: Only use Tailwind CSS - Never use Bootstrap (causes conflicts)
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

#### Unified Navigation System

The application uses a consolidated navigation system with these components:

- **NavigationService** - Single source of truth for navigation structure
- **IconService** - Heroicons integration with consistent icon mapping
- **NavigationComposer** - View composer for shared navigation data
- **Unified Navigation Components** - Single responsive navigation system
  - `components/navigation/unified-nav.blade.php` - Main navigation component
  - `components/navigation/nav-item.blade.php` - Individual navigation items
- **Responsive Design** - Desktop sidebar, mobile overlay, and bottom navigation

### Vue.js Integration Pattern

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
Route::prefix('api/v1')
  ->middleware('auth:sanctum')
  ->group(function () {
    // Mobile app, third-party integrations
  });

// Session-based API for Vue components
Route::prefix('api')
  ->middleware(['auth', 'verified'])
  ->group(function () {
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
- **Persistent Authentication**: Remember me functionality with 1-year token validity

### Persistent Authentication Configuration

The system implements highly persistent "Remember Me" functionality:

```php
// config/auth.php - Remember token configuration
'guards' => [
  'web' => [
    'driver' => 'session',
    'provider' => 'users',
    'remember' => 525600, // 365 days in minutes
  ],
],

// config/session.php & .env - Session configuration
SESSION_LIFETIME=43200          // 30 days in minutes
SESSION_EXPIRE_ON_CLOSE=false  // Don't expire on browser close

// User experience
- Remember me checkbox checked by default
- Users stay logged in for 1 year unless they clear browser cache
- Automatic session extension for users with remember tokens
- Secure cookie configuration with HttpOnly and SameSite protection
```

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

This codebase follows enterprise Laravel development practices with modern Vue.js integration,
making it suitable for school environments requiring secure, biometric-based attendance tracking.

## System Command Center Design Pattern

The **Pusat Komando Sistem** (System Command Center) serves as the gold standard for design patterns, spacing, and layout symmetry across the application. All admin interfaces should follow these exact patterns:

### Page Structure Template

```php
@extends('layouts.authenticated-unified')
@section('title', 'Page Title')

<!-- Page Header Pattern -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Page Title</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Subtitle description</p>
        </div>
        <!-- Action buttons aligned right -->
    </div>
</div>
```

### Grid System Specifications

#### Primary Stats Grid (5-column layout)
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- 5 metric cards with consistent sizing -->
</div>
```

#### Content Grid (3-column with span)
```html
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Main content: lg:col-span-2 -->
    <!-- Sidebar content: 1 column -->
</div>
```

#### Management Grid (4-column)
```html
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <!-- 4 equal management cards -->
</div>
```

#### Administration Panel (2-column)
```html
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- 2 equal admin panels -->
</div>
```

### Card Styling Standards

#### Standard Card Base Classes
```css
bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700
```

#### Advanced Glassmorphism Cards
```css
bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 ease-out
```

#### Card Content Padding
- **Standard cards**: `p-6`
- **Compact cards**: `p-4`
- **Mini cards**: `p-3`

### Spacing System (Tailwind Scale)

#### Section Spacing
- **Between major sections**: `mb-8`
- **Between cards in grid**: `gap-6`
- **Internal card spacing**: `mb-4`, `mb-6`

#### Element Spacing
- **Icon containers**: `w-10 h-10`, `w-12 h-12`
- **Avatar sizes**: `w-8 h-8`, `w-10 h-10`
- **Badge padding**: `px-3 py-1`
- **Button spacing**: `space-x-2`, `space-x-3`

#### Text Spacing
- **Title margins**: `mb-4`, `mb-6`
- **Paragraph spacing**: `mb-1`, `mt-1`
- **List spacing**: `space-y-3`, `space-y-4`

### Border Radius Hierarchy
- **Cards**: `rounded-lg` (standard), `rounded-xl` (featured), `rounded-2xl` (premium)
- **Icons**: `rounded-lg`, `rounded-xl`
- **Badges**: `rounded-full`
- **Buttons**: `rounded-md` (standard)

### Color System & Status Indicators

#### Status Colors
- **Success/Active**: `green-500` to `emerald-600`
- **Warning/Pending**: `amber-500` to `orange-600`
- **Error/Critical**: `red-500` to `rose-600`
- **Info/Neutral**: `blue-500` to `cyan-500`
- **System/Muted**: `gray-500` to `slate-600`

#### Gradient Patterns
```css
/* Status gradients */
bg-gradient-to-r from-green-500 to-emerald-600
bg-gradient-to-r from-amber-500 to-orange-600
bg-gradient-to-r from-red-500 to-rose-600

/* Background gradients */
bg-gradient-to-br from-blue-500 to-purple-600
bg-gradient-to-br from-green-500/10 to-emerald-500/10
```

### Typography Hierarchy

#### Headers
- **Page Title**: `text-3xl font-bold`
- **Section Title**: `text-xl font-semibold`
- **Card Title**: `text-xl font-semibold` or `text-lg font-semibold`
- **Metric Value**: `text-2xl font-bold`

#### Body Text
- **Primary text**: `text-sm font-medium`
- **Secondary text**: `text-xs text-gray-500 dark:text-gray-400`
- **Captions**: `text-xs text-gray-500`

### Component Patterns

#### Metric Cards Structure
```html
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-4">
        <div class="p-3 bg-{color}-600 rounded-lg shadow-md">
            <!-- Icon SVG -->
        </div>
        <span class="text-sm text-{color}-600">Status badge</span>
    </div>
    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Value</h3>
    <p class="text-gray-600 dark:text-gray-400 text-sm">Description</p>
</x-ui.card>
```

#### Activity Feed Structure
```html
<div class="space-y-3">
    <div class="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-{color}-600 rounded-full flex items-center justify-center">
                <!-- Avatar or initials -->
            </div>
            <div class="ml-4">
                <div class="text-sm font-medium">Name</div>
                <div class="text-xs text-gray-500">Details</div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-lg font-bold">Time</div>
            <div class="text-xs text-gray-500">Date</div>
        </div>
    </div>
</div>
```

### Responsive Behavior

#### Breakpoint Strategy
- **Mobile**: `grid-cols-1` (single column)
- **Tablet**: `md:grid-cols-2` (2 columns)
- **Desktop**: `lg:grid-cols-3`, `lg:grid-cols-4`, `lg:grid-cols-5`
- **Large**: `xl:grid-cols-4`, `xl:grid-cols-5` (maintain proportions)

#### Element Hiding/Showing
- **Mobile-first**: Show essential information
- **Tablet+**: `hidden sm:inline`, `hidden md:flex`
- **Desktop+**: `hidden lg:flex` for detailed info

### Symmetry & Alignment Rules

#### Card Height Balance
- Use natural content flow for equal heights
- Consistent padding creates visual rhythm
- Avoid forced height constraints

#### Grid Alignment
- **Equal columns**: Use consistent `gap-6`
- **Span columns**: Use `lg:col-span-2` sparingly
- **Nested grids**: Maintain `gap-4` for internal elements

#### Content Alignment
- **Flexbox patterns**: `justify-between`, `items-center`
- **Text alignment**: Left-align for readability
- **Action alignment**: Right-align for consistency

### Implementation Guidelines

1. **Always use the x-ui.card component** for consistent styling
2. **Follow the exact spacing hierarchy** from the Command Center
3. **Maintain grid proportions** across different pages
4. **Use consistent color patterns** for status indicators
5. **Apply glassmorphism effects** for premium sections only
6. **Ensure dark mode compatibility** with all patterns

This design system ensures perfect visual consistency and symmetry across all administrative interfaces in the attendance management system.

## Dashboard Real Data Implementation

The dashboard system has been completely refactored to use 100% real data from the database, eliminating all hardcoded values and mock data. All metrics, statistics, and visual elements now reflect actual system state and database content.

### Real Data Integration Completed

#### **Backend Services**
- **DashboardController**: All mock data replaced with real database queries
  - `getTodaySchedule()`: Connects to `employee_schedules`, `periods`, `subjects` tables
  - System health calculations based on actual service status
  - Real trend calculations (present today change, attendance rate change, leave changes)
  - Dynamic schedule status (upcoming/ongoing/completed) based on current time

- **DashboardService**: 100% real database implementation
  - `getRealtimeAttendanceStatus()`: Real employee counts, attendance rates, late arrivals
  - `getTeachingCoverageRate()`: Actual teacher presence calculations
  - `getSubstituteTeacherNeeds()`: Real absent teacher detection with scheduled classes
  - `getTeachingSummary()`: Real teaching hours from schedule data
  - `getLeaveBalance()`: Actual leave calculations from database
  - `getPersonalPerformance()`: Real performance metrics from attendance patterns
  - Chart data methods: `getWeeklyAttendanceChart()`, `getMonthlyAttendanceChart()`, `getQuarterlyAttendanceChart()`

#### **Frontend Components**
- **All Dashboard Views**: Eliminated 200+ lines of hardcoded values
  - Super Admin Dashboard: Real attendance table, activities, analytics
  - Teacher Dashboard: Real performance metrics, teaching hours, leave balance
  - Staff Dashboard: Real work tracking, attendance rates, chart data
  - Main Dashboard: Real activity feed from attendance and leave tables

- **Vue.js ModernDashboard**: Real API integration
  - Connects to `/api/dashboard/data` for real-time statistics
  - Chart data from `/api/dashboard/chart-data` with period filtering
  - Auto-refresh every 5 minutes for live updates
  - Real system status monitoring

#### **Data Sources Connected**
```php
// Real attendance calculations
$totalEmployees = Employee::active()->count();
$checkedInToday = Attendance::whereDate('date', $today)
    ->whereNotNull('check_in_time')
    ->distinct('employee_id')
    ->count();

// Real recent activities
$recentActivities = Attendance::with('employee')
    ->whereDate('date', today())
    ->orderBy('check_in_time', 'desc')
    ->get();

// Real performance metrics
$attendanceRate = $totalEmployees > 0 ? 
    round(($checkedInToday / $totalEmployees) * 100, 1) : 0;
```

#### **Security & Monitoring**
- **Real Security Alerts**: `getSecurityAlertsCount()` checks actual suspicious activities
- **System Health**: Actual database, storage, and face recognition status checks
- **Audit Integration**: Connect to real audit logs for activity tracking
- **Face Recognition Metrics**: Real confidence scoring from metadata

#### **Performance Optimizations**
- **Caching Strategy**: 15-minute cache for Super Admin dashboard data
- **Optimized Queries**: Efficient database queries with eager loading
- **Error Handling**: Proper try/catch blocks for missing tables/data
- **Empty States**: Beautiful fallback UI when no data available

#### **Chart & Analytics**
- **Historical Data**: Real trend analysis from attendance patterns
- **Weekly/Monthly/Quarterly Charts**: Actual attendance statistics
- **Performance Tracking**: Real punctuality scores, attendance rates
- **Predictive Metrics**: Based on historical attendance data

### API Endpoints
All dashboard API endpoints now return real data:
- `GET /api/dashboard/data` - Real-time dashboard statistics
- `GET /api/dashboard/chart-data` - Historical attendance charts
- `GET /api/dashboard/widget/{widget}` - Specific widget data

### Production Ready Features
- **Zero Hardcoded Values**: All metrics from database
- **Real-time Updates**: Auto-refresh and live data
- **Scalable Architecture**: Automatically scales with data growth
- **Error Resilient**: Proper fallbacks for edge cases
- **Performance Monitored**: Optimized queries and caching

## Schedule Management UI/UX Design System

The **Schedule Management** module serves as the exemplary design pattern for complex data management interfaces, featuring advanced interactions, calendar views, and teacher assignment workflows. All schedule-related interfaces should follow these comprehensive patterns:

### Schedule Page Architecture

#### Primary Layout Structure
```blade
@extends('layouts.authenticated-unified')
@section('title', 'Schedule Management')

<!-- Page Header with Multi-Action Toolbar -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Schedule Management</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola jadwal pelajaran dan penugasan guru</p>
        </div>
        <div class="flex items-center space-x-3">
            <!-- Secondary Actions -->
            <button class="bg-white dark:bg-gray-700 border...">Import</button>
            <button class="bg-white dark:bg-gray-700 border...">Calendar View</button>
            <!-- Primary Actions -->
            <button class="bg-green-600 hover:bg-green-700...">Schedule Builder</button>
            <button class="bg-blue-600 hover:bg-blue-700...">Add Schedule</button>
        </div>
    </div>
</div>
```

### Schedule-Specific Components

#### 1. Statistics Cards for Schedule Metrics
```html
<!-- 4-column grid for schedule statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Period Count Card -->
    <x-ui.card class="bg-white dark:bg-gray-800...">
        <div class="p-3 bg-blue-600 rounded-lg shadow-md">
            <svg class="w-6 h-6 text-white"><!-- Clock icon --></svg>
        </div>
        <h3 class="text-2xl font-bold">{{ $periods->count() }}</h3>
        <p class="text-sm text-gray-600">Total Periode</p>
    </x-ui.card>
</div>
```

#### 2. Day Navigation Tabs
```html
<!-- Interactive day filter tabs -->
<x-ui.card class="mb-8">
    <div class="p-6 border-b">
        <h3 class="text-xl font-semibold">Filter Hari</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-5 gap-3">
            @foreach($days as $day)
            <button class="day-tab" data-day="{{ $day['id'] }}">
                <span class="block text-xs">{{ $day['short'] }}</span>
                <span class="block text-sm font-semibold">{{ $day['name'] }}</span>
                <span class="badge">{{ $day['count'] }} periode</span>
            </button>
            @endforeach
        </div>
    </div>
</x-ui.card>
```

#### 3. Schedule Calendar Grid
```html
<!-- Weekly calendar view with time slots -->
<div class="calendar-grid">
    <div class="time-column">
        <!-- Time slots from 07:00 to 17:00 -->
    </div>
    <div class="days-grid">
        <!-- Monday to Friday columns -->
        <div class="day-column" v-for="day in weekDays">
            <div class="schedule-block" 
                 v-for="schedule in day.schedules"
                 :style="{
                     top: getTopPosition(schedule.start_time),
                     height: getBlockHeight(schedule.duration)
                 }">
                <!-- Schedule content -->
            </div>
        </div>
    </div>
</div>
```

#### 4. Teacher Assignment Modal
```html
<!-- Modal for assigning teachers to periods -->
<x-ui.modal id="assignTeacherModal">
    <form @submit.prevent="assignTeacher">
        <div class="space-y-4">
            <!-- Teacher selection -->
            <div>
                <label class="block text-sm font-medium mb-2">Pilih Guru</label>
                <select class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700...">
                    <option v-for="teacher in availableTeachers">
                        {{ teacher.name }} ({{ teacher.workload }}%)
                    </option>
                </select>
            </div>
            <!-- Date range picker -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label>Tanggal Mulai</label>
                    <input type="date" class="form-input">
                </div>
                <div>
                    <label>Tanggal Selesai</label>
                    <input type="date" class="form-input">
                </div>
            </div>
        </div>
    </form>
</x-ui.modal>
```

### Schedule UI Patterns

#### 1. Color Coding System
```javascript
const scheduleColors = {
    subjects: {
        'Matematika': 'bg-blue-100 text-blue-800 border-blue-300',
        'B.Indonesia': 'bg-green-100 text-green-800 border-green-300',
        'B.Inggris': 'bg-purple-100 text-purple-800 border-purple-300',
        'IPA': 'bg-red-100 text-red-800 border-red-300',
        'IPS': 'bg-yellow-100 text-yellow-800 border-yellow-300'
    },
    status: {
        'assigned': 'bg-green-50 border-green-200',
        'unassigned': 'bg-gray-50 border-gray-200',
        'conflict': 'bg-red-50 border-red-200'
    }
};
```

#### 2. Interactive Table with Click Actions
```html
<table class="schedule-table">
    <thead>
        <tr>
            <th>Periode</th>
            @foreach($days as $day)
                <th>{{ $day['name'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($periods as $period)
        <tr>
            <td class="period-info">
                <div class="font-medium">{{ $period->name }}</div>
                <div class="text-xs text-gray-500">
                    {{ $period->start_time }} - {{ $period->end_time }}
                </div>
            </td>
            @foreach($days as $day)
            <td class="schedule-cell" 
                @click="openAssignModal({{ $period->id }}, {{ $day['id'] }})">
                <!-- Cell content or empty state -->
            </td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
```

#### 3. Teacher Workload Summary Cards
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($teachers as $teacher)
    <div class="teacher-card p-4 bg-white dark:bg-gray-800 rounded-lg">
        <div class="flex items-center space-x-3 mb-3">
            <img src="{{ $teacher->photo_url }}" class="w-10 h-10 rounded-full">
            <div>
                <h4 class="font-medium">{{ $teacher->name }}</h4>
                <p class="text-xs text-gray-500">{{ $teacher->subject }}</p>
            </div>
        </div>
        <!-- Workload progress bar -->
        <div class="mb-2">
            <div class="flex justify-between text-sm mb-1">
                <span>Beban Mengajar</span>
                <span>{{ $teacher->workload }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full"
                     style="width: {{ $teacher->workload }}%"></div>
            </div>
        </div>
        <!-- Stats -->
        <div class="grid grid-cols-2 gap-2 text-xs">
            <div>
                <span class="text-gray-500">Periode:</span>
                <span class="font-medium">{{ $teacher->periods_count }}</span>
            </div>
            <div>
                <span class="text-gray-500">Jam:</span>
                <span class="font-medium">{{ $teacher->hours_count }}</span>
            </div>
        </div>
    </div>
    @endforeach
</div>
```

### Schedule Builder Interface

#### 1. Interactive Grid Builder
```javascript
// Schedule builder component
const scheduleBuilder = {
    grid: [], // 2D array of schedule slots
    selectedCells: [],
    
    toggleCell(periodId, dayId) {
        const key = `${periodId}-${dayId}`;
        if (this.selectedCells.includes(key)) {
            this.selectedCells = this.selectedCells.filter(c => c !== key);
        } else {
            this.selectedCells.push(key);
        }
        this.updatePreview();
    },
    
    bulkAssign() {
        // Assign selected teacher to all selected cells
    }
};
```

#### 2. Quick Actions Panel
```html
<div class="quick-actions sticky top-4">
    <x-ui.card>
        <h3 class="font-semibold mb-4">Quick Actions</h3>
        <div class="space-y-2">
            <button class="w-full text-left px-3 py-2 hover:bg-gray-50">
                <svg class="w-4 h-4 inline mr-2"><!-- icon --></svg>
                Clear Selected Cells
            </button>
            <button class="w-full text-left px-3 py-2 hover:bg-gray-50">
                <svg class="w-4 h-4 inline mr-2"><!-- icon --></svg>
                Copy Schedule Pattern
            </button>
            <button class="w-full text-left px-3 py-2 hover:bg-gray-50">
                <svg class="w-4 h-4 inline mr-2"><!-- icon --></svg>
                Auto-Fill Empty Slots
            </button>
        </div>
    </x-ui.card>
</div>
```

### Import/Export Features

#### 1. Import Modal with Drag-Drop
```html
<div class="import-dropzone" 
     @drop="handleDrop" 
     @dragover.prevent
     @dragenter.prevent>
    <svg class="w-12 h-12 mx-auto mb-4 text-gray-400"><!-- upload icon --></svg>
    <p class="text-center">
        Drag & drop file Excel jadwal atau
        <label class="text-blue-600 cursor-pointer">
            browse
            <input type="file" class="hidden" accept=".xlsx,.xls">
        </label>
    </p>
</div>
```

#### 2. Export Options
```html
<div class="export-options p-4">
    <h4 class="font-medium mb-3">Export Format</h4>
    <div class="space-y-2">
        <label class="flex items-center">
            <input type="radio" name="format" value="excel" checked>
            <span class="ml-2">Excel (.xlsx)</span>
        </label>
        <label class="flex items-center">
            <input type="radio" name="format" value="pdf">
            <span class="ml-2">PDF Document</span>
        </label>
        <label class="flex items-center">
            <input type="radio" name="format" value="ical">
            <span class="ml-2">iCalendar (.ics)</span>
        </label>
    </div>
</div>
```

### Responsive Behavior

#### Mobile Optimizations
```css
/* Mobile schedule view */
@media (max-width: 768px) {
    .schedule-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    
    .calendar-grid {
        /* Switch to vertical day view */
        display: flex;
        flex-direction: column;
    }
    
    .day-navigation {
        /* Horizontal scroll for days */
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
```

### Interactive Features

#### 1. Conflict Detection
```javascript
function detectConflicts(teacherId, periodId, dayId) {
    // Check if teacher is already assigned at this time
    const conflicts = schedules.filter(s => 
        s.teacher_id === teacherId &&
        s.period_id === periodId &&
        s.day_id === dayId
    );
    
    if (conflicts.length > 0) {
        showConflictModal(conflicts);
        return false;
    }
    return true;
}
```

#### 2. Real-time Updates
```javascript
// WebSocket or polling for live updates
Echo.channel('schedules')
    .listen('ScheduleUpdated', (e) => {
        updateScheduleCell(e.schedule);
        showNotification('Schedule updated by ' + e.user);
    });
```

### Accessibility Features

1. **Keyboard Navigation**
   - Tab through schedule cells
   - Arrow keys for grid navigation
   - Enter to open assignment modal
   - Escape to close modals

2. **Screen Reader Support**
   - ARIA labels for all interactive elements
   - Descriptive text for schedule blocks
   - Status announcements for changes

3. **Color Blind Friendly**
   - Patterns in addition to colors
   - High contrast text
   - Icons to supplement color coding

### Performance Optimizations

1. **Lazy Loading**
   - Load schedule data per week/month
   - Virtual scrolling for large datasets
   - Paginated teacher lists

2. **Caching**
   - Cache teacher availability
   - Store draft schedules locally
   - Offline mode support

3. **Batch Operations**
   - Bulk assign/unassign
   - Queue updates for sync
   - Optimistic UI updates

This comprehensive Schedule Management design system provides a robust foundation for complex scheduling interfaces with excellent user experience and performance.

## Multi-Layered Schedule Management System

The school attendance system implements a sophisticated multi-layered scheduling architecture that handles various types of schedules with override capabilities and role-based attendance calculations.

### Schedule Architecture Overview

#### 1. Monthly Base Schedule Creation (`/schedules/create`)

**Schedule Template Definition**:
- **Nama Jadwal**: Descriptive name for the monthly schedule template
- **Bulan**: Target month for the schedule (January - December)
- **Date Range**: Start and end date selector for schedule validity period
- **Working Hours**: Default work time range (e.g., 08:00 - 16:00)
- **Location Selection**: Import from Location Management system for multi-site schools

```php
// Database Schema: monthly_schedules table
Schema::create('monthly_schedules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name'); // Nama Jadwal
    $table->integer('month'); // 1-12
    $table->integer('year');
    $table->date('start_date');
    $table->date('end_date');
    $table->time('default_start_time'); // Jam kerja mulai
    $table->time('default_end_time'); // Jam kerja selesai
    $table->uuid('location_id');
    $table->json('metadata')->nullable(); // Additional schedule settings
    $table->timestamps();
    $table->foreign('location_id')->references('id')->on('locations');
});
```

#### 2. Employee Schedule Assignment System

**Assignment Interface** (`/schedules/assign`):
- **Schedule Selection**: Choose from created monthly schedule templates
- **Multi-Employee Assignment**: 
  - Bulk assignment with range selectors (e.g., "Guru A-D", "Pegawai B-C")
  - Individual employee selection with checkboxes
  - Role-based filtering (Guru Tetap, Guru Honorer, Staff)
- **Batch Processing**: One-click assignment for entire month

```php
// Database Schema: employee_monthly_schedules table
Schema::create('employee_monthly_schedules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('monthly_schedule_id');
    $table->uuid('employee_id');
    $table->date('effective_date');
    $table->time('start_time');
    $table->time('end_time');
    $table->uuid('location_id');
    $table->enum('status', ['active', 'overridden', 'holiday'])->default('active');
    $table->json('override_metadata')->nullable();
    $table->timestamps();
    
    $table->foreign('monthly_schedule_id')->references('id')->on('monthly_schedules');
    $table->foreign('employee_id')->references('id')->on('employees');
    $table->unique(['employee_id', 'effective_date']); // One schedule per employee per day
});
```

### Override System Architecture

#### 3. National Holiday Override (`/schedules/holidays`)

**Holiday Management Interface**:
- **Holiday Input**: Admin can add national/regional holidays
- **Automatic Override**: System automatically marks holiday dates as non-working
- **Schedule Nullification**: Any existing schedules on holiday dates become inactive

```php
// Database Schema: national_holidays table
Schema::create('national_holidays', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name'); // e.g., "Hari Kemerdekaan"
    $table->date('holiday_date');
    $table->enum('type', ['national', 'regional', 'religious', 'school']);
    $table->text('description')->nullable();
    $table->boolean('is_recurring')->default(false); // Annual holidays
    $table->timestamps();
});

// Override Logic Implementation
public function applyHolidayOverrides()
{
    $holidays = NationalHoliday::whereYear('holiday_date', Carbon::now()->year)->get();
    
    foreach ($holidays as $holiday) {
        EmployeeMonthlySchedule::where('effective_date', $holiday->holiday_date)
            ->update([
                'status' => 'holiday',
                'override_metadata' => json_encode([
                    'holiday_name' => $holiday->name,
                    'holiday_type' => $holiday->type,
                    'original_status' => 'active'
                ])
            ]);
    }
}
```

#### 4. Teaching Schedule Override (Guru Honorer Only)

**Teaching Schedule Integration**:
- **Teaching Period Definition**: Subject-specific class schedules (e.g., 11:00-13:00 WITA)
- **Attendance Time Override**: For honorary teachers (Guru Honorer), attendance calculation uses teaching hours instead of base schedule
- **Late Status Logic**: If teacher arrives after teaching start time, marked as "Terlambat"
- **Permanent Teacher Exception**: Guru Tetap always follow base morning schedule regardless of teaching hours

```php
// Database Schema: teaching_schedules table
Schema::create('teaching_schedules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('teacher_id'); // Must be employee with teacher role
    $table->uuid('subject_id');
    $table->uuid('class_id');
    $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
    $table->time('teaching_start_time');
    $table->time('teaching_end_time');
    $table->date('effective_from');
    $table->date('effective_until')->nullable();
    $table->json('metadata')->nullable(); // Room, additional info
    $table->timestamps();
    
    $table->foreign('teacher_id')->references('id')->on('employees');
    $table->index(['teacher_id', 'day_of_week', 'effective_from']);
});

// Attendance Calculation Override Logic
public function calculateAttendanceHours(Employee $employee, Carbon $date)
{
    $baseSchedule = $employee->getScheduleForDate($date);
    
    // For Guru Honorer (Honorary Teachers), use teaching schedule
    if ($employee->employee_type === 'guru_honorer') {
        $teachingSchedule = $employee->getTeachingScheduleForDate($date);
        
        if ($teachingSchedule) {
            return [
                'expected_start' => $teachingSchedule->teaching_start_time,
                'expected_end' => $teachingSchedule->teaching_end_time,
                'attendance_basis' => 'teaching_schedule',
                'override_applied' => true
            ];
        }
    }
    
    // For Guru Tetap (Permanent Teachers) and Staff, always use base schedule
    return [
        'expected_start' => $baseSchedule->start_time,
        'expected_end' => $baseSchedule->end_time,
        'attendance_basis' => 'base_schedule',
        'override_applied' => false
    ];
}
```

### Attendance Calculation Rules

#### Role-Based Attendance Logic

**Guru Honorer (Honorary Teachers)**:
```php
// If teaching schedule exists for the day
if ($teachingSchedule && $attendance->check_in_time) {
    $expectedStart = $teachingSchedule->teaching_start_time;
    $actualStart = $attendance->check_in_time;
    
    if ($actualStart > $expectedStart) {
        $status = 'late';
        $late_duration = $expectedStart->diff($actualStart);
    } else {
        $status = 'present';
    }
    
    // Working hours calculation based on teaching period only
    $working_hours = $teachingSchedule->teaching_start_time
        ->diff($teachingSchedule->teaching_end_time)->h;
}
```

**Guru Tetap (Permanent Teachers)**:
```php
// Always use base morning schedule regardless of teaching hours
$baseSchedule = $employee->getBaseScheduleForDate($date);
$expectedStart = $baseSchedule->start_time; // e.g., 08:00

if ($attendance->check_in_time > $expectedStart) {
    $status = 'late';
} else {
    $status = 'present';
}

// Full working day calculation
$working_hours = $baseSchedule->start_time
    ->diff($baseSchedule->end_time)->h;
```

### Implementation Workflow

#### Schedule Creation Process:
1. **Admin creates monthly schedule template** with basic parameters
2. **System generates daily schedule entries** for entire month
3. **Admin assigns employees** to schedule using bulk assignment interface
4. **Holiday overrides applied** automatically for national holidays
5. **Teaching schedules imported** from existing education system
6. **System calculates final attendance rules** per employee per day

#### Daily Attendance Processing:
1. **Check employee type** (Guru Tetap, Guru Honorer, Staff)
2. **Retrieve base schedule** for the date
3. **Check for holiday override** (skip attendance if holiday)
4. **Apply teaching schedule override** (for Guru Honorer only)
5. **Calculate attendance status** based on applicable schedule
6. **Store attendance record** with metadata about applied rules

### Database Relationships

```php
// Key Relationships
MonthlySchedule::hasMany(EmployeeMonthlySchedule::class)
Employee::hasMany(EmployeeMonthlySchedule::class)
Employee::hasMany(TeachingSchedule::class, 'teacher_id')
Location::hasMany(MonthlySchedule::class)
NationalHoliday::affectsMany(EmployeeMonthlySchedule::class) // Via override logic

// Query Examples
// Get effective schedule for employee on specific date
$effectiveSchedule = EmployeeMonthlySchedule::where('employee_id', $employeeId)
    ->where('effective_date', $date)
    ->where('status', '!=', 'holiday')
    ->first();

// Get teaching override for guru honorer
$teachingOverride = TeachingSchedule::where('teacher_id', $employeeId)
    ->where('day_of_week', $date->dayOfWeek)
    ->where('effective_from', '<=', $date)
    ->where(function($q) use ($date) {
        $q->whereNull('effective_until')
          ->orWhere('effective_until', '>=', $date);
    })
    ->first();
```

### API Endpoints for Schedule Management

```php
// Schedule Template Management
POST /api/schedules/monthly          // Create monthly schedule template
GET  /api/schedules/monthly          // List monthly schedules
PUT  /api/schedules/monthly/{id}     // Update schedule template

// Employee Assignment
POST /api/schedules/assign           // Bulk assign employees to schedule
GET  /api/schedules/assignments      // View current assignments
DELETE /api/schedules/assignments/{id} // Remove assignment

// Holiday Management
POST /api/schedules/holidays         // Add national holiday
GET  /api/schedules/holidays         // List holidays
PUT  /api/schedules/holidays/{id}    // Update holiday
DELETE /api/schedules/holidays/{id}  // Remove holiday

// Teaching Schedule Integration
POST /api/schedules/teaching         // Import/create teaching schedules
GET  /api/schedules/teaching/{teacherId} // Get teacher's teaching schedule

// Schedule Calculation
GET  /api/schedules/effective/{employeeId}/{date} // Get effective schedule for employee on date
GET  /api/schedules/attendance-rules/{employeeId}/{date} // Get attendance calculation rules
```

### Frontend Implementation Notes

#### Schedule Creation Form (`/schedules/create`):
- **Month/Year Picker**: Dropdown or calendar widget
- **Date Range Selector**: Start date and end date inputs with validation
- **Time Picker**: Working hours with 15-minute intervals
- **Location Dropdown**: Populated from Location Management system
- **Preview Calendar**: Show generated schedule days before saving

#### Assignment Interface (`/schedules/assign`):
- **Schedule Selector**: Dropdown of created monthly schedules
- **Employee Multi-Select**: Advanced multi-select with search and filtering
- **Bulk Actions**: Select by role, department, or name ranges
- **Assignment Preview**: Table showing which employees get which schedules
- **Conflict Detection**: Warn if employee already has schedule for period

#### Holiday Management:
- **Calendar View**: Visual calendar showing existing holidays
- **Holiday Import**: CSV import for bulk holiday addition
- **Override Impact**: Show how many schedules will be affected
- **Recurring Holidays**: Annual holidays that auto-apply each year

This multi-layered schedule system provides complete flexibility for educational institutions while maintaining clear attendance calculation rules and proper override hierarchies.

## Code Refactoring & Optimization Guidelines

Based on the comprehensive refactoring work completed across 19 phases (optimizing 18+ files and reducing 316+ long lines), follow these strict guidelines when creating or modifying Blade templates and Vue.js components:

### CSS Class Extraction Strategy

#### 1. Long Line Detection & Resolution
- **Target**: Lines >120 characters require optimization
- **Method**: Extract inline Tailwind classes into reusable CSS classes in `/resources/css/app.css`
- **Priority**: Complex forms, modals, cards, and interactive components

#### 2. CSS Class Organization Pattern
```css
/* Always organize CSS classes by component type with clear sections */

/* ========================================
   [COMPONENT NAME] OPTIMIZATION SYSTEM
   ======================================== */

/* Base component styles */
.component-base {
    @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700;
}

/* Component variations */
.component-variant-primary {
    @apply bg-gradient-to-r from-blue-500 to-blue-600;
}

/* Interactive states */
.component-hover {
    @apply hover:shadow-xl transition-all duration-300;
}

/* Responsive patterns */
.component-grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6;
}
```

#### 3. Reusable Class Naming Convention
- **Base Components**: `.feature-card`, `.stats-card`, `.modal-container`
- **Status Indicators**: `.status-success`, `.status-warning`, `.status-error`
- **Interactive Elements**: `.action-button-group`, `.form-grid`, `.progress-bar-container`
- **Layout Systems**: `.stats-grid`, `.dashboard-grid`, `.workflow-step-container`

### Blade Template Optimization Patterns

#### 1. Feature Card System
```blade
<!-- BEFORE (long line) -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-all duration-300">

<!-- AFTER (optimized) -->
<div class="feature-card p-6 hover:shadow-xl">
```

#### 2. Modal Component Optimization
```blade
<!-- BEFORE -->
<div class="fixed inset-0 z-50 overflow-y-auto bg-slate-500 bg-opacity-75 transition-opacity">
    <div class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full">

<!-- AFTER -->
<div class="modal-overlay">
    <div class="modal-container">
```

#### 3. Form Field Standardization
```blade
<!-- BEFORE -->
<textarea class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm">

<!-- AFTER -->
<textarea class="form-textarea">
```

#### 4. Progress & Status Components
```blade
<!-- Progress bars -->
<div class="progress-bar-container">
    <div class="progress-bar-fill" style="width: {{ $percentage }}%"></div>
</div>

<!-- Status badges -->
<span class="status-badge" x-bind:class="status === 'success' ? 'status-success' : 'status-warning'">
```

### Alpine.js Integration Optimization

#### 1. Dynamic Class Binding Patterns
```blade
<!-- BEFORE (long conditional classes) -->
x-bind:class="condition ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white' : 'bg-slate-300 cursor-not-allowed text-slate-500'"

<!-- AFTER (using CSS classes) -->
x-bind:class="condition ? 'success-btn' : 'disabled-btn'"
```

#### 2. Component State Management
```blade
<!-- Use semantic CSS classes for different states -->
<div class="workflow-step-number"
     x-bind:class="step.completed ? 'workflow-step-completed' : 
                  step.id === current ? 'workflow-step-current' : 
                  'workflow-step-inactive'">
```

### Icon Component System

#### 1. SVG Icon Optimization
```blade
<!-- BEFORE (inline SVG with long classes) -->
<svg class="w-5 h-5 mr-2 text-blue-500 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">

<!-- AFTER (using icon components) -->
<x-icons.edit class="action-icon-animated" />
```

#### 2. Icon Component Creation Pattern
```blade
{{-- resources/views/components/icons/edit.blade.php --}}
<svg {{ $attributes->merge(['class' => 'w-5 h-5']) }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
</svg>
```

### Responsive Design Optimization

#### 1. Grid System Standardization
```blade
<!-- Statistics grids -->
<div class="stats-grid">                    <!-- 4-column responsive -->
<div class="dashboard-grid">                <!-- 5-column metrics -->
<div class="management-grid">               <!-- 3-column with span -->

<!-- Workflow components -->
<div class="workflow-step-container">
<div class="action-button-group">
```

#### 2. Mobile-First Responsive Patterns
```css
/* Define mobile-first responsive classes */
.responsive-grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6;
}

.responsive-text {
    @apply text-sm md:text-base lg:text-lg;
}
```

### Vue.js Component Optimization

#### 1. Composable Class Patterns
```javascript
// Use CSS classes instead of inline styles in Vue computed properties
computed: {
    cardClasses() {
        return {
            'stats-card-active': this.isActive,
            'stats-card-disabled': this.isDisabled,
            'stats-card-loading': this.isLoading
        }
    }
}
```

#### 2. Template Optimization
```vue
<!-- BEFORE -->
<div :class="`bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 ${isActive ? 'ring-2 ring-blue-500' : ''}`">

<!-- AFTER -->
<div class="feature-card" :class="cardClasses">
```

### Mandatory Optimization Checklist

#### Before Creating Any Page:
1. ✅ **Scan for long lines** (>120 characters)
2. ✅ **Extract common Tailwind patterns** to CSS classes
3. ✅ **Create reusable icon components** for repeated SVGs
4. ✅ **Use semantic class names** that describe function, not appearance
5. ✅ **Test dark mode compatibility** for all new classes
6. ✅ **Ensure responsive behavior** across all breakpoints

#### Required CSS Class Categories:
- **Layout**: `.feature-card`, `.modal-container`, `.grid-system`
- **Interactive**: `.button-variants`, `.form-controls`, `.action-groups`  
- **Status**: `.status-success/warning/error`, `.progress-indicators`
- **Components**: `.workflow-components`, `.statistics-cards`, `.navigation-items`

#### Performance Requirements:
- **Line Length**: Maximum 120 characters per line
- **CSS Classes**: Minimum 20 reusable classes per complex page
- **Maintenance**: Zero inline style repetition across templates
- **Semantic**: Class names must indicate purpose, not styling

### Implementation Priority

1. **Critical**: Forms, modals, complex interactive components
2. **High**: Dashboard cards, statistics displays, navigation elements  
3. **Medium**: Simple cards, basic layouts, static content
4. **Low**: Single-use elements, one-off styling

### Code Review Standards

Any Blade template or Vue component must pass:
- ✅ No lines >120 characters
- ✅ Consistent CSS class naming
- ✅ Proper component extraction
- ✅ Dark mode compatibility
- ✅ Mobile responsiveness
- ✅ Performance optimization

**IMPORTANT**: Always prioritize maintainability and reusability over quick implementation. The 19-phase refactoring effort established these patterns to ensure consistent, scalable, and maintainable code across the entire attendance management system.
