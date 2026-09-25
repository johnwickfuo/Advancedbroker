<?php
declare(strict_types=1);

/*
 * Frozen USD -> account-currency rates captured for this build on 2026-09-25.
 * They are intentionally NOT refreshed automatically. Every AI purchase also
 * stores the exact rate/date used so future code/config changes cannot alter
 * historical purchases.
 */
return [
    'fx_snapshot_date' => '2026-09-25',
    'rates' => [
        'USD'=>1.000000,
        'GBP'=>0.755316,
        'EUR'=>0.879090,
        'CHF'=>0.829470,
        'SEK'=>9.918540,
        'NOK'=>9.519500,
        'DKK'=>6.565860,
        'CAD'=>1.414130,
        'BRL'=>5.191800,
        'MXN'=>17.675900,
        'JPY'=>158.148000,
        'KRW'=>1354.990000,
        'SGD'=>1.278590,
        'HKD'=>7.843420,
        'INR'=>95.832800,
        'AUD'=>1.423080,
        'NZD'=>1.765660,
        'ZAR'=>16.390500,
        'AED'=>3.673000,
        'SAR'=>3.751080,
        'PLN'=>3.841490,
        'PHP'=>62.456000,
        'TTD'=>6.800790,
        'JMD'=>157.699000,
        'BBD'=>2.012030,
    ],
    'default_batch_size' => 50,
];
