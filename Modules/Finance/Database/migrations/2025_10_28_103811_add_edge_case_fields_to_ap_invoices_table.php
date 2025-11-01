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
        if (Schema::hasTable('ap_invoices')) {
            try {
                $isSqlite = DB::getDriverName() === 'sqlite';

                Schema::table('ap_invoices', function (Blueprint $table) use ($isSqlite) {
                    // Don't use after() on SQLite to avoid table reconstruction
                    if ($isSqlite) {
                        $table->timestamp('voided_at')->nullable();
                        $table->foreignId('voided_by_id')->nullable()->constrained('users')->onDelete('set null');
                        $table->text('void_reason')->nullable();
                        $table->foreignId('replaces_invoice_id')->nullable()->constrained('ap_invoices')->onDelete('restrict');
                    } else {
                        $table->timestamp('voided_at')->nullable()->after('status');
                        $table->foreignId('voided_by_id')->nullable()->constrained('users')->onDelete('set null')->after('voided_at');
                        $table->text('void_reason')->nullable()->after('voided_by_id');
                        $table->foreignId('replaces_invoice_id')->nullable()->constrained('ap_invoices')->onDelete('restrict')->after('void_reason');
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
        if (Schema::hasTable('ap_invoices')) {
            try {
                Schema::table('ap_invoices', function (Blueprint $table) {
                    $table->dropForeign(['voided_by_id']);
                    $table->dropForeign(['replaces_invoice_id']);
                    $table->dropColumn(['voided_at', 'voided_by_id', 'void_reason', 'replaces_invoice_id']);
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
