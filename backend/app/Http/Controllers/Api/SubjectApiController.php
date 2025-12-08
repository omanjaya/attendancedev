<?php

namespace App\Http\Controllers\Api;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Subject::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
        }

        $subjects = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($subjects, 'Subjects retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $subject = Subject::create($validated);

        return $this->apiResponse($subject, 'Subject created successfully', 201);
    }

    public function show($id)
    {
        $subject = Subject::findOrFail($id);
        return $this->apiResponse($subject, 'Subject retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:subjects,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $subject->update($validated);

        return $this->apiResponse($subject, 'Subject updated successfully');
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return $this->apiResponse(null, 'Subject deleted successfully');
    }
}
