<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diseno editado de cada documento (editor visual de LWM, 2026-11-12). Una fila
 * por tipo; sin fila, el documento usa su preajuste de fabrica. El layout va en
 * el formato v1 de atomo/documents (congelado), asi que al migrar a Atomo se
 * copia tal cual a su tabla de preajustes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 40)->unique();
            $table->json('layout');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_layouts');
    }
};
