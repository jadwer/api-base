<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename 'type' column to 'contact_type' to avoid collision with
     * JSON:API reserved keyword 'type' (resource type).
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->renameColumn('type', 'contact_type');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->renameColumn('contact_type', 'type');
        });
    }
};
