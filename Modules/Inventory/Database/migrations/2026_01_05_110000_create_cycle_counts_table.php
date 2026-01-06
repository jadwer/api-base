<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IV-M001: Cycle Count Scheduling
 *
 * Cycle counts are periodic physical inventory counts used to verify
 * stock accuracy without stopping operations (unlike full inventory).
 *
 * ABC Analysis Integration:
 * - A items (high value): count monthly
 * - B items (medium value): count quarterly
 * - C items (low value): count annually
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_counts', function (Blueprint $table) {
            $table->id();

            // Reference number for the count
            $table->string('count_number')->unique();

            // What we're counting
            $table->foreignId('warehouse_id')->constrained()->onDelete('restrict');
            $table->foreignId('warehouse_location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('restrict');

            // Schedule info
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();

            // Status workflow: scheduled -> in_progress -> completed/cancelled
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])
                  ->default('scheduled');

            // Count results
            $table->decimal('system_quantity', 15, 4)->nullable();
            $table->decimal('counted_quantity', 15, 4)->nullable();
            $table->decimal('variance_quantity', 15, 4)->nullable();
            $table->decimal('variance_value', 15, 2)->nullable();

            // Who performed the count
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('counted_by')->nullable()->constrained('users')->onDelete('set null');

            // ABC classification for scheduling priority
            $table->enum('abc_class', ['A', 'B', 'C'])->nullable();

            // Adjustment reference (if variance was adjusted)
            $table->foreignId('adjustment_movement_id')->nullable();

            // Notes and metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index('scheduled_date');
            $table->index('status');
            $table->index(['warehouse_id', 'status']);
            $table->index(['product_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_counts');
    }
};
