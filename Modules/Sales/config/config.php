<?php

return [
    'name' => 'Sales',

    /*
    |--------------------------------------------------------------------------
    | Auto Invoice on Delivery
    |--------------------------------------------------------------------------
    |
    | SA-M001: When enabled, automatically creates an AR Invoice when a Sales
    | Order status changes to 'delivered'. Disable this to require manual
    | invoice generation.
    |
    */
    'auto_invoice_on_delivery' => env('SALES_AUTO_INVOICE', true),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for email notifications in the Sales module.
    |
    */
    'notifications' => [
        /*
         * Email address to receive admin notifications when a quote is converted.
         * Set to null to disable admin notifications.
         */
        'quote_converted_admin_email' => env('SALES_QUOTE_CONVERTED_ADMIN_EMAIL', null),

        /*
         * Email address to receive admin notifications when a quote is sent.
         * Set to null to disable admin notifications.
         */
        'quote_sent_admin_email' => env('SALES_QUOTE_SENT_ADMIN_EMAIL', null),
    ],
];
