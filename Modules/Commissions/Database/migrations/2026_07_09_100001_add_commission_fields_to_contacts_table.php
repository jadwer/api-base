<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS5 Commissions + WS7.1 Bind fields for contacts.
 *
 * All columns are nullable: existing rows keep working untouched.
 * credit_months is credit expressed in months (payment_terms in days is kept
 * for backwards compatibility).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Commissions (WS5)
            $table->foreignId('default_salesperson_id')->nullable()
                ->after('payment_terms')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('collections_agent_id')->nullable()
                ->after('default_salesperson_id')
                ->constrained('users')->nullOnDelete();
            $table->decimal('commission_pct_override', 5, 2)->nullable()
                ->after('collections_agent_id');

            // Bind fields (WS7.1)
            $table->string('regimen_fiscal', 4)->nullable()->after('commission_pct_override');
            $table->string('uso_cfdi', 4)->nullable()->after('regimen_fiscal');
            $table->unsignedTinyInteger('credit_months')->nullable()->after('uso_cfdi');
            $table->string('bank_account_number', 34)->nullable()->after('credit_months');
            $table->string('referral_source')->nullable()->after('bank_account_number');
            $table->string('cuenta_contable')->nullable()->after('referral_source');
            $table->decimal('discount_pct', 5, 2)->nullable()->after('cuenta_contable');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_salesperson_id');
            $table->dropConstrainedForeignId('collections_agent_id');
            $table->dropColumn([
                'commission_pct_override',
                'regimen_fiscal',
                'uso_cfdi',
                'credit_months',
                'bank_account_number',
                'referral_source',
                'cuenta_contable',
                'discount_pct',
            ]);
        });
    }
};
