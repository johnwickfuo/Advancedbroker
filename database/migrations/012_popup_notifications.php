<?php
declare(strict_types=1);
use App\Support\Database;
return static function(Database $db): void {
    $db->execute("UPDATE notifications SET channel='popup' WHERE channel<>'popup'");
};
