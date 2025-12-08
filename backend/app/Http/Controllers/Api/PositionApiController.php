<?php

namespace App\Http\Controllers\Api;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PositionApiController extends BaseApiController
{
    /**
     * Get all positions
     */
    public function index(Request $request)
    {
        $query = Position::query();

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
            $positions = $query->ordered()->get();
            return $this->apiResponse($positions, 'Positions retrieved');
        }

        $positions = $query->ordered()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($positions, 'Positions retrieved');
    }

    /**
     * Get single position
     */
    public function show(string $id)
    {
        $position = Position::find($id);

        if (!$position) {
            return $this->errorResponse('Position not found', 404);
        }

        $position->employees_count = $position->employees()->count();

        return $this->apiResponse($position, 'Position retrieved');
    }

    /**
     * Create new position
     */
    public function store(Request $request)
    {
        // Check authorization
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage positions.', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:positions,code',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            $position = Position::create($validated);

            Log::info('Position created', [
                'position_id' => $position->id,
                'code' => $position->code,
                'created_by' => auth()->id(),
            ]);

            return $this->apiResponse($position, 'Position created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create position', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return $this->errorResponse('Failed to create position: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update position
     */
    public function update(Request $request, string $id)
    {
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage positions.', 403);
        }

        $position = Position::find($id);

        if (!$position) {
            return $this->errorResponse('Position not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:50|unique:positions,code,' . $id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $position->update($validated);

            Log::info('Position updated', [
                'position_id' => $position->id,
                'code' => $position->code,
                'updated_by' => auth()->id(),
            ]);

            return $this->apiResponse($position->fresh(), 'Position updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update position', [
                'position_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to update position: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete position
     */
    public function destroy(string $id)
    {
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage positions.', 403);
        }

        $position = Position::find($id);

        if (!$position) {
            return $this->errorResponse('Position not found', 404);
        }

        // Check if any employees are using this position
        $employeesCount = $position->employees()->count();
        if ($employeesCount > 0) {
            return $this->errorResponse(
                "Tidak dapat menghapus posisi ini karena masih digunakan oleh {$employeesCount} pegawai.",
                400
            );
        }

        try {
            $position->delete();

            Log::info('Position deleted', [
                'position_id' => $id,
                'code' => $position->code,
                'deleted_by' => auth()->id(),
            ]);

            return $this->apiResponse(null, 'Position deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete position', [
                'position_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to delete position: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reorder positions
     */
    public function reorder(Request $request)
    {
        if (!auth()->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin'])) {
            return $this->errorResponse('Unauthorized. Only admins can manage positions.', 403);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|uuid|exists:positions,id',
            'order.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            foreach ($validated['order'] as $item) {
                Position::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            return $this->apiResponse(null, 'Positions reordered successfully');
        } catch (\Exception $e) {
            Log::error('Failed to reorder positions', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to reorder positions', 500);
        }
    }
}
