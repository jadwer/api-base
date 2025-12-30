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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict');
            $table->string('order_number')->unique();
            $table->enum('status', [
                'draft', 'pending', 'confirmed', 'processing',
                'shipped', 'delivered', 'completed',
                'cancelled', 'returned', 'refunded'
            ])->default('draft'); // Consolidado: 10 estados
            $table->date('order_date');
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Ecommerce fields (consolidado)
            $table->enum('order_source', ['ecommerce', 'manual', 'api', 'import'])->default('manual');
            $table->unsignedBigInteger('checkout_session_id')->nullable(); // FK agregada en migración separada
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();

            // Finance integration (consolidado)
            $table->unsignedBigInteger('ar_invoice_id')->nullable(); // FK agregada en migración separada
            $table->enum('invoicing_status', ['not_invoiced', 'partial', 'invoiced'])->default('not_invoiced');
            $table->string('financial_status')->default('not_invoiced');
            $table->text('invoicing_notes')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index('checkout_session_id');
            $table->index('order_source');
            $table->index('tracking_number');
            $table->index('financial_status');
            $table->index('contact_id');
            $table->index('status');
            $table->index('order_date');
            $table->index(['contact_id', 'status']);
            $table->index('invoicing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
