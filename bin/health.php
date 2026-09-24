<?php
declare(strict_types=1);
/** Safe CLI health check: no credentials, SQL errors or file paths are emitted. */
require __DIR__ . '/bootstrap.php';

$failures = [];
try { $db = \App\Application::database(BASE_PATH); $db->scalar('SELECT 1'); } catch (Throwable) { $failures[] = 'database'; }
foreach (['storage/cache','storage/logs','storage/private','storage/uploads'] as $directory) {
    $path = BASE_PATH . '/' . $directory;
    if (!is_dir($path) || !is_writable($path)) $failures[] = $directory;
}
if ($failures) { fwrite(STDERR, "Health check failed: " . implode(', ', $failures) . "\n"); exit(1); }
echo "Health check passed.\n";
