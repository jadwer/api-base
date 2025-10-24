<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("bank_accounts", function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->unique();
            $table->string('account_name');
            $table->string('bank_name');
            $table->string('currency')->default('MXN');
            $table->integer('gl_account_id');
            $table->decimal('current_balance', 10, 2)->nullable()->default('0');
            $table->decimal('opening_balance', 10, 2)->nullable()->default('0');
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->nullable()->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("bank_accounts");
    }
};