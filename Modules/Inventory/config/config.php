<?php

return [
    'name' => 'Inventory',

    /*
    |--------------------------------------------------------------------------
    | Reorder Quantity Multiplier
    |--------------------------------------------------------------------------
    |
    | IV-M002: When calculating suggested order quantity for stock below
    | reorder point, this multiplier determines the target quantity.
    | For example, 2 means order enough to reach 2x the reorder point.
    |
    */
    'reorder_quantity_multiplier' => env('INVENTORY_REORDER_MULTIPLIER', 2),
];
