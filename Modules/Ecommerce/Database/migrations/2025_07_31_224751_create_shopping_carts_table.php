<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("shopping_carts", function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('status');
            $table->datetime('expires_at')->nullable();
            $table->decimal('total_amount');
            $table->string('currency');
            $table->string('coupon_code')->nullable();
            $table->decimal('discount_amount')->nullable()->default(0);
            $table->decimal('tax_amount')->nullable()->default(0);
            $table->decimal('shipping_amount')->nullable()->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("shopping_carts");
    }
};