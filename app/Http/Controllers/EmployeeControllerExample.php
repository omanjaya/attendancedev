<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Employee Controller Example
 * 
 * Demonstrates how to use the repository pattern in controllers.
 * This provides better testability and separation of concerns.
 */
class EmployeeControllerExample extends Controller
{
    /**
     * Employee repository instance.
     */
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}

    /**
     * Display a listing of employees.
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'department' => $request->get('department'),
            'position' => $request->get('position'),
            'employment_type' => $request->get('employment_type'),
            'is_active' => $request->get('is_active')
        ];

        // Remove null filters
        $filters = array_filter($filters, fn($value) => $value !== null);

        $employees = $this->employeeRepository->getAll($filters, $request->get('per_page', 15));

        return view('employees.index', compact('employees'));
    }

    /**
     * Display the specified employee.
     */
    public function show(string $id)
    {
        $employee = $this->employeeRepository->findById($id);

        if (!$employee) {
            abort(404, 'Employee not found');
        }

        return view('employees.show', compact('employee'));
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'employee_code' => 'nullable|string|unique:employees,employee_code',
            'department' => 'required|string|max:100',
            'position' => 'required|string|max:100',
            'employment_type' => 'required|in:permanent,contract,part_time,honorary',
            'salary_type' => 'required|in:monthly,hourly',
            'base_salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'is_active' => 'boolean'
        ]);

        try {
            $employee = $this->employeeRepository->create($validated);

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Employee created successfully');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create employee: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, string $id)
    {
        $employee = $this->employeeRepository->findById($id);

        if (!$employee) {
            abort(404, 'Employee not found');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'department' => 'required|string|max:100',
            'position' => 'required|string|max:100',
            'employment_type' => 'required|in:permanent,contract,part_time,honorary',
            'salary_type' => 'required|in:monthly,hourly',
            'base_salary' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        try {
            $employee = $this->employeeRepository->update($employee, $validated);

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Employee updated successfully');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update employee: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(string $id)
    {
        $employee = $this->employeeRepository->findById($id);

        if (!$employee) {
            abort(404, 'Employee not found');
        }

        try {
            $this->employeeRepository->delete($employee);

            return redirect()
                ->route('employees.index')
                ->with('success', 'Employee deleted successfully');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete employee: ' . $e->getMessage());
        }
    }

    /**
     * API: Get active employees for dropdowns.
     */
    public function getActiveEmployees(): JsonResponse
    {
        $employees = $this->employeeRepository->getActive();

        return response()->json([
            'data' => $employees->map(fn($employee) => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_code' => $employee->employee_code,
                'department' => $employee->department,
                'position' => $employee->position
            ])
        ]);
    }

    /**
     * API: Search employees.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $employees = $this->employeeRepository->search($query);

        return response()->json([
            'data' => $employees->map(fn($employee) => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_code' => $employee->employee_code,
                'department' => $employee->department
            ])
        ]);
    }

    /**
     * API: Get employee statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->employeeRepository->getStatistics();

        return response()->json([
            'data' => $statistics
        ]);
    }

    /**
     * Bulk operations example.
     */
    public function bulkAction(Request $request)
    {
        $action = $request->get('action');
        $employeeIds = $request->get('employee_ids', []);

        if (empty($employeeIds)) {
            return back()->with('error', 'No employees selected');
        }

        try {
            switch ($action) {
                case 'activate':
                    $this->employeeRepository->bulkUpdate(
                        array_map(fn($id) => ['id' => $id, 'is_active' => true], $employeeIds)
                    );
                    $message = 'Employees activated successfully';
                    break;

                case 'deactivate':
                    $this->employeeRepository->bulkUpdate(
                        array_map(fn($id) => ['id' => $id, 'is_active' => false], $employeeIds)
                    );
                    $message = 'Employees deactivated successfully';
                    break;

                default:
                    return back()->with('error', 'Invalid action');
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Bulk operation failed: ' . $e->getMessage());
        }
    }
}