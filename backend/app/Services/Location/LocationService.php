<?php

namespace App\Services\Location;

use App\Models\Location;
use Illuminate\Support\Facades\DB;

class LocationService
{
    /**
     * Get all locations with employee count
     */
    public function getAllLocations(): array
    {
        return Location::withCount('employees')
            ->orderBy('name')
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'radius_meters' => $location->radius_meters,
                    'is_active' => $location->is_active,
                    'require_face_recognition' => $location->require_face_recognition,
                    'employees_count' => $location->employees_count,
                    'created_at' => $location->created_at,
                    'updated_at' => $location->updated_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get locations for select dropdown (simplified)
     */
    public function getLocationsForSelect(): array
    {
        return Location::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address'])
            ->toArray();
    }

    /**
     * Get location statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Location::count(),
            'active' => Location::where('is_active', true)->count(),
            'inactive' => Location::where('is_active', false)->count(),
            'with_gps' => Location::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->count(),
            'with_face_recognition' => Location::where('require_face_recognition', true)->count(),
        ];
    }

    /**
     * Create a new location
     */
    public function createLocation(array $data): Location
    {
        return DB::transaction(function () use ($data) {
            return Location::create([
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'radius_meters' => $data['radius_meters'] ?? 100,
                'is_active' => $data['is_active'] ?? true,
                'require_face_recognition' => $data['require_face_recognition'] ?? false,
            ]);
        });
    }

    /**
     * Update location
     */
    public function updateLocation(Location $location, array $data): Location
    {
        return DB::transaction(function () use ($location, $data) {
            $updates = [];

            $fields = ['name', 'address', 'latitude', 'longitude', 'radius_meters', 'is_active', 'require_face_recognition'];

            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            $location->update($updates);

            return $location->fresh();
        });
    }

    /**
     * Delete location
     */
    public function deleteLocation(Location $location): bool
    {
        // Check if location has employees
        if ($location->employees()->count() > 0) {
            throw new \Exception('Cannot delete location with assigned employees');
        }

        return $location->delete();
    }

    /**
     * Toggle location status
     */
    public function toggleStatus(Location $location): Location
    {
        $location->update(['is_active' => !$location->is_active]);
        return $location->fresh();
    }

    /**
     * Get location by ID
     */
    public function getLocationById(string $id): ?Location
    {
        return Location::with('employees')->find($id);
    }
}
