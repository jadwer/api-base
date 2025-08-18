<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("contact_addresses", function (Blueprint $table) {
            $table->id();
            $table->integer('contact_id');
            $table->string('address_type')->default('billing');
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country')->default('MX');
            $table->string('postal_code')->nullable();
            $table->boolean('is_default')->nullable()->default();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("contact_addresses");
    }
};