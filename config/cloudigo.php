<?php

declare(strict_types=1);

return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    'short_url_base' => rtrim((string) env('SHORT_URL_BASE', env('APP_URL', 'http://localhost')), '/'),
    'redirect_rate_limit' => (int) env('REDIRECT_RATE_LIMIT', 600),
    'reserved_aliases' => [
        'api',
        'admin',
        'login',
        'register',
        'logout',
        'dashboard',
        'links',
        'analytics',
        'health',
        'status',
        'storage',
        'sanctum',
        'password',
        'forgot-password',
        'reset-password',
    ],
];
