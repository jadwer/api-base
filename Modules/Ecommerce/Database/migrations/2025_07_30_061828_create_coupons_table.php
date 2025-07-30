<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("coupons", function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->decimal('value');
            $table->decimal('min_amount')->nullable();
            $table->decimal('max_amount')->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('used_count');
            $table->datetime('starts_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->boolean('is_active');
            $table->json('customer_ids')->nullable();
            $table->json('product_ids')->nullable();
            $table->json('category_ids')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("coupons");
    }
};