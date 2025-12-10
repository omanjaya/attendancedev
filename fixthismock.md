# FIX MOCK DATA - Panduan Lengkap

## OVERVIEW

File ini berisi daftar semua mock/fake data yang harus diganti dengan integrasi API yang sebenarnya.

**ATURAN PENTING:**
1. Jangan hapus mock data sebelum API backend sudah siap
2. Buat API endpoint di backend DULU, baru update frontend
3. Gunakan pattern yang sudah ada di codebase (TanStack Query, apiClient)
4. Test setiap perubahan sebelum lanjut ke file berikutnya

---

## PRIORITAS TINGGI (Core Features)

### 1. Admin Attendance Management

**File:** `frontend/src/pages/admin/attendance/mobile.tsx`

**Masalah:** Data attendance statistics dan records di-hardcode dalam queryFn

**Lokasi Mock (line ~20-50):**
```typescript
queryFn: async () => {
    // TODO: Replace with actual API call
    return {
        presentToday: 128,
        lateToday: 12,
        // ... hardcoded data
    };
}
```

**Solusi:**
1. Buat API endpoint: `GET /api/v1/attendance/admin/stats?date=YYYY-MM-DD`
2. Buat API endpoint: `GET /api/v1/attendance/admin/records?date=YYYY-MM-DD&search=&status=`
3. Update queryFn untuk memanggil API

**Backend yang perlu dibuat:**
```php
// File: backend/app/Http/Controllers/Api/AttendanceApiController.php
// Tambah method:
public function adminStats(Request $request) {
    $date = $request->get('date', now()->format('Y-m-d'));
    // Return: presentToday, lateToday, absentToday, onLeaveToday, attendanceRate
}

public function adminRecords(Request $request) {
    $date = $request->get('date', now()->format('Y-m-d'));
    $search = $request->get('search');
    $status = $request->get('status');
    // Return: array of attendance records with employee info
}
```

**Frontend yang perlu diupdate:**
```typescript
// File: frontend/src/lib/api/attendance.ts
// Tambah function:
export async function getAdminAttendanceStats(date: string) {
    const response = await apiClient.get('/attendance/admin/stats', { params: { date } });
    return response.data.data;
}

export async function getAdminAttendanceRecords(params: { date: string; search?: string; status?: string }) {
    const response = await apiClient.get('/attendance/admin/records', { params });
    return response.data.data;
}
```

---

**File:** `frontend/src/pages/admin/attendance/desktop.tsx`

**Masalah:** Mutation functions (approve, reject, manual entry) hanya console.log

**Lokasi Mock (line ~50-80):**
```typescript
const approveAttendanceMutation = useMutation({
    mutationFn: async (id: number) => {
        // TODO: Replace with actual API call
        console.log('Approving attendance:', id);
        return { success: true };
    },
});
```

**Solusi:**
1. Buat API endpoint: `POST /api/v1/attendance/{id}/approve`
2. Buat API endpoint: `POST /api/v1/attendance/{id}/reject`
3. Buat API endpoint: `POST /api/v1/attendance/manual`

**Backend yang perlu dibuat:**
```php
// File: backend/app/Http/Controllers/Api/AttendanceApiController.php
public function approve(Request $request, string $id) {
    // Approve attendance record
}

public function reject(Request $request, string $id) {
    $validated = $request->validate(['reason' => 'required|string']);
    // Reject with reason
}

public function manualEntry(Request $request) {
    $validated = $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'date' => 'required|date',
        'check_in' => 'required|date_format:H:i',
        'check_out' => 'nullable|date_format:H:i',
        'notes' => 'nullable|string',
    ]);
    // Create manual attendance entry
}
```

---

### 2. Employee Payroll

**File:** `frontend/src/pages/employee/payroll/mobile.tsx`
**File:** `frontend/src/pages/employee/payroll/desktop.tsx`

**Masalah:** Data payroll di-hardcode dalam queryFn

**Lokasi Mock:**
```typescript
queryFn: async () => {
    // TODO: Replace with actual API call
    return [
        {
            id: '1',
            month: 'Januari',
            year: 2025,
            // ... hardcoded data
        },
    ];
}
```

**Solusi:**
1. Buat API endpoint: `GET /api/v1/payroll/employee?year=YYYY`
2. Buat API endpoint: `GET /api/v1/payroll/{id}/download` (PDF)

