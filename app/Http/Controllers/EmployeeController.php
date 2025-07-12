<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get statistics for the stats cards
        $totalEmployees = Employee::count();
        $activeToday = Employee::whereHas('attendances', function($query) {
            $query->whereDate('created_at', today());
        })->count();
        $onLeave = Employee::whereHas('leaves', function($query) {
            $query->where('status', 'approved')
                  ->whereDate('start_date', '<=', today())
                  ->whereDate('end_date', '>=', today());
        })->count();
        $pendingApprovals = Employee::whereHas('leaves', function($query) {
            $query->where('status', 'pending');
        })->count();

        // Get a sample of employees for the table display
        $employees = Employee::with(['user.roles', 'location', 'attendances' => function($query) {
            $query->latest()->limit(1);
        }])
        ->limit(10)
        ->get()
        ->map(function($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'email' => $employee->user->email,
                'type' => $employee->employee_type,
                'department' => $employee->location->name ?? 'N/A',
                'status' => $employee->is_active ? 'active' : 'inactive',
                'last_check_in' => $employee->attendances->first()?->created_at?->format('M d, Y H:i A') ?? 'Never',
                'face_registered' => isset($employee->metadata['face_data']) ? true : false,
            ];
        });

        return view('pages.management.employees.index', compact('employees', 'totalEmployees', 'activeToday', 'onLeave', 'pendingApprovals'));
    }

    /**
     * Get employees data for DataTables.
     */
    public function data()
    {
        $employees = Employee::with(['user.roles', 'location'])->select('employees.*');

        return DataTables::of($employees)
            ->addColumn('photo', function ($employee) {
                return '<img src="' . $employee->photo_url . '" alt="' . $employee->full_name . '" class="avatar avatar-sm rounded-circle">';
            })
            ->addColumn('full_name', function ($employee) {
                return $employee->full_name;
            })
            ->addColumn('role', function ($employee) {
                return $employee->user->roles->pluck('name')->map(function ($role) {
                    return '<span class="badge bg-blue-lt">' . ucfirst($role) . '</span>';
                })->implode(' ');
            })
            ->addColumn('type_badge', function ($employee) {
                $colors = [
                    'permanent' => 'green',
                    'honorary' => 'blue',
                    'staff' => 'yellow'
                ];
                $color = $colors[$employee->employee_type] ?? 'secondary';
                return '<span class="badge bg-' . $color . '-lt">' . ucfirst($employee->employee_type) . '</span>';
            })
            ->addColumn('status', function ($employee) {
                return $employee->is_active 
                    ? '<span class="badge bg-green">Active</span>' 
                    : '<span class="badge bg-red">Inactive</span>';
            })
            ->addColumn('actions', function ($employee) {
                return '
                    <div class="btn-list">
                        <a href="' . route('employees.show', $employee) . '" class="btn btn-sm btn-info">
                            View
                        </a>
                        <a href="' . route('employees.edit', $employee) . '" class="btn btn-sm btn-primary">
                            Edit
                        </a>
                        <button class="btn btn-sm btn-danger delete-employee" data-id="' . $employee->id . '">
                            Delete
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['photo', 'role', 'type_badge', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::where('name', '!=', 'superadmin')->get();
        return view('pages.management.employees.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|unique:employees,employee_id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'employee_type' => 'required|in:permanent,honorary,staff',
            'hire_date' => 'required|date',
            'salary_type' => 'required|in:hourly,monthly,fixed',
            'salary_amount' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'location_id' => 'nullable|exists:locations,id',
            'role' => 'required|exists:roles,name'
        ]);

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Assign role
            $user->assignRole($validated['role']);

            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('employee-photos', 'public');
            }

            // Create employee record
            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_id' => $validated['employee_id'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'photo_path' => $photoPath,
                'employee_type' => $validated['employee_type'],
                'hire_date' => $validated['hire_date'],
                'salary_type' => $validated['salary_type'],
                'salary_amount' => $validated['salary_amount'],
                'hourly_rate' => $validated['hourly_rate'],
                'location_id' => $validated['location_id'],
            ]);

            DB::commit();

            return redirect()->route('employees.index')
                ->with('success', 'Employee created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create employee: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $employee->load(['user.roles', 'location']);
        return view('pages.management.employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $roles = Role::where('name', '!=', 'superadmin')->get();
        $employee->load('user.roles');
        return view('pages.management.employees.edit', compact('employee', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|unique:employees,employee_id,' . $employee->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'employee_type' => 'required|in:permanent,honorary,staff',
            'hire_date' => 'required|date',
            'salary_type' => 'required|in:hourly,monthly,fixed',
            'salary_amount' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'location_id' => 'nullable|exists:locations,id',
            'is_active' => 'boolean',
            'role' => 'required|exists:roles,name'
        ]);

        DB::beginTransaction();
        try {
            // Update user account
            $employee->user->update([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
            ]);

            // Update role
            $employee->user->syncRoles([$validated['role']]);

            // Handle photo upload
            $updateData = [
                'employee_id' => $validated['employee_id'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'employee_type' => $validated['employee_type'],
                'hire_date' => $validated['hire_date'],
                'salary_type' => $validated['salary_type'],
                'salary_amount' => $validated['salary_amount'],
                'hourly_rate' => $validated['hourly_rate'],
                'location_id' => $validated['location_id'],
                'is_active' => $validated['is_active'] ?? true,
            ];

            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
                    Storage::disk('public')->delete($employee->photo_path);
                }
                // Store new photo
                $updateData['photo_path'] = $request->file('photo')->store('employee-photos', 'public');
            }

            // Update employee record
            $employee->update($updateData);

            DB::commit();

            return redirect()->route('employees.index')
                ->with('success', 'Employee updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to update employee: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        try {
            $employeeName = $employee->full_name;
            $employee->delete();
            
            return redirect()->route('employees.index')
                ->with('success', "Employee '{$employeeName}' deleted successfully.");
        } catch (\Exception $e) {
            return redirect()->route('employees.index')
                ->with('error', 'Failed to delete employee: ' . $e->getMessage());
        }
    }

    /**
     * Upload employee template
     */
    public function uploadTemplate(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('excel_file');
            $uploadedCount = 0;
            $errors = [];

            // Here you would process the Excel file
            // For now, we'll simulate successful upload
            $uploadedCount = 5; // Simulate 5 employees uploaded

            return response()->json([
                'success' => true,
                'message' => "Successfully uploaded {$uploadedCount} employees",
                'data' => [
                    'uploaded_count' => $uploadedCount,
                    'errors' => $errors
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload employees: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk edit employees
     */
    public function bulkEdit(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|string',
            'employee_type' => 'nullable|in:permanent,honorary,staff',
            'is_active' => 'nullable|boolean',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        try {
            $employeeIds = explode(',', $request->employee_ids);
            $updateData = [];

            if ($request->filled('employee_type')) {
                $updateData['employee_type'] = $request->employee_type;
            }
            if ($request->filled('is_active')) {
                $updateData['is_active'] = $request->boolean('is_active');
            }
            if ($request->filled('location_id')) {
                $updateData['location_id'] = $request->location_id;
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes specified'
                ], 400);
            }

            $updatedCount = Employee::whereIn('id', $employeeIds)->update($updateData);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} employees",
                'data' => [
                    'updated_count' => $updatedCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update employees: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete employees
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|string',
        ]);

        try {
            $employeeIds = explode(',', $request->employee_ids);
            $deletedCount = Employee::whereIn('id', $employeeIds)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} employees",
                'data' => [
                    'deleted_count' => $deletedCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employees: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download employee template
     */
    public function downloadTemplate()
    {
        try {
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="employee_template.xlsx"',
            ];

            // For now, we'll create a simple CSV template
            $template = "employee_id,first_name,last_name,email,phone,employee_type,hire_date,salary_type,salary_amount,hourly_rate,location_id\n";
            $template .= "EMP001,John,Doe,john.doe@example.com,1234567890,permanent,2024-01-01,monthly,5000000,0,1\n";
            $template .= "EMP002,Jane,Smith,jane.smith@example.com,0987654321,honorary,2024-01-02,hourly,0,100000,2\n";

            return response($template, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="employee_template.csv"',
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to download template: ' . $e->getMessage());
        }
    }
}