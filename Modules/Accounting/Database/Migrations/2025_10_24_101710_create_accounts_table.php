<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("accounts", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->comment('Future multi-tenancy support');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('account_type');
            $table->string('nature')->default('debit');
            $table->integer('level')->default(1);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->string('currency')->default('MXN');
            $table->boolean('is_postable')->default(1);
            $table->boolean('is_cash_flow')->default(false);
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("accounts");
    }
};