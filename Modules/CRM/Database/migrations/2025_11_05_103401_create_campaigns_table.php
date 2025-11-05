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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['email', 'social_media', 'event', 'webinar', 'direct_mail', 'telemarketing'])->default('email');
            $table->enum('status', ['planning', 'active', 'paused', 'completed', 'cancelled'])->default('planning');

            // Relationships
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('Campaign owner');

            // Campaign timeline
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Financial tracking
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->decimal('expected_revenue', 15, 2)->nullable();
            $table->decimal('actual_revenue', 15, 2)->nullable();

            // Campaign details
            $table->string('target_audience')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable()->comment('Additional campaign data');

            $table->timestamps();

            // Indexes for filtering and sorting
            $table->index(['status', 'start_date']);
            $table->index('type');
            $table->index(['user_id', 'status']);
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
