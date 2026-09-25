<?php
declare(strict_types=1);

return [
    'enabled' => filter_var(env('GOOGLE_TRANSLATE_ENABLED', false), FILTER_VALIDATE_BOOL),
    'api_key' => (string)env('GOOGLE_TRANSLATE_API_KEY', ''),
    'cache_seconds' => (int)env('GOOGLE_TRANSLATE_CACHE_SECONDS', 604800),
    'timeout_seconds' => (int)env('GOOGLE_TRANSLATE_TIMEOUT', 6),
];
