<?php

namespace App\Http\Controllers\Api;

use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Classroom::with('academicYear');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $classrooms = $query->orderBy('grade_level')->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($classrooms, 'Classrooms retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer|min:1',
            'major' => 'nullable|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $classroom = Classroom::create($validated);

        return $this->apiResponse($classroom->load('academicYear'), 'Classroom created successfully', 201);
    }

    public function show($id)
    {
        $classroom = Classroom::with('academicYear')->findOrFail($id);
        return $this->apiResponse($classroom, 'Classroom retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'grade_level' => 'sometimes|integer|min:1',
            'major' => 'nullable|string|max:255',
            'academic_year_id' => 'sometimes|exists:academic_years,id',
        ]);

        $classroom->update($validated);

        return $this->apiResponse($classroom->load('academicYear'), 'Classroom updated successfully');
    }

    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);
        $classroom->delete();

        return $this->apiResponse(null, 'Classroom deleted successfully');
    }
}
