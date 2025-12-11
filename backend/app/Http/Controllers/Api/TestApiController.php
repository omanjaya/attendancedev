<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;

/**
 * Test API Controller for API Documentation Demo
 */
class TestApiController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/test",
     *     operationId="testApi",
     *     tags={"Test"},
     *     summary="Test API endpoint",
     *     description="Simple test endpoint to verify API is working",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful test response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API is working correctly"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="version", type="string", example="1.0.0"),
     *                 @OA\Property(property="environment", type="string", example="development"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function test()
    {
        return $this->apiResponse(
            [
                'version' => '1.0.0',
                'environment' => app()->environment(),
                'timestamp' => now()->toISOString(),
            ],
            'API is working correctly',
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/health",
     *     operationId="healthCheck",
     *     tags={"Test"},
     *     summary="Health check endpoint",
     *     description="Check API health status",
     *
     *     @OA\Response(
     *         response=200,
     *         description="API is healthy",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", example="healthy"),
     *             @OA\Property(property="database", type="string", example="connected"),
     *             @OA\Property(property="cache", type="string", example="working"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function health()
    {
        try {
            // Test database connection
            \DB::connection()->getPdo();
            $database = 'connected';
        } catch (\Exception $e) {
            $database = 'disconnected';
        }

        return response()->json([
            'status' => 'healthy',
            'database' => $database,
            'cache' => 'working',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
