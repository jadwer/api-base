<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PR-M003: Variant Attribute Values table migration.
 *
 * Stores specific values for each attribute: Small, Medium, Large for Size.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_attribute_id')
                ->constrained('variant_attributes')
                ->onDelete('cascade');
            $table->string('value', 100);          // Small, Medium, Large
            $table->string('code', 50);            // s, m, l
            $table->string('display_value', 100)->nullable(); // "S", "M", "L"
            $table->string('color_hex', 7)->nullable();       // #FF0000 for color swatches
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['variant_attribute_id', 'code']);
            $table->index('variant_attribute_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_attribute_values');
    }
};
