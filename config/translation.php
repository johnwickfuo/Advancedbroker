<?php
declare(strict_types=1);
$root=dirname(__DIR__);
return [
    'enabled'=>filter_var(env('OFFLINE_TRANSLATION_ENABLED',true),FILTER_VALIDATE_BOOL),
    'python'=>(string)env('TRANSLATION_PYTHON','python3'),
    'script'=>$root.'/bin/offline_translate.py',
    'cache_seconds'=>(int)env('OFFLINE_TRANSLATION_CACHE_SECONDS',604800),
    'timeout_seconds'=>max(1,min(15,(int)env('OFFLINE_TRANSLATION_TIMEOUT_SECONDS',4))),
    'failure_cooldown_seconds'=>max(30,min(3600,(int)env('OFFLINE_TRANSLATION_FAILURE_COOLDOWN_SECONDS',300))),
    'packages_dir'=>(string)env('ARGOS_PACKAGES_DIR',$root.'/storage/private/argos/packages'),
    'xdg_data_home'=>(string)env('ARGOS_XDG_DATA_HOME',$root.'/storage/private/argos/data'),
    'xdg_config_home'=>(string)env('ARGOS_XDG_CONFIG_HOME',$root.'/storage/private/argos/config'),
    'xdg_cache_home'=>(string)env('ARGOS_XDG_CACHE_HOME',$root.'/storage/private/argos/cache'),
];
