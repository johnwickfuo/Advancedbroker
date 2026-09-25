<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
try {
    $db = \App\Application::database(BASE_PATH);
    (new \Database\Seeds\CountrySeeder())->run($db);
    (new \Database\Seeds\AuthSettingsSeeder())->run($db);
    (new \Database\Seeds\CompanySeeder())->run($db);
    (new \Database\Seeds\AiTradingSeeder())->run($db);
    (new \Database\Seeds\QaUserSeeder())->run($db);
    echo "Seeded countries, languages, authentication settings, company records, AI trading categories and optional QA user.\n";
} catch (Throwable $e) {
    fwrite(STDERR,"Seed command failed: ".$e->getMessage()."\n");
    exit(1);
}
