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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Financial details
            $table->decimal('amount', 15, 2)->comment('Deal value');
            $table->integer('probability')->default(0)->comment('Win probability 0-100');
            $table->decimal('expected_revenue', 15, 2)->nullable()->comment('amount * probability / 100');
            $table->decimal('actual_revenue', 15, 2)->nullable()->comment('Actual revenue when closed');

            // Dates
            $table->date('close_date')->comment('Expected close date');

            // Status and pipeline
            $table->enum('status', ['open', 'won', 'lost', 'abandoned'])->default('open');
            $table->string('stage')->default('qualification')->comment('Current sales stage');
            $table->enum('forecast_category', ['pipeline', 'best_case', 'commit', 'closed'])->default('pipeline');

            // Relationships
            $table->foreignId('lead_id')->nullable()->constrained('leads')->onDelete('set null')->comment('Original lead');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('Opportunity owner/sales rep');
            $table->foreignId('pipeline_stage_id')->nullable()->constrained('pipeline_stages')->onDelete('set null')->comment('Current pipeline stage');
            $table->foreignId('contact_id')->nullable()->comment('Will be constrained when Contact module is implemented');

            // Additional details
            $table->string('source')->nullable()->comment('Original source from lead');
            $table->string('next_step')->nullable()->comment('Next action to take');
            $table->string('loss_reason')->nullable()->comment('Reason for loss if status=lost');

            // Win/Loss tracking
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['pipeline_stage_id', 'status']);
            $table->index('close_date');
            $table->index(['forecast_category', 'close_date']);
            $table->index('won_at');
            $table->index('lost_at');
        });

        // Add FK from activities to opportunities (consolidado desde add_opportunity_id_to_activities_table)
        Schema::table('activities', function (Blueprint $table) {
            $table->foreign('opportunity_id')
                ->references('id')
                ->on('opportunities')
                ->onDelete('cascade');
            $table->index(['opportunity_id', 'activity_type'], 'activities_opportunity_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove FK from activities first (consolidado)
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['opportunity_id']);
            $table->dropIndex('activities_opportunity_type_index');
        });

        Schema::dropIfExists('opportunities');
    }
};
