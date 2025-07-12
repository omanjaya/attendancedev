<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.locations.index');
    }

    /**
     * Get locations data for DataTables.
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
                    return number_format($location->latitude, 6) . ', ' . number_format($location->longitude, 6);
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
                if ($location->wifi_ssid) {
                    $methods[] = '<span class="badge bg-green-lt">WiFi</span>';
                }
                return count($methods) > 0 ? implode(' ', $methods) : '<span class="text-muted">None</span>';
            })
            ->addColumn('actions', function ($location) {
                return '
                    <div class="btn-list">
                        <a href="' . route('locations.show', $location) . '" class="btn btn-sm btn-info">
                            View
                        </a>
                        <a href="' . route('locations.edit', $location) . '" class="btn btn-sm btn-primary">
                            Edit
                        </a>
                        <button class="btn btn-sm btn-danger delete-location" data-id="' . $location->id . '">
                            Delete
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['coordinates', 'status', 'verification_methods', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.locations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:locations,name',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:1000',
            'wifi_ssid' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        try {
            $location = Location::create([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'radius_meters' => $validated['radius_meters'],
                'wifi_ssid' => $validated['wifi_ssid'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return redirect()->route('locations.index')
                ->with('success', 'Location created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create location: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location)
    {
        $location->load('employees.user');
        return view('pages.locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location)
    {
        return view('pages.locations.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:locations,name,' . $location->id,
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:1000',
            'wifi_ssid' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        try {
            $location->update([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'radius_meters' => $validated['radius_meters'],
                'wifi_ssid' => $validated['wifi_ssid'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return redirect()->route('locations.index')
                ->with('success', 'Location updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update location: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        try {
            // Check if location has employees
            if ($location->employees()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete location with assigned employees. Please reassign employees first.'
                ], 400);
            }

            $location->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get locations for select dropdown.
     */
    public function getLocationsForSelect()
    {
        $locations = Location::where('is_active', true)
            ->select('id', 'name', 'address')
            ->orderBy('name')
            ->get();

        return response()->json($locations);
    }

    /**
     * Verify if coordinates are within location radius.
     */
    public function verifyLocation(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_id' => 'nullable|exists:locations,id'
        ]);

        if ($validated['location_id']) {
            $location = Location::find($validated['location_id']);
        } else {
            // Find the nearest location
            $location = $this->findNearestLocation($validated['latitude'], $validated['longitude']);
        }

        if (!$location || !$location->latitude || !$location->longitude) {
            return response()->json([
                'verified' => false,
                'message' => 'No GPS-enabled location found.',
                'location' => null
            ]);
        }

        $distance = $this->calculateDistance(
            $validated['latitude'], 
            $validated['longitude'],
            $location->latitude, 
            $location->longitude
        );

        $verified = $distance <= $location->radius_meters;

        return response()->json([
            'verified' => $verified,
            'distance' => round($distance, 2),
            'allowed_radius' => $location->radius_meters,
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address
            ],
            'message' => $verified 
                ? 'Location verified successfully.' 
                : "You are {$distance}m away. Must be within {$location->radius_meters}m."
        ]);
    }

    /**
     * Find the nearest location to given coordinates.
     */
    private function findNearestLocation($latitude, $longitude)
    {
        return Location::where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("
                *, 
                (6371000 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->orderBy('distance')
            ->first();
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}