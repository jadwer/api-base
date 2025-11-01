<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 3.5 Performance Optimization - Stage 2
     * Adds critical indexes to improve query performance across all modules.
     * Silently skips indexes that already exist.
     */
    public function up(): void
    {
        // Helper to add index safely
        $addIndex = function(string $table, $columns, string $name) {
            try {
                Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                    $table->index($columns, $name);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                // Silently skip if:
                // - Index already exists (error 1061 - Duplicate key name)
                // - Column doesn't exist (error 1072 - Key column ... doesn't exist)
                // - Table doesn't exist (error 1146 - Table ... doesn't exist)
                $msg = $e->getMessage();
                if (!str_contains($msg, 'Duplicate key name') &&
                    !str_contains($msg, "doesn't exist in table") &&
                    !str_contains($msg, "doesn't exist") &&
                    !str_contains($msg, "no such table") &&
                    !str_contains($msg, "no such column") &&
                    !str_contains($msg, "syntax error")) {
                    throw $e;
                }
            }
        };

        // ACCOUNTING MODULE
        $addIndex('fiscal_periods', 'status', 'idx_fiscal_periods_status');
        $addIndex('fiscal_periods', 'year', 'idx_fiscal_periods_year');
        $addIndex('fiscal_periods', 'month', 'idx_fiscal_periods_month');
        $addIndex('fiscal_periods', ['year', 'month'], 'idx_fiscal_periods_year_month');
        $addIndex('fiscal_periods', ['start_date', 'end_date'], 'idx_fiscal_periods_dates');

        $addIndex('journal_entries', 'fiscal_period_id', 'idx_journal_entries_fiscal_period_id');
        $addIndex('journal_entries', 'status', 'idx_journal_entries_status');
        $addIndex('journal_entries', 'date', 'idx_journal_entries_date');
        $addIndex('journal_entries', ['fiscal_period_id', 'status'], 'idx_journal_entries_period_status');

        $addIndex('journal_lines', 'journal_entry_id', 'idx_journal_lines_entry_id');
        $addIndex('journal_lines', 'account_id', 'idx_journal_lines_account_id');
        $addIndex('journal_lines', ['journal_entry_id', 'account_id'], 'idx_journal_lines_entry_account');

        $addIndex('accounts', 'status', 'idx_accounts_status');
        $addIndex('accounts', 'account_type', 'idx_accounts_type');
        $addIndex('accounts', 'parent_id', 'idx_accounts_parent_id');
        $addIndex('accounts', ['parent_id', 'status'], 'idx_accounts_parent_status');

        // FINANCE MODULE
        $addIndex('ar_invoices', 'contact_id', 'idx_ar_invoices_contact_id');
        $addIndex('ar_invoices', 'status', 'idx_ar_invoices_status');
        $addIndex('ar_invoices', 'invoice_date', 'idx_ar_invoices_invoice_date');
        $addIndex('ar_invoices', 'due_date', 'idx_ar_invoices_due_date');
        $addIndex('ar_invoices', ['contact_id', 'status'], 'idx_ar_invoices_contact_status');
        $addIndex('ar_invoices', ['due_date', 'status'], 'idx_ar_invoices_due_status');
        $addIndex('ar_invoices', 'sales_order_id', 'idx_ar_invoices_sales_order_id');
        $addIndex('ar_invoices', 'journal_entry_id', 'idx_ar_invoices_journal_entry_id');
        $addIndex('ar_invoices', 'gl_posting_status', 'idx_ar_invoices_gl_posting_status');

        $addIndex('ap_invoices', 'contact_id', 'idx_ap_invoices_contact_id');
        $addIndex('ap_invoices', 'status', 'idx_ap_invoices_status');
        $addIndex('ap_invoices', 'invoice_date', 'idx_ap_invoices_invoice_date');
        $addIndex('ap_invoices', 'due_date', 'idx_ap_invoices_due_date');
        $addIndex('ap_invoices', ['contact_id', 'status'], 'idx_ap_invoices_contact_status');
        $addIndex('ap_invoices', ['due_date', 'status'], 'idx_ap_invoices_due_status');
        $addIndex('ap_invoices', 'purchase_order_id', 'idx_ap_invoices_purchase_order_id');
        $addIndex('ap_invoices', 'journal_entry_id', 'idx_ap_invoices_journal_entry_id');
        $addIndex('ap_invoices', 'gl_posting_status', 'idx_ap_invoices_gl_posting_status');

        $addIndex('payments', 'contact_id', 'idx_payments_contact_id');
        $addIndex('payments', 'payment_date', 'idx_payments_payment_date');
        $addIndex('payments', 'status', 'idx_payments_status');
        $addIndex('payments', 'bank_account_id', 'idx_payments_bank_account_id');
        $addIndex('payments', ['contact_id', 'payment_date'], 'idx_payments_contact_date');

        $addIndex('ap_payments', 'contact_id', 'idx_ap_payments_contact_id');
        $addIndex('ap_payments', 'payment_date', 'idx_ap_payments_payment_date');
        $addIndex('ap_payments', 'status', 'idx_ap_payments_status');
        $addIndex('ap_payments', 'bank_account_id', 'idx_ap_payments_bank_account_id');
        $addIndex('ap_payments', ['contact_id', 'payment_date'], 'idx_ap_payments_contact_date');

        $addIndex('payment_applications', 'payment_id', 'idx_payment_applications_payment_id');
        $addIndex('payment_applications', 'invoice_id', 'idx_payment_applications_invoice_id');

        $addIndex('ap_payment_applications', 'ap_payment_id', 'idx_ap_payment_applications_payment_id');
        $addIndex('ap_payment_applications', 'ap_invoice_id', 'idx_ap_payment_applications_invoice_id');

        $addIndex('bank_accounts', 'status', 'idx_bank_accounts_status');
        $addIndex('bank_accounts', 'is_active', 'idx_bank_accounts_is_active');

        $addIndex('bank_transactions', 'bank_account_id', 'idx_bank_transactions_bank_account_id');
        $addIndex('bank_transactions', 'transaction_date', 'idx_bank_transactions_date');
        $addIndex('bank_transactions', 'status', 'idx_bank_transactions_status');
        $addIndex('bank_transactions', ['bank_account_id', 'transaction_date'], 'idx_bank_transactions_account_date');

        // CONTACTS MODULE
        $addIndex('contacts', 'status', 'idx_contacts_status');
        $addIndex('contacts', 'is_customer', 'idx_contacts_is_customer');
        $addIndex('contacts', 'is_supplier', 'idx_contacts_is_supplier');
        $addIndex('contacts', ['is_customer', 'status'], 'idx_contacts_customer_status');
        $addIndex('contacts', ['is_supplier', 'status'], 'idx_contacts_supplier_status');
        $addIndex('contacts', 'contact_type', 'idx_contacts_contact_type');
        $addIndex('contacts', 'classification', 'idx_contacts_classification');

        $addIndex('contact_people', 'contact_id', 'idx_contact_people_contact_id');
        $addIndex('contact_people', 'is_primary', 'idx_contact_people_is_primary');
        $addIndex('contact_people', ['contact_id', 'is_primary'], 'idx_contact_people_contact_primary');

        $addIndex('contact_addresses', 'contact_id', 'idx_contact_addresses_contact_id');
        $addIndex('contact_addresses', 'address_type', 'idx_contact_addresses_address_type');
        $addIndex('contact_addresses', 'is_default', 'idx_contact_addresses_is_default');
        $addIndex('contact_addresses', ['contact_id', 'is_default'], 'idx_contact_addresses_contact_default');

        $addIndex('contact_documents', 'contact_id', 'idx_contact_documents_contact_id');
        $addIndex('contact_documents', 'document_type', 'idx_contact_documents_document_type');
        $addIndex('contact_documents', 'is_verified', 'idx_contact_documents_is_verified');
        $addIndex('contact_documents', 'expires_at', 'idx_contact_documents_expires_at');

        // SALES MODULE
        $addIndex('sales_orders', 'contact_id', 'idx_sales_orders_contact_id');
        $addIndex('sales_orders', 'status', 'idx_sales_orders_status');
        $addIndex('sales_orders', 'order_date', 'idx_sales_orders_order_date');
        $addIndex('sales_orders', ['contact_id', 'status'], 'idx_sales_orders_contact_status');
        $addIndex('sales_orders', 'invoicing_status', 'idx_sales_orders_invoicing_status');
        $addIndex('sales_orders', 'financial_status', 'idx_sales_orders_financial_status');

        $addIndex('sales_order_items', 'sales_order_id', 'idx_sales_order_items_order_id');
        $addIndex('sales_order_items', 'product_id', 'idx_sales_order_items_product_id');

        // PURCHASE MODULE
        $addIndex('purchase_orders', 'contact_id', 'idx_purchase_orders_contact_id');
        $addIndex('purchase_orders', 'status', 'idx_purchase_orders_status');
        $addIndex('purchase_orders', 'order_date', 'idx_purchase_orders_order_date');
        $addIndex('purchase_orders', ['contact_id', 'status'], 'idx_purchase_orders_contact_status');
        $addIndex('purchase_orders', 'receiving_status', 'idx_purchase_orders_receiving_status');
        $addIndex('purchase_orders', 'invoicing_status', 'idx_purchase_orders_invoicing_status');

        $addIndex('purchase_order_items', 'purchase_order_id', 'idx_purchase_order_items_order_id');
        $addIndex('purchase_order_items', 'product_id', 'idx_purchase_order_items_product_id');

        // PRODUCT MODULE (only foreign keys - no status/is_active columns)
        $addIndex('products', 'category_id', 'idx_products_category_id');
        $addIndex('products', 'brand_id', 'idx_products_brand_id');
        $addIndex('products', 'unit_id', 'idx_products_unit_id');

        // INVENTORY MODULE
        $addIndex('warehouses', 'is_active', 'idx_warehouses_is_active');
        $addIndex('warehouses', 'warehouse_type', 'idx_warehouses_warehouse_type');

        $addIndex('stock', 'product_id', 'idx_stock_product_id');
        $addIndex('stock', 'warehouse_id', 'idx_stock_warehouse_id');
        $addIndex('stock', ['product_id', 'warehouse_id'], 'idx_stock_product_warehouse');
        $addIndex('stock', 'status', 'idx_stock_status');

        $addIndex('inventory_movements', 'product_id', 'idx_inventory_movements_product_id');
        $addIndex('inventory_movements', 'warehouse_id', 'idx_inventory_movements_warehouse_id');
        $addIndex('inventory_movements', 'movement_type', 'idx_inventory_movements_movement_type');
        $addIndex('inventory_movements', 'movement_date', 'idx_inventory_movements_movement_date');
        $addIndex('inventory_movements', ['product_id', 'movement_date'], 'idx_inventory_movements_product_date');
        $addIndex('inventory_movements', 'source_type', 'idx_inventory_movements_source_type');
        $addIndex('inventory_movements', 'source_id', 'idx_inventory_movements_source_id');

        $addIndex('product_batches', 'product_id', 'idx_product_batches_product_id');
        $addIndex('product_batches', 'warehouse_id', 'idx_product_batches_warehouse_id');
        $addIndex('product_batches', 'expiration_date', 'idx_product_batches_expiration_date');
        $addIndex('product_batches', 'status', 'idx_product_batches_status');

        // ECOMMERCE MODULE
        $addIndex('shopping_carts', 'user_id', 'idx_shopping_carts_user_id');
        $addIndex('shopping_carts', 'status', 'idx_shopping_carts_status');
        $addIndex('shopping_carts', 'session_id', 'idx_shopping_carts_session_id');
        $addIndex('shopping_carts', ['user_id', 'status'], 'idx_shopping_carts_user_status');

        $addIndex('shopping_cart_items', 'shopping_cart_id', 'idx_shopping_cart_items_cart_id');
        $addIndex('shopping_cart_items', 'product_id', 'idx_shopping_cart_items_product_id');

        $addIndex('coupons', 'status', 'idx_coupons_status');
        $addIndex('coupons', 'valid_from', 'idx_coupons_valid_from');
        $addIndex('coupons', 'valid_until', 'idx_coupons_valid_until');
        $addIndex('coupons', ['valid_from', 'valid_until'], 'idx_coupons_validity_dates');

        // SYSTEM TABLES
        $addIndex('activity_log', 'subject_type', 'idx_activity_log_subject_type');
        $addIndex('activity_log', 'subject_id', 'idx_activity_log_subject_id');
        $addIndex('activity_log', 'causer_type', 'idx_activity_log_causer_type');
        $addIndex('activity_log', 'causer_id', 'idx_activity_log_causer_id');
        $addIndex('activity_log', 'created_at', 'idx_activity_log_created_at');

        $addIndex('critical_action_logs', 'user_id', 'idx_critical_action_logs_user_id');
        $addIndex('critical_action_logs', 'action_type', 'idx_critical_action_logs_action_type');
        $addIndex('critical_action_logs', 'entity_type', 'idx_critical_action_logs_entity_type');
        $addIndex('critical_action_logs', 'entity_id', 'idx_critical_action_logs_entity_id');
        $addIndex('critical_action_logs', 'created_at', 'idx_critical_action_logs_created_at');
    }

    public function down(): void
    {
        // Drop indexes in reverse order (silently ignore if not exists)
        $dropIndex = function(string $table, string $name) {
            try {
                Schema::table($table, function (Blueprint $table) use ($name) {
                    $table->dropIndex($name);
                });
            } catch (\Exception $e) {
                // Silently skip if index doesn't exist
            }
        };

        $dropIndex('critical_action_logs', 'idx_critical_action_logs_created_at');
        $dropIndex('critical_action_logs', 'idx_critical_action_logs_entity_id');
        $dropIndex('critical_action_logs', 'idx_critical_action_logs_entity_type');
        $dropIndex('critical_action_logs', 'idx_critical_action_logs_action_type');
        $dropIndex('critical_action_logs', 'idx_critical_action_logs_user_id');

        $dropIndex('activity_log', 'idx_activity_log_created_at');
        $dropIndex('activity_log', 'idx_activity_log_causer_id');
        $dropIndex('activity_log', 'idx_activity_log_causer_type');
        $dropIndex('activity_log', 'idx_activity_log_subject_id');
        $dropIndex('activity_log', 'idx_activity_log_subject_type');

        $dropIndex('coupons', 'idx_coupons_validity_dates');
        $dropIndex('coupons', 'idx_coupons_valid_until');
        $dropIndex('coupons', 'idx_coupons_valid_from');
        $dropIndex('coupons', 'idx_coupons_status');

        $dropIndex('shopping_cart_items', 'idx_shopping_cart_items_product_id');
        $dropIndex('shopping_cart_items', 'idx_shopping_cart_items_cart_id');

        $dropIndex('shopping_carts', 'idx_shopping_carts_user_status');
        $dropIndex('shopping_carts', 'idx_shopping_carts_session_id');
        $dropIndex('shopping_carts', 'idx_shopping_carts_status');
        $dropIndex('shopping_carts', 'idx_shopping_carts_user_id');

        $dropIndex('product_batches', 'idx_product_batches_status');
        $dropIndex('product_batches', 'idx_product_batches_expiration_date');
        $dropIndex('product_batches', 'idx_product_batches_warehouse_id');
        $dropIndex('product_batches', 'idx_product_batches_product_id');

        $dropIndex('inventory_movements', 'idx_inventory_movements_source_id');
        $dropIndex('inventory_movements', 'idx_inventory_movements_source_type');
        $dropIndex('inventory_movements', 'idx_inventory_movements_product_date');
        $dropIndex('inventory_movements', 'idx_inventory_movements_movement_date');
        $dropIndex('inventory_movements', 'idx_inventory_movements_movement_type');
        $dropIndex('inventory_movements', 'idx_inventory_movements_warehouse_id');
        $dropIndex('inventory_movements', 'idx_inventory_movements_product_id');

        $dropIndex('stock', 'idx_stock_status');
        $dropIndex('stock', 'idx_stock_product_warehouse');
        $dropIndex('stock', 'idx_stock_warehouse_id');
        $dropIndex('stock', 'idx_stock_product_id');

        $dropIndex('warehouses', 'idx_warehouses_warehouse_type');
        $dropIndex('warehouses', 'idx_warehouses_is_active');

        $dropIndex('products', 'idx_products_unit_id');
        $dropIndex('products', 'idx_products_brand_id');
        $dropIndex('products', 'idx_products_category_id');

        $dropIndex('purchase_order_items', 'idx_purchase_order_items_product_id');
        $dropIndex('purchase_order_items', 'idx_purchase_order_items_order_id');

        $dropIndex('purchase_orders', 'idx_purchase_orders_invoicing_status');
        $dropIndex('purchase_orders', 'idx_purchase_orders_receiving_status');
        $dropIndex('purchase_orders', 'idx_purchase_orders_contact_status');
        $dropIndex('purchase_orders', 'idx_purchase_orders_order_date');
        $dropIndex('purchase_orders', 'idx_purchase_orders_status');
        $dropIndex('purchase_orders', 'idx_purchase_orders_contact_id');

        $dropIndex('sales_order_items', 'idx_sales_order_items_product_id');
        $dropIndex('sales_order_items', 'idx_sales_order_items_order_id');

        $dropIndex('sales_orders', 'idx_sales_orders_financial_status');
        $dropIndex('sales_orders', 'idx_sales_orders_invoicing_status');
        $dropIndex('sales_orders', 'idx_sales_orders_contact_status');
        $dropIndex('sales_orders', 'idx_sales_orders_order_date');
        $dropIndex('sales_orders', 'idx_sales_orders_status');
        $dropIndex('sales_orders', 'idx_sales_orders_contact_id');

        $dropIndex('contact_documents', 'idx_contact_documents_expires_at');
        $dropIndex('contact_documents', 'idx_contact_documents_is_verified');
        $dropIndex('contact_documents', 'idx_contact_documents_document_type');
        $dropIndex('contact_documents', 'idx_contact_documents_contact_id');

        $dropIndex('contact_addresses', 'idx_contact_addresses_contact_default');
        $dropIndex('contact_addresses', 'idx_contact_addresses_is_default');
        $dropIndex('contact_addresses', 'idx_contact_addresses_address_type');
        $dropIndex('contact_addresses', 'idx_contact_addresses_contact_id');

        $dropIndex('contact_people', 'idx_contact_people_contact_primary');
        $dropIndex('contact_people', 'idx_contact_people_is_primary');
        $dropIndex('contact_people', 'idx_contact_people_contact_id');

        $dropIndex('contacts', 'idx_contacts_classification');
        $dropIndex('contacts', 'idx_contacts_contact_type');
        $dropIndex('contacts', 'idx_contacts_supplier_status');
        $dropIndex('contacts', 'idx_contacts_customer_status');
        $dropIndex('contacts', 'idx_contacts_is_supplier');
        $dropIndex('contacts', 'idx_contacts_is_customer');
        $dropIndex('contacts', 'idx_contacts_status');

        $dropIndex('bank_transactions', 'idx_bank_transactions_account_date');
        $dropIndex('bank_transactions', 'idx_bank_transactions_status');
        $dropIndex('bank_transactions', 'idx_bank_transactions_date');
        $dropIndex('bank_transactions', 'idx_bank_transactions_bank_account_id');

        $dropIndex('bank_accounts', 'idx_bank_accounts_is_active');
        $dropIndex('bank_accounts', 'idx_bank_accounts_status');

        $dropIndex('ap_payment_applications', 'idx_ap_payment_applications_invoice_id');
        $dropIndex('ap_payment_applications', 'idx_ap_payment_applications_payment_id');

        $dropIndex('payment_applications', 'idx_payment_applications_invoice_id');
        $dropIndex('payment_applications', 'idx_payment_applications_payment_id');

        $dropIndex('ap_payments', 'idx_ap_payments_contact_date');
        $dropIndex('ap_payments', 'idx_ap_payments_bank_account_id');
        $dropIndex('ap_payments', 'idx_ap_payments_status');
        $dropIndex('ap_payments', 'idx_ap_payments_payment_date');
        $dropIndex('ap_payments', 'idx_ap_payments_contact_id');

        $dropIndex('payments', 'idx_payments_contact_date');
        $dropIndex('payments', 'idx_payments_bank_account_id');
        $dropIndex('payments', 'idx_payments_status');
        $dropIndex('payments', 'idx_payments_payment_date');
        $dropIndex('payments', 'idx_payments_contact_id');

        $dropIndex('ap_invoices', 'idx_ap_invoices_gl_posting_status');
        $dropIndex('ap_invoices', 'idx_ap_invoices_journal_entry_id');
        $dropIndex('ap_invoices', 'idx_ap_invoices_purchase_order_id');
        $dropIndex('ap_invoices', 'idx_ap_invoices_due_status');
        $dropIndex('ap_invoices', 'idx_ap_invoices_contact_status');
        $dropIndex('ap_invoices', 'idx_ap_invoices_due_date');
        $dropIndex('ap_invoices', 'idx_ap_invoices_invoice_date');
        $dropIndex('ap_invoices', 'idx_ap_invoices_status');
        $dropIndex('ap_invoices', 'idx_ap_invoices_contact_id');

        $dropIndex('ar_invoices', 'idx_ar_invoices_gl_posting_status');
        $dropIndex('ar_invoices', 'idx_ar_invoices_journal_entry_id');
        $dropIndex('ar_invoices', 'idx_ar_invoices_sales_order_id');
        $dropIndex('ar_invoices', 'idx_ar_invoices_due_status');
        $dropIndex('ar_invoices', 'idx_ar_invoices_contact_status');
        $dropIndex('ar_invoices', 'idx_ar_invoices_due_date');
        $dropIndex('ar_invoices', 'idx_ar_invoices_invoice_date');
        $dropIndex('ar_invoices', 'idx_ar_invoices_status');
        $dropIndex('ar_invoices', 'idx_ar_invoices_contact_id');

        $dropIndex('accounts', 'idx_accounts_parent_status');
        $dropIndex('accounts', 'idx_accounts_parent_id');
        $dropIndex('accounts', 'idx_accounts_type');
        $dropIndex('accounts', 'idx_accounts_status');

        $dropIndex('journal_lines', 'idx_journal_lines_entry_account');
        $dropIndex('journal_lines', 'idx_journal_lines_account_id');
        $dropIndex('journal_lines', 'idx_journal_lines_entry_id');

        $dropIndex('journal_entries', 'idx_journal_entries_period_status');
        $dropIndex('journal_entries', 'idx_journal_entries_date');
        $dropIndex('journal_entries', 'idx_journal_entries_status');
        $dropIndex('journal_entries', 'idx_journal_entries_fiscal_period_id');

        $dropIndex('fiscal_periods', 'idx_fiscal_periods_dates');
        $dropIndex('fiscal_periods', 'idx_fiscal_periods_year_month');
        $dropIndex('fiscal_periods', 'idx_fiscal_periods_month');
        $dropIndex('fiscal_periods', 'idx_fiscal_periods_year');
        $dropIndex('fiscal_periods', 'idx_fiscal_periods_status');
    }
};
