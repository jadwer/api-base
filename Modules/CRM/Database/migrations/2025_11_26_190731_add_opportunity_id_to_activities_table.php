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
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('opportunity_id')
                ->nullable()
                ->after('lead_id')
                ->constrained('opportunities')
                ->onDelete('cascade')
                ->comment('Related opportunity');

            // Add index for performance
            $table->index(['opportunity_id', 'activity_type'], 'activities_opportunity_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['opportunity_id']);
            $table->dropIndex('activities_opportunity_type_index');
            $table->dropColumn('opportunity_id');
        });
    }
};
