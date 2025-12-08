<?php

namespace App\Http\Controllers\Api;

use App\Models\EmployeeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeTypeApiController extends BaseApiController
{
    /**
     * Get all employee types
     */
    public function index(Request $request)
    {
        $query = EmployeeType::query();

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        // Filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->get('schedule_mode')) {
            $query->where('schedule_mode', $request->get('schedule_mode'));
        }

        // Default: only active, ordered by sort_order
        if (!$request->has('include_inactive')) {
            $query->active();
        }

        // For select dropdowns, return all without pagination
        if ($request->boolean('all')) {
            $types = $query->ordered()->get();
            return $this->apiResponse($types, 'Employee types retrieved');
        }

        $types = $query->ordered()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($types, 'Employee types retrieved');
    }

    /**
     * Get single employee type
     */
    public function show(string $id)
    {
        $type = EmployeeType::find($id);

        if (!$type) {
            return $this->errorResponse('Employee type not found', 404);
        }

        // Include employee count
        $type->employees_count = $type->employees()->count();

        return $this->apiResponse($type, 'Employee type retrieved');
    }

    /**
     * Create new employee type
     */
    public function store(Request $request)
    {
        // Check authorization (super admin or admin)
        $user = auth()->user();
        $userRoles = $user ? $user->load('roles')->roles->pluck('name')->toArray() : [];
        $allowedRoles = ['super-admin', 'Super Admin', 'admin', 'Admin'];

        if (empty(array_intersect($userRoles, $allowedRoles))) {
            return $this->errorResponse('Unauthorized. Only admins can manage employee types.', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:employee_types,code',
            'description' => 'nullable|string|max:500',
            'schedule_mode' => 'required|in:fixed,flexible',
            'is_active' => 'boolean',
        ]);

        try {
            $type = EmployeeType::create($validated);

            Log::info('Employee type created', [
                'type_id' => $type->id,
                'code' => $type->code,
                'created_by' => auth()->id(),
            ]);

            return $this->apiResponse($type, 'Employee type created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create employee type', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return $this->errorResponse('Failed to create employee type: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update employee type
     */
    public function update(Request $request, string $id)
    {
        // Check authorization (super admin or admin)
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage employee types.', 403);
        }

        $type = EmployeeType::find($id);

        if (!$type) {
            return $this->errorResponse('Employee type not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:50|unique:employee_types,code,' . $id,
            'description' => 'nullable|string|max:500',
            'schedule_mode' => 'sometimes|in:fixed,flexible',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $type->update($validated);

            Log::info('Employee type updated', [
                'type_id' => $type->id,
                'code' => $type->code,
                'updated_by' => auth()->id(),
            ]);

            return $this->apiResponse($type->fresh(), 'Employee type updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update employee type', [
                'type_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to update employee type: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete employee type
     */
    public function destroy(string $id)
    {
        // Check authorization (super admin or admin)
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage employee types.', 403);
        }

        $type = EmployeeType::find($id);

        if (!$type) {
            return $this->errorResponse('Employee type not found', 404);
        }

        // Check if any employees are using this type
        $employeesCount = $type->employees()->count();
        if ($employeesCount > 0) {
            return $this->errorResponse(
                "Tidak dapat menghapus tipe ini karena masih digunakan oleh {$employeesCount} pegawai. Hapus atau ubah tipe pegawai terlebih dahulu.",
                400
            );
        }

        try {
            $type->delete();

            Log::info('Employee type deleted', [
                'type_id' => $id,
                'code' => $type->code,
                'deleted_by' => auth()->id(),
            ]);

            return $this->apiResponse(null, 'Employee type deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete employee type', [
                'type_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to delete employee type: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get available features list
     */
    public function features()
    {
        return $this->apiResponse(EmployeeType::availableFeatures(), 'Available features retrieved');
    }

    /**
     * Reorder employee types
     */
    public function reorder(Request $request)
    {
        // Check authorization (super admin or admin)
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage employee types.', 403);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|uuid|exists:employee_types,id',
            'order.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            foreach ($validated['order'] as $item) {
                EmployeeType::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            return $this->apiResponse(null, 'Employee types reordered successfully');
        } catch (\Exception $e) {
            Log::error('Failed to reorder employee types', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to reorder employee types', 500);
        }
    }
}
