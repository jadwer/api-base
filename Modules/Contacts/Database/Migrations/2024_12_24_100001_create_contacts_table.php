<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("contacts", function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('company');
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_customer')->nullable()->default();
            $table->boolean('is_supplier')->nullable()->default();
            $table->decimal('credit_limit', 10, 2)->nullable()->default(0);
            $table->decimal('current_credit', 10, 2)->nullable()->default(0);
            $table->string('classification')->nullable();
            $table->integer('payment_terms')->nullable()->default(30);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("contacts");
    }
};