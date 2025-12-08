<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DepartmentApiController extends BaseApiController
{
    /**
     * Get all departments
     */
    public function index(Request $request)
    {
        $query = Department::query();

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        // Active filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Default: only active, ordered
        if (!$request->has('include_inactive')) {
            $query->active();
        }

        // For select dropdowns, return all without pagination
        if ($request->boolean('all')) {
            $departments = $query->ordered()->get();
            return $this->apiResponse($departments, 'Departments retrieved');
        }

        $departments = $query->ordered()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($departments, 'Departments retrieved');
    }

    /**
     * Get single department
     */
    public function show(string $id)
    {
        $department = Department::find($id);

        if (!$department) {
            return $this->errorResponse('Department not found', 404);
        }

        $department->employees_count = $department->employees()->count();

        return $this->apiResponse($department, 'Department retrieved');
    }

    /**
     * Create new department
     */
    public function store(Request $request)
    {
        // Check authorization
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage departments.', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:departments,code',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            $department = Department::create($validated);

            Log::info('Department created', [
                'department_id' => $department->id,
                'code' => $department->code,
                'created_by' => auth()->id(),
            ]);

            return $this->apiResponse($department, 'Department created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create department', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return $this->errorResponse('Failed to create department: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update department
     */
    public function update(Request $request, string $id)
    {
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage departments.', 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return $this->errorResponse('Department not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:50|unique:departments,code,' . $id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $department->update($validated);

            Log::info('Department updated', [
                'department_id' => $department->id,
                'code' => $department->code,
                'updated_by' => auth()->id(),
            ]);

            return $this->apiResponse($department->fresh(), 'Department updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update department', [
                'department_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to update department: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete department
     */
    public function destroy(string $id)
    {
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage departments.', 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return $this->errorResponse('Department not found', 404);
        }

        // Check if any employees are using this department
        $employeesCount = $department->employees()->count();
        if ($employeesCount > 0) {
            return $this->errorResponse(
                "Tidak dapat menghapus unit kerja ini karena masih digunakan oleh {$employeesCount} pegawai.",
                400
            );
        }

        try {
            $department->delete();

            Log::info('Department deleted', [
                'department_id' => $id,
                'code' => $department->code,
                'deleted_by' => auth()->id(),
            ]);

            return $this->apiResponse(null, 'Department deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete department', [
                'department_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to delete department: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reorder departments
     */
    public function reorder(Request $request)
    {
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage departments.', 403);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|uuid|exists:departments,id',
            'order.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            foreach ($validated['order'] as $item) {
                Department::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            return $this->apiResponse(null, 'Departments reordered successfully');
        } catch (\Exception $e) {
            Log::error('Failed to reorder departments', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to reorder departments', 500);
        }
    }
}
