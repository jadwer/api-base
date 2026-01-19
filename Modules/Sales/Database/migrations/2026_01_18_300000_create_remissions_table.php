<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SA-M006: Remission documents for sales orders
     *
     * A remission (remisión) is a delivery document that accompanies merchandise
     * when shipped to the customer. It's NOT a fiscal document but serves as:
     * - Proof of delivery
     * - Inventory control document
     * - Customer acknowledgment receipt
     */
    public function up(): void
    {
        Schema::create('remissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();

            // Document identification
            $table->string('remission_number', 50)->unique();
            $table->enum('status', ['draft', 'printed', 'delivered', 'cancelled'])->default('draft');

            // Dates
            $table->date('remission_date');
            $table->date('delivery_date')->nullable();

            // Delivery information
            $table->string('delivered_by')->nullable();
            $table->string('received_by')->nullable();
            $table->text('delivery_notes')->nullable();

            // Address (can override order shipping address)
            $table->json('shipping_address')->nullable();

            // Document generation
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            // Internal notes
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['sales_order_id', 'status']);
            $table->index('remission_date');
        });

        Schema::create('remission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Quantities
            $table->decimal('quantity', 15, 4);

            // Snapshot data (denormalized for PDF generation)
            $table->string('product_name')->nullable();
            $table->string('product_sku')->nullable();
            $table->string('unit')->nullable()->default('PZA');
            $table->text('notes')->nullable();

            // Lot/batch tracking if applicable
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['remission_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remission_items');
        Schema::dropIfExists('remissions');
    }
};
