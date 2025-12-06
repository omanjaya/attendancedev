<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeApiController extends BaseApiController
{
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

                // Prepare metadata for department and position
                $metadata = [];
                if (isset($validated['department'])) {
                    $metadata['department'] = $validated['department'];
                }
                if (isset($validated['position'])) {
                    $metadata['position'] = $validated['position'];
                }

                // Prepare employee data
                $employeeData = [
                    'user_id' => $user->id,
                    'employee_id' => $validated['employee_code'] ?? 'EMP' . str_pad(Employee::count() + 1, 4, '0', STR_PAD_LEFT),
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'employee_type_id' => $validated['employee_type_id'],
                    // 'employee_type' => $validated['employment_type'] ?? 'staff', // Deprecated
                    'salary_type' => $validated['salary_type'] ?? 'monthly', // Deprecated
                    'salary_amount' => $validated['base_salary'] ?? 0,
                    'hire_date' => $validated['hire_date'] ?? ($request->get('join_date') ?? now()),
                    'is_active' => $validated['is_active'] ?? true,
                    'location_id' => $validated['location_id'] ?? null, // Assign location
                    'metadata' => $metadata, // Store department and position in metadata
                ];

                // Create employee
                return Employee::create($employeeData);
            });

            return $this->apiResponse(
                $employee->load(['user', 'location']),
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
     */
    public function show($id)
    {
        try {
            $employee = Employee::with(['user', 'location'])->find($id);

            if (!$employee) {
                return $this->errorResponse('Employee not found', 404);
            }

            return $this->apiResponse($employee, 'Employee retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update employee
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
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
            'location_id' => 'nullable|uuid|exists:locations,id', // Allow location update
        ]);

        try {
            $employee->update([
                'full_name' => $validated['full_name'] ?? $employee->full_name,
                'phone' => $validated['phone'] ?? $employee->phone,
                'employee_type_id' => $validated['employee_type_id'] ?? $employee->employee_type_id,
                'salary_type' => $validated['salary_type'] ?? $employee->salary_type,
                'salary_amount' => $validated['base_salary'] ?? $employee->salary_amount,
                'hire_date' => $validated['hire_date'] ?? $employee->hire_date,
                'is_active' => $validated['is_active'] ?? $employee->is_active,
                'location_id' => $validated['location_id'] ?? $employee->location_id, // Update location if provided
            ]);

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
     */
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
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
     */
    public function uploadAvatar(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
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
     */
    public function deleteAvatar($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
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
        if (!$currentUser->hasRole(['admin', 'super-admin', 'kepala-sekolah'])) {
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
}
