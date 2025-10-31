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
        Schema::create('cfdi_items', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('cfdi_invoice_id')->constrained('cfdi_invoices')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('restrict'); // Optional product reference

            // Item Information
            $table->unsignedInteger('numero_linea'); // Line number (1, 2, 3...)
            $table->string('clave_prod_serv', 20); // SAT Product/Service code
            $table->string('clave_unidad', 10)->default('ACT'); // SAT Unit code (ACT, KGM, E48, etc)
            $table->string('unidad', 50)->nullable(); // Unit description
            $table->decimal('cantidad', 18, 6); // Quantity
            $table->text('descripcion'); // Item description
            $table->string('no_identificacion', 100)->nullable(); // SKU or internal code

            // Prices (stored as integers in cents/centavos)
            $table->unsignedBigInteger('valor_unitario'); // Unit price
            $table->unsignedBigInteger('importe'); // Amount (quantity * unit price)
            $table->unsignedBigInteger('descuento')->default(0); // Discount amount

            // Taxes
            $table->json('impuestos')->nullable(); // Taxes array (traslados y retenciones)
            $table->string('objeto_imp', 2)->default('02'); // 01=No objeto, 02=Sí objeto, 03=Sí objeto no obligado, 04=Sí objeto exento

            // Optional fields for customs/international trade
            $table->string('numero_pedimento')->nullable(); // Customs number
            $table->json('cuenta_predial')->nullable(); // Property account numbers
            $table->json('informacion_aduanera')->nullable(); // Customs information

            // Additional data
            $table->json('metadata')->nullable(); // Additional metadata

            $table->timestamps();

            // Indexes
            $table->index('cfdi_invoice_id');
            $table->index('product_id');
            $table->index('numero_linea');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfdi_items');
    }
};
