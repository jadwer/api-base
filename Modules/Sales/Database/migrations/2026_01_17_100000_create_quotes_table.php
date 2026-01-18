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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict');
            $table->foreignId('shopping_cart_id')->nullable()->constrained('shopping_carts')->onDelete('set null');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->onDelete('set null');
            $table->string('quote_number')->unique();
            $table->enum('status', [
                'draft',      // Creada, no enviada
                'sent',       // Enviada al cliente
                'accepted',   // Cliente aceptó
                'rejected',   // Cliente rechazó
                'expired',    // Pasó valid_until
                'converted',  // Convertida a SalesOrder
                'cancelled'   // Cancelada
            ])->default('draft');
            $table->date('quote_date');
            $table->date('valid_until')->nullable();
            $table->string('estimated_eta')->nullable(); // ETA estimado de entrega
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('MXN');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable(); // Notas internas (no visibles para cliente)
            $table->text('terms_and_conditions')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('converted_at')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('contact_id');
            $table->index('shopping_cart_id');
            $table->index('sales_order_id');
            $table->index('status');
            $table->index('quote_date');
            $table->index('valid_until');
            $table->index(['contact_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
