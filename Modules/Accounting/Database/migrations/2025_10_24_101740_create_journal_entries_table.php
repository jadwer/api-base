<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("journal_entries", function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->onDelete('restrict');
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->onDelete('restrict');
            $table->string('number')->nullable()->unique();
            $table->date('date');
            $table->date('accounting_date')->nullable(); // Consolidado
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_debit', 10, 2)->default(0);
            $table->decimal('total_credit', 10, 2)->default(0);
            $table->unsignedBigInteger('company_id')->nullable()->comment('Future multi-tenancy support');
            $table->string('status')->default('draft');
            $table->datetime('approved_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->datetime('posted_at')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->onDelete('restrict');
            $table->text('reversal_reason')->nullable();
            // Additional reversal fields
            $table->boolean('is_reversal')->default(false);
            $table->foreignId('reverses_entry_id')->nullable()->constrained('journal_entries')->onDelete('restrict');
            // Source tracking (polymorphic)
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('fiscal_period_id');
            $table->index('status');
            $table->index('date');
            $table->index(['fiscal_period_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("journal_entries");
    }
};