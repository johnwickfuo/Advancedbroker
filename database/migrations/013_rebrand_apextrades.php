<?php
declare(strict_types=1);

use App\Support\Database;

return static function (Database $db): void {
    $db->execute(
        "UPDATE country_branding
         SET brand_name='ApexTrades',
             short_name='APEXTRADES',
             updated_at=NOW()
         WHERE LOWER(COALESCE(brand_name,'')) IN ('upgraded broker','upgradedbroker')
            OR LOWER(COALESCE(short_name,'')) IN ('upgraded broker','upgradedbroker')"
    );
};
