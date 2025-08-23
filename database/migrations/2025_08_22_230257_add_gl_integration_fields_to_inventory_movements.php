<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add GL integration fields to inventory_movements
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->bigInteger('gl_journal_entry_id')->unsigned()->nullable()->after('metadata');
            $table->enum('gl_posting_status', ['pending', 'posted', 'error'])->default('pending')->after('gl_journal_entry_id');
            $table->decimal('cost_per_unit', 10, 4)->default(0.0000)->after('gl_posting_status');
            $table->decimal('total_cost', 10, 2)->default(0.00)->after('cost_per_unit');
            $table->text('gl_posting_notes')->nullable()->after('total_cost');
            
            $table->index(['gl_journal_entry_id']);
            $table->index(['gl_posting_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex(['gl_journal_entry_id']);
            $table->dropIndex(['gl_posting_status']);
            $table->dropColumn([
                'gl_journal_entry_id', 
                'gl_posting_status', 
                'cost_per_unit', 
                'total_cost',
                'gl_posting_notes'
            ]);
        });
    }
};
