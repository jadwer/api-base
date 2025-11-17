<?php

return [
    'name' => 'Finance',

    /*
    |--------------------------------------------------------------------------
    | Credit Management Configuration
    |--------------------------------------------------------------------------
    */
    'min_payment_score' => env('FINANCE_MIN_PAYMENT_SCORE', 60),

    /*
    |--------------------------------------------------------------------------
    | Credit Hold Configuration (FI-M003)
    |--------------------------------------------------------------------------
    |
    | credit_hold_days: Number of days an invoice can be overdue before
    | automatic credit hold is placed on the customer.
    |
    | Default: 60 days
    */
    'credit_hold_days' => env('FINANCE_CREDIT_HOLD_DAYS', 60),
];
