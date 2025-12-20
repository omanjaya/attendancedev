# 📚 Attendance System - Complete Flow Documentation

**Version:** 1.0
**Last Updated:** 2025-12-20
**Total Documentation:** ~321 KB across 6 phases

---

## 🎯 Overview

Dokumentasi ini mencakup seluruh flow aplikasi Attendance Management System dari perspektif:
- **Frontend** (React 19, TypeScript, TanStack)
- **Backend** (Laravel 12, PHP 8.3)
- **Face Recognition** (Python DeepFace/ArcFace)
- **Database** (PostgreSQL 16)
- **Infrastructure** (Docker, Redis, Nginx)

---

## 📋 Table of Contents

| Phase | Dokumentasi | Deskripsi | Size |
|-------|-------------|-----------|------|
| 1 | [Authentication & User Management](./FLOW_PHASE1_AUTH.md) | Login, 2FA, Roles, Permissions | 36 KB |
| 2 | [Employee & Master Data](./FLOW_PHASE2_MASTER_DATA.md) | CRUD Employees, Departments, Positions, dll | 53 KB |
| 3 | [Attendance & Face Recognition](./FLOW_PHASE3_ATTENDANCE.md) | Check-in/out, Face verification, Liveness | 63 KB |
| 4 | [Leave & Corrections](./FLOW_PHASE4_LEAVE_CORRECTIONS.md) | Cuti, Koreksi absensi, Approval workflow | 59 KB |
| 5 | [Scheduling & Teaching](./FLOW_PHASE5_SCHEDULING.md) | Jadwal kerja, Jadwal mengajar, Grade builder | 80 KB |
| 6 | [Reports & Analytics](./FLOW_PHASE6_REPORTS.md) | Dashboard, Laporan, Export Excel/PDF | 30 KB |

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        FRONTEND (React 19)                       │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐            │
│  │  Login  │  │Dashboard│  │Attendance│ │ Reports │            │
│  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘            │
│       │            │            │            │                   │
│       └────────────┴─────┬──────┴────────────┘                   │
│                          │                                       │
│              ┌───────────▼───────────┐                          │
│              │   TanStack Query      │                          │
│              │   (API Client)        │                          │
│              └───────────┬───────────┘                          │
└──────────────────────────┼──────────────────────────────────────┘
                           │ HTTPS/API
┌──────────────────────────▼──────────────────────────────────────┐
│                         NGINX                                    │
│                    (Reverse Proxy)                               │
└──────────┬────────────────────────────────────┬─────────────────┘
           │                                    │
┌──────────▼──────────┐              ┌─────────▼─────────┐
│  LARAVEL BACKEND    │              │  DEEPFACE SERVICE │
│  (PHP 8.3)          │◄────────────►│  (Python)         │
│                     │    HTTP      │                   │
│  • Auth/Sanctum     │              │  • ArcFace 512-d  │
│  • Controllers      │              │  • RetinaFace     │
│  • Services         │              │  • Liveness Check │
│  • Repositories     │              │                   │
└──────────┬──────────┘              └───────────────────┘
           │
    ┌──────┴──────┐
    │             │
┌───▼───┐    ┌───▼───┐
│ Redis │    │Postgres│
│ Cache │    │   DB   │
└───────┘    └────────┘
```

---

## 👥 User Roles & Access

| Role | Dashboard | Employees | Attendance | Leave | Schedules | Reports |
|------|-----------|-----------|------------|-------|-----------|---------|
| **super-admin** | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full |
| **admin** | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full |
| **kepala-sekolah** | ✅ View | ✅ View | ✅ View | ✅ Approve | ✅ View | ✅ Full |
| **guru** | ✅ Own | ❌ | ✅ Own | ✅ Request | ✅ Own | ✅ Own |
| **pegawai** | ✅ Own | ❌ | ✅ Own | ✅ Request | ❌ | ✅ Own |

---

## 🔄 Main User Flows

### Flow 1: Employee Check-In (Face Recognition)
```
Employee → Open App → Dashboard → "Check-In" Button
    │
    ▼
Camera Opens → Face Detected → Liveness Check
    │
    ▼
Face Verified (ArcFace) → Location Validated → Time Validated
    │
    ▼
Attendance Record Created → Success Notification
```
📖 [Detail: Phase 3 - Attendance Flow](./FLOW_PHASE3_ATTENDANCE.md)

---

### Flow 2: Leave Request & Approval
```
Employee → Leave Page → Create Request → Submit
    │
    ▼
Admin/Kepala Sekolah → Pending Requests → Review
    │
    ▼
Approve/Reject → Employee Notified → Balance Updated
```
📖 [Detail: Phase 4 - Leave & Corrections](./FLOW_PHASE4_LEAVE_CORRECTIONS.md)

---

### Flow 3: Admin Creates Teaching Schedule
```
Admin → Schedules → Grade Builder → Select Grade
    │
    ▼
Drag & Drop Teachers → Assign Time Slots → Conflict Check
    │
    ▼
Save Schedule → Teachers Can View Their Schedule
```
📖 [Detail: Phase 5 - Scheduling](./FLOW_PHASE5_SCHEDULING.md)

---

### Flow 4: Generate Monthly Report
```
Admin → Reports → Select Report Type → Set Filters
    │
    ▼
Date Range, Department, Employee Type
    │
    ▼
