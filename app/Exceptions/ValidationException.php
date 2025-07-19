<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Custom validation exception with enhanced error handling
 */
class ValidationException extends Exception
{
    protected array $errors;

    protected string $field;

    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        string $field = '',
        int $code = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->field = $field;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the field that failed validation
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Render the exception into an HTTP response
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'error_type' => 'validation_error',
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'timestamp' => now()->toISOString(),
        ];

        if (! empty($this->field)) {
            $response['field'] = $this->field;
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
            ];
        }

        return response()->json($response, $this->getCode());
    }

    /**
     * Common validation exceptions
     */
    public static function required(string $field): self
    {
        return new self(
            "The {$field} field is required.",
            [$field => ["The {$field} field is required."]],
            $field
        );
    }

    public static function invalid(string $field, string $value): self
    {
        return new self(
            "The {$field} field is invalid.",
            [$field => ["The {$field} field is invalid."]],
            $field
        );
    }

    public static function unique(string $field, string $value): self
    {
        return new self(
            "The {$field} has already been taken.",
            [$field => ["The {$field} has already been taken."]],
            $field
        );
    }

    public static function min(string $field, int $min): self
    {
        return new self(
            "The {$field} must be at least {$min} characters.",
            [$field => ["The {$field} must be at least {$min} characters."]],
            $field
        );
    }

    public static function max(string $field, int $max): self
    {
        return new self(
            "The {$field} may not be greater than {$max} characters.",
            [$field => ["The {$field} may not be greater than {$max} characters."]],
            $field
        );
    }

    public static function email(string $field): self
    {
        return new self(
            "The {$field} must be a valid email address.",
            [$field => ["The {$field} must be a valid email address."]],
            $field
        );
    }

    public static function numeric(string $field): self
    {
        return new self(
            "The {$field} must be a number.",
            [$field => ["The {$field} must be a number."]],
            $field
        );
    }

    public static function date(string $field): self
    {
        return new self(
            "The {$field} must be a valid date.",
            [$field => ["The {$field} must be a valid date."]],
            $field
        );
    }

    public static function multiple(array $errors): self
    {
        $message = 'Multiple validation errors occurred.';

        return new self($message, $errors);
    }
}
