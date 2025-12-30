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
        Schema::disableForeignKeyConstraints();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 400);
            $table->string('sku', 50)->nullable()->unique();
            $table->longText('description');
            $table->longText('full_description');
            $table->float('price')->nullable();
            $table->float('cost')->nullable();
            $table->boolean('iva')->default(false);
            $table->string('img_path', 400)->nullable();
            $table->string('datasheet_path', 400)->nullable();
            $table->foreignId('unit_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('brand_id')->constrained();
            $table->boolean('is_active')->default(true); // Consolidado
            $table->float('average_rating')->nullable(); // Consolidado
            $table->integer('total_reviews')->default(0); // Consolidado
            $table->integer('total_sales')->default(0); // Consolidado
            $table->timestamps();

            // Performance indexes
            $table->index('category_id');
            $table->index('brand_id');
            $table->index('unit_id');
        });

        Schema::enableForeignKeyConstraints();
    }    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
