<?php

namespace App\Http\Controllers\Api;

use App\Models\Period;
use Illuminate\Http\Request;

class PeriodApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Period::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $periods = $query->orderBy('order_index')->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($periods, 'Periods retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_break' => 'boolean',
            'order_index' => 'integer',
        ]);

        $period = Period::create($validated);

        return $this->apiResponse($period, 'Period created successfully', 201);
    }

    public function show($id)
    {
        $period = Period::findOrFail($id);
        return $this->apiResponse($period, 'Period retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $period = Period::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'is_break' => 'boolean',
            'order_index' => 'integer',
        ]);

        $period->update($validated);

        return $this->apiResponse($period, 'Period updated successfully');
    }

    public function destroy($id)
    {
        $period = Period::findOrFail($id);
        $period->delete();

        return $this->apiResponse(null, 'Period deleted successfully');
    }
}
