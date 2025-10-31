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
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('employees')->onDelete('restrict');
            $table->date('review_date');
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->integer('overall_rating')->default(0); // 1-5 scale
            $table->integer('goals_rating')->default(0);
            $table->integer('skills_rating')->default(0);
            $table->integer('attendance_rating')->default(0);
            $table->text('comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'acknowledged'])->default('draft');
            $table->timestamps();

            // Indexes
            $table->index('employee_id');
            $table->index('reviewer_id');
            $table->index('status');
            $table->index('review_date');
            $table->index(['employee_id', 'review_date']); // Composite for employee review history
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
