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
        'api_key'       => env('MAILCHIMP_API_KEY'),
        'server_prefix' => env('MAILCHIMP_SERVER_PREFIX'),
        'audience_id'   => env('MAILCHIMP_AUDIENCE_ID'),
        'subscribe_status' => env('MAILCHIMP_SUBSCRIBE_STATUS', 'subscribed'),
        'ecommerce_store_id' => env('MAILCHIMP_ECOMMERCE_STORE_ID', 'antikvarijat-biblos'),
        'ecommerce_store_name' => env('MAILCHIMP_ECOMMERCE_STORE_NAME', 'Antikvarijat Biblos'),
        'ecommerce_currency_code' => env('MAILCHIMP_ECOMMERCE_CURRENCY_CODE', 'EUR'),
        'abandoned_cart_tag' => env('MAILCHIMP_TAG_ABANDONED_CART', 'abandoned_cart'),
        'customer_tag' => env('MAILCHIMP_TAG_CUSTOMER', 'customer'),
    ],

    /*******************************************************************************
     *                              END Copyright : AGmedia                         *
     *******************************************************************************/

];
