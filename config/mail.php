<?php
declare(strict_types=1);
return ['driver' => env('MAIL_DRIVER', 'smtp'), 'host' => env('MAIL_HOST'), 'port' => (int) env('MAIL_PORT', 587), 'username' => env('MAIL_USERNAME'), 'password' => env('MAIL_PASSWORD'), 'encryption' => env('MAIL_ENCRYPTION', 'tls'), 'from' => ['address' => env('MAIL_FROM_ADDRESS'), 'name' => env('MAIL_FROM_NAME', env('APP_NAME'))]];
