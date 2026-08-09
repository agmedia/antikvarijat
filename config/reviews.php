<?php

return [
    'request_emails_enabled' => env('REVIEW_REQUEST_EMAILS_ENABLED', false),
    'request_delay_days' => (int) env('REVIEW_REQUEST_DELAY_DAYS', 30),
    'request_max_attempts' => (int) env('REVIEW_REQUEST_MAX_ATTEMPTS', 3),
    'request_link_days' => (int) env('REVIEW_REQUEST_LINK_DAYS', 180),
    'default_locale' => env('REVIEW_REQUEST_DEFAULT_LOCALE', 'hr'),
];
