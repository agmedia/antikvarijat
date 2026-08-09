<?php

return [
    'emails_enabled' => env('WISHLIST_EMAILS_ENABLED', env('APP_ENV') === 'production'),
    'notification_batch_size' => (int) env('WISHLIST_NOTIFICATION_BATCH_SIZE', 50),
];
