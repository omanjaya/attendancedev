<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Throwable;

/**
 * Error Boundary Middleware
 *
 * Catches and handles exceptions in a consistent way across the application
 */
class ErrorBoundary
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * Handle the exception
     */
    protected function handleException(Request $request, Throwable $e): BaseResponse
    {
        // Log the exception
        $this->logException($request, $e);

        // Check if exception has custom render method
        if (method_exists($e, 'render')) {
            $response = $e->render($request);
            // Ensure JSON response for AJAX requests
            if ($this->expectsJson($request) && !$this->isJsonResponse($response)) {
                return $this->convertToJsonResponse($response, $e);
            }
            return $response;
        }

        // Handle different exception types
        return match (true) {
            $e instanceof \Illuminate\Validation\ValidationException => $this->handleValidationException($request, $e),
            $e instanceof \Illuminate\Auth\AuthenticationException => $this->handleAuthenticationException($request, $e),
            $e instanceof \Illuminate\Auth\Access\AuthorizationException => $this->handleAuthorizationException($request, $e),
            $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => $this->handleModelNotFoundException($request, $e),
            $e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException => $this->handleThrottleException($request, $e),
            $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => $this->handleNotFoundHttpException($request, $e),
            $e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException => $this->handleMethodNotAllowedException($request, $e),
            default => $this->handleGenericException($request, $e),
        };
    }

    /**
     * Log the exception
     */
    protected function logException(Request $request, Throwable $e): void
    {
        $context = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        // Add request data for non-GET requests
        if (! $request->isMethod('GET')) {
            $context['request_data'] = $request->except(['password', 'password_confirmation']);
        }

        Log::error('Exception caught by ErrorBoundary: '.$e->getMessage(), $context);
    }

    /**
     * Check if request expects JSON response
     */
    protected function expectsJson(Request $request): bool
    {
        return $request->expectsJson() || 
               $request->wantsJson() || 
               $request->header('Accept') === 'application/json' ||
               $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Check if response is already JSON
     */
    protected function isJsonResponse($response): bool
    {
        return $response instanceof JsonResponse;
    }

    /**
     * Convert response to JSON format
     */
    protected function convertToJsonResponse($response, Throwable $e): JsonResponse
    {
        $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 500;
        
        return response()->json([
            'success' => false,
            'error_type' => 'conversion_error',
            'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }

    /**
     * Handle validation exceptions
     */
    protected function handleValidationException(Request $request, \Illuminate\Validation\ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_type' => 'validation_error',
            'message' => 'Validation failed',
            'errors' => $e->errors(),
            'timestamp' => now()->toISOString(),
        ], 422);
    }

    /**
     * Handle authentication exceptions
     */
    protected function handleAuthenticationException(Request $request, \Illuminate\Auth\AuthenticationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_type' => 'authentication_error',
            'message' => 'Authentication required',
            'timestamp' => now()->toISOString(),
        ], 401);
    }

    /**
     * Handle authorization exceptions
     */
    protected function handleAuthorizationException(Request $request, \Illuminate\Auth\Access\AuthorizationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_type' => 'authorization_error',
            'message' => 'Insufficient permissions',
            'timestamp' => now()->toISOString(),
        ], 403);
    }

    /**
     * Handle model not found exceptions
     */
    protected function handleModelNotFoundException(Request $request, \Illuminate\Database\Eloquent\ModelNotFoundException $e): JsonResponse
    {
        $modelName = class_basename($e->getModel());

        return response()->json([
            'success' => false,
            'error_type' => 'not_found',
            'message' => "{$modelName} not found",
            'timestamp' => now()->toISOString(),
        ], 404);
    }

    /**
     * Handle throttle exceptions
     */
    protected function handleThrottleException(Request $request, \Illuminate\Http\Exceptions\ThrottleRequestsException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_type' => 'rate_limit_exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
            'timestamp' => now()->toISOString(),
        ], 429);
    }

    /**
     * Handle not found HTTP exceptions
     */
    protected function handleNotFoundHttpException(Request $request, \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_type' => 'not_found',
            'message' => 'Resource not found',
            'timestamp' => now()->toISOString(),
        ], 404);
    }

    /**
     * Handle method not allowed exceptions
     */
    protected function handleMethodNotAllowedException(Request $request, \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_type' => 'method_not_allowed',
            'message' => 'Method not allowed',
            'allowed_methods' => $e->getHeaders()['Allow'] ?? null,
            'timestamp' => now()->toISOString(),
        ], 405);
    }

    /**
     * Handle generic exceptions
     */
    protected function handleGenericException(Request $request, Throwable $e): JsonResponse
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        $response = [
            'success' => false,
            'error_type' => 'internal_error',
            'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            'timestamp' => now()->toISOString(),
        ];

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        return response()->json($response, $statusCode);
    }
}
