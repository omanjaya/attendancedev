<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Custom exception for business logic errors
 */
class BusinessLogicException extends Exception
{
    protected string $errorCode;

    protected array $context;

    public function __construct(
        string $message = '',
        string $errorCode = 'business_logic_error',
        array $context = [],
        int $code = 400,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
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
            'error_type' => 'business_logic_error',
            'error_code' => $this->errorCode,
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
     * Common business logic exceptions
     */
    public static function unauthorized(string $action): self
    {
        return new self(
            "You are not authorized to {$action}",
            'unauthorized_action',
            ['action' => $action],
            403
        );
    }

    public static function forbidden(string $resource): self
    {
        return new self(
            "Access to {$resource} is forbidden",
            'forbidden_resource',
            ['resource' => $resource],
            403
        );
    }

    public static function notFound(string $resource, string $identifier): self
    {
        return new self(
            "{$resource} not found",
            'resource_not_found',
            ['resource' => $resource, 'identifier' => $identifier],
            404
        );
    }

    public static function conflict(string $resource, string $reason): self
    {
        return new self(
            "Conflict with {$resource}: {$reason}",
            'resource_conflict',
            ['resource' => $resource, 'reason' => $reason],
            409
        );
    }

    public static function rateLimit(string $action, int $limit): self
    {
        return new self(
            "Rate limit exceeded for {$action}. Maximum {$limit} attempts allowed.",
            'rate_limit_exceeded',
            ['action' => $action, 'limit' => $limit],
            429
        );
    }

    public static function maintenanceMode(): self
    {
        return new self(
            'System is currently under maintenance. Please try again later.',
            'maintenance_mode',
            [],
            503
        );
    }

    public static function invalidOperation(string $operation, string $reason): self
    {
        return new self(
            "Invalid operation '{$operation}': {$reason}",
            'invalid_operation',
            ['operation' => $operation, 'reason' => $reason]
        );
    }

    public static function insufficientPermissions(string $permission): self
    {
        return new self(
            "Insufficient permissions. Required: {$permission}",
            'insufficient_permissions',
            ['required_permission' => $permission],
            403
        );
    }

    public static function dataIntegrityViolation(string $details): self
    {
        return new self(
            "Data integrity violation: {$details}",
            'data_integrity_violation',
            ['details' => $details],
            422
        );
    }

    public static function externalServiceError(string $service, string $error): self
    {
        return new self(
            "External service error from {$service}: {$error}",
            'external_service_error',
            ['service' => $service, 'error' => $error],
            502
        );
    }
}
