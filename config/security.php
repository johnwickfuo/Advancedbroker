<?php
declare(strict_types=1);
return ['trusted_proxies' => array_filter(explode(',', (string) env('TRUSTED_PROXIES', ''))), 'password_hash_algorithm' => PASSWORD_DEFAULT, 'private_download_ttl_seconds' => 300];
