<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Administrator impersonation lifetime
    |--------------------------------------------------------------------------
    |
    | A support session is intentionally short lived. Every request made while
    | impersonating revalidates both the administrator and the customer.
    |
    */
    'ttl_minutes' => (int) env('IMPERSONATION_TTL_MINUTES', 60),
];
