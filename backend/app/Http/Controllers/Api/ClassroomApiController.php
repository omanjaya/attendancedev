<?php

namespace App\Http\Controllers\Api;

use App\Models\AcademicClass;
use Illuminate\Http\Request;

class ClassroomApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = AcademicClass::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        $classrooms = $query->orderBy('grade_level')
                           ->orderBy('major')
                           ->orderBy('class_number')
                           ->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($classrooms, 'Classrooms retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'grade_level' => 'required|string|max:10',
            'major' => 'nullable|string|max:50',
            'class_number' => 'required|string|max:10',
            'capacity' => 'integer|min:1|max:200',
            'room' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['name'])) {
            $parts = [$validated['grade_level']];
            if (!empty($validated['major'])) {
                $parts[] = $validated['major'];
            }
            $parts[] = $validated['class_number'];
            $validated['name'] = implode('-', $parts);
        }

        $classroom = AcademicClass::create($validated);

        return $this->apiResponse($classroom, 'Classroom created successfully', 201);
    }

    public function show($id)
    {
        $classroom = AcademicClass::findOrFail($id);
        return $this->apiResponse($classroom, 'Classroom retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $classroom = AcademicClass::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'grade_level' => 'sometimes|string|max:10',
            'major' => 'nullable|string|max:50',
            'class_number' => 'sometimes|string|max:10',
            'capacity' => 'integer|min:1|max:200',
            'room' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $classroom->fill($validated);
        
        // Regenerate name if components changed and name wasn't explicitly provided
        if ((isset($validated['grade_level']) || isset($validated['major']) || isset($validated['class_number'])) && empty($request->name)) {
             $parts = [$classroom->grade_level];
             if ($classroom->major) {
                 $parts[] = $classroom->major;
             }
             $parts[] = $classroom->class_number;
             $classroom->name = implode('-', $parts);
        }

        $classroom->save();

        return $this->apiResponse($classroom, 'Classroom updated successfully');
    }

    public function destroy($id)
    {
        $classroom = AcademicClass::findOrFail($id);
        $classroom->delete();

        return $this->apiResponse(null, 'Classroom deleted successfully');
    }
}
