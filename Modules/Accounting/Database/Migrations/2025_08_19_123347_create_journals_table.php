<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("journals", function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('auto_numbering')->default(1);
            $table->string('sequence_prefix')->nullable();
            $table->integer('sequence_next')->default(1);
            $table->string('default_currency')->nullable();
            $table->string('post_policy')->default('manual');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("journals");
    }
};