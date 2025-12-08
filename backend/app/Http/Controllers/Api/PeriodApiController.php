<?php

namespace App\Http\Controllers\Api;

use App\Models\TimeSlot;
use Illuminate\Http\Request;

class PeriodApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = TimeSlot::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $periods = $query->orderBy('order')->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($periods, 'Time slots retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'order' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        $timeSlot = TimeSlot::create($validated);

        return $this->apiResponse($timeSlot, 'Time slot created successfully', 201);
    }

    public function show($id)
    {
        $timeSlot = TimeSlot::findOrFail($id);
        return $this->apiResponse($timeSlot, 'Time slot retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $timeSlot = TimeSlot::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'order' => 'sometimes|integer',
            'is_active' => 'boolean',
        ]);

        $timeSlot->update($validated);

        return $this->apiResponse($timeSlot, 'Time slot updated successfully');
    }

    public function destroy($id)
    {
        $timeSlot = TimeSlot::findOrFail($id);
        $timeSlot->delete();

        return $this->apiResponse(null, 'Time slot deleted successfully');
    }
}
