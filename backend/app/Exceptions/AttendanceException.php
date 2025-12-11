<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Custom exception for attendance-related errors
 */
class AttendanceException extends Exception
{
    protected string $errorType;

    protected array $context;

    public function __construct(
        string $message = '',
        string $errorType = 'attendance_error',
        array $context = [],
        int $code = 400,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorType = $errorType;
        $this->context = $context;
    }

    /**
     * Get the error type
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Get the error context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render the exception into an HTTP response
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'error_type' => $this->errorType,
            'message' => $this->getMessage(),
            'timestamp' => now()->toISOString(),
        ];

        if (! empty($this->context)) {
            $response['context'] = $this->context;
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => $this->getTraceAsString(),
            ];
        }

        return response()->json($response, $this->getCode());
    }

    /**
     * Common attendance exception types
     */
    public static function alreadyCheckedIn(string $employeeId, string $checkInTime): self
    {
        return new self(
            "Employee already checked in today at {$checkInTime}",
            'already_checked_in',
            ['employee_id' => $employeeId, 'check_in_time' => $checkInTime]
        );
    }

    public static function noCheckInRecord(string $employeeId): self
    {
        return new self(
            'No check-in record found for today. Please check in first.',
            'no_check_in_record',
            ['employee_id' => $employeeId]
        );
    }

    public static function alreadyCheckedOut(string $employeeId, string $checkOutTime): self
    {
        return new self(
            "Employee already checked out today at {$checkOutTime}",
            'already_checked_out',
            ['employee_id' => $employeeId, 'check_out_time' => $checkOutTime]
        );
    }

    public static function locationVerificationFailed(string $employeeId, array $location): self
    {
        return new self(
            'Location verification failed. Please check in from the designated location.',
            'location_verification_failed',
            ['employee_id' => $employeeId, 'location' => $location],
            403
        );
    }

    public static function faceRecognitionFailed(string $employeeId, float $confidence): self
    {
        return new self(
            'Face recognition failed. Please try again with better lighting.',
            'face_recognition_failed',
            ['employee_id' => $employeeId, 'confidence' => $confidence],
            400
        );
    }

    public static function employeeNotFound(string $employeeId): self
    {
        return new self(
            'Employee not found',
            'employee_not_found',
            ['employee_id' => $employeeId],
            404
        );
    }

    public static function employeeInactive(string $employeeId): self
    {
        return new self(
            'Employee is inactive and cannot perform attendance actions',
            'employee_inactive',
            ['employee_id' => $employeeId],
            403
        );
    }
}
