<?php

return [
    'min_amount' => 10,
    'max_amount' => 300,
    'amount_step' => 10,
    'default_amount' => 30,
    'code_prefix' => 'BIBLOS-',
    'code_length' => 10,
    'reservation_minutes' => 180,
    'payment_codes' => ['corvus', 'corvus_wallets'],
    'emails_enabled' => env('GIFT_VOUCHER_EMAILS_ENABLED', true),
];
