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
        Schema::create('critical_action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id')->nullable();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('action');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('changes_snapshot')->nullable();
            $table->json('model_snapshot')->nullable();
            $table->boolean('requires_retention')->default(true);
            $table->integer('retention_years')->default(7);
            $table->string('verification_hash');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['model_type', 'model_id']);
            $table->index('activity_id');
            $table->index('action');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('critical_action_logs');
    }
};
