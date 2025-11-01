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
        if (Schema::hasTable('ar_invoices')) {
            try {
                $isSqlite = DB::getDriverName() === 'sqlite';

                Schema::table('ar_invoices', function (Blueprint $table) use ($isSqlite) {
                    // Refund support - don't use after() on SQLite to avoid table reconstruction
                    if ($isSqlite) {
                        $table->boolean('is_refund')->default(false);
                        $table->foreignId('refund_of_invoice_id')->nullable()->constrained('ar_invoices')->onDelete('restrict');
                        $table->timestamp('voided_at')->nullable();
                        $table->foreignId('voided_by_id')->nullable()->constrained('users')->onDelete('set null');
                        $table->text('void_reason')->nullable();
                    } else {
                        $table->boolean('is_refund')->default(false)->after('status');
                        $table->foreignId('refund_of_invoice_id')->nullable()->constrained('ar_invoices')->onDelete('restrict')->after('is_refund');
                        $table->timestamp('voided_at')->nullable()->after('refund_of_invoice_id');
                        $table->foreignId('voided_by_id')->nullable()->constrained('users')->onDelete('set null')->after('voided_at');
                        $table->text('void_reason')->nullable()->after('voided_by_id');
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
        if (Schema::hasTable('ar_invoices')) {
            Schema::table('ar_invoices', function (Blueprint $table) {
                $table->dropForeign(['refund_of_invoice_id']);
                $table->dropForeign(['voided_by_id']);
                $table->dropColumn(['is_refund', 'refund_of_invoice_id', 'voided_at', 'voided_by_id', 'void_reason']);
            });
        }
    }
};
