<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CO-M001: Add indexes for duplicate detection
 *
 * - Unique index on tax_id (RFC) to prevent duplicates
 * - Index on email for fast lookup
 * - Index on name for similarity searches
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Unique index on tax_id - prevents duplicate RFC
            // Note: Using unique index instead of constraint to allow NULL values
            $table->unique('tax_id', 'contacts_tax_id_unique');

            // Index on email for duplicate detection
            $table->index('email', 'contacts_email_index');

            // Index on name for similarity searches
            $table->index('name', 'contacts_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique('contacts_tax_id_unique');
            $table->dropIndex('contacts_email_index');
            $table->dropIndex('contacts_name_index');
        });
    }
};
