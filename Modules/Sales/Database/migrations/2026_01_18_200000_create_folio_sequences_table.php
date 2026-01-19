<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Folio sequences for configurable document numbering.
     * Supports: quotes, sales_orders, purchase_orders, invoices, etc.
     */
    public function up(): void
    {
        Schema::create('folio_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50)->unique(); // quote, sales_order, purchase_order, invoice, etc.
            $table->string('prefix', 20)->default(''); // COT, OV, OC, FAC, FAC-W, REFAC, etc.
            $table->boolean('include_year')->default(true); // Include year in folio
            $table->string('year_format', 4)->default('y'); // 'y' for 26, 'Y' for 2026
            $table->string('separator', 5)->default(''); // Separator between prefix and number (empty, -, /)
            $table->integer('padding')->default(6); // Number of digits (000001)
            $table->bigInteger('current_sequence')->default(0); // Current sequence number
            $table->boolean('reset_yearly')->default(false); // Reset sequence each year
            $table->integer('last_reset_year')->nullable(); // Year of last reset
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['document_type', 'is_active']);
        });

        // Insert default configurations
        DB::table('folio_sequences')->insert([
            [
                'document_type' => 'quote',
                'prefix' => 'COT-',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '',
                'padding' => 6,
                'current_sequence' => 0,
                'reset_yearly' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'document_type' => 'sales_order',
                'prefix' => 'OV-',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '',
                'padding' => 6,
                'current_sequence' => 0,
                'reset_yearly' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'document_type' => 'purchase_order',
                'prefix' => 'OC-',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '',
                'padding' => 6,
                'current_sequence' => 0,
                'reset_yearly' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'document_type' => 'invoice',
                'prefix' => 'FAC',
                'include_year' => false,
                'year_format' => 'y',
                'separator' => '',
                'padding' => 6,
                'current_sequence' => 0,
                'reset_yearly' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'document_type' => 'invoice_online',
                'prefix' => 'FAC-W',
                'include_year' => false,
                'year_format' => 'y',
                'separator' => '',
                'padding' => 6,
                'current_sequence' => 0,
                'reset_yearly' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'document_type' => 'invoice_refac',
                'prefix' => 'REFAC',
                'include_year' => false,
                'year_format' => 'y',
                'separator' => '',
                'padding' => 6,
                'current_sequence' => 0,
                'reset_yearly' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folio_sequences');
    }
};
