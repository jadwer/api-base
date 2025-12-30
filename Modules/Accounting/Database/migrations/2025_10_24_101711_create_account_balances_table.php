<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("account_balances", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->comment('Future multi-tenancy support');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');
            $table->integer('fiscal_year');
            $table->integer('fiscal_month');
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('period_debits', 10, 2)->default(0);
            $table->decimal('period_credits', 10, 2)->default(0);
            $table->decimal('closing_balance', 10, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("account_balances");
    }
};