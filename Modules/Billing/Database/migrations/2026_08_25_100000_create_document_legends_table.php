<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_legends', function (Blueprint $table) {
            $table->id();
            // quote | sales_order | cfdi_invoice | remission (unique: una leyenda por tipo)
            $table->string('document_type')->unique();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_legends');
    }
};
