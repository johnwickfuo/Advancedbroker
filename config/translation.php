<?php
declare(strict_types=1);
return [
    'enabled'=>filter_var(env('OFFLINE_TRANSLATION_ENABLED',true),FILTER_VALIDATE_BOOL),
    'python'=>(string)env('TRANSLATION_PYTHON','python3'),
    'script'=>BASE_PATH.'/bin/offline_translate.py',
    'cache_seconds'=>(int)env('OFFLINE_TRANSLATION_CACHE_SECONDS',604800),
];
