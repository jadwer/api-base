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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('source')->nullable()->comment('website, referral, cold call, etc.');
            $table->enum('status', ['new', 'contacted', 'qualified', 'unqualified', 'converted'])->default('new');
            $table->enum('rating', ['hot', 'warm', 'cold'])->default('warm');

            // Relationships
            $table->foreignId('contact_id')->nullable()->comment('Will be constrained when Contact module is implemented');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('Assigned sales rep');

            // Lead details
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Business info
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->date('estimated_close_date')->nullable();
            $table->timestamp('converted_at')->nullable();

            // Additional info
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for filtering and sorting
            $table->index(['status', 'created_at']);
            $table->index(['rating', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('email');
            $table->index('converted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
