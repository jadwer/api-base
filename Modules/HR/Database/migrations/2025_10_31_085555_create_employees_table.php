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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignId('position_id')->constrained('positions')->onDelete('restrict');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('employee_code', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->unique();
            $table->string('phone', 50)->nullable();
            $table->date('hire_date');
            $table->date('birth_date')->nullable();
            $table->decimal('salary', 12, 2);
            $table->enum('status', ['active', 'on_leave', 'suspended', 'terminated'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index('department_id');
            $table->index('position_id');
            $table->index('user_id');
            $table->index('employee_code');
            $table->index('email');
            $table->index('status');
            $table->index('hire_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
