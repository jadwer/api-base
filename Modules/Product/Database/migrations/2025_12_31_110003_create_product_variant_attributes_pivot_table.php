<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PR-M003: Pivot table linking product variants to their attribute values.
 *
 * A variant "Product X - Red - Large" would have two entries here:
 * - product_variant_id -> variant_attribute_value_id (Red)
 * - product_variant_id -> variant_attribute_value_id (Large)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->onDelete('cascade');
            $table->foreignId('variant_attribute_value_id')
                ->constrained('variant_attribute_values')
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_variant_id', 'variant_attribute_value_id'], 'pva_unique');
            $table->index('product_variant_id');
            $table->index('variant_attribute_value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attributes');
    }
};
