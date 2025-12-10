<?php

namespace App\Services\Location;

use App\Models\Location;

class LocationVerificationService
{
    /**
     * Verify if coordinates are within allowed location radius
     */
    public function verifyLocation(float $latitude, float $longitude, ?string $locationId = null): array
    {
        // Find location (by ID or nearest)
        if ($locationId) {
            $location = Location::find($locationId);
        } else {
            $location = $this->findNearestLocation($latitude, $longitude);
        }

        // No GPS-enabled location found
        if (!$location || !$location->latitude || !$location->longitude) {
            return [
                'verified' => false,
                'message' => 'No GPS-enabled location found.',
                'location' => null,
                'distance' => null,
                'allowed_radius' => null,
            ];
        }

        // Calculate distance
        $distance = $this->calculateDistance(
            $latitude,
            $longitude,
            $location->latitude,
            $location->longitude
        );

        $verified = $distance <= $location->radius_meters;

        return [
            'verified' => $verified,
            'distance' => round($distance, 2),
            'allowed_radius' => $location->radius_meters,
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'radius_meters' => $location->radius_meters,
                'require_face_recognition' => $location->require_face_recognition,
            ],
            'message' => $verified
                ? 'Location verified successfully.'
                : "You are " . round($distance, 2) . "m away. Must be within {$location->radius_meters}m.",
        ];
    }

    /**
     * Find the nearest active location to given coordinates
     */
    public function findNearestLocation(float $latitude, float $longitude): ?Location
    {
        return Location::where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw(
                '*,
                (6371000 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance',
                [$latitude, $longitude, $latitude]
            )
            ->orderBy('distance')
            ->first();
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     *
     * @return float Distance in meters
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
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

    /**
     * Check if coordinates are within multiple locations
     */
    public function findAllLocationsInRange(float $latitude, float $longitude, int $maxDistance = 1000): array
    {
        $locations = Location::where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $inRange = [];

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $location->latitude,
                $location->longitude
            );

            if ($distance <= $maxDistance) {
                $inRange[] = [
                    'location' => $location,
                    'distance' => round($distance, 2),
                    'within_radius' => $distance <= $location->radius_meters,
                ];
            }
        }

        // Sort by distance
        usort($inRange, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return $inRange;
    }
}
