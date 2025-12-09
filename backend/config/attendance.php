<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, employees MUST have a monthly schedule assigned to check-in/out.
    | When disabled, attendance is allowed without schedules (legacy mode).
    |
    | Recommended: true for production, false for development/testing
    |
    */
    'strict_mode' => env('ATTENDANCE_STRICT_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Schedule Validation Bypass Roles
    |--------------------------------------------------------------------------
    |
    | Users with these roles can bypass schedule validation.
    | Useful for admin testing and emergency situations.
    |
    | Format: Comma-separated role names
    | Example: 'super_admin,admin,tester'
    |
    */
    'bypass_roles' => array_filter(
        array_map('trim', explode(',', env('ATTENDANCE_BYPASS_ROLES', 'super_admin')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Enable Audit Logging for Bypass
    |--------------------------------------------------------------------------
    |
    | When enabled, all schedule validation bypasses will be logged.
    | Recommended: true for production (security & compliance)
    |
    */
    'log_bypass' => env('ATTENDANCE_LOG_BYPASS', true),

    /*
    |--------------------------------------------------------------------------
    | Location Verification (Geofencing)
    |--------------------------------------------------------------------------
    |
    | Require employees to be within location radius when checking in/out.
    | Uses GPS coordinates and Haversine formula for distance calculation.
    |
    | Recommended: true for production (prevents remote check-in)
    |
    */
    'require_location_verification' => env('ATTENDANCE_REQUIRE_LOCATION', true),

    /*
    |--------------------------------------------------------------------------
    | Face Verification
    |--------------------------------------------------------------------------
    |
    | Require face recognition verification for check-in/out.
    | Uses DeepFace with ArcFace model for high accuracy.
    |
    | Recommended: true for production (prevents buddy punching)
    |
    */
    'require_face_verification' => env('ATTENDANCE_REQUIRE_FACE', true),

    /*
    |--------------------------------------------------------------------------
    | Time Window Validation
    |--------------------------------------------------------------------------
    |
    | Enable/disable time window validation for check-in and check-out.
    | When disabled, employees can check-in/out at any time within working days.
    |
    */
    'validate_time_windows' => env('ATTENDANCE_VALIDATE_TIME_WINDOWS', true),

    /*
    |--------------------------------------------------------------------------
    | Working Day Validation
    |--------------------------------------------------------------------------
    |
    | Enable/disable working day validation.
    | When disabled, employees can check-in/out on any day.
    |
    */
    'validate_working_days' => env('ATTENDANCE_VALIDATE_WORKING_DAYS', true),

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Exception
    |--------------------------------------------------------------------------
    |
    | Allow attendance even when validation fails during maintenance windows.
    | Useful for system migrations or emergency situations.
    |
    */
    'maintenance_mode' => env('ATTENDANCE_MAINTENANCE_MODE', false),
];
