<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("bank_statement_lines", function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained('bank_statements')->onDelete('restrict');
            $table->date('txn_date');
            $table->decimal('amount', 10, 2);
            $table->string('counterparty')->nullable();
            $table->string('reference')->nullable();
            $table->string('fitid')->nullable();
            $table->string('status')->default('unreconciled');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("bank_statement_lines");
    }
};