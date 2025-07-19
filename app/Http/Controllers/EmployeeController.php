<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\Request;

/**
 * Employee Management Controller
 *
 * Handles all employee-related HTTP requests with minimal complexity
 * Following single responsibility principle
 */
class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $service
    ) {}

    /**
     * Display employee list
     */
    public function index()
    {
        return view('pages.management.employees.index', $this->service->getIndexData());
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('pages.management.employees.create', $this->service->getFormData());
    }

    /**
     * Store new employee
     */
    public function store(EmployeeRequest $request)
    {
        try {
            $employee = $this->service->create($request->validated());

            return redirect()->route('employees.index')
                ->with('success', "Employee '{$employee->name}' has been created successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create employee. Please check your data and try again.')
                ->withInput();
        }
    }

    /**
     * Display employee details
     */
    public function show(Employee $employee)
    {
        return view('pages.management.employees.show', compact('employee'));
    }

    /**
     * Show edit form
     */
    public function edit(Employee $employee)
    {
        $employee->load(['user.roles', 'location']);

        return view('pages.management.employees.edit', [
            'employee' => $employee,
            ...$this->service->getFormData(),
        ]);
    }

    /**
     * Update employee
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        try {
            $this->service->update($employee, $request->validated());

            return redirect()->route('employees.index')
                ->with('success', "Employee '{$employee->name}' has been updated successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update employee. Please check your data and try again.')
                ->withInput();
        }
    }

    /**
     * Delete employee
     */
    public function destroy(Employee $employee)
    {
        try {
            $employeeName = $employee->name;
            $employee->delete();

            return redirect()->route('employees.index')
                ->with('success', "Employee '{$employeeName}' has been deleted successfully.");
        } catch (\Exception $e) {
            return redirect()->route('employees.index')
                ->with('error', 'Failed to delete employee. Please try again.');
        }
    }

    /**
     * DataTables AJAX endpoint
     */
    public function data(Request $request)
    {
        return $this->service->getDataTableResponse($request);
    }

    /**
     * Bulk operations handler
     */
    public function bulk(Request $request)
    {
        $result = $this->service->handleBulkOperation($request);

        // Handle JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json($result);
        }

        // Handle regular form submission
        $flashType = $result['success'] ? 'success' : 'error';

        return redirect()->route('employees.index')->with($flashType, $result['message']);
    }

    /**
     * Download CSV template
     */
    public function template()
    {
        $templatePath = storage_path('templates/employees.csv');

        // Check if template file exists
        if (! file_exists($templatePath)) {
            return redirect()->back()->with('error', 'Template file not found. Please contact administrator.');
        }

        try {
            return response()->download($templatePath, 'employee_template.csv', [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="employee_template.csv"',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to download template. Please try again.');
        }
    }

    /**
     * Export employees data
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            $filters = $request->only(['search', 'department', 'status', 'type', 'face_registered', 'date_from', 'date_to']);

            return $this->service->exportEmployees($format, $filters);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengekspor data: '.$e->getMessage());
        }
    }

    /**
     * Import employees from file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120', // Max 5MB
        ]);

        try {
            $file = $request->file('file');
            $results = $this->service->importEmployees($file);

            $message = "Import berhasil! {$results['success']} karyawan berhasil diimpor.";

            if (! empty($results['errors'])) {
                $message .= ' Terdapat '.count($results['errors']).' error.';
            }

            return redirect()->route('employees.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengimpor data: '.$e->getMessage());
        }
    }
}