**Backend yang perlu dibuat:**
```php
// File: backend/app/Http/Controllers/Api/PayrollApiController.php
public function employeePayroll(Request $request) {
    $employee = $request->user()->employee;
    $year = $request->get('year', now()->year);
    
    $payrolls = Payroll::where('employee_id', $employee->id)
        ->whereYear('period_start', $year)
        ->orderBy('period_start', 'desc')
        ->get();
    
    return $this->apiResponse($payrolls->map(fn($p) => [
        'id' => $p->id,
        'month' => $p->period_start->format('F'),
        'year' => $p->period_start->year,
        'basic_salary' => $p->basic_salary,
        'allowances' => $p->allowances,
        'deductions' => $p->deductions,
        'net_salary' => $p->net_salary,
        'status' => $p->status,
        'paid_at' => $p->paid_at,
    ]));
}

public function downloadPayslip(string $id) {
    // Generate PDF payslip
}
```

**Frontend yang perlu diupdate:**
```typescript
// File: frontend/src/lib/api/payroll.ts (BUAT BARU)
import { apiClient } from './client';

export async function getEmployeePayroll(year: number) {
    const response = await apiClient.get('/payroll/employee', { params: { year } });
    return response.data.data;
}

export async function downloadPayslip(id: string) {
    const response = await apiClient.get(`/payroll/${id}/download`, { responseType: 'blob' });
    return response.data;
}
```

---

### 3. Employee Credentials Management

**File:** `frontend/src/pages/admin/employees/credentials.tsx`

**Masalah:** Semua data di-hardcode (mockStats, mockEmployeesWithoutUsers, mockEmployeesWithUsers)

**Lokasi Mock (line ~10-50):**
```typescript
const mockStats = {
    total_employees: 45,
    with_users: 38,
    // ...
};

const mockEmployeesWithoutUsers = [
    { id: 1, full_name: 'Andi Prasetyo', ... },
];

const mockEmployeesWithUsers = [
    { id: 6, full_name: 'Ahmad Fauzi', ... },
];
```

**Solusi:**
1. Buat API endpoint: `GET /api/v1/employees/credentials/stats`
2. Buat API endpoint: `GET /api/v1/employees/credentials/without-users`
3. Buat API endpoint: `GET /api/v1/employees/credentials/with-users`
4. Buat API endpoint: `POST /api/v1/employees/credentials/create-users`
5. Buat API endpoint: `POST /api/v1/employees/credentials/reset-passwords`

**Backend yang perlu dibuat:**
```php
// File: backend/app/Http/Controllers/Api/EmployeeCredentialController.php (BUAT BARU)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeCredentialController extends Controller
{
    public function stats()
    {
        $total = Employee::count();
        $withUsers = Employee::whereHas('user')->count();
        $withoutUsers = $total - $withUsers;
        
        return response()->json([
            'data' => [
                'total_employees' => $total,
                'with_users' => $withUsers,
                'without_users' => $withoutUsers,
                'percentage_with_users' => $total > 0 ? round(($withUsers / $total) * 100) : 0,
            ]
        ]);
    }
    
    public function withoutUsers(Request $request)
    {
        $employees = Employee::whereDoesntHave('user')
            ->with('location')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'email' => $e->email,
                'employee_type' => $e->employee_type,
                'location' => $e->location?->name,
                'hire_date' => $e->hire_date?->format('Y-m-d'),
            ]);
        
        return response()->json(['data' => $employees]);
    }
    
    public function withUsers(Request $request)
    {
        $employees = Employee::whereHas('user')
            ->with(['user', 'location'])
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'email' => $e->email,
                'role' => $e->user?->roles->first()?->name,
                'last_login' => $e->user?->last_login_at?->format('Y-m-d H:i'),
                'created_at' => $e->user?->created_at?->format('Y-m-d'),
            ]);
        
        return response()->json(['data' => $employees]);
    }
    
    public function createUsers(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);
        
        $results = [];
        foreach ($validated['employee_ids'] as $employeeId) {
            $employee = Employee::find($employeeId);
            if ($employee && !$employee->user) {
                $password = Str::random(10);
                $user = User::create([
                    'name' => $employee->full_name,
                    'email' => $employee->email,
                    'password' => Hash::make($password),
                ]);
                $employee->update(['user_id' => $user->id]);
                
                $results[] = [
                    'employee_name' => $employee->full_name,
                    'email' => $employee->email,
                    'password' => $password,
                    'success' => true,
                ];
            }
        }
        
        return response()->json(['data' => $results]);
    }
    
    public function resetPasswords(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);
        
        $results = [];
        foreach ($validated['employee_ids'] as $employeeId) {
            $employee = Employee::with('user')->find($employeeId);
            if ($employee && $employee->user) {
                $password = Str::random(10);
                $employee->user->update([
                    'password' => Hash::make($password),
                ]);
                
                $results[] = [
                    'employee_name' => $employee->full_name,
                    'email' => $employee->email,
                    'password' => $password,
                    'success' => true,
                ];
            }
        }
        
        return response()->json(['data' => $results]);
    }
}
```

