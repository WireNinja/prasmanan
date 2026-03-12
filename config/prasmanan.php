<?php

use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use WireNinja\Prasmanan\Filament\Pages\BetterEditProfile;
use WireNinja\Prasmanan\Filament\Pages\LoginOptions;
use WireNinja\Prasmanan\Filament\Pages\ManageAuthSettings;
use WireNinja\Prasmanan\Settings\SystemAppSettings;
use WireNinja\Prasmanan\Settings\SystemAuthSettings;

return [
    /*
    |--------------------------------------------------------------------------
    | Broadcasting (Laravel Echo) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for real-time notifications via Laravel Echo.
    |
    */
    'broadcasting' => [
        'enabled' => env('PRASMANAN_BROADCASTING_ENABLED', true),
        'channel_prefix' => env('PRASMANAN_BROADCASTING_CHANNEL_PREFIX', 'App.Models.User.'),
        'event_name' => env('PRASMANAN_BROADCASTING_EVENT', 'Showcase\\SendWelcomeMessageEvent'),
        'sound_url' => env('PRASMANAN_BROADCASTING_SOUND_URL', '/sounds/default.wav'),
    ],

    'security' => [
        'max_file_upload_size' => env('PRASMANAN_TEMPORARY_MAX_FILE_SIZE', 51200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sidebar Panels Configuration
    |--------------------------------------------------------------------------
    |
    | Define the Enum class that provides the list of panels to display
    | in the dual-pane sidebar.
    |
    */
    'panel_enum' => 'App\Enums\PanelEnum',

    'resource_enum' => 'App\Enums\FilamentResourceEnum',

    /*
    |--------------------------------------------------------------------------
    | Horizon Configuration
    |--------------------------------------------------------------------------
    |
    | Define where Horizon should route mail notifications.
    | Leave empty if you don't want Horizon to send email notifications.
    |
    */
    'horizon' => [
        'mail_notification_to' => env('PRASMANAN_HORIZON_MAIL_TO', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | PWA (Progressive Web App) Configuration
    |--------------------------------------------------------------------------
    */
    'pwa' => [
        'enabled' => env('PRASMANAN_PWA_ENABLED', true),
    ],

    'filament' => [
        'dark_mode' => env('FILAMENT_DARK_MODE', false),

        // Settings
        'auth_setting' => SystemAuthSettings::class,
        'app_settings' => [
            SystemAuthSettings::class,
            SystemAppSettings::class,
        ],
        'settings_page' => ManageAuthSettings::class,

        // Security & Authentication
        'mfa' => [
            'recovery_code_count' => 10,
            'code_window' => 10,
        ],

        // Proxyable classes
        'profile_page' => BetterEditProfile::class,
        'login_page' => LoginOptions::class,

        // UI & Layout Preferences
        'font' => 'IBM Plex Sans',
        'colors' => [
            'primary' => Color::Zinc,
        ],
        'sidebar_width' => '350px',
        'sidebar_collapsible_on_desktop' => true,
        'collapsible_navigation_groups' => true,

        // Application Behaviour
        'spa_mode' => true,
        'spa_url_exceptions' => [
            '*/auth/google*',
        ],

        // Base Content
        'pages' => [
            Dashboard::class,
        ],
        'widgets' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Threshold Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Nightwatch exception signals and rate limiting.
    |
    */
    'monitoring' => [
        'slow_request_threshold' => (int) env('PRASMANAN_SLOW_REQUEST_THRESHOLD', 2000), // ms
        'slow_query_threshold' => (int) env('PRASMANAN_SLOW_QUERY_THRESHOLD', 1000),   // ms
        'slow_job_threshold' => (int) env('PRASMANAN_SLOW_JOB_THRESHOLD', 10000),    // ms
        'rate_limit' => [
            'api' => (int) env('PRASMANAN_API_RATE_LIMIT', 60),  // per minute
            'login' => (int) env('PRASMANAN_LOGIN_RATE_LIMIT', 5), // per minute
        ],
    ],
];
