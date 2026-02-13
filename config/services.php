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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    */

    'tap' => [
        'secret_key' => env('TAP_SECRET_KEY'),
        'publishable_key' => env('TAP_PUBLISHABLE_KEY'),
        'is_live' => env('TAP_IS_LIVE', false),
    ],

    'tabby' => [
        'secret_key' => env('TABBY_SECRET_KEY'),
        'public_key' => env('TABBY_PUBLIC_KEY'),
        'merchant_code' => env('TABBY_MERCHANT_CODE'),
        'is_live' => env('TABBY_IS_LIVE', false),
    ],

    'tamara' => [
        'api_token' => env('TAMARA_API_TOKEN'),
        'merchant_url' => env('TAMARA_MERCHANT_URL'),
        'notification_key' => env('TAMARA_NOTIFICATION_KEY'),
        'is_live' => env('TAMARA_IS_LIVE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aramex Shipping Configuration
    |--------------------------------------------------------------------------
    */

    'aramex' => [
        'username' => env('ARAMEX_USERNAME'),
        'password' => env('ARAMEX_PASSWORD'),
        'account_number' => env('ARAMEX_ACCOUNT_NUMBER'),
        'account_pin' => env('ARAMEX_ACCOUNT_PIN'),
        'account_entity' => env('ARAMEX_ACCOUNT_ENTITY', 'DXB'),
        'account_country_code' => env('ARAMEX_ACCOUNT_COUNTRY_CODE', 'AE'),
        'is_live' => env('ARAMEX_IS_LIVE', false),
        'base_url' => env('ARAMEX_IS_LIVE', false)
            ? 'https://ws.aramex.net/ShippingAPI.V2/'
            : 'https://ws.dev.aramex.net/ShippingAPI.V2/',
    ],

];
