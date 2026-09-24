<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
try { $db = \App\Application::database(BASE_PATH); (new \Database\Seeds\CountrySeeder())->run($db); (new \Database\Seeds\AuthSettingsSeeder())->run($db); (new \Database\Seeds\CompanySeeder())->run($db); echo "Seeded countries, languages, authentication settings and verified company records.\n"; } catch (Throwable $e) { fwrite(STDERR,"Seed command could not connect to the configured database. Check .env DB_* values and MySQL availability.\n"); exit(1); }
