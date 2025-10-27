<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // 1. Fix AR Invoices: customer_id → contact_id + add sales_order_id
        if (Schema::hasTable('ar_invoices') && Schema::hasColumn('ar_invoices', 'customer_id')) {
            if ($driver === 'mysql') {
                // MySQL/MariaDB: Use CHANGE COLUMN
                DB::statement('ALTER TABLE ar_invoices CHANGE COLUMN customer_id contact_id BIGINT UNSIGNED NOT NULL');
            } elseif ($driver === 'sqlite') {
                // SQLite: Rename using Schema builder (works in Laravel 12)
                Schema::table('ar_invoices', function (Blueprint $table) {
                    $table->renameColumn('customer_id', 'contact_id');
                });
            }

            // Add sales_order_id if not exists
            if (!Schema::hasColumn('ar_invoices', 'sales_order_id')) {
                Schema::table('ar_invoices', function (Blueprint $table) {
                    $table->unsignedBigInteger('sales_order_id')->nullable()->after('contact_id');
                    $table->index('sales_order_id');
                    $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('restrict');
                });
            }
        }

        // 2. Fix AP Invoices: supplier_id → contact_id + add purchase_order_id
        if (Schema::hasTable('ap_invoices') && Schema::hasColumn('ap_invoices', 'supplier_id')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE ap_invoices CHANGE COLUMN supplier_id contact_id BIGINT UNSIGNED NOT NULL');
            } elseif ($driver === 'sqlite') {
                Schema::table('ap_invoices', function (Blueprint $table) {
                    $table->renameColumn('supplier_id', 'contact_id');
                });
            }

            // Add purchase_order_id if not exists
            if (!Schema::hasColumn('ap_invoices', 'purchase_order_id')) {
                Schema::table('ap_invoices', function (Blueprint $table) {
                    $table->unsignedBigInteger('purchase_order_id')->nullable()->after('contact_id');
                    $table->index('purchase_order_id');
                    $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('restrict');
                });
            }
        }

        // 3. Fix Payments: customer_id → contact_id
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'customer_id')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE payments CHANGE COLUMN customer_id contact_id BIGINT UNSIGNED NOT NULL');
            } elseif ($driver === 'sqlite') {
                Schema::table('payments', function (Blueprint $table) {
                    $table->renameColumn('customer_id', 'contact_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        // Reverse AR Invoices changes
        if (Schema::hasTable('ar_invoices') && Schema::hasColumn('ar_invoices', 'contact_id')) {
            if (Schema::hasColumn('ar_invoices', 'sales_order_id')) {
                Schema::table('ar_invoices', function (Blueprint $table) {
                    $table->dropForeign(['sales_order_id']);
                    $table->dropColumn('sales_order_id');
                });
            }

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE ar_invoices CHANGE COLUMN contact_id customer_id BIGINT UNSIGNED NOT NULL');
            } elseif ($driver === 'sqlite') {
                Schema::table('ar_invoices', function (Blueprint $table) {
                    $table->renameColumn('contact_id', 'customer_id');
                });
            }
        }

        // Reverse AP Invoices changes
        if (Schema::hasTable('ap_invoices') && Schema::hasColumn('ap_invoices', 'contact_id')) {
            if (Schema::hasColumn('ap_invoices', 'purchase_order_id')) {
                Schema::table('ap_invoices', function (Blueprint $table) {
                    $table->dropForeign(['purchase_order_id']);
                    $table->dropColumn('purchase_order_id');
                });
            }

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE ap_invoices CHANGE COLUMN contact_id supplier_id BIGINT UNSIGNED NOT NULL');
            } elseif ($driver === 'sqlite') {
                Schema::table('ap_invoices', function (Blueprint $table) {
                    $table->renameColumn('contact_id', 'supplier_id');
                });
            }
        }

        // Reverse Payments changes
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'contact_id')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE payments CHANGE COLUMN contact_id customer_id BIGINT UNSIGNED NOT NULL');
            } elseif ($driver === 'sqlite') {
                Schema::table('payments', function (Blueprint $table) {
                    $table->renameColumn('contact_id', 'customer_id');
                });
            }
        }
    }
};
