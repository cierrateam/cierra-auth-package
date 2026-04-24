<?php

// config for VendorName/Skeleton
return [
    'host' => env('CIERRA_AUTH_HOST', 'https://dev.admin.cierra.ai'),
    'client_id' => env('CIERRA_AUTH_CLIENT_ID', 'client-id'),
    'client_secret' => env('CIERRA_AUTH_CLIENT_SECRET', 'client-secret'),
    'redirect_after_login' => env('CIERRA_AUTH_REDIRECT_AFTER_LOGIN', '/'),
    'redirect_after_logout' => env('CIERRA_AUTH_REDIRECT_AFTER_LOGOUT', '/'),

    'registers_app_id' => env('CIERRA_APP_ID', null),
    'client_enrollment_secret' => env('CIERRA_AUTH_CLIENT_ENROLLMENT_SECRET', null),

    // License enforcement (v0.3.0+)
    'required_application_slug' => env('CIERRA_APP_SLUG', null),
    'required_features' => [],
    'require_active_seat' => true,
    'on_license_missing' => 'redirect',
    'license_missing_redirect' => '/cierra-auth/no-license',
    'webhook_secret' => env('CIERRA_AUTH_WEBHOOK_SECRET', null),
    'context_cache_ttl' => 300,
    'log_webhook_payloads' => false,
];
