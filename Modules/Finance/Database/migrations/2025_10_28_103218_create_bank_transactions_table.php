<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('bank_account_id')
                ->constrained('bank_accounts')
                ->onDelete('restrict');

            // Transaction Details
            $table->date('transaction_date');
            $table->decimal('amount', 15, 2);
            $table->string('transaction_type'); // debit or credit
            $table->string('reference')->nullable();
            $table->text('description')->nullable();

            // Reconciliation
            $table->string('reconciliation_status')->default('unreconciled'); // unreconciled, reconciled, pending
            $table->foreignId('reconciled_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reconciled_at')->nullable();
            $table->text('reconciliation_notes')->nullable();

            // Bank Statement Info
            $table->string('statement_number')->nullable();
            $table->decimal('running_balance', 15, 2)->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('bank_account_id');
            $table->index('transaction_date');
            $table->index('reconciliation_status');
            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['bank_account_id', 'reconciliation_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
