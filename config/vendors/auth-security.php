<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security settings for authentication including throttling,
    | credential lockout, and CAPTCHA integration.
    |
    */

    'throttling' => [
        // IP-based rate limiting (global brute force protection)
        'ip_limit' => env('AUTH_THROTTLE_IP_LIMIT', 50),
        'ip_period_minutes' => env('AUTH_THROTTLE_IP_PERIOD', 60),

        // Identifier-based rate limiting (per account)
        'identifier_limit' => env('AUTH_THROTTLE_IDENTIFIER_LIMIT', 5),
        'identifier_period_minutes' => env('AUTH_THROTTLE_IDENTIFIER_PERIOD', 15),

        // Combined (IP + Identifier) rate limiting
        'combined_limit' => env('AUTH_THROTTLE_COMBINED_LIMIT', 3),
        'combined_period_minutes' => env('AUTH_THROTTLE_COMBINED_PERIOD', 15),

        // Device banning - Progressive ban durations (in minutes)
        'ban_duration_first' => env('AUTH_BAN_DURATION_FIRST', 60), // 1 hour
        'ban_duration_second' => env('AUTH_BAN_DURATION_SECOND', 1440), // 24 hours
        'ban_duration_third' => env('AUTH_BAN_DURATION_THIRD', 10080), // 7 days
        'ban_duration_permanent' => null, // Permanent ban after 4th offense
    ],

    'credentials' => [
        // PIN/Pattern lockout settings
        'max_failed_attempts' => env('AUTH_CREDENTIAL_MAX_ATTEMPTS', 5),
        'lockout_duration_minutes' => env('AUTH_CREDENTIAL_LOCKOUT_DURATION', 15),
    ],

    'captcha' => [
        // Cloudflare Turnstile settings
        'enabled' => env('AUTH_CAPTCHA_ENABLED', true),

        // Show CAPTCHA after N failed login attempts
        'trigger_after_failures' => env('AUTH_CAPTCHA_TRIGGER_FAILURES', 2),

        // Cloudflare Turnstile credentials
        'turnstile' => [
            'site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY'),
            'secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY'),
        ],
    ],

    'session' => [
        // Session keys for tracking
        'failed_attempts_key' => 'login_failed_attempts',
        'captcha_required_key' => 'captcha_required',
    ],

];
