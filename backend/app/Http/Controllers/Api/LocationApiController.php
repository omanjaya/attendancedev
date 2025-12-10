<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Services\Location\LocationService;
use App\Services\Location\LocationVerificationService;
use Illuminate\Http\Request;

class LocationApiController extends BaseApiController
{
    public function __construct(
        private LocationService $locationService,
        private LocationVerificationService $verificationService
    ) {}

    /**
     * Get all locations
     */
    public function index()
    {
        $locations = $this->locationService->getAllLocations();
        return $this->apiResponse($locations, 'Locations retrieved successfully');
    }

    /**
     * Get locations for select dropdown
     */
    public function forSelect()
    {
        $locations = $this->locationService->getLocationsForSelect();
        return $this->apiResponse($locations, 'Locations for select retrieved');
    }

    /**
     * Get location statistics
     */
    public function statistics()
    {
        $stats = $this->locationService->getStatistics();
        return $this->apiResponse($stats, 'Location statistics retrieved');
    }

    /**
     * Verify location based on GPS coordinates
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $result = $this->verificationService->verifyLocation(
            $validated['latitude'],
            $validated['longitude'],
            $validated['location_id'] ?? null
        );

        return response()->json($result);
    }

    /**
     * Find all locations in range
     */
    public function findInRange(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'max_distance' => 'nullable|integer|min:1|max:10000',
        ]);

        $locations = $this->verificationService->findAllLocationsInRange(
            $validated['latitude'],
            $validated['longitude'],
            $validated['max_distance'] ?? 1000
        );

        return $this->apiResponse($locations, 'Locations in range retrieved');
    }

    /**
     * Create a new location
     */
    public function store(StoreLocationRequest $request)
    {
        try {
            $location = $this->locationService->createLocation($request->validated());
            return $this->apiResponse($location, 'Location created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create location: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single location
     */
    public function show(string $id)
    {
        $location = $this->locationService->getLocationById($id);

        if (!$location) {
            return $this->errorResponse('Location not found', 404);
        }

        return $this->apiResponse($location, 'Location retrieved successfully');
    }

    /**
     * Update location
     */
    public function update(UpdateLocationRequest $request, string $id)
    {
        $location = $this->locationService->getLocationById($id);

        if (!$location) {
            return $this->errorResponse('Location not found', 404);
        }

        try {
            $location = $this->locationService->updateLocation($location, $request->validated());
            return $this->apiResponse($location, 'Location updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update location: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete location
     */
    public function destroy(string $id)
    {
        $location = $this->locationService->getLocationById($id);

        if (!$location) {
            return $this->errorResponse('Location not found', 404);
        }

        try {
            $this->locationService->deleteLocation($location);
            return $this->apiResponse(null, 'Location deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Toggle location status
     */
    public function toggleStatus(string $id)
    {
        $location = $this->locationService->getLocationById($id);

        if (!$location) {
            return $this->errorResponse('Location not found', 404);
        }

        try {
            $location = $this->locationService->toggleStatus($location);
            return $this->apiResponse($location, 'Location status updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to toggle status: ' . $e->getMessage(), 500);
        }
    }
}
