<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_emails', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('module', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('mailable_class', 500);
            $table->json('available_variables');
            $table->json('sample_data')->nullable();
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->string('default_subject', 255)->nullable();
            $table->timestamps();

            $table->index('module');
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_emails');
    }
};
