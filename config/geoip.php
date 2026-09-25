<?php
declare(strict_types=1);
return [
    'driver' => env('GEOIP_DRIVER', 'auto'), // auto, cloudflare, maxmind, ipwhois
    'database_path' => env('GEOIP_DATABASE_PATH', ''),
    'trust_cloudflare_country_header' => filter_var(env('TRUST_CLOUDFLARE_COUNTRY_HEADER', false), FILTER_VALIDATE_BOOL),
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),
    'remote_fallback' => filter_var(env('GEOIP_REMOTE_FALLBACK', true), FILTER_VALIDATE_BOOL),
    'remote_timeout' => (int)env('GEOIP_REMOTE_TIMEOUT', 2),
    'cache_seconds' => (int)env('GEOIP_CACHE_SECONDS', 21600),
];
