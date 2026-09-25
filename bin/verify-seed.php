<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

try {
    $db = \App\Application::database(BASE_PATH);
    $countries = $db->select(
        'SELECT id,name,is_global FROM countries WHERE is_global=1 OR (is_active=1 AND is_enabled=1) ORDER BY is_global ASC,sort_order ASC,id ASC'
    );

    $failed = false;
    foreach ($countries as $country) {
        $target = !empty($country['is_global']) ? 20 : 10;
        $active = (int)$db->scalar(
            'SELECT COUNT(*) FROM investment_offerings o JOIN companies c ON c.id=o.company_id WHERE o.country_id=? AND o.is_active=1 AND c.status="ACTIVE"',
            [$country['id']]
        );
        $featured = (int)$db->scalar(
            'SELECT COUNT(*) FROM investment_offerings o JOIN companies c ON c.id=o.company_id WHERE o.country_id=? AND o.is_active=1 AND o.is_featured=1 AND c.status="ACTIVE"',
            [$country['id']]
        );

        printf("%s: %d active, %d featured (minimum %d / 5)\n", $country['name'], $active, $featured, $target);
        if ($active < $target || $featured < 5) $failed = true;
    }

    if ($failed) {
        fwrite(STDERR, "Catalogue seed verification failed.\n");
        exit(1);
    }

    echo "Catalogue seed verification passed.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Catalogue seed verification could not run: " . $e->getMessage() . "\n");
    exit(1);
}
