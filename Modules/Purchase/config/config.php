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
];
