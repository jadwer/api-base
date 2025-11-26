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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            // Activity type and content
            $table->enum('activity_type', ['call', 'email', 'meeting', 'note', 'task'])->default('note');
            $table->string('subject');
            $table->text('description')->nullable();

            // Activity timing
            $table->timestamp('activity_date')->nullable()->comment('When the activity occurred/is scheduled');
            $table->integer('duration')->nullable()->comment('Duration in minutes');

            // Outcome and status
            $table->string('outcome')->nullable()->comment('Result of the activity');
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'pending'])->default('pending');

            // Relationships
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('User who performed/owns the activity');
            $table->foreignId('lead_id')->nullable()->constrained('leads')->onDelete('cascade')->comment('Related lead');
            $table->foreignId('contact_id')->nullable()->comment('Related contact - will be constrained when Contact module is implemented');
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('set null')->comment('Related campaign');

            // Additional info
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['activity_type', 'activity_date']);
            $table->index(['user_id', 'activity_date']);
            $table->index(['lead_id', 'activity_type']);
            $table->index(['campaign_id', 'activity_type']);
            $table->index(['status', 'activity_date']);
            $table->index('activity_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
