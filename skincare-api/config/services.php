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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bakong' => [
        'token' => env('BAKONG_TOKEN'),
        'account_id' => env('BAKONG_ACCOUNT_ID', 'seyha_chhorn@bkrt'),
        'merchant_name' => env('BAKONG_MERCHANT_NAME', 'Seyha Chhorn'),
        'merchant_city' => env('BAKONG_MERCHANT_CITY', 'Phnom Penh'),
    ],

    'stripe' => [
        // The second argument keeps the existing .env names working while
        // allowing the correctly-spelled names below for all new deploys.
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', env('PUBLISH_STRIPE_KEY')),
        'secret' => env('STRIPE_SECRET_KEY', env('SECRECT_STRIPE_KEY')),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'usd'),
        'merchant_display_name' => env('STRIPE_MERCHANT_DISPLAY_NAME', 'Hinata Skincare'),
    ],

];
