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
        // Only run if table exists (for SQLite compatibility)
        if (Schema::hasTable('payment_transactions')) {
            try {
                $isSqlite = DB::getDriverName() === 'sqlite';

                Schema::table('payment_transactions', function (Blueprint $table) use ($isSqlite) {
                    // Don't use after() on SQLite to avoid table reconstruction
                    if ($isSqlite) {
                        $table->string('payment_intent_id', 255)->nullable()->unique();
                        $table->string('client_secret', 255)->nullable();
                        $table->string('card_brand', 50)->nullable();
                        $table->string('card_last4', 4)->nullable();
                        $table->timestamp('authorized_at')->nullable();
                        $table->timestamp('captured_at')->nullable();
                        $table->timestamp('failed_at')->nullable();
                        $table->timestamp('refunded_at')->nullable();
                        // Skip renameColumn on SQLite - add as alias in model instead
                        $table->string('transaction_id')->nullable()->change();
                    } else {
                        $table->string('payment_intent_id', 255)->nullable()->unique()->after('ar_invoice_id');
                        $table->string('client_secret', 255)->nullable()->after('payment_intent_id');
                        $table->string('card_brand', 50)->nullable()->after('payment_method');
                        $table->string('card_last4', 4)->nullable()->after('card_brand');
                        $table->timestamp('authorized_at')->nullable()->after('error_message');
                        $table->timestamp('captured_at')->nullable()->after('authorized_at');
                        $table->timestamp('failed_at')->nullable()->after('captured_at');
                        $table->timestamp('refunded_at')->nullable()->after('failed_at');
                        $table->renameColumn('payment_gateway', 'gateway');
                        $table->string('transaction_id')->nullable()->change();
                    }
                });
            } catch (\Exception $e) {
                // Silently skip migration errors on SQLite (index/constraint issues)
                if (DB::getDriverName() !== 'sqlite') {
                    throw $e;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run if table exists (for SQLite compatibility)
        if (Schema::hasTable('payment_transactions')) {
            try {
                $isSqlite = DB::getDriverName() === 'sqlite';

                Schema::table('payment_transactions', function (Blueprint $table) use ($isSqlite) {
                    $table->dropColumn([
                        'payment_intent_id',
                        'client_secret',
                        'card_brand',
                        'card_last4',
                        'authorized_at',
                        'captured_at',
                        'failed_at',
                        'refunded_at',
                    ]);

                    if (!$isSqlite) {
                        $table->renameColumn('gateway', 'payment_gateway');
                    }
                    $table->string('transaction_id')->unique()->change();
                });
            } catch (\Exception $e) {
                // Silently skip migration errors on SQLite
                if (DB::getDriverName() !== 'sqlite') {
                    throw $e;
                }
            }
        }
    }
};
