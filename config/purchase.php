<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Purchase Order Approval Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines the approval workflow thresholds and rules for
    | purchase orders. The system uses a three-tier approval structure
    | based on order total amount and additional risk factors.
    |
    */

    'approval' => [
        /*
        |--------------------------------------------------------------------------
        | Approval Thresholds
        |--------------------------------------------------------------------------
        |
        | Define the monetary thresholds for each approval tier.
        | Orders below the minimum threshold don't require approval.
        |
        */
        'thresholds' => [
            // Tier 1: Procurement Manager approval required
            'tier_1' => 50000,

            // Tier 2: Finance Director approval required (in addition to Tier 1)
            'tier_2' => 250000,

            // Tier 3: CFO approval required (in addition to Tier 1 & 2)
            'tier_3' => 1000000,
        ],

        /*
        |--------------------------------------------------------------------------
        | Additional Approval Triggers
        |--------------------------------------------------------------------------
        |
        | Additional conditions that may require approval regardless of amount
        |
        */
        'triggers' => [
            // Require approval for first-time suppliers
            'first_time_supplier' => true,

            // Item value threshold - any single item above this requires approval
            'high_value_item_threshold' => 100000,

            // Require approval for orders with rush/urgent delivery
            'rush_orders' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Approval Roles
        |--------------------------------------------------------------------------
        |
        | Define which roles can approve at each tier level
        |
        */
        'approver_roles' => [
            'tier_1' => ['admin', 'procurement_manager'],
            'tier_2' => ['admin', 'finance_director'],
            'tier_3' => ['admin', 'cfo'],
        ],

        /*
        |--------------------------------------------------------------------------
        | Approval Timeout
        |--------------------------------------------------------------------------
        |
        | Number of days before an approval request is considered expired
        |
        */
        'timeout_days' => 30,

        /*
        |--------------------------------------------------------------------------
        | Auto-Approval Rules
        |--------------------------------------------------------------------------
        |
        | Define conditions where orders are automatically approved
        |
        */
        'auto_approve' => [
            // Auto-approve orders below this amount
            'threshold' => 50000,

            // Auto-approve from trusted suppliers (with X+ successful orders)
            'trusted_supplier_min_orders' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Purchase Order Numbering
    |--------------------------------------------------------------------------
    |
    | Define the format for purchase order numbers
    |
    */
    'numbering' => [
        'prefix' => 'PO',
        'padding' => 6, // PO-000001
        'separator' => '-',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Default values for new purchase orders
    |
    */
    'defaults' => [
        'payment_terms' => 'net_30',
        'delivery_terms' => 'FOB',
        'currency' => 'MXN',
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Integration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic inventory updates
    |
    */
    'inventory' => [
        // Automatically create inventory movements when PO is received
        'auto_receive' => true,

        // Default warehouse for receiving (null = require selection)
        'default_warehouse_id' => null,

        // Create product batches for serialized/batch-tracked items
        'create_batches' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Email notification settings for purchase workflow
    |
    */
    'notifications' => [
        // Notify approvers when approval is required
        'approval_required' => true,

        // Notify requestor when order is approved/rejected
        'approval_status_change' => true,

        // Notify when order is received
        'order_received' => true,

        // Reminder days before approval timeout
        'reminder_days' => [7, 3, 1],
    ],

];
