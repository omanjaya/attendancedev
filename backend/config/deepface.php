<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DeepFace Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for DeepFace face recognition service cluster
    |
    */

    'enabled' => env('DEEPFACE_ENABLED', true),

    'base_url' => env('DEEPFACE_BASE_URL', 'http://127.0.0.1'),

    /**
     * DeepFace service ports for load balancing
     * Add more ports here to scale horizontally
     */
    'ports' => array_filter(array_map('intval', explode(',', env('DEEPFACE_PORTS', '8001,8002,8003,8004,8005')))),

    /**
     * Timeout for requests to DeepFace service (seconds)
     */
    'timeout' => env('DEEPFACE_TIMEOUT', 30),

    /**
     * Health check interval (seconds)
     */
    'health_check_interval' => env('DEEPFACE_HEALTH_CHECK_INTERVAL', 30),

    /**
     * Max retries before giving up
     */
    'max_retries' => env('DEEPFACE_MAX_RETRIES', 2),

    /**
     * Face verification threshold (0-1)
     * Lower = stricter matching
     */
    'threshold' => env('DEEPFACE_THRESHOLD', 0.68),

    /**
     * Enable liveness detection (anti-spoofing)
     */
    'liveness_enabled' => env('DEEPFACE_LIVENESS_ENABLED', true),

    /**
     * Cache face embeddings in Redis
     */
    'cache_embeddings' => env('DEEPFACE_CACHE_EMBEDDINGS', true),

    /**
     * Embedding cache TTL (seconds)
     */
    'cache_ttl' => env('DEEPFACE_CACHE_TTL', 3600),

];
