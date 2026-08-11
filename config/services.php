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

    /*******************************************************************************
     *                              END Copyright : AGmedia                         *
     *******************************************************************************/

];
