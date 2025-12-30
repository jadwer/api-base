<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("exchange_rate_policies", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->comment('Future multi-tenancy support');
            $table->string('currency');
            $table->string('source')->default('manual');
            $table->string('scope')->default('company');
            $table->integer('max_age_days')->default(1);
            $table->decimal('tolerance_percentage', 10, 2)->default(0);
            $table->decimal('require_approval_over', 10, 2)->nullable();
            $table->boolean('is_active')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("exchange_rate_policies");
    }
};