<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
if (is_file(BASE_PATH . '/vendor/autoload.php')) require BASE_PATH . '/vendor/autoload.php';
spl_autoload_register(static function (string $class): void { if (str_starts_with($class, 'App\\')) { $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php'; if (is_file($file)) require $file; } if (str_starts_with($class, 'Database\\Seeds\\')) { $file = BASE_PATH . '/database/seeds/' . substr($class, strlen('Database\\Seeds\\')) . '.php'; if (is_file($file)) require $file; } });
require BASE_PATH . '/app/Support/helpers.php';
\App\Support\Env::load(BASE_PATH . '/.env');
