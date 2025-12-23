<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Determine status based on is_active
        $status = $this->is_active ? 'active' : 'inactive';

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'user_id' => $this->user_id,
            'name' => $this->full_name,
            'email' => $this->user?->email ?? '',
            'phone' => $this->phone,
            // Get from relation first, fallback to metadata, then default
            'position' => $this->positionRelation?->name ?? $this->metadata['position'] ?? null,
            'department' => $this->departmentRelation?->name ?? $this->metadata['department'] ?? null,
            // Also include IDs for reference
            'position_id' => $this->position_id,
            'department_id' => $this->department_id,
            'status' => $status,
            'join_date' => $this->hire_date?->format('Y-m-d'),
            'avatar' => $this->photo_path,
            'address' => $this->metadata['address'] ?? null,
            'birth_date' => $this->metadata['date_of_birth'] ?? null,
            'gender' => $this->metadata['gender'] ?? null,
            'face_registered' => $this->hasFaceData(),
            'location_id' => $this->location_id,
            'location_name' => $this->location?->name,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Check if employee has face data
     */
    private function hasFaceData(): bool
    {
        // Check if employee has face data in metadata
        // Face data is stored under 'face_recognition' key with 'registered_at' timestamp
        return !empty($this->metadata['face_recognition']['registered_at']) ||
               !empty($this->metadata['face_encodings']) ||
               !empty($this->metadata['face_descriptor']);
    }
}
