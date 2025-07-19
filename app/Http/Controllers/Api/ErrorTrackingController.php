<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ErrorTrackingController extends Controller
{
    /**
     * Store a client-side error
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'error' => 'required|array',
                'error.name' => 'required|string|max:255',
                'error.message' => 'required|string|max:1000',
                'error.stack' => 'nullable|string|max:5000',
                'context' => 'nullable|array',
                'context.component' => 'nullable|string|max:255',
                'context.action' => 'nullable|string|max:255',
                'context.user' => 'nullable|array',
                'context.metadata' => 'nullable|array',
                'context.timestamp' => 'nullable|string',
                'context.userAgent' => 'nullable|string|max:500',
                'context.url' => 'nullable|string|max:500',
            ]);

            // Enrich error data
            $errorData = [
                'id' => uniqid('err_', true),
                'timestamp' => Carbon::now()->toISOString(),
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->header('referer'),
                'environment' => app()->environment(),
                'error' => $validated['error'],
                'context' => $validated['context'] ?? [],
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'memory_usage' => memory_get_usage(true),
                    'request_time' => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
                ],
            ];

            // Determine error severity
            $severity = $this->determineSeverity($errorData);
            $errorData['severity'] = $severity;

            // Log the error
            $this->logError($errorData, $severity);

            // Store in database if it's a critical error
            if ($this->shouldStoreInDatabase($errorData)) {
                $this->storeErrorInDatabase($errorData);
            }

            // Send notification for critical errors
            if ($severity === 'critical') {
                $this->notifyAdminsOfCriticalError($errorData);
            }

            return response()->json([
                'success' => true,
                'error_id' => $errorData['id'],
                'message' => 'Error logged successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Invalid error data',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            // Log the error but don't expose internal details
            Log::error('Error tracking controller failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to log error',
                ],
                500,
            );
        }
    }

    /**
     * Get error statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('view_audit_logs');

        try {
            $timeframe = $request->get('timeframe', '24h');
            $component = $request->get('component');

            $stats = $this->getErrorStatistics($timeframe, $component);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Error statistics retrieval failed', [
                'exception' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve error statistics',
                ],
                500,
            );
        }
    }

    /**
     * Get recent errors
     */
    public function recent(Request $request): JsonResponse
    {
        $this->authorize('view_audit_logs');

        try {
            $limit = min($request->get('limit', 50), 100);
            $severity = $request->get('severity');
            $component = $request->get('component');

            $errors = $this->getRecentErrors($limit, $severity, $component);

            return response()->json([
                'success' => true,
                'data' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error('Recent errors retrieval failed', [
                'exception' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve recent errors',
                ],
                500,
            );
        }
    }

    /**
     * Determine error severity based on error data
     */
    private function determineSeverity(array $errorData): string
    {
        $error = $errorData['error'];
        $context = $errorData['context'] ?? [];

        // Critical errors
        if (
            str_contains($error['message'], 'ChunkLoadError') ||
            str_contains($error['message'], 'TypeError: Cannot read') ||
            str_contains($error['message'], 'ReferenceError') ||
            ($context['action'] ?? '') === 'vue_error' ||
            ($context['action'] ?? '') === 'unhandledrejection'
        ) {
            return 'critical';
        }

        // Warning level errors
        if (
            str_contains($error['message'], 'Network Error') ||
            str_contains($error['message'], 'Failed to fetch') ||
            str_contains($error['name'], 'AbortError') ||
            ($context['component'] ?? '') === 'FaceRecognition'
        ) {
            return 'warning';
        }

        // Default to info level
        return 'info';
    }

    /**
     * Log error with appropriate level
     */
    private function logError(array $errorData, string $severity): void
    {
        $logContext = [
            'error_id' => $errorData['id'],
            'user_id' => $errorData['user_id'],
            'component' => $errorData['context']['component'] ?? 'unknown',
            'action' => $errorData['context']['action'] ?? 'unknown',
            'error_name' => $errorData['error']['name'],
            'error_message' => $errorData['error']['message'],
            'url' => $errorData['url'],
            'user_agent' => $errorData['user_agent'],
        ];

        switch ($severity) {
            case 'critical':
                Log::error('Critical client-side error', $logContext);
                break;
            case 'warning':
                Log::warning('Client-side warning', $logContext);
                break;
            default:
                Log::info('Client-side error', $logContext);
                break;
        }
    }

    /**
     * Determine if error should be stored in database
     */
    private function shouldStoreInDatabase(array $errorData): bool
    {
        // Store critical errors and errors from authenticated users
        return $errorData['severity'] === 'critical' || $errorData['user_id'] !== null;
    }

    /**
     * Store error in database
     */
    private function storeErrorInDatabase(array $errorData): void
    {
        try {
            // This would typically use an ErrorLog model
            // For now, we'll store in a JSON file for simplicity
            $filename = 'errors/'.date('Y-m-d').'.json';
            $existingData = [];

            if (Storage::exists($filename)) {
                $content = Storage::get($filename);
                $existingData = json_decode($content, true) ?? [];
            }

            $existingData[] = $errorData;

            // Keep only the last 1000 errors per day
            if (count($existingData) > 1000) {
                $existingData = array_slice($existingData, -1000);
            }

            Storage::put($filename, json_encode($existingData, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::error('Failed to store error in database', [
                'exception' => $e->getMessage(),
                'error_id' => $errorData['id'],
            ]);
        }
    }

    /**
     * Notify admins of critical errors
     */
    private function notifyAdminsOfCriticalError(array $errorData): void
    {
        try {
            // This would typically send notifications to admins
            // For now, we'll just log it
            Log::channel('slack')->critical('Critical frontend error detected', [
                'error_id' => $errorData['id'],
                'component' => $errorData['context']['component'] ?? 'unknown',
                'message' => $errorData['error']['message'],
                'user_id' => $errorData['user_id'],
                'url' => $errorData['url'],
                'timestamp' => $errorData['timestamp'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to notify admins of critical error', [
                'exception' => $e->getMessage(),
                'error_id' => $errorData['id'],
            ]);
        }
    }

    /**
     * Get error statistics
     */
    private function getErrorStatistics(string $timeframe, ?string $component): array
    {
        // This would typically query a database
        // For now, we'll return mock data
        return [
            'total_errors' => 42,
            'critical_errors' => 3,
            'warning_errors' => 15,
            'info_errors' => 24,
            'top_components' => [
                ['component' => 'FaceRecognition', 'count' => 12],
                ['component' => 'ScheduleModal', 'count' => 8],
                ['component' => 'AttendanceForm', 'count' => 6],
            ],
            'error_trend' => [
                ['hour' => '00:00', 'count' => 2],
                ['hour' => '01:00', 'count' => 1],
                ['hour' => '02:00', 'count' => 3],
                // ... more hourly data
            ],
            'timeframe' => $timeframe,
            'component_filter' => $component,
        ];
    }

    /**
     * Get recent errors
     */
    private function getRecentErrors(int $limit, ?string $severity, ?string $component): array
    {
        try {
            $errors = [];
            $today = date('Y-m-d');
            $filename = "errors/{$today}.json";

            if (Storage::exists($filename)) {
                $content = Storage::get($filename);
                $allErrors = json_decode($content, true) ?? [];

                // Filter by severity and component if specified
                $filteredErrors = array_filter($allErrors, function ($error) use ($severity, $component) {
                    if ($severity && ($error['severity'] ?? '') !== $severity) {
                        return false;
                    }
                    if ($component && ($error['context']['component'] ?? '') !== $component) {
                        return false;
                    }

                    return true;
                });

                // Get the most recent errors
                $errors = array_slice(array_reverse($filteredErrors), 0, $limit);
            }

            return $errors;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve recent errors', [
                'exception' => $e->getMessage(),
                'limit' => $limit,
                'severity' => $severity,
                'component' => $component,
            ]);

            return [];
        }
    }
}
