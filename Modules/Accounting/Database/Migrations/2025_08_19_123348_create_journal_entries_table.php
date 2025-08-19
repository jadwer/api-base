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
            $table->foreignId('period_id')->constrained('fiscal_periods')->onDelete('restrict');
            $table->string('number')->nullable()->unique();
            $table->date('date');
            $table->string('currency')->nullable();
            $table->decimal('exchange_rate', 10, 2)->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->datetime('posted_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->onDelete('restrict');
            $table->string('source_type')->nullable();
            $table->bigInteger('source_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("journal_entries");
    }
};