**Routes yang perlu ditambah:**
```php
// File: backend/routes/api.php
Route::prefix('employees/credentials')->group(function () {
    Route::get('stats', [EmployeeCredentialController::class, 'stats']);
    Route::get('without-users', [EmployeeCredentialController::class, 'withoutUsers']);
    Route::get('with-users', [EmployeeCredentialController::class, 'withUsers']);
    Route::post('create-users', [EmployeeCredentialController::class, 'createUsers']);
    Route::post('reset-passwords', [EmployeeCredentialController::class, 'resetPasswords']);
});
```

---

## PRIORITAS SEDANG (Admin Features)

### 4. Schedule Management

**File:** `frontend/src/pages/admin/schedules/edit.tsx`
**File:** `frontend/src/pages/admin/schedules/show.tsx`

**Masalah:** `mockSchedule` hardcoded

**Solusi:** Sudah ada API di `/api/v1/schedules/{id}`, tinggal gunakan dengan useQuery

**Contoh fix:**
```typescript
// Ganti ini:
const mockSchedule = { ... };

// Dengan ini:
const { id } = useParams();
const { data: schedule, isLoading } = useQuery({
    queryKey: ['schedule', id],
    queryFn: () => getSchedule(id),
});
```

---

**File:** `frontend/src/pages/admin/schedules/calendar.tsx`

**Masalah:** `mockSchedules` hardcoded

**Solusi:**
1. Buat API endpoint: `GET /api/v1/schedules/calendar?month=MM&year=YYYY`

**Backend:**
```php
public function calendar(Request $request)
{
    $month = $request->get('month', now()->month);
    $year = $request->get('year', now()->year);
    
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();
    
    $schedules = EmployeeMonthlySchedule::whereBetween('effective_date', [$startDate, $endDate])
        ->with('employee')
        ->get()
        ->groupBy(fn($s) => $s->effective_date->format('Y-m-d'));
    
    return response()->json(['data' => $schedules]);
}
```

---

**File:** `frontend/src/pages/admin/schedules/builder.tsx`

**Masalah:** `mockSubjects`, `mockTeachers` hardcoded

**Solusi:** Sudah ada API:
- Subjects: `GET /api/v1/subjects`
- Teachers: `GET /api/v1/employees?employee_type=guru`

**Contoh fix:**
```typescript
// Ganti ini:
const mockSubjects = [...];
const mockTeachers = [...];

// Dengan ini:
const { data: subjectsData } = useQuery({
    queryKey: ['subjects'],
    queryFn: getSubjects,
});
const subjects = subjectsData?.data || [];

const { data: teachersData } = useQuery({
    queryKey: ['employees', { employee_type: 'guru' }],
    queryFn: () => getEmployees({ employee_type: 'guru' }),
});
const teachers = teachersData?.data || [];
```

---

**File:** `frontend/src/pages/admin/schedules/assign.tsx`
**File:** `frontend/src/pages/admin/schedules/tabs/ScheduleAssignContent.tsx`

**Masalah:** `mockSchedules`, `mockEmployees` hardcoded

**Solusi:** Sama seperti di atas, gunakan API yang sudah ada

---

**File:** `frontend/src/pages/admin/schedules/tabs/MonthlyScheduleContent.tsx`

**Masalah:** `mockMonthlySchedules` hardcoded

**Solusi:** Gunakan API `GET /api/v1/schedules/monthly`

---

### 5. Leave Calendar

**File:** `frontend/src/pages/admin/leave/calendar.tsx`

**Masalah:** `mockLeaves` hardcoded

**Solusi:**
1. Buat API endpoint: `GET /api/v1/leave/calendar?month=MM&year=YYYY`

**Backend:**
```php
public function calendar(Request $request)
{
    $month = $request->get('month', now()->month);
    $year = $request->get('year', now()->year);
    
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();
    
    $leaves = Leave::where('status', 'approved')
        ->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate]);
        })
        ->with('employee')
        ->get()
        ->groupBy(fn($l) => $l->start_date->format('Y-m-d'));
    
    return response()->json(['data' => $leaves]);
}
```

