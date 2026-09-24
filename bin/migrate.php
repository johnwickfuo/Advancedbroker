<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
try { $db = \App\Application::database(BASE_PATH); $db->execute('CREATE TABLE IF NOT EXISTS migrations (migration VARCHAR(190) PRIMARY KEY, batch INT UNSIGNED NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'); $files = glob(BASE_PATH . '/database/migrations/*.php') ?: []; $applied = array_column($db->select('SELECT migration FROM migrations'), 'migration');
if (($argv[1] ?? '') === 'status') { foreach ($files as $file) { $name = basename($file); echo (in_array($name, $applied, true) ? '[up]  ' : '[down]') . " $name\n"; } exit; }
$batch = (int)$db->scalar('SELECT COALESCE(MAX(batch),0)+1 FROM migrations'); foreach ($files as $file) { $name = basename($file); if (in_array($name, $applied, true)) continue; $migration = require $file; if (!is_callable($migration)) throw new RuntimeException("Migration $name must return a callable."); /* MySQL DDL implicitly commits, so migrations own their atomicity strategy. */ $migration($db); $db->execute('INSERT INTO migrations (migration,batch) VALUES (?,?)', [$name,$batch]); echo "Migrated: $name\n"; }
} catch (Throwable $e) { fwrite(STDERR,"Migration command could not connect to the configured database. Check .env DB_* values and MySQL availability.\n"); exit(1); }
