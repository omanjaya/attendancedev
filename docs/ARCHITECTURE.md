# 🏗️ System Architecture

Arsitektur sistem Attendance Management System.

## Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         PRODUCTION                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│    ┌──────────┐     ┌──────────┐     ┌──────────────────┐       │
│    │  Users   │────▶│  Nginx   │────▶│  React SPA       │       │
│    │ (Mobile/ │     │  (SSL)   │     │  (Static Files)  │       │
│    │ Desktop) │     └────┬─────┘     └──────────────────┘       │
│    └──────────┘          │                                       │
│                          │ /api/*                                │
│                          ▼                                       │
│                   ┌──────────────┐                               │
│                   │  PHP-FPM    │                                │
│                   │  (Laravel)  │                                │
│                   └──────┬───────┘                               │
│                          │                                       │
│            ┌─────────────┼─────────────┐                        │
│            ▼             ▼             ▼                        │
│     ┌───────────┐ ┌───────────┐ ┌───────────────┐              │
│     │PostgreSQL │ │   Redis   │ │   DeepFace    │              │
│     │(Database) │ │ (Cache/   │ │  (Python)     │              │
│     └───────────┘ │  Queue)   │ │  :8001-8005   │              │
│                   └───────────┘ └───────────────┘              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Components

### 1. Frontend (React SPA)

**Tech Stack:**

- React 19 (Concurrent Features)
- TypeScript 5.x
- TanStack Router (Type-safe routing)
- TanStack Query (Server state)
- Zustand (Client state)
- Tailwind CSS 4 + shadcn/ui

**Key Features:**

- Mobile-first responsive design
- Offline capability (PWA)
- Client-side face detection (MediaPipe)
- Real-time notifications (Pusher)

### 2. Backend (Laravel API)

**Tech Stack:**

- Laravel 12 (PHP 8.2+)
- Sanctum (API Authentication)
- spatie/laravel-permission (RBAC)
- L5-Swagger (API Documentation)

**Architecture Pattern: Service Layer**

```
Request → Controller → Service → Repository → Model
                          │
                          └→ External Services (DeepFace, etc)
```

**Layer Responsibilities:**

| Layer | Responsibility |
|-------|----------------|
| Controller | HTTP handling, validation delegation |
| Service | Business logic, transactions |
| Repository | Data access, query building |
| Model | Data structure, relationships |

### 3. Database (PostgreSQL)

**Schema Overview:**

```sql
-- Core Tables
users           -- Authentication
employees       -- Employee data
locations       -- Office locations

-- Attendance
attendances     -- Check-in/out records
schedules       -- Work schedules
monthly_schedules -- Monthly assignments

-- Leave Management
leave_types     -- Types of leave
leave_balances  -- Employee balances
leave_requests  -- Leave applications

-- Payroll
payrolls        -- Payroll records
payroll_deductions
payroll_bonuses

-- Face Recognition
face_recognition_data -- Face embeddings
```

### 4. Face Recognition (DeepFace)

**Architecture:**

- 5-instance cluster for load balancing
- ArcFace model (99.82% accuracy)
- Round-robin request distribution
- Health check monitoring

**Flow:**

```
Client → Laravel → DeepFace Cluster → Response
                        │
                   ┌────┼────┬────┬────┐
                   ▼    ▼    ▼    ▼    ▼
                :8001 :8002 :8003 :8004 :8005
```

### 5. Cache & Queue (Redis)

**Usage:**

- Session storage
- Cache layer
- Queue driver
- Face embedding cache

## Data Flow

### Attendance Check-in Flow

```
1. User opens app
           │
2. GPS location captured
           │
3. Location validated against office radius
           │
4. Face captured via camera
           │
5. Client-side liveness detection
           │
6. Face sent to server
           │
7. Laravel validates session
           │
8. DeepFace verifies face
           │
9. Attendance record created
           │
10. Success response to client
```

### Authentication Flow

```
1. POST /api/v1/login (email, password)
           │
2. Validate credentials
           │
3. Generate Sanctum token
           │
4. Return token + user data
           │
5. Client stores token
           │
6. Subsequent requests with: Authorization: Bearer {token}
```

## Security

### Authentication

- Sanctum token-based auth
- Optional 2FA via TOTP
- Session encryption
- CSRF protection

### Authorization

- Role-based access control (RBAC)
- Policy-based authorization
- Route middleware protection

### Data Protection

- HTTPS only
- SQL injection prevention (Eloquent)
- XSS protection (React auto-escaping)
- CORS configuration

## Performance Optimizations

### Backend

- OPcache enabled
- Route caching
- Config caching
- Query optimization
- Redis caching

### Frontend

- Code splitting
- Lazy loading
- Image optimization
- Service worker caching

### Database

- Proper indexing
- Connection pooling
- Query optimization
- Read replicas (optional)

## Scalability

### Horizontal Scaling

- Stateless API design
- Redis for session sharing
- Load balancer ready

### Vertical Scaling

- PHP-FPM worker tuning
- PostgreSQL connection pooling
- Redis memory optimization

## Monitoring

### Application

- Laravel logs
- Error tracking
- Performance metrics

### Infrastructure

- Server metrics (CPU, RAM, Disk)
- Database connections
- Redis memory
- Queue length
