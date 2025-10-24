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
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_debit', 10, 2)->default(0);
            $table->decimal('total_credit', 10, 2)->default(0);
            $table->foreignId('company_id')->constrained('companies')->onDelete('restrict');
            $table->string('status')->default('draft');
            $table->datetime('approved_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('approved_bies')->onDelete('restrict');
            $table->datetime('posted_at')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('posted_bies')->onDelete('restrict');
            $table->foreignId('reversal_of_id')->nullable()->constrained('reversal_ofs')->onDelete('restrict');
            $table->text('reversal_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("journal_entries");
    }
};