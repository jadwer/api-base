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
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict'); // Consolidado: era customer_id
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('MXN');
            $table->decimal('applied_amount', 10, 2)->nullable()->default('0');
            $table->decimal('unapplied_amount', 10, 2)->nullable()->default('0');
            $table->string('status')->default('unapplied');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->nullable()->default(1);
            $table->timestamps();

            // Performance indexes
            $table->index('contact_id');
            $table->index('payment_date');
            $table->index('status');
            $table->index('bank_account_id');
            $table->index(['contact_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("payments");
    }
};