---

### 6. Holiday Show

**File:** `frontend/src/pages/admin/holidays/show.tsx`

**Masalah:** `mockHoliday` hardcoded

**Solusi:** Gunakan API yang sudah ada `GET /api/v1/holidays/{id}`

```typescript
const { id } = useParams();
const { data: holiday, isLoading } = useQuery({
    queryKey: ['holiday', id],
    queryFn: () => getHoliday(id),
});
```

---

### 7. User Management

**File:** `frontend/src/pages/admin/users/edit.tsx`
**File:** `frontend/src/pages/admin/users/show.tsx`

**Masalah:** `mockUser` hardcoded

**Solusi:** Buat/gunakan API untuk user management

**Backend yang perlu dibuat:**
```php
// File: backend/app/Http/Controllers/Api/UserApiController.php
public function show(string $id)
{
    $user = User::with(['employee', 'roles'])->findOrFail($id);
    return response()->json(['data' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->roles->first()?->name,
        'status' => $user->is_active ? 'active' : 'inactive',
        'permissions' => $user->getAllPermissions()->pluck('name'),
        'last_login' => $user->last_login_at,
        'created_at' => $user->created_at,
    ]]);
}

public function update(Request $request, string $id)
{
    // Update user
}
```

---

**File:** `frontend/src/hooks/use-users.ts`

**Masalah:** `mockUsers`, `mockDevices` hardcoded

**Solusi:** Buat API endpoints dan update hook untuk fetch dari API

---

### 8. Security Features

**File:** `frontend/src/pages/admin/security/two-factor.tsx`

**Masalah:** `mock2FAStatus` hardcoded

**Solusi:**
1. Buat API endpoint: `GET /api/v1/auth/2fa/status`
2. Buat API endpoint: `POST /api/v1/auth/2fa/enable`
3. Buat API endpoint: `POST /api/v1/auth/2fa/disable`
4. Buat API endpoint: `POST /api/v1/auth/2fa/verify`

---

**File:** `frontend/src/pages/admin/security/events.tsx`

**Masalah:** `mockEvents` hardcoded

**Solusi:**
1. Buat API endpoint: `GET /api/v1/security/events`

**Backend:**
```php
// Buat table security_events
// Buat model SecurityEvent
// Log semua security events (login, logout, password change, etc)
```

---

**File:** `frontend/src/pages/admin/security/devices.tsx`

**Masalah:** `mockDevices` hardcoded

**Solusi:**
1. Buat API endpoint: `GET /api/v1/auth/devices`
2. Buat API endpoint: `DELETE /api/v1/auth/devices/{id}`

---

**File:** `frontend/src/hooks/use-security.ts`

**Masalah:** Semua mock: `mockDevices`, `mockSessions`, `mockAuditLogs`, `mockAlerts`

**Solusi:** Buat semua API endpoints yang diperlukan

---

### 9. Face Recognition History

**File:** `frontend/src/pages/admin/face-recognition/index.tsx`

**Masalah:** `verificationHistory` hardcoded

**Solusi:**
1. Buat API endpoint: `GET /api/v1/face/verification-history`

**Backend:**
```php
public function verificationHistory(Request $request)
{
    $history = FaceVerificationLog::with('employee')
        ->orderBy('created_at', 'desc')
        ->limit(100)
        ->get()
        ->map(fn($log) => [
            'id' => $log->id,
            'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
            'status' => $log->status,
            'confidence' => $log->confidence,
            'method' => $log->method,
            'employee_name' => $log->employee?->full_name,
        ]);
    
    return response()->json(['data' => $history]);
}
```

---

## PRIORITAS RENDAH

### 10. Dashboard Mock

**File:** `frontend/src/lib/api/dashboard.ts`
**File:** `frontend/src/hooks/use-dashboard.ts`

**Masalah:** `getMockDashboardData()` dan `useMockDashboard()`

**Status:** Ini adalah development helper, tidak dipakai di production. Bisa dihapus atau dibiarkan.

---

### 11. Error Tracking

**File:** `backend/app/Http/Controllers/Api/ErrorTrackingController.php`

**Masalah:** Return mock data

**Solusi:** Implement actual error tracking atau integrate dengan Sentry

---

## CHECKLIST

Gunakan checklist ini untuk tracking progress:

### Core Features
- [ ] Admin Attendance Stats API
- [ ] Admin Attendance Records API
- [ ] Admin Attendance Approve/Reject API
- [ ] Admin Attendance Manual Entry API
- [ ] Employee Payroll API
- [ ] Employee Payslip Download API
- [ ] Employee Credentials Stats API
- [ ] Employee Credentials Create Users API
- [ ] Employee Credentials Reset Passwords API

