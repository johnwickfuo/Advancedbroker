<?php
declare(strict_types=1);

use App\Support\Database;

return static function(Database $db): void {
    $hasColumn=static function(string $column)use($db):bool{
        return (bool)$db->scalar(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="notifications" AND COLUMN_NAME=?',
            [$column]
        );
    };

    if(!$hasColumn('display_limit')){
        $db->execute('ALTER TABLE notifications ADD COLUMN display_limit SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER data');
    }
    if(!$hasColumn('display_count')){
        $db->execute('ALTER TABLE notifications ADD COLUMN display_count SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER display_limit');
    }

    $db->execute('UPDATE notifications SET display_limit=1 WHERE display_limit<1');
    $db->execute('UPDATE notifications SET display_count=display_limit WHERE read_at IS NOT NULL AND display_count<display_limit');
};
