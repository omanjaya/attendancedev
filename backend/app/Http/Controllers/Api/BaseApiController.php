<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Attendance Management System API",
 *     version="1.0.0",
 *     description="API for school attendance management system with face recognition, GPS verification, and comprehensive employee management.",
 *
 *     @OA\Contact(
 *         email="admin@attendance-system.com",
 *         name="API Support",
 *         url="https://attendance-system.com/support"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format (Bearer <token>)"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Laravel Sanctum token authentication"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication and authorization endpoints"
 * )
 * @OA\Tag(
 *     name="Employees",
 *     description="Employee management operations"
 * )
 * @OA\Tag(
 *     name="Attendance",
 *     description="Attendance tracking and management"
 * )
 * @OA\Tag(
 *     name="Leave",
 *     description="Leave request and approval management"
 * )
 * @OA\Tag(
 *     name="Schedules",
 *     description="Schedule and calendar management"
 * )
 * @OA\Tag(
 *     name="Payroll",
 *     description="Payroll calculation and management"
 * )
 * @OA\Tag(
 *     name="Reports",
 *     description="Analytics and reporting endpoints"
 * )
 * @OA\Tag(
 *     name="Security",
 *     description="Security and two-factor authentication"
 * )
 */
class BaseApiController extends Controller
{
    /**
     * Standard API response format.
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function apiResponse($data = null, string $message = 'Success', int $status = 200)
    {
        return response()->json(
            [
                'success' => $status < 400,
                'message' => $message,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ],
            $status,
        );
    }

    /**
     * Standard error response format.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message = 'Error', int $status = 400, array $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Validation error response.
     *
     * @param  \Illuminate\Support\MessageBag  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse($errors)
    {
        return $this->errorResponse('Validation failed', 422, $errors->toArray());
    }

    /**
     * Paginated response format.
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $paginator
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginatedResponse($paginator, string $message = 'Success')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }
}
