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
];
