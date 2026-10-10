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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'stripe' => [
        'publishable' => env('STRIPE_PUBLISHABLE_KEY'),
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // Revalidacion bajo demanda de la ficha publica (Next ISR). URL completa
    // del route handler (https://<sitio>/api/revalidate) y el mismo token que
    // REVALIDATE_TOKEN del frontend. Sin alguno de los dos, no se llama.
    'storefront' => [
        'revalidate_url' => env('STOREFRONT_REVALIDATE_URL'),
        'revalidate_token' => env('STOREFRONT_REVALIDATE_TOKEN'),
        'revalidate_timeout' => (int) env('STOREFRONT_REVALIDATE_TIMEOUT', 5),
    ],

];
