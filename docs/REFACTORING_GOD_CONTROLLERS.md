# God Controllers Refactoring Guide

> Dokumentasi lengkap untuk refactoring God Controllers ke Clean Architecture

## Daftar Isi

1. [Executive Summary](#executive-summary)
2. [Apa itu God Controller?](#apa-itu-god-controller)
3. [Analisis Current State](#analisis-current-state)
4. [Target Architecture](#target-architecture)
5. [Refactoring Guide per Controller](#refactoring-guide-per-controller)
6. [Code Examples](#code-examples)
7. [Testing Strategy](#testing-strategy)
8. [Migration Checklist](#migration-checklist)

---

## Executive Summary

### God Controllers yang Teridentifikasi

| Controller | Lines | Methods | Priority | Alasan |
|------------|-------|---------|----------|--------|
| `PayrollApiController.php` | 1022 | 25 | 🔴 HIGH | Terbesar, services sudah ada |
| `SecurityController.php` | 778 | 25 | 🔴 HIGH | Banyak method, critical feature |
| `ImportController.php` | 569 | 7 | 🟡 MEDIUM | File processing kompleks |
| `LeaveController.php` | 566 | 13 | 🟡 MEDIUM | Business logic kompleks |
| `ScheduleController.php` | 556 | 14 | 🟡 MEDIUM | Query building kompleks |

### Controllers yang Sudah Baik (Referensi)

| Controller | Lines | Methods | Service Usage |
|------------|-------|---------|---------------|
| `FaceRecognitionController.php` | 959 | 17 | ✅ 27 refs |
| `AttendanceController.php` | 816 | 16 | ✅ 28 refs |
| `TwoFactorController.php` | 526 | 16 | ✅ 46 refs |

### Services yang Sudah Ada (Bisa Dipakai)

```
app/Services/
├── PayrollCalculationService.php     ← Untuk PayrollApiController
├── OptimizedPayrollService.php       ← Untuk PayrollApiController
├── Payroll/
│   └── PayrollReportGeneratorService.php
├── SecurityService.php               ← Untuk SecurityController
├── SecurityEventService.php          ← Untuk SecurityController
├── Security/
│   └── SecurityReportService.php
├── LeaveService.php                  ← Untuk LeaveController
├── ScheduleService.php               ← Untuk ScheduleController
└── ScheduleManagementService.php     ← Untuk ScheduleController
```

---

## Apa itu God Controller?

### Definisi
God Controller adalah anti-pattern dimana satu controller class memiliki terlalu banyak responsibility, melanggar **Single Responsibility Principle (SRP)**.

### Ciri-ciri God Controller

| Ciri | Threshold | Status di Codebase |
|------|-----------|-------------------|
| Lines of Code | > 500 | ❌ Ada 5 controller |
| Methods | > 10 | ❌ Ada yang 25+ methods |
| Dependencies | > 10 | ⚠️ Beberapa |
| IF statements | > 20 | ❌ Ada 43 IF |
| Nested code | > 3 levels | ⚠️ Ada |
| Constructor params | > 5 | ✅ Kebanyakan 0 (tidak DI) |

### Bahaya God Controller

```
┌─────────────────────────────────────────────────────────────────┐
│                    BAHAYA GOD CONTROLLER                        │
├─────────────────────────────────────────────────────────────────┤
│ 1. SULIT DIPAHAMI                                               │
│    - Developer baru butuh waktu lama untuk memahami             │
│    - Logic tersebar dan tidak terorganisir                      │
│                                                                 │
│ 2. SULIT DI-MAINTAIN                                            │
│    - Perubahan kecil bisa break fitur lain                      │
│    - "Takut sentuh" syndrome                                    │
│                                                                 │
│ 3. SULIT DI-TEST                                                │
│    - Unit test hampir impossible                                │
│    - Hanya bisa integration test                                │
│    - Coverage rendah                                            │
│                                                                 │
│ 4. BUG DOMINO                                                   │
│    - Fix satu bug, muncul bug lain                              │
│    - Side effects tidak terprediksi                             │
│                                                                 │
│ 5. TIDAK REUSABLE                                               │
│    - Logic tidak bisa dipakai di tempat lain                    │
│    - Duplikasi code                                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## Analisis Current State

### 1. PayrollApiController.php (1022 lines, 25 methods)

**Location:** `app/Http/Controllers/Api/PayrollApiController.php`

**Current Problems:**
```php
// ❌ PROBLEM 1: Direct Model Access (tidak pakai Repository)
$query = PayrollPeriod::query();
$periods = $query->paginate($perPage);

// ❌ PROBLEM 2: Business Logic di Controller
if ($period->status !== 'draft') {
    return $this->errorResponse('Only draft periods can be deleted', 422);
}

// ❌ PROBLEM 3: Data Transformation di Controller
$result = $payrolls->map(function ($payroll) {
    return [
        'id' => $payroll->id,
        'employee_id' => $payroll->employee_id,
        // ... 20+ lines mapping
    ];
});

// ❌ PROBLEM 4: Query Building di Controller
$query->whereHas('employee', function ($q) use ($search) {
    $q->where('full_name', 'ilike', "%{$search}%");
});
```

**Methods List:**
```
PERIOD MANAGEMENT (7 methods):
├── periods()
├── showPeriod()
├── storePeriod()
├── updatePeriod()
├── destroyPeriod()
├── statistics()
└── config()

EMPLOYEE PAYROLL (5 methods):
├── employeePayroll()
├── periodEmployees()
├── showEmployeePayroll()
├── updateEmployeePayroll()
└── downloadPayslip()

PAYROLL ITEMS (4 methods):
├── getPayrollItems()
├── storePayrollItem()
├── updatePayrollItem()
└── destroyPayrollItem()

FORMULAS (7 methods):
├── getFormulas()
├── showFormula()
├── storeFormula()
├── updateFormula()
├── destroyFormula()
├── toggleFormulaStatus()
├── previewFormula()
└── formulaConfig()

MISC (2 methods):
├── itemCategories()
└── formulaConfig()
```

---

### 2. SecurityController.php (778 lines, 25 methods)

**Location:** `app/Http/Controllers/SecurityController.php`

**Current Problems:**
- No service injection
- Direct model queries
- Business logic in controller
- 33 IF statements

**Methods Grouping:**
```
SESSION MANAGEMENT:
├── activeSessions()
├── revokeSession()
├── revokeAllSessions()
└── revokeOtherSessions()

LOGIN HISTORY:
├── loginHistory()
└── loginAttempts()

SECURITY EVENTS:
├── securityEvents()
├── eventDetails()
└── markEventReviewed()

DEVICE MANAGEMENT:
├── trustedDevices()
├── trustDevice()
├── untrustDevice()
└── deviceDetails()

IP MANAGEMENT:
├── ipWhitelist()
├── addIpToWhitelist()
├── removeIpFromWhitelist()
└── checkIp()

SECURITY SETTINGS:
├── securitySettings()
├── updateSecuritySettings()
└── resetSecuritySettings()

REPORTS:
├── securityReport()
├── exportSecurityReport()
└── securityDashboard()

MISC:
├── passwordHistory()
└── forcePasswordChange()
```

---

### 3. LeaveController.php (566 lines, 13 methods)

**Location:** `app/Http/Controllers/LeaveController.php`

**Methods:**
```
├── index()
├── create()
├── store()
├── show()
├── edit()
├── update()
├── destroy()
├── approve()
├── reject()
├── cancel()
├── calendar()
├── balance()
└── history()
```

**Available Service:** `LeaveService.php` (20330 bytes) - TIDAK DIPAKAI!

---

### 4. ScheduleController.php (556 lines, 14 methods)

**Location:** `app/Http/Controllers/ScheduleController.php`

**Available Services:**
- `ScheduleService.php` (17092 bytes)
- `ScheduleManagementService.php` (19563 bytes)

---

### 5. ImportController.php (569 lines, 7 methods)

**Location:** `app/Http/Controllers/Api/ImportController.php`

**Problems:**
- File processing langsung di controller
- Excel parsing di controller
- Validation logic kompleks

---

## Target Architecture

### Clean Architecture Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                         HTTP REQUEST                             │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      MIDDLEWARE LAYER                            │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐               │
│  │    Auth     │ │   Throttle  │ │    CORS     │               │
│  └─────────────┘ └─────────────┘ └─────────────┘               │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    CONTROLLER (Thin Layer)                       │
│                                                                  │
│  Responsibilities:                                               │
│  ✅ Route handling                                               │
│  ✅ Request/Response formatting                                  │
│  ✅ Call FormRequest for validation                              │
│  ✅ Call Service for business logic                              │
│  ✅ Return appropriate HTTP response                             │
│                                                                  │
│  NOT Responsibilities:                                           │
│  ❌ Business logic                                               │
│  ❌ Data transformation                                          │
│  ❌ Direct database queries                                      │
│  ❌ File processing                                              │
└─────────────────────────────┬───────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              │                               │
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────────────┐
│      FORM REQUEST       │     │           SERVICE               │
│                         │     │                                 │
│  ✅ Input validation    │     │  ✅ Business logic              │
│  ✅ Authorization       │     │  ✅ Orchestration               │
│  ✅ Sanitization        │     │  ✅ Transaction management      │
│                         │     │  ✅ Event dispatching           │
└─────────────────────────┘     └──────────────┬──────────────────┘
                                               │
                              ┌────────────────┼────────────────┐
                              │                │                │
                              ▼                ▼                ▼
               ┌──────────────────┐ ┌──────────────┐ ┌──────────────┐
               │   REPOSITORY     │ │   EXTERNAL   │ │    EVENT     │
               │                  │ │   SERVICES   │ │   HANDLERS   │
               │ ✅ Data access   │ │              │ │              │
               │ ✅ Query building│ │ ✅ API calls │ │ ✅ Side      │
               │ ✅ Caching       │ │ ✅ Storage   │ │    effects   │
               └────────┬─────────┘ └──────────────┘ └──────────────┘
                        │
                        ▼
               ┌──────────────────┐
               │      MODEL       │
               │                  │
               │ ✅ Data structure│
               │ ✅ Relationships │
               │ ✅ Accessors     │
               │ ✅ Scopes        │
               └──────────────────┘
```

### Directory Structure (Target)

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── Payroll/                    # ← Split by domain
│   │       │   ├── PayrollPeriodController.php
│   │       │   ├── PayrollItemController.php
│   │       │   ├── PayrollFormulaController.php
│   │       │   └── PayrollEmployeeController.php
│   │       ├── Security/
│   │       │   ├── SessionController.php
│   │       │   ├── DeviceController.php
│   │       │   ├── SecurityEventController.php
│   │       │   └── SecuritySettingsController.php
│   │       └── Leave/
│   │           ├── LeaveRequestController.php
│   │           ├── LeaveApprovalController.php
│   │           └── LeaveBalanceController.php
│   │
│   └── Requests/                           # ← Form Requests
│       ├── Payroll/
│       │   ├── StorePayrollPeriodRequest.php
│       │   ├── UpdatePayrollPeriodRequest.php
│       │   └── StorePayrollItemRequest.php
│       └── Security/
│           └── UpdateSecuritySettingsRequest.php
│
├── Services/
│   ├── Payroll/
│   │   ├── PayrollPeriodService.php
│   │   ├── PayrollCalculationService.php   # ← Sudah ada
│   │   ├── PayrollItemService.php
│   │   └── PayrollFormulaService.php
│   ├── Security/
│   │   ├── SessionManagementService.php
│   │   ├── DeviceManagementService.php
│   │   └── SecurityEventService.php        # ← Sudah ada
│   └── Leave/
│       └── LeaveService.php                # ← Sudah ada
│
├── Repositories/                           # ← NEW
│   ├── PayrollRepository.php
│   ├── PayrollPeriodRepository.php
│   ├── SecurityEventRepository.php
│   └── LeaveRequestRepository.php
│
└── DTOs/                                   # ← NEW (Optional)
    ├── Payroll/
    │   ├── PayrollPeriodDTO.php
    │   └── PayrollItemDTO.php
    └── Security/
        └── SecurityEventDTO.php
```

---

## Refactoring Guide per Controller

### Phase 1: PayrollApiController (Priority: HIGH)

#### Step 1: Split into Domain Controllers

**BEFORE (1 file, 25 methods):**
```
PayrollApiController.php (1022 lines)
├── periods(), showPeriod(), storePeriod(), updatePeriod(), destroyPeriod()
├── statistics(), config()
├── employeePayroll(), periodEmployees(), showEmployeePayroll()
├── updateEmployeePayroll(), downloadPayslip()
├── getPayrollItems(), storePayrollItem(), updatePayrollItem(), destroyPayrollItem()
├── itemCategories()
├── getFormulas(), showFormula(), storeFormula(), updateFormula()
├── destroyFormula(), toggleFormulaStatus(), previewFormula(), formulaConfig()
```

**AFTER (4 files, ~6 methods each):**
```
Api/Payroll/
├── PayrollPeriodController.php (~200 lines)
│   ├── index()      ← periods()
│   ├── show()       ← showPeriod()
│   ├── store()      ← storePeriod()
│   ├── update()     ← updatePeriod()
│   ├── destroy()    ← destroyPeriod()
│   ├── statistics() ← statistics()
│   └── config()     ← config()
│
├── PayrollEmployeeController.php (~250 lines)
│   ├── index()      ← periodEmployees()
│   ├── show()       ← showEmployeePayroll()
│   ├── update()     ← updateEmployeePayroll()
│   ├── myPayroll()  ← employeePayroll()
│   └── downloadPayslip() ← downloadPayslip()
│
├── PayrollItemController.php (~150 lines)
│   ├── index()      ← getPayrollItems()
│   ├── store()      ← storePayrollItem()
│   ├── update()     ← updatePayrollItem()
│   ├── destroy()    ← destroyPayrollItem()
│   └── categories() ← itemCategories()
│
└── PayrollFormulaController.php (~200 lines)
    ├── index()      ← getFormulas()
    ├── show()       ← showFormula()
    ├── store()      ← storeFormula()
    ├── update()     ← updateFormula()
    ├── destroy()    ← destroyFormula()
    ├── toggle()     ← toggleFormulaStatus()
    ├── preview()    ← previewFormula()
    └── config()     ← formulaConfig()
```

#### Step 2: Create/Update Services

**File: `app/Services/Payroll/PayrollPeriodService.php`**

```php
<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Repositories\PayrollPeriodRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PayrollPeriodService
{
    public function __construct(
        private readonly PayrollPeriodRepository $repository
    ) {}

    public function getPeriods(array $filters): LengthAwarePaginator
    {
        return $this->repository->getWithFilters($filters);
    }

    public function getPeriodById(string $id): ?PayrollPeriod
    {
        return $this->repository->findWithRelations($id, ['payrollItems.employee']);
    }

    public function createPeriod(array $data): PayrollPeriod
    {
        return DB::transaction(function () use ($data) {
            $period = $this->repository->create([
                ...$data,
                'status' => 'draft',
            ]);

            // Dispatch event if needed
            // event(new PayrollPeriodCreated($period));

            return $period;
        });
    }

    public function updatePeriod(PayrollPeriod $period, array $data): PayrollPeriod
    {
        return DB::transaction(function () use ($period, $data) {
            $this->repository->update($period, $data);
            return $period->fresh();
        });
    }

    public function deletePeriod(PayrollPeriod $period): bool
    {
        if (!$this->canDelete($period)) {
            throw new \DomainException('Only draft periods can be deleted');
        }

        return $this->repository->delete($period);
    }

    public function canDelete(PayrollPeriod $period): bool
    {
        return $period->status === 'draft';
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }
}
```

#### Step 3: Create Repository

**File: `app/Repositories/PayrollPeriodRepository.php`**

```php
<?php

namespace App\Repositories;

use App\Models\PayrollPeriod;
use Illuminate\Pagination\LengthAwarePaginator;

class PayrollPeriodRepository
{
    public function getWithFilters(array $filters): LengthAwarePaginator
    {
        $query = PayrollPeriod::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('start_date', $filters['year']);
        }

        if (!empty($filters['month'])) {
            $query->whereMonth('start_date', $filters['month']);
        }

        $query->orderBy('start_date', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function findWithRelations(string $id, array $relations = []): ?PayrollPeriod
    {
        return PayrollPeriod::with($relations)->find($id);
    }

    public function create(array $data): PayrollPeriod
    {
        return PayrollPeriod::create($data);
    }

    public function update(PayrollPeriod $period, array $data): bool
    {
        return $period->update($data);
    }

    public function delete(PayrollPeriod $period): bool
    {
        return $period->delete();
    }

    public function getStatistics(): array
    {
        return [
            'total_periods' => PayrollPeriod::count(),
            'draft_periods' => PayrollPeriod::where('status', 'draft')->count(),
            'processed_periods' => PayrollPeriod::where('status', 'processed')->count(),
            'paid_periods' => PayrollPeriod::where('status', 'paid')->count(),
        ];
    }
}
```

#### Step 4: Create Form Request

**File: `app/Http/Requests/Payroll/StorePayrollPeriodRequest.php`**

```php
<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-payroll');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:monthly,weekly,biweekly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'pay_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama periode wajib diisi',
            'end_date.after' => 'Tanggal akhir harus setelah tanggal mulai',
        ];
    }
}
```

#### Step 5: Refactored Controller

**File: `app/Http/Controllers/Api/Payroll/PayrollPeriodController.php`**

```php
<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Payroll\StorePayrollPeriodRequest;
use App\Http\Requests\Payroll\UpdatePayrollPeriodRequest;
use App\Services\Payroll\PayrollPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollPeriodController extends BaseApiController
{
    public function __construct(
        private readonly PayrollPeriodService $periodService
    ) {}

    /**
     * GET /api/v1/payroll/periods
     */
    public function index(Request $request): JsonResponse
    {
        $periods = $this->periodService->getPeriods([
            'status' => $request->get('status'),
            'year' => $request->get('year'),
            'month' => $request->get('month'),
            'per_page' => $request->get('per_page', 15),
        ]);

        return $this->paginatedResponse($periods, 'Payroll periods retrieved');
    }

    /**
     * GET /api/v1/payroll/periods/{id}
     */
    public function show(string $id): JsonResponse
    {
        $period = $this->periodService->getPeriodById($id);

        if (!$period) {
            return $this->errorResponse('Period not found', 404);
        }

        return $this->apiResponse($period, 'Period retrieved');
    }

    /**
     * POST /api/v1/payroll/periods
     */
    public function store(StorePayrollPeriodRequest $request): JsonResponse
    {
        $period = $this->periodService->createPeriod($request->validated());

        return $this->apiResponse($period, 'Period created', 201);
    }

    /**
     * PUT /api/v1/payroll/periods/{id}
     */
    public function update(UpdatePayrollPeriodRequest $request, string $id): JsonResponse
    {
        $period = $this->periodService->getPeriodById($id);

        if (!$period) {
            return $this->errorResponse('Period not found', 404);
        }

        $updated = $this->periodService->updatePeriod($period, $request->validated());

        return $this->apiResponse($updated, 'Period updated');
    }

    /**
     * DELETE /api/v1/payroll/periods/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $period = $this->periodService->getPeriodById($id);

        if (!$period) {
            return $this->errorResponse('Period not found', 404);
        }

        try {
            $this->periodService->deletePeriod($period);
            return $this->apiResponse(null, 'Period deleted');
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/v1/payroll/statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->periodService->getStatistics();

        return $this->apiResponse($stats, 'Statistics retrieved');
    }
}
```

#### Step 6: Update Routes

**File: `routes/api.php`**

```php
// BEFORE (spaghetti routes)
Route::prefix('payroll')->group(function () {
    Route::get('/periods', [PayrollApiController::class, 'periods']);
    Route::get('/periods/{id}', [PayrollApiController::class, 'showPeriod']);
    Route::post('/periods', [PayrollApiController::class, 'storePeriod']);
    // ... 20+ more routes
});

// AFTER (organized by domain)
Route::prefix('payroll')->group(function () {
    // Periods
    Route::apiResource('periods', Payroll\PayrollPeriodController::class);
    Route::get('periods/statistics', [Payroll\PayrollPeriodController::class, 'statistics']);

    // Employee Payrolls
    Route::get('periods/{period}/employees', [Payroll\PayrollEmployeeController::class, 'index']);
    Route::get('periods/{period}/employees/{employee}', [Payroll\PayrollEmployeeController::class, 'show']);
    Route::put('periods/{period}/employees/{employee}', [Payroll\PayrollEmployeeController::class, 'update']);
    Route::get('my-payroll', [Payroll\PayrollEmployeeController::class, 'myPayroll']);
    Route::get('payslip/{id}/download', [Payroll\PayrollEmployeeController::class, 'downloadPayslip']);

    // Items
    Route::apiResource('payrolls.items', Payroll\PayrollItemController::class)->shallow();
    Route::get('item-categories', [Payroll\PayrollItemController::class, 'categories']);

    // Formulas
    Route::apiResource('formulas', Payroll\PayrollFormulaController::class);
    Route::post('formulas/{id}/toggle', [Payroll\PayrollFormulaController::class, 'toggle']);
    Route::post('formulas/{id}/preview', [Payroll\PayrollFormulaController::class, 'preview']);
});
```

---

### Phase 2: SecurityController (Priority: HIGH)

#### Split Strategy

```
BEFORE: SecurityController.php (778 lines, 25 methods)

AFTER:
├── Security/SessionController.php
│   ├── index()           ← activeSessions()
│   ├── revoke()          ← revokeSession()
│   ├── revokeAll()       ← revokeAllSessions()
│   └── revokeOthers()    ← revokeOtherSessions()
│
├── Security/LoginHistoryController.php
│   ├── index()           ← loginHistory()
│   └── attempts()        ← loginAttempts()
│
├── Security/SecurityEventController.php
│   ├── index()           ← securityEvents()
│   ├── show()            ← eventDetails()
│   └── markReviewed()    ← markEventReviewed()
│
├── Security/DeviceController.php
│   ├── index()           ← trustedDevices()
│   ├── trust()           ← trustDevice()
│   ├── untrust()         ← untrustDevice()
│   └── show()            ← deviceDetails()
│
├── Security/IpWhitelistController.php
│   ├── index()           ← ipWhitelist()
│   ├── store()           ← addIpToWhitelist()
│   ├── destroy()         ← removeIpFromWhitelist()
│   └── check()           ← checkIp()
│
├── Security/SecuritySettingsController.php
│   ├── index()           ← securitySettings()
│   ├── update()          ← updateSecuritySettings()
│   └── reset()           ← resetSecuritySettings()
│
└── Security/SecurityReportController.php
    ├── index()           ← securityReport()
    ├── export()          ← exportSecurityReport()
    └── dashboard()       ← securityDashboard()
```

#### Services to Create/Use

```php
// Existing (bisa dipakai langsung):
- SecurityService.php
- SecurityEventService.php
- Security/SecurityReportService.php

// Need to create:
- Security/SessionManagementService.php
- Security/DeviceManagementService.php
- Security/IpWhitelistService.php
```

---

### Phase 3: LeaveController (Priority: MEDIUM)

#### Current State
- 566 lines, 13 methods
- LeaveService.php SUDAH ADA (20330 bytes) tapi TIDAK DIPAKAI!

#### Refactor Strategy
1. Inject `LeaveService` ke controller
2. Pindahkan semua business logic ke service
3. Controller hanya handle request/response

```php
// BEFORE
public function approve(Request $request, $id)
{
    $leave = LeaveRequest::findOrFail($id);

    if ($leave->status !== 'pending') {
        return back()->with('error', 'Tidak bisa approve');
    }

    $leave->update([
        'status' => 'approved',
        'approved_by' => auth()->id(),
        'approved_at' => now(),
    ]);

    // Send notification...
    // Update balance...
    // etc
}

// AFTER
public function approve(ApproveLeaveRequest $request, string $id)
{
    try {
        $leave = $this->leaveService->approve($id, $request->user());
        return $this->apiResponse($leave, 'Leave approved');
    } catch (\DomainException $e) {
        return $this->errorResponse($e->getMessage(), 422);
    }
}
```

---

## Code Examples

### Example: Thin Controller Pattern

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreEmployeeRequest;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;

class EmployeeController extends BaseApiController
{
    // ✅ Single dependency injection
    public function __construct(
        private readonly EmployeeService $employeeService
    ) {}

    // ✅ Thin method - only 5 lines
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->create($request->validated());

        return $this->apiResponse($employee, 'Employee created', 201);
    }

    // ✅ No business logic, just orchestration
    public function show(string $id): JsonResponse
    {
        $employee = $this->employeeService->findById($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        return $this->apiResponse($employee);
    }
}
```

### Example: Service with Business Logic

```php
<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\EmployeeRepository;
use App\Events\EmployeeCreated;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepository $repository,
        private readonly EmployeeIdGeneratorService $idGenerator
    ) {}

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            // Business logic: Generate employee ID
            $data['employee_id'] = $this->idGenerator->generate();

            // Business logic: Set default values
            $data['status'] = 'active';
            $data['joined_at'] = $data['joined_at'] ?? now();

            // Create via repository
            $employee = $this->repository->create($data);

            // Dispatch domain event
            event(new EmployeeCreated($employee));

            return $employee;
        });
    }

    public function findById(string $id): ?Employee
    {
        return $this->repository->findWithRelations($id, [
            'department',
            'position',
            'user',
        ]);
    }
}
```

### Example: Repository Pattern

```php
<?php

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeRepository
{
    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    public function update(Employee $employee, array $data): bool
    {
        return $employee->update($data);
    }

    public function delete(Employee $employee): bool
    {
        return $employee->delete();
    }

    public function findWithRelations(string $id, array $relations = []): ?Employee
    {
        return Employee::with($relations)->find($id);
    }

    public function getWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Employee::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('full_name', 'ilike', "%{$filters['search']}%")
                  ->orWhere('employee_id', 'ilike', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('full_name')->paginate($filters['per_page'] ?? 15);
    }

    public function getActiveCount(): int
    {
        return Employee::where('status', 'active')->count();
    }
}
```

---

## Testing Strategy

### Unit Test untuk Service

```php
<?php

namespace Tests\Unit\Services;

use App\Models\PayrollPeriod;
use App\Repositories\PayrollPeriodRepository;
use App\Services\Payroll\PayrollPeriodService;
use Mockery;
use Tests\TestCase;

class PayrollPeriodServiceTest extends TestCase
{
    private PayrollPeriodService $service;
    private $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(PayrollPeriodRepository::class);
        $this->service = new PayrollPeriodService($this->mockRepository);
    }

    public function test_can_delete_draft_period(): void
    {
        $period = new PayrollPeriod(['status' => 'draft']);

        $this->mockRepository
            ->shouldReceive('delete')
            ->with($period)
            ->once()
            ->andReturn(true);

        $result = $this->service->deletePeriod($period);

        $this->assertTrue($result);
    }

    public function test_cannot_delete_non_draft_period(): void
    {
        $period = new PayrollPeriod(['status' => 'processed']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only draft periods can be deleted');

        $this->service->deletePeriod($period);
    }

    public function test_get_periods_with_filters(): void
    {
        $filters = ['status' => 'draft', 'year' => 2024];

        $this->mockRepository
            ->shouldReceive('getWithFilters')
            ->with($filters)
            ->once()
            ->andReturn(collect([]));

        $this->service->getPeriods($filters);
    }
}
```

### Feature Test untuk Controller

```php
<?php

namespace Tests\Feature\Api\Payroll;

use App\Models\PayrollPeriod;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayrollPeriodControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->admin()->create();
    }

    public function test_can_list_periods(): void
    {
        PayrollPeriod::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/payroll/periods');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'start_date', 'end_date', 'status']
                    ],
                    'meta'
                ]
            ]);
    }

    public function test_can_create_period(): void
    {
        $data = [
            'name' => 'January 2024',
            'type' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'pay_date' => '2024-02-05',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/payroll/periods', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'January 2024')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('payroll_periods', ['name' => 'January 2024']);
    }

    public function test_cannot_delete_processed_period(): void
    {
        $period = PayrollPeriod::factory()->create(['status' => 'processed']);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/v1/payroll/periods/{$period->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only draft periods can be deleted');
    }
}
```

---

## Migration Checklist

### Pre-Refactor Checklist

- [ ] Backup database
- [ ] Create new branch: `refactor/payroll-controller`
- [ ] Document current API contracts (request/response)
- [ ] Identify all routes using the controller
- [ ] Write integration tests for current behavior (if not exist)
- [ ] Notify team about refactoring plan

### Refactoring Checklist (Per Controller)

#### PayrollApiController

- [ ] Create `app/Http/Controllers/Api/Payroll/` directory
- [ ] Create `PayrollPeriodController.php`
- [ ] Create `PayrollEmployeeController.php`
- [ ] Create `PayrollItemController.php`
- [ ] Create `PayrollFormulaController.php`
- [ ] Create `app/Http/Requests/Payroll/` directory
- [ ] Create Form Request classes
- [ ] Create `app/Services/Payroll/PayrollPeriodService.php`
- [ ] Create `app/Repositories/PayrollPeriodRepository.php`
- [ ] Update routes in `routes/api.php`
- [ ] Write unit tests for services
- [ ] Write feature tests for controllers
- [ ] Run full test suite
- [ ] Manual QA testing
- [ ] Deploy to staging
- [ ] Monitor for errors
- [ ] Deploy to production
- [ ] Delete old `PayrollApiController.php`

#### SecurityController

- [ ] Create `app/Http/Controllers/Api/Security/` directory
- [ ] Split into domain controllers
- [ ] Create/update services
- [ ] Create Form Requests
- [ ] Update routes
- [ ] Write tests
- [ ] QA & Deploy

#### LeaveController

- [ ] Inject existing `LeaveService`
- [ ] Move business logic to service
- [ ] Create Form Requests
- [ ] Write tests
- [ ] QA & Deploy

### Post-Refactor Checklist

- [ ] Update API documentation
- [ ] Update Postman/Insomnia collections
- [ ] Update frontend API calls (if routes changed)
- [ ] Remove deprecated controller files
- [ ] Code review by team
- [ ] Update CLAUDE.md with new architecture

---

## Metrics to Track

### Before Refactor
| Metric | PayrollApiController | SecurityController |
|--------|---------------------|-------------------|
| Lines | 1022 | 778 |
| Methods | 25 | 25 |
| Cyclomatic Complexity | High | High |
| Test Coverage | ~0% | ~0% |
| Dependencies | 0 (no DI) | 0 (no DI) |

### After Refactor (Target)
| Metric | Target per Controller |
|--------|----------------------|
| Lines | < 200 |
| Methods | < 10 |
| Cyclomatic Complexity | Low |
| Test Coverage | > 80% |
| Dependencies | 1-3 services |

---

## References

1. **SOLID Principles**: https://en.wikipedia.org/wiki/SOLID
2. **Laravel Best Practices**: https://github.com/alexeymezenin/laravel-best-practices
3. **Repository Pattern**: https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html
4. **Strangler Fig Pattern**: https://martinfowler.com/bliki/StranglerFigApplication.html

---

*Generated: December 2024*
*Author: Claude Code Assistant*
