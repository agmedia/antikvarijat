<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reliable order e-mail delivery
    |--------------------------------------------------------------------------
    |
    | Checkout tries to send both messages immediately.  The scheduler retries
    | durable, unsent delivery rows until SMTP accepts them or max_attempts is
    | reached.  A stale claim lets a later run recover from a killed PHP process.
    |
    */

    'batch_size' => (int) env('ORDER_NOTIFICATION_BATCH_SIZE', 25),
    // Zero means unlimited retries. Admin order mail must never be abandoned
    // merely because the SMTP service was unavailable for a longer period.
    'max_attempts' => (int) env('ORDER_NOTIFICATION_MAX_ATTEMPTS', 0),
    'base_retry_minutes' => (int) env('ORDER_NOTIFICATION_RETRY_MINUTES', 2),
    'max_retry_minutes' => (int) env('ORDER_NOTIFICATION_MAX_RETRY_MINUTES', 60),
    'stale_claim_minutes' => (int) env('ORDER_NOTIFICATION_STALE_CLAIM_MINUTES', 10),
    'max_seconds' => (int) env('ORDER_NOTIFICATION_MAX_SECONDS', 50),
];
