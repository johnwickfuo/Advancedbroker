<?php
declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Upgraded Broker'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'key' => (string) env('APP_KEY', ''),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'session' => ['name' => env('SESSION_NAME', 'upgradedbroker_session'), 'lifetime' => (int) env('SESSION_LIFETIME', 7200), 'secure' => filter_var(env('SESSION_SECURE_COOKIE', true), FILTER_VALIDATE_BOOL), 'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string)env('TRUSTED_PROXIES','')))))],
    'uploads' => ['max_bytes' => (int) env('UPLOAD_MAX_BYTES', 5242880)],
    'rate_limit_per_minute' => (int) env('RATE_LIMIT_PER_MINUTE', 60),
];
