<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("contact_documents", function (Blueprint $table) {
            $table->id();
            $table->integer('contact_id');
            $table->string('document_type');
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->integer('uploaded_by')->nullable();
            $table->date('verified_at')->nullable();
            $table->integer('verified_by')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("contact_documents");
    }
};