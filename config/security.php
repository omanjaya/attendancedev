<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | attendance management system.
    |
    */

    'password' => [
        /*
        |--------------------------------------------------------------------------
        | Password Requirements
        |--------------------------------------------------------------------------
        |
        | Configure password strength requirements for user accounts.
        |
        */
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_special_chars' => env('PASSWORD_REQUIRE_SPECIAL', true),
        'prevent_common_passwords' => env('PASSWORD_PREVENT_COMMON', true),
        'history_check' => env('PASSWORD_HISTORY_CHECK', 5), // Number of previous passwords to check
        'expiry_days' => env('PASSWORD_EXPIRY_DAYS', 90), // Password expiry in days (0 = never)
    ],

    'session' => [
        /*
        |--------------------------------------------------------------------------
        | Session Security
        |--------------------------------------------------------------------------
        |
        | Configure session security settings including timeouts and validation.
        |
        */
        'lifetime' => env('SESSION_LIFETIME', 120), // Minutes
        'idle_timeout' => env('SESSION_IDLE_TIMEOUT', 30), // Minutes of inactivity
        'concurrent_sessions' => env('MAX_CONCURRENT_SESSIONS', 3), // Max sessions per user
        'validate_ip' => env('SESSION_VALIDATE_IP', true),
        'validate_user_agent' => env('SESSION_VALIDATE_USER_AGENT', true),
        'regenerate_on_login' => env('SESSION_REGENERATE_ON_LOGIN', true),
    ],

    'rate_limiting' => [
        /*
        |--------------------------------------------------------------------------
        | Rate Limiting Configuration
        |--------------------------------------------------------------------------
        |
        | Configure rate limiting for various actions to prevent abuse.
        |
        */
        'login' => [
            'max_attempts' => env('RATE_LIMIT_LOGIN_ATTEMPTS', 5),
            'window_minutes' => env('RATE_LIMIT_LOGIN_WINDOW', 15),
            'lockout_minutes' => env('RATE_LIMIT_LOGIN_LOCKOUT', 60),
        ],
        'api' => [
            'max_attempts' => env('RATE_LIMIT_API_ATTEMPTS', 100),
            'window_minutes' => env('RATE_LIMIT_API_WINDOW', 60),
        ],
        'password_reset' => [
            'max_attempts' => env('RATE_LIMIT_PASSWORD_RESET_ATTEMPTS', 3),
            'window_minutes' => env('RATE_LIMIT_PASSWORD_RESET_WINDOW', 60),
        ],
    ],

    'ip_whitelist' => [
        /*
        |--------------------------------------------------------------------------
        | IP Address Whitelisting
        |--------------------------------------------------------------------------
        |
        | Configure IP address restrictions for enhanced security.
        |
        */
        'enabled' => env('IP_WHITELIST_ENABLED', false),
        
        // Global allowed IPs (applies to all users)
        'global' => [
            // '192.168.1.0/24',
            // '10.0.0.0/8',
        ],
        
        // Guard-specific IPs
        'guards' => [
            'admin' => [
                // '192.168.1.100',
                // '10.0.0.50',
            ],
            'api' => [
                // '192.168.1.0/24',
            ],
        ],
        
        // Role-specific IPs
        'roles' => [
            'admin' => [
                // '192.168.1.100',
            ],
            'manager' => [
                // '192.168.1.0/24',
            ],
        ],
    ],

    'encryption' => [
        /*
        |--------------------------------------------------------------------------
        | Data Encryption Settings
        |--------------------------------------------------------------------------
        |
        | Configure encryption for sensitive data fields.
        |
        */
        'face_data' => [
            'enabled' => env('ENCRYPT_FACE_DATA', true),
            'cipher' => env('FACE_DATA_CIPHER', 'AES-256-CBC'),
        ],
        'employee_metadata' => [
            'enabled' => env('ENCRYPT_EMPLOYEE_METADATA', true),
            'cipher' => env('EMPLOYEE_METADATA_CIPHER', 'AES-256-CBC'),
        ],
        'audit_logs' => [
            'enabled' => env('ENCRYPT_AUDIT_LOGS', false),
            'cipher' => env('AUDIT_LOGS_CIPHER', 'AES-256-CBC'),
        ],
    ],

    'headers' => [
        /*
        |--------------------------------------------------------------------------
        | Security Headers Configuration
        |--------------------------------------------------------------------------
        |
        | Configure security headers to protect against common attacks.
        |
        */
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', true),
            'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', true),
        ],
        'csp' => [
            'enabled' => env('SECURITY_CSP_ENABLED', true),
            'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),
            'report_uri' => env('SECURITY_CSP_REPORT_URI', null),
        ],
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('SECURITY_X_XSS_PROTECTION', '1; mode=block'),
    ],

    'audit' => [
        /*
        |--------------------------------------------------------------------------
        | Security Audit Configuration
        |--------------------------------------------------------------------------
        |
        | Configure security auditing and monitoring settings.
        |
        */
        'log_failed_logins' => env('AUDIT_LOG_FAILED_LOGINS', true),
        'log_successful_logins' => env('AUDIT_LOG_SUCCESSFUL_LOGINS', true),
        'log_password_changes' => env('AUDIT_LOG_PASSWORD_CHANGES', true),
        'log_permission_changes' => env('AUDIT_LOG_PERMISSION_CHANGES', true),
        'log_sensitive_data_access' => env('AUDIT_LOG_SENSITIVE_DATA_ACCESS', true),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
        'alert_on_suspicious_activity' => env('AUDIT_ALERT_SUSPICIOUS_ACTIVITY', true),
        'alert_email' => env('AUDIT_ALERT_EMAIL', null),
    ],

    'monitoring' => [
        /*
        |--------------------------------------------------------------------------
        | Security Monitoring
        |--------------------------------------------------------------------------
        |
        | Configure real-time security monitoring and alerting.
        |
        */
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        'suspicious_activity_threshold' => env('SECURITY_SUSPICIOUS_THRESHOLD', 7),
        'failed_login_alert_threshold' => env('SECURITY_FAILED_LOGIN_ALERT_THRESHOLD', 10),
        'new_device_notification' => env('SECURITY_NEW_DEVICE_NOTIFICATION', true),
        'unusual_activity_notification' => env('SECURITY_UNUSUAL_ACTIVITY_NOTIFICATION', true),
        'admin_notification_email' => env('SECURITY_ADMIN_NOTIFICATION_EMAIL', null),
    ],

    'file_upload' => [
        /*
        |--------------------------------------------------------------------------
        | File Upload Security
        |--------------------------------------------------------------------------
        |
        | Configure security settings for file uploads.
        |
        */
        'scan_uploads' => env('SECURITY_SCAN_UPLOADS', true),
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'max_file_size' => env('SECURITY_MAX_FILE_SIZE', 10485760), // 10MB in bytes
        'quarantine_suspicious_files' => env('SECURITY_QUARANTINE_SUSPICIOUS_FILES', true),
        'virus_scan_enabled' => env('SECURITY_VIRUS_SCAN_ENABLED', false),
        'virus_scan_command' => env('SECURITY_VIRUS_SCAN_COMMAND', 'clamscan'),
    ],

    'api' => [
        /*
        |--------------------------------------------------------------------------
        | API Security Configuration
        |--------------------------------------------------------------------------
        |
        | Configure security settings for API endpoints.
        |
        */
        'require_https' => env('API_REQUIRE_HTTPS', true),
        'cors_enabled' => env('API_CORS_ENABLED', true),
        'cors_origins' => env('API_CORS_ORIGINS', '*'),
        'request_signature_validation' => env('API_REQUEST_SIGNATURE_VALIDATION', false),
        'webhook_signature_secret' => env('API_WEBHOOK_SIGNATURE_SECRET', null),
        'api_key_rotation_days' => env('API_KEY_ROTATION_DAYS', 90),
    ],

    'face_detection' => [
        /*
        |--------------------------------------------------------------------------
        | Face Detection Security
        |--------------------------------------------------------------------------
        |
        | Configure security settings for face detection features.
        |
        */
        'min_confidence_threshold' => env('FACE_DETECTION_MIN_CONFIDENCE', 0.8),
        'liveness_detection_required' => env('FACE_DETECTION_LIVENESS_REQUIRED', true),
        'max_registration_attempts' => env('FACE_DETECTION_MAX_REGISTRATION_ATTEMPTS', 3),
        'face_data_retention_days' => env('FACE_DETECTION_DATA_RETENTION_DAYS', 90),
        'prevent_spoofing' => env('FACE_DETECTION_PREVENT_SPOOFING', true),
        'gesture_verification' => env('FACE_DETECTION_GESTURE_VERIFICATION', true),
    ],

    'backup' => [
        /*
        |--------------------------------------------------------------------------
        | Backup Security
        |--------------------------------------------------------------------------
        |
        | Configure security settings for system backups.
        |
        */
        'encrypt_backups' => env('BACKUP_ENCRYPT', true),
        'backup_encryption_key' => env('BACKUP_ENCRYPTION_KEY', null),
        'secure_backup_storage' => env('BACKUP_SECURE_STORAGE', true),
        'backup_integrity_check' => env('BACKUP_INTEGRITY_CHECK', true),
        'offsite_backup_required' => env('BACKUP_OFFSITE_REQUIRED', false),
    ],
];