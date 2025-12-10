<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Services\Location\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class LocationController extends Controller
{
    public function __construct(
        private LocationService $locationService
    ) {}

    /**
     * Display a listing of locations (Blade view)
     */
    public function index()
    {
        return view('pages.management.locations.index');
    }

    /**
     * Get locations data for DataTables
     */
    public function data()
    {
        $locations = Location::withCount('employees')->select('locations.*');

        return DataTables::of($locations)
            ->addColumn('employee_count', function ($location) {
                return $location->employees_count;
            })
            ->addColumn('coordinates', function ($location) {
                if ($location->latitude && $location->longitude) {
                    return number_format($location->latitude, 6) .
                        ', ' .
                        number_format($location->longitude, 6);
                }
                return '<span class="text-muted">Not set</span>';
            })
            ->addColumn('status', function ($location) {
                return $location->is_active
                    ? '<span class="badge bg-green">Active</span>'
                    : '<span class="badge bg-red">Inactive</span>';
            })
            ->addColumn('verification_methods', function ($location) {
                $methods = [];
                if ($location->latitude && $location->longitude) {
                    $methods[] = '<span class="badge bg-blue-lt">GPS</span>';
                }
                if ($location->require_face_recognition) {
                    $methods[] = '<span class="badge bg-purple-lt">Face Recognition</span>';
                }
                return implode(' ', $methods) ?: '<span class="text-muted">None</span>';
            })
            ->addColumn('actions', function ($location) {
                return '
                    <div class="btn-group" role="group">
                        <a href="' . route('locations.show', $location->id) . '" class="btn btn-sm btn-info">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="' . route('locations.edit', $location->id) . '" class="btn btn-sm btn-warning">
                            <i class="ti ti-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-danger delete-location" data-id="' . $location->id . '">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['status', 'coordinates', 'verification_methods', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new location
     */
    public function create()
    {
        return view('pages.management.locations.create');
    }

    /**
     * Store a newly created location
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:500',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'radius_meters' => 'nullable|integer|min:1|max:10000',
                'is_active' => 'boolean',
                'require_face_recognition' => 'boolean',
            ]);

            $this->locationService->createLocation($validated);

            return redirect()
                ->route('locations.index')
                ->with('success', 'Location created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create location', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Failed to create location: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified location
     */
    public function show(Location $location)
    {
        $location->load('employees');
        return view('pages.management.locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified location
     */
    public function edit(Location $location)
    {
        return view('pages.management.locations.edit', compact('location'));
    }

    /**
     * Update the specified location
     */
    public function update(Request $request, Location $location)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'address' => 'nullable|string|max:500',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'radius_meters' => 'nullable|integer|min:1|max:10000',
                'is_active' => 'boolean',
                'require_face_recognition' => 'boolean',
            ]);

            $this->locationService->updateLocation($location, $validated);

            return redirect()
                ->route('locations.index')
                ->with('success', 'Location updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update location', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Failed to update location: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified location
     */
    public function destroy(Location $location)
    {
        try {
            $this->locationService->deleteLocation($location);

            return redirect()
                ->route('locations.index')
                ->with('success', 'Location deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete location', ['error' => $e->getMessage()]);
            return back()->with('error', $e->getMessage());
        }
    }
}
