<?php

return [
    'name' => 'Purchase',

    /*
    |--------------------------------------------------------------------------
    | Receiving Tolerance Percentage
    |--------------------------------------------------------------------------
    |
    | PU-005: Maximum tolerance percentage for receiving quantities that
    | exceed the ordered quantity. For example, 5 means receiving up to 105%
    | of the ordered quantity is allowed.
    |
    */
    'receiving_tolerance_percent' => env('PURCHASE_RECEIVING_TOLERANCE', 5),

    /*
    |--------------------------------------------------------------------------
    | Reconciliation Tolerance Percentage
    |--------------------------------------------------------------------------
    |
    | PU-M002: Maximum tolerance percentage for invoice amount variance when
    | reconciling AP Invoices against Purchase Orders. Invoices exceeding this
    | tolerance will be marked with 'discrepancy' status.
    |
    */
    'reconciliation_tolerance_percent' => env('PURCHASE_RECONCILIATION_TOLERANCE', 5),

    /*
    |--------------------------------------------------------------------------
    | Price Variance Warning Percentage
    |--------------------------------------------------------------------------
    |
    | PU-M002: Threshold percentage for unit price variance warnings during
    | reconciliation. Variance above this threshold generates a warning but
    | does not block reconciliation.
    |
    */
    'price_variance_warning_percent' => env('PURCHASE_PRICE_VARIANCE_WARNING', 10),

    /*
    |--------------------------------------------------------------------------
    | Auto-Reconcile Invoices
    |--------------------------------------------------------------------------
    |
    | PU-M002: When enabled, automatically reconciles AP Invoices against
    | their linked Purchase Orders upon invoice creation.
    |
    */
    'auto_reconcile_invoices' => env('PURCHASE_AUTO_RECONCILE', true),
];
