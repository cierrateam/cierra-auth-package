<?php

// config for VendorName/Skeleton
return [
    'host' => env('CIERRA_AUTH_HOST', 'https://dev.admin.cierra.ai'),
    'client_id' => env('CIERRA_AUTH_CLIENT_ID', 'client-id'),
    'client_secret' => env('CIERRA_AUTH_CLIENT_SECRET', 'client-secret'),
    'redirect_after_login' => env('CIERRA_AUTH_REDIRECT_AFTER_LOGIN', '/'),
];
