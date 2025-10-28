<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            // Add foreign key for reversal relationship (other reversal fields already exist)
            if (!Schema::hasColumn('journal_entries', 'reverses_entry_id')) {
                $table->foreignId('reverses_entry_id')->nullable()->constrained('journal_entries')->onDelete('restrict')->after('is_reversal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'reverses_entry_id')) {
                $table->dropForeign(['reverses_entry_id']);
                $table->dropColumn('reverses_entry_id');
            }
        });
    }
};
