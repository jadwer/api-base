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
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('location_type', ['aisle', 'rack', 'shelf', 'bin', 'zone', 'bay'])->default('bin');
            $table->string('aisle')->nullable();
            $table->string('rack')->nullable();
            $table->string('shelf')->nullable();
            $table->string('level')->nullable();
            $table->string('position')->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->decimal('max_volume', 10, 2)->nullable();
            $table->string('dimensions')->nullable(); // "LxWxH"
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->integer('priority')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['warehouse_id', 'is_active']);
            $table->index(['warehouse_id', 'location_type']);
            $table->index(['aisle', 'rack', 'shelf']);
            $table->index('barcode');
            $table->unique(['warehouse_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};