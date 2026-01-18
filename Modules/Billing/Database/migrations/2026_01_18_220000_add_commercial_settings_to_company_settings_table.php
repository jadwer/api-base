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
        Schema::table('company_settings', function (Blueprint $table) {
            // Company Contact Information
            $table->string('address')->nullable()->after('logo_path');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('phone')->nullable()->after('state');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');

            // Commercial Settings
            $table->json('bank_accounts')->nullable()->after('website')
                ->comment('Array of bank account information for payments');
            $table->json('commercial_conditions')->nullable()->after('bank_accounts')
                ->comment('Array of commercial conditions/terms for quotes');
            $table->json('quote_settings')->nullable()->after('commercial_conditions')
                ->comment('Settings specific to quote generation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'city',
                'state',
                'phone',
                'email',
                'website',
                'bank_accounts',
                'commercial_conditions',
                'quote_settings',
            ]);
        });
    }
};
