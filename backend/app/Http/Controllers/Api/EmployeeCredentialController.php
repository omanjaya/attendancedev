<?php

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