### Admin Features
- [ ] Schedule Show/Edit - Connect to existing API
- [ ] Schedule Calendar API
- [ ] Schedule Builder - Connect to existing API
- [ ] Schedule Assign - Connect to existing API
- [ ] Monthly Schedule Content - Connect to existing API
- [ ] Leave Calendar API
- [ ] Holiday Show - Connect to existing API
- [ ] User Show/Edit API
- [ ] User Management Hook Update

### Security Features
- [ ] 2FA Status API
- [ ] 2FA Enable/Disable API
- [ ] Security Events API
- [ ] Security Devices API
- [ ] Security Sessions API
- [ ] Security Audit Logs API
- [ ] Security Alerts API

### Other
- [ ] Face Recognition History API
- [ ] Error Tracking Implementation

---

## CONTOH PATTERN YANG BENAR

### Backend Controller Pattern
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExampleController extends Controller
{
    use ApiResponse;
    
    public function index(Request $request)
    {
        $data = Model::query()
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->paginate($request->get('per_page', 15));
        
        return $this->apiResponse($data, 'Data retrieved successfully');
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $item = Model::create($validated);
        
        return $this->apiResponse($item, 'Created successfully', 201);
    }
}
```

### Frontend API Pattern
```typescript
// File: frontend/src/lib/api/example.ts
import { apiClient } from './client';

export interface ExampleItem {
    id: string;
    name: string;
}

export async function getExamples(params?: { search?: string; page?: number }) {
    const response = await apiClient.get<{ data: ExampleItem[] }>('/examples', { params });
    return response.data;
}

export async function getExample(id: string) {
    const response = await apiClient.get<{ data: ExampleItem }>(`/examples/${id}`);
    return response.data.data;
}

export async function createExample(data: Partial<ExampleItem>) {
    const response = await apiClient.post<{ data: ExampleItem }>('/examples', data);
    return response.data.data;
}
```

### Frontend Hook Pattern
```typescript
// File: frontend/src/hooks/use-examples.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getExamples, getExample, createExample } from '@/lib/api/example';

export const exampleKeys = {
    all: ['examples'] as const,
    list: (filters?: object) => [...exampleKeys.all, 'list', filters] as const,
    detail: (id: string) => [...exampleKeys.all, 'detail', id] as const,
};

export function useExamples(filters?: { search?: string }) {
    return useQuery({
        queryKey: exampleKeys.list(filters),
        queryFn: () => getExamples(filters),
    });
}

export function useExample(id: string) {
    return useQuery({
        queryKey: exampleKeys.detail(id),
        queryFn: () => getExample(id),
        enabled: !!id,
    });
}

export function useCreateExample() {
    const queryClient = useQueryClient();
    
    return useMutation({
        mutationFn: createExample,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: exampleKeys.all });
        },
    });
}
```

### Frontend Component Pattern (Mengganti Mock)
```typescript
// SEBELUM (dengan mock):
const mockData = [{ id: 1, name: 'Test' }];

export default function ExamplePage() {
    return (
        <div>
            {mockData.map(item => <div key={item.id}>{item.name}</div>)}
        </div>
    );
}

// SESUDAH (dengan API):
import { useExamples } from '@/hooks/use-examples';

export default function ExamplePage() {
    const { data, isLoading, error } = useExamples();
    
    if (isLoading) return <div>Loading...</div>;
    if (error) return <div>Error: {error.message}</div>;
    
    return (
        <div>
            {data?.data?.map(item => <div key={item.id}>{item.name}</div>)}
        </div>
    );
}
```

---

## CATATAN PENTING

1. **Jangan langsung hapus mock** - Pastikan API sudah ready dan tested
2. **Gunakan TypeScript types** - Definisikan interface untuk semua response
3. **Handle loading & error states** - Selalu tampilkan loading dan error UI
4. **Invalidate cache** - Setelah mutasi, invalidate query cache yang relevan
5. **Test di browser** - Pastikan tidak ada console errors setelah perubahan

---

## URUTAN PENGERJAAN YANG DISARANKAN

1. **Week 1:** Core Features (Attendance Admin, Payroll, Credentials)
2. **Week 2:** Schedule Management (Calendar, Builder, Assign)
3. **Week 3:** User & Leave Management
4. **Week 4:** Security Features
5. **Week 5:** Polish & Testing
