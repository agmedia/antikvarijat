<?php

return [
    'request_emails_enabled' => env('REVIEW_REQUEST_EMAILS_ENABLED', false),
    'request_delay_days' => (int) env('REVIEW_REQUEST_DELAY_DAYS', 30),
    'request_max_attempts' => (int) env('REVIEW_REQUEST_MAX_ATTEMPTS', 3),
    'request_link_days' => (int) env('REVIEW_REQUEST_LINK_DAYS', 180),
    'default_locale' => env('REVIEW_REQUEST_DEFAULT_LOCALE', 'hr'),
    'backfill_max_orders' => (int) env('REVIEW_BACKFILL_MAX_ORDERS', 5000),
    'backfill_default_interval_seconds' => (int) env('REVIEW_BACKFILL_INTERVAL_SECONDS', 5),
    'backfill_interval_options' => [1, 2, 5, 10, 15, 30, 60],
    'backfill_run_seconds' => (int) env('REVIEW_BACKFILL_RUN_SECONDS', 58),
];
