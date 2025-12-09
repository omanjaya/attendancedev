<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EmployeeResource;
use App\Http\Traits\PreventsIdor;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeApiController extends BaseApiController
{
    use PreventsIdor;
    /**
     * Get list of employees with pagination and filtering
     */
    public function index(Request $request)
    {
        $query = Employee::query()
            ->with(['user:id,name,email', 'location:id,name']);

        // Apply search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%");
                    });
            });
        }

        // Apply department filter (search in metadata JSON)
        if ($department = $request->get('department')) {
            $query->whereJsonContains('metadata->department', $department);
        }

        // Apply position filter (search in metadata JSON)
        if ($position = $request->get('position')) {
            $query->whereJsonContains('metadata->position', $position);
        }

        // Apply employment type filter
        if ($type = $request->get('employment_type')) {
            $query->where('employee_type', $type);
        }

        // Apply status filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortField = $request->get('sort', 'full_name');
        $sortDir = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDir);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $employees = $query->paginate($perPage);

        // Transform using EmployeeResource
        return response()->json([
            'success' => true,
            'message' => 'Employees retrieved successfully',
            'data' => EmployeeResource::collection($employees->items()),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Create a new employee
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
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
            'password' => 'nullable|string|min:8', // Accept password from frontend
            'role' => 'nullable|string|in:pegawai,guru,admin,kepala-sekolah', // Accept role
            'location_id' => 'nullable|uuid|exists:locations,id', // Accept location assignment
            // Dynamic fields based on employee type
            'subject_id' => 'nullable|uuid|exists:subjects,id', // For teachers (flexible schedule)
            'department_id' => 'nullable|uuid|exists:departments,id', // For staff (fixed schedule) - Unit Kerja
            'position_id' => 'nullable|uuid|exists:positions,id', // For staff (fixed schedule) - Jabatan
        ]);

        try {
            $employee = DB::transaction(function () use ($validated, $request) {
                // Create user first with provided or default password
                $password = $validated['password'] ?? 'password123';
                $role = $validated['role'] ?? 'pegawai';

                // Create user (exclude role from attributes as it's not a column)
                $user = \App\Models\User::create([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($password), // Use Hash facade for consistency
                    'force_password_change' => false, // Disable force password change for easier onboarding
                ]);

                // Assign role using Spatie
                $user->assignRole($role);

                // Prepare metadata for department and position (legacy fallback)
                $metadata = [];
                if (isset($validated['department'])) {
                    $metadata['department'] = $validated['department'];
                }
                if (isset($validated['position'])) {
                    $metadata['position'] = $validated['position'];
                }

                // Prepare employee data
                // Determine legacy employee_type based on role or employee_type_id
                $legacyEmployeeType = 'staff'; // Default to staff
                if ($role === 'guru') {
                    $legacyEmployeeType = 'honorary'; // Teachers are typically honorary
                } elseif (in_array($role, ['admin', 'kepala-sekolah'])) {
                    $legacyEmployeeType = 'permanent'; // Admin/Principal are permanent
                }

                $employeeData = [
                    'user_id' => $user->id,
                    'employee_id' => $validated['employee_code'] ?? 'EMP' . str_pad(Employee::count() + 1, 4, '0', STR_PAD_LEFT),
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'employee_type' => $legacyEmployeeType, // Legacy enum column (NOT NULL)
                    'employee_type_id' => $validated['employee_type_id'],
                    'salary_type' => $validated['salary_type'] ?? 'monthly',
                    'salary_amount' => $validated['base_salary'] ?? 0,
                    'hire_date' => $validated['hire_date'] ?? ($request->get('join_date') ?? now()),
                    'is_active' => $validated['is_active'] ?? true,
                    'location_id' => $validated['location_id'] ?? null,
                    // Dynamic fields based on employee type
                    'subject_id' => $validated['subject_id'] ?? null,
                    'department_id' => $validated['department_id'] ?? null,
                    'position_id' => $validated['position_id'] ?? null,
                    'metadata' => $metadata,
                ];

                // Create employee
                return Employee::create($employeeData);
            });

            return $this->apiResponse(
                $employee->load(['user', 'location', 'subject', 'departmentRelation', 'positionRelation']),
                'Employee created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search employees
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return $this->apiResponse([], 'Search query required');
        }

        $employees = Employee::query()
            ->select(['id', 'employee_id', 'full_name', 'is_active'])
            ->where('full_name', 'like', "%{$query}%")
            ->orWhere('employee_id', 'like', "%{$query}%")
            ->limit(20)
            ->get();

        return $this->apiResponse($employees, 'Search results');
    }

    /**
     * Get employee statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Employee::count(),
            'active' => Employee::where('is_active', true)->count(),
            'inactive' => Employee::where('is_active', false)->count(),
            'by_type' => Employee::select('employee_type', DB::raw('count(*) as count'))
                ->groupBy('employee_type')
                ->pluck('count', 'employee_type'),
        ];

        return $this->apiResponse($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get single employee
     * 
     * Security: IDOR protection - non-admin users can only view their own data
     */
    public function show($id)
    {
        try {
            $employee = Employee::with(['user', 'location'])->find($id);

            if (!$employee) {
                return $this->errorResponse('Employee not found', 404);
            }

            // IDOR Protection: Check if user can access this employee
            if (!$this->canAccessEmployee($employee)) {
                return $this->errorResponse('Unauthorized access to employee data', 403);
            }

            return $this->apiResponse($employee, 'Employee retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update employee
     * 
     * Security: IDOR protection - non-admin users can only update their own data
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // IDOR Protection: Check if user can access this employee
        if (!$this->canAccessEmployee($employee)) {
            return $this->errorResponse('Unauthorized access to employee data', 403);
        }

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'department' => 'sometimes|string|max:100',
            'position' => 'sometimes|string|max:100',
            'employee_type_id' => 'sometimes|exists:employee_types,id',
            'salary_type' => 'sometimes|in:monthly,hourly',
            'base_salary' => 'sometimes|numeric|min:0',
            'hire_date' => 'sometimes|date',
            'is_active' => 'boolean',
            'location_id' => 'nullable|uuid|exists:locations,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            
            // Extended Profile Fields (stored in metadata)
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female',
            'nik' => 'nullable|string|max:20',
            'npwp' => 'nullable|string|max:25',
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed',
            'religion' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            
            // JSON fields
            'emergency_contact' => 'nullable|array',
            'emergency_contact.name' => 'nullable|string|max:100',
            'emergency_contact.relation' => 'nullable|string|max:50',
            'emergency_contact.phone' => 'nullable|string|max:20',
            
            'education' => 'nullable|array',
            'education.level' => 'nullable|string|max:20',
            'education.institution' => 'nullable|string|max:100',
            'education.major' => 'nullable|string|max:100',
            'education.year' => 'nullable|numeric|digits:4',
            
            'bank_account' => 'nullable|array',
            'bank_account.bank_name' => 'nullable|string|max:50',
            'bank_account.account_number' => 'nullable|string|max:50',
            'bank_account.account_holder' => 'nullable|string|max:100',
        ]);

        try {
            // Prepare update data
            $updateData = [
                'full_name' => $validated['full_name'] ?? $employee->full_name,
                'phone' => $validated['phone'] ?? $employee->phone,
                'employee_type_id' => $validated['employee_type_id'] ?? $employee->employee_type_id,
                'salary_type' => $validated['salary_type'] ?? $employee->salary_type,
                'salary_amount' => $validated['base_salary'] ?? $employee->salary_amount,
                'hire_date' => $validated['hire_date'] ?? $employee->hire_date,
                'is_active' => $request->has('is_active') ? $validated['is_active'] : $employee->is_active,
                'location_id' => $request->has('location_id') ? $validated['location_id'] : $employee->location_id,
                'subject_id' => $request->has('subject_id') ? $validated['subject_id'] : $employee->subject_id,
                'department_id' => $request->has('department_id') ? $validated['department_id'] : $employee->department_id,
                'position_id' => $request->has('position_id') ? $validated['position_id'] : $employee->position_id,
            ];

            // Handle metadata updates
            // 1. Prepare Public Metadata (Searchable)
            $publicMetadata = isset($employee->attributes['metadata']) 
                ? json_decode($employee->attributes['metadata'], true) 
                : [];
            if (is_string($publicMetadata)) $publicMetadata = json_decode($publicMetadata, true) ?? [];

            // 2. Prepare Sensitive Data (Encrypted)
            $sensitiveData = $employee->sensitive_data ?? [];
            
            // Define keys for each bucket
            $sensitiveKeys = [
                'birth_date', 'birth_place', 'gender', 'nik', 'npwp', 
                'marital_status', 'religion', 'address',
                'emergency_contact', 'education', 'bank_account'
            ];

            $publicKeys = ['department', 'position', 'face_recognition']; // face_recognition stays in public meta for performance? or easy access

            $isSensitiveUpdated = false;
            $isPublicUpdated = false;

            // Process validated data
            foreach ($sensitiveKeys as $key) {
                if (array_key_exists($key, $validated)) {
                    $sensitiveData[$key] = $validated[$key];
                    $isSensitiveUpdated = true;
                }
            }

            // Handle legacy department/position overrides in metadata
            if (isset($validated['department'])) {
                $publicMetadata['department'] = $validated['department'];
                $isPublicUpdated = true;
            }
            if (isset($validated['position'])) {
                $publicMetadata['position'] = $validated['position'];
                $isPublicUpdated = true;
            }

            if ($isPublicUpdated) {
                $updateData['metadata'] = $publicMetadata;
            }
            
            if ($isSensitiveUpdated) {
                $updateData['sensitive_data'] = $sensitiveData;
            }

            $employee->update($updateData);

            return $this->apiResponse(
                $employee->fresh()->load(['user', 'location']),
                'Employee updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete employee
     * 
     * Security: IDOR protection - only admins can delete employees
     */
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // IDOR Protection: Check if user can access this employee (admin-level only)
        if (!$this->canAccessEmployee($employee)) {
            return $this->errorResponse('Unauthorized to delete this employee', 403);
        }

        try {
            $employee->delete();

            return $this->apiResponse(null, 'Employee deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete employee: ' . $e->getMessage(), 500);
        }
    }
    /**
     * Get employees with registered face data
     */
    public function withFaceData()
    {
        // Filter employees who have face descriptor in metadata
        $employees = Employee::where('is_active', true)
            ->get()
            ->filter(function ($employee) {
                return isset($employee->metadata['face_recognition']['descriptor']);
            })
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'employee_id' => $employee->employee_id,
                    'photo_url' => $employee->photo_url,
                    'face_descriptor' => $employee->metadata['face_recognition']['descriptor'] ?? null
                ];
            })
            ->values();

        return $this->apiResponse($employees, 'Employees with face data retrieved');
    }

    /**
     * Get employee dashboard data
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee record not found', 404);
        }

        $today = now();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // 1. Attendance Stats
        $attendanceStats = [
            'thisMonth' => \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->count(),
            'present' => \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'present')
                ->count(),
            'late' => \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'late')
                ->count(),
            'absent' => \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'absent')
                ->count(),
            'todayStatus' => null,
            'checkIn' => null,
            'checkOut' => null,
        ];

        $todayAttendance = \App\Models\Attendance::where('employee_id', $employee->id)
            ->forDate($today)
            ->first();

        if ($todayAttendance) {
            $attendanceStats['todayStatus'] = $todayAttendance->check_out_time ? 'checked-out' : 'checked-in';
            $attendanceStats['checkIn'] = $todayAttendance->check_in_time ? $todayAttendance->check_in_time->format('H:i') : null;
            $attendanceStats['checkOut'] = $todayAttendance->check_out_time ? $todayAttendance->check_out_time->format('H:i') : null;
        }

        // 2. Leave Stats
        // Assuming 12 days annual leave default if not in database
        $annualLeave = 12;
        $usedLeave = \App\Models\Leave::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $today->year)
            ->count(); // This is a simplification, should sum days

        $pendingLeave = \App\Models\Leave::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $leaveStats = [
            'balance' => $annualLeave - $usedLeave,
            'used' => $usedLeave,
            'pending' => $pendingLeave,
        ];

        // 3. Schedule
        $todaySchedule = $employee->getEffectiveScheduleForDate($today);
        
        $todayShift = 'Tidak Ada Jadwal';
        $todayTime = '-';
        $canAttend = $todaySchedule['can_attend'] ?? false;
        
        if ($todaySchedule['schedule_type'] === 'holiday') {
            $todayShift = 'Libur: ' . ($todaySchedule['holiday_name'] ?? 'Hari Libur');
            $todayTime = 'Libur';
        } elseif ($todaySchedule['schedule_type'] === 'teaching_override') {
            $todayShift = 'Mengajar';
            $todayTime = ($todaySchedule['start_time'] ? $todaySchedule['start_time']->format('H:i') : '-') . ' - ' . ($todaySchedule['end_time'] ? $todaySchedule['end_time']->format('H:i') : '-');
        } elseif ($todaySchedule['schedule_type'] === 'base_schedule') {
            $todayShift = 'Regular';
            $todayTime = ($todaySchedule['start_time'] ? $todaySchedule['start_time']->format('H:i') : '-') . ' - ' . ($todaySchedule['end_time'] ? $todaySchedule['end_time']->format('H:i') : '-');
        } elseif ($todaySchedule['schedule_type'] === 'no_teaching') {
             $todayShift = 'Tidak Ada Jadwal Mengajar';
             $todayTime = '-';
        }

        $nextShift = null;

        // Find next working day
        for ($i = 1; $i <= 7; $i++) {
            $nextDate = $today->copy()->addDays($i);
            $schedule = $employee->getEffectiveScheduleForDate($nextDate);
            
            if (($schedule['can_attend'] ?? false) && ($schedule['working_hours'] > 0 || $schedule['schedule_type'] === 'teaching_override')) {
                $shiftName = 'Regular';
                if ($schedule['schedule_type'] === 'teaching_override') {
                    $shiftName = 'Mengajar';
                }
                
                $nextShift = [
                    'date' => $nextDate->isoFormat('dddd, D MMMM'),
                    'shift' => $shiftName,
                    'time' => ($schedule['start_time'] ? $schedule['start_time']->format('H:i') : '-') . ' - ' . ($schedule['end_time'] ? $schedule['end_time']->format('H:i') : '-'),
                ];
                break;
            }
        }

        $scheduleData = [
            'today' => [
                'shift' => $todayShift,
                'time' => $todayTime,
                'location' => $employee->location->name ?? 'Office',
                'can_attend' => $canAttend,
                'message' => $todaySchedule['message'] ?? '',
                'schedule_type' => $todaySchedule['schedule_type'] ?? 'none',
            ],
            'nextShift' => $nextShift,
        ];

        // 4. Payroll (Mock for now as Payroll module might be complex)
        $payrollData = [
            'lastPayment' => [
                'amount' => $employee->salary_amount ?? 0,
                'date' => $today->copy()->subMonth()->endOfMonth()->format('Y-m-d'),
                'status' => 'paid'
            ],
            'nextPayment' => [
                'date' => $today->copy()->endOfMonth()->format('Y-m-d'),
                'estimated' => $employee->salary_amount ?? 0
            ]
        ];

        return $this->apiResponse([
            'attendance' => $attendanceStats,
            'leave' => $leaveStats,
            'schedule' => $scheduleData,
            'payroll' => $payrollData,
        ], 'Employee dashboard data retrieved');
    }

    /**
     * Upload avatar for employee
     * 
     * Security: IDOR protection - users can only upload their own avatar
     */
    public function uploadAvatar(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // IDOR Protection: Check if user can access this employee
        if (!$this->canAccessEmployee($employee)) {
            return $this->errorResponse('Unauthorized to upload avatar', 403);
        }

        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048', // Max 2MB
        ]);

        try {
            // Delete old avatar if exists
            if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
                Storage::disk('public')->delete($employee->photo_path);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            // Update employee photo_path
            $employee->update(['photo_path' => $avatarPath]);

            return $this->apiResponse([
                'employee' => $employee->fresh()->load(['user', 'location']),
                'avatar_url' => asset("storage/{$avatarPath}"),
            ], 'Avatar uploaded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload avatar: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete employee avatar
     * 
     * Security: IDOR protection - users can only delete their own avatar
     */
    public function deleteAvatar($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // IDOR Protection: Check if user can access this employee
        if (!$this->canAccessEmployee($employee)) {
            return $this->errorResponse('Unauthorized to delete avatar', 403);
        }

        try {
            // Delete avatar file if exists
            if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
                Storage::disk('public')->delete($employee->photo_path);
            }

            // Clear photo_path in database
            $employee->update(['photo_path' => null]);

            return $this->apiResponse(null, 'Avatar deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete avatar: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset password for an employee's user account (Admin only)
     */
    public function resetPassword(Request $request, $id)
    {
        $employee = Employee::with('user')->find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        if (!$employee->user) {
            return $this->errorResponse('User account not found for this employee', 404);
        }

        // Check if current user has permission (optional extra check)
        $currentUser = $request->user();
        
        $userRoles = $currentUser->getRoleNames()->map(function($role) {
            return strtolower($role);
        })->toArray();
        
        $allowedRoles = ['admin', 'super-admin', 'super admin', 'kepala-sekolah'];
        $hasAccess = !empty(array_intersect($userRoles, $allowedRoles));

        if (!$hasAccess) {
             return $this->errorResponse('Unauthorized. Only admins can reset passwords.', 403);
        }

        $validated = $request->validate([
            'new_password' => 'sometimes|string|min:8',
            'send_email' => 'boolean',
        ]);

        try {
            // Generate a random password if not provided
            $newPassword = $validated['new_password'] ?? \Illuminate\Support\Str::random(12);
            
            $employee->user->update([
                'password' => Hash::make($newPassword),
                'force_password_change' => true, // Force user to change password on next login
                'password_changed_at' => null, // Reset password change date
            ]);

            // TODO: Optionally send email notification with new password
            // if ($validated['send_email'] ?? false) {
            //     Mail::to($employee->user->email)->send(new PasswordResetNotification($newPassword));
            // }

            return $this->apiResponse([
                'employee_id' => $employee->id,
                'email' => $employee->user->email,
                'temporary_password' => $newPassword, // Only show this once
                'force_password_change' => true,
                'message' => 'Password berhasil direset. User harus mengubah password saat login berikutnya.',
            ], 'Password reset successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk actions for employees (delete, reset password, etc.)
     */
    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:delete,reset_password,activate,deactivate',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
        ]);

        $action = $validated['action'];
        $employeeIds = $validated['employee_ids'];
        $currentUser = $request->user();
        
        // Manual case-insensitive role check
        $userRoles = $currentUser->getRoleNames()->map(function($role) {
            return strtolower($role);
        })->toArray();
        
        $allowedRoles = ['admin', 'super-admin', 'super admin', 'kepala-sekolah'];
        $hasAccess = !empty(array_intersect($userRoles, $allowedRoles));

        if (!$hasAccess) {
             \Illuminate\Support\Facades\Log::warning('Bulk Action Unauthorized: User ' . $currentUser->id . ' Roles: ' . implode(',', $userRoles));
            return $this->errorResponse('Unauthorized. Only admins can perform bulk actions.', 403);
        }

        try {
            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => [],
                'reset_passwords' => [], // Only for reset_password action
            ];

            DB::beginTransaction();

            switch ($action) {
                case 'delete':
                    foreach ($employeeIds as $id) {
                        try {
                            $employee = Employee::find($id);
                            if ($employee) {
                                // Delete associated user account if exists
                                if ($employee->user_id) {
                                    \App\Models\User::where('id', $employee->user_id)->delete();
                                }
                                $employee->delete();
                                $results['success']++;
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Employee ID {$id} not found";
                            }
                        } catch (\Exception $e) {
                            $results['failed']++;
                            $results['errors'][] = "Failed to delete employee ID {$id}: " . $e->getMessage();
                        }
                    }
                    break;

                case 'reset_password':
                    foreach ($employeeIds as $id) {
                        try {
                            $employee = Employee::with('user')->find($id);
                            if ($employee && $employee->user) {
                                $newPassword = \Illuminate\Support\Str::random(12);
                                $employee->user->update([
                                    'password' => Hash::make($newPassword),
                                    'force_password_change' => true,
                                    'password_changed_at' => null,
                                ]);
                                $results['success']++;
                                $results['reset_passwords'][] = [
                                    'employee_id' => $employee->id,
                                    'name' => $employee->full_name,
                                    'email' => $employee->user->email,
                                    'temporary_password' => $newPassword,
                                ];
                            } elseif (!$employee) {
                                $results['failed']++;
                                $results['errors'][] = "Employee ID {$id} not found";
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Employee ID {$id} has no user account";
                            }
                        } catch (\Exception $e) {
                            $results['failed']++;
                            $results['errors'][] = "Failed to reset password for employee ID {$id}: " . $e->getMessage();
                        }
                    }
                    break;

                case 'activate':
                    foreach ($employeeIds as $id) {
                        try {
                            $employee = Employee::find($id);
                            if ($employee) {
                                $employee->update(['is_active' => true]);
                                $results['success']++;
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Employee ID {$id} not found";
                            }
                        } catch (\Exception $e) {
                            $results['failed']++;
                            $results['errors'][] = "Failed to activate employee ID {$id}: " . $e->getMessage();
                        }
                    }
                    break;

                case 'deactivate':
                    foreach ($employeeIds as $id) {
                        try {
                            $employee = Employee::find($id);
                            if ($employee) {
                                $employee->update(['is_active' => false]);
                                $results['success']++;
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Employee ID {$id} not found";
                            }
                        } catch (\Exception $e) {
                            $results['failed']++;
                            $results['errors'][] = "Failed to deactivate employee ID {$id}: " . $e->getMessage();
                        }
                    }
                    break;
            }

            DB::commit();

            $message = "Bulk {$action} completed: {$results['success']} success, {$results['failed']} failed";
            
            return $this->apiResponse($results, $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Bulk action failed: ' . $e->getMessage(), 500);
        }
    }
}
