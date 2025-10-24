<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("audit_logs", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->comment('Future multi-tenancy support');
            $table->string('model_type');
            $table->bigInteger('model_id');
            $table->string('action');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('payload_hash');
            $table->boolean('requires_retention')->default(false);
            $table->datetime('retention_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("audit_logs");
    }
};