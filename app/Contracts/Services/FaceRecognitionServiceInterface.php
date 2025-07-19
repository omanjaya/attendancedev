<?php

namespace App\Contracts\Services;

use App\Models\Employee;
use Illuminate\Http\UploadedFile;

interface FaceRecognitionServiceInterface
{
    /**
     * Register a face for an employee
     */
    public function registerFace(
        Employee $employee,
        array $descriptor,
        ?UploadedFile $image = null,
        array $metadata = []
    ): array;

    /**
     * Verify a face against registered faces
     */
    public function verifyFace(
        array $descriptor,
        ?Employee $employee = null,
        float $threshold = 0.6
    ): array;

    /**
     * Update face data for an employee
     */
    public function updateFaceData(
        Employee $employee,
        array $descriptor,
        array $metadata = []
    ): bool;

    /**
     * Delete face data for an employee
     */
    public function deleteFaceData(Employee $employee): bool;

    /**
     * Get face data for an employee
     */
    public function getFaceData(Employee $employee): ?array;

    /**
     * Calculate similarity between two face descriptors
     */
    public function calculateSimilarity(array $descriptor1, array $descriptor2): float;

    /**
     * Validate face descriptor
     */
    public function validateDescriptor(array $descriptor): bool;

    /**
     * Check liveness of face
     */
    public function checkLiveness(array $faceData): bool;

    /**
     * Get all registered faces
     */
    public function getAllRegisteredFaces(array $filters = []): array;

    /**
     * Batch verify multiple faces
     */
    public function batchVerify(array $faces, float $threshold = 0.6): array;
}