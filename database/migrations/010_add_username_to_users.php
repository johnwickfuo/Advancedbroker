<?php
declare(strict_types=1);

use App\Support\Database;

return static function(Database $db): void {
    $has = (bool)$db->scalar(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="users" AND COLUMN_NAME="username"'
    );
    if (!$has) {
        $db->execute('ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL AFTER uuid, ADD UNIQUE KEY users_username_unique(username)');
    }
};
