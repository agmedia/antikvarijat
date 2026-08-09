<?php

return [
    'enabled' => env('ABANDONED_CART_EMAILS_ENABLED', false),

    // Podsjetnici vrijede isključivo za narudžbe nastale od ovog trenutka nadalje.
    'starts_at' => '2026-08-09 00:00:00',

    // Prvi podsjetnik ide nakon 60 minuta, drugi idući dan u vrijeme narudžbe.
    'delays_minutes' => [
        1 => 60,
        2 => 24 * 60,
    ],

    'max_reminders' => 2,
    'batch_size' => 25,
    'recovery_link_days' => 7,
    'claim_timeout_minutes' => 15,
];
