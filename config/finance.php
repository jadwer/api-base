<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Accounts for Finance Operations
    |--------------------------------------------------------------------------
    |
    | These account IDs are used for automatic posting from Finance modules
    | to General Ledger. Configure based on your chart of accounts.
    |
    */

    'default_accounts' => [
        // Assets
        'bank' => 1,           // 1100 - Banco
        'ar_control' => 2,     // 1200 - Clientes (AR Control)
        
        // Liabilities  
        'ap_control' => 3,     // 2100 - Proveedores (AP Control)
        
        // Revenue
        'revenue' => 4,        // 5000 - Ingresos por Ventas
        
        // Expenses
        'expense' => 5,        // 4000 - Gastos Generales
    ],

    /*
    |--------------------------------------------------------------------------
    | Finance Settings
    |--------------------------------------------------------------------------
    */

    'settings' => [
        'currency' => 'MXN',
        'decimal_precision' => 2,
        'auto_post_to_gl' => true,
        'require_balanced_entries' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Account Mappings by Document Type
    |--------------------------------------------------------------------------
    */

    'document_mappings' => [
        'ap_invoice' => [
            'debit_account' => 'expense',    // Gasto
            'credit_account' => 'ap_control' // Proveedores
        ],
        
        'ar_invoice' => [
            'debit_account' => 'ar_control', // Clientes  
            'credit_account' => 'revenue'    // Ingresos
        ],
        
        'ap_payment' => [
            'debit_account' => 'ap_control', // Proveedores
            'credit_account' => 'bank'       // Banco
        ],
        
        'ar_receipt' => [
            'debit_account' => 'bank',       // Banco
            'credit_account' => 'ar_control' // Clientes
        ]
    ]

];