<?php

namespace App\Services\FaceRecognition;

use Illuminate\Http\JsonResponse;

class FaceRecognitionAuthorizationService
{
    /**
     * Check if user can manage employee face data
     */
    public function canManageEmployeeFaceData($user, int $employeeId): bool
    {
        $isOwnProfile = $user->employee && $user->employee->id == $employeeId;
        return $isOwnProfile || $user->can('manage_employees');
    }

    /**
     * Check if user can view employee face data
     */
    public function canViewEmployeeFaceData($user, int $employeeId): bool
    {
        $isOwnProfile = $user->employee && $user->employee->id == $employeeId;
        return $isOwnProfile || $user->can('view_employees');
    }

    /**
     * Get forbidden response for manage permission
     */
    public function getForbiddenManageResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Forbidden. You do not have the required permission.',
            'required_permission' => 'manage_employees'
        ], 403);
    }

    /**
     * Get forbidden response for view permission
     */
    public function getForbiddenViewResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Forbidden. You do not have the required permission.',
            'required_permission' => 'view_employees'
        ], 403);
    }
}
