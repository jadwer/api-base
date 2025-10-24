<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("fiscal_periods", function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('year');
            $table->integer('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('open');
            $table->datetime('closed_at')->nullable();
            $table->foreignId('closed_by_id')->nullable()->constrained('closed_bies')->onDelete('restrict');
            $table->foreignId('closing_entry_id')->nullable()->constrained('closing_entries')->onDelete('restrict');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("fiscal_periods");
    }
};