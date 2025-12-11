<?php

namespace App\Http\Controllers\Api;

use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = AcademicYear::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $academicYears = $query->orderBy('start_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($academicYears, 'Academic years retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'semester' => 'required|in:odd,even',
            'is_active' => 'boolean',
        ]);

        if ($validated['is_active'] ?? false) {
            // Deactivate other academic years if this one is active
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        $academicYear = AcademicYear::create($validated);

        return $this->apiResponse($academicYear, 'Academic year created successfully', 201);
    }

    public function show($id)
    {
        $academicYear = AcademicYear::findOrFail($id);
        return $this->apiResponse($academicYear, 'Academic year retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $academicYear = AcademicYear::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'semester' => 'sometimes|in:odd,even',
            'is_active' => 'boolean',
        ]);

        if ($validated['is_active'] ?? false) {
            AcademicYear::where('id', '!=', $id)->where('is_active', true)->update(['is_active' => false]);
        }

        $academicYear->update($validated);

        return $this->apiResponse($academicYear, 'Academic year updated successfully');
    }

    public function destroy($id)
    {
        $academicYear = AcademicYear::findOrFail($id);
        $academicYear->delete();

        return $this->apiResponse(null, 'Academic year deleted successfully');
    }
}