Generate → View → Export (Excel/PDF)
```
📖 [Detail: Phase 6 - Reports](./FLOW_PHASE6_REPORTS.md)

---

## 🗄️ Database Overview

### Core Tables

| Table | Description | Phase |
|-------|-------------|-------|
| `users` | User accounts, auth, 2FA | Phase 1 |
| `employees` | Employee data, face embeddings | Phase 2 |
| `departments` | Organizational structure | Phase 2 |
| `positions` | Job positions | Phase 2 |
| `locations` | Physical locations for attendance | Phase 2 |
| `attendances` | Daily attendance records | Phase 3 |
| `leave_requests` | Leave/cuti requests | Phase 4 |
| `attendance_corrections` | Correction requests | Phase 4 |
| `work_schedules` | Default work schedules | Phase 5 |
| `teaching_schedules` | Teacher class schedules | Phase 5 |

### Key Relationships

```
users 1:1 employees
employees N:1 departments
employees N:1 positions
employees 1:N attendances
employees 1:N leave_requests
employees 1:N teaching_schedules
```

---

## 🔌 API Endpoints Summary

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Login with email/password |
| POST | `/api/v1/auth/logout` | Logout |
| GET | `/api/v1/auth/me` | Get current user |
| POST | `/api/v1/auth/change-password` | Change password |

### Employees
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/employees` | List employees |
| POST | `/api/v1/employees` | Create employee |
| GET | `/api/v1/employees/{id}` | Get employee |
| PUT | `/api/v1/employees/{id}` | Update employee |
| DELETE | `/api/v1/employees/{id}` | Delete employee |

### Attendance
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/attendance-face/check-in` | Check-in with face |
| POST | `/api/v1/attendance-face/check-out` | Check-out with face |
| GET | `/api/v1/attendance/today` | Today's attendance |
| GET | `/api/v1/attendance/history` | Attendance history |

### Face Recognition
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/face/deepface/verify` | Verify face |
| POST | `/api/v1/face/deepface/extract-embedding` | Extract face embedding |
| POST | `/api/v1/face/deepface/check-liveness` | Anti-spoofing check |

### Leave & Corrections
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/leave-requests` | List leave requests |
| POST | `/api/v1/leave-requests` | Create leave request |
| POST | `/api/v1/leave-requests/{id}/approve` | Approve request |
| POST | `/api/v1/leave-requests/{id}/reject` | Reject request |

### Schedules
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/schedules/grade` | Save grade schedule |
| GET | `/api/v1/schedules/grade/{grade}` | Load grade schedule |
| GET | `/api/v1/schedules/my-schedule` | Get my schedule |

### Reports
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/reports/attendance` | Attendance report |
| GET | `/api/v1/reports/leave` | Leave report |
| GET | `/api/v1/reports/employees` | Employee report |

---

## 🛡️ Security Features

| Feature | Implementation | Phase |
|---------|----------------|-------|
| Authentication | Laravel Sanctum (token-based) | Phase 1 |
| 2FA | TOTP with recovery codes | Phase 1 |
| CAPTCHA | Cloudflare Turnstile | Phase 1 |
| Rate Limiting | Laravel throttle middleware | Phase 1 |
| RBAC | Spatie Permission | Phase 1 |
| Face Verification | DeepFace ArcFace 512-d | Phase 3 |
| Liveness Detection | Anti-spoofing via DeepFace | Phase 3 |
| XSS Protection | CSP headers | All |
| CSRF Protection | Laravel CSRF tokens | All |

---

## 📱 Frontend Structure

```
frontend/src/
├── pages/
│   ├── admin/           # Admin pages
│   │   ├── dashboard/
│   │   ├── employees/
│   │   ├── attendance/
│   │   ├── leave-requests/
│   │   ├── schedules/
│   │   └── reports/
│   ├── employee/        # Employee pages
│   │   ├── dashboard/
│   │   ├── attendance/
│   │   ├── leave/
│   │   └── profile/
│   └── login.tsx
├── components/
│   ├── ui/              # shadcn/ui components
│   └── shared/          # Shared components
├── lib/
│   ├── api/             # API clients
│   └── utils/           # Utilities
├── stores/              # Zustand stores
└── hooks/               # Custom hooks
```

---

## 🐳 Docker Services

| Service | Container | Port | Description |
|---------|-----------|------|-------------|
| PostgreSQL | attendancedev-postgres | 5432 | Database |
| Redis | attendancedev-redis | 6379 | Cache & Session |
| Backend | attendancedev-backend | - | Laravel API |
| Frontend | attendancedev-frontend | 5173 | React Dev |
| DeepFace | attendancedev-deepface-1 | 8001 | Face Recognition |
| DeepFace | attendancedev-deepface-2 | 8002 | Face Recognition (replica) |
| Nginx | attendancedev-nginx | 80, 443 | Reverse Proxy |
| Adminer | attendancedev-adminer | 8080 | DB Admin (dev only) |

---

## 📖 Related Documentation

- [Deployment Guide](./DEPLOYMENT_GUIDE.md)
- [API Documentation](./API.md)
- [Architecture Overview](./ARCHITECTURE.md)
- [System Flow Analysis](./SYSTEM_FLOW_ANALYSIS.md)

---

## 📝 Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2025-12-20 | 1.0 | Initial complete documentation |

---

*Generated by Claude Code - Attendance Management System Documentation*
