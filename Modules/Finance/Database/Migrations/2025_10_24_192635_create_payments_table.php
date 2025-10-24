<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("payments", function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->integer('customer_id');
            $table->integer('bank_account_id');
            $table->integer('payment_method_id');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('MXN');
            $table->decimal('applied_amount', 10, 2)->nullable()->default('0');
            $table->decimal('unapplied_amount', 10, 2)->nullable()->default('0');
            $table->string('status')->default('unapplied');
            $table->integer('journal_entry_id')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->nullable()->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("payments");
    }
};