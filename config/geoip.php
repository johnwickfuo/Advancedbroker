<?php
declare(strict_types=1);
return [
    'driver' => env('GEOIP_DRIVER', 'auto'), // auto, cloudflare, maxmind
    'database_path' => env('GEOIP_DATABASE_PATH', ''),
    'trust_cloudflare_country_header' => filter_var(env('TRUST_CLOUDFLARE_COUNTRY_HEADER', false), FILTER_VALIDATE_BOOL),
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),
];
