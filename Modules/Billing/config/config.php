<?php

return [
    'name' => 'Billing',

    /*
    |--------------------------------------------------------------------------
    | SW Sapien PAC Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SW Sapien PAC (Proveedor Autorizado de Certificación)
    | for CFDI stamping and cancellation.
    |
    */
    'sw_pac' => [
        'enabled' => env('SW_PAC_ENABLED', false),
        'url' => env('SW_PAC_URL', 'https://services.test.sw.com.mx'),
        'token' => env('SW_PAC_TOKEN', ''),
        'user' => env('SW_PAC_USER', ''),
        'password' => env('SW_PAC_PASSWORD', ''),

        // Timeout in seconds for API requests
        'timeout' => env('SW_PAC_TIMEOUT', 30),

        // Retry configuration
        'retry_attempts' => env('SW_PAC_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('SW_PAC_RETRY_DELAY', 1000), // milliseconds

        // Webhook configuration
        'webhook_secret' => env('SW_PAC_WEBHOOK_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | CFDI Configuration
    |--------------------------------------------------------------------------
    */
    'cfdi' => [
        'version' => '4.0',
        'storage_path' => env('CFDI_STORAGE_PATH', 'cfdi'),
        'xml_path' => env('CFDI_XML_PATH', 'cfdi/xml'),
        'pdf_path' => env('CFDI_PDF_PATH', 'cfdi/pdf'),
        // Zona horaria del lugar de expedicion para la Fecha del CFDI. El SAT
        // exige hora local del emisor; la app puede correr en UTC. Por tenant.
        'timezone' => env('CFDI_TIMEZONE', 'America/Mexico_City'),
    ],
];
