<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fractionations', function (Blueprint $table) {
            $table->id();
            $table->string('folio_number')->unique();
            $table->foreignId('source_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('destination_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_conversion_id')->nullable()->constrained('product_conversions')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('source_quantity', 15, 4);
            $table->decimal('produced_quantity', 15, 4);
            $table->decimal('waste_percentage', 5, 2)->default(0);
            $table->decimal('waste_quantity', 15, 4)->default(0);
            $table->decimal('conversion_factor_used', 15, 4);
            $table->foreignId('exit_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->foreignId('entry_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            $table->text('notes')->nullable();
            $table->dateTime('executed_at')->nullable();
            $table->timestamps();

            $table->index(['source_product_id', 'executed_at']);
            $table->index(['warehouse_id', 'executed_at']);
            $table->index('status');
            $table->index('user_id');
        });

        // Insert FolioSequence for fractionation (idempotent)
        DB::table('folio_sequences')->insertOrIgnore([
            'document_type' => 'fractionation',
            'prefix' => 'FRAC',
            'include_year' => true,
            'year_format' => 'y',
            'separator' => '-',
            'padding' => 6,
            'current_sequence' => 0,
            'reset_yearly' => false,
            'last_reset_year' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fractionations');
        DB::table('folio_sequences')->where('document_type', 'fractionation')->delete();
    }
};
