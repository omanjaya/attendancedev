# Phase 2: Employee & Master Data Management Flow

**Attendance Management System Documentation**

Last Updated: 2025-12-20

---

## Table of Contents

1. [Employee Management](#1-employee-management)
2. [Master Data - Employee Types](#2-master-data---employee-types)
3. [Master Data - Departments](#3-master-data---departments)
4. [Master Data - Positions](#4-master-data---positions)
5. [Master Data - Locations](#5-master-data---locations)
6. [Master Data - Subjects](#6-master-data---subjects)
7. [Master Data - Classrooms](#7-master-data---classrooms)
8. [Master Data - Academic Years](#8-master-data---academic-years)
9. [Master Data - Holidays](#9-master-data---holidays)
10. [Database Schema](#10-database-schema)

---

## 1. Employee Management

### Overview
Employee management handles CRUD operations for all employee records, including teachers (guru), staff (pegawai), and honorary teachers (guru honorer). The system supports bulk operations, credential management, and integration with face recognition.

### Frontend Implementation

#### Pages
- **Location**: `/opt/attendancedev/frontend/src/pages/admin/employees/`
- **Main Entry**: `index.tsx` (responsive wrapper)
- **Desktop View**: `desktop.tsx`
- **Mobile View**: `mobile.tsx`
- **Create Form**: `create.tsx`
- **Edit Form**: `edit.tsx`
- **Detail View**: `show.tsx`
- **Credentials**: `credentials.tsx` (manage user accounts)

#### API Client
**Location**: `/opt/attendancedev/frontend/src/lib/api/employees.ts`

**Key Functions**:
```typescript
// List employees with filters
getEmployees(filters?: EmployeeFilters): Promise<PaginatedResponse<Employee>>

// Get single employee
getEmployee(id: string): Promise<Employee>

// Create employee
createEmployee(data: EmployeeFormData): Promise<Employee>

// Update employee
updateEmployee(id: string, data: Partial<EmployeeFormData>): Promise<Employee>

// Delete employee
deleteEmployee(id: string): Promise<void>

// Search employees
searchEmployees(query: string): Promise<Employee[]>

// Get statistics
getEmployeeStatistics(): Promise<EmployeeStatistics>

// Dashboard data
getEmployeeDashboardData(): Promise<EmployeeDashboardData>
getEmployeeDashboardById(id: string): Promise<EmployeeDashboardData>

// Avatar management
uploadEmployeeAvatar(employeeId: string, file: File): Promise<{ avatar_url: string }>
deleteEmployeeAvatar(employeeId: string): Promise<void>

// Password reset (admin only)
resetEmployeePassword(employeeId: string, newPassword?: string): Promise<void>

// Bulk operations
bulkAction(action: string, employeeIds: string[]): Promise<void>
```

#### Employee Form Fields
```typescript
interface EmployeeFormData {
  // Basic Info
  full_name: string;              // Required
  email: string;                  // Required, unique
  phone?: string;
  employee_code?: string;         // Auto-generated if empty

  // Employee Type & Role
  employee_type_id: string;       // Required, FK to employee_types
  role?: 'pegawai' | 'guru' | 'admin' | 'kepala-sekolah';

  // Department/Position (for staff)
  department_id?: string;         // FK to departments (for pegawai)
  position_id?: string;           // FK to positions (for pegawai)

  // Subject (for teachers)
  subject_id?: string;            // FK to subjects (for guru)

  // Location
  location_id?: string;           // FK to locations

  // Employment Details
  hire_date?: Date;
  salary_type?: 'monthly' | 'hourly';
  base_salary?: number;

  // Status
  is_active: boolean;             // Default: true

  // Account Creation
  password?: string;              // Min 8 chars

  // Additional Personal Data
  birth_date?: Date;
  birth_place?: string;
  gender?: 'male' | 'female';
  nik?: string;                   // National ID
  npwp?: string;                  // Tax ID
  marital_status?: 'single' | 'married' | 'divorced' | 'widowed';
  religion?: string;
  address?: string;
  emergency_contact?: object;     // JSON
  education?: object;             // JSON
  bank_info?: object;             // JSON
}
```

### API Endpoints

#### Base URL: `/api/v1/employees`

| Method | Endpoint | Description | Auth | Request | Response |
|--------|----------|-------------|------|---------|----------|
| GET | `/` | List employees | Required | `?page=1&per_page=15&search=...&employee_type_id=...&department_id=...&is_active=...` | `PaginatedResponse<Employee>` |
| POST | `/` | Create employee | Admin | `EmployeeFormData` | `Employee` |
| GET | `/search` | Search employees | Required | `?q=query` | `Employee[]` |
| GET | `/statistics` | Get statistics | Required | - | `EmployeeStatistics` |
| GET | `/dashboard` | Current user dashboard | Required | - | `EmployeeDashboardData` |
| GET | `/with-face-data` | Employees with face | Required | - | `Employee[]` |
| GET | `/{id}` | Get employee details | Required | - | `Employee` |
| GET | `/{id}/dashboard` | Employee dashboard (admin) | Admin | - | `EmployeeDashboardData` |
| PUT | `/{id}` | Update employee | Admin/Owner | `Partial<EmployeeFormData>` | `Employee` |
| DELETE | `/{id}` | Delete employee | Admin | - | `void` |
| POST | `/{id}/avatar` | Upload avatar | Admin/Owner | `FormData` | `{ avatar_url }` |
| DELETE | `/{id}/avatar` | Delete avatar | Admin/Owner | - | `void` |
| POST | `/{id}/reset-password` | Reset password | Admin | `{ new_password?, send_email? }` | `{ password }` |
| POST | `/bulk` | Bulk operations | Admin | `{ action, employee_ids[] }` | `{ results[], summary }` |

#### Employee Credentials Management
**Base URL**: `/api/v1/employees/credentials`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/stats` | Get user account statistics |
| GET | `/without-users` | Employees without user accounts |
| GET | `/with-users` | Employees with user accounts |
| POST | `/create-users` | Create user accounts in bulk |
| POST | `/reset-passwords` | Reset passwords in bulk |

### Backend Implementation

#### Controller
**Location**: `/opt/attendancedev/backend/app/Http/Controllers/Api/EmployeeApiController.php`

**Key Methods**:
- `index()` - List employees with filters, pagination
- `store()` - Create new employee
- `show($id)` - Get employee details (with IDOR protection)
- `update($id)` - Update employee
- `destroy($id)` - Delete employee
- `search()` - Search employees by name/email
- `statistics()` - Get employee statistics
- `dashboard()` - Get current employee dashboard
- `dashboardById($id)` - Get employee dashboard by ID (admin)
- `uploadAvatar($id)` - Upload employee avatar
- `deleteAvatar($id)` - Delete employee avatar
- `resetPassword($id)` - Reset employee password (admin)
- `bulk()` - Bulk actions (delete, activate, deactivate, reset_password)

**Validation Rules** (store):
```php
[
    'full_name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'phone' => 'nullable|string|max:20',
    'employee_code' => 'nullable|string|unique:employees,employee_id',
    'department' => 'sometimes|string|max:100',
    'position' => 'sometimes|string|max:100',
    'employee_type_id' => 'required|exists:employee_types,id',
    'salary_type' => 'sometimes|in:monthly,hourly',
    'base_salary' => 'sometimes|numeric|min:0',
    'hire_date' => 'sometimes|date',
    'is_active' => 'boolean',
    'password' => 'nullable|string|min:8',
    'role' => 'nullable|string|in:pegawai,guru,admin,kepala-sekolah',
    'location_id' => 'nullable|uuid|exists:locations,id',
    'subject_id' => 'nullable|uuid|exists:subjects,id',
    'department_id' => 'nullable|uuid|exists:departments,id',
    'position_id' => 'nullable|uuid|exists:positions,id',
]
```

#### Service Layer
**Location**: `/opt/attendancedev/backend/app/Services/Employee/`

**Services**:
1. **EmployeeService** - Core CRUD operations
2. **EmployeeStatisticsService** - Statistics and dashboard data
3. **EmployeeAvatarService** - Avatar upload/delete, password reset
4. **EmployeeBulkService** - Bulk operations
5. **EmployeeIdGeneratorService** - Auto-generate employee IDs

#### Model
**Location**: `/opt/attendancedev/backend/app/Models/Employee.php`

**Fillable Fields**:
```php
[
    'user_id', 'employee_id', 'full_name', 'phone',
    'photo_path', 'employee_type', 'employee_type_id', 'hire_date',
    'salary_type', 'salary_amount', 'hourly_rate', 'location_id',
    'metadata', 'sensitive_data', 'is_active',
    'subject_id', 'department_id', 'position_id',
]
```

**Relationships**:
- `user()` - BelongsTo User
- `location()` - BelongsTo Location
- `employeeTypeRelation()` - BelongsTo EmployeeType
- `subject()` - BelongsTo Subject (for teachers)
- `departmentRelation()` - BelongsTo Department (for staff)
- `positionRelation()` - BelongsTo Position (for staff)
- `attendances()` - HasMany Attendance
- `leaves()` - HasMany Leave
- `schedules()` - HasMany EmployeeSchedule
- `monthlySchedules()` - HasMany EmployeeMonthlySchedule
- `teachingSchedules()` - HasMany TeachingSchedule

**Accessors**:
- `name` - Returns `full_name`
- `email` - From user or metadata
- `department` - From departmentRelation or metadata
- `position` - From positionRelation or metadata
- `status` - 'active' or 'inactive'
- `photo_url` - Full URL or ui-avatars fallback
- `face_registered` - Boolean if face descriptor exists
- `role` - From user roles
- `roles` - Array of role names

**Scopes**:
- `active()` - Where is_active = true
- `withTodayAttendance()` - Eager load today's attendance
- `withLatestAttendance()` - Eager load latest attendance

**Business Methods**:
- `isGuruHonorer()` - Check if employee is honorary teacher
- `getScheduleForDate($date)` - Get schedule for specific date
- `getTodaySchedule()` - Get today's schedule
- `getEffectiveScheduleForDate($date)` - Get effective schedule (handles flexible/fixed)
- `getTeachingSchedulesForDate($date)` - Get all teaching sessions for date

### Database Schema

**Table**: `employees`

```sql
CREATE TABLE employees (
    id UUID PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    employee_id VARCHAR(50) UNIQUE,
    employee_type ENUM('permanent', 'honorary', 'staff'),  -- Legacy
    employee_type_id UUID REFERENCES employee_types(id),   -- New FK
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    hire_date DATE NOT NULL,
    salary_type ENUM('hourly', 'monthly', 'fixed'),
    salary_amount DECIMAL(10,2),
    hourly_rate DECIMAL(8,2),
    is_active BOOLEAN DEFAULT TRUE,
    location_id UUID REFERENCES locations(id),
    subject_id UUID REFERENCES subjects(id),           -- For teachers
    department_id UUID REFERENCES departments(id),     -- For staff
    position_id UUID REFERENCES positions(id),         -- For staff
    photo_path VARCHAR(255),
    metadata JSONB DEFAULT '{}',                       -- Public metadata
    sensitive_data TEXT,                               -- Encrypted (face_descriptor, etc)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,

    INDEX idx_employee_type (employee_type),
    INDEX idx_is_active (is_active),
    INDEX idx_full_name (full_name)
);
```

**Metadata Structure**:
```json
{
  "email": "employee@school.edu",
  "department": "IT Department",  // Legacy
  "position": "Staff",            // Legacy
  "address": "Jl. Example 123",
  "birth_date": "1990-01-01",
  "birth_place": "Jakarta",
  "gender": "male",
  "nik": "1234567890123456",
  "npwp": "12.345.678.9-012.000",
  "marital_status": "married",
  "religion": "Islam",
  "emergency_contact": {
    "name": "Jane Doe",
    "phone": "08123456789",
    "relationship": "spouse"
  },
  "education": {
    "degree": "Bachelor",
    "major": "Computer Science",
    "university": "University of Indonesia",
    "graduation_year": 2012
  },
  "bank_info": {
    "bank_name": "BCA",
    "account_number": "1234567890",
    "account_holder": "John Doe"
  }
}
```

**Sensitive Data** (encrypted):
```json
{
  "face_recognition": {
    "descriptor": [...],  // Removed from API responses
    "registered_at": "2025-01-15T10:30:00Z"
  }
}
```

### Features

#### 1. List Employees
- **Pagination**: 15 per page (configurable)
- **Filters**:
  - Search by name/email
  - Filter by employee_type_id
  - Filter by department_id
  - Filter by is_active
- **Sorting**: By name, hire_date, employee_id

#### 2. Create Employee
- Auto-generate employee_id if not provided
- Create linked user account (optional)
- Assign role based on employee type
- Link to department/position (for staff)
- Link to subject (for teachers)
- Assign location
- Upload avatar (optional)

#### 3. Edit Employee
- Update all employee fields
- Change employee type (updates related fields)
- Update department/position/subject
- Change location
- Toggle active status
- IDOR protection (admins only or owner)

#### 4. Delete/Deactivate Employee
- Soft delete (preserves historical data)
- Cascade deletes related data
- Check for existing attendance/leave records

#### 5. Employee Types
Based on `employee_type_id`:
- **Guru PNS** - Fixed schedule teacher
- **Guru Honor** - Flexible schedule (teaching-based)
- **Pegawai** - Staff with department/position
- **Custom Types** - Configurable via EmployeeType master data

#### 6. Link to User Account
- Create user account for employee
- Auto-generate password (or custom)
- Assign role (guru, pegawai, admin, kepala-sekolah)
- Email credentials to employee (optional)
- Bulk create accounts for multiple employees

#### 7. Bulk Operations
- **Actions**: delete, activate, deactivate, reset_password
- **Authorization**: Admin only
- **Response**: Success/failure for each employee

---

## 2. Master Data - Employee Types

### Overview
Employee Types define the behavior and schedule requirements for different employee categories. This replaces the legacy `employee_type` enum field with a flexible, configurable system.

### Frontend Implementation

#### Pages
**Location**: `/opt/attendancedev/frontend/src/pages/admin/settings/employee-types/index.tsx`

#### API Client
**Location**: `/opt/attendancedev/frontend/src/lib/api/master-data.ts`

**Key Functions**:
```typescript
getEmployeeTypes(params?: any): Promise<PaginatedResponse<EmployeeType>>
getEmployeeTypesAll(): Promise<EmployeeType[]>  // For dropdowns
getEmployeeType(id: string): Promise<EmployeeType>
getEmployeeTypeFeatures(): Promise<Record<string, string>>
createEmployeeType(data: Partial<EmployeeType>): Promise<EmployeeType>
updateEmployeeType(id: string, data: Partial<EmployeeType>): Promise<EmployeeType>
deleteEmployeeType(id: string): Promise<void>
reorderEmployeeTypes(order: { id: string, sort_order: number }[]): Promise<void>
```

#### Employee Type Form Fields
```typescript
interface EmployeeType {
  id: string;
  name: string;                         // e.g., "Guru PNS", "Guru Honor"
  code: string;                         // e.g., "GPNS", "GHONOR"
  description?: string;
  schedule_mode: 'fixed' | 'flexible';  // Fixed=monthly schedule, Flexible=teaching schedule
  is_active: boolean;
  sort_order: number;

  // Optional schedule configuration
  default_start_time?: string;          // e.g., "07:00"
  default_end_time?: string;            // e.g., "15:00"
  work_days?: string[];                 // e.g., ["Mon", "Tue", "Wed", "Thu", "Fri"]
  late_tolerance_minutes?: number;      // Default: 15

  // Behavior flags
  require_schedule_for_attendance?: boolean;  // Default: true
  can_override_by_teaching?: boolean;         // For flexible teachers

  // Counts
  employees_count?: number;
}
```

### API Endpoints

#### Base URL: `/api/v1/admin/employee-types`

| Method | Endpoint | Description | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/` | List employee types | `?search=...&is_active=...&schedule_mode=...&all=true` | `PaginatedResponse<EmployeeType>` or `EmployeeType[]` |
| POST | `/` | Create employee type | `{ name, code, schedule_mode, ... }` | `EmployeeType` |
| GET | `/{id}` | Get employee type | - | `EmployeeType` |
| PUT | `/{id}` | Update employee type | `Partial<EmployeeType>` | `EmployeeType` |
| DELETE | `/{id}` | Delete employee type | - | `void` |
| GET | `/features` | Get available features | - | `Record<string, string>` |
| POST | `/reorder` | Reorder employee types | `{ order: [{ id, sort_order }] }` | `void` |

### Backend Implementation

#### Controller
**Location**: `/opt/attendancedev/backend/app/Http/Controllers/Api/EmployeeTypeApiController.php`

**Authorization**: Admin or Super Admin only

**Key Methods**:
- `index()` - List with filters, supports `?all=true` for dropdowns
- `store()` - Create with validation
- `show($id)` - Get with employee count
- `update($id)` - Update
- `destroy($id)` - Delete (prevents if employees exist)
- `features()` - Get available features list
- `reorder()` - Update sort_order for multiple records

#### Model
**Location**: `/opt/attendancedev/backend/app/Models/EmployeeType.php`

**Relationships**:
- `employees()` - HasMany Employee

**Scopes**:
- `active()` - Where is_active = true
- `ordered()` - Order by sort_order

**Methods**:
- `isFlexible()` - Returns true if schedule_mode = 'flexible'
- `availableFeatures()` - Static method returning feature definitions

### Database Schema

**Table**: `employee_types`

```sql
CREATE TABLE employee_types (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    schedule_mode ENUM('fixed', 'flexible') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,

    -- Schedule defaults
    default_start_time TIME,
    default_end_time TIME,
    work_days JSONB,  -- ["Mon", "Tue", "Wed", "Thu", "Fri"]
    late_tolerance_minutes INTEGER DEFAULT 15,

    -- Behavior flags
    require_schedule_for_attendance BOOLEAN DEFAULT TRUE,
    can_override_by_teaching BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_active_order (is_active, sort_order)
);
```

### Default Employee Types

```sql
INSERT INTO employee_types (name, code, description, schedule_mode, can_override_by_teaching) VALUES
('Guru PNS', 'GPNS', 'Guru Pegawai Negeri Sipil - Fixed schedule', 'fixed', false),
('Guru Honor', 'GHONOR', 'Guru Honorer - Teaching schedule based', 'flexible', true),
('Pegawai', 'STAFF', 'Staff administrasi/TU', 'fixed', false),
('Kepala Sekolah', 'KEPSEK', 'Kepala Sekolah', 'fixed', false);
```

---

## 3. Master Data - Departments

### Overview
Departments (Unit Kerja) are organizational units for staff employees. Examples: IT Department, Finance, Administration, HR.

### Frontend Implementation

#### API Client
**Location**: `/opt/attendancedev/frontend/src/lib/api/master-data.ts`

**Key Functions**:
```typescript
getDepartments(params?: any): Promise<PaginatedResponse<Department>>
getDepartmentsAll(): Promise<Department[]>  // For dropdowns
getDepartment(id: string): Promise<Department>
createDepartment(data: Partial<Department>): Promise<Department>
updateDepartment(id: string, data: Partial<Department>): Promise<Department>
deleteDepartment(id: string): Promise<void>
reorderDepartments(order: { id: string, sort_order: number }[]): Promise<void>
```

#### Department Form Fields
```typescript
interface Department {
  id: string;
  name: string;            // e.g., "IT Department"
  code: string;            // e.g., "IT"
  description?: string;
  is_active: boolean;
  sort_order: number;
  employees_count?: number;
}
```

### API Endpoints

#### Base URL: `/api/v1/admin/departments`

| Method | Endpoint | Description | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/` | List departments | `?search=...&is_active=...&all=true` | `PaginatedResponse<Department>` or `Department[]` |
| POST | `/` | Create department | `{ name, code, description?, is_active? }` | `Department` |
| GET | `/{id}` | Get department | - | `Department` |
| PUT | `/{id}` | Update department | `Partial<Department>` | `Department` |
| DELETE | `/{id}` | Delete department | - | `void` |
| POST | `/reorder` | Reorder departments | `{ order: [{ id, sort_order }] }` | `void` |

### Backend Implementation

#### Controller
**Location**: `/opt/attendancedev/backend/app/Http/Controllers/Api/DepartmentApiController.php`

**Authorization**: Admin or Super Admin only

**Validation Rules**:
```php
[
    'name' => 'required|string|max:100',
    'code' => 'required|string|max:50|unique:departments,code',
    'description' => 'nullable|string|max:500',
    'is_active' => 'boolean',
]
```

**Delete Protection**: Cannot delete if employees are assigned to this department.

#### Model
**Location**: `/opt/attendancedev/backend/app/Models/Department.php`

**Relationships**:
- `employees()` - HasMany Employee

**Scopes**:
- `active()` - Where is_active = true
- `ordered()` - Order by sort_order

### Database Schema

**Table**: `departments`

```sql
CREATE TABLE departments (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_active_order (is_active, sort_order)
);
```

### Example Data

```json
[
  { "name": "Tata Usaha", "code": "TU", "description": "Administrasi umum" },
  { "name": "IT & Sistem", "code": "IT", "description": "IT support dan sistem" },
  { "name": "Keuangan", "code": "FIN", "description": "Keuangan dan akuntansi" },
  { "name": "SDM", "code": "HR", "description": "Human Resources" },
  { "name": "Perpustakaan", "code": "LIB", "description": "Perpustakaan sekolah" }
]
```

---

## 4. Master Data - Positions

### Overview
Positions (Jabatan) define job titles for staff employees. Examples: Staff Admin, IT Support, Accountant, Librarian.

### Frontend Implementation

#### API Client
**Location**: `/opt/attendancedev/frontend/src/lib/api/master-data.ts`

**Key Functions**:
```typescript
getPositions(params?: any): Promise<PaginatedResponse<Position>>
getPositionsAll(): Promise<Position[]>  // For dropdowns
getPosition(id: string): Promise<Position>
createPosition(data: Partial<Position>): Promise<Position>
updatePosition(id: string, data: Partial<Position>): Promise<Position>
deletePosition(id: string): Promise<void>
reorderPositions(order: { id: string, sort_order: number }[]): Promise<void>
```

#### Position Form Fields
```typescript
interface Position {
  id: string;
  name: string;            // e.g., "Staff Admin"
  code: string;            // e.g., "SADM"
  description?: string;
  is_active: boolean;
  sort_order: number;
  employees_count?: number;
}
```

### API Endpoints

#### Base URL: `/api/v1/admin/positions`

| Method | Endpoint | Description | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/` | List positions | `?search=...&is_active=...&all=true` | `PaginatedResponse<Position>` or `Position[]` |
| POST | `/` | Create position | `{ name, code, description?, is_active? }` | `Position` |
| GET | `/{id}` | Get position | - | `Position` |
| PUT | `/{id}` | Update position | `Partial<Position>` | `Position` |
| DELETE | `/{id}` | Delete position | - | `void` |
| POST | `/reorder` | Reorder positions | `{ order: [{ id, sort_order }] }` | `void` |

### Backend Implementation

#### Controller
**Location**: `/opt/attendancedev/backend/app/Http/Controllers/Api/PositionApiController.php`

**Authorization**: Admin or Super Admin only

**Validation Rules**:
```php
[
    'name' => 'required|string|max:100',
    'code' => 'required|string|max:50|unique:positions,code',
    'description' => 'nullable|string|max:500',
    'is_active' => 'boolean',
]
```

**Delete Protection**: Cannot delete if employees are assigned to this position.

#### Model
**Location**: `/opt/attendancedev/backend/app/Models/Position.php`

**Relationships**:
- `employees()` - HasMany Employee

**Scopes**:
- `active()` - Where is_active = true
- `ordered()` - Order by sort_order

### Database Schema

**Table**: `positions`

```sql
CREATE TABLE positions (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_active_order (is_active, sort_order)
);
```

### Example Data

```json
[
  { "name": "Staff Administrasi", "code": "SADM", "description": "Staff administrasi umum" },
  { "name": "IT Support", "code": "ITSP", "description": "IT support technician" },
  { "name": "Akuntan", "code": "ACC", "description": "Accountant" },
  { "name": "Pustakawan", "code": "LIB", "description": "Librarian" },
  { "name": "Cleaning Service", "code": "CS", "description": "Cleaning service" },
  { "name": "Security", "code": "SEC", "description": "Security guard" }
]
```

---

## 5. Master Data - Locations

### Overview
Locations define physical attendance points with GPS coordinates and verification settings. Supports Work From Anywhere (WFA) configuration.

### Frontend Implementation

#### API Client
**Location**: `/opt/attendancedev/frontend/src/lib/api/locations.ts`

**Key Functions**:
```typescript
getLocations(): Promise<Location[]>
getLocation(id: string): Promise<Location>
createLocation(data: LocationFormData): Promise<Location>
updateLocation(id: string, data: Partial<LocationFormData>): Promise<Location>
deleteLocation(id: string): Promise<void>
toggleLocationStatus(id: string): Promise<Location>
assignEmployees(locationId: string, employeeIds: string[]): Promise<void>

// GPS Verification
verifyLocation(params: {
  latitude: number;
  longitude: number;
  location_id?: string;
}): Promise<{
  verified: boolean;
  distance?: number;
  location?: Location;
  message: string;
}>
```

#### Location Form Fields
```typescript
interface LocationFormData {
  name: string;                        // Required, e.g., "Kantor Pusat"
  address?: string;
  latitude?: number;                   // -90 to 90
  longitude?: number;                  // -180 to 180
  radius_meters: number;               // Default: 100
  require_face_recognition: boolean;   // Default: true
  is_active: boolean;                  // Default: true
  wifi_ssid?: string;                  // WiFi SSID for verification
  wifi_bssid?: string;                 // WiFi MAC address
}
```

### API Endpoints

#### Base URL: `/api/v1/admin/locations`

| Method | Endpoint | Description | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/` | List locations | - | `Location[]` |
| POST | `/` | Create location | `LocationFormData` | `Location` |
| GET | `/{id}` | Get location | - | `Location` |
| PUT | `/{id}` | Update location | `Partial<LocationFormData>` | `Location` |
| DELETE | `/{id}` | Delete location | - | `void` |
| POST | `/{id}/toggle-status` | Toggle active status | - | `Location` |
| POST | `/{id}/assign-employees` | Assign employees | `{ employee_ids: string[] }` | `void` |

#### Location Verification Endpoints
**Base URL**: `/api/v1/locations`

| Method | Endpoint | Description | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/select` | For dropdowns | - | `Location[]` (simplified) |
| POST | `/verify` | Verify GPS location | `{ latitude, longitude, location_id? }` | `{ verified, distance, location, message }` |
| POST | `/find-in-range` | Find nearby locations | `{ latitude, longitude, max_distance? }` | `Location[]` |

### Backend Implementation

#### Controller
**Location**: `/opt/attendancedev/backend/app/Http/Controllers/Api/LocationApiController.php`

**Services**:
- `LocationService` - CRUD operations
- `LocationVerificationService` - GPS distance calculation

**Validation Rules**:
```php
[
    'name' => 'required|string|max:255',
    'address' => 'nullable|string|max:500',
    'latitude' => 'nullable|numeric|between:-90,90',
    'longitude' => 'nullable|numeric|between:-180,180',
    'radius_meters' => 'required|integer|min:10|max:10000000',
    'require_face_recognition' => 'boolean',
    'is_active' => 'boolean',
    'wifi_ssid' => 'nullable|string|max:100',
    'wifi_bssid' => 'nullable|string|max:17',
]
```

**WFA (Work From Anywhere) Logic**:
- No GPS coordinates OR
- Radius >= 9999999 meters OR
- Name contains "remote" or "wfa"
- Always verified, no distance check

#### Model
**Location**: `/opt/attendancedev/backend/app/Models/Location.php`

**Relationships**:
- `employees()` - HasMany Employee
- `attendances()` - HasMany Attendance

**Methods**:
- `isWFA()` - Check if location is Work From Anywhere
- `verifyCoordinates($lat, $lng)` - Calculate distance and verify

### Database Schema

**Table**: `locations`

```sql
CREATE TABLE locations (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    latitude DECIMAL(10, 8),        -- GPS latitude
    longitude DECIMAL(11, 8),       -- GPS longitude
    radius_meters INTEGER DEFAULT 100,
    require_face_recognition BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    wifi_ssid VARCHAR(100),         -- Optional WiFi SSID
    wifi_bssid VARCHAR(17),         -- Optional WiFi MAC
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_active (is_active),
    INDEX idx_coordinates (latitude, longitude)
);
```

### GPS Distance Calculation

**Haversine Formula** (in LocationVerificationService):
```php
public function calculateDistance($lat1, $lng1, $lat2, $lng2): float
{
    $earthRadius = 6371000; // meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earthRadius * $c; // meters
}
```

### Example Data

```json
[
  {
    "name": "Kantor Pusat",
    "address": "Jl. Sudirman No. 123, Jakarta",
    "latitude": -6.2088,
    "longitude": 106.8456,
    "radius_meters": 100,
    "require_face_recognition": true
  },
  {
    "name": "Gedung A - Ruang Guru",
    "address": "Gedung A Lt. 2",
    "latitude": -6.2089,
    "longitude": 106.8457,
    "radius_meters": 50,
    "require_face_recognition": false
  },
  {
    "name": "Work From Anywhere",
    "address": "Remote",
    "latitude": null,
    "longitude": null,
    "radius_meters": 9999999,
    "require_face_recognition": false
  }
]
```

---

## 6. Master Data - Subjects

### Overview
Subjects (Mata Pelajaran) are courses taught by teachers. Used for teacher assignment and schedule management.

### Frontend Implementation

#### API Client
**Location**: `/opt/attendancedev/frontend/src/lib/api/master-data.ts`

**Key Functions**:
```typescript
getSubjects(params?: any): Promise<PaginatedResponse<Subject>>
getSubjectsAll(): Promise<Subject[]>  // For dropdowns
createSubject(data: Partial<Subject>): Promise<Subject>
updateSubject(id: string, data: Partial<Subject>): Promise<Subject>
deleteSubject(id: string): Promise<void>
```

#### Subject Form Fields
```typescript
interface Subject {
  id: string;
  name: string;           // e.g., "Matematika"
  code: string;           // e.g., "MTK"
  description?: string;
  is_active: boolean;
}
```

### API Endpoints

#### Base URL: `/api/v1/admin/subjects`

| Method | Endpoint | Description | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/` | List subjects | `?search=...&per_page=15` | `PaginatedResponse<Subject>` |
| POST | `/` | Create subject | `{ name, code, description?, is_active? }` | `Subject` |
| GET | `/{id}` | Get subject | - | `Subject` |
| PUT | `/{id}` | Update subject | `Partial<Subject>` | `Subject` |
| DELETE | `/{id}` | Delete subject | - | `void` |

### Backend Implementation

#### Controller
**Location**: `/opt/attendancedev/backend/app/Http/Controllers/Api/SubjectApiController.php`

**Validation Rules**:
```php
[
    'name' => 'required|string|max:255',
    'code' => 'required|string|max:50|unique:subjects,code',
    'description' => 'nullable|string',
    'is_active' => 'boolean',
]
```

#### Model
**Location**: `/opt/attendancedev/backend/app/Models/Subject.php`

**Relationships**:
- `employees()` - HasMany Employee (teachers)
- `teachingSchedules()` - HasMany TeachingSchedule

### Database Schema

**Table**: `subjects`

```sql
CREATE TABLE subjects (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_active (is_active)
);
```

### Example Data

```json
[
  { "name": "Matematika", "code": "MTK" },
  { "name": "Bahasa Indonesia", "code": "BIND" },
  { "name": "Bahasa Inggris", "code": "BING" },
  { "name": "Fisika", "code": "FIS" },
  { "name": "Kimia", "code": "KIM" },
  { "name": "Biologi", "code": "BIO" },
  { "name": "Sejarah", "code": "SEJ" },
  { "name": "Geografi", "code": "GEO" },
  { "name": "Ekonomi", "code": "EKO" },
  { "name": "Sosiologi", "code": "SOS" },
  { "name": "Pendidikan Agama", "code": "PAI" },
  { "name": "Pendidikan Jasmani", "code": "PJOK" },
  { "name": "Seni Budaya", "code": "SBK" },
  { "name": "Prakarya", "code": "PKK" },
  { "name": "Teknologi Informasi", "code": "TIK" }
]
```

---

## 7. Master Data - Classrooms

### Overview
Classrooms (Kelas) represent academic classes in the school. Used for teaching schedules and student assignment.

### Frontend Implementation

#### API Client
**Location**: `/opt/attendancedev/frontend/src/lib/api/master-data.ts`

**Key Functions**:
```typescript
getClassrooms(params?: any): Promise<PaginatedResponse<Classroom>>
createClassroom(data: Partial<Classroom>): Promise<Classroom>
updateClassroom(id: string, data: Partial<Classroom>): Promise<Classroom>
deleteClassroom(id: string): Promise<void>
```

#### Classroom Form Fields
```typescript
interface Classroom {
  id: string;
  name: string;           // Auto-generated or custom, e.g., "10-IPA-1"
  grade_level: string;    // e.g., "10", "11", "12"
  major?: string;         // e.g., "IPA", "IPS"
  class_number: string;   // e.g., "1", "2", "3"
  capacity?: number;      // Max students
  room?: string;          // Physical room number
  is_active: boolean;
}
```

### API Endpoints

#### Base URL: `/api/v1/admin/classrooms`

| Method | Endpoint | Description | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/` | List classrooms | `?search=...&grade_level=...&per_page=15` | `PaginatedResponse<Classroom>` |
| POST | `/` | Create classroom | `{ grade_level, major?, class_number, capacity?, room? }` | `Classroom` |
| GET | `/{id}` | Get classroom | - | `Classroom` |
| PUT | `/{id}` | Update classroom | `Partial<Classroom>` | `Classroom` |
| DELETE | `/{id}` | Delete classroom | - | `void` |

### Backend Implementation

#### Controller
**Location**: `/opt/attendancedev/backend/app/Http/Controllers/Api/ClassroomApiController.php`

**Validation Rules**:
```php
[
    'name' => 'nullable|string|max:100',  // Auto-generated if empty
    'grade_level' => 'required|string|max:10',
    'major' => 'nullable|string|max:50',
    'class_number' => 'required|string|max:10',
    'capacity' => 'integer|min:1|max:200',
    'room' => 'nullable|string|max:50',
    'is_active' => 'boolean',
]
```

**Auto-generate Name**:
```php
// If name is empty, generate from: {grade_level}-{major}-{class_number}
// Example: "10-IPA-1"
if (empty($validated['name'])) {
    $parts = [$validated['grade_level']];
    if (!empty($validated['major'])) {
        $parts[] = $validated['major'];
    }
    $parts[] = $validated['class_number'];
    $validated['name'] = implode('-', $parts);
}
```

#### Model
**Location**: `/opt/attendancedev/backend/app/Models/AcademicClass.php`

**Relationships**:
- `teachingSchedules()` - HasMany TeachingSchedule

### Database Schema

**Table**: `academic_classes`

```sql
CREATE TABLE academic_classes (
    id UUID PRIMARY KEY,
    name VARCHAR(100),
    grade_level VARCHAR(10) NOT NULL,     -- "10", "11", "12"
    major VARCHAR(50),                    -- "IPA", "IPS", NULL
    class_number VARCHAR(10) NOT NULL,    -- "1", "2", "3"
    capacity INTEGER,
    room VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_grade (grade_level),
    INDEX idx_active (is_active)
);
```

### Example Data

```json
[
  { "grade_level": "10", "major": "IPA", "class_number": "1", "name": "10-IPA-1", "capacity": 36, "room": "A-101" },
  { "grade_level": "10", "major": "IPA", "class_number": "2", "name": "10-IPA-2", "capacity": 36, "room": "A-102" },
  { "grade_level": "10", "major": "IPS", "class_number": "1", "name": "10-IPS-1", "capacity": 36, "room": "A-103" },
  { "grade_level": "11", "major": "IPA", "class_number": "1", "name": "11-IPA-1", "capacity": 36, "room": "B-101" },
  { "grade_level": "11", "major": "IPA", "class_number": "2", "name": "11-IPA-2", "capacity": 36, "room": "B-102" },
  { "grade_level": "12", "major": "IPA", "class_number": "1", "name": "12-IPA-1", "capacity": 36, "room": "C-101" }
]
```

---

## 8. Master Data - Academic Years

### Overview
Academic Years define school year periods and semesters. Only one academic year can be active at a time.

### Frontend Implementation

#### API Client
**Location**: `/opt/attendancedev/frontend/src/lib/api/master-data.ts`

**Key Functions**:
```typescript
getAcademicYears(params?: any): Promise<PaginatedResponse<AcademicYear>>
createAcademicYear(data: Partial<AcademicYear>): Promise<AcademicYear>
updateAcademicYear(id: string, data: Partial<AcademicYear>): Promise<AcademicYear>
deleteAcademicYear(id: string): Promise<void>
```

#### Academic Year Form Fields
```typescript
interface AcademicYear {
  id: string;
  name: string;              // e.g., "2024/2025"
  start_date: Date;          // Start of academic year
  end_date: Date;            // End of academic year
  semester: 'odd' | 'even';  // Ganjil/Genap
  is_active: boolean;        // Only one can be active
}
```

### API Endpoints

#### Base URL: `/api/v1/admin/academic-years`

| Method | Endpoint | Description | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/` | List academic years | `?search=...&is_active=...&per_page=15` | `PaginatedResponse<AcademicYear>` |
| POST | `/` | Create academic year | `{ name, start_date, end_date, semester, is_active? }` | `AcademicYear` |
| GET | `/{id}` | Get academic year | - | `AcademicYear` |
| PUT | `/{id}` | Update academic year | `Partial<AcademicYear>` | `AcademicYear` |
| DELETE | `/{id}` | Delete academic year | - | `void` |

### Backend Implementation

#### Controller
**Location**: `/opt/attendancedev/backend/app/Http/Controllers/Api/AcademicYearApiController.php`

**Validation Rules**:
```php
[
    'name' => 'required|string|max:255',
    'start_date' => 'required|date',
    'end_date' => 'required|date|after:start_date',
    'semester' => 'required|in:odd,even',
    'is_active' => 'boolean',
]
```

**Auto-deactivate Logic**:
```php
// When creating/updating with is_active = true
if ($validated['is_active'] ?? false) {
    // Deactivate all other academic years
    AcademicYear::where('is_active', true)->update(['is_active' => false]);
}
```

#### Model
**Location**: `/opt/attendancedev/backend/app/Models/AcademicYear.php`

### Database Schema

**Table**: `academic_years`

```sql
CREATE TABLE academic_years (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    semester ENUM('odd', 'even') NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date)
);
```

### Example Data

```json
[
  {
    "name": "2024/2025 - Semester Ganjil",
    "start_date": "2024-07-01",
    "end_date": "2024-12-31",
    "semester": "odd",
    "is_active": false
  },
  {
    "name": "2024/2025 - Semester Genap",
    "start_date": "2025-01-01",
    "end_date": "2025-06-30",
    "semester": "even",
    "is_active": true
  }
]
```

---

## 9. Master Data - Holidays

### Overview
Holidays define non-working days for attendance. Supports national holidays, religious holidays, school breaks, and recurring patterns.

### Frontend Implementation

#### API Client
**Location**: `/opt/attendancedev/frontend/src/lib/api/holidays.ts` (if exists) or custom implementation

**Key Functions**:
```typescript
getHolidays(params?: any): Promise<PaginatedResponse<Holiday>>
getHolidaysByMonth(month: number, year: number): Promise<Holiday[]>
createHoliday(data: Partial<Holiday>): Promise<Holiday>
updateHoliday(id: string, data: Partial<Holiday>): Promise<Holiday>
deleteHoliday(id: string): Promise<void>
getHolidayStatistics(year?: number): Promise<HolidayStatistics>
bulkImportHolidays(holidays: Partial<Holiday>[]): Promise<{ imported: number, total: number }>
```

#### Holiday Form Fields
```typescript
interface Holiday {
  id: string;
  name: string;                  // e.g., "Hari Kemerdekaan RI"
  description?: string;
  date: Date;                    // Holiday date
  end_date?: Date;               // For multi-day holidays
  type: 'public_holiday' | 'religious_holiday' | 'school_holiday' | 'substitute_holiday';
  status: 'active' | 'cancelled' | 'moved';
  is_recurring: boolean;         // Repeats annually?
  recurring_pattern?: object;    // Recurrence rules
  affected_roles?: string[];     // Which roles are affected
  source?: string;               // Data source
  color?: string;                // Calendar color
  is_paid: boolean;              // Is it paid leave?
  metadata?: object;
}
```

### API Endpoints

#### Base URL: `/api/v1/holidays`

| Method | Endpoint | Description | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/` | List holidays | `?year=2025&type=...&status=...&start_date=...&end_date=...` | `PaginatedResponse<Holiday>` |
| POST | `/` | Create holiday (with upsert) | `Holiday` | `Holiday` |
| GET | `/by-month` | Get holidays by month | `?month=1&year=2025` | `{ holidays, count, month, year }` |
| GET | `/statistics` | Get statistics | `?year=2025` | `HolidayStatistics` |
| POST | `/bulk-import` | Import multiple holidays | `{ holidays: Holiday[] }` | `{ imported, total, errors }` |
| GET | `/{id}` | Get holiday | - | `Holiday` |
| PUT | `/{id}` | Update holiday | `Partial<Holiday>` | `Holiday` |
| DELETE | `/{id}` | Delete holiday | - | `void` |

### Backend Implementation

#### Controller
**Location**: `/opt/attendancedev/backend/app/Http/Controllers/Api/HolidayApiController.php`

**Validation Rules**:
```php
[
    'name' => 'required|string|max:255',
    'description' => 'nullable|string',
    'date' => 'required|date',
    'end_date' => 'nullable|date|after_or_equal:date',
    'type' => 'required|in:public_holiday,religious_holiday,school_holiday,substitute_holiday',
    'status' => 'sometimes|in:active,cancelled,moved',
    'is_recurring' => 'sometimes|boolean',
    'recurring_pattern' => 'nullable|array',
    'affected_roles' => 'nullable|array',
    'source' => 'nullable|string',
    'color' => 'nullable|string|max:7',
    'is_paid' => 'sometimes|boolean',
    'metadata' => 'nullable|array',
]
```

**Upsert Logic** (prevents duplicates):
```php
// Check for existing holiday with same name, date, and type
$existing = Holiday::where('name', $validated['name'])
    ->where('date', $validated['date'])
    ->where('type', $validated['type'])
    ->first();

if ($existing) {
    // Update existing instead of creating duplicate
    $existing->update($validated);
    return $existing;
}
```

#### Model
**Location**: `/opt/attendancedev/backend/app/Models/Holiday.php`

**Scopes**:
- `active()` - Where status = 'active'

**Methods**:
- `isPaidHoliday()` - Check if is_paid = true
- `isRecurring()` - Check if is_recurring = true

### Database Schema

**Table**: `holidays`

```sql
CREATE TABLE holidays (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    end_date DATE,
    type ENUM('public_holiday', 'religious_holiday', 'school_holiday', 'substitute_holiday') NOT NULL,
    status ENUM('active', 'cancelled', 'moved') DEFAULT 'active',
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_pattern JSONB,
    affected_roles JSONB,           -- ["all"] or ["guru", "pegawai"]
    source VARCHAR(255),            -- "manual", "government_api", etc
    color VARCHAR(7) DEFAULT '#dc3545',
    is_paid BOOLEAN DEFAULT TRUE,
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_date (date),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_date_status (date, status)
);
```

### Holiday Types

1. **public_holiday** - National holidays (Independence Day, etc)
2. **religious_holiday** - Religious holidays (Eid, Christmas, etc)
3. **school_holiday** - School breaks (semester break, etc)
4. **substitute_holiday** - Government-designated collective leave

### Recurring Pattern

```json
{
  "frequency": "yearly",
  "interval": 1,
  "specific_date": "08-17",  // MM-DD format for fixed date
  "islamic_date": {          // For Islamic calendar
    "month": 10,             // Shawwal
    "day": 1                 // 1st
  }
}
```

### Example Data

```json
[
  {
    "name": "Hari Kemerdekaan RI",
    "date": "2025-08-17",
    "type": "public_holiday",
    "is_recurring": true,
    "recurring_pattern": { "frequency": "yearly", "specific_date": "08-17" },
    "is_paid": true,
    "color": "#dc3545"
  },
  {
    "name": "Hari Raya Idul Fitri",
    "date": "2025-03-30",
    "end_date": "2025-03-31",
    "type": "religious_holiday",
    "is_recurring": false,
    "is_paid": true,
    "color": "#28a745"
  },
  {
    "name": "Libur Semester",
    "date": "2025-06-15",
    "end_date": "2025-07-14",
    "type": "school_holiday",
    "is_paid": true,
    "color": "#007bff"
  },
  {
    "name": "Cuti Bersama Lebaran",
    "date": "2025-04-01",
    "end_date": "2025-04-04",
    "type": "substitute_holiday",
    "is_paid": true,
    "color": "#ffc107"
  }
]
```

### Integration with Attendance

Holidays are automatically checked during attendance:

```php
// In Employee::getEffectiveScheduleForDate()
$globalHoliday = Holiday::active()
    ->whereDate('date', $date)
    ->first();

if ($globalHoliday) {
    return [
        'schedule_type' => 'holiday',
        'can_attend' => false,
        'message' => 'Hari libur: ' . $globalHoliday->name,
        'holiday_name' => $globalHoliday->name,
        'color' => $globalHoliday->color,
    ];
}
```

---

## 10. Database Schema

### Entity Relationship Diagram

```
┌─────────────┐         ┌──────────────┐
│   users     │────────<│  employees   │
└─────────────┘         └──────────────┘
                              │
                    ┌─────────┼─────────────┬─────────────┐
                    │         │             │             │
                    ▼         ▼             ▼             ▼
            ┌──────────┐ ┌─────────┐ ┌────────────┐ ┌─────────┐
            │locations │ │subjects │ │departments │ │positions│
            └──────────┘ └─────────┘ └────────────┘ └─────────┘
                              │
                              │
                    ┌─────────┼─────────────┐
                    │         │             │
                    ▼         ▼             ▼
        ┌──────────────────────────┐ ┌─────────────┐
        │ teaching_schedules       │ │   holidays  │
        └──────────────────────────┘ └─────────────┘
                    │
                    ▼
        ┌──────────────────────────┐
        │ academic_classes         │
        └──────────────────────────┘
                    │
                    ▼
        ┌──────────────────────────┐
        │ academic_years           │
        └──────────────────────────┘
```

### Complete Schema Summary

#### Core Tables
- **users** - User accounts (authentication)
- **employees** - Employee records (extends users)
- **employee_types** - Employee type definitions (fixed/flexible)

#### Master Data Tables
- **departments** - Organizational units (for staff)
- **positions** - Job positions (for staff)
- **subjects** - Teaching subjects (for teachers)
- **locations** - Physical attendance locations (GPS)
- **academic_classes** - School classes
- **academic_years** - School year periods
- **holidays** - Non-working days

#### Relationship Tables
- **employee_schedules** - Legacy employee schedules
- **employee_monthly_schedules** - Monthly attendance schedules
- **teaching_schedules** - Teaching sessions (for flexible teachers)
- **attendances** - Attendance records
- **leaves** - Leave requests
- **payrolls** - Payroll records

### Key Foreign Keys

```sql
-- employees table
ALTER TABLE employees
    ADD CONSTRAINT fk_employees_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_employees_location FOREIGN KEY (location_id)
        REFERENCES locations(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_employees_employee_type FOREIGN KEY (employee_type_id)
        REFERENCES employee_types(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_employees_subject FOREIGN KEY (subject_id)
        REFERENCES subjects(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_employees_department FOREIGN KEY (department_id)
        REFERENCES departments(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_employees_position FOREIGN KEY (position_id)
        REFERENCES positions(id) ON DELETE SET NULL;
```

### Indexes for Performance

```sql
-- employees
CREATE INDEX idx_employees_type ON employees(employee_type_id);
CREATE INDEX idx_employees_active ON employees(is_active);
CREATE INDEX idx_employees_name ON employees(full_name);
CREATE INDEX idx_employees_location ON employees(location_id);
CREATE INDEX idx_employees_department ON employees(department_id);

-- locations
CREATE INDEX idx_locations_active ON locations(is_active);
CREATE INDEX idx_locations_coords ON locations(latitude, longitude);

-- subjects
CREATE INDEX idx_subjects_active ON subjects(is_active);

-- academic_classes
CREATE INDEX idx_classes_grade ON academic_classes(grade_level);
CREATE INDEX idx_classes_active ON academic_classes(is_active);

-- holidays
CREATE INDEX idx_holidays_date ON holidays(date);
CREATE INDEX idx_holidays_type ON holidays(type);
CREATE INDEX idx_holidays_status ON holidays(status);
CREATE INDEX idx_holidays_date_status ON holidays(date, status);

-- departments/positions
CREATE INDEX idx_departments_active ON departments(is_active, sort_order);
CREATE INDEX idx_positions_active ON positions(is_active, sort_order);

-- employee_types
CREATE INDEX idx_employee_types_active ON employee_types(is_active, sort_order);
```

---

## Summary

This Phase 2 documentation covers all aspects of Employee & Master Data Management:

### Implemented Features
- Full CRUD for employees with role-based access
- Employee types with flexible/fixed schedule modes
- Departments and positions for staff organization
- Subjects for teacher assignment
- Classrooms for academic structure
- Academic years with semester management
- Holidays with recurring patterns
- GPS-based location verification
- Bulk operations for employee management
- User account credential management

### API Architecture
- RESTful API design
- Consistent response format
- Proper validation and error handling
- IDOR protection for employee data
- Admin-only endpoints for master data
- Pagination support for large datasets

### Database Design
- UUID primary keys for security
- Proper foreign key relationships
- Soft deletes for historical data
- JSONB for flexible metadata
- Optimized indexes for performance

### Frontend Integration
- Responsive design (mobile/desktop)
- TanStack Query for data fetching
- Form validation
- Real-time updates
- Dropdown population from master data

---

**Related Documentation**:
- [Phase 1: Authentication & Authorization](FLOW_PHASE1_AUTH.md)
- [Phase 3: Attendance Management](FLOW_PHASE3_ATTENDANCE.md)
- [System Architecture](ARCHITECTURE.md)
- [API Reference](API.md)
- [Deployment Guide](DEPLOYMENT_GUIDE.md)
