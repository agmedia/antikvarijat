<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    'recaptcha' => [
        'sitekey'    => env('GOOGLE_RECAPTCHA_SITE_KEY'),
        'secret'     => env('GOOGLE_RECAPTCHA_SECRET_KEY'),
        'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        'bypass_local' => env('RECAPTCHA_BYPASS_LOCAL', true),
    ],

    'mailchimp' => [
        'api_key' => env('MAILCHIMP_API_KEY'),
        'server_prefix' => env('MAILCHIMP_SERVER_PREFIX'),
        'audience_id' => env('MAILCHIMP_AUDIENCE_ID'),
        'subscribe_status' => env('MAILCHIMP_SUBSCRIBE_STATUS', 'subscribed'),
        'ecommerce_store_id' => env('MAILCHIMP_ECOMMERCE_STORE_ID', 'antikvarijat-biblos'),
        'ecommerce_store_name' => env('MAILCHIMP_ECOMMERCE_STORE_NAME', 'Antikvarijat Biblos'),
        'ecommerce_currency_code' => env('MAILCHIMP_ECOMMERCE_CURRENCY_CODE', 'EUR'),
        'ecommerce_automations_enabled' => env('MAILCHIMP_ECOMMERCE_AUTOMATIONS_ENABLED', false),
        'storefront_url' => env('MAILCHIMP_STOREFRONT_URL', env('APP_URL')),
    ],

    'google_translate' => [
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
        'source' => env('GOOGLE_TRANSLATE_SOURCE', 'hr'),
        'target' => env('GOOGLE_TRANSLATE_TARGET', 'en'),
        'use_public_endpoint' => env('GOOGLE_TRANSLATE_USE_PUBLIC_ENDPOINT', true),
    ],

    'google_login' => [
        'enabled' => env('GOOGLE_LOGIN_ENABLED', false),
        'client_id' => env('GOOGLE_LOGIN_CLIENT_ID'),
        'client_secret' => env('GOOGLE_LOGIN_CLIENT_SECRET'),
    ],

    'google_reviews' => [
        'rating' => env('GOOGLE_REVIEWS_RATING'),
        'review_count' => env('GOOGLE_REVIEWS_COUNT'),
    ],

    'vialibri' => [
        'access_code' => env('VIALIBRI_ACCESS_CODE'),
    ],

    'gls' => [
        'client_number' => env('GLS_CLIENT_NUMBER', 380006286),
        'username' => env('GLS_USERNAME', 'info@antikvarijat-biblos.hr'),
        'password' => env('GLS_PASSWORD', 'Frankrsto2013'),
        'wsdl' => env('GLS_WSDL', 'https://api.mygls.hr/ParcelService.svc?singleWsdl'),
        'language' => env('GLS_LANGUAGE', 'HR'),
        'tracking_url' => env('GLS_TRACKING_URL', 'https://gls-group.com/HR/hr/pracenje-posiljke/'),
    ],

    'boxnow' => [
        'base_url' => env('BOXNOW_API_URL', 'https://api-production.boxnow.hr/api/v1'),
        'client_id' => env('BOXNOW_CLIENT_ID'),
        'client_secret' => env('BOXNOW_CLIENT_SECRET'),
        'api_partner_id' => env('BOXNOW_API_PARTNER_ID'),
        'widget_partner_id' => env('BOXNOW_WIDGET_PARTNER_ID', 123),
        'warehouse_location_id' => env('BOXNOW_WAREHOUSE_LOCATION_ID'),
        'origin_name' => env('BOXNOW_ORIGIN_NAME', env('MAIL_FROM_NAME', 'Antikvarijat Biblos')),
        'origin_email' => env('BOXNOW_ORIGIN_EMAIL', env('MAIL_FROM_ADDRESS')),
        'origin_phone' => env('BOXNOW_ORIGIN_PHONE'),
        'tracking_url' => env('BOXNOW_TRACKING_URL', 'https://track.boxnow.hr/en?track={parcel}'),
        'allow_return' => env('BOXNOW_ALLOW_RETURN', true),
    ],

    // Optional bootstrap values. Once saved in the administration, Wolt
    // settings are read from the database and secrets remain encrypted there.
    'wolt' => [
        'module_enabled' => env('WOLT_DRIVE_ENABLED', false),
        'environment' => env('WOLT_DRIVE_ENVIRONMENT', 'development'),
        'api_key' => env('WOLT_DRIVE_API_KEY'),
        'webhook_secret' => env('WOLT_DRIVE_WEBHOOK_SECRET'),
        'merchant_id' => env('WOLT_DRIVE_MERCHANT_ID'),
        'venue_id' => env('WOLT_DRIVE_VENUE_ID'),
        'availability_cache_seconds' => env('WOLT_DRIVE_CACHE_SECONDS', 300),
        'preparation_time_minutes' => env('WOLT_DRIVE_PREPARATION_MINUTES', 30),
        'request_timeout_seconds' => env('WOLT_DRIVE_TIMEOUT_SECONDS', 20),
        'fallback_weight_grams' => env('WOLT_DRIVE_FALLBACK_WEIGHT_GRAMS', 500),
        'cod_enabled' => env('WOLT_DRIVE_COD_ENABLED', false),
        'pricing_mode' => env('WOLT_DRIVE_PRICING_MODE', 'fixed'),
        'quote_markup_percent' => env('WOLT_DRIVE_QUOTE_MARKUP_PERCENT', 0),
        'max_quote_price' => env('WOLT_DRIVE_MAX_QUOTE_PRICE', 0),
        'support_url' => env('WOLT_DRIVE_SUPPORT_URL', env('APP_URL')),
        'support_email' => env('WOLT_DRIVE_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS')),
        'support_phone' => env('WOLT_DRIVE_SUPPORT_PHONE'),
    ],

    /*******************************************************************************
     *                              END Copyright : AGmedia                         *
     *******************************************************************************/

];
