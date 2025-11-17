<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PU-001: Add approval workflow fields to purchase_orders table
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Approval status (separate from order status)
            $table->enum('approval_status', ['not_required', 'pending', 'approved', 'rejected'])
                ->default('not_required')
                ->after('status');

            // Approval timestamp
            $table->timestamp('approved_at')->nullable()->after('approval_status');

            // Who approved it
            $table->foreignId('approved_by_id')->nullable()->after('approved_at');
            $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');

            // Metadata for approval history (JSON field)
            $table->json('metadata')->nullable()->after('notes');

            // Index for filtering approved/pending orders
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['approved_by_id']);
            $table->dropColumn(['approval_status', 'approved_at', 'approved_by_id', 'metadata']);
        });
    }
};
