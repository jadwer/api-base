<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("account_mappings", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->comment('Future multi-tenancy support');
            $table->string('mapping_type');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');
            $table->integer('version')->default(1);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(1);
            $table->foreignId('created_by_id')->constrained('users')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("account_mappings");
    }
};