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

    /*
    |--------------------------------------------------------------------------
    | GL Account Mappings
    |--------------------------------------------------------------------------
    |
    | IV-010: Default GL account codes for inventory movements.
    | These can be overridden per warehouse in the warehouse_accounts config.
    |
    */
    // Refactor ciclo (Patron 3, decision D-b): antes estos codigos con guion
    // (115-001, 205-001, 500-001, 680-001) NO existian en el catalogo mexicano
    // (CatalogoCuentasMexicanoSeeder usa codigos de 4 digitos), asi que TODO posting
    // GL de inventario reventaba con "Required GL accounts not found". Se mapean a las
    // cuentas REALES verificadas en el chart (todas is_postable=1):
    //   1108 Almacen (activo) | 5101 Costo de Ventas | 2101 Proveedores.
    // La variacion de inventario (mermas/sobrantes) va a 5101 (Costo de Ventas) por
    // convencion; si el cliente quiere una cuenta de merma dedicada, se agrega al chart
    // y se repunta aqui.
    'gl_accounts' => [
        'inventory_asset' => env('INVENTORY_GL_ASSET', '1108'),          // Almacen (activo)
        'inventory_accrual' => env('INVENTORY_GL_ACCRUAL', '2101'),      // Proveedores (contrapartida entrada)
        'cogs' => env('INVENTORY_GL_COGS', '5101'),                      // Costo de Ventas
        'inventory_variance' => env('INVENTORY_GL_VARIANCE', '5101'),    // Variacion -> Costo de Ventas
    ],

    /*
    |--------------------------------------------------------------------------
    | Warehouse-Specific GL Accounts
    |--------------------------------------------------------------------------
    |
    | IV-010: Override GL accounts for specific warehouses (multi-entity).
    | Format: warehouse_id => ['account_type' => 'account_code']
    |
    | Example:
    | 'warehouse_accounts' => [
    |     1 => ['inventory_asset' => '115-010'],  // Warehouse 1 custom account
    |     2 => ['inventory_asset' => '115-020'],  // Warehouse 2 custom account
    | ],
    |
    */
    'warehouse_accounts' => env('INVENTORY_WAREHOUSE_ACCOUNTS', []),

    /*
    |--------------------------------------------------------------------------
    | GL Posting Retry Configuration
    |--------------------------------------------------------------------------
    |
    | IV-010: Automatic retry mechanism for failed GL postings.
    | Retries follow exponential backoff strategy.
    |
    */
    'retry' => [
        /*
        | Enable automatic retry for failed GL postings
        */
        'enabled' => env('INVENTORY_RETRY_ENABLED', true),

        /*
        | Maximum number of retry attempts before giving up
        */
        'max_attempts' => env('INVENTORY_RETRY_MAX_ATTEMPTS', 3),

        /*
        | Backoff delay in seconds for each retry attempt
        | [1st retry, 2nd retry, 3rd retry, ...]
        | Default: 1 min, 5 min, 15 min
        */
        'backoff_seconds' => [
            env('INVENTORY_RETRY_BACKOFF_1', 60),    // 1 minute
            env('INVENTORY_RETRY_BACKOFF_2', 300),   // 5 minutes
            env('INVENTORY_RETRY_BACKOFF_3', 900),   // 15 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | GL Posting Notifications
    |--------------------------------------------------------------------------
    |
    | IV-010: Email notifications for GL posting failures after max retries.
    |
    */
    'notifications' => [
        'failed_posting_emails' => env('INVENTORY_FAILED_POSTING_EMAILS', 'accounting@example.com'),
    ],
];
