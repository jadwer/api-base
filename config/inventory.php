<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inventory GL Account Mappings
    |--------------------------------------------------------------------------
    |
    | This file defines the General Ledger account mappings for inventory
    | movements. These mappings are used to automatically create journal
    | entries when inventory movements occur (IV-010).
    |
    */

    'gl_accounts' => [
        /*
        |--------------------------------------------------------------------------
        | Inventory Asset Account
        |--------------------------------------------------------------------------
        |
        | Main inventory asset account where inventory value is tracked
        | Account Type: Asset
        | Normal Balance: Debit
        |
        */
        'inventory_asset' => '115-001', // Inventario

        /*
        |--------------------------------------------------------------------------
        | Inventory Accrual Account
        |--------------------------------------------------------------------------
        |
        | Used when receiving inventory from suppliers (purchase entry)
        | Account Type: Liability
        | Normal Balance: Credit
        |
        */
        'inventory_accrual' => '205-001', // Proveedores (Accounts Payable)

        /*
        |--------------------------------------------------------------------------
        | Cost of Goods Sold Account
        |--------------------------------------------------------------------------
        |
        | Used when inventory exits (sales, consumption)
        | Account Type: Expense
        | Normal Balance: Debit
        |
        */
        'cogs' => '601-001', // Costo de Ventas

        /*
        |--------------------------------------------------------------------------
        | Inventory Variance Account
        |--------------------------------------------------------------------------
        |
        | Used for inventory adjustments (cycle counts, shrinkage, etc.)
        | Account Type: Other Income/Expense
        | Normal Balance: Can be Debit or Credit
        |
        */
        'inventory_variance' => '801-001', // Otros Gastos

        /*
        |--------------------------------------------------------------------------
        | Work in Process Account (Optional)
        |--------------------------------------------------------------------------
        |
        | For manufacturing/assembly operations
        | Account Type: Asset
        | Normal Balance: Debit
        |
        */
        'work_in_process' => null, // Not used yet

        /*
        |--------------------------------------------------------------------------
        | Warehouse-Specific Accounts (Multi-Entity)
        |--------------------------------------------------------------------------
        |
        | Define warehouse-specific GL accounts for multi-entity scenarios
        | Format: warehouse_id => ['inventory_asset' => 'account_code', ...]
        |
        */
        'warehouse_accounts' => [
            // Example for multi-warehouse, multi-entity:
            // 1 => [
            //     'inventory_asset' => '115-001',
            //     'cogs' => '601-001',
            // ],
            // 2 => [
            //     'inventory_asset' => '115-002',
            //     'cogs' => '601-002',
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | GL Posting Rules
    |--------------------------------------------------------------------------
    |
    | Define which movement types should create GL entries
    |
    */
    'gl_posting' => [
        'enabled' => true,

        'movement_types' => [
            'entry' => [
                'post_to_gl' => true,
                'debit_account' => 'inventory_asset',
                'credit_account' => 'inventory_accrual',
                'description_template' => 'Inventory Entry - Movement #{movement_id}',
            ],
            'exit' => [
                'post_to_gl' => true,
                'debit_account' => 'cogs',
                'credit_account' => 'inventory_asset',
                'description_template' => 'Inventory Exit - Movement #{movement_id}',
            ],
            'adjustment' => [
                'post_to_gl' => true,
                // Positive: DR Inventory Asset, CR Variance
                // Negative: DR Variance, CR Inventory Asset
                'description_template' => 'Inventory Adjustment - Movement #{movement_id}',
            ],
            'transfer' => [
                'post_to_gl' => false, // Internal transfer within same entity
                'description_template' => 'Inventory Transfer - Movement #{movement_id}',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for retrying failed GL postings
    |
    */
    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'backoff_seconds' => [60, 300, 900], // 1 min, 5 min, 15 min
        'notify_on_failure' => true,
        'notification_email' => env('ACCOUNTING_NOTIFICATION_EMAIL', 'accounting@example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Movement Configuration
    |--------------------------------------------------------------------------
    |
    | General settings for inventory movements
    |
    */
    'movements' => [
        'require_cost' => true, // Require cost_per_unit on all movements
        'require_reference' => true, // Require reference_type and reference_id
        'auto_complete' => false, // Auto-complete movements after creation
        'allow_negative_stock' => false, // Allow stock to go negative
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Tracking
    |--------------------------------------------------------------------------
    |
    | Settings for product batch tracking (FEFO, expiration, lot numbers)
    |
    */
    'batches' => [
        'enabled' => true,
        'require_lot_number' => false,
        'require_expiration' => false,
        'fefo_strategy' => true, // First Expired First Out
        'expiration_warning_days' => 30, // Warn X days before expiration
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock Valuation Method
    |--------------------------------------------------------------------------
    |
    | Supported methods: 'fifo', 'lifo', 'average', 'standard'
    |
    */
    'valuation_method' => env('INVENTORY_VALUATION_METHOD', 'fifo'),

    /*
    |--------------------------------------------------------------------------
    | Audit Trail
    |--------------------------------------------------------------------------
    |
    | Settings for inventory audit trail
    |
    */
    'audit' => [
        'track_stock_changes' => true,
        'require_approval_for_adjustments' => false,
        'log_all_movements' => true,
    ],

];
