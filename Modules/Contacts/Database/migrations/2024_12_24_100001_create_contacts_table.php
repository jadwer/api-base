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
            $table->enum('credit_status', ['active', 'hold', 'blocked'])->default('active'); // Consolidado
            $table->timestamp('credit_hold_at')->nullable(); // Consolidado
            $table->text('credit_hold_reason')->nullable(); // Consolidado
            $table->decimal('minimum_payment_score', 5, 2)->default(60.00); // Consolidado
            $table->decimal('current_credit', 10, 2)->nullable()->default(0);
            $table->string('classification')->nullable();
            $table->integer('payment_terms')->nullable()->default(30);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('status');
            $table->index('is_customer');
            $table->index('is_supplier');
            $table->index(['is_customer', 'status']);
            $table->index(['is_supplier', 'status']);
            $table->index('type');
            $table->index('classification');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("contacts");
    }
};