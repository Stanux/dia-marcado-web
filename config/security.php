<?php

return [
    'debug' => [
        'enable_ignition_routes' => (bool) env('SECURITY_ENABLE_IGNITION_ROUTES', false),
    ],

    'monitoring' => [
        'expose_publicly' => (bool) env('SECURITY_MONITORING_PUBLIC', false),
        'token' => env('SECURITY_MONITORING_TOKEN', ''),
        'token_header' => env('SECURITY_MONITORING_TOKEN_HEADER', 'X-Health-Token'),
        'allowed_ips' => array_values(array_filter(array_map(
            static fn (string $ip): string => trim($ip),
            explode(',', (string) env('SECURITY_MONITORING_ALLOWED_IPS', ''))
        ))),
    ],

    'headers' => [
        'enabled' => (bool) env('SECURITY_HEADERS_ENABLED', true),
        'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=()'),
        'content_security_policy' => env('SECURITY_CSP', ''),
        'hsts' => [
            'enabled' => (bool) env('SECURITY_HSTS_ENABLED', false),
            'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => (bool) env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => (bool) env('SECURITY_HSTS_PRELOAD', false),
        ],
    ],
];
