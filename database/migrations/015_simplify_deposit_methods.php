<?php
declare(strict_types=1);

use App\Support\Database;

return static function(Database $db): void {
    $hasColumn=static function(string $column)use($db):bool{
        return (bool)$db->scalar(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="deposit_methods" AND COLUMN_NAME=?',
            [$column]
        );
    };

    if(!$hasColumn('method_type')){
        $db->execute('ALTER TABLE deposit_methods ADD COLUMN method_type VARCHAR(20) NOT NULL DEFAULT "CUSTOM" AFTER slug');
    }
    if(!$hasColumn('payment_details')){
        $db->execute('ALTER TABLE deposit_methods ADD COLUMN payment_details JSON NULL AFTER instructions');
    }
};
