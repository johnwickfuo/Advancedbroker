<?php
declare(strict_types=1);

return [
    'enabled'=>filter_var(env('GOOGLE_TRANSLATION_ENABLED',true),FILTER_VALIDATE_BOOL),
    'api_key'=>(string)env('GOOGLE_TRANSLATE_API_KEY',''),
    'cache_seconds'=>(int)env('GOOGLE_TRANSLATION_CACHE_SECONDS',604800),
    'timeout_seconds'=>max(2,min(15,(int)env('GOOGLE_TRANSLATION_TIMEOUT_SECONDS',6))),
];
