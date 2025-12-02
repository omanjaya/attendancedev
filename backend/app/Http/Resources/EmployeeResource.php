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
            // Get from metadata
            'position' => $this->metadata['position'] ?? 'Belum Diatur',
            'department' => $this->metadata['department'] ?? 'Belum Diatur',
            'status' => $status,
            'join_date' => $this->hire_date?->format('Y-m-d'),
            'avatar' => $this->photo_path,
            'address' => $this->metadata['address'] ?? null,
            'birth_date' => $this->metadata['date_of_birth'] ?? null,
            'gender' => $this->metadata['gender'] ?? null,
            'face_registered' => $this->hasFaceData(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Check if employee has face data
     */
    private function hasFaceData(): bool
    {
        // Check if employee has face encodings in metadata or database
        return !empty($this->metadata['face_encodings']) ||
               !empty($this->metadata['face_descriptor']);
    }
}
