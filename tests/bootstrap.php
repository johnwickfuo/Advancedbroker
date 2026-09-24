<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(static function (string $class): void { if (str_starts_with($class, 'App\\')) { $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php'; if (is_file($file)) require $file; } }); require BASE_PATH . '/app/Support/helpers.php';
if (session_status() === PHP_SESSION_NONE) { $sessionPath = sys_get_temp_dir() . '/upgradedbroker-tests'; if (!is_dir($sessionPath)) mkdir($sessionPath, 0700, true); session_save_path($sessionPath); }
function test(string $name, callable $assertions): void { try { $assertions(); echo "PASS $name\n"; } catch (Throwable $e) { fwrite(STDERR, "FAIL $name: {$e->getMessage()}\n"); exit(1); } }
function expect(bool $condition, string $message = 'Expectation failed'): void { if (!$condition) throw new RuntimeException($message); }
