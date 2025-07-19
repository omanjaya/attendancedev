<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Error Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for client-side error tracking and monitoring.
    | This includes Sentry integration, performance monitoring, and user tracking.
    |
    */

    'enabled' => env('ERROR_TRACKING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Sentry Configuration
    |--------------------------------------------------------------------------
    |
    | Sentry DSN for error tracking. If not provided, errors will only be
    | logged locally and sent to the custom error endpoint.
    |
    */

    'sentry_dsn' => env('SENTRY_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Sample Rate
    |--------------------------------------------------------------------------
    |
    | Percentage of errors to capture. 1.0 means capture all errors,
    | 0.1 means capture 10% of errors. Useful for high-traffic applications.
    |
    */

    'sample_rate' => (float) env('ERROR_TRACKING_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable performance monitoring to track page load times, component
    | render times, and other performance metrics.
    |
    */

    'enable_performance_monitoring' => env('ERROR_TRACKING_PERFORMANCE', true),

    /*
    |--------------------------------------------------------------------------
    | User Tracking
    |--------------------------------------------------------------------------
    |
    | Enable user context tracking to associate errors with specific users.
    | This helps with debugging but may have privacy implications.
    |
    */

    'enable_user_tracking' => env('ERROR_TRACKING_USER_TRACKING', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for error reporting to prevent abuse.
    |
    */

    'rate_limits' => [
        'errors_per_minute' => (int) env('ERROR_TRACKING_RATE_LIMIT', 50),
        'errors_per_hour' => (int) env('ERROR_TRACKING_HOURLY_LIMIT', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for storing errors locally when Sentry is not available
    | or for additional backup.
    |
    */

    'storage' => [
        'disk' => env('ERROR_TRACKING_DISK', 'local'),
        'path' => env('ERROR_TRACKING_PATH', 'errors'),
        'max_files_per_day' => (int) env('ERROR_TRACKING_MAX_FILES', 10),
        'max_errors_per_file' => (int) env('ERROR_TRACKING_MAX_ERRORS', 1000),
        'retention_days' => (int) env('ERROR_TRACKING_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for notifying administrators of critical errors.
    |
    */

    'notifications' => [
        'enabled' => env('ERROR_TRACKING_NOTIFICATIONS', true),
        'channels' => ['slack', 'mail'],
        'critical_threshold' => (int) env('ERROR_TRACKING_CRITICAL_THRESHOLD', 5),
        'notification_cooldown' => (int) env('ERROR_TRACKING_COOLDOWN', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Filtering Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for filtering out noise and unwanted errors.
    |
    */

    'filters' => [
        // Error messages to ignore (case-insensitive)
        'ignored_messages' => [
            'Network Error',
            'Failed to fetch',
            'Load failed',
            'Script error.',
            'ResizeObserver loop limit exceeded',
            'Non-Error promise rejection captured',
            'ChunkLoadError',
            'Loading chunk',
            'Loading CSS chunk',
        ],

        // User agents to ignore (bot detection)
        'ignored_user_agents' => [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'headless',
            'phantom',
            'selenium',
        ],

        // URLs to ignore error reporting from
        'ignored_urls' => ['/health', '/ping', '/metrics'],

        // Error stack traces containing these strings will be ignored
        'ignored_stack_patterns' => [
            'chrome-extension://',
            'moz-extension://',
            'safari-extension://',
            'edge-extension://',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Overrides
    |--------------------------------------------------------------------------
    |
    | Override settings based on the application environment.
    |
    */

    'environments' => [
        'local' => [
            'enabled' => true,
            'sample_rate' => 1.0,
            'enable_performance_monitoring' => true,
            'enable_user_tracking' => false,
        ],

        'testing' => [
            'enabled' => false,
            'sample_rate' => 0.0,
            'enable_performance_monitoring' => false,
            'enable_user_tracking' => false,
        ],

        'staging' => [
            'enabled' => true,
            'sample_rate' => 0.5,
            'enable_performance_monitoring' => true,
            'enable_user_tracking' => true,
        ],

        'production' => [
            'enabled' => true,
            'sample_rate' => 0.1,
            'enable_performance_monitoring' => false,
            'enable_user_tracking' => true,
        ],
    ],
